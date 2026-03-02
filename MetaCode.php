<?php
/**
 * Plugin Name: MetaCode
 * Description: Código (HTML/JS/CSS) en hooks específicos de WordPress con organización por categorías.
 * Version:     0.1.1
 * Author:      Manuel Cerón
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. REGISTRO DE CPT Y TAXONOMÍA (CATEGORÍAS)
 */
add_action( 'init', function() {

    // Registro de la Taxonomía primero para que el CPT la reconozca
    register_taxonomy( 'mc_categoria', 'mc_snippet', [
        'label'             => 'Categorías MetaCode',
        'labels'            => [
            'name'              => 'Categorías',
            'singular_name'     => 'Categoría',
            'menu_name'         => 'Categorías',
            'all_items'         => 'Todas las Categorías',
            'edit_item'         => 'Editar Categoría',
            'view_item'         => 'Ver Categoría',
            'update_item'       => 'Actualizar Categoría',
            'add_new_item'      => 'Añadir Nueva Categoría',
            'new_item_name'     => 'Nombre de Nueva Categoría',
            'search_items'      => 'Buscar Categorías',
        ],
        'hierarchical'      => true, // Comportamiento de categorías (checkboxes)
        'show_ui'           => true,
        'show_admin_column' => true, // Importante: Ver la categoría en el listado general
        'show_in_nav_menus' => false,
        'public'            => false,
    ]);

    // Registro del Custom Post Type
    register_post_type( 'mc_snippet', [
        'label'               => 'MetaCode',
        'public'              => false,
        'show_ui'             => true,
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'menu_icon'           => 'dashicons-code-standards',
        'supports'            => [ 'title', 'editor' ],
        'show_in_menu'        => true,
        'taxonomies'          => [ 'mc_categoria' ],
    ]);
});

/**
 * 2. METABOXES (HOOK, ESTADO, NOTAS)
 */
add_action( 'add_meta_boxes', function() {
    // Selector de Hook
    add_meta_box( 'mc_hook_selector', 'Configuración de Inyección', function( $post ) {
        $current_hook = get_post_meta( $post->ID, '_mc_hook', true ) ?: 'wp_footer';
        $hooks = [
            'wp_head'              => 'wp_head - Antes de cerrar </head>',
            'wp_body_open'         => 'wp_body_open - Tras abrir <body>',
            'wp_footer'            => 'wp_footer - Antes de cerrar </body>',
            'loop_start'           => 'loop_start - Inicio del Loop',
            'loop_end'             => 'loop_end - Fin del Loop',
            'the_post'             => 'the_post - Inicio de cada Post',
            'get_sidebar'          => 'get_sidebar - Antes de Sidebar',
            'get_footer'           => 'get_footer - Antes de cargar Footer',
            'comment_form_before'  => 'comment_form_before - Antes de Comentarios',
            'comment_form_after'   => 'comment_form_after - Después de Comentarios',
            'wp_enqueue_scripts'   => 'wp_enqueue_scripts - Registro técnico de recursos (Enqueue)',
        ];
        ?>
        <p>¿Dónde quieres inyectar este código?</p>
        <select name="mc_hook" style="width:100%;">
            <?php foreach ( $hooks as $value => $label ) : ?>
                <option value="<​?php echo esc_attr($value); ?>" <?php selected( $current_hook, $value ); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }, 'mc_snippet', 'side' );

    // Estado
    add_meta_box( 'mc_estado', 'Estado del Snippet', function( $post ) {
        $estado = get_post_meta( $post->ID, '_mc_estado', true ) ?: 'activo';
        ?>
        <select name="mc_estado" style="width:100%;">
            <option value="activo" <?php selected( $estado, 'activo' ); ?>>✅ Activo</option>
            <option value="inactivo" <?php selected( $estado, 'inactivo' ); ?>>⛔ Inactivo</option>
        </select>
        <?php
    }, 'mc_snippet', 'side' );

    // Notas
    add_meta_box( 'mc_notas', 'Observaciones / Notas', function( $post ) {
        $notas = get_post_meta( $post->ID, '_mc_notas', true );
        ?>
        <textarea name="mc_notas" rows="5" style="width:100%;font-family:monospace;font-size:12px;"><?php echo esc_textarea( $notas ); ?></textarea>
        <?php
    }, 'mc_snippet', 'normal' );
});

/**
 * 3. GUARDADO DE DATOS
 */
add_action( 'save_post', function( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( isset( $_POST['mc_hook'] ) ) update_post_meta( $post_id, '_mc_hook', sanitize_text_field( $_POST['mc_hook'] ) );
    if ( isset( $_POST['mc_estado'] ) ) update_post_meta( $post_id, '_mc_estado', sanitize_text_field( $_POST['mc_estado'] ) );
    if ( isset( $_POST['mc_notas'] ) ) update_post_meta( $post_id, '_mc_notas', sanitize_textarea_field( $_POST['mc_notas'] ) );
});

/**
 * 4. LÓGICA DE INYECCIÓN
 */
add_action( 'wp', function() {
    $snippets = get_posts([
        'post_type'   => 'mc_snippet',
        'post_status' => 'publish',
        'numberposts' => -1
    ]);

    foreach ( $snippets as $snippet ) {
        if ( get_post_meta( $snippet->ID, '_mc_estado', true ) !== 'activo' ) continue;

        $hook = get_post_meta( $snippet->ID, '_mc_hook', true ) ?: 'wp_footer';
        $code = $snippet->post_content;
        $nombre = esc_html( $snippet->post_title );

        add_action( $hook, function() use ( $code, $nombre ) {
            echo "\n<!-- MetaCode: {$nombre} -->\n{$code}\n<!-- /MetaCode -->\n";
        });
    }
});