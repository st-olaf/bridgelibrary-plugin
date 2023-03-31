<?php
/**
 * Bridge Library Primo API class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library Primo API class.
 *
 * @since 1.0.0
 */
class Bridge_Library_API_Primo extends Bridge_Library {

	/**
	 * Class instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Primo Async Process for background processing.
	 *
	 * @since 1.0.0
	 *
	 * @var Primo_Async_Process $async
	 */
	public $async;

	/**
	 * External service.
	 *
	 * @since 1.0.0
	 *
	 * @var string $service
	 */
	private $service = 'primo';

	/**
	 * API base URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string $base_url
	 */
	private $base_url = 'https://api-na.hosted.exlibrisgroup.com/primo/v1/';

	/**
	 * Default number of objects to retrieve.
	 *
	 * @since 1.0.0
	 *
	 * @var int $limit
	 */
	public $limit = 100;

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
	 * User JWTs keyed by WP user ID.
	 *
	 * @since 1.0.0
	 *
	 * @var array $user_jwt
	 */
	private $user_jwt = array();

	/**
	 * Map Google institution meta to Primo view IDs.
	 *
	 * @since 1.0.0
	 *
	 * @var array $institution_vid_mapping
	 */
	private $institution_vid_mapping = array(
		'carleton.edu' => '01BRC_INST:CCO',
		'stolaf.edu'   => '01BRC_INST:SOC',
	);

	/**
	 * Map Google institution meta to Primo institution names.
	 *
	 * @since 1.0.0
	 *
	 * @var array $institution_name_mapping
	 */
	private $institution_name_mapping = array(
		'carleton.edu' => 'CC Student',
		'stolaf.edu'   => 'SO Student',
	);

