<?php
/**
 * Main Bridge Library class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Main Bridge Library class.
 *
 * @since 1.0.0
 */
class Bridge_Library {

	/**
	 * Class instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		require_once BL_PLUGIN_DIR . '/vendor/autoload.php';

		// Admin.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-admin.php';
		$this->admin = Bridge_Library_Admin::get_instance();

		// APIs.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-api-alma.php';
		$this->alma_api = Bridge_Library_API_Alma::get_instance();
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-api-primo.php';
		$this->primo_api = Bridge_Library_API_Primo::get_instance();
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-api-libguides-11.php';
		$this->libguides_api_11 = Bridge_Library_API_LibGuides_11::get_instance();
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-api-libguides-12.php';
		$this->libguides_api_12 = Bridge_Library_API_LibGuides_12::get_instance();

		// Users.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-users.php';
		$this->users = Bridge_Library_Users::get_instance();

		// Data structure.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-data-structure.php';
		$this->data_structure = Bridge_Library_Data_Structure::get_instance();

		// Courses.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-courses.php';
		$this->courses = Bridge_Library_Courses::get_instance();

		// Resources.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-resources.php';
		$this->resources = Bridge_Library_Resources::get_instance();

		// Librarians.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-librarians.php';
		$this->librarians = Bridge_Library_Librarians::get_instance();

		// Logging.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-logging.php';
		$this->logging = Bridge_Library_Logging::get_instance();

		// User Interest Feeds.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-user-interest-feeds.php';
		$this->user_interest_feeds = Bridge_Library_User_Interest_Feeds::get_instance();

		// API provider.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-api-provider-base.php';
		new Bridge_Library_API_Provider_Base();

		// ACF tables compatibility.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-acf-tables-compatibility.php';
		$this->acf_tables_compat = Bridge_Library_ACF_Tables_Compatibility::get_instance();

		// WPGraphQL.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-graphql-authentication.php';
		Bridge_Library_GraphQL_Authentication::get_instance();

		// WP CLI.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-wp-cli.php';
		add_action(
			'cli_init',
			function() {
				WP_CLI::add_command( 'bridge-library', Bridge_Library_WP_CLI::class );
			}
		);

		// Scheduling.
		register_activation_hook( BL_PLUGIN_FILE, array( $this, 'schedule_automatic_updates' ) );
		register_deactivation_hook( BL_PLUGIN_FILE, array( $this, 'clear_automatic_updates' ) );

		add_action(
			'bridge_library_schedule_daily',
			function () {
				error_log( gmdate( 'Y-m-d H:i:s' ) . ': running daily Bridge Library tasks.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			},
			1
		);
	}

	/**
	 * Get plugin version.
	 *
	 * @since 1.0.0
	 *
	 * @return string Plugin version string.
	 */
	public function get_plugin_version() {
		return BL_PLUGIN_VERSION;
	}

	/**
	 * Add admin notice about missing API keys.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function missing_api_keys() {
		$class = 'notice notice-error';
		$url   = admin_url( 'admin.php?page=acf-options-site-options' );

		$message = __( 'Missing API keys. Please enter them here: ', 'bridge-library' );

		// Translators: %1$s is the admin notice class; %2$s is the message; %3$s is the URL to the settings page.
		printf( '<div class="%1$s"><p>%2$s <a href="%3$s" class="button button-primary">Go to Settings</a></p></div>', esc_attr( $class ), esc_html( $message ), esc_url( $url ) );
	}

	/**
	 * Add scheduled events.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function schedule_automatic_updates() {

		// Allowing for a 6-hour timezone offset, run starting at 1AM.
		wp_schedule_event( strtotime( 'tomorrow 7am' ), 'daily', 'bridge_library_schedule_daily' );
	}

	/**
	 * Clear our scheduled events.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function clear_automatic_updates() {
		$hooks = array(
			// Daily recurring updates.
			'bridge_library_schedule_daily',

			// Background processing cron jobs.
			'wp_bridge_library_get_all_pages_cron',
			'wp_bridge_library_get_all_libguides_cron',
		);

		foreach ( $hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
	}

}
