<?php
/**
 * Archivo: includes/mc-viewer.php
 * Descripción: Visor que muestra, por categoría, un "plugin virtual" con todos los snippets.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Interceptar descarga ZIP en admin_init (antes de cualquier output)
 */
add_action( 'admin_init', function() {

    if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'mc-viewer' ) return;
    if ( ! isset( $_GET['download'] ) || $_GET['download'] !== 'zip' ) return;
    if ( ! isset( $_GET['cat_id'] ) ) return;
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );

    $cat_id = intval( $_GET['cat_id'] );
    $cat    = get_term( $cat_id, 'mc_categoria' );

    if ( ! $cat || is_wp_error( $cat ) ) wp_die( 'Categoría no encontrada.' );

    $snippets = get_posts([
        'post_type'   => 'mc_snippet',
        'numberposts' => -1,
        'tax_query'   => [[
            'taxonomy' => 'mc_categoria',
            'field'    => 'term_id',
            'terms'    => $cat->term_id,
        ]],
    ]);

    if ( empty( $snippets ) ) wp_die( 'Esta categoría no tiene snippets.' );

    $slug        = $cat->slug;
    $plugin_code = mc_build_category_plugin_code( $cat, $snippets );

    $tmp_dir  = get_temp_dir();
    $zip_path = $tmp_dir . $slug . '-' . time() . '.zip';

    if ( ! class_exists( 'ZipArchive' ) ) wp_die( 'ZipArchive no está disponible en este servidor.' );

    $zip = new ZipArchive();
    $result = $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

    if ( $result !== true ) wp_die( 'Error al crear el ZIP. Código: ' . $result );

    $zip->addEmptyDir( $slug );
    $zip->addFromString( $slug . '/' . $slug . '.php', $plugin_code );
    $zip->close();

    if ( ! file_exists( $zip_path ) ) wp_die( 'El archivo ZIP no se generó correctamente.' );

    // Limpiar cualquier output buffer previo
    if ( ob_get_length() ) ob_end_clean();

    header( 'Content-Type: application/zip' );
    header( 'Content-Disposition: attachment; filename="' . $slug . '.zip"' );
    header( 'Content-Length: ' . filesize( $zip_path ) );
    header( 'Pragma: no-cache' );
    header( 'Expires: 0' );
    readfile( $zip_path );
    unlink( $zip_path );
    exit;
});
/**
 * Submenú en el CPT MetaCode
 */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=mc_snippet',
        'Visor de Código MetaCode',
        'Análisis de Snippets',
        'manage_options',
        'mc-viewer',
        'mc_render_viewer_page'
    );
});

/**
 * Helper: Construye el bloque PHP completo de una categoría (como si fuera un plugin)
 */
function mc_build_category_plugin_code( WP_Term $cat, array $snippets ) {

    $plugin_name = $cat->name;
    $description = ! empty( $cat->description ) ? $cat->description : 'Snippets de la categoría ' . $cat->name . '.';
    $autor       = get_term_meta( $cat->term_id, 'mc_cat_autor', true ) ?: 'Manuel Cerón';
    $version     = get_term_meta( $cat->term_id, 'mc_cat_version', true ) ?: '0.1.0';

    $lines = [];

    $lines[] = '<?php';
    $lines[] = '/**';
    $lines[] = ' * Plugin Name: ' . $plugin_name;
    $lines[] = ' * Description: ' . $description;
    $lines[] = ' * Version:     ' . $version;
    $lines[] = ' * Author:      ' . $autor;
    $lines[] = ' * License:     GPL2';
    $lines[] = ' */';
    $lines[] = '';
    $lines[] = "if ( ! defined( 'ABSPATH' ) ) exit;";
    $lines[] = '';

    foreach ( $snippets as $snippet ) {

        $hook   = get_post_meta( $snippet->ID, '_mc_hook', true ) ?: 'wp_footer';
        $estado = get_post_meta( $snippet->ID, '_mc_estado', true ) ?: 'activo';
        $titulo = $snippet->post_title;
        $code   = $snippet->post_content;

        $lines[] = '// Snippet: ' . $titulo . ' (ESTADO: ' . strtoupper($estado) . ', Hook: ' . $hook . ')';

        $clean_code      = str_replace( ['<br />', '<br>', '<br/>'], "\n", $code );
        $content_for_php = str_replace( ["\\", "'"], ["\\\\", "\\'"], $clean_code );

        $lines[] = "add_action('{$hook}', function() {";
        $lines[] = "    echo '{$content_for_php}';";
        $lines[] = "});";
        $lines[] = '';
    }

    return implode("\n", $lines);
}

/**
 * Helper: Genera y descarga el ZIP de una categoría
 */
