<?php
/**
 * Bridge Library LibGuides API class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use \League\OAuth2\Client\Provider\GenericProvider;
use \League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Bridge Library LibGuides API class.
 *
 * @since 1.0.0
 */
class Bridge_Library_API_LibGuides_12 extends Bridge_Library {

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
	 * @var LibGuides_12_Async_Process $async
	 */
	public $async;

	/**
	 * External service.
	 *
	 * @since 1.0.0
	 *
	 * @var string $service
	 */
	private $service = 'LibGuides_12';

	/**
	 * API base URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string $base_url
	 */
	private $base_url = 'https://lgapi-us.libapps.com/1.2/';

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
	 * @return Bridge_Library_API_LibGuides_12 class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_API_LibGuides_12();
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

		require_once BL_PLUGIN_DIR . '/inc/class-libguides-12-async-process.php';
		$this->async = new LibGuides_12_Async_Process();
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
	 * Retrieve acess token.
	 *
	 * @since 1.0.0
	 *
	 * @return string|WP_Error Access token or error.
	 */
	public function get_access_token() {

		$access_token = get_transient( 'bridge_library_access_token_' . $this->service . $this->institution );

		if ( empty( $access_token ) ) {

			$provider = new \League\OAuth2\Client\Provider\GenericProvider(
				array(
					'clientId'                => $this->client_id,
					'clientSecret'            => $this->client_secret,
					'urlAuthorize'            => $this->base_url . 'authorize',
					'urlAccessToken'          => $this->base_url . 'oauth/token',
					'urlResourceOwnerDetails' => $this->base_url . 'resource',
				)
			);

			try {
				$access_token_array = $provider->getAccessToken( 'client_credentials' );
				set_transient( 'bridge_library_access_token_' . $this->service . $this->institution, $access_token_array->getToken(), $access_token_array->getExpires() - time() );
				$access_token = $access_token_array->getToken();
			} catch ( \League\OAuth2\Client\Provider\Exception\IdentityProviderException $e ) {
				return new WP_Error( $e->getCode(), $e->getMessage() );
			}
		}

		return $access_token;
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

		$request_args['headers'] = wp_parse_args(
			$request_args['headers'],
			array(
				'Authorization' => 'Bearer ' . $this->get_access_token(),
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
	 * Retrieve LibGuides user object.
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
			array( 'expand' => 'az_types' )
		);

		$assets = $this->request( 'az', $query );

		return $assets;
	}

	/**
	 * Retrieve LibGuides assets for a specific guide.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $guide_id LibGuides Guide ID.
	 * @param array $query    Query parameters.
	 *
	 * @return array          LibGuides assets.
	 */
	public function get_assets_for_guide( $guide_id, $query = array() ) {
		$query = wp_parse_args(
			$query,
			array(
				'guide_ids' => $guide_id,
				'expand'    => 'subjects,metadata,pages',
				'status'    => '1', // Limit to published.
			)
		);

		$guides = $this->request( 'az', $query );

		return $guides;
	}

}
