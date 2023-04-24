<?php
/**
 * Plugin Name: Bridge Library Custom Functionality
 * Plugin URI: https://luminfire.com
 * Description: Custom site functionality
 * Author: LuminFire
 * Version: 1.3.1
 * Requires PHP: 7.4
 * Author URI: https://luminfire.com/
 *
 * @package  bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin version.
if ( ! defined( 'BL_PLUGIN_VERSION' ) ) {
	define( 'BL_PLUGIN_VERSION', '1.3.1' );
}

// Define plugin file.
if ( ! defined( 'BL_PLUGIN_FILE' ) ) {
	define( 'BL_PLUGIN_FILE', __FILE__ );
}

// Define plugin file directory.
if ( ! defined( 'BL_PLUGIN_DIR' ) ) {
	define( 'BL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

// Define plugin file URL.
if ( ! defined( 'BL_PLUGIN_DIR_URL' ) ) {
	define( 'BL_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
}

// Include the main class.
if ( ! class_exists( 'Bridge_Library' ) ) {
	include_once dirname( __FILE__ ) . '/inc/class-bridge-library.php';
	Bridge_Library::get_instance();
}
