<?php
/**
 * Bridge Library data structure class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library data structure class.
 *
 * @since 1.0.0
 */
class Bridge_Library_Data_Structure {

	/**
	 * Custom post types.
	 *
	 * @since 1.0.0
	 *
	 * @var array $custom_post_types
	 */
	public $custom_post_types = array(
		'course',
		'resource',
		'librarian',
	);

	/**
	 * Custom taxonomies.
	 *
	 * @since 1.0.0
	 *
	 * @var array $custom_taxonomies
	 */
	public $custom_taxonomies = array(
		'resource_type',
		'resource_format',
		'institution',
		'academic_department',
		'course_term',
	);

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_Data_Structure class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_Data_Structure();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Register CPTs and taxonomies.
		add_action( 'init', array( $this, 'register_cpt' ) );

		/**
		 * Disable storing of meta data values in core meta tables where a custom
		 * database table has been defined for fields. Any fields that aren't mapped
		 * to a custom database table will still be stored in the core meta tables.
		 */
		add_filter( 'acfcdt/settings/store_acf_values_in_core_meta', '__return_false' );

		/**
		 * Disable storing of ACF field key references in core meta tables where a custom
		 * database table has been defined for fields. Any fields that aren't mapped to a
		 * custom database table will still have their key references stored in the core
		 * meta tables.
		 */
		add_filter( 'acfcdt/settings/store_acf_keys_in_core_meta', '__return_false' );

		// Override LuminFire ACF JSON storage directory.
		add_filter( 'acf/settings/save_json', array( $this, 'get_local_json_path' ), 25 );
		add_filter( 'acf/settings/load_json', array( $this, 'add_local_json_path' ), 25 );

		// Handle Post-2-Post quirks.
		// TODO: revisit after https://github.com/Hube2/acf-post2post/pull/31/ is merged.
		add_action( 'acf/post2post/relationship_updated', array( $this, 'post_2_post' ), 10, 3 );

		// Supply the number of post types per term.
		add_filter( 'get_terms', array( $this, 'get_term_cpt_count' ), 10, 4 );

