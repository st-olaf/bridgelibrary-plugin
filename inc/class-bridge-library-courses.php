<?php
/**
 * Bridge Library courses class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library courses class.
 *
 * @since 1.0.0
 */
class Bridge_Library_Courses extends Bridge_Library {

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
	private $acf_meta_table = 'bridge_library_course_meta';

	/**
	 * Academic term slug mapping.
	 *
	 * @since 1.0.0
	 *
	 * @var array $academic_term_slugs
	 */
	private $academic_term_slugs = array(
		'WI' => 'Winter',
		'SP' => 'Spring',
		'FA' => 'Fall',
		'IN' => 'Interim',
		'S1' => 'Summer 1',
		'S2' => 'Summer 2',
	);

	/**
	 * Course API to CPT field mapping.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $course_field_mapping = array(

		// From Alma.
		'id'                       => 'alma_id',
		'section'                  => 'course_section',
		'start_date'               => 'start_date',
		'end_date'                 => 'end_date',
		'code'                     => 'course_code',

		// Added in extract_course_data() function.
		'course_number'            => 'course_number',
		'institution'              => 'institution',
		'academic_department'      => 'academic_department',
		'academic_department_code' => 'academic_department_code',
		'degree_level'             => 'degree_level',
		'course_term'              => 'course_term',
	);

	/**
	 * Institution course code mapping.
	 *
	 * @since 1.0.0
	 *
	 * @var array $institutions_course_code_mapping
	 */
	private $institutions_course_code_mapping = array(
		'C' => 'Carleton',
		'S' => 'St. Olaf',
	);

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_Courses class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_Courses();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Add text to menu page.
		add_action( 'bridge_library_admin_settings', array( $this, 'admin_refresh_courses_text' ) );

		// Handle course updates from dashboard.
		add_action( 'wp_ajax_update_courses', array( $this, 'ajax_update_courses' ) );
		add_action( 'wp_ajax_update_course_by_id', array( $this, 'ajax_update_course_by_id' ) );
		add_action( 'wp_ajax_start_background_update', array( $this, 'ajax_start_background_update' ) );

		// Schedule automatic updates.
		add_action( 'bridge_library_schedule_daily', array( $this, 'background_update_courses' ) );

		// Add category-level resources to post related resources.
		add_filter( 'acf/load_value/key=field_5cc326f90696b', array( $this, 'include_academic_department_resources' ), 10, 3 );

		// Add institution and course number data to admin list view.
		add_action( 'manage_posts_extra_tablenav', array( $this, 'include_course_data_in_title' ) );

