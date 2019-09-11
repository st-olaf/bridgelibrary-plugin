<?php
/**
 * Alma API async request processing.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Alma API async request processing.
 *
 * @package bridge-library
 */
class Alma_Async_Process extends WP_Background_Process {

	/**
	 * Name of action.
	 *
	 * @var string
	 */
	protected $action = 'bridge_library_get_all_pages';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$alma = Bridge_Library_API_Alma::get_instance();
		$data = $alma->request( $item['path'], $item['query'] );

		$courses = Bridge_Library_Courses::get_instance();
		$courses->update_courses( $data['course'] );

		return false;
	}

}