		// Add filter dropdown to resources.
		add_action( 'restrict_manage_posts', array( $this, 'filter_resources_by_taxonomy' ) );
	}

	/**
	 * Register CPT and taxonomies.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_cpt() {

		/**
		 * Courses CPT.
		 */
		register_extended_post_type(
			'course',
			array(
				'menu_icon'           => 'dashicons-welcome-learn-more',
				'show_in_rest'        => true,
				'show_in_graphql'     => true,
				'graphql_single_name' => 'Course',
				'graphql_plural_name' => 'Courses',
				'capability_type'     => 'course',
				'capabilities'        => array(
					'publish_posts'       => 'publish_courses',
					'edit_posts'          => 'edit_courses',
					'edit_others_posts'   => 'edit_others_courses',
					'delete_posts'        => 'delete_courses',
					'delete_others_posts' => 'delete_others_courses',
					'read_private_posts'  => 'read_private_courses',
					'edit_post'           => 'edit_course',
					'delete_post'         => 'delete_course',
					'read_post'           => 'read_course',
				),
				'admin_cols'          => array(
					'institution'         => array(
						'title'    => 'Institution',
						'taxonomy' => 'institution',
					),
					'academic_department' => array(
						'title'    => 'Academic Department',
						'taxonomy' => 'academic_department',
					),
					'course_term'         => array(
						'title'    => 'Course Term',
						'taxonomy' => 'course_term',
					),
				),
			)
		);

		/**
		 * Resources CPT.
		 */
		register_extended_post_type(
			'resource',
			array(
				'menu_icon'           => 'dashicons-book-alt',
				'show_in_rest'        => true,
				'show_in_graphql'     => true,
				'graphql_single_name' => 'Resource',
				'graphql_plural_name' => 'Resources',
				'capability_type'     => 'resource',
				'capabilities'        => array(
					'publish_posts'       => 'publish_resources',
					'edit_posts'          => 'edit_resources',
					'edit_others_posts'   => 'edit_others_resources',
					'delete_posts'        => 'delete_resources',
					'delete_others_posts' => 'delete_others_resources',
					'read_private_posts'  => 'read_private_resources',
					'edit_post'           => 'edit_resource',
					'delete_post'         => 'delete_resource',
					'read_post'           => 'read_resource',
				),
				'admin_cols'          => array(
					'resource_type'       => array(
						'title'    => 'Resource Type',
						'taxonomy' => 'resource_type',
					),
					'academic_department' => array(
						'title'    => 'Academic Department',
						'taxonomy' => 'academic_department',
					),
				),
			)
		);

		/**
		 * Librarians CPT.
		 */
		register_extended_post_type(
			'librarian',
			array(
				'menu_icon'           => 'dashicons-groups',
				'show_in_rest'        => true,
				'show_in_graphql'     => true,
				'graphql_single_name' => 'Librarian',
				'graphql_plural_name' => 'Librarians',
				'capability_type'     => 'librarian',
				'capabilities'        => array(
					'publish_posts'       => 'publish_librarians',
					'edit_posts'          => 'edit_librarians',
					'edit_others_posts'   => 'edit_others_librarians',
					'delete_posts'        => 'delete_librarians',
					'delete_others_posts' => 'delete_others_librarians',
					'read_private_posts'  => 'read_private_librarians',
					'edit_post'           => 'edit_librarian',
					'delete_post'         => 'delete_librarian',
					'read_post'           => 'read_librarian',
				),
				'admin_cols'          => array(
					'academic_department' => array(
						'title'    => 'Academic Department',
						'taxonomy' => 'academic_department',
					),
				),
			)
		);

		/**
		 * Hidden course taxonomy.
		 */
		register_extended_taxonomy(
			'hidden',
			array(
				'course',
			),
			array(
				'capabilities'        => array(
					'assign_terms' => 'assign_terms_bridge_library',
					'delete_terms' => 'delete_terms_bridge_library',
					'edit_terms'   => 'edit_terms_bridge_library',
					'manage_terms' => 'manage_terms_bridge_library',
				),
				'show_in_graphql'     => true,
				'graphql_single_name' => 'HiddenCourse',
				'graphql_plural_name' => 'HiddenCourses',
			)
		);

		/**
		 * Resource type taxonomy.
		 */
		register_extended_taxonomy(
			'resource_type',
			array(
				'resource',
			),
			array(
				'capabilities'        => array(
					'assign_terms' => 'assign_terms_bridge_library',
					'delete_terms' => 'delete_terms_bridge_library',
					'edit_terms'   => 'edit_terms_bridge_library',
					'manage_terms' => 'manage_terms_bridge_library',
				),
				'show_in_graphql'     => true,
				'graphql_single_name' => 'ResourceType',
				'graphql_plural_name' => 'ResourceTypes',
			)
		);

		/**
		 * Resource format taxonomy.
		 */
		register_extended_taxonomy(
			'resource_format',
			array(
				'resource',
			),
			array(
				'meta_box_cb'         => false,
				'capabilities'        => array(
					'assign_terms' => 'assign_terms_bridge_library',
					'delete_terms' => 'delete_terms_bridge_library',
					'edit_terms'   => 'edit_terms_bridge_library',
					'manage_terms' => 'manage_terms_bridge_library',
				),
				'show_in_graphql'     => true,
				'graphql_single_name' => 'ResourceFormat',
				'graphql_plural_name' => 'ResourceFormats',
			)
		);

		/**
		 * Institution taxonomy.
		 */
		register_extended_taxonomy(
			'institution',
			array(
				'course',
				'resource',
				'librarian',
			),
			array(
				'meta_box_cb'           => false,
				'capabilities'          => array(
					'assign_terms' => 'assign_terms_bridge_library',
					'delete_terms' => 'delete_terms_bridge_library',
					'edit_terms'   => 'edit_terms_bridge_library',
					'manage_terms' => 'manage_terms_bridge_library',
				),
				'update_count_callback' => array( $this, 'update_count_callback' ),
				'show_in_graphql'       => true,
				'graphql_single_name'   => 'Institution',
				'graphql_plural_name'   => 'Institutions',
			)
		);

		/**
		 * Academic Department taxonomy.
		 */
		register_extended_taxonomy(
			'academic_department',
			array(
				'course',
				'resource',
				'librarian',
			),
			array(
				'meta_box_cb'         => false,
				'show_in_graphql'     => true,
				'graphql_single_name' => 'AcademicDepartment',
				'graphql_plural_name' => 'AcademicDepartments',
			)
		);

		/**
		 * Course term taxonomy.
		 */
		register_extended_taxonomy(
			'course_term',
			array(
				'course',
			),
			array(
				'show_in_graphql'     => true,
				'graphql_single_name' => 'CourseTerm',
				'graphql_plural_name' => 'CourseTerms',
			)
		);
	}

	/**
	 * Update the custom ACF table for post-2-post relationships.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_id    The post ID.
	 * @param string $field_name ACF field name.
	 * @param mixed  $value      New value.
	 *
	 * @return void
	 */
	public function post_2_post( $post_id, $field_name, $value ) {
		if ( 0 === $post_id ) {
			return;
		}

		$post_type = get_post_type( $post_id );

		if ( in_array( $post_type, array( 'course', 'resource' ), true ) ) {
			if ( 'course' === $post_type ) {
				$api = Bridge_Library_Courses::get_instance();
			} elseif ( 'resource' === $post_type ) {
				$api = Bridge_Library_Resources::get_instance();
			}

			$api->update_related_post_ids( (int) $post_id, $value );
		}
	}

	/**
	 * Store count of custom post types per tax term.
	 *
	 * @since 1.0.0
	 *
	 * @param array       $terms    Array of term objects.
	 * @param WP_Taxonomy $taxonomy Taxonomy object.
	 *
	 * @return void
	 */
	public function update_count_callback( $terms, $taxonomy ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $wpdb;

		foreach ( $terms as $term ) {
			$query       = "SELECT post_type, COUNT(ID) AS quantity FROM $wpdb->posts WHERE post_type IN ('" . implode( "','", $this->custom_post_types ) . "') AND ID IN (SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d) GROUP BY post_type;";
			$post_counts = $wpdb->get_results( $wpdb->prepare( $query, $term ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- false positive
			foreach ( $post_counts as $count ) {
				update_term_meta( $term, 'post_count_' . $count->post_type, $count->quantity );
			}
		}
	}

	/**
	 * Replace the term count with term count for the current post type.
	 *
	 * @param array         $terms      Array of found terms.
	 * @param array         $taxonomies An array of taxonomies.
	 * @param array         $args       An array of get_terms() arguments.
	 * @param WP_Term_Query $term_query The WP_Term_Query object.
	 *
	 * @return array                    Array of found terms.
	 */
	public function get_term_cpt_count( $terms, $taxonomies, $args, $term_query ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! is_admin() || is_customize_preview() ) {
			return $terms;
		}

		$current_screen = get_current_screen();

		if ( is_null( $current_screen ) ) {
			return $terms;
		}

		if ( in_array( $current_screen->post_type, $this->custom_post_types, true ) && in_array( $current_screen->taxonomy, $this->custom_taxonomies, true ) ) {
			foreach ( $terms as $term ) {
				if ( ! $term instanceof WP_Term ) {
					continue;
				}

				$term->count = get_term_meta( $term->term_id, 'post_count_' . $current_screen->post_type, true );
			}
		}

		return $terms;
	}

	/**
	 * Display a custom taxonomy dropdown in admin.
	 */
	public function filter_resources_by_taxonomy() {
		global $typenow;

		if ( 'resource' === $typenow ) {
			$taxonomies = array(
				'resource_type',
				'academic_department',
			);

			foreach ( $taxonomies as $taxonomy ) {
				$selected      = array_key_exists( $taxonomy, $_GET ) && ! empty( $_GET[ $taxonomy ] ) ? sanitize_key( wp_unslash( $_GET[ $taxonomy ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$info_taxonomy = get_taxonomy( $taxonomy );
				wp_dropdown_categories(
					array(
						// Translators: %s is the taxonomy name.
						'show_option_all' => sprintf( __( 'Show all %s', 'bridge-library' ), $info_taxonomy->label ),
						'taxonomy'        => $taxonomy,
						'name'            => $taxonomy,
						'orderby'         => 'name',
						'selected'        => $selected,
						'hide_empty'      => true,
						'value_field'     => 'slug',
					)
				);
			}
		}
	}

	/**
	 * Load ACF JSON from plugin directory.
	 *
	 * @return string
	 */
	public function get_local_json_path() {
		$directory = BL_PLUGIN_DIR . '/acf-json';

		if ( ! is_dir( $directory ) ) {
			mkdir( $directory );
		}

		return $directory;
	}

	/**
	 * Store ACF JSON in plugin directory.
	 *
	 * @param array $paths Storage paths.
	 *
	 * @return array
	 */
	public function add_local_json_path( $paths ) {
		$paths[] = BL_PLUGIN_DIR . '/acf-json';
		return $paths;
	}

}
