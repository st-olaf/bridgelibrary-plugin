<?php
/**
 * Bridge Library librarians class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library librarians class.
 *
 * @since 1.0.0
 */
class Bridge_Library_Librarians extends Bridge_Library {

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_Librarians class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_Librarians();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_filter( 'acf/load_value/key=field_5ce2b758500b1', array( $this, 'load_backup_email_address' ), 10, 3 );
		add_filter( 'acf/load_value/key=field_5cc9c079b4410', array( $this, 'load_backup_image_url' ), 10, 3 );
	}

	/**
	 * Load WP user email address as backup for the custom email field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value   Field value.
	 * @param int    $post_id Post ID.
	 * @param array  $field   Field object.
	 *
	 * @return string         Field value.
	 */
	public function load_backup_email_address( $value, $post_id, $field ) {
		if ( empty( $value ) ) {
			$user  = get_user_by( 'id', str_replace( 'user_', '', $post_id ) );
			$value = $user->user_email;
		}

		return $value;
	}

	/**
	 * Load Gravatar as a backup for the Google profile photo field.
	 *
	 * @since 1.0.0
	 *
	 * @param string $value   Field value.
	 * @param int    $post_id Post ID.
	 * @param array  $field   Field object.
	 *
	 * @return string         Field value.
	 */
	public function load_backup_image_url( $value, $post_id, $field ) {
		if ( empty( $value ) ) {
			$value = get_avatar_url( str_replace( 'user_', '', $post_id ) );
		}

		return $value;
	}

}
