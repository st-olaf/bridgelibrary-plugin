<?php
/**
 * User update async request processing.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * User update async request processing.
 *
 * @package bridge-library
 */
class Bridge_Library_Users_Async_Process extends WP_Background_Process {

	/**
	 * Name of action.
	 *
	 * @var string
	 */
	protected $action = 'bridge_library_update_user_data';

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
		$users = Bridge_Library_Users::get_instance();

		$action  = $item['action'];
		$user_id = $item['user_id'];

		$process = $users->{$action}( $user_id );

		if ( is_wp_error( $process ) ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions
			error_log(
				print_r(
					[
						'context' => 'Updating user: ' . wp_json_encode( $item ),
						'error'   => $process,
					]
				)
			);
			// phpcs:enable WordPress.PHP.DevelopmentFunctions
		}

		return false;
	}

}
