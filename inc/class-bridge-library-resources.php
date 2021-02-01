<?php
/**
 * Bridge Library resources class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library resources class.
 *
 * @since 1.0.0
 */
class Bridge_Library_Resources extends Bridge_Library {

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * ACF Custom DB table name (without wpdb prefix).
	 *
	 * @since 1.0.0
	 *
	 * @var string $acf_meta_table
	 */
	private $acf_meta_table = 'bridge_library_resource_meta';

	/**
	 * Citation (reading list) mapping.
	 *
	 * @since 1.0.0
	 *
	 * @var array $citation_mapping
	 */
	private $citation_mapping = array(

		// Alma => ACF.
		'mms_id' => 'primo_id',
		'author' => 'author',
		'isbn'   => 'isbn',
		'year'   => 'publication_year',
	);

	/**
	 * Primo mapping.
	 *
	 * @since 1.0.0
	 *
	 * @var array $primo_mapping
	 */
	private $primo_mapping = array(

		// Primo => ACF.
		'recordid' => 'primo_id',
		'author'   => 'author',
	);

	/**
	 * Primo PNX data mapping.
	 *
	 * @since 1.0.0
	 *
	 * @var array $primo_pnx_mapping
	 */
	private $primo_pnx_mapping = array(

		// Primo => ACF.
		'creationdate' => 'publication_year',
	);

	/**
	 * LibGuides Asset data mapping.
	 *
	 * @since 1.0.0
	 *
	 * @var array $libguides_asset_mapping
	 */
	private $libguides_asset_mapping = array(

		// LibGuides => ACF.
		'id'  => 'libguides_id',
		'url' => 'url',
	);

	/**
	 * LibGuides Guide data mapping.
	 *
	 * @since 1.0.0
	 *
	 * @var array $libguides_guide_mapping
	 */
	private $libguides_guide_mapping = array(

		// LibGuides => ACF.
		'id'           => 'libguides_id',
		'friendly_url' => 'url',
	);

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_Resources class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_Resources();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Cache data from Primo PNX API.
		add_action( 'acf/save_post', array( $this, 'cache_metadata' ), 10 );

		// Add to department related resources.
		add_action( 'acf/update_value/key=field_5cc327908d3f4', array( $this, 'save_department_resources' ), 8, 3 );

		// Add ajax button to refresh Primo resources.
		add_action( 'academic_department_edit_form', array( $this, 'add_tax_term_actions' ), 10, 2 );
		add_action( 'wp_ajax_cache_primo_resources', array( $this, 'ajax_cache_primo_resources' ) );

		// Admin tools page and ajax hooks.
		add_action( 'bridge_library_admin_settings', array( $this, 'admin_refresh_resources_text' ) );
		add_action( 'wp_ajax_create_resource_from_libguides_assets', array( $this, 'ajax_create_resource_from_libguides_assets' ) );
		add_action( 'wp_ajax_update_libguides_resource_by_id', array( $this, 'ajax_update_libguides_resource_by_id' ) );
		add_action( 'wp_ajax_start_bg_libguides_assets_update', array( $this, 'ajax_start_bg_libguides_assets_update' ) );
		add_action( 'wp_ajax_start_bg_libguides_guides_update', array( $this, 'ajax_start_bg_libguides_guides_update' ) );
		add_action( 'wp_ajax_import_libguides_to_course', array( $this, 'ajax_import_libguides_to_course' ) );

		// Force Publication Year to a string for GraphQL.
		add_filter( 'acf/load_value/key=field_5cc87637abbc6', array( $this, 'force_publication_year_string_load' ) );
		add_filter( 'acf/update_value/key=field_5cc87637abbc6', array( $this, 'force_publication_year_string_update' ) );

		// Tweak ACF fields.
		add_filter( 'acf/prepare_field/key=field_5d5c682cb83a2', array( $this, 'load_catalyst_field' ) );
		add_filter( 'acf/prepare_field/key=field_5d707ba9173d4', array( $this, 'load_course_links_field' ) );
		add_filter( 'acf/prepare_field/key=field_5cd9abad8a9cb', array( $this, 'disable_primo_image_url_field' ) );
		add_filter( 'acf/prepare_field/key=field_5cc86dd2d9f71', array( $this, 'disable_primo_image_url_field' ) );
		add_filter( 'acf/prepare_field/key=field_5fcff25f7b23b', array( $this, 'add_libguides_import_button' ) );

