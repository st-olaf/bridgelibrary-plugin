<?php
/**
 * Bridge Library Alma API class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library Alma API class.
 *
 * @since 1.0.0
 */
class Bridge_Library_API_Alma extends Bridge_Library {

	/**
	 * Class instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Alma Async Process for background processing.
	 *
	 * @since 1.0.0
	 *
	 * @var Alma_Async_Process $async
	 */
	public $async;

	/**
	 * External service.
	 *
	 * @since 1.0.0
	 *
	 * @var string $service
	 */
	private $service = 'alma';

	/**
	 * API base URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string $base_url
	 */
	private $base_url = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/';

	/**
	 * Default number of objects to retrieve.
	 *
	 * @since 1.0.0
	 *
	 * @var int $limit
	 */
	public $limit = 50;

	/**
	 * API mode.
	 *
	 * @since 1.0.0
	 *
	 * @var string $mode
	 */
	private $mode;

	/**
	 * API key.
	 *
	 * @since 1.0.0
	 *
	 * @var string $api_key
	 */
	private $api_key;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_API_Alma class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_API_Alma();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->mode    = get_field( $this->service . '_mode', 'option' );
		$this->api_key = get_field( $this->service . '_' . $this->mode . '_api_key', 'option' );

		if ( empty( $this->api_key ) ) {
			add_action( 'admin_notices', array( $this, 'missing_api_keys' ) );
		}

		require_once BL_PLUGIN_DIR . '/inc/class-alma-async-process.php';
		$this->async = new Alma_Async_Process();
	}

	/**
	 * Send a request to the Alma API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path         Path to retrieve.
	 * @param array  $query        Query paramaters.
	 * @param array  $request_args Request parameters.
	 *
	 * @return array               Decoded JSON response from Alma API.
	 */
	public function request( $path, $query = array(), $request_args = array() ) {
		$request_args = wp_parse_args(
			$request_args,
			array(
				'method'  => 'GET',
				'timeout' => 15,
				'headers' => array(),
			)
		);

		$request_args['headers'] = wp_parse_args(
			$request_args['headers'],
			array(
				'Authorization' => 'apikey ' . $this->api_key,
				'Accept'        => 'application/json',
			)
		);

		$query = wp_parse_args(
			$query,
			array(
				'limit' => $this->limit,
			)
		);

		$path .= '?' . http_build_query( $query );

		$response = wp_remote_request( $this->base_url . $path, $request_args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// If the HTTP status code is not in the 200 range, we consider it an error.
		if ( 2 !== absint( wp_remote_retrieve_response_code( $response ) / 100 ) ) {
			return new WP_Error( wp_remote_retrieve_response_code( $response ), wp_remote_retrieve_body( $response ) );
		}

		return json_decode( $response['body'], true );
	}

	/**
	 * Retrieve Alma user object.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email User email address.
	 *
	 * @return stdClass     Alma user object.
	 */
	public function get_user_by_email( $email ) {
		$user = $this->request( 'users/' . $email );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		if ( isset( $user['errorsExist'] ) ) {
			$wp_error = new WP_Error( 'alma_user', 'Couldn’t retrieve user.' );
			foreach ( $user['errorList']['error'] as $error ) {
				$wp_error->add( 'alma_user', $error['errorMessage'] );
			}
			return $wp_error;
		}

		return $user;
	}

	/**
	 * Retrieve loans for the given user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id Alma user ID.
	 * @param array $params  Optional query parameters.
	 *
	 * @return array         Array of loan data.
	 */
	public function get_loans( $user_id, $params = array() ) {
		$loans = $this->request( 'users/' . $user_id . '/loans', $params );

		if ( is_wp_error( $loans ) ) {
			return $loans;
		}

		if ( isset( $loans['errorsExist'] ) ) {
			$wp_error = new WP_Error( 'alma_loans', 'Couldn’t retrieve user loans.' );
			foreach ( $loans['errorList']['error'] as $error ) {
				$wp_error->add( 'alma_loans', $error['errorMessage'] );
			}
			return $wp_error;
		}

		return $loans;
	}

	/**
	 * Retrieve requests for the given user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id Alma user ID.
	 * @param array $params  Optional query parameters.
	 *
	 * @return array         Array of request data.
	 */
	public function get_requests( $user_id, $params = array() ) {
		$requests = $this->request( 'users/' . $user_id . '/requests', $params );

		if ( is_wp_error( $requests ) ) {
			return $requests;
		}

		if ( isset( $requests['errorsExist'] ) ) {
			$wp_error = new WP_Error( 'alma_requests', 'Couldn’t retrieve user requests.' );
			foreach ( $requests['errorList']['error'] as $error ) {
				$wp_error->add( 'alma_requests', $error['errorMessage'] );
			}
			return $wp_error;
		}

		return $requests;
	}

	/**
	 * Retrieve fees for the given user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id Alma user ID.
	 * @param array $params  Optional query parameters.
	 *
	 * @return array         Array of request data.
	 */
	public function get_fees( $user_id, $params = array() ) {
		$fees = $this->request( 'users/' . $user_id . '/fees', $params );

		if ( is_wp_error( $fees ) ) {
			return $fees;
		}

		if ( isset( $fees['errorsExist'] ) ) {
			$wp_error = new WP_Error( 'alma_fees', 'Couldn’t retrieve user fees.' );
			foreach ( $fees['errorList']['error'] as $error ) {
				$wp_error->add( 'alma_fees', $error['errorMessage'] );
			}
			return $wp_error;
		}

		return $fees;
	}

}