function mc_download_category_zip( $cat_id ) {

    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'No autorizado.' );

    $cat = get_term( $cat_id, 'mc_categoria' );
    if ( ! $cat || is_wp_error( $cat ) ) wp_die( 'Categoría no encontrada.' );

    $snippets = get_posts([
        'post_type'   => 'mc_snippet',
        'numberposts' => -1,
        'tax_query'   => [[
            'taxonomy' => 'mc_categoria',
            'field'    => 'term_id',
            'terms'    => $cat->term_id,
        ]],
    ]);

    if ( empty( $snippets ) ) wp_die( 'Esta categoría no tiene snippets.' );

    // Usamos el slug como nombre de archivo y carpeta
    $slug        = $cat->slug;
    $plugin_code = mc_build_category_plugin_code( $cat, $snippets );

    // Archivo temporal para el ZIP
    $tmp_dir  = get_temp_dir();
    $zip_path = $tmp_dir . $slug . '.zip';

    $zip = new ZipArchive();
    if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
        wp_die( 'No se pudo crear el archivo ZIP.' );
    }

    // Estructura: <slug>/<slug>.php
    $zip->addEmptyDir( $slug );
    $zip->addFromString( $slug . '/' . $slug . '.php', $plugin_code );
    $zip->close();

    // Enviar el archivo al navegador
    header( 'Content-Type: application/zip' );
    header( 'Content-Disposition: attachment; filename="' . $slug . '.zip"' );
    header( 'Content-Length: ' . filesize( $zip_path ) );
    header( 'Pragma: no-cache' );
    readfile( $zip_path );

    // Limpiar archivo temporal
    unlink( $zip_path );
    exit;
}

/**
 * Render de la página de visor
 */
function mc_render_viewer_page() {

    $cat_id   = isset( $_GET['cat_id'] ) ? intval( $_GET['cat_id'] ) : 0;
    $download = isset( $_GET['download'] ) ? sanitize_text_field( $_GET['download'] ) : '';
    $base_url = admin_url( 'edit.php?post_type=mc_snippet&page=mc-viewer' );

    // Interceptar descarga ANTES de cualquier output HTML
    if ( $cat_id && $download === 'zip' ) {
        mc_download_category_zip( $cat_id );
        return; // exit() ya está dentro, pero por claridad
    }
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Análisis de Snippets (MetaCode)</h1>
        <hr class="wp-header-end">
        <div style="margin-top:20px;">

        <?php if ( ! $cat_id ) :
            // --- ÍNDICE: Listado de categorías ---
            $categorias = get_terms([
                'taxonomy'   => 'mc_categoria',
                'hide_empty' => false,
            ]);

            if ( empty( $categorias ) ) : ?>
                <div class="notice notice-info"><p>No hay categorías creadas aún.</p></div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th>Descripción</th>
                            <th>Autor</th>
                            <th>Versión</th>
                            <th>Snippets</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $categorias as $cat ) :
                            $url   = $base_url . '&cat_id=' . $cat->term_id;
                            $autor = get_term_meta( $cat->term_id, 'mc_cat_autor', true ) ?: '—';
                            $ver   = get_term_meta( $cat->term_id, 'mc_cat_version', true ) ?: '—';
                            ?>
                            <tr>
                                <td><a href="<?php echo esc_url( $url ); ?>"><strong><?php echo esc_html( $cat->name ); ?></strong></a></td>
                                <td><?php echo esc_html( $cat->description ?: '—' ); ?></td>
                                <td><?php echo esc_html( $autor ); ?></td>
                                <td><?php echo esc_html( $ver ); ?></td>
                                <td><?php echo intval( $cat->count ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php else :
            // --- DETALLE: Código de la categoría seleccionada ---
            $cat = get_term( $cat_id, 'mc_categoria' );

            if ( ! $cat || is_wp_error( $cat ) ) : ?>
                <div class="notice notice-error"><p>Categoría no encontrada.</p></div>
            <?php else :
                $snippets = get_posts([
                    'post_type'   => 'mc_snippet',
                    'numberposts' => -1,
                    'tax_query'   => [[
                        'taxonomy' => 'mc_categoria',
                        'field'    => 'term_id',
                        'terms'    => $cat->term_id,
                    ]],
                ]);

                $zip_url = $base_url . '&cat_id=' . $cat_id . '&download=zip';
                ?>
                <div style="margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
                    <a href="<?php echo esc_url( $base_url ); ?>" class="button">← Volver al índice</a>
                    <div>
                        <a href="<?php echo esc_url( $zip_url ); ?>" class="button button-primary">⬇ Descargar ZIP</a>
                        <a href="<?php echo get_edit_term_link( $cat->term_id, 'mc_categoria' ); ?>" class="button" style="margin-left:5px;">Editar Categoría</a>
                    </div>
                </div>

                <?php if ( empty( $snippets ) ) : ?>
                    <div class="notice notice-info"><p>Esta categoría no tiene snippets asignados.</p></div>
                <?php else :
                    $plugin_code = mc_build_category_plugin_code( $cat, $snippets );
                    ?>
                    <div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; overflow:hidden;">
                        <div style="background:#f6f7f7; padding:10px 20px; border-bottom:1px solid #ccd0d4;">
                            <h2 style="margin:0;">📁 <?php echo esc_html( $cat->name ); ?></h2>
                        </div>
                        <div style="padding:20px;">
                            <pre style="background:#1e1e1e; color:#d4d4d4; padding:15px; border-radius:5px; overflow-x:auto; font-family:'Consolas','Monaco',monospace; font-size:13px; line-height:1.6; border:1px solid #000; white-space:pre;"><?php echo esc_html( $plugin_code ); ?></pre>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        </div>
    </div>
    <?php
}