	/**
	 * Map Google institution meta to Primo scopes.
	 *
	 * @since 1.1.0
	 *
	 * @var array $institution_scope_mapping
	 */
	private $institution_scope_mapping = array(
		'carleton.edu' => 'CCO_MyCampus_PCI',
		'stolaf.edu'   => 'SOC_MyCampus_CI',
	);

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_API_Primo class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_API_Primo();
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
	}

	/**
	 * Send a request to the Primo API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $path         Path to retrieve.
	 * @param array  $query        Query paramaters.
	 * @param array  $request_args Request parameters.
	 *
	 * @return array|WP_Error      Decoded JSON response from Primo API.
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
	 * Get vid for the given user ID.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $user_id WP user ID.
	 *
	 * @return string        Primo vid parameter.
	 */
	public function get_vid_by_user( $user_id = null ) {
		$user   = Bridge_Library_Users::get_instance();
		$domain = $user->get_domain( $user_id );

		return $this->institution_vid_mapping[ $domain ];
	}

	/**
	 * Get vid for the given domain.
	 *
	 * @since 1.0.0
	 *
	 * @param string $domain Domain name.
	 *
	 * @return string        Primo vid parameter.
	 */
	public function get_vid_by_domain( $domain ) {
		return $this->institution_vid_mapping[ $domain ];
	}

	/**
	 * Get Primo user JWT.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id     WP user ID.
	 *
	 * @return string|WP_Error Primo user JWT.
	 */
	public function get_user_jwt( $user_id ) {

		if ( empty( $this->user_jwt[ $user_id ] ) ) {
			$wp_user      = get_user_by( 'id', $user_id );
			$user_api     = Bridge_Library_Users::get_instance();
			$domain       = $user_api->get_domain( $user_id );
			$alternate_id = get_field( 'alma_id', 'user_' . $user_id );

			$primo_user = array(
				'viewId'      => $this->get_vid_by_user( $user_id ),
				'institution' => '01BRC_INST',
				'language'    => 'en_US',
				'userName'    => $alternate_id,
				'userGroup'   => $this->institution_name_mapping[ $domain ],
				'onCampus'    => true,
				'displayName' => $wp_user->first_name . ' ' . $wp_user->last_name,
			);

			$args = array(
				'method'  => 'POST',
				'body'    => wp_json_encode( $primo_user ),
				'headers' => array(
					'content-type' => 'application/json',
				),
			);

			$jwt = $this->request( 'userJwt', array(), $args );

			if ( is_wp_error( $jwt ) ) {
				return $jwt;
			} else {
				$this->user_jwt[ $user_id ] = $jwt;
			}
		}

		return $this->user_jwt[ $user_id ];
	}

	/**
	 * Cache Primo resource to a WordPress CPT.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $record      Primo record data.
	 * @param array  $tax_input   tax_input parameters.
	 * @param string $domain_name Institution domain name.
	 *
	 * @return int                WP post ID.
	 */
	public function cache_primo_resource( $record, $tax_input = array(), $domain_name = '' ) {
		$resources = Bridge_Library_Resources::get_instance();
		$post_id   = $resources->create_resource_from_primo( $record, $tax_input, $domain_name );

		return $post_id;
	}

	/**
	 * Generate the URL to view a resource on Primo frontend.
	 *
	 * @since 1.0.0
	 *
	 * @param string $doc_id  Primo record ID.
	 * @param string $domain  Institution domain name.
	 *
	 * @return string         Full URL.
	 */
	public function generate_full_view_url( $doc_id, $domain ) {
		return 'https://bridge.primo.exlibrisgroup.com/discovery/fulldisplay?docid=alma' . $doc_id . '&vid=' . $this->get_vid_by_domain( $domain );
	}

	/**
	 * Retrieve and cache Primo user favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id     WP user ID.
	 *
	 * @return array<int, int>|WP_Error Array of user favorite post IDs.
	 */
	public function get_user_favorites( $user_id ) {

		$jwt = $this->get_user_jwt( $user_id );

		if ( is_wp_error( $jwt ) ) {
			return $jwt;
		}

		$user   = Bridge_Library_Users::get_instance();
		$domain = $user->get_domain( $user_id );

		// The Favorites API requires a Bearer authorization header instead of API key so it can identify the user.
		$request_args = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $jwt,
			),
		);

		// Since the Favorites API requries a Bearer authorization header, we have to pass the API key as a query parameter.
		$favorites = $this->request( 'favorites', array( 'apikey' => $this->api_key ), $request_args );

		$favorite_ids  = array();
		$favorite_term = get_term_by( 'slug', 'favorite', 'resource_type' );
		$tax_input     = array(
			'resource_type' => array( (int) $favorite_term->term_id ),
		);

		$request_args = array(
			'vid'   => $this->get_vid_by_user( $user_id ),
			'tab'   => 'Everything',
			'scope' => $this->institution_scope_mapping[ $domain ],
		);

		foreach ( $favorites['records'] as $favorite ) {

			// Fetch each of the favorites by ID as a full Primo object.
			$request_args['q'] = 'any,contains,' . preg_replace( '/alma/', '', $favorite['recordId'] );

			$records = $this->request( 'search', $request_args );

			if ( ! is_wp_error( $records ) ) {
				foreach ( $records['docs'] as $record ) {
					$favorite_ids[] = $this->cache_primo_resource( $record, $tax_input, $domain );
				}
			}
		}

		if ( ! is_wp_error( $favorites ) ) {
			update_field( 'primo_favorites', $favorite_ids, 'user_' . $user_id );
		}

		return $favorite_ids;
	}

	/**
	 * Retrieve databases for the given search term.
	 *
	 * @since 1.0.0
	 *
	 * @param string $term   Search term.
	 * @param string $domain Institution domain name.
	 *
	 * @return array         Array of Primo resources.
	 */
	public function get_databases_for_term( $term, $domain ) {
		$query = array(
			'vid'       => $this->get_vid_by_domain( $domain ),
			'tab'       => 'default_tab',
			'scope'     => 'everything',
			'databases' => 'any,' . $term,
		);

		$database_query = $this->request( 'search', $query );

		return $database_query['docs'];
	}

	/**
	 * Retrieve PNX data for the specified resource.
	 *
	 * @since 1.0.0
	 *
	 * @param string $primo_id Primo ID.
	 *
	 * @return array|WP_Error  Primo resource.
	 */
	public function get_pnx_data( $primo_id ) {
		$query = array(
			'q' => 'any,contains,' . $primo_id,
		);

		$results = $this->request( 'pnxs', $query );

		if ( is_wp_error( $results ) ) {
			return $results;
		}

		return $results['docs'];
	}

}
