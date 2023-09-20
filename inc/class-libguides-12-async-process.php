<?php
/**
 * LibGuides API async request processing.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * LibGuides API async request processing.
 *
 * @package bridge-library
 */
class LibGuides_12_Async_Process extends WP_Background_Process {

	/**
	 * Name of action.
	 *
	 * @var string
	 */
	protected $action = 'bridge_library_get_all_libguides';

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
		error_log( 'LibGuides 1.2 async process: ' . wp_json_encode( $item ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		$resources = Bridge_Library_Resources::get_instance();

		foreach ( $item['assets'] as $asset ) {
			$resources->create_resource_from_libguides_asset( $asset, $item['institution'] );
		}

		return false;
	}

}
