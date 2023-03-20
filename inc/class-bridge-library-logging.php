<?php
/**
 * Bridge Library logging class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library logging class.
 *
 * @since 1.0.0
 */
class Bridge_Library_Logging extends Bridge_Library {


	/**
	 * Class instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Debugging mode.
	 *
	 * @since 1.0.0
	 *
	 * @var ?bool $debug
	 */
	public $debug = null;

	/**
	 * Tracking ID.
	 *
	 * @since 1.0.0
	 *
	 * @var ?string $tracking_id
	 */
	public $tracking_id = null;

	/**
	 * Default request parameters.
	 *
	 * @since 1.0.0
	 *
	 * @var array $default_params
	 */
	private $default_params = array(
		'v'  => 1, // Version.
		'ds' => 'wordpress', // Data source.
	);

	/**
	 * User UUID.
	 *
	 * @since 1.0.0
	 *
	 * @var ?int $user_cid
	 */
	private $user_cid = null;

	/**
	 * Tracking URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string $url
	 */
	private $url = 'https://www.google-analytics.com/collect';

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_Logging class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_Logging();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Initialize class variables.
		$this->is_debugging();
		$this->get_tracking_id();

		// Caching.
		add_action( 'bl_cache_data', array( $this, 'cache_data' ), 10, 2 );

		// Admin.
		add_action( 'admin_footer', array( $this, 'pageview' ) );

		// Frontend.
		add_action( 'wp_login', array( $this, 'wp_login' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'pageview' ) );

		// API.
		add_action( 'bl_api_get_user', array( $this, 'get_user' ) );
		add_action( 'bl_api_get_course', array( $this, 'get_course' ) );
		add_action( 'bl_api_get_resource', array( $this, 'get_resource' ) );
	}

	/**
	 * Determine if debugging mode or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if debugging mode, false if not.
	 */
	private function is_debugging() {
		if ( ! isset( $this->debug ) ) {
			$mode_setting = get_option( 'options_tracking_debug_mode', false ); // Use get_option() instead of get_field() so we can get this before ACF has fully initialized.
			$this->debug  = ( '1' === $mode_setting );
		}

		return $this->debug;
	}

	/**
	 * Get tracking ID.
	 *
	 * @since 1.0.0
	 *
	 * @return string Tracking ID.
	 */
	private function get_tracking_id() {
		if ( ! isset( $this->tracking_id ) ) {
			$this->tracking_id = get_option( 'options_tracking_id', '' ); // Use get_option() instead of get_field() so we can get this before ACF has fully initialized.
		}

		return $this->tracking_id;
	}

	/**
	 * Generate anonymized client ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 *
	 * @return string      Client ID string.
	 */
	private function get_user_cid( $user_id = null ) {

		if ( ! isset( $this->user_cid ) ) {

			if ( is_null( $user_id ) ) {
				$user_id = get_current_user_id();
			}

			$this->user_cid = get_user_meta( $user_id, 'bridge_library_uuid', true );

			if ( ! $this->user_cid ) {
				update_field( 'bridge_library_uuid', Bridge_Library_Users::get_instance()->generate_uuid(), 'user_' . $user_id );

				$this->user_cid = get_user_meta( $user_id, 'bridge_library_uuid', true );
			}
		}

		return $this->user_cid;
	}

	/**
	 * Get application name.
	 *
	 * @since 1.0.0
	 *
	 * @return string Application name.
	 */
	private function get_application_name() {
		if ( wp_doing_ajax() ) {
			return 'wp_ajax';
		} elseif ( wp_doing_cron() ) {
			return 'wp_cron';
		}

		return 'wp';
	}

	/**
	 * Send logging events.
	 *
	 * @since 1.0.0
	 *
	 * @param array $event_data Event data.
	 * @param int   $user_id    WP user ID.
	 *
	 * @return bool             Whether the request succeeded (was not a failure).
	 */
	public function log( $event_data = array(), $user_id = null ) {
		global $pagename;

		$data = wp_parse_args(
			$event_data,
			array_merge(
				$this->default_params,
				array(
					// General info.
					'tid' => $this->get_tracking_id(), // Google Analytics property ID.
					'cid' => $this->get_user_cid( $user_id ), // User UUID.
					'uip' => wp_unslash( $_SERVER['REMOTE_ADDR'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					'ua'  => wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

					// Content info.
					'dh'  => 'https://' . wp_unslash( $_SERVER['HTTP_HOST'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					'dp'  => wp_unslash( $_SERVER['REQUEST_URI'] ), // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
					'dt'  => $pagename, // Document title.

					// App info.
					'an'  => $this->get_application_name(), // Application name.
					'av'  => $this->get_plugin_version(), // Application version.

					// Hit info.
					't'   => 'event', // Hit Type.

					// ec => 'web',        // Event Category. Required.
					// ea => 'refresh',    // Event Action. Required.
					// el => 'courses',    // Event label.
					// ev => 300,          // Event value.
				)
			)
		);

		$request_args = array(
			'blocking' => false, // Don’t wait for a response.
			'body'     => http_build_query( $data ),
		);

		// Debugging.
		if ( $this->is_debugging() ) {
			$this->url                = str_replace( '.com/collect', '.com/debug/collect', $this->url );
			$request_args['blocking'] = true;
		}

		$post = wp_remote_post( $this->url, $request_args );

		// Debugging.
		if ( $this->is_debugging() ) {
			global $wpdb;

			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->prefix . 'bridge_library_logging',
				array(
					'timestamp' => gmdate( 'Y-m-d H:i:s' ),
					'request'   => wp_json_encode( $data ),
					'data'      => wp_remote_retrieve_body( $post ),
				)
			);
		}

		return ! is_wp_error( $post );
	}

	/**
	 * Log generic pageview.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Event log response.
	 */
	public function pageview() {
		return $this->log(
			array(
				't' => 'pageview',
			)
		);
	}

	/**
	 * Log user login.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $user_login User nicename.
	 * @param WP_User $user       User object.
	 *
	 * @return bool               Event log response.
	 */
	public function wp_login( $user_login, $user ) {
		return $this->log(
			array(
				'ec' => 'web',
				'ea' => 'wp_login',
			),
			$user->ID
		);
	}

	/**
	 * Log our student API endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool        Event log response.
	 */
	public function get_user( $user_id ) {
		return $this->log(
			array(
				'ec' => 'api',
				'ea' => 'get_user',
				'el' => 'Users',
				'ev' => $user_id,
			),
			$user_id
		);
	}

	/**
	 * Log our course API endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool        Event log response.
	 */
	public function get_course( $post_id ) {
		return $this->log(
			array(
				'ec' => 'api',
				'ea' => 'get_course',
				'el' => 'Courses',
				'ev' => $post_id,
			)
		);
	}

	/**
	 * Log our resource API endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool        Event log response.
	 */
	public function get_resource( $post_id ) {
		return $this->log(
			array(
				'ec' => 'api',
				'ea' => 'get_resource',
				'el' => 'Resources',
				'ev' => $post_id,
			)
		);
	}

	/**
	 * Log cache updates.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type    CPT key.
	 * @param int    $user_id User ID.
	 *
	 * @return bool           Event log response.
	 */
	public function cache_data( $type, $user_id ) {
		return $this->log(
			array(
				'ec' => 'api',
				'ea' => 'cache_data',
				'el' => ucfirst( $type ),
			),
			$user_id
		);
	}

}
