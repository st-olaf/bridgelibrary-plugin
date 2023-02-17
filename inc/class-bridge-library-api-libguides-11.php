<?php
/**
 * Bridge Library LibGuides API class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library LibGuides API class.
 *
 * @since 1.0.0
 */
class Bridge_Library_API_LibGuides_11 extends Bridge_Library {

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * LibGuides Async Process for background processing.
	 *
	 * @since 1.0.0
	 *
	 * @var LibGuides_11_Async_Process $async
	 */
	public $async;

	/**
	 * External service.
	 *
	 * @since 1.0.0
	 *
	 * @var string $service
	 */
	private $service = 'LibGuides_11';

	/**
	 * API base URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string $base_url
	 */
	private $base_url = 'https://lgapi-us.libapps.com/1.1/';

	/**
	 * API mode.
	 *
	 * @since 1.0.0
	 *
	 * @var string $mode
	 */
	private $mode;

	/**
	 * Client ID to use for API call.
	 *
	 * @since 1.0.0
	 *
	 * @var string $client_id
	 */
	private $client_id;

	/**
	 * Client secret to use for API call.
	 *
	 * @since 1.0.0
	 *
	 * @var string $client_secret
	 */
	private $client_secret;

	/**
	 * Institution slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string $institution
	 */
	private $institution;

	/**
	 * Client IDs array.
	 *
	 * @since 1.0.0
	 *
	 * @var array $client_ids
	 */
	private $client_ids;

	/**
	 * Client secrets array.
	 *
	 * @since 1.0.0
	 *
	 * @var array $client_secrets
	 */
	private $client_secrets;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_API_LibGuides_11 class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_API_LibGuides_11();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		$this->client_ids     = array(
			'stolaf'   => get_field( $this->service . '_stolaf_client_id', 'option' ),
			'carleton' => get_field( $this->service . '_carleton_client_id', 'option' ),
		);
		$this->client_secrets = array(
			'stolaf'   => get_field( $this->service . '_stolaf_client_secret', 'option' ),
			'carleton' => get_field( $this->service . '_carleton_client_secret', 'option' ),
		);

		if ( empty( $this->client_ids ) || empty( $this->client_secrets ) ) {
			add_action( 'admin_notices', array( $this, 'missing_api_keys' ) );
		}

		require_once BL_PLUGIN_DIR . '/inc/class-libguides-11-async-process.php';
		$this->async = new LibGuides_11_Async_Process();
	}

	/**
	 * Set client ID/secrets appropriately.
	 *
	 * @since 1.0.0
	 *
	 * @param string $institution Institution slug.
	 *
	 * @return self
	 */
	public function set_institution( $institution ) {
		$this->client_id     = $this->client_ids[ $institution ];
		$this->client_secret = $this->client_secrets[ $institution ];
		$this->institution   = $institution;

		return $this;
	}

	/**
	 * Retrieve API tokens.
	 *
	 * @since 1.0.0
	 *
	 * @return array API tokens.
	 */
	public function get_api_tokens() {
		return array(
			'site_id' => $this->client_id,
			'key'     => $this->client_secret,
		);
	}

	/**
	 * Send a request to the LibGuides API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path         Path to retrieve.
	 * @param array  $query        Query paramaters.
	 * @param array  $request_args Request parameters.
	 *
	 * @return array               Decoded JSON response from LibGuides API.
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

		$query = wp_parse_args(
			$query,
			$this->get_api_tokens()
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
	 * Retrieve all LibGuides assets.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query Query parameters.
	 *
	 * @return array       LibGuides assets.
	 */
	public function get_assets( $query = array() ) {
		$query = wp_parse_args(
			$query,
			array(
				'expand' => 'subjects,metadata,pages',
				'status' => '1', // Limit to published.
			)
		);

		$assets = $this->request( 'assets', $query );

		return $assets;
	}

	/**
	 * Retrieve all LibGuides guides.
	 *
	 * @since 1.0.0
	 *
	 * @param array $query Query parameters.
	 *
	 * @return array       LibGuides assets.
	 */
	public function get_guides( $query = array() ) {
		$query = wp_parse_args(
			$query,
			array(
				'expand' => 'subjects,metadata,pages',
			)
		);

		$guides = $this->request( 'guides', $query );

		return $guides;
	}

	/**
	 * Retrieve a single LibGuides guide.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $id    Guide ID.
	 * @param array $query Query parameters.
	 *
	 * @return array       LibGuides assets.
	 */
	public function get_guide( int $id, $query = array() ) {
		$query = wp_parse_args(
			$query,
			array(
				'expand' => 'subjects,metadata,pages',
			)
		);

		$guides = $this->request( 'guides/' . $id, $query );

		foreach ( $guides as $guide ) {
			if ( $id === $guide['id'] ) {
				return $guide;
			}
		}

		return array();
	}

}
