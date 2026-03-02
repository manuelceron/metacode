<?php
/**
 * Archivo: includes/mc-viewer.php
 * Descripción: Visor que muestra, por categoría, un "plugin virtual" con todos los snippets.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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
    // Extraemos la descripción real de la categoría de WordPress
    $description = ! empty( $cat->description ) ? $cat->description : 'Snippets pertenecientes a la categoría ' . $cat->name . '.';

    $lines = [];

    // Header de plugin
    $lines[] = '<?php';
    $lines[] = '/**';
    $lines[] = ' * Plugin Name: ' . $plugin_name;
    $lines[] = ' * Description: ' . $description;
    $lines[] = ' * Version: 0.1.0';
    $lines[] = ' * Author: Manuel Cerón';
    $lines[] = ' * License: GPL2';
    $lines[] = ' */';
    $lines[] = '';
    $lines[] = "if ( ! defined( 'ABSPATH' ) ) exit;";
    $lines[] = '';

    // Snippets
    foreach ( $snippets as $snippet ) {

        $hook   = get_post_meta( $snippet->ID, '_mc_hook', true ) ?: 'wp_footer';
        $estado = get_post_meta( $snippet->ID, '_mc_estado', true ) ?: 'activo';
        $titulo = $snippet->post_title;
        $code   = $snippet->post_content;

        // Comentario de bloque para identificar el snippet
        $lines[] = '// Snippet: ' . $titulo . ' (ESTADO: ' . strtoupper($estado) . ', Hook: ' . $hook . ')';

        // Limpieza de etiquetas <br /> que WordPress a veces inserta en el editor
        $clean_code = str_replace( array('<br />', '<br>', '<br/>'), "\n", $code );

        // Escapamos comillas simples para el echo PHP
        $content_for_php = str_replace(
            ["\\", "'"],
            ["\\\\", "\\'"],
            $clean_code
        );

        $lines[] = "add_action('{$hook}', function() {";
        $lines[] = "    echo '{$content_for_php}';";
        $lines[] = "});";
        $lines[] = ''; // línea en blanco entre snippets
    }

    return implode("\n", $lines);
}

/**
 * Render de la página de visor
 */
function mc_render_viewer_page() {

    $categorias = get_terms([
        'taxonomy'   => 'mc_categoria',
        'hide_empty' => false,
    ]);
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Análisis de Snippets (MetaCode)</h1>
        <hr class="wp-header-end">

        <div style="margin-top:20px;">

            <?php if ( empty( $categorias ) ) : ?>
                <div class="notice notice-info">
                    <p>No hay categorías creadas aún.</p>
                </div>
            <?php endif; ?>

            <?php foreach ( $categorias as $cat ) :

                $snippets = get_posts([
                    'post_type'   => 'mc_snippet',
                    'numberposts' => -1,
                    'tax_query'   => [[
                        'taxonomy' => 'mc_categoria',
                        'field'    => 'term_id',
                        'terms'    => $cat->term_id,
                    ]],
                ]);

                if ( empty( $snippets ) ) continue;

                // Generamos el "plugin virtual" de esta categoría
                $plugin_code = mc_build_category_plugin_code( $cat, $snippets );
                ?>

                <div style="margin-bottom:40px; background:#fff; border:1px solid #ccd0d4; border-radius:4px; overflow:hidden;">
                    <div style="background:#f6f7f7; padding:10px 20px; border-bottom:1px solid #ccd0d4; display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin:0;">📁 Categoría: <?php echo esc_html( $cat->name ); ?></h2>
                        <a href="<?php echo get_edit_term_link( $cat->term_id, 'mc_categoria' ); ?>" class="button button-small">Editar Categoría</a>
                    </div>

                    <div style="padding:20px;">
                        <pre style="background:#1e1e1e; color:#d4d4d4; padding:15px; border-radius:5px; overflow-x:auto; font-family:'Consolas','Monaco',monospace; font-size:13px; line-height:1.6; border:1px solid #000; white-space:pre;"><?php echo esc_html( $plugin_code ); ?></pre>
                    </div>
                </div>

            <?php endforeach; ?>

        </div>
    </div>
    <?php
}