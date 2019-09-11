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
class Bridge_Library_API_Provider_Base extends WP_REST_Controller {

	/**
	 * REST API namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string $namespace
	 */
	public $namespace = 'bridge-library/v1';

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Students.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-api-provider-students.php';
		new Bridge_Library_API_Provider_Students();

		// Courses.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-api-provider-courses.php';
		new Bridge_Library_API_Provider_Courses();

		// Resources.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-api-provider-resources.php';
		new Bridge_Library_API_Provider_Resources();
	}

}
