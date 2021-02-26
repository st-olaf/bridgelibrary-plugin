<?php
/**
 * Bridge Library user interest feeds.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library user interest feeds.
 *
 * @since 1.0.0
 */
class Bridge_Library_User_Interest_Feeds {

	/**
	 * User Interest Feed CPT key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $post_type = 'user_interest_feed';

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_User_Interest_Feeds class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_User_Interest_Feeds();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// CPT.
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'save_post', array( $this, 'maybe_flush_cache' ), 25 );

		// Feed.
		add_action( 'init', array( $this, 'register_feed' ) );

		add_filter( 'the_content', array( $this, 'maybe_render_our_feed_url' ) );
	}

	/**
	 * Get the cache key.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $cpt_id      CPT ID.
	 * @param string $institution Institution slug.
	 *
	 * @return string
	 */
	private function get_cache_key( $cpt_id, $institution ) {
		return 'user-interest-feeds-' . $cpt_id . '-' . hash( 'sha256', $institution );
	}

	/**
	 * Build our custom feed URL.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $cpt_id      CPT ID.
	 * @param string $institution Institution slug.
	 *
	 * @return string
	 */
	public function build_feed_url( $cpt_id, $institution = null ) {
		if ( is_null( $institution ) ) {
			$institution = str_replace( '.edu', '', get_field( 'bridge_library_institution', 'user_' . get_current_user_id() ) );
		}

		return home_url( 'user-interest-feed/?feed_id=' . $cpt_id . '&institution=' . $institution );
	}

	/**
	 * Register the CPT.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_cpt() {
		register_extended_post_type(
			$this->post_type,
			array(
				'menu_icon'           => 'dashicons-rss',
				'show_in_rest'        => true,
				'show_in_graphql'     => true,
				'graphql_single_name' => 'User Interest Feed',
				'graphql_plural_name' => 'User Interest Feeds',
				'capability_type'     => 'user_interest_feed',
				'capabilities'        => array(
					'publish_posts'       => 'publish_user_interest_feeds',
					'edit_posts'          => 'edit_user_interest_feeds',
					'edit_others_posts'   => 'edit_others_user_interest_feeds',
					'delete_posts'        => 'delete_user_interest_feeds',
					'delete_others_posts' => 'delete_others_user_interest_feeds',
					'read_private_posts'  => 'read_private_user_interest_feeds',
					'edit_post'           => 'edit_user_interest_feed',
					'delete_post'         => 'delete_user_interest_feed',
					'read_post'           => 'read_user_interest_feed',
				),
				'admin_cols'          => array(
					'feed_url' => array(
						'title'    => 'Feed URL',
						'meta_key' => 'feed_url', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					),
				),
			),
			array(
				'slug' => 'user-interest-feeds',
			)
		);
	}

	/**
	 * Flush the cache when a feed is updated.
	 *
	 * @since 1.0.0
	 *
	 * @param int $post_id CPT ID.
	 *
	 * @return void
	 */
	public function maybe_flush_cache( $post_id ) {
		if ( get_post_type( $post_id ) !== $this->post_type ) {
			return;
		}

		delete_transient( $this->get_cache_key( $post_id, 'carleton' ) );
		delete_transient( $this->get_cache_key( $post_id, 'stolaf' ) );
	}

	/**
	 * Display custom feed URL on single view.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Markup.
	 *
	 * @return string
	 */
	public function maybe_render_our_feed_url( $content ) {
		if ( get_post_type() === $this->post_type ) {
			$content .= $this->build_feed_url( get_the_ID() );
		}

		return $content;
	}

	/**
	 * Register the feed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_feed() {
		add_feed( 'user-interest-feed', array( $this, 'render_feed' ) );
	}

	/**
	 * Render the feed.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function render_feed() {
		header( 'Content-Type: application/rss+xml' );

		// phpcs:disable WordPress.Security.NonceVerification
		if ( ! array_key_exists( 'feed_id', $_GET ) ) {
			http_response_code( 400 );
			echo 'Error: the feed ID must be provided.';
			return;
		}

		$cpt_id = intval( $_GET['feed_id'] );
		if ( isset( $_GET['institution'] ) ) {
			$institution = sanitize_key( $_GET['institution'] );
		} else {
			$institution = false;
		}
		// phpcs:enable WordPress.Security.NonceVerification

		echo $this->get_feed_contents( $cpt_id, $institution ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Retrieve feed contents.
	 *
	 * @param string $cpt_id      User Interest Feed CPT.
	 * @param string $institution Institution slug.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_feed_contents( $cpt_id, $institution ) {
		if ( get_transient( $this->get_cache_key( $cpt_id, $institution ) ) ) {
			return get_transient( $this->get_cache_key( $cpt_id, $institution ) );
		}

		$feed_url = get_field( 'feed_url', $cpt_id );

		$request = wp_remote_get( $feed_url, array( 'timeout' => 30 ) );

		if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
			return '';
		}

		$contents = wp_remote_retrieve_body( $request );

		$xml = simplexml_load_string( $contents );

		// Replace feed URL.
		$xml->channel->link = $this->build_feed_url( $cpt_id, $institution );

		// Replace all item links.
		foreach ( $xml->channel->item as $item ) {
			$url = $item->children( 'alma', true )->link;

			$item->children( 'alma', true )->link = $this->replace_institution_url( $url, $institution );
		}

		set_transient( $this->get_cache_key( $cpt_id, $institution ), $xml->asXML(), 12 * HOUR_IN_SECONDS );

		return $xml->asXML();
	}

	/**
	 * Replace institution code in feed URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url         Item URL.
	 * @param string $institution Institution slug.
	 *
	 * @return string
	 */
	public function replace_institution_url( $url, $institution ) {
		// Note: if we don’t replace the encoded '&amp;', it’ll be double-encoded.
		$search = array( '&amp;', 'INST:CCO', 'scope=CCO' );
		switch ( $institution ) {
			case 'stolaf':
			case 'stolaf.edu':
			case 'st-olaf':
				$replace = array( '&', 'INST:SOC', 'scope=SOC' );
				break;
			case 'carleton':
			case 'carleton.edu':
			default:
				$replace = array( '&', 'INST:CCO', 'scope=CCO' );
				break;
		}

		return str_replace( $search, $replace, $url );
	}
}
