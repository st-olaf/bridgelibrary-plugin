<?php
/**
 * Bridge Library admin class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library admin class.
 *
 * @since 1.0.0
 */
class Bridge_Library_Admin {

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_Admin class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_Admin();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Add menu pages.
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );

		// Register assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register admin assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_script( 'bridge-library-admin', BL_PLUGIN_DIR_URL . 'assets/js/admin.js', array( 'jquery' ), BL_PLUGIN_VERSION, true );
	}

	/**
	 * Add admin menu pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_menu_pages() {

		// Add top-level page.
		add_menu_page(
			__( 'Bridge Library Tools', 'bridge-library' ),
			__( 'Bridge Library Tools', 'bridge-library' ),
			'manage_options_bridge_library',
			'bridge_library_settings',
			array( $this, 'top_settings_page' ),
			'dashicons-admin-generic'
		);

		// Add ACF site options page.
		acf_add_options_page(
			array(
				'page_title'  => 'Site Options',
				'capability'  => 'manage_options_bridge_library',
				'parent_slug' => 'bridge_library_settings',
			)
		);
	}

	/**
	 * Display top-level settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function top_settings_page() {

		// Verify permissions.
		if ( ! current_user_can( 'manage_options_bridge_library' ) ) {
			echo '<p>Sorry, you’re not allowed to do that.</p>';
			wp_die();
		}

		wp_enqueue_script( 'bridge-library-admin' );

		echo '<div class="wrap">';
		echo '<h2>Bridge Library Tools</h2>';

		/**
		 * Run actions.
		 */
		do_action( 'bridge_library_admin_settings' );

		echo '</div>';
	}

}
