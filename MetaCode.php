<?php
/**
 * Plugin Name: MetaCode
 * Description: Sistema avanzado para inyectar código (HTML, CSS, JS) en hooks específicos de WordPress.
 * Version: 0.2.0
 * Author: Manuel Cerón
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MC_PATH', plugin_dir_path( __FILE__ ) );
define( 'MC_URL',  plugin_dir_url( __FILE__ ) );

require_once MC_PATH . 'includes/mc-core.php';

if ( is_admin() ) {
    require_once MC_PATH . 'includes/mc-viewer.php';
}