		// Include course code and number in searches.
		add_action( 'posts_join', array( $this, 'search_acf_fields_join' ), 10, 2 );
		add_action( 'posts_where', array( $this, 'search_acf_fields_where' ), 10, 2 );
	}

	/**
	 * Add text and links to top-level settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_refresh_courses_text() {
		?>
		<h2>Courses</h2>
		<p>Courses are cached in WordPress to cut down on the number of API queries and are automatically refreshed nightly.</p>

		<form class="bridge-library-admin-ajax">
			<table class="form-table">
				<tr>
					<th>Update Some Courses</th>
					<td>
						<p>Use this utility to manually refresh the courses.</p>

						<input type="hidden" name="action" value="update_courses" />
						<?php wp_nonce_field( 'update_courses' ); ?>

						<p class="messages"></p>

						<p><label for="query[offset]">Offset: <input type="text" name="query[offset]" value="0" /></label></p>
						<p><label for="query[limit]">Limit (max 100): <input type="text" name="query[limit]" value="50" /></label></p>
						<p><input type="submit" class="button button-primary" value="Update Courses" /></p>
					</td>
				</tr>
			</table>
		</form>

		<form class="bridge-library-admin-ajax">
			<table class="form-table">
				<tr>
					<th>Update All Courses</th>
					<td>
						<p>Use this button to start a full background update of all courses.</p>

						<p class="messages"></p>

						<input type="hidden" name="action" value="start_background_update" />
						<?php wp_nonce_field( 'start_background_update' ); ?>

						<p><input type="submit" class="button button-primary" value="Start Background Update" /></p>
					</td>
				</tr>
			</table>
		</form>

		<form class="bridge-library-admin-ajax">
			<table class="form-table">
				<tr>
					<th>Update A Specific Course</th>
					<td>
						<p>Use this utility to manually update just one course giving an Alma ID or course code.</p>

						<input type="hidden" name="action" value="update_course_by_id" />
						<?php wp_nonce_field( 'update_course_by_id' ); ?>

						<p class="messages"></p>

						<p><label for="course_id">Alma Course ID: <input type="text" name="course_id" placeholder="6634258760002971" /></label></p>
						<p><label for="course_code">Course Code: <input type="text" name="course_code" placeholder="C|ARBC|206|00|19/SP" /></label></p>
						<p><input type="submit" class="button button-primary" value="Update a Course" /></p>
					</td>
				</tr>
			</table>
		</form>

		<hr/>

		<?php
	}

	/**
	 * Trigger some course updates via Ajax.
	 *
	 * Built for development/testing purposes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_update_courses() {
		$query = array();

		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['action'] ) && ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
			wp_send_json_error( 'Access denied.', 401 );
			wp_die();
		}

		if ( isset( $_REQUEST['query'] ) ) {
			foreach ( wp_unslash( $_REQUEST['query'] ) as $key => $value ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizing each key below.
				$query[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		$alma_api = Bridge_Library_API_Alma::get_instance();
		$results  = $alma_api->request( 'courses/', $query );

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results );
		}

		$updated = $this->update_courses( $results['course'] );

		wp_send_json_success( 'Updated ' . count( $updated ) . ' ' . _n( 'course', 'courses', count( $updated ), 'bridge-library' ) . '.', 200 );
	}

	/**
	 * Trigger a single course update via Ajax.
	 *
	 * Built for development/testing purposes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_update_course_by_id() {
		$query = array();

		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['action'] ) && ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
			wp_send_json_error( 'Access denied.', 401 );
			wp_die();
		}

		if ( isset( $_REQUEST['query'] ) ) {
			foreach ( wp_unslash( $_REQUEST['query'] ) as $key => $value ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitizing each key below.
				$query[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		if ( ! empty( $_REQUEST['course_code'] ) ) {
			$course_code = sanitize_text_field( wp_unslash( $_REQUEST['course_code'] ) );
			$alma_api    = Bridge_Library_API_Alma::get_instance();
			$query['q']  = 'code~' . $course_code;
			$results     = $alma_api->request( 'courses', $query );

			if ( empty( $results['course'] ) ) {
				wp_send_json_error( 'No such course found.', 404 );
				wp_die();
			}

			$results = $results['course'];
		} elseif ( ! empty( $_REQUEST['course_id'] ) ) {
			$course_id = sanitize_key( $_REQUEST['course_id'] );
			$alma_api  = Bridge_Library_API_Alma::get_instance();
			$results   = array( $alma_api->request( 'courses/' . $course_id, $query ) );
		}

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results );
			wp_die();
		}

		$updated = $this->update_courses( $results );

		wp_send_json_success( 'Updated ' . count( $updated ) . ' ' . _n( 'course', 'courses', count( $updated ), 'bridge-library' ) . '.', 200 );
	}

	/**
	 * Start a full background update.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_start_background_update() {
		if ( isset( $_REQUEST['_wpnonce'] ) && isset( $_REQUEST['action'] ) && ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
			wp_send_json_error( 'Access denied.', 401 );
			wp_die();
		}

		$this->background_update_courses();

		wp_send_json_success( 'Started background update.', 201 );
	}

	/**
	 * Cache courses to WP data.
	 *
	 * @since 1.0.0
	 *
	 * @return void Kicks off background update process.
	 */
	public function background_update_courses() {
		$alma_api = Bridge_Library_API_Alma::get_instance();

		// Get the first batch.
		$results = $alma_api->request( 'courses/' );

		// Iterate as many times as necessary to get all courses.
		if ( array_key_exists( 'total_record_count', $results ) ) {
			$total_count = $results['total_record_count'];
			$per_page    = count( $results['course'] );

			if ( $total_count > $per_page ) {
				$total_pages = ceil( $total_count / $per_page );

				for ( $i = 1; $i < $total_pages; $i++ ) {
					$query['offset'] = $i * $alma_api->limit;
					$alma_api->async->push_to_queue(
						array(
							'path'  => 'courses/',
							'query' => $query,
						)
					);
				}

				$alma_api->async->save()->dispatch();
			}
		}

		// Process the first set.
		$this->update_courses( $results['course'] );
	}

	/**
	 * Get post ID for the given Alma ID.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Fall back to wp_postmeta.
	 *
	 * @param int $alma_id Alma ID.
	 *
	 * @return int|null    WP course ID or null if it doesn’t exist.
	 */
	public function get_post_id_by_alma_id( $alma_id ) {
		global $wpdb;
		$post_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- this is more performant than a get_posts with meta_query would be.
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE alma_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
				$alma_id
			)
		);

		// If ACF custom DB fails, search wp_postmeta.
		if ( is_null( $post_id ) ) {
			$post_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'alma_id' AND meta_value = %s;",
					$alma_id
				)
			);
		}

		return $post_id;
	}

	/**
	 * Get post ID for the given course code.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Fall back to wp_postmeta.
	 *
	 * @param string $course_code Course code.
	 *
	 * @return int|null           WP course ID or null if it doesn’t exist.
	 */
	public function get_post_id_by_course_code( $course_code ) {
		global $wpdb;
		$post_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- this is more performant than a get_posts with meta_query would be.
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE course_code = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
				$course_code
			)
		);

		// If ACF custom DB fails, search wp_postmeta.
		if ( 0 === $post_id ) {
			$post_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'course_code' AND meta_value = %s;",
					$course_code
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
	 * Get array of post IDs for the given course codes.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Fall back to wp_postmeta.
	 *
	 * @param array $course_codes Course code.
	 *
	 * @return array              WP course IDs or empty array.
	 */
	public function get_post_ids_by_course_codes( $course_codes ) {
		global $wpdb;

		// Escape each array member.
		$course_codes = array_map(
			function( $code ) {
				return "'" . esc_sql( $code ) . "'";
			},
			$course_codes
		);

		$sql   = "SELECT post_id FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE course_code IN (" . implode( ',', $course_codes ) . ')'; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
		$posts = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- this is more performant than a get_posts with meta_query would be, and we’ve escaped all the input above.

		// If ACF fails, try wp_postmeta.
		if ( is_null( $posts ) ) {
			$posts = $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = 'course_code' AND meta_value IN (" . implode( ',', $course_codes ) . ');' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- this is more performant than a get_posts with meta_query would be, and we’ve escaped all the input above.
		}

		if ( is_null( $posts ) ) {
			$posts = array();
		}

		return $posts;
	}

	/**
	 * Get array of post data for the given Alma ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $alma_id Alma ID.
	 *
	 * @return array|null  Array of course meta.
	 */
	public function get_post_data_by_alma_id( $alma_id ) {
		global $wpdb;
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- this is more performant than a get_posts with meta_query would be.
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE alma_id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
				$alma_id
			),
			ARRAY_A
		);
		if ( empty( $rows ) ) {
			$rows = array();
		}
		return $rows;
	}

	/**
	 * Get array of post data for the given WP IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param array $post_ids Course code.
	 *
	 * @return array          Array of course meta.
	 */
	public function get_post_data_by_post_ids( $post_ids ) {
		global $wpdb;

		// Escape each array member.
		$post_ids = array_map(
			function( $code ) {
				return "'" . esc_sql( $code ) . "'";
			},
			$post_ids
		);

		$sql  = "SELECT * FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE post_id IN (" . implode( ',', $post_ids ) . ')'; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- this is more performant than a get_posts with meta_query would be, and we’ve escaped all the input above.
		if ( is_null( $rows ) ) {
			$rows = array();
		}
		return $rows;
	}

	/**
	 * Get array of post data for the given course codes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $course_codes Course code.
	 *
	 * @return array              Array of course meta.
	 */
	public function get_post_data_by_course_codes( $course_codes ) {
		global $wpdb;

		// Escape each array member.
		$course_codes = array_map(
			function( $code ) {
				return "'" . esc_sql( $code ) . "'";
			},
			$course_codes
		);

		$sql  = "SELECT * FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE course_code IN (" . implode( ',', $course_codes ) . ')'; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
		$rows = $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- this is more performant than a get_posts with meta_query would be, and we’ve escaped all the input above.
		if ( is_null( $rows ) ) {
			$rows = array();
		}
		return $rows;
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
	 * Import/update course CPT.
	 *
	 * @since 1.0.0
	 *
	 * @param array $courses Array of courses data from Alma.
	 *
	 * @return array         Array of CPT IDs.
	 */
	public function update_courses( $courses ) {
		$updated = array();

		foreach ( $courses as $course ) {

			$post_id = $this->get_post_id_by_course_code( $course['code'] );

			// Course data.
			$course_cpt_data = array(
				'post_type'     => 'course',
				'post_status'   => 'publish',
				'post_title'    => $course['name'],
				'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( $course['created_date'] ) ),
				'post_modified' => gmdate( 'Y-m-d H:i:s', strtotime( $course['last_modified_date'] ) ),
			);

			if ( ! empty( $post_id ) ) {
				$course_cpt_data['ID'] = $post_id;
			}

			$post_id = wp_insert_post( $course_cpt_data );

			// Determine taxonomy terms.
			$course = $this->extract_course_data( $course, $post_id );

			// All meta data.
			foreach ( $this->course_field_mapping as $alma_key => $acf_key ) {

				// Ensure the array key exists.
				if ( ! array_key_exists( $alma_key, $course ) ) {
					continue;
				}

				// Format dates.
				if ( in_array( $acf_key, array( 'start_date', 'end_date' ), true ) ) {
					$course[ $alma_key ] = gmdate( 'Ymd', strtotime( $course[ $alma_key ] ) );
				}

				update_field( $acf_key, $course[ $alma_key ], $post_id );
			}

			$updated[] = $post_id;
		}

		return $updated;
	}

	/**
	 * Decode course codes.
	 *
	 * @since 1.0.0
	 *
	 * @param string $course_code Course code string. Example: C|LCST|291|11|19/WI.
	 *
	 * @return array|string       Array of course code data or string.
	 */
	public function decode_course_code( $course_code ) {

		if ( false !== strpos( $course_code, '|' ) ) {
			$parts = explode( '|', $course_code );

			$course_code = array(
				'institution'    => str_replace( array_flip( $this->institutions_course_code_mapping ), $this->institutions_course_code_mapping, $parts[0] ),
				'course_number'  => $parts[2],
				'course_section' => $parts[3],
				'course_term'    => $parts[4],
			);
		}

		return $course_code;
	}

	/**
	 * Fill $course with taxonomy metadata.
	 *
	 * @since 1.0.0
	 *
	 * @param array $course  Course data from Alma.
	 * @param int   $post_id CPT post ID.
	 *
	 * @return array         Modified course data from Alma.
	 */
	private function extract_course_data( $course, $post_id ) {

		$course_code = $this->decode_course_code( $course['code'] );

		if ( is_array( $course_code ) ) {

			$institution_term = $this->get_or_create_term( $course_code['institution'], 'institution' );
			wp_set_object_terms( $post_id, $institution_term, 'institution' );

			$course['institution']    = array( $institution_term );
			$course['course_number']  = $course_code['course_number'];
			$course['course_section'] = $course_code['course_section'];
			$course['course_term']    = array( $this->get_course_term( explode( '/', $course_code['course_term'] ) ) );
		}

		if ( isset( $course['academic_department']['desc'] ) ) {
			$academic_department_term = $this->get_or_create_term( $course['academic_department']['desc'], 'academic_department' );
			wp_set_object_terms( $post_id, $academic_department_term, 'academic_department' );
			$course['academic_department_code'] = $course['academic_department']['value'];
			$course['academic_department']      = array( $academic_department_term );
		}

		$alma_api    = Bridge_Library_API_Alma::get_instance();
		$full_course = $alma_api->request( 'courses/' . $course['id'], array( 'view' => 'full' ) );

		if ( ! is_wp_error( $full_course ) && ! empty( $full_course['reading_lists'] ) ) {
			$resources = Bridge_Library_Resources::get_instance();
			if ( array_key_exists( 'reading_lists', $full_course ) && array_key_exists( 'reading_list', $full_course['reading_lists'] ) && count( $full_course['reading_lists']['reading_list'] ) > 0 ) {
				foreach ( $full_course['reading_lists']['reading_list'] as $reading_list ) {
					$citations = $reading_list['citations']['citation'];

					$active_resources   = array();
					$existing_resources = get_field( 'related_courses_resources', $post_id );
					if ( ! is_array( $existing_resources ) ) {
						$existing_resources = array();
					}

					// Add/update resources.
					foreach ( $citations as $citation ) {
						$resource_id = $resources->update_reading_list( $citation, $post_id );

						$active_resources[] = (int) $resource_id;
						unset( $existing_resources[ array_search( (int) $resource_id, $existing_resources, true ) ] );
					}

					// Delete missing resources.
					foreach ( array_flip( $existing_resources ) as $trash_id ) {
						wp_delete_post( $trash_id );
					}

					// Update the course post.
					update_field( 'related_courses_resources', $active_resources, $post_id );

				}
			}
		}

		return $course;
	}

	/**
	 * Create or retrieve a term ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $term     Term name.
	 * @param string $taxonomy Taxonomy.
	 *
	 * @return int             Term ID.
	 */
	private function get_or_create_term( $term, $taxonomy ) {
		require_once ABSPATH . '/wp-admin/includes/taxonomy.php';
		$term = wp_create_term( $term, $taxonomy );
		return (int) $term['term_id'];
	}

	/**
	 * Retrieve a term ID from the academic term.
	 *
	 * @since 1.0.0
	 *
	 * @param array $term_parts Academic term and year.
	 *
	 * @return array            Academic term and year with term IDs.
	 */
	private function get_course_term( $term_parts ) {

		$term_slug = '20' . $term_parts[0] . '-' . str_replace( array_flip( $this->academic_term_slugs ), $this->academic_term_slugs, $term_parts[1] );

		$term = wp_create_term( $term_slug, 'course_term' );
		return $term['term_id'];
	}

	/**
	 * Include department-related resources for courses.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value   ACF field value.
	 * @param int   $post_id Post ID.
	 * @param array $field   ACF field object.
	 *
	 * @return mixed         ACF field value.
	 */
	public function include_academic_department_resources( $value, $post_id, $field ) {

		// Include term results for GraphQL results. We don’t want to deal with tracking and unsetting changes to all posts when a resource->academic department relationship is updated.
		if ( isset( $_SERVER['REQUEST_URI'] ) && '/graphql' === $_SERVER['REQUEST_URI'] ) {
			if ( is_null( $value ) ) {
				$value = array();
			}

			$academic_departments = wp_get_post_terms( $post_id, 'academic_department', array( 'fields' => 'ids' ) );
			foreach ( $academic_departments as $term_id ) {
				$department_resources = get_field( 'related_resources', 'category_' . $term_id );
				if ( is_array( $department_resources ) && ! empty( $department_resources ) ) {
					$value = array_merge( $department_resources, $value );
				}
				// De-dupe it.
				$value = array_unique( $value );
			}
		}

		return $value;
	}

	/**
	 * Add course data to admin title.
	 *
	 * @since 1.0.0
	 *
	 * @return void Registers filter.
	 */
	public function include_course_data_in_title() {
		if ( 'edit-course' === get_current_screen()->id ) {
			$resources = Bridge_Library_Resources::get_instance();
			add_filter( 'the_title', array( $resources, 'modify_course_acf_titles' ), 10, 2 );
		}
	}

	/**
	 * Include custom DB table in search query JOIN clause.
	 *
	 * @since 1.0.2
	 *
	 * @param string   $join  JOIN query clause.
	 * @param WP_Query $query Query object, passed by reference.
	 *
	 * @return string         JOIN query clause.
	 */
	public function search_acf_fields_join( $join, $query ) {
		if ( $query->is_main_query() && 'course' === $query->get( 'post_type' ) ) {
			global $wpdb;
			$join .= " JOIN {$wpdb->prefix}{$this->acf_meta_table} acfcourse ON acfcourse.post_id = {$wpdb->posts}.ID";
		}

		return $join;
	}

	/**
	 * Include custom DB fields in search query WHERE clause.
	 *
	 * @since 1.0.2
	 *
	 * @param string   $where WHERE query clause.
	 * @param WP_Query $query Query object, passed by reference.
	 *
	 * @return string         WHERE query clause.
	 */
	public function search_acf_fields_where( $where, $query ) {
		if ( $query->is_main_query() && 'course' === $query->get( 'post_type' ) ) {
			global $wpdb;
			$where .= $wpdb->prepare(
				" OR (CONCAT(academic_department_code, ' ', course_number) LIKE %s OR course_code LIKE %s)",
				array(
					'%' . $query->get( 's' ) . '%',
					'%' . str_replace( ' ', '|', $query->get( 's' ) ) . '%',
				)
			);
		}

		return $where;
	}

}
