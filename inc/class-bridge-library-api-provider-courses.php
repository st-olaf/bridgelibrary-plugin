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
class Bridge_Library_API_Provider_Courses {

	/**
	 * CPT key.
	 *
	 * @since 1.0.0
	 *
	 * @var string $cpt
	 */
	public $post_type = 'course';

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

		// Add data to each item.
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
	 * @param WP_REST_Response $response Response object.
	 * @param WP_Post          $post     Post object.
	 * @param WP_REST_Request  $request  Request object.
	 *
	 * @return WP_REST_Response          Response object.
	 */
	public function prepare_post( $response, $post, $request ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bridge_library_{$this->post_type}_meta WHERE post_id = %d", $post->ID ), 'ARRAY_A' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $row ) ) {
			$row = new stdClass();
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

		if ( 'full' === $request->get_param( 'view' ) ) {
			$related_post_ids = json_decode( $row['related_courses_resources'] );
			if ( ! empty( $related_post_ids ) ) {
				$rest_controller = new WP_REST_Posts_Controller( 'resource' );
				$sub_request     = $request;
				$sub_request->set_param( 'view', null );

				$data = array();
				foreach ( $related_post_ids as $post_id ) {
					$post = get_post( $post_id );
					if ( isset( $post ) ) {
						$post_obj = $rest_controller->prepare_item_for_response( $post, $sub_request );
						$data[]   = $post_obj->data;
					}
				}
				$row['related_courses_resources'] = $data;
			}

			$core_post_ids = json_decode( $row['related_courses_resources'] );
			if ( ! empty( $core_post_ids ) ) {
				$rest_controller = new WP_REST_Posts_Controller( 'resource' );
				$sub_request     = $request;
				$sub_request->set_param( 'view', null );

				$data = array();
				foreach ( $core_post_ids as $post_id ) {
					$post = get_post( $post_id );
					if ( isset( $post ) ) {
						$post_obj = $rest_controller->prepare_item_for_response( $post, $sub_request );
						$data[]   = $post_obj->data;
					}
				}
				$row['core_resources'] = $data;
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
			'description'       => __( 'Course code to look up.', 'bridge-library' ),
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
			$courses = Bridge_Library_Courses::get_instance();
			$post_id = $courses->get_post_ids_by_course_codes( array( $course_code ) );

			// Handle invalid course codes.
			if ( 0 === $post_id ) {
				add_filter( 'posts_pre_query', '__return_empty_array' );
				return $args;
			} else {
				$args['post__in'] = $post_id;
			}
		}

		return $args;
	}

}
