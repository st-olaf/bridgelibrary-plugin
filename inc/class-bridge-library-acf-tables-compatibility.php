<?php
/**
 * Bridge Library ACF custom tables compatibility class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library ACF custom tables compatibility class.
 *
 * @since 1.0.0
 */
class Bridge_Library_ACF_Tables_Compatibility extends Bridge_Library {

	/**
	 * Object types to load from ACF custom tables.
	 *
	 * @since 1.0.0
	 *
	 * @var array $object_types
	 */
	private $object_types = array(
		'course',
		'resource',
		'user',
	);

	/**
	 * Meta keys to unset.
	 *
	 * These are standard fields for the ACF custom table, not ACF meta keys.
	 *
	 * @since 1.0.0
	 *
	 * @var array $unset
	 */
	private $unset = array(
		'id',
		'post_id',
	);

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_ACF_Tables_Compatibility class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_ACF_Tables_Compatibility();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_filter( 'acf/pre_load_meta', array( $this, 'pre_load_meta' ), 10, 2 );
	}

	/**
	 * Load metakeys from ACF custom database tables.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $meta    Null or array of meta keys.
	 * @param int   $post_id WP post ID.
	 *
	 * @return mixed         Null or array of meta keys.
	 */
	public function pre_load_meta( $meta, $post_id ) {
		if ( ! isset( $post_id ) ) {
			return $meta;
		}

		$post_type = get_post_type( $post_id );
		if ( in_array( $post_type, $this->object_types, true ) ) {
			global $wpdb;

			$meta       = array();
			$table_name = esc_sql( $wpdb->prefix . 'bridge_library_' . $post_type . '_meta' );
			$row        = $wpdb->get_row( 'SELECT * FROM ' . $table_name . ' LIMIT 1', 'ARRAY_A' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- table name is escaped above.

			foreach ( $this->unset as $key ) {
				unset( $row[ $key ] );
			}

			foreach ( $row as $key => $data ) {
				$field = get_field_object( $key );
				if ( isset( $field['key'] ) && $field['name'] === $key ) {
					$meta[ $key ]       = '';
					$meta[ '_' . $key ] = $field['key'];
				}
			}
		}

		return $meta;
	}

}
