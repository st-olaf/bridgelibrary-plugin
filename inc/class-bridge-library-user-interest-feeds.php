<?php
/**
 * Bridge Library user interest feeds.
 *
 * @package bridge-library
 */

use GraphQLRelay\Node\Node;

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

		// Display per-institution URL on single view.
		add_filter( 'the_content', array( $this, 'maybe_render_our_feed_url' ) );

		// Add per-institution URL to GraphQL fields.
		add_action( 'graphql_register_types', array( $this, 'graphql_register_standalone_user_interest_feed' ) );
		add_action( 'graphql_register_types', array( $this, 'graphql_register_all_feeds_for_user' ) );
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
	 * Get the current user’s institution.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 *
	 * @return string
	 */
	private function get_user_institution( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		return str_replace( '.edu', '', get_field( 'bridge_library_institution', 'user_' . $user_id ) );
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
			$institution = $this->get_user_institution();
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
				'graphql_single_name' => 'UserInterestFeed',
				'graphql_plural_name' => 'UserInterestFeeds',
				'supports'            => array(
					'title',
					'page-attributes',
				),
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
			$alma_url = $item->children( 'alma', true )->link;

			// Handle items without alma:link details.
			if ( empty( $alma_url ) ) {
				$item->link = $this->replace_institution_url( $item->link, $institution );
			} else {

				$item->children( 'alma', true )->link = $this->replace_institution_url( $alma_url, $institution );
				$item->addChild( 'link' );
				$item->link = $this->replace_institution_url( $alma_url, $institution );
			}

			if ( empty( $item->guid ) ) {
				$item->addChild( 'guid' );
			}
			$item->guid = $item->link;
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

	/**
	 * Register custom feed URL for GraphQL.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function graphql_register_standalone_user_interest_feed() {
		$config = array(
			'type'        => 'String',
			'description' => __( 'User interest feeds', 'bridge-library' ),
			'args'        => array(
				'userId' => array(
					'type'        => 'ID',
					'description' => __( 'Enter your user ID to determine the correct institution', 'bridge-library' ),
				),
			),
			'resolve'     => function( $root, $args, $context, $info ) {
				// If $root is an array, it’s coming from the graphql_register_all_feeds_for_user() method and we can just pass it on through.
				if ( is_array( $root ) && array_key_exists( 'subscribeUrl', $root ) ) {
					return $root['subscribeUrl'];
				}

				$user_id = 0;
				if ( array_key_exists( 'userId', $args ) ) {
					$user_object = Node::fromGlobalId( $args['userId'] );
					$user_id     = $user_object['id'];
				} elseif ( array_key_exists( 'email', $info->variableValues ) ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$user = get_user_by( 'email', $info->variableValues['email'] ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					if ( is_a( $user, WP_User::class ) ) {
						$user_id = $user->ID;
					}
				}
				return $this->build_feed_url( get_the_ID(), $this->get_user_institution( $user_id ) );
			},
		);
		register_graphql_field( 'UserInterestFeed', 'subscribeUrl', $config );
		register_graphql_field( 'UserInterestFeeds', 'subscribeUrl', $config );

		// Register a custom `feedName` field.
		register_graphql_field(
			'UserInterestFeed',
			'feedName',
			array(
				'type'        => 'String',
				'description' => __( 'Alias of the post title.', 'bridge-library' ),
				'resolve'     => function( $root, $args, $context, $info ) {
					if ( is_array( $root ) ) {
						return get_the_title( $root['id'] );
					} else {
						return get_the_title( $root->fields['databaseId'] );
					}
				},
			)
		);
	}

	/**
	 * Return all user interest feeds on a User graphql model.
	 *
	 * Simplifies the frontend React logic.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function graphql_register_all_feeds_for_user() {
		$config = array(
			'type'        => array( 'list_of' => 'UserInterestFeed' ),
			'description' => __( 'All available user interest feeds', 'bridge-library' ),
			'resolve'     => function( $root, $args, $context, $info ) {
				$feeds = array();
				$posts = new WP_Query(
					array(
						'post_type'      => $this->post_type,
						'posts_per_page' => -1,
					)
				);

				foreach ( $posts->posts as $post ) {
					$feeds[] = array(
						'id'           => $post->ID,
						'title'        => get_the_title( $post ),
						'name'         => get_the_title( $post ),
						'slug'         => get_post_field( 'post_name', $post ),
						'subscribeUrl' => $this->build_feed_url( $post->ID, $this->get_user_institution( $root->fields['userId'] ) ),
					);
				}

				return $feeds;
			},

		);
		register_graphql_field( 'User', 'userInterestFeeds', $config );
	}
}