		// Load backend JS.
		add_action(
			'acf/input/admin_enqueue_scripts',
			function() {
				wp_enqueue_script( 'bridge-library-admin' );
			}
		);
	}

	/**
	 * Get post ID for the given Alma ID.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Fall back to wp_postmeta.
	 *
	 * @param int $alma_id Alma ID.
	 *
	 * @return int|null    WP course ID.
	 */
	public function get_post_id_by_alma_id( $alma_id ) {
		global $wpdb;
		$post_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- this is more performant than a get_posts with meta_query would be.
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE alma_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
				$alma_id
			)
		);

		// If ACF custom DB fails, search wp_postmeta.
		if ( 0 === $post_id ) {
			$post_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'alma_id' AND meta_value = %s;",
					$alma_id
				)
			);
		}

		// Set to null for downstream behavior.
		if ( 0 === $post_id ) {
			$post_id = null;
		}

		return $post_id;
	}

	/**
	 * Get post ID for the given Primo ID.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Fall back to wp_postmeta.
	 *
	 * @param int $primo_id Primo ID.
	 *
	 * @return int|null     WP course ID.
	 */
	public function get_post_id_by_primo_id( $primo_id ) {
		global $wpdb;
		$post_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- this is more performant than a get_posts with meta_query would be.
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE primo_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
				$primo_id
			)
		);

		// If ACF custom DB fails, search wp_postmeta.
		if ( 0 === $post_id ) {
			$post_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'primo_id' AND meta_value = %s;",
					$primo_id
				)
			);
		}

		// Set to null for downstream behavior.
		if ( 0 === $post_id ) {
			$post_id = null;
		}

		return $post_id;
	}

	/**
	 * Get post ID for the given LibGuides ID.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Fall back to wp_postmeta.
	 *
	 * @param int $libguides_id LibGuides ID.
	 *
	 * @return int|null         WP course ID.
	 */
	public function get_post_id_by_libguides_id( $libguides_id ) {
		global $wpdb;
		$post_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- this is more performant than a get_posts with meta_query would be.
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE libguides_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
				$libguides_id
			)
		);

		// If ACF custom DB fails, search wp_postmeta.
		if ( 0 === $post_id ) {
			$post_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'libguides_id' AND meta_value = %s;",
					$libguides_id
				)
			);
		}

		// Set to null for downstream behavior.
		if ( 0 === $post_id ) {
			$post_id = null;
		}

		return $post_id;
	}

	/**
	 * Manually update the custom table with related resource IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $post_id Post ID.
	 * @param mixed $value   New value.
	 *
	 * @return bool          Whether database was updated or not.
	 */
	public function update_related_post_ids( $post_id, $value ) {
		global $wpdb;

		// Convert string IDs to integers.
		$value = array_map(
			function( $id ) {
				return absint( $id );
			},
			$value
		);

		$value = wp_json_encode( $value );

		$update = $wpdb->update( $wpdb->prefix . $this->acf_meta_table, array( 'related_courses_resources' => $value ), array( 'post_id' => $post_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return false !== $update;
	}

	/**
	 * Save resources to academic departments ACF field.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value   Field value.
	 * @param int   $post_id Post ID.
	 * @param array $field   ACF Field object.
	 *
	 * @return mixed         Field value.
	 */
	public function save_department_resources( $value, $post_id, $field ) {

		// Get values and force types.
		$old_departments = get_field( 'related_departments', $post_id );
		if ( false === $old_departments ) {
			$old_departments = array();
		}

		if ( '' === $value ) {
			$new_departments = array();
		} else {
			$new_departments = $value;
		}

		// Remove any that were dropped.
		$removed = array_diff( $old_departments, $new_departments );
		if ( ! empty( $removed ) ) {
			foreach ( $removed as $term_id ) {
				$existing = get_field( 'related_resources', 'category_' . $term_id );
				if ( is_null( $existing ) ) {
					$existing = array();
				}
				$flipped = array_flip( $existing );
				unset( $flipped[ $post_id ] );
				$existing = array_flip( $flipped );
				update_field( 'related_resources', $existing, 'category_' . $term_id );
			}
		}

		// Add any that were added.
		$added = array_diff( $new_departments, $old_departments );
		if ( ! empty( $added ) ) {
			foreach ( $added as $term_id ) {
				$existing = get_field( 'related_resources', 'category_' . $term_id );
				if ( is_null( $existing ) ) {
					$existing = array();
				}
				$existing = array_merge( $existing, array( $post_id ) );
				update_field( 'related_resources', array_unique( $existing ), 'category_' . $term_id );
			}
		}

		return $value;
	}

	/**
	 * Cache resource metadata from Primo PNX for manually-added Primo resources.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	public function cache_metadata( $post_id ) {

		if ( 'resource' !== get_post_type( $post_id ) ) {
			return;
		}

		$resource_url = get_field( 'url', $post_id );

		$domain = '';
		if ( false !== strpos( $resource_url, 'stolaf-primo' ) ) {
			$domain = 'stolaf.edu';
		} elseif ( false !== strpos( $resource_url, 'carleton-primo' ) ) {
			$domain = 'carleton.edu';
		}

		if ( ! empty( $domain ) ) {
			$url = wp_parse_url( $resource_url, PHP_URL_QUERY );
			parse_str( $url, $params );

			if ( isset( $params['docid'] ) ) {
				$primo_api = Bridge_Library_API_Primo::get_instance();
				$query     = array(
					'vid'          => $primo_api->get_vid_by_domain( $domain ),
					'search_scope' => 'Everything',
				);

				$results = $primo_api->request( 'pnxs/PC/' . $params['docid'], $query );

				$this->cache_primo_image( $results, $post_id );
			}
		}
	}

	/**
	 * Extract and cache Primo thumbnail info from PNX array.
	 *
	 * @since 1.0.0
	 *
	 * @param array $primo_resource  Primo resource.
	 * @param int   $post_id WP post ID.
	 *
	 * @return bool          Whether value existed and was updated.
	 */
	public function cache_primo_image( $primo_resource, $post_id ) {
		// Find the thumbnail.
		$thumbnail = '';
		$links     = $primo_resource['delivery']['link'];

		foreach ( $links as $link ) {
			if ( 'thumbnail' === $link['displayLabel'] && ! empty( $link['linkURL'] ) ) {
				$thumbnail = $link['linkURL'];
				break;
			}
		}

		if ( 'no_cover' === $thumbnail ) {
			return false;
		}

		if ( ! empty( $thumbnail ) ) {
			if ( false !== strpos( $thumbnail, 'updateGBSCover' ) ) {
				// Google Books cover API.
				return update_field( 'primo_image_info', $thumbnail, $post_id );
			} else {
				// Image URL.
				return update_field( 'primo_image_url', $thumbnail, $post_id );
			}
		}

		return false;
	}

	/**
	 * Update reading list resources from a given course.
	 *
	 * @since 1.0.0
	 *
	 * @param array $citation Citation metadata.
	 * @param int   $post_id  WP post ID for related course.
	 *
	 * @return int            Resource post ID.
	 */
	public function update_reading_list( $citation, $post_id ) {
		$resource_id = $this->get_post_id_by_alma_id( $citation['id'] );

		$title = $citation['metadata']['title'];
		if ( is_null( $title ) && ! empty( $citation['metadata']['journal_title'] ) ) {
			$title = $citation['metadata']['journal_title'];
		}

		$post_data = array(
			'post_type'   => 'resource',
			'post_status' => 'publish',
			'post_title'  => $title,
		);

		if ( ! empty( $resource_id ) ) {
			$post_data['ID'] = (int) $resource_id;
		}

		$resource_id = wp_insert_post( $post_data );

		// Alma ID.
		update_field( 'alma_id', $citation['id'], $resource_id );

		// Resource metadata.
		foreach ( $this->citation_mapping as $alma_key => $acf_key ) {
			update_field( $acf_key, $citation['metadata'][ $alma_key ], $resource_id );
		}

		// Resource format.
		$format = get_term_by( 'slug', $citation['type']['value'], 'resource_format', 'ARRAY_A' );
		if ( ! $format ) {
			$format = wp_insert_term( $citation['type']['desc'], 'resource_format', array( 'slug' => $citation['type']['value'] ) );
		}
		update_field( 'resource_format', $format['term_id'], $resource_id );

		// Resource Type taxonomy.
		wp_set_object_terms( $resource_id, 'reading-list', 'resource_type', true );

		// Institution.
		$institution = wp_get_post_terms( $post_id, 'institution', array( 'fields' => 'ids' ) );
		wp_set_object_terms( $resource_id, $institution, 'institution' );

		// Manually generate URL.
		if ( '8' == $institution[0] ) {
			$institution_primo = 'stolaf.edu';
		} else {
			$institution_primo = 'carleton.edu';
		}
		$mms_id_primo = $citation['metadata']['mms_id'];
		$primo_api    = Bridge_Library_API_Primo::get_instance();
		$url          = $primo_api->generate_full_view_url( $mms_id_primo, $institution_primo );
		update_field( 'url', $url, $resource_id );

		// Related courses for the resource.
		$related_courses = get_field( 'related_courses_resources', $resource_id );
		if ( is_array( $related_courses ) ) {
			$related_courses = array_merge( $related_courses, array( $post_id ) );
		} else {
			$related_courses = array( $post_id );
		}
		update_field( 'related_courses_resources', $related_courses, $resource_id );

		return $resource_id;
	}

	/**
	 * Add buttons to tag term pages.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term $tag      Term object.
	 * @param string  $taxonomy Taxonomy name.
	 *
	 * @return void
	 */
	public function add_tax_term_actions( $tag, $taxonomy ) {
		wp_enqueue_script( 'bridge-library-admin' );
		?>
		<h2>User Actions</h2>
		<table class="form-table">
			<tbody>

				<tr id="cache-user-primo-resources">
					<th>Refresh Primo Resources for This Department</th>
					<td>
						<a class="button button-primary bridge-library-admin-ajax" target = "_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=cache_primo_resources&data[term_id]=' . $tag->term_id ), 'cache_primo_resources' ) ); ?>">Refresh Primo Resources</a>
						<p class="messages"></p>
					</td>
				</tr>

			</tbody>
		</table>
		<?php
	}

	/**
	 * Ajax handler for caching related Primo resources.
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function ajax_cache_primo_resources() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'cache_primo_resources' ) || ! isset( $_REQUEST['data']['term_id'] ) ) {
			wp_send_json_error( 'You’re not allowed to do that.', 401 );
			wp_die();
		}

		$term_id = sanitize_key( $_REQUEST['data']['term_id'] );
		$term    = get_term_by( 'id', $term_id, 'academic_department' );

		$primo_api          = Bridge_Library_API_Primo::get_instance();
		$stolaf_databases   = $primo_api->get_databases_for_term( $term->name, 'stolaf.edu' );
		$carleton_databases = $primo_api->get_databases_for_term( $term->name, 'carleton.edu' );
		$database_ids       = array();

		foreach ( $stolaf_databases as $database ) {
			$database_ids[] = $this->create_resource_from_database( $database, $term_id, 'stolaf.edu' );
		}

		foreach ( $carleton_databases as $database ) {
			$database_ids[] = $this->create_resource_from_database( $database, $term_id, 'carleton.edu' );
		}

		wp_send_json_success( $database_ids );
	}

	/**
	 * Create a resource CPT from a generic Primo object.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $resource    Primo data object.
	 * @param array  $tax_input   tax_input arguments, accepting taxonomy and slug.
	 * @param string $domain_name Institution domain name.
	 *
	 * @return int                WP post ID.
	 */
	public function create_resource_from_primo( $resource, $tax_input = array(), $domain_name = '' ) {
		$post_args = array(
			'post_type'   => 'resource',
			'post_status' => 'publish',
			'post_title'  => $resource['pnx']['display']['title'][0],
		);

		if ( ! empty( $resource['pnx']['display']['lds10'][0] ) ) {
			$post_args['post_content'] = $resource['pnx']['display']['lds10'][0];
		}

		if ( ! empty( $tax_input ) ) {
			foreach ( $tax_input as $taxonomy => $term_ids ) {
				$post_args['tax_input'][ $taxonomy ] = $term_ids;
			}
		}

		// Get Primo ID.
		if ( array_key_exists( 'recordId', $resource ) ) {
			$primo_id = $resource['recordId'];
		} else {
			$primo_id = $resource['pnx']['control']['recordid'][0];
		}

		$resource_id = $this->get_post_id_by_primo_id( $primo_id );
		if ( ! empty( $resource_id ) ) {
			$post_args['ID'] = $resource_id;
		}

		$post_id = wp_insert_post( $post_args );

		// Set metadata fields including Primo ID.
		foreach ( $this->primo_mapping as $primo_key => $acf_key ) {
			if ( array_key_exists( $primo_key, $resource ) ) {
				update_field( $acf_key, $resource[ $primo_key ], $post_id );
			}
		}

		// Set PNX metadata fields.
		foreach ( $this->primo_pnx_mapping as $primo_key => $acf_key ) {
			if ( array_key_exists( $primo_key, $resource ) ) {
				update_field( $acf_key, $resource[ $primo_key ], $post_id );
			}
		}

		// Manually generate URL.
		$primo_api = Bridge_Library_API_Primo::get_instance();
		$url       = $primo_api->generate_full_view_url( $primo_id, $domain_name );
		update_field( 'url', $url, $post_id );

		// Save thumbnail.
		$this->cache_primo_image( $resource, $post_id );

		return $post_id;
	}

	/**
	 * Create resource CPT item from a research database.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $database       Research database object.
	 * @param int    $departent_id   Academic discipline term ID.
	 * @param string $domain_name    Institution domain name.
	 *
	 * @return int                   WP post ID.
	 */
	public function create_resource_from_database( $database, $departent_id, $domain_name ) {
		$resource_term = get_term_by( 'slug', 'research-database', 'resource_type' );

		if ( 'stolaf.edu' === $domain_name ) {
			$institution_term = get_term_by( 'slug', 'st-olaf', 'institution' );
		} elseif ( 'carleton.edu' === $domain_name ) {
			$institution_term = get_term_by( 'slug', 'carleton', 'institution' );
		}

		$tax_input = array(
			'institution'         => array( $institution_term->term_id ),
			'academic_department' => array( $departent_id ),
			'resource_type'       => array( $resource_term->term_id ),
		);

		$post_id = $this->create_resource_from_primo( $database, $tax_input, $domain_name );

		return $post_id;
	}

	/**
	 * Create or update a resource CPT item from a LibGuides asset.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $asset       Asset data.
	 * @param string $institution Institution.
	 *
	 * @return int                WP post ID.
	 */
	public function create_resource_from_libguides_asset( $asset, $institution ) {

		$data = array(
			'post_type'     => 'resource',
			'post_status'   => 'publish',
			'post_title'    => $asset['name'],
			'post_content'  => $asset['description'],
			'post_date'     => $asset['created'],
			'post_modified' => $asset['updated'],
		);

		$post_id = $this->get_post_id_by_libguides_id( $asset['id'] );
		if ( ! empty( $post_id ) ) {
			$data['ID'] = $post_id;
		}

		$asset_id = wp_insert_post( $data );

		// Metadata.
		foreach ( $this->libguides_asset_mapping as $libguides_key => $acf_key ) {
			if ( 'url' === $libguides_key ) {
				$asset = $this->ensure_http_prefix( $asset, $libguides_key );
			}
			update_field( $acf_key, $asset[ $libguides_key ], $asset_id );
		}

		// Taxonomies.
		$libguides_term = get_term_by( 'name', 'LibGuides', 'resource_type' );

		$terms = array( $libguides_term->term_id );
		if ( array_key_exists( 'az_types', $asset ) ) {
			foreach ( $asset['az_types'] as $type ) {
				$term = term_exists( $type['name'], 'resource_type' );
				if ( ! $term ) {
					$term = wp_insert_term( $type['name'], 'resource_type', array( 'parent' => $libguides_term->term_id ) );
				}
				$terms[] = (int) $term['term_id'];
			}
		}
		wp_set_object_terms( $asset_id, $terms, 'resource_type', true );

		wp_set_object_terms( $asset_id, $institution, 'institution', true );

		return $asset_id;
	}

	/**
	 * Create or update a resource CPT item from a LibGuides guide.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $guide       Guide data.
	 * @param string $institution Institution.
	 *
	 * @return int                WP post ID.
	 */
	public function create_resource_from_libguides_guide( $guide, $institution ) {

		$data = array(
			'post_type'     => 'resource',
			'post_status'   => 'publish',
			'post_title'    => $guide['name'],
			'post_content'  => $guide['description'],
			'post_date'     => $guide['created'],
			'post_modified' => $guide['updated'],
		);

		$post_id = $this->get_post_id_by_libguides_id( $guide['id'] );
		if ( ! empty( $post_id ) ) {
			$data['ID'] = $post_id;
		}

		$guide_id = wp_insert_post( $data );

		// Metadata.
		foreach ( $this->libguides_guide_mapping as $libguides_key => $acf_key ) {
			if ( 'friendly_url' === $libguides_key ) {
				$guide = $this->ensure_http_prefix( $guide, $libguides_key );
			}
			update_field( $acf_key, $guide[ $libguides_key ], $guide_id );
		}

		// Taxonomies.
		$libguides_term = get_term_by( 'name', 'LibGuides', 'resource_type' );

		$guide_type = term_exists( $guide['type_label'], 'resource_type' );
		if ( ! $guide_type ) {
			$guide_type = wp_insert_term( $guide['type_label'], 'resource_type', array( 'parent' => $libguides_term->term_id ) );
		}
		$terms = array(
			$libguides_term->term_id,
			(int) $guide_type['term_id'],
		);

		wp_set_object_terms( $guide_id, $terms, 'resource_type', true );

		wp_set_object_terms( $guide_id, $institution, 'institution', true );

		return $guide_id;
	}

	/**
	 * Add text and links to top-level settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void Displays HTML.
	 */
	public function admin_refresh_resources_text() {
		?>
		<h2>Resources</h2>
		<p>Resources are cached in WordPress to cut down on the number of API queries and are refreshed as needed.</p>

		<form class="bridge-library-admin-ajax">
			<table class="form-table">
				<tr>
					<th>Update All LibGuides Assets</th>
					<td>
						<p>Use this button to start a full background update of all LibGuides assets.</p>

						<p class="messages"></p>

						<input type="hidden" name="action" value="start_bg_libguides_assets_update" />
						<?php wp_nonce_field( 'start_bg_libguides_assets_update' ); ?>

						<p>Run:
							<label><input type="radio" value="async" class="wait-for" name="async" checked="checked" />asynchronously</label>
							<label><input type="radio" value="sync" class="wait-for" name="async" />synchronously</label>
						</p>

						<p><input type="submit" class="button button-primary" value="Update Assets" /></p>
					</td>
				</tr>
			</table>
		</form>

		<form class="bridge-library-admin-ajax">
			<table class="form-table">
				<tr>
					<th>Update A Specific LibGuides Asset</th>
					<td>
						<p>Use this utility to manually update just one resource by using the LibGuides ID.</p>

						<input type="hidden" name="action" value="update_libguides_resource_by_id" />
						<?php wp_nonce_field( 'update_libguides_resource_by_id' ); ?>

						<p class="messages"></p>

						<p><label for="libguides_asset_id">LibGuides Asset ID: <input type="text" name="libguides_asset_id" placeholder="14174" /></label></p>
						<p><input type="submit" class="button button-primary" value="Update an Asset" /></p>
					</td>
				</tr>
			</table>
		</form>

		<form class="bridge-library-admin-ajax">
			<table class="form-table">
				<tr>
					<th>Update All LibGuides Guides</th>
					<td>
						<p>Use this button to start a full background update of all LibGuides guides.</p>

						<p class="messages"></p>

						<input type="hidden" name="action" value="start_bg_libguides_guides_update" />
						<?php wp_nonce_field( 'start_bg_libguides_guides_update' ); ?>

						<p>Run:
							<label><input type="radio" value="async" class="wait-for" name="async" checked="checked" />asynchronously</label>
							<label><input type="radio" value="sync" class="wait-for" name="async" />synchronously</label>
						</p>

						<p><input type="submit" class="button button-primary" value="Update Guides" /></p>
					</td>
				</tr>
			</table>
		</form>

		<form class="bridge-library-admin-ajax">
			<table class="form-table">
				<tr>
					<th>Update A Specific LibGuides Guide</th>
					<td>
						<p>Use this utility to manually update just one resource by using the LibGuides ID.</p>

						<input type="hidden" name="action" value="update_libguides_resource_by_id" />
						<?php wp_nonce_field( 'update_libguides_resource_by_id' ); ?>

						<p class="messages"></p>

						<p><label for="libguides_guide_id">LibGuides Guide ID: <input type="text" name="libguides_guide_id" placeholder="14174" /></label></p>
						<p><input type="submit" class="button button-primary" value="Update a Guide" /></p>
					</td>
				</tr>
			</table>
		</form>

		<hr />

		<?php
	}

	/**
	 * Update just one LibGuides asset by ID.
	 *
	 * @since 1.0.0
	 *
	 * @return void Sends WP JSON response.
	 */
	public function ajax_update_libguides_resource_by_id() {

		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['action'] ) && ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
			wp_send_json_error( 'Access denied.', 401 );
			wp_die();
		}

		$libguides_api_12 = Bridge_Library_API_LibGuides_12::get_instance();

		$libguides_api_12->set_institution( 'stolaf' );
		$stolaf_results = $libguides_api_12->get_assets();

		if ( is_wp_error( $stolaf_results ) ) {
			wp_send_json_error( $stolaf_results );
		}

		$libguides_api_12->set_institution( 'carleton' );
		$carleton_results = $libguides_api_12->get_assets();

		if ( is_wp_error( $carleton_results ) ) {
			wp_send_json_error( $carleton_results );
		}

		if ( array_key_exists( 'libguides_asset_id', $_REQUEST ) && ! empty( $_REQUEST['libguides_asset_id'] ) ) {
			$asset_id = sanitize_key( $_REQUEST['libguides_asset_id'] );
		} else {
			$asset_id = null;
		}

		foreach ( $stolaf_results as $result ) {
			if ( $asset_id === $result['id'] ) {
				$updated = $this->create_resource_from_libguides_asset( $result, 'st-olaf' );
			}
		}

		foreach ( $carleton_results as $result ) {
			if ( $asset_id === $result['id'] ) {
				$updated = $this->create_resource_from_libguides_asset( $result, 'carleton' );
			}
		}

		wp_send_json_success( 'Updated resource with ID ' . $updated . '.' );
	}

	/**
	 * Start a full background update of LibGuides assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void Sends WP JSON response.
	 */
	public function ajax_start_bg_libguides_assets_update() {
		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['action'] ) && ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
			wp_send_json_error( 'Access denied.', 401 );
			wp_die();
		}

		if ( array_key_exists( 'async', $_REQUEST ) && isset( $_REQUEST['async'] ) && 'async' !== $_REQUEST['async'] ) {
			$async = false;
		} else {
			$async = true;
		}

		$results = $this->background_create_resource_from_libguides_assets( $async );

		if ( $async ) {
			wp_send_json_success( 'Started background update.', 201 );
		} else {
			wp_send_json_success( 'Finished update for ' . count( $results ) . ' resources.', 200 );
		}
	}

	/**
	 * Cache LibGuides assets to WP data.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $async Whether to run async or not.
	 *
	 * @return void|array Kicks off background update process; if sync, returns array of CPT IDs.
	 */
	public function background_create_resource_from_libguides_assets( $async ) {

		// Get the data.
		$libguides_api_12 = Bridge_Library_API_LibGuides_12::get_instance();

		$libguides_api_12->set_institution( 'stolaf' );
		$stolaf_results = $libguides_api_12->get_assets();

		$libguides_api_12->set_institution( 'carleton' );
		$carleton_results = $libguides_api_12->get_assets();

		// LibGuides doesn’t have a limit parameter, so we have to get everything in one call.
		// If async, we’ll break it up into chunks and schedule them to be processed.
		if ( $async ) {
			$chunks = array_chunk( $stolaf_results, 100 );
			foreach ( $chunks as $index => $chunk ) {
				$libguides_api_12->async->push_to_queue(
					array(
						'assets'      => $chunk,
						'institution' => 'st-olaf',
					)
				);
			}

			$chunks = array_chunk( $carleton_results, 100 );
			foreach ( $chunks as $index => $chunk ) {
				$libguides_api_12->async->push_to_queue(
					array(
						'assets'      => $chunk,
						'institution' => 'carleton',
					)
				);
			}

			// Now start the queue.
			return $libguides_api_12->async->save()->dispatch();
		} else {
			$assets = array();
			foreach ( $stolaf_results as $asset ) {
				$assets[] = $this->create_resource_from_libguides_asset( $asset, 'st-olaf' );
			}
			foreach ( $carleton_results as $asset ) {
				$assets[] = $this->create_resource_from_libguides_asset( $asset, 'carleton' );
			}

			return $assets;
		}
	}

	/**
	 * Start a full background update of LibGuides assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void Sends WP JSON response.
	 */
	public function ajax_start_bg_libguides_guides_update() {
		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['action'] ) && ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
			wp_send_json_error( 'Access denied.', 401 );
			wp_die();
		}

		if ( array_key_exists( 'async', $_REQUEST ) && isset( $_REQUEST['async'] ) && 'async' !== $_REQUEST['async'] ) {
			$async = false;
		} else {
			$async = true;
		}

		$this->background_create_resource_from_libguides_guides( $async );

		wp_send_json_success( 'Started background update.', 201 );
	}

	/**
	 * Import specific LibGuides guides to a specific course.
	 *
	 * @since 1.0.0
	 *
	 * @return void Sends WP JSON response.
	 */
	public function ajax_import_libguides_to_course() {
		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['action'] ) && ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
			wp_send_json_error( 'Access denied.', 401 );
			wp_die();
		}

		if ( ! isset( $_REQUEST['post_id'] ) ) {
			wp_send_json_error( 'Missing resource ID.', 400 );
			wp_die();
		}

		if ( ! isset( $_REQUEST['libguides_guide_id'] ) ) {
			wp_send_json_error( 'Missing LibGuides Guide ID.', 400 );
			wp_die();
		}

		$post_id = absint( $_REQUEST['post_id'] );

		$libguides = $this->background_create_resource_from_libguides_guides( false );

		$core_resources = get_field( 'core_resources', $post_id );
		if ( empty( $core_resources ) ) {
			$core_resources = array();
		}
		update_field( 'core_resources', array_merge( $core_resources, $libguides ), $post_id );

		wp_send_json_success( 'Finished update for ' . count( $libguides ) . ' resources. See the <a href="' . get_edit_post_link( $post_id ) . '">Core Resources field</a>.', 200 );
	}

	/**
	 * Cache LibGuides guides to WP data.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $async Whether to run async or not.
	 *
	 * @return mixed Void for async, array of post IDs for sync.
	 */
	public function background_create_resource_from_libguides_guides( $async ) {

		// Get the data.
		$libguides_api_11 = Bridge_Library_API_LibGuides_11::get_instance();

		$libguides_api_11->set_institution( 'stolaf' );
		$stolaf_results = $libguides_api_11->get_guides();

		$libguides_api_11->set_institution( 'carleton' );
		$carleton_results = $libguides_api_11->get_guides();

		// LibGuides doesn’t have a limit parameter, so we have to get everything in one call.
		// If async, we’ll break it up into chunks and schedule them to be processed.
		if ( $async ) {
			$chunks = array_chunk( $stolaf_results, 100 );
			foreach ( $chunks as $index => $chunk ) {
				$libguides_api_11->async->push_to_queue(
					array(
						'guides'      => $chunk,
						'institution' => 'st-olaf',
					)
				);
			}

			$chunks = array_chunk( $carleton_results, 100 );
			foreach ( $chunks as $index => $chunk ) {
				$libguides_api_11->async->push_to_queue(
					array(
						'guides'      => $chunk,
						'institution' => 'carleton',
					)
				);
			}

			// Now start the queue.
			return $libguides_api_11->async->save()->dispatch();
		} else {
			$results = array();
			foreach ( $stolaf_results as $guide ) {
				$results[] = $this->create_resource_from_libguides_guide( $guide, 'st-olaf' );
			}
			foreach ( $carleton_results as $guide ) {
				$results[] = $this->create_resource_from_libguides_guide( $guide, 'carleton' );
			}
			return $results;
		}
	}

	/**
	 * Handle misformatted publication years.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value ACF field value.
	 *
	 * @return string      ACF field value.
	 */
	public function force_publication_year_string_load( $value ) {
		if ( is_array( $value ) ) {
			$value = $value[0];
		}

		return $value;
	}

	/**
	 * Handle misformatted publication years.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value ACF field value.
	 *
	 * @return string      ACF field value.
	 */
	public function force_publication_year_string_update( $value ) {
		if ( false !== strpos( $value, '[' ) ) {
			$value = str_replace(
				array( '[', ']' ),
				array( '', '' ),
				$value
			);
		}

		return $value;
	}

	/**
	 * Load domain-customized link to Catalyst.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field object.
	 *
	 * @return array       Field object.
	 */
	public function load_catalyst_field( $field ) {
		$message = json_decode( $field['message'], true );
		$users   = Bridge_Library_Users::get_instance();
		$domain  = $users->get_domain();

		$field['message'] = sprintf( $message['description'], $message['links'][ $domain ] );

		return $field;
	}

	/**
	 * Load links to frontend courses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field object.
	 *
	 * @return array       Field object.
	 */
	public function load_course_links_field( $field ) {
		$courses      = get_field( 'related_courses_resources', get_the_ID() );
		$course_links = array();

		if ( empty( $courses ) ) {
			$field['message'] = '<p>After selecting some related courses, check this area for links to see them on the frontend.';
		} else {
			foreach ( $courses as $course ) {
				$course_links[] = '<li><a target="_blank" href="' . get_permalink( $course ) . '">' . get_the_title( $course ) . '</a></li>';
			}
			$field['message'] = '<p>View related courses:</p><ul>' . implode( $course_links ) . '</ul>';
		}

		return $field;
	}

	/**
	 * Disable Primo Image URLs, display images if available.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field object.
	 *
	 * @return array       Field object.
	 */
	public function disable_primo_image_url_field( $field ) {
		$field['disabled'] = true;

		if ( ! empty( $field['value'] ) ) {
			$field['instructions'] .= '<br/><img src="' . $field['value'] . '" />';
		}

		return $field;
	}

	/**
	 * Prepend URLs with http prefix.
	 *
	 * @since 1.0.5
	 *
	 * @param array  $asset LibGuides asset/guide.
	 * @param string $key   Array key to check.
	 *
	 * @return array        LibGuides asset/guide.
	 */
	private function ensure_http_prefix( $asset, $key ) {
		if ( false === strpos( $asset[ $key ], 'http' ) && 0 === strpos( $asset[ $key ], '//' ) ) {
			$asset[ $key ] = 'https:' . $asset[ $key ];
		}

		return $asset;
	}

	/**
	 * Add nonce and button to message field.
	 *
	 * @param array $field ACF field object.
	 *
	 * @return array
	 */
	public function add_libguides_import_button( $field ) {
		$url_parts = array(
			'page'      => 'bridge_library_import_libguides',
			'nonce'     => wp_create_nonce( 'import_libguides' ),
			'course_id' => get_the_ID(),
		);

		$field['message'] .= '<p><a href="' . esc_url( admin_url( 'admin.php?' . http_build_query( $url_parts ) ) ) . '" target="_blank" class="button button-primary">Begin</a></p>';

		return $field;
	}
}
