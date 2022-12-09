<?php
/**
 * Bridge Library WP CLI.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library WP CLI.
 *
 * @since 1.0.0
 */
class Bridge_Library_WP_CLI {

	/**
	 * Import all courses from Alma.
	 *
	 * ## OPTIONS
	 *
	 * [--background]
	 * : Run in the background using the wp cron system.
	 *
	 * [--offset=<1>]
	 * : First page of results to retrieve
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bridge-library update-courses
	 *     Success: Processing batch 1…
	 *     Info: Processing 2 more batches
	 *     Success: Processing batch 2…
	 *     Success: Processing batch 3…
	 *     Success: Complete!
	 *
	 *     $ wp bridge-library update-courses --offset=1
	 *     Success: Processing batch 2…
	 *     Info: Processing 1 more batch
	 *     Success: Processing batch 3…
	 *     Success: Complete!
	 *
	 *     $ wp bridge-library update-courses
	 *     Success: Updated 1052 courses.
	 *
	 *     $ wp bridge-library update-courses --background
	 *     Success: Started updating courses in the background.
	 *
	 * @subcommand update-courses
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command flags.
	 *
	 * @return void
	 */
	public function update_courses( $args, $assoc_args ) {
		$courses  = Bridge_Library_Courses::get_instance();
		$alma_api = Bridge_Library_API_Alma::get_instance();
		$query    = array();

		if ( array_key_exists( 'offset', $assoc_args ) ) {
			$offset          = absint( $assoc_args['offset'] );
			$query['offset'] = $offset * $alma_api->limit;
		} else {
			$offset = 1;
		}

		if ( array_key_exists( 'background', $assoc_args ) ) {
			$courses->background_update_courses();
			$this->success( __( 'Started updating courses in the background.', 'bridge-library' ) );
		} else {
			$results = $alma_api->request( 'courses/', $query );
			$this->success(
				sprintf(
					// Translators: %d is the offset.
					__( 'Processing batch %d…', 'bridge-library' ),
					$offset
				)
			);
			$courses->update_courses( $results['course'] );

			// Iterate as many times as necessary to get all courses.
			if ( array_key_exists( 'total_record_count', $results ) ) {
				$total_count = $results['total_record_count'];
				$per_page    = count( $results['course'] );

				if ( $total_count > $per_page ) {
					$total_pages = ceil( $total_count / $per_page );

					$this->info(
						sprintf(
							// Translators: %d is the count of pages.
							_n( 'Processing %d more batch', 'Processing %d more batches', $total_pages, 'bridge-library' ),
							( $total_pages + 1 - $offset ),
						)
					);

					for ( $i = $offset; $i < $total_pages; $i++ ) {
						$query['offset'] = $i * $alma_api->limit;
						$results         = $alma_api->request( 'courses/', $query );
						// Translators: %s is the page number.
						$this->success( sprintf( __( 'Processing batch %s…', 'bridge-library' ), ( $i + 1 ) ) );
						$courses->update_courses( $results['course'] );
					}
				}
			}

			$this->success( __( 'Complete!', 'bridge-library' ) );
		}
	}

	/**
	 * Import one or multiple courses from Alma.
	 *
	 * ## OPTIONS
	 *
	 * <course_code>...
	 * : One or more course codes or Alma course IDs
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp bridge-library update-course "S|FMS|101|22/FA" 17006385690002971
	 *     Success: Updated 2 courses.
	 *
	 * @subcommand update-course
	 *
	 * @param array $args       Command arguments.
	 * @param array $assoc_args Command flags.
	 *
	 * @return void
	 */
	public function update_course( $args, $assoc_args ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$alma_api = Bridge_Library_API_Alma::get_instance();
		$courses  = array();

		foreach ( $args as $course_code_or_id ) {
			$course_code_or_id = sanitize_text_field( $course_code_or_id );

			if ( false !== strpos( $course_code_or_id, '|' ) ) {
				// Course code.
				$query   = array( 'q' => 'code~' . $course_code_or_id );
				$results = $alma_api->request( 'courses', $query );
			} else {
				// Alma ID.
				$results = array( 'course' => $alma_api->request( 'courses/' . $course_code_or_id ) );
			}

			if ( empty( $results['course'] ) ) {
				// Translators: %s is the course code or ID.
				$this->error( sprintf( __( 'Couldn’t find course %s', 'bridge-library' ), $course_code_or_id ) );
			} else {
				$courses[] = $results['course'];
			}
		}

		$updated = Bridge_Library_Courses::get_instance()->update_courses( $courses );

		$this->success(
			sprintf(
				// Translators: %1$d is the count of courses; %2$s is singular/plural course.
				__( 'Updated %1$d %2$s.', 'bridge-library' ),
				count( $updated ),
				_n( 'course', 'courses', count( $updated ), 'bridge-library' ),
			)
		);
	}

	/**
	 * Log a normal message.
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 */
	protected function info( string $message ) : void {
		$message = 'Info: ' . $message;

		if ( $this->is_wp_cli() ) {
			WP_CLI::log( $message );
		} else {
			error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			echo esc_attr( $message );
		}
	}

	/**
	 * Log a success message.
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 */
	protected function success( string $message ) : void {
		if ( $this->is_wp_cli() ) {
			WP_CLI::success( $message );
		} else {
			error_log( 'Success: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			echo 'Success: ' . esc_attr( $message );
		}
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 */
	protected function warning( string $message ) : void {
		if ( $this->is_wp_cli() ) {
			WP_CLI::warning( $message );
		} else {
			error_log( 'Warning: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			echo 'Warning: ' . esc_attr( $message );
		}
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message Message to log.
	 *
	 * @return void
	 */
	protected function error( string $message ) : void {
		if ( $this->is_wp_cli() ) {
			WP_CLI::error( $message );
		} else {
			error_log( 'Error: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			echo 'Error: ' . esc_attr( $message );
		}
	}

	/**
	 * Determine whether this request is running in wp-cli environment.
	 *
	 * @return bool
	 */
	protected function is_wp_cli() : bool {
		return ( defined( 'WP_CLI' ) && WP_CLI );
	}
}
