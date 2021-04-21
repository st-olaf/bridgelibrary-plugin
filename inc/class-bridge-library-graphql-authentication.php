<?php
/**
 * Bridge Library librarians class.
 *
 * @package bridge-library
 */

use WPGraphQL\JWT_Authentication\Auth;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path( BL_PLUGIN_FILE ) . '/../wp-graphql-jwt-authentication/src/Auth.php';

/**
 * Bridge Library librarians class.
 *
 * @since 1.0.0
 */
class Bridge_Library_GraphQL_Authentication extends Auth {

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_GraphQL_Authentication class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_GraphQL_Authentication();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		define( 'GRAPHQL_JWT_AUTH_SECRET_KEY', ':,Mau:P}p8CRGv{LekCsodX7}IIg}^0b' );
	}

	/**
	 * Get a signed token for the logged-in user.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_user_token() {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		return self::get_signed_token( wp_get_current_user() );
	}
}
