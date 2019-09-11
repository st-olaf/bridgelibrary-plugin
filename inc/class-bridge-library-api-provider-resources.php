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
class Bridge_Library_API_Provider_Resources {

	/**
	 * CPT key.
	 *
	 * @since 1.0.0
	 *
	 * @var string $cpt
	 */
	public $post_type = 'resource';

	/**
	 * Custom DB columns to unset.
	 *
	 * @since 1.0.0
	 *
	 * @var array $unset
	 */
	public $unset = array(
		'id',
		'post_id',
	);

	/**
	 * Taxonomy keys to expand.
	 *
	 * @since 1.0.0
	 *
	 * @var array $taxonomies
	 */
	public $taxonomies = array(
		'academic_department',
		'course_term',
		'institution',
	);

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_filter( "rest_prepare_{$this->post_type}", array( $this, 'prepare_post' ), 10, 3 );

		// Add custom query parameters.
		add_filter( "rest_{$this->post_type}_collection_params", array( $this, 'collection_params' ), 10, 2 );
		add_filter( "rest_{$this->post_type}_query", array( $this, 'post_query' ), 10, 2 );
	}

	/**
	 * Add custom data to REST API response.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request  $response Response object.
	 * @param WP_Post          $post     Post object.
	 * @param WP_REST_Response $request  Request object.
	 *
	 * @return WP_REST_Response            Response object.
	 */
	public function prepare_post( $response, $post, $request ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bridge_library_{$this->post_type}_meta WHERE post_id = %d", $post->ID ), 'ARRAY_A' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $row ) ) {
			$row = array();
		} else {
			foreach ( $this->unset as $key ) {
				unset( $row[ $key ] );
			}

			foreach ( $this->taxonomies as $taxonomy ) {
				$terms = get_the_terms( $post->ID, $taxonomy );
				if ( empty( $terms ) ) {
					$terms = array();
				}
				$row[ $taxonomy ] = $terms;
			}
		}

		// Add URL based on user’s institution.
		if ( is_user_logged_in() && empty( $row['url'] ) && ! empty( $row['primo_id'] ) ) {
			$user       = Bridge_Library_Users::get_instance();
			$domain     = $user->get_domain();
			$primo      = Bridge_Library_API_Primo::get_instance();
			$row['url'] = $primo->generate_full_view_url( $row['primo_id'], $domain );
		}

		// Add librarian info.
		if ( ! empty( $row['librarian_user_id'] ) ) {
			$user = get_user_by( 'id', $row['librarian_user_id'] );

			$user_data = array(
				'ID'              => $user->ID,
				'first_name'      => $user->first_name,
				'last_name'       => $user->last_name,
				'display_name'    => $user->display_name,
				'user_email'      => $user->user_email,
				'user_url'        => $user->user_url,
				'picture_url'     => get_field( 'picture_url', 'user_' . $user->ID ),
				'phone_number'    => get_field( 'librarian_phone_number', 'user_' . $user->ID ),
				'office_location' => get_field( 'librarian_office_location', 'user_' . $user->ID ),
			);

			$custom_image = get_field( 'librarian_picture_url', 'user_' . $user->ID );
			if ( ! empty( $custom_image ) ) {
				$user_data['picture_url'] = $custom_image;
			}

			$custom_email = get_field( 'librarian_email_address', 'user_' . $user->ID );
			if ( ! empty( $custom_email ) ) {
				$user_data['user_email'] = $custom_email;
			}

			$row['librarian_user_id'] = $user_data;
		}

		// Show full data if requested.
		if ( 'full' === $request->get_param( 'view' ) ) {
			$post_ids = json_decode( $row['related_courses_resources'] );

			if ( ! empty( $post_ids ) ) {
				$rest_controller = new WP_REST_Posts_Controller( 'course' );
				$sub_request     = $request;
				$sub_request->set_param( 'view', null );

				$data = array();
				foreach ( $post_ids as $post_id ) {
					$post = get_post( $post_id );
					if ( isset( $post ) ) {
						$post_obj = $rest_controller->prepare_item_for_response( $post, $sub_request );
						$data[]   = $post_obj->data;
					}
				}
				$row['related_courses_resources'] = $data;
			}
		}

		$response->data[ $this->post_type . '_data' ] = $row;

		$logging  = Bridge_Library_Logging::get_instance();
		$function = 'get_' . $this->post_type;
		call_user_func( array( $logging, $function ), $post->ID );

		return $response;
	}

	/**
	 * Add custom query parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $query_params Query parameters.
	 * @param string $post_type    Post type.
	 *
	 * @return array               Query parameters.
	 */
	public function collection_params( $query_params, $post_type ) {

		$params['course_code'] = array(
			'description'       => __( 'Limit resources to those for this course code.', 'bridge-library' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['view'] = array(
			'description'       => __( 'If set to “full,” the related resource fields will be populated with full JSON objects rather than an array of WordPress post IDs.', 'bridge-library' ),
			'type'              => 'bool',
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $query_params;
	}

	/**
	 * Filter the query arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array           $args    Key value array of query var to query value.
	 * @param WP_REST_Request $request The request used.
	 */
	public function post_query( $args, $request ) {
		$course_code = $request->get_param( 'course_code' );

		if ( ! empty( $course_code ) ) {

			// Note: a meta query will not work because all the ACF data is stored in custom tables.
			$courses      = Bridge_Library_Courses::get_instance();
			$course_data  = $courses->get_post_data_by_course_codes( array( $course_code ) );
			$resource_ids = array();

			foreach ( $course_data as $course ) {
				if ( ! empty( $course['related_courses_resources'] ) ) {
					$resource_ids = array_merge( $resource_ids, json_decode( $course['related_courses_resources'] ) );
				}
			}

			// Handle invalid course codes.
			if ( empty( $resource_ids ) ) {
				add_filter( 'posts_pre_query', '__return_empty_array' );
				return $args;
			} else {
				$args['post__in'] = $resource_ids;
			}
		}

		return $args;
	}

}
