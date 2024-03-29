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
	 * @var self
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
		'IN' => 'January', /** Changed from 'Interim' to 'January' */
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
		'instructors'              => 'instructors',
	);

	/**
	 * Institution course code mapping.
	 *
	 * @since 1.0.0
	 *
	 * @var array<string, string>
	 */
	const INSTITUTIONS_COURSE_CODE_MAPPING = array(
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
		add_action( 'wp_ajax_copy_resources_to_course', array( $this, 'ajax_copy_resources_to_course' ) );

		// Schedule automatic updates.
		add_action( 'bridge_library_schedule_daily', array( $this, 'background_update_courses' ), 10 );

		// Add category-level resources and librarians to course related resources and librarians.
		add_filter( 'acf/load_value/key=field_5cc326f90696b', array( $this, 'include_academic_department_resources' ), 10, 3 );
		add_filter( 'acf/load_value/key=field_5e5819970fbfd', array( $this, 'include_academic_department_librarians' ), 10, 3 );

		// Add institution and course number data to admin list view.
		add_action( 'manage_posts_extra_tablenav', array( $this, 'include_course_data_in_title' ) );

		// Include course code and number in searches.
		add_action( 'posts_join', array( $this, 'search_acf_fields_join' ), 10, 2 );
		add_action( 'posts_where', array( $this, 'search_acf_fields_where' ), 10, 2 );
		add_filter( 'acf/fields/relationship/query/name=related_courses_resources', array( $this, 'search_acf_fields_from_acf' ), 10, 3 );

		// Set/reset hidden flag when academic department settings are changed.
		add_action( 'saved_term', array( $this, 'saved_term' ), 10, 4 );

		// Exclude hidden courses from frontend.
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );

		// Tweak course titles in admin list and resource related_courses ACF field.
		add_filter( 'acf/fields/relationship/result/key=field_5cc3260215ce7', array( $this, 'modify_course_acf_titles' ), 10, 2 );
	}

	/**
	 * Retrieve the course term taxonomy term matching the date.
	 *
	 * @since 1.5.0
	 *
	 * @param string $date Optional date; defaults to today.
	 *
	 * @return array<array-key, int>
	 */
	public static function current_course_term_ids( $date = null ) {
		$user_institution = Bridge_Library_Users::get_instance()->get_domain();
		$date             = new DateTimeImmutable( $date );
		$this_year        = date( 'Y' );
		$term_names       = array();

		if ( 'stolaf.edu' === $user_institution ) {
			switch ( $date->format( 'F' ) ) {
				case 'January':
					$term_names[] = $this_year . '-January';
					break;

				case 'February':
				case 'March':
				case 'April':
				case 'May':
					$term_names[] = $this_year . '-Spring';
					break;

				case 'June':
				case 'July':
				case 'August':
					$term_names[] = $this_year . '-Summer';
					$term_names[] = $this_year . '-Summer 1';
					$term_names[] = $this_year . '-Summer 2';
					break;

				case 'September':
				case 'October':
				case 'November':
				case 'December':
					$term_names[] = $this_year . '-Fall';
					break;
			}
		} elseif ( 'carleton.edu' === $user_institution ) {
			switch ( $date->format( 'F' ) ) {
				case 'January':
				case 'February':
					$term_names[] = $this_year . '-Winter';
					break;

				case 'March':
					$term_names[] = date( 'd' ) > 20
						? $this_year . '-Winter'
						: $this_year . '-Spring';
					break;

				case 'April':
				case 'May':
				case 'June':
					$term_names[] = $this_year . '-Spring';
					break;

				case 'July': // Not part of the Fall term, but should display upcoming courses.
				case 'August': // Not part of the Fall term, but should display upcoming courses.
				case 'September':
				case 'October':
				case 'November':
				case 'December':
					$term_names[] = $this_year . '-Fall';
					break;
			}
		}

		if ( empty( $term_names ) ) {
			return array();
		}

		$args = array(
			'taxonomy' => 'course_term',
			'name'     => $term_names,
			'fields'   => 'ids',
		);

		return ( new WP_Term_Query( $args ) )->terms;
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
						<?php wp_nonce_field( 'update_courses', 'update_courses_nonce' ); ?>

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
						<?php wp_nonce_field( 'start_background_update', 'start_background_update_nonce' ); ?>

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
						<?php wp_nonce_field( 'update_course_by_id', 'update_course_by_id_nonce' ); ?>

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

		if ( ! isset( $_REQUEST['update_courses_nonce'] ) || ! isset( $_REQUEST['action'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['update_courses_nonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
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

		if ( ! isset( $_REQUEST['update_course_by_id_nonce'] ) || ! isset( $_REQUEST['action'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['update_course_by_id_nonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
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
		} else {
			$results = new WP_Error( '',' Missing course id and code' );
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
		if ( ! isset( $_REQUEST['start_background_update_nonce'] ) || ! isset( $_REQUEST['action'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['start_background_update_nonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
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

			error_log( 'Alma background update: retrieved ' . $total_count . ' courses' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			if ( $total_count > $per_page ) {
				$total_pages = ceil( $total_count / $per_page );

				error_log( 'Alma background update: ' . $total_pages . ' pages' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

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
	 * Start a full background update.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_copy_resources_to_course() {
		if ( ! isset( $_REQUEST['copy_resources_to_course_nonce'] ) || ! isset( $_REQUEST['action'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['copy_resources_to_course_nonce'] ), sanitize_key( $_REQUEST['action'] ) ) ) {
			wp_send_json_error( 'Access denied.', 401 );
			wp_die();
		}

		if ( ! isset( $_REQUEST['source_id'] ) || ! isset( $_REQUEST['destination_id'] ) || ! isset( $_REQUEST['which_resources'] ) ) {
			wp_send_json_error( 'Invalid request.', 400 );
			wp_die();
		}

		$which          = sanitize_key( wp_unslash( $_REQUEST['which_resources'] ) );
		$source_id      = absint( $_REQUEST['source_id'] );
		$destination_id = absint( $_REQUEST['destination_id'] );
		$results        = array();

		if ( in_array( $which, array( 'all_resources', 'core_resources' ), true ) ) {
			$core_resources       = get_field( 'core_resources', $source_id );
			$core_resources_count = count( $core_resources );
			update_field( 'core_resources', $core_resources, $destination_id );
			// Translators: %d is the count of resources.
			$results[] = sprintf( __( 'copied %1$d core %2$s', 'bridge-library' ), $core_resources_count, _n( 'resource', 'resources', $core_resources_count, 'bridge-library' ) );
		}

		if ( in_array( $which, array( 'all_resources', 'other_resources' ), true ) ) {
			$related_courses_resources       = get_field( 'related_courses_resources', $source_id );
			$related_courses_resources_count = count( $related_courses_resources );
			update_field( 'related_courses_resources', $related_courses_resources, $destination_id );
			// Translators: %d is the count of resources.
			$results[] = sprintf( __( 'copied %1$d related %2$s', 'bridge-library' ), $related_courses_resources_count, _n( 'resource', 'resources', $related_courses_resources_count, 'bridge-library' ) );
		}

		wp_send_json_success( __( 'Results', 'bridge-library' ) . ': ' . implode( '; ', $results ), 201 );
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
	 * @return array<int, int> WP course IDs.
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
		$imploded_course_codes = implode( ',', $course_codes );

		$hidden_term = get_term_by( 'slug', 'hidden', 'hidden' );

		$hidden_post_query = <<<SQL
			SELECT object_id
			FROM {$wpdb->term_relationships}
			WHERE term_taxonomy_id = $hidden_term->term_id
		SQL;

		$query = sprintf(
			<<<SQL
				SELECT post_id
				FROM {$wpdb->prefix}{$this->acf_meta_table}
				WHERE course_code IN ($imploded_course_codes)
				AND post_id NOT IN ($hidden_post_query)
			SQL, // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
		);
		$posts = $wpdb->get_col( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- this is more performant than a get_posts with meta_query would be, and we’ve escaped all the input above.

		// If ACF fails, try wp_postmeta.
		if ( is_null( $posts ) ) {
			$posts = $wpdb->get_col( <<<SQL
				SELECT post_id
				FROM {$wpdb->postmeta}
				WHERE meta_key = 'course_code'
				AND meta_value IN ($imploded_course_codes);
			SQL
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- this is more performant than a get_posts with meta_query would be, and we’ve escaped all the input above.
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

			// Attach taxonomy terms.
			$course = $this->extract_course_data( $course, $post_id );

			// Add other metadata.
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
				'institution'    => str_replace( array_flip( self::INSTITUTIONS_COURSE_CODE_MAPPING ), self::INSTITUTIONS_COURSE_CODE_MAPPING, $parts[0] ),
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
			$course['course_term']    = array_filter( array( $this->get_course_term( explode( '/', $course_code['course_term'] ) ) ) );
		}

		if ( isset( $course['academic_department']['desc'] ) ) {
			$academic_department_name = $course['academic_department']['desc'];
		} elseif ( isset( $course['academic_department']['value'] ) ) {
			$academic_department_name = $course['academic_department']['value'];
		} else {
			$academic_department_name = null;
		}

		if ( isset( $academic_department_name ) && is_array( $course_code ) && array_key_exists( 'institution', $course_code ) ) {
			$academic_department_term_id = $this->get_or_create_term( $academic_department_name, 'academic_department', $course_code['institution'] );
			wp_set_object_terms( $post_id, $academic_department_term_id, 'academic_department' );
			$course['academic_department_code'] = $course['academic_department']['value'];
			$course['academic_department']      = array( $academic_department_term_id );

			$term = get_term( $academic_department_term_id, 'academic_department' );
			$title_search_strings = get_field( 'hide_courses_by_title', 'term_' . $term->term_id );

			$post = get_post( $post_id );

			if ( get_field( 'hide_all_courses', 'term_' . $term->term_id ) ) {
				$this->set_visible( array( $post ), false );
			} elseif ( strlen( $title_search_strings) > 0 && false !== strpos( $post->post_title, $title_search_strings ) ) {
				$this->set_visible( array( get_post( $post_id ) ), false );
			} else {
				$this->set_visible( array( get_post( $post_id ) ), true );
			}
		}

		$alma_api    = Bridge_Library_API_Alma::get_instance();
		$full_course = $alma_api->request( 'courses/' . $course['id'], array( 'view' => 'full' ) );

		if ( is_wp_error( $full_course ) ) {
			return $course;
		}

		if ( ! empty( $full_course['instructor'] ) ) {
			$course['instructors'] = array_map(
				function( $instructor ) {
					return array(
						'name' => implode( ' ', array_filter( array( $instructor['first_name'], $instructor['last_name'] ) ) ),
					);
				},
				$full_course['instructor']
			);
		}

		if ( empty( $full_course['reading_lists'] ) ) {
			return $course;
		}

		if ( array_key_exists( 'reading_lists', $full_course ) && array_key_exists( 'reading_list', $full_course['reading_lists'] ) && is_iterable( $full_course['reading_lists']['reading_list'] ) && ! empty( $full_course['reading_lists']['reading_list'] ) ) {
			$resources = Bridge_Library_Resources::get_instance();
			foreach ( $full_course['reading_lists']['reading_list'] as $reading_list ) {
				$citations = $reading_list['citations']['citation'];

				$active_resources   = array();
				$existing_resources = get_field( 'related_courses_resources', $post_id );
				if ( ! is_array( $existing_resources ) ) {
					$existing_resources = array();
				}

				if ( is_array( $citations ) ) {
					foreach ( $citations as $citation ) {
						$resource_id        = $resources->update_reading_list( $citation, $post_id );
						$active_resources[] = (int) $resource_id;
					}
				}

				$active_resources = array_merge( $active_resources, $existing_resources );
				$active_resources = array_unique( $active_resources );

				// Update the course post.
				update_field( 'related_courses_resources', $active_resources, $post_id );
			}
		}

		return $course;
	}

	/**
	 * Create or retrieve a term ID.
	 *
	 * @since 1.0.0
	 *
	 * @param string $term             Term name.
	 * @param string $taxonomy         Taxonomy.
	 * @param string $institution_name Institution name.
	 *
	 * @return int                     Term ID.
	 */
	private function get_or_create_term( $term, $taxonomy, $institution_name = null ) {
		require_once ABSPATH . '/wp-admin/includes/taxonomy.php';

		if ( is_null( $institution_name ) ) {
			$term = wp_create_term( $term, $taxonomy );
		} else {
			$term = wp_create_term( $term . ' (' . $institution_name . ')', $taxonomy );
		}

		return absint( $term['term_id'] );
	}

	/**
	 * Retrieve a term ID from the academic term.
	 *
	 * @since 1.0.0
	 *
	 * @param array $term_parts Academic term and year.
	 *
	 * @return int|null         Academic term ID.
	 */
	private function get_course_term( $term_parts ) {

		if ( empty( array_filter( $term_parts ) ) ) {
			return null;
		}

		$term_slug = '20' . $term_parts[0] . '-' . str_replace( array_flip( $this->academic_term_slugs ), $this->academic_term_slugs, $term_parts[1] );

		$term = wp_create_term( $term_slug, 'course_term' );
		return absint( $term['term_id'] );
	}

	/**
	 * Include department-level related resources for courses.
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
			if ( ! is_array( $value ) ) {
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
	 * Include department-level librarians for courses.
	 * If any librarians are associated with the course, they should supersede the department-level librarian(s).
	 *
	 * @see https://git.luminfire.net/clients/stolaf-bridge/bridge-library-plugin-v1/issues/9.
	 *
	 * @since 1.0.6
	 *
	 * @param mixed $value   ACF field value.
	 * @param int   $post_id Post ID.
	 * @param array $field   ACF field object.
	 *
	 * @return mixed         ACF field value.
	 */
	public function include_academic_department_librarians( $value, $post_id, $field ) {

		// Include term results for GraphQL results. We don’t want to deal with tracking and unsetting changes to all posts when a resource->academic department relationship is updated.
		if ( isset( $_SERVER['REQUEST_URI'] ) && '/graphql' === $_SERVER['REQUEST_URI'] ) {
			if ( is_null( $value ) ) {
				$value = array();
			}

			// If no librarians are set for the course, get the department-level librarians.
			if ( empty( $value ) ) {
				$librarian_query = new WP_Query(
					array(
						'post_type'      => 'librarian',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
							array(
								'taxonomy' => 'academic_department',
								'terms'    => wp_get_post_terms( $post_id, 'academic_department', array( 'fields' => 'ids' ) ),
							),
						),
					)
				);

				if ( $librarian_query->have_posts() ) {
					$value = $librarian_query->posts;
				}
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
			add_filter( 'the_title', array( $this, 'modify_course_acf_titles' ), 10, 2 );
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
		if ( $this->search_custom_columns( $query ) ) {
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
		if ( $this->search_custom_columns( $query ) ) {
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

	/**
	 * Should we search custom DB columns?
	 *
	 * @since 1.0.4
	 *
	 * @param WP_Query $query WP_Query object.
	 *
	 * @return bool           Whether to use custom column or not.
	 */
	private function search_custom_columns( $query ) {
		if ( $_POST && array_key_exists( 'action', $_POST ) && 'acf/fields/relationship/query' === $_POST['action'] && array_key_exists( 'field_key', $_POST ) && 'field_5cc3260215ce7' === $_POST['field_key'] ) { // phpcs:ignore WordPress.Security.NonceVerification -- ACF will handle this.
			return true;
		} elseif ( $query->is_main_query() && 'course' === $query->get( 'post_type' ) && $query->is_search() ) {
			return true;
		}

		return false;
	}

	/**
	 * Hook into ACF related_courses_resources search to include custom DB column.
	 *
	 * @since 1.0.4
	 *
	 * @param array $args    WP_Query args.
	 * @param array $field   Field object.
	 * @param int   $post_id Post ID.
	 *
	 * @return array         WP_Query args.
	 */
	public function search_acf_fields_from_acf( $args, $field, $post_id ) {
		$this->should_search_custom_db = true;
		return $args;
	}

	/**
	 * Add more data to course title in ACF group.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $title   Post title.
	 * @param int|WP_Post $post    Post ID or object.
	 *
	 * @return string              Post title.
	 */
	public function modify_course_acf_titles( $title, $post ) {
		$course_number = get_field( 'course_number', $post );
		$institution   = get_the_terms( $post, 'institution' );
		$course_code   = explode( '|', get_field( 'course_code', $post ) );

		// Add term to ACF fields but not course CPT list.
		if ( get_current_screen() && 'edit-course' === get_current_screen()->id ) {
			$course_term = array();
		} else {
			$course_term = get_the_terms( $post, 'course_term' );
		}

		if ( ! empty( $institution ) ) {

			return sprintf(
				'%1$s%2$s: %3$s %4$s %5$s',
				$course_code[1],
				$course_number ? ' ' . $course_number : '',
				$title,
				is_array( $institution ) ? '(' . implode( ', ', wp_list_pluck( $institution, 'name' ) ) . ')' : '',
				is_array( $course_term ) ? '(' . implode( ', ', wp_list_pluck( $course_term, 'name' ) ) . ')' : ''
			);
		}

		return $title;
	}

	/**
	 * Exclude hidden posts from the frontend.
	 *
	 * @since 1.3.0
	 *
	 * @param WP_Query $query Query.
	 *
	 * @return void
	 */
	public function pre_get_posts( WP_Query $query ) {
		if ( is_admin() || 'course' !== $query->get( 'post_type' ) ) {
			return;
		}

		// Note: courses are also cached using Bridge_Library_Courses::get_post_ids_by_course_codes() method.
		// Any changes here must be manually made there as well.

		$tax_query = $query->get( 'tax_query' );

		if ( ! $tax_query ) {
			$tax_query = array();
		}

		$tax_query[] = array(
			'taxonomy' => 'hidden',
			'field'    => 'slug',
			'terms'    => 'hidden',
			'operator' => 'NOT IN',
		);

		$query->set( 'tax_query', $tax_query );
	}

	/**
	 * Hide/reset hidden flags for related courses.
	 *
	 * @since 1.3.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @param bool   $update   Whether this is an existing term being updated.
	 *
	 * @return void
	 */
	public function saved_term( int $term_id, int $tt_id, string $taxonomy, bool $update ) {
		if ( 'academic_department' !== $taxonomy ) {
			return;
		}

		if ( get_field( 'hide_all_courses', 'term_' . $term_id ) ) {
			$this->set_all_course_visibility_for_department( $term_id, false );
		} else {
			if ( get_field( 'hide_courses_by_title', 'term_' . $term_id ) ) {
				$this->set_specific_course_visibility_for_term( $term_id, get_field( 'hide_courses_by_title', 'term_' . $term_id ) );
			} else {
				$this->set_all_course_visibility_for_department( $term_id, true );
			}
		}

	}

	/**
	 * Set visibility for all courses in this department.
	 *
	 * @since 1.3.0
	 *
	 * @param int  $term_id Term ID.
	 * @param bool $visible Visible.
	 *
	 * @return void
	 */
	protected function set_all_course_visibility_for_department( int $term_id, bool $visible ) {
		$courses = $this->get_courses_for_department( $term_id );

		$this->set_visible( $courses, $visible );
	}

	/**
	 * Set visibility for courses matching the search strings.
	 *
	 * @since 1.3.0
	 *
	 * @param int    $term_id        Term ID.
	 * @param string $search_strings Search strings.
	 *
	 * @return void
	 */
	protected function set_specific_course_visibility_for_term( int $term_id, string $search_strings ) {
		$all_courses    = $this->get_courses_for_department( $term_id );
		$hidden_courses = array();

		$search_strings = explode( PHP_EOL, $search_strings );
		$search_strings = array_map( 'trim', $search_strings );

		foreach ( $all_courses as $index => $course ) {
			foreach ( $search_strings as $search_string ) {
				if ( false !== strpos( $course->post_title, $search_string ) ) {
					$hidden_courses[ $index ] = $course;
					continue;
				}
			}
		}

		$this->set_visible( $hidden_courses, false );
		$this->set_visible( array_diff_key( $all_courses, $hidden_courses ), true );
	}

	/**
	 * Set visibility on the given posts.
	 *
	 * @since 1.3.0
	 *
	 * @param array<int, \WP_Post> $posts   Array of posts.
	 * @param bool                 $visible Visible.
	 *
	 * @return void
	 */
	protected function set_visible( array $posts, bool $visible ) {
		if ( $visible ) {
			foreach ( $posts as $post ) {
				wp_remove_object_terms( $post->ID, 'hidden', 'hidden' );
			}
		} else {
			foreach ( $posts as $post ) {
				wp_set_object_terms( $post->ID, 'hidden', 'hidden', true );
			}
		}
	}

	/**
	 * Get all courses for the given department.
	 *
	 * @since 1.3.0
	 *
	 * @param int $term_id Term ID.
	 *
	 * @return array<int, \WP_Post>
	 */
	private function get_courses_for_department( int $term_id ): array {
		return (new WP_Query(
			array(
				'post_type'      => 'course',
				'posts_per_page' => -1,
				'post_status'    => 'any',
				'tax_query'      => array(
					array(
						'taxonomy' => 'academic_department',
						'terms'    => $term_id,
					),
				),
			)
		))->posts;
	}
}
