<?php
/**
 * Plugin Name: MetaCode
 * Description: Código (HTML/JS/CSS) en hooks específicos de WordPress.
 * Version:     0.1.0
 * Author:      Manuel Cerón
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. REGISTRO DEL ALMACÉN DE CÓDIGO (CPT)
 */
add_action( 'init', function() {
    register_post_type( 'mc_snippet', [
        'label'               => 'MetaCode',
        'public'              => false,
        'show_ui'             => true,
        'capability_type'     => 'post',
        'hierarchical'        => false,
        'menu_icon'           => 'dashicons-code-standards',
        'supports'            => [ 'title', 'editor' ], // Título = Nombre, Editor = El código
        'show_in_menu'        => true,
    ]);
});

/**
 * 2. METABOX PARA ELEGIR EL "MOMENTO" (HOOK)
 */
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'mc_hook_selector', 'Configuración de Inyección', function( $post ) {
        $current_hook = get_post_meta( $post->ID, '_mc_hook', true ) ?: 'wp_footer';
        $hooks = [
            'wp_head'              => 'wp_head - Antes de cerrar </head> (CSS, Meta, Scripts)',
            'wp_body_open'         => 'wp_body_open - Justo tras abrir <body> (GTM, Botones flotantes)',
            'wp_footer'            => 'wp_footer - Antes de cerrar </body> (Scripts pesados, Chat)',
            'loop_start'           => 'loop_start - Antes del primer post del listado',
            'loop_end'             => 'loop_end - Después del último post del listado',
            'the_post'             => 'the_post - Al inicio de cada post individual',
            'get_sidebar'          => 'get_sidebar - Justo antes de cargar la barra lateral',
            'get_footer'           => 'get_footer - Justo antes de cargar la plantilla del footer',
            'comment_form_before'  => 'comment_form_before - Antes del formulario de comentarios',
            'comment_form_after'   => 'comment_form_after - Después del formulario de comentarios',
            'wp_enqueue_scripts'   => 'wp_enqueue_scripts - Registro técnico de recursos (Enqueue)',
        ];
        ?>
        <p>¿Dónde quieres inyectar este código?</p>
        <select name="mc_hook" style="width:100%;">
            <?php foreach ( $hooks as $value => $label ) : ?>
                <option value="<​?php echo $value; ?>" <?php selected( $current_hook, $value ); ?>><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }, 'mc_snippet', 'side' );
});

/**
 * 3. METABOX ESTADO (ACTIVO / INACTIVO)
 */
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'mc_estado', 'Estado del Snippet', function( $post ) {
        $estado = get_post_meta( $post->ID, '_mc_estado', true ) ?: 'activo';
        ?>
        <select name="mc_estado" style="width:100%;">
            <option value="activo"   <?php selected( $estado, 'activo' );   ?>>✅ Activo</option>
            <option value="inactivo" <?php selected( $estado, 'inactivo' ); ?>>⛔ Inactivo</option>
        </select>
        <p style="color:#888;font-size:11px;margin-top:6px;">Los snippets inactivos no se inyectan en el sitio.</p>
        <?php
    }, 'mc_snippet', 'side' );
});

/**
 * 4. METABOX OBSERVACIONES / NOTAS
 */
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'mc_notas', 'Observaciones / Notas', function( $post ) {
        $notas = get_post_meta( $post->ID, '_mc_notas', true );
        ?>
        <textarea name="mc_notas" rows="5" style="width:100%;font-family:monospace;font-size:12px;" placeholder="Describe qué hace este snippet, por qué existe, dependencias, etc."><?php echo esc_textarea( $notas ); ?></textarea>
        <?php
    }, 'mc_snippet', 'normal' );
});

// Guardar el hook, estado y notas
add_action( 'save_post', function( $post_id ) {
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( isset( $_POST['mc_hook'] ) ) {
        update_post_meta( $post_id, '_mc_hook', sanitize_text_field( $_POST['mc_hook'] ) );
    }
    if ( isset( $_POST['mc_estado'] ) ) {
        update_post_meta( $post_id, '_mc_estado', sanitize_text_field( $_POST['mc_estado'] ) );
    }
    if ( isset( $_POST['mc_notas'] ) ) {
        update_post_meta( $post_id, '_mc_notas', sanitize_textarea_field( $_POST['mc_notas'] ) );
    }
});

/**
 * 5. LÓGICA DE INYECCIÓN DINÁMICA
 */
add_action( 'wp', function() {
    $snippets = get_posts([
        'post_type'   => 'mc_snippet',
        'post_status' => 'publish',
        'numberposts' => -1
    ]);

    foreach ( $snippets as $snippet ) {
        // Respetar estado activo/inactivo
        $estado = get_post_meta( $snippet->ID, '_mc_estado', true ) ?: 'activo';
        if ( $estado !== 'activo' ) continue;

        $hook = get_post_meta( $snippet->ID, '_mc_hook', true ) ?: 'wp_footer';
        $code = $snippet->post_content;
        $nombre = esc_html( $snippet->post_title );

        add_action( $hook, function() use ( $code, $nombre ) {
            echo "\n<!-- MetaCode: {$nombre} -->\n";
            echo $code;
            echo "\n<!-- /MetaCode: {$nombre} -->\n";
        }, 10 );
    }
});