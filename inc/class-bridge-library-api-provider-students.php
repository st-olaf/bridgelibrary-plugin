<?php
/**
 * Bridge Library API class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library API class.
 *
 * @since 1.0.0
 */
class Bridge_Library_API_Provider_Students extends Bridge_Library_API_Provider_Base {

	/**
	 * Endpoint base URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rest_base
	 */
	public $rest_base = 'student';

	/**
	 * Parameter mappings.
	 *
	 * This array defines mappings between public API query parameters whose
	 * values are accepted as-passed, and their internal WP_Query parameter
	 * name equivalents (some are the same). Only values which are also
	 * present in $registered will be set.
	 *
	 * @since 1.0.0
	 *
	 * @var array $parameter_mappings
	 */
	public $parameter_mappings = array(
		'exclude'  => 'exclude',
		'include'  => 'include',
		'order'    => 'order',
		'per_page' => 'number',
		'search'   => 'search',
		'slug'     => 'nicename__in',
	);

	/**
	 * CPT keys to expand when view is “full.”
	 *
	 * @since 1.0.0
	 *
	 * @var array $full_data_cpts
	 */
	public $full_data_cpts = array(
		'courses',
		'resources',
		'primo_favorites',
	);

	/**
	 * Custom taxonomy keys to expand when view is “full.”
	 *
	 * @since 1.0.0
	 *
	 * @var array $full_data_taxonomies
	 */
	public $full_data_taxonomies = array(
		'academic_departments',
	);

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() {

		// All students.
		register_rest_route(
			$this->namespace,
			$this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		// An individual student by WP ID.
		register_rest_route(
			$this->namespace,
			$this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

	}

	/**
	 * Get student users matching the specified args.
	 *
	 * @since 1.0.0
	 *
	 * @param array           $args    Query args.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array                   User objects.
	 */
	public function get_student_users( $args = array(), $request ) {
		$user_args = wp_parse_args(
			$args,
			array(
				'role__in' => array(
					'bridge_library_staff',
					'subscriber',
				),
			)
		);

		$users = get_users( $user_args );

		$results = array();

		foreach ( $users as $user ) {
			$user_response = $this->prepare_item_for_response( $user, $request );

			if ( is_a( $user_response, 'WP_REST_Response' ) ) {
				return $user_response;
			}

			$results[] = $user_response;
		}

		return $results;
	}

	/**
	 * Prepare a user for REST response.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User         $user    WP user object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array                   Limited user data.
	 */
	public function prepare_item_for_response( $user, $request ) {
		$user_data = array(
			'ID'                      => $user->ID,
			'courses_updated'         => (int) get_field( 'courses_cache_updated', 'user_' . $user->ID ),
			'resources_updated'       => (int) get_field( 'resources_cache_updated', 'user_' . $user->ID ),
			'primo_favorites_updated' => (int) get_field( 'primo_favorites_cache_updated', 'user_' . $user->ID ),
		);

		if ( $request->get_param( 'timestamp' ) ) {
			$request_timestamp = (int) $request->get_param( 'timestamp' );

			// Handle JS date with microseconds.
			// This will break at 2286-11-20 17:46:40 and needs to be updated before then.
			if ( strlen( $request_timestamp ) > 10 ) {
				$request_timestamp = (int) substr( $request_timestamp, 0, 10 );
			}

			if ( $request_timestamp >= $user_data['courses_updated'] && $request_timestamp >= $user_data['resources_updated'] && $request_timestamp >= $user_data['primo_favorites_updated'] ) {
				return new WP_REST_Response(
					array(
						'No new data',
					),
					304
				);
			}
		}

		$user_data = array(
			'academic_departments'    => get_field( 'academic_departments', 'user_' . $user->ID ),
			'courses'                 => get_field( 'courses', 'user_' . $user->ID ),
			'courses_updated'         => get_field( 'courses_cache_updated', 'user_' . $user->ID ),
			'resources'               => get_field( 'resources', 'user_' . $user->ID ),
			'resources_updated'       => get_field( 'resources_cache_updated', 'user_' . $user->ID ),
			'primo_favorites'         => get_field( 'primo_favorites', 'user_' . $user->ID ),
			'primo_favorites_updated' => get_field( 'primo_favorites_cache_updated', 'user_' . $user->ID ),
		);

		// Show personal data only to the logged-in user.
		if ( is_user_logged_in() ) {
			$user = wp_get_current_user();
			if ( $user->ID === $request->get_param( 'id' ) ) {
				$user_data['first_name']  = $user->first_name;
				$user_data['last_name']   = $user->last_name;
				$user_data['picture_url'] = get_field( 'picture_url', 'user_' . $user->ID );
			}
		}

		if ( 'full' === $request->get_param( 'view' ) ) {

			// CPTs.
			foreach ( $this->full_data_cpts as $key ) {
				$post_ids = $user_data[ $key ];

				$post_type = rtrim( $key, 's' );

				// Special handling for Primo favorites since they are resource CPT, but stored separately.
				if ( 'primo_favorite' === $post_type ) {
					$post_type = 'resource';
				}

				$rest_controller = new WP_REST_Posts_Controller( $post_type );

				if ( ! empty( $post_ids ) ) {
					$data = array();
					foreach ( $post_ids as $post_id ) {
						$post = get_post( $post_id );
						if ( isset( $post ) ) {
							$post_obj = $rest_controller->prepare_item_for_response( $post, $request );
							$data[]   = $post_obj->data;
						}
					}
					$user_data[ $key ] = $data;
				}
			}

			// Taxonomies.
			foreach ( $this->full_data_taxonomies as $key ) {
				$term_ids = $user_data[ $key ];

				$taxonomy = rtrim( $key, 's' );

				$rest_controller = new WP_REST_Terms_Controller( $taxonomy );

				if ( ! empty( $term_ids ) ) {
					$data = array();
					foreach ( $term_ids as $term_id ) {
						$term = get_term( $term_id );
						if ( isset( $term ) ) {
							$term_obj = $rest_controller->prepare_item_for_response( $term, $request );
							$data[]   = $term_obj->data;
						}
					}
					$user_data[ $key ] = $data;
				}
			}
		}

		$user_data['_links'] = $this->prepare_links( $user_data );

		// Action hook for logging.
		do_action( 'bl_api_get_user', $user->ID );

		return $user_data;
	}

	/**
	 * Prepares links for the request.
	 *
	 * @since 1.0.0
	 *
	 * @param array $user_data User data array.
	 *
	 * @return array           Links for the given user.
	 */
	protected function prepare_links( $user_data ) {
		$base = sprintf( '%s/%s', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $user_data['ID'] ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		return $links;
	}

	/**
	 * Get one student by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response        Response object.
	 */
	public function get_item( $request ) {

		$args = array(
			'include' => $request->get_param( 'id' ),
		);

		$args = $this->parse_param_args( $args, $request );

		$users    = $this->get_student_users( $args, $request );
		$response = rest_ensure_response( $users );

		return $response;
	}

	/**
	 * Get list of students.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response        Response object.
	 */
	public function get_items( $request ) {

		$args = array();

		$args = $this->parse_param_args( $args, $request );

		$users    = $this->get_student_users( $args, $request );
		$response = rest_ensure_response( $users );

		return $response;
	}

	/**
	 * Map API parameters to query parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param array           $args    Array for query arguments.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array                   Array of query arguments.
	 */
	public function parse_param_args( $args, $request ) {
		/**
		 * For each known parameter which is both registered and present in the request,
		 * set the parameter's value on the query $args.
		 */
		foreach ( $this->parameter_mappings as $api_param => $wp_param ) {
			if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
				$args[ $wp_param ] = $request[ $api_param ];
			}
		}

		if ( ! empty( $request->get_param( 'email_address' ) ) ) {
			$args['search']         = $request->get_param( 'email_address' );
			$args['search_columns'] = array( 'email' );
		}

		return $args;
	}

	/**
	 * Add custom data to collection params.
	 *
	 * @since 1.0.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['email'] = array(
			'description'       => __( 'Email address of the user to look up.', 'bridge-library' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_email',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['view'] = array(
			'description'       => __( 'If set to “full,” the courses, resources, and primo_favorites fields will be populated with full JSON objects rather than an array of WordPress post IDs.', 'bridge-library' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['timestamp'] = array(
			'description'       => __( 'Accepts a timestamp to compare to the user’s cache metadata timestamp. If the cache timestamp is older, a `304 Not Modified` response will be returned without any data.', 'bridge-library' ),
			'type'              => 'integer',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}

	/**
	 * Checks if a given request has access to get items.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_Error|bool True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		return true;
	}

	/**
	 * Add response schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array Response schema.
	 */
	public function get_public_item_schema() {

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'student',
			'type'       => 'object',
			'properties' => array(
				'ID'                      => array(
					'description' => esc_html__( 'Unique identifier for the student.', 'bridge-library' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'academic_departments'    => array(
					'description' => esc_html__( 'Academic departments for the student.', 'bridge-library' ),
					'type'        => 'array|object',
				),
				'courses'                 => array(
					'description' => esc_html__( 'Courses the student is enrolled in.', 'bridge-library' ),
					'type'        => 'array|object',
				),
				'courses_updated'         => array(
					'description' => esc_html__( 'Unix timestamp courses were last updated.', 'bridge-library' ),
					'type'        => 'integer',
				),
				'resources'               => array(
					'description' => esc_html__( 'Resources suggested for the student based on their acamedic departments and courses.', 'bridge-library' ),
					'type'        => 'array|object',
				),
				'resources_updated'       => array(
					'description' => esc_html__( 'Unix timestamp resources were last updated.', 'bridge-library' ),
					'type'        => 'integer',
				),
				'primo_favorites'         => array(
					'description' => esc_html__( 'Primo resources the student has favorited.', 'bridge-library' ),
					'type'        => 'array|object',
				),
				'primo_favorites_updated' => array(
					'description' => esc_html__( 'Unix timestamp Primo favorites were last updated.', 'bridge-library' ),
					'type'        => 'integer',
				),
			),
		);

		return $schema;
	}

}
