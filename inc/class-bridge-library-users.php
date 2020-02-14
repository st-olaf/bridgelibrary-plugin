<?php
/**
 * Bridge Library users class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library users class.
 *
 * @since 1.0.0
 */
class Bridge_Library_Users {

	/**
	 * Class instance.
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Async processing class.
	 *
	 * @since 1.0.0
	 *
	 * @var Bridge_Library_Users_Async_Process $async
	 */
	public $async;

	/**
	 * ACF Custom DB table name (without wpdb prefix).
	 *
	 * @since 1.0.0
	 *
	 * @var string $acf_meta_table
	 */
	private $acf_meta_table = 'bridge_library_user_meta';

	/**
	 * Post types to manage capabilities for.
	 *
	 * @since 1.0.0
	 *
	 * @var array $post_types
	 */
	private $post_types = array(
		'course',
		'resource',
		'librarian',
	);

	/**
	 * Read-only ACF fields for non-admin users.
	 *
	 * @since 1.0.0
	 *
	 * @var array $read_only_fields
	 */
	private $read_only_fields = array(
		// User data.
		'alma_id',
		'alternate_id',
		'bridge_library_institution',
		'expiration_date',
		'google_id',
		'primo_id',

		// Related CPTs.
		'courses',
		'courses_cache_updated',
		'resources',
		'resources_cache_updated',
		'primo_favorites',
		'primo_favorites_cache_updated',
		'librarians',
		'librarians_cache_updated',
		'circulation_data',
		'circulation_data_cache_updated',
	);

	/**
	 * Map Google institution meta to institutions taxonomy term slugs.
	 *
	 * @since 1.0.0
	 *
	 * @var array $institution_term_mapping
	 */
	private $institution_term_mapping = array(
		'carleton.edu' => 'carleton',
		'stolaf.edu'   => 'st-olaf',
	);

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_Users class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_Users();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Add library staff role and set custom permissions.
		register_activation_hook( BL_PLUGIN_FILE, array( $this, 'set_user_permissions' ) );
		register_deactivation_hook( BL_PLUGIN_FILE, array( $this, 'remove_user_permissions' ) );

		// Add custom capabilities.
		add_filter( 'map_meta_cap', array( $this, 'map_meta_caps' ), 10, 4 );

		// Store data on signup.
		add_action( 'gal_user_loggedin', array( $this, 'store_user_meta' ), 10, 5 );

		// Refresh user data on login.
		add_action( 'wp_login', array( $this, 'update_user_data' ), 10, 2 );

		// Restrict courses to staff user’s institution.
		add_action( 'pre_get_posts', array( $this, 'scope_staff_courses' ) );

		foreach ( $this->post_types as $post_type ) {
			add_filter( "views_edit-$post_type", array( $this, 'add_admin_views_note' ) );
		}

		// Add action buttons to user profile page.
		add_action( 'show_user_profile', array( $this, 'add_user_actions' ), 5 );
		add_action( 'edit_user_profile', array( $this, 'add_user_actions' ), 5 );

		// Add ajax hooks.
		add_action( 'wp_ajax_cache_user_data', array( $this, 'ajax_cache_user_data' ) );

		// Add personal data hooks.
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ), 10 );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ), 10 );

		// Schedule automatic user expiry.
		add_action( 'bridge_library_schedule_daily', array( $this, 'clean_up_users' ) );

		// Add user cleanup to admin tools page.
		add_action( 'bridge_library_admin_settings', array( $this, 'admin_clean_up_users' ), 11 );
		add_action( 'wp_ajax_clean_up_users', array( $this, 'ajax_clean_up_users' ) );

		// Set up async handler.
		require_once BL_PLUGIN_DIR . '/inc/class-bridge-library-users-async-process.php';
		$this->async = new Bridge_Library_Users_Async_Process();

		// Allow admin users to save favorite posts.
		add_filter( 'pre_option_sticky_posts', array( $this, 'get_user_sticky_posts' ), 10, 3 );

		// Handle favorite post ACF group.
		add_filter( 'acf/load_value/key=field_5d3207d0cd007', array( $this, 'acf_load_user_sticky_posts_meta' ), 10, 3 );
		add_filter( 'acf/update_value/key=field_5d3207d0cd007', array( $this, 'acf_update_user_sticky_posts_meta' ), 10, 3 );

		// Handle user favorites display and query.
		add_filter( 'views_edit-course', array( $this, 'course_views' ) );
		add_filter( 'views_edit-resource', array( $this, 'resource_views' ) );
		add_action( 'pre_get_posts', array( $this, 'limit_to_user_favorites' ) );
		add_filter( 'acf/fields/relationship/query/name=related_courses_resources', array( $this, 'acf_sort_user_sticky_posts' ), 10, 3 );
		add_filter( 'acf/fields/relationship/query/name=core_resources', array( $this, 'acf_sort_user_sticky_posts' ), 10, 3 );

		// Prevent non-admins from editing some ACF fields.
		add_action( 'edit_user_profile', array( $this, 'maybe_load_admin_js' ) );
		add_action( 'show_user_profile', array( $this, 'maybe_load_admin_js' ) );

		// Force circulation data to a string for GraphQL.
		add_filter( 'acf/load_value/key=field_5d52eb9a29516', array( $this, 'force_circulation_data_string' ) );

		// Add user’s institution to body class to control institution-specific link visibility.
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	/**
	 * Get a user’s institution.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id WP user ID.
	 *
	 * @return string           User domain name.
	 */
	public function get_domain( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
		}

		$domain = get_field( 'bridge_library_institution', 'user_' . $user_id );

		if ( empty( $domain ) ) {
			$domain = 'stolaf.edu';
		}

		return $domain;
	}

	/**
	 * Get a user’s courses.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id WP user ID.
	 *
	 * @return array            User course IDs.
	 */
	public function get_courses( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
		}

		return get_field( 'courses', 'user_' . $user_id );
	}

	/**
	 * Get a user’s resources.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id WP user ID.
	 *
	 * @return array            User resource IDs.
	 */
	public function get_resources( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
		}

		return get_field( 'resources', 'user_' . $user_id );
	}

	/**
	 * Get a user’s suggested librarians.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id WP user ID.
	 *
	 * @return array            User resource IDs.
	 */
	public function get_librarians( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
		}

		return get_field( 'librarians', 'user_' . $user_id );
	}

	/**
	 * Get a user’s Primo favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $user_id WP user ID.
	 *
	 * @return array            User course IDs.
	 */
	public function get_primo_favorites( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user    = wp_get_current_user();
			$user_id = $user->ID;
		}

		return get_field( 'primo_favorites', 'user_' . $user_id );
	}

	/**
	 * Store some Google user data as user meta.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User                               $user            User object.
	 * @param GoogleGAL_Service_Oauth2_Userinfoplus $userinfo        USer data object.
	 * @param bool                                  $userdidnotexist Whether user existed in WP or not.
	 * @param GoogleGAL_Client                      $client          Google login client.
	 * @param GoogleGAL_Service                     $oauthservice    Google oAuth service.
	 *
	 * @return void
	 */
	public function store_user_meta( $user, $userinfo, $userdidnotexist, $client, $oauthservice ) {

		if ( $userdidnotexist ) {
			update_field( 'google_id', $userinfo->id, 'user_' . $user->ID );
			update_field( 'bridge_library_institution', $userinfo->hd, 'user_' . $user->ID );
			update_field( 'picture_url', $userinfo->picture, 'user_' . $user->ID );

			$alma = $this->get_alma_data( $user->ID );
			$this->set_alma_id( $user->ID, $alma['primary_id'] );
			$this->set_alternate_user_id( $user->ID, $alma );
			$this->set_expiration_date( $user->ID, $alma['expiry_date'] );

			if ( $userdidnotexist ) {
				update_field( 'bridge_library_uuid', $this->generate_uuid() );
			}

			$logging = Bridge_Library_Logging::get_instance();
			$logging->log(
				array(
					'ec' => 'web',
					'ea' => 'signup',
				),
				$user->ID
			);
		}

		$this->update_user_data( $user->username, $user->ID );
	}

	/**
	 * Generate a UUID.
	 *
	 * @since 1.0.0
	 *
	 * @return string UUID.
	 */
	public function generate_uuid() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low".
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			// 16 bits for "time_mid".
			wp_rand( 0, 0xffff ),
			// 16 bits for "time_hi_and_version"; four most significant bits holds version number 4.
			wp_rand( 0, 0x0fff ) | 0x4000,
			// 16 bits, 8 bits for "clk_seq_hi_res", 8 bits for "clk_seq_low", two most significant bits holds zero and one for variant DCE1.1.
			wp_rand( 0, 0x3fff ) | 0x8000,
			// 48 bits for "node".
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff )
		);
	}

	/**
	 * Update user data on login.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $user_login User login name.
	 * @param WP_User $user       User object.
	 *
	 * @return void
	 */
	public function update_user_data( $user_login, $user ) {

		$this->async->push_to_queue(
			array(
				'action'  => 'cache_user_courses',
				'user_id' => $user->ID,
			)
		);

		$this->async->push_to_queue(
			array(
				'action'  => 'cache_user_resources',
				'user_id' => $user->ID,
			)
		);

		$this->async->push_to_queue(
			array(
				'action'  => 'cache_suggested_librarians',
				'user_id' => $user->ID,
			)
		);

		$this->async->push_to_queue(
			array(
				'action'  => 'cache_user_primo_favorites',
				'user_id' => $user->ID,
			)
		);

		$this->async->push_to_queue(
			array(
				'action'  => 'cache_circulation_data',
				'user_id' => $user->ID,
			)
		);

		// Now start the queue.
		$this->async->save()->dispatch();
	}

	/**
	 * Create staff role.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function set_user_permissions() {

		// Add library staff role.
		add_role(
			'bridge_library_staff',
			'Bridge Library Staff',
			[
				'read'                          => true,

				'edit_others_courses'           => true,
				'edit_published_courses'        => true,
				'publish_courses'               => true,
				'delete_others_courses'         => false,
				'delete_published_courses'      => false,

				'edit_others_resources'         => true,
				'edit_published_resources'      => true,
				'publish_resources'             => true,
				'delete_others_resources'       => true,
				'delete_published_resources'    => true,

				'edit_others_librarians'        => true,
				'edit_published_librarians'     => true,
				'publish_librarians'            => true,
				'delete_others_librarians'      => true,
				'delete_published_librarians'   => true,

				'add_terms_bridge_library'      => true,
				'edit_terms_bridge_library'     => true,
				'manage_terms_bridge_library'   => true,

				'manage_options_bridge_library' => true,
			]
		);

		// Add permissions to admin role.
		$admin = get_role( 'administrator' );

		$admin->add_cap( 'edit_others_courses' );
		$admin->add_cap( 'edit_published_courses' );
		$admin->add_cap( 'publish_courses' );
		$admin->add_cap( 'delete_others_courses' );
		$admin->add_cap( 'delete_published_courses' );

		$admin->add_cap( 'edit_others_resources' );
		$admin->add_cap( 'edit_published_resources' );
		$admin->add_cap( 'publish_resources' );
		$admin->add_cap( 'delete_others_resources' );
		$admin->add_cap( 'delete_published_resources' );

		$admin->add_cap( 'edit_others_librarians' );
		$admin->add_cap( 'edit_published_librarians' );
		$admin->add_cap( 'publish_librarians' );
		$admin->add_cap( 'delete_others_librarians' );
		$admin->add_cap( 'delete_published_librarians' );

		$admin->add_cap( 'add_terms_bridge_library' );
		$admin->add_cap( 'edit_terms_bridge_library' );
		$admin->add_cap( 'delete_terms_bridge_library' );

		$admin->add_cap( 'manage_options_bridge_library' );
	}

	/**
	 * Removes Bridge Library Staff role on deactivation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function remove_user_permissions() {
		remove_role( 'bridge_library_staff' );
	}

	/**
	 * Map meta capabilities.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $caps    Array of the user's capabilities.
	 * @param string $cap     Capability name.
	 * @param int    $user_id User ID.
	 * @param array  $args    Adds context to the capability, typically the object ID.
	 *
	 * @return array          Array of the user’s capabilities.
	 */
	public function map_meta_caps( $caps, $cap, $user_id, $args ) {

		// If user is not staff, bail out.
		$user = get_user_by( 'id', $user_id );
		if ( false !== $user && empty( array_intersect( $user->roles, array( 'administrator', 'bridge_library_staff' ) ) ) ) {
			return $caps;
		}

		// Note: we could check institution tax terms here and set capabilities appropriately, but that will be slower than setting a tax_query to control visibility. See scope_staff_courses().
		$check_caps = array(
			'add_terms_bridge_library',
			'delete_terms_bridge_library',
			'edit_terms_bridge_library',
			'manage_terms_bridge_library',
		);
		foreach ( $this->post_types as $post_type ) {

			$check_caps = array_merge(
				$check_caps,
				array(
					'edit_' . $post_type,
					'edit_' . $post_type . 's',
					'delete_' . $post_type,
					'delete_' . $post_type . 's',
					'read_' . $post_type,
					'read_' . $post_type . 's',
				)
			);
		}

		/* If editing, deleting, or reading a $post_type, get the post and post type object. */
		if ( in_array( $cap, $check_caps, true ) ) {
			$caps = array();
			if ( ! empty( $args ) ) {
				$post = get_post( $args[0] );
				if ( ! empty( $post ) ) {
					$post_type_obj = get_post_type_object( $post->post_type );
				} else {
					$post_type_obj = get_post_type_object( rtrim( $post_type, 's' ) );
				}
			} else {
				$post_type_obj = get_post_type_object( rtrim( $post_type, 's' ) );
			}

			if ( "edit_$post_type" === $cap || "edit_$post_type" . 's' === $cap ) {
				if ( isset( $post ) && $user_id === $post->post_author ) {
					$caps[] = $post_type_obj->cap->edit_posts;
				} else {
					$caps[] = $post_type_obj->cap->edit_others_posts;
				}
			} elseif ( "delete_$post_type" === $cap || "delete_$post_type" . 's' === $cap ) {
				if ( isset( $post ) && $user_id === $post->post_author ) {
					$caps[] = $post_type_obj->cap->delete_posts;
				} else {
					$caps[] = $post_type_obj->cap->delete_others_posts;
				}
			} elseif ( "read_$post_type" === $cap || "read_$post_type" . 's' === $cap ) {
				if ( isset( $post ) && 'private' !== $post->post_status ) {
					$caps[] = 'read';
				} elseif ( isset( $post ) && $user_id === $post->post_author ) {
					$caps[] = 'read';
				} else {
					$caps[] = $post_type_obj->cap->read_private_posts;
				}
			}
		}

		/* Return the capabilities required by the user. */
		return $caps;
	}

	/**
	 * Determine if the logged-in user is a Bridge Library staff user or not.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether user is bridge library staff or not.
	 */
	public function is_staff_user() {
		$user = wp_get_current_user();
		return ( in_array( 'bridge_library_staff', (array) $user->roles, true ) );
	}

	/**
	 * Scope visible courses to the current user’s institution.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Query $query Query object.
	 *
	 * @return WP_Query       Query object.
	 */
	public function scope_staff_courses( $query ) {

		// Run only for specific post types.
		if ( ! in_array( $query->get( 'post_type' ), $this->post_types, true ) ) {
			return $query;
		}

		// Disable scoping for librarians CPT.
		if ( 'librarian' === $query->get( 'post_type' ) ) {
			return $query;
		}

		if ( $this->is_staff_user() ) {

			// Replace with term slug.
			$domain      = $this->get_domain();
			$institution = str_replace( array_flip( $this->institution_term_mapping ), $this->institution_term_mapping, $domain );

			$tax_query = array(
				array(
					'taxonomy' => 'institution',
					'field'    => 'slug',
					'terms'    => $institution,
				),
			);

			$query->set( 'tax_query', $tax_query );
		}

		return $query;
	}

	/**
	 * Add note to admin post count.
	 *
	 * @since 1.0.0
	 *
	 * @param array $views An array of available list table views.
	 *
	 * @return array       An array of available list table views.
	 */
	public function add_admin_views_note( $views ) {
		if ( $this->is_staff_user() ) {
			$views[] = 'Note: counts include all posts from both institutions, but you only see posts from your institution.';
		}

		return $views;
	}

	/**
	 * Get a user’s Alma data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 *
	 * @return array       Alma user data.
	 */
	public function get_alma_data( $user_id ) {
		$wp_user = get_user_by( 'ID', $user_id );
		$alma    = Bridge_Library_API_Alma::get_instance();
		$user    = $alma->get_user_by_email( $wp_user->user_email );

		return $user;
	}

	/**
	 * Set a user’s Alma primary ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 * @param int $alma_id Alma user ID.
	 *
	 * @return bool        Whether field was set or not.
	 */
	public function set_alma_id( $user_id, $alma_id ) {
		return update_field( 'alma_id', $alma_id, 'user_' . $user_id );
	}

	/**
	 * Set user’s alternate ID, used for retrieving Primo favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $user_id   WP user ID.
	 * @param array $user_data Alma data.
	 *
	 * @return bool            Whether the usermeta was updated or not.
	 */
	public function set_alternate_user_id( $user_id, $user_data ) {
		$alternate_id = '';

		foreach ( $user_data['user_identifier'] as $id ) {
			if ( 'OTHER_ID_1' === $id['id_type']['value'] ) {
				$alternate_id = $id['value'];
				break;
			}
		}

		if ( ! empty( $alternate_id ) ) {
			return update_field( 'alternate_id', $alternate_id, 'user_' . $user_id );
		}

		return false;
	}

	/**
	 * Set user expiration date.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $user_id         WP user ID.
	 * @param string $expiration_date Expiration date.
	 *
	 * @return bool                   Whether field was set or not.
	 */
	public function set_expiration_date( $user_id, $expiration_date ) {
		$date = strtotime( $expiration_date );
		return update_field( 'expiration_date', gmdate( 'Ymd', $date ), 'user_' . $user_id );
	}

	/**
	 * Add action buttons to user profile page.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $profileuser User object.
	 *
	 * @return void
	 */
	public function add_user_actions( $profileuser ) {
		wp_enqueue_script( 'bridge-library-admin' );
		?>
		<h2>User Actions</h2>
		<table class="form-table">
			<tbody>

				<tr id="cache-user-courses">
					<th>Retrieve and Store Alternate User ID</th>
					<td>
						<p><a class="button button-primary bridge-library-admin-ajax" target = "_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=cache_user_data&data[data]=alternate_user_id&data[user_id]=' . $profileuser->ID ), 'cache_user_data' ) ); ?>">Retrieve Alternate User ID</a></p>
						<p class="messages"></p>
					</td>
				</tr>

				<tr id="cache-user-courses">
					<th>Refresh Alma Course Data</th>
					<td>
						<p><a class="button button-primary bridge-library-admin-ajax" target = "_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=cache_user_data&data[data]=courses&data[user_id]=' . $profileuser->ID ), 'cache_user_data' ) ); ?>">Flush/Cache User Courses</a></p>
						<p>Run: <label><input type="radio" value="async" class="wait-for" name="wait_for_courses" checked="checked" />asynchronously</label> <label><input type="radio" value="sync" class="wait-for" name="wait_for_courses" />synchronously</label> </p>
						<p class="messages"></p>
					</td>
				</tr>

				<tr id="cache-user-resources">
					<th>Refresh Matched Resources</th>
					<td>
						<p><a class="button button-primary bridge-library-admin-ajax" target = "_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=cache_user_data&data[data]=resources&data[user_id]=' . $profileuser->ID ), 'cache_user_data' ) ); ?>">Flush/Cache User Resources</a></p>
						<p>Run: <label><input type="radio" value="async" class="wait-for" name="wait_for_resources" checked="checked" />asynchronously</label> <label><input type="radio" value="sync" class="wait-for" name="wait_for_resources" />synchronously</label> </p>
						<p class="messages"></p>
					</td>
				</tr>

				<tr id="cache-user-primo-favorites">
					<th>Refresh Primo User Favorites</th>
					<td>
						<p><a class="button button-primary bridge-library-admin-ajax" target = "_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=cache_user_data&data[data]=primo_favorites&data[user_id]=' . $profileuser->ID ), 'cache_user_data' ) ); ?>">Flush/Cache Primo Favorites</a></p>
						<p>Run: <label><input type="radio" value="async" class="wait-for" name="wait_for_primo_favorites" checked="checked" />asynchronously</label> <label><input type="radio" value="sync" class="wait-for" name="wait_for_primo_favorites" />synchronously</label> </p>
						<p class="messages"></p>
					</td>
				</tr>

				<tr id="cache-user-librarians">
					<th>Refresh Suggested Librarians</th>
					<td>
						<p><a class="button button-primary bridge-library-admin-ajax" target = "_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=cache_user_data&data[data]=librarians&data[user_id]=' . $profileuser->ID ), 'cache_user_data' ) ); ?>">Flush/Cache Suggested Librarians</a></p>
						<p>Run: <label><input type="radio" value="async" class="wait-for" name="wait_for_librarians" checked="checked" />asynchronously</label> <label><input type="radio" value="sync" class="wait-for" name="wait_for_librarians" />synchronously</label> </p>
						<p class="messages"></p>
					</td>
				</tr>

				<tr id="cache-user-circulation_info">
					<th>Refresh Circulation Information</th>
					<td>
						<p><a class="button button-primary bridge-library-admin-ajax" target = "_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-ajax.php?action=cache_user_data&data[data]=circulation_info&data[user_id]=' . $profileuser->ID ), 'cache_user_data' ) ); ?>">Flush/Cache Circulation Information</a></p>
						<p>Run: <label><input type="radio" value="async" class="wait-for" name="wait_for_circulation_info" checked="checked" />asynchronously</label> <label><input type="radio" value="sync" class="wait-for" name="wait_for_circulation_info" />synchronously</label> </p>
						<p class="messages"></p>
					</td>
				</tr>

			</tbody>
		</table>
		<?php
	}

	/**
	 * Ajax wrapper for caching functions.
	 *
	 * @since 1.0.0
	 *
	 * @return void Sends WP JSON error/success message.
	 */
	public function ajax_cache_user_data() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'cache_user_data' ) || ! isset( $_REQUEST['data']['user_id'] ) || ! isset( $_REQUEST['data']['data'] ) ) {
			wp_send_json_error( 'You’re not allowed to do that.', 401 );
			wp_die();
		}

		$user_id   = sanitize_key( $_REQUEST['data']['user_id'] );
		$data_type = sanitize_key( $_REQUEST['data']['data'] );

		if ( array_key_exists( 'async', $_REQUEST ) && isset( $_REQUEST['async'] ) ) {
			$method = sanitize_key( $_REQUEST['async'] );
		} else {
			$method = 'async';
		}

		// Whitelist the data types for security.
		switch ( $data_type ) {
			case 'courses':
				if ( 'async' === $method ) {
					$this->async->push_to_queue(
						array(
							'action'  => 'cache_user_courses',
							'user_id' => $user_id,
						)
					);
					$this->async->save()->dispatch();
					$results = 'Started background update.';
				} else {
					$results = $this->cache_user_courses( $user_id );
				}
				break;

			case 'resources':
				if ( 'async' === $method ) {
					$this->async->push_to_queue(
						array(
							'action'  => 'cache_user_resources',
							'user_id' => $user_id,
						)
					);
					$this->async->save()->dispatch();
					$results = 'Started background update.';
				} else {
					$results = $this->cache_user_resources( $user_id );
				}
				break;

			case 'librarians':
				if ( 'async' === $method ) {
					$this->async->push_to_queue(
						array(
							'action'  => 'cache_suggested_librarians',
							'user_id' => $user_id,
						)
					);
					$this->async->save()->dispatch();
					$results = 'Started background update.';
				} else {
					$results = $this->cache_suggested_librarians( $user_id );
				}
				break;

			case 'primo_favorites':
				if ( 'async' === $method ) {
					$this->async->push_to_queue(
						array(
							'action'  => 'cache_user_primo_favorites',
							'user_id' => $user_id,
						)
					);
					$this->async->save()->dispatch();
					$results = 'Started background update.';
				} else {
					$results = $this->cache_user_primo_favorites( $user_id );
				}
				break;

			case 'alternate_user_id':
				$user_data = $this->get_alma_data( $user_id );
				$results   = $this->set_alternate_user_id( $user_id, $user_data );
				break;

			case 'circulation_info':
				if ( 'async' === $method ) {
					$this->async->push_to_queue(
						array(
							'action'  => 'cache_circulation_data',
							'user_id' => $user_id,
						)
					);
					$this->async->save()->dispatch();
					$results = 'Started background update.';
				} else {
					$results = $this->cache_circulation_data( $user_id );
				}
				break;

			default:
				$results = new WP_Error( 405, 'Method not allowed' );
				break;
		}

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results );
		}

		wp_send_json_success( $results );
	}

	/**
	 * Retrieve user courses from Alma and cache to user meta.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 *
	 * @return array       Cached course and academic department IDs.
	 */
	public function cache_user_courses( $user_id ) {

		$wp_user = get_user_by( 'id', $user_id );

		$alma = Bridge_Library_API_Alma::get_instance();
		$user = $alma->get_user_by_email( $wp_user->user_email );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		/**
		 * Update expiry date.
		 */
		$this->set_expiration_date( $user_id, $user['expiry_date'] );

		/**
		 * Retrieve and cache courses.
		 */
		$courses              = Bridge_Library_Courses::get_instance();
		$course_codes         = array();
		$academic_departments = array();

		foreach ( $user['user_identifier'] as $key => $identifier ) {
			if ( 'Other' === $identifier['id_type']['value'] && false !== strpos( $identifier['value'], '|' ) ) {
				$course_codes[] = $identifier['value'];
			}
		}

		if ( ! empty( $course_codes ) ) {
			$course_ids = $courses->get_post_ids_by_course_codes( $course_codes );

			/**
			 * Compute and cache academic departments.
			 */
			foreach ( $course_ids as $id ) {
				$academic_departments = array_merge( $academic_departments, wp_list_pluck( get_the_terms( $id, 'academic_department' ), 'term_id' ) );
			}
		} else {
			// As a fallback, add empty arrays.
			$course_ids           = array();
			$academic_departments = array();
		}

		update_field( 'courses', $course_ids, 'user_' . $user_id );
		update_field( 'academic_departments', $academic_departments, 'user_' . $user_id );

		$this->update_cache_timestamp( 'courses', $user_id );

		do_action( 'bl_cache_data', 'courses', $user_id );

		return array(
			'course_ids'           => $course_ids,
			'academic_departments' => $academic_departments,
		);
	}

	/**
	 * Retrieve and cache available resource IDs for the given user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 *
	 * @return array       Cached resource IDs.
	 */
	public function cache_user_resources( $user_id ) {

		/**
		 * Get resource IDs from user courses.
		 */
		$resource_ids    = array();
		$user_course_ids = get_field( 'courses', 'user_' . $user_id );

		if ( ! empty( $user_course_ids ) ) {
			foreach ( $user_course_ids as $id ) {
				$resource_ids = array_merge( $resource_ids, (array) get_field( 'core_resources', $id ) );
				$resource_ids = array_merge( $resource_ids, (array) get_field( 'related_courses_resources', $id ) );
			}
		}

		/**
		 * Get resource IDs for general academic departments.
		 */
		$department_args = array(
			'post_type'      => 'resource',
			'posts_per_page' => -1,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'academic_department',
					'field'    => 'term_id',
					'operator' => 'IN',
					'terms'    => get_field( 'academic_departments', 'user_' . $user_id ),
				),
			),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		$department_resources = new WP_Query( $department_args );

		if ( $department_resources->have_posts() ) {
			$resource_ids = array_merge( $resource_ids, $department_resources->posts );
		}

		wp_reset_postdata();

		update_field( 'resources', $resource_ids, 'user_' . $user_id );

		$this->update_cache_timestamp( 'resources', $user_id );

		do_action( 'bl_cache_data', 'resources', $user_id );

		return array(
			'resource_ids' => $resource_ids,
		);
	}

	/**
	 * Retrieve and cache suggested librarians for the given user.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 *
	 * @return array       Cached resource IDs.
	 */
	public function cache_suggested_librarians( $user_id ) {

		/**
		 * Get librarian IDs for general academic departments.
		 */
		$librarian_args = array(
			'post_type'      => 'librarian',
			'posts_per_page' => -1,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'academic_department',
					'field'    => 'term_id',
					'operator' => 'IN',
					'terms'    => get_field( 'academic_departments', 'user_' . $user_id ),
				),
			),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		);

		$suggested_librarians = new WP_Query( $librarian_args );

		if ( $suggested_librarians->have_posts() ) {
			$librarian_ids = $suggested_librarians->posts;
		} else {
			$librarian_ids = array();
		}

		wp_reset_postdata();

		update_field( 'librarians', $librarian_ids, 'user_' . $user_id );

		$this->update_cache_timestamp( 'librarians', $user_id );

		do_action( 'bl_cache_data', 'librarians', $user_id );

		return array(
			'librarian_ids' => $librarian_ids,
		);
	}

	/**
	 * Retrieve and cache Primo user favorites.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 *
	 * @return array       Cached Primo Favorite IDs.
	 */
	public function cache_user_primo_favorites( $user_id ) {
		$primo     = Bridge_Library_API_Primo::get_instance();
		$favorites = $primo->get_user_favorites( $user_id );

		$this->update_cache_timestamp( 'primo_favorites', $user_id );

		do_action( 'bl_cache_data', 'primo_favorites', $user_id );

		return $favorites;
	}

	/**
	 * Retrieve and cache circulation data.
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id WP user ID.
	 *
	 * @return array       Circulation data.
	 */
	public function cache_circulation_data( $user_id ) {
		$alma_id  = get_field( 'alma_id', 'user_' . $user_id );
		$alma     = Bridge_Library_API_Alma::get_instance();
		$loans    = $alma->get_loans( $alma_id );
		$requests = $alma->get_requests( $alma_id );
		$fees     = $alma->get_fees( $alma_id );
		$data     = array();

		if ( ! is_a( $loans, 'WP_Error' ) ) {
			$data['loans']       = $loans['item_loan'];
			$data['loans_count'] = $loans['total_record_count'];
		}

		if ( ! is_a( $requests, 'WP_Error' ) ) {
			$data['requests']       = $requests['user_request'];
			$data['requests_count'] = $loans['total_record_count'];
		}

		if ( ! is_a( $fees, 'WP_Error' ) ) {
			$data['fees']       = $fees['fee'];
			$data['fees_count'] = $fees['total_record_count'];
		}

        $cleandata = str_replace('\\', '\\\\', json_encode($data, true));

		update_field( 'circulation_data', $cleandata, 'user_' . $user_id );

		$this->update_cache_timestamp( 'circulation_data', $user_id );

		do_action( 'bl_cache_data', 'circulation_data', $user_id );

		$response = 'Retrieved ' . $data['loans_count'] . ' loans, ' . $data['requests_count'] . ' requests, and ' . $data['fees_count'] . ' fees.';


		return $response;
	}


	/**
	 * Update the user’s cache timestamp.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type    Type of resource.
	 * @param int    $user_id WP user ID.
	 *
	 * @return bool           Whether field was updated or not.
	 */
	public function update_cache_timestamp( $type, $user_id ) {
		return update_field( $type . '_cache_updated', time(), 'user_' . $user_id );
	}

	/**
	 * Register a personal data exporter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $exporters Registered exporters.
	 *
	 * @return array           Registered exporters.
	 */
	public function register_exporter( $exporters ) {
		$exporters['bridge-library'] = array(
			'exporter_friendly_name' => __( 'Bridge Library Plugin', 'bridge-library' ),
			'callback'               => array( $this, 'export_personal_data' ),
		);

		return $exporters;
	}

	/**
	 * Add personal data to export filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email_address User’s email address.
	 * @param int    $page          Page.
	 *
	 * @return array                Personal data.
	 */
	public function export_personal_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );

		$courses     = get_field( 'courses', 'user_' . $user->ID );
		$course_api  = Bridge_Library_Courses::get_instance();
		$course_data = $course_api->get_post_data_by_post_ids( $courses );

		return array(
			'data' => array(
				array(
					'group_id'    => 'bridge_library_user',
					'group_label' => 'Bridge Library User Data',
					'item_id'     => 'user-' . $user->ID,
					'data'        => array(
						array(
							'name'  => __( 'Bridge Library Institution', 'bridge-library' ),
							'value' => get_user_meta( $user->ID, 'bridge_library_institution', true ),
						),
						array(
							'name'  => __( 'Alma ID', 'bridge-library' ),
							'value' => get_user_meta( $user->ID, 'alma_id', true ),
						),
						array(
							'name'  => __( 'Google User ID', 'bridge-library' ),
							'value' => get_user_meta( $user->ID, 'google_id', true ),
						),
						array(
							'name'  => __( 'Google Photo URL', 'bridge-library' ),
							'value' => get_user_meta( $user->ID, 'picture_url', true ),
						),
						array(
							'name'  => __( 'Anonymous UUID', 'bridge-library' ),
							'value' => get_user_meta( $user->ID, 'bridge_library_uuid', true ),
						),
						array(
							'name'  => __( 'Enrolled Course Codes', 'bridge-library' ),
							'value' => implode( ', ', wp_list_pluck( $course_data, 'course_code' ) ),
						),
					),
				),
			),
			'done' => true,
		);
	}

	/**
	 * Register a personal data eraser.
	 *
	 * @since 1.0.0
	 *
	 * @param array $erasers Registered erasers.
	 *
	 * @return array         Registered erasers.
	 */
	public function register_eraser( $erasers ) {
		$erasers['bridge-library'] = array(
			'eraser_friendly_name' => __( 'Bridge Library Plugin', 'bridge-library' ),
			'callback'             => array( $this, 'erase_personal_data' ),
		);

		return $erasers;
	}

	/**
	 * Add personal data to export filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email_address User’s email address.
	 * @param int    $page          Page.
	 *
	 * @return array                Personal data.
	 */
	public function erase_personal_data( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );

		// Set defaults.
		$items_removed  = true;
		$items_retained = false;
		$messages       = array();

		if ( get_user_meta( $user->ID, 'bridge_library_institution', true ) && ! delete_user_meta( $user->ID, 'bridge_library_institution' ) ) {
			$items_removed  = false;
			$items_retained = true;
			$messages[]     = 'Failed to delete bridge_library_institution user meta.';
		}

		if ( get_user_meta( $user->ID, 'alma_id', true ) && ! delete_user_meta( $user->ID, 'alma_id' ) ) {
			$items_removed  = false;
			$items_retained = true;
			$messages[]     = 'Failed to delete alma_id user meta.';
		}

		if ( get_user_meta( $user->ID, 'google_id', true ) && ! delete_user_meta( $user->ID, 'google_id' ) ) {
			$items_removed  = false;
			$items_retained = true;
			$messages[]     = 'Failed to delete google_id user meta.';
		}

		if ( get_user_meta( $user->ID, 'picture_url', true ) && ! delete_user_meta( $user->ID, 'picture_url' ) ) {
			$items_removed  = false;
			$items_retained = true;
			$messages[]     = 'Failed to delete picture_url user meta.';
		}

		if ( get_user_meta( $user->ID, 'bridge_library_uuid', true ) && ! delete_user_meta( $user->ID, 'bridge_library_uuid' ) ) {
			$items_removed  = false;
			$items_retained = true;
			$messages[]     = 'Failed to delete bridge_library_uuid user meta.';
		}

		return array(
			'items_removed'  => $items_removed,
			'items_retained' => $items_retained,
			'messages'       => $messages,
			'done'           => true,
		);
	}

	/**
	 * Automatically clean up user accounts.
	 *
	 * @since 1.0.0
	 * @since 1.0.1 Fall back to wp_postmeta.
	 *
	 * @return void
	 */
	public function clean_up_users() {
		global $wpdb;

		$query     = $wpdb->prepare(
			"SELECT user_id FROM {$wpdb->prefix}{$this->acf_meta_table} WHERE expiration_date IS NOT NULL AND expiration_date < %d;", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- there’s currently no way to escape a table name.
			gmdate( 'Ymd', strtotime( '3 months ago' ) )
		);
		$old_users = $wpdb->get_col( $query, 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- this is more performant than a get_users with meta_query would be, and query is prepared above.

		// If empty, try wp_usermeta.
		if ( empty( $old_users ) ) {
			$query     = $wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'expiration_date' AND meta_value IS NOT NULL AND meta_value < %d;",
				gmdate( 'Ymd', strtotime( '3 months ago' ) )
			);
			$old_users = $wpdb->get_col( $query, 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		}

		foreach ( $old_users as $old_user ) {
			$user = get_user_by( 'id', $old_user );

			// Don’t delete admins or staff.
			if ( empty( array_intersect( (array) $user->roles, array( 'administrator', 'bridge_library_staff' ) ) ) ) {
				wp_delete_user( $old_user );
			}
		}
	}

	/**
	 * Add action buttons to user profile page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function admin_clean_up_users() {
		wp_enqueue_script( 'bridge-library-admin' );
		?>
		<form class="bridge-library-admin-ajax">
			<table class="form-table">
				<tr>
					<th>Clean Up Old Users</th>
					<td>
						<p>Use this button to manually delete all users with expiration dates over 3 months ago.</p>

						<p class="messages"></p>

						<input type="hidden" name="action" value="clean_up_users" />
						<?php wp_nonce_field( 'clean_up_users' ); ?>

						<p><input type="submit" class="button button-primary" value="Clean Up Users" /></p>
					</td>
				</tr>
			</table>
		</form>
		<hr />
		<?php
	}

	/**
	 * Ajax kickoff user cleanup.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ajax_clean_up_users() {
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'clean_up_users' ) ) {
			wp_send_json_error( 'You’re not allowed to do that.', 401 );
			wp_die();
		}

		$clean_up = $this->clean_up_users();

		wp_send_json_success( 'Started user cleanup.' );
	}

	/**
	 * Treate user favorite posts as sticky.
	 *
	 * @param bool|mixed $pre_option The value to return instead of the option value. This differs from
	 *                               `$default`, which is used as the fallback value in the event the option
	 *                               doesn't exist elsewhere in get_option(). Default false (to skip past the
	 *                               short-circuit).
	 * @param string     $option     Option name.
	 * @param mixed      $default    The fallback value to return if the option does not exist. Default is false.
	 *
	 * @return bool|mixed            The value to return.
	 */
	public function get_user_sticky_posts( $pre_option, $option, $default ) {
		return $this->get_favorite_posts();
	}

	/**
	 * Load user’s favorite posts.
	 *
	 * @since 1.0.0.0
	 *
	 * @param mixed $value   Field value.
	 * @param int   $post_id Post ID.
	 * @param array $field   Field object.
	 *
	 * @return mixed         Field value.
	 */
	public function acf_load_user_sticky_posts_meta( $value, $post_id, $field ) {
		$favorites = (array) $this->get_favorite_posts();
		return in_array( (int) $post_id, $favorites, true );
	}

	/**
	 * Save user’s favorite posts.
	 *
	 * @since 1.0.0.0
	 *
	 * @param mixed $value   Field value.
	 * @param int   $post_id Post ID.
	 * @param array $field   Field object.
	 *
	 * @return mixed         Field value.
	 */
	public function acf_update_user_sticky_posts_meta( $value, $post_id, $field ) {
		$this->save_favorite_posts( $post_id, (bool) $value );

		// Don’t save any postmeta.
		return false;
	}

	/**
	 * Save favorite post IDs to user’s profile.
	 *
	 * @since 1.0.0.0
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $value   Checkbox value.
	 *
	 * @return bool         Whether user profile was updated.
	 */
	public function save_favorite_posts( $post_id, $value ) {
		$user_id = get_current_user_id();

		$favorites = (array) $this->get_favorite_posts( $user_id );

		if ( $value ) {
			$favorites = array_merge( $favorites, array( (int) $post_id ) );
		} else {
			$key = array_search( $post_id, $favorites, true );
			if ( false !== $key ) {
				unset( $favorites[ $key ] );
			}
		}

		return update_field( 'user_favorites', $favorites, 'user_' . $user_id );
	}

	/**
	 * Retrieve favorite posts for the given user.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $user_id Optional user ID. Defaults to logged-in user.
	 *
	 * @return array|null    User favorite posts.
	 */
	public function get_favorite_posts( $user_id = 0 ) {
		if ( 0 === $user_id ) {
			$user_id = get_current_user_id();
		}

		return get_field( 'user_favorites', 'user_' . $user_id );
	}

	/**
	 * Add user favorites to admin list table.
	 *
	 * @since 1.0.0.0
	 *
	 * @param string[] $views An array of available list table views.
	 *
	 * @return string[] An array of available list table views.
	 */
	public function course_views( $views ) {
		$class = '';
		if ( isset( $_GET['user_favorite'] ) && 'true' === sanitize_key( $_GET['user_favorite'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$class = 'current';
		}

		$views['favorites'] = sprintf( '<a href="edit.php?post_type=course&user_favorite=true" class="%s">My Favorites</a>', $class );
		return $views;
	}

	/**
	 * Add user favorites to admin list table.
	 *
	 * @since 1.0.0.0
	 *
	 * @param string[] $views An array of available list table views.
	 *
	 * @return string[] An array of available list table views.
	 */
	public function resource_views( $views ) {
		$class = '';
		if ( isset( $_GET['user_favorite'] ) && 'true' === sanitize_key( $_GET['user_favorite'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$class = 'current';
		}

		$views['favorites'] = sprintf( '<a href="edit.php?post_type=resource&user_favorite=true" class="%s">My Favorites</a>', $class );
		return $views;
	}

	/**
	 * Limit returned results to user favorites.
	 *
	 * @since 1.0.0.0
	 *
	 * @param WP_Query $query Query object.
	 *
	 * @return WP_Query       Query object.
	 */
	public function limit_to_user_favorites( $query ) {
		if ( is_admin() && $query->is_main_query() && ! wp_doing_ajax() ) {
			if ( isset( $_GET['user_favorite'] ) && 'true' === sanitize_key( $_GET['user_favorite'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$query->set( 'post__in', $this->get_favorite_posts() );
			}
		}

		return $query;
	}

	/**
	 * Put user favorites at top of ACF field.
	 *
	 * @since 1.0.0.0
	 *
	 * @param array $args    WP_Query args.
	 * @param array $field   Field object.
	 * @param int   $post_id Post ID.
	 *
	 * @return array          WP_Query args.
	 */
	public function acf_sort_user_sticky_posts( $args, $field, $post_id ) {
		// If this is the first page, include sticky posts and sort them to the top.
		if ( array_key_exists( 'paged', $args ) && 1 === (int) $args['paged'] ) {

			// Run a query to get the post IDs, then merge in user favorite IDs.
			$args2            = $args;
			$args2['fields']  = 'ids';
			$orig_query       = get_posts( $args2 );
			$args['post__in'] = array_merge( $this->get_favorite_posts(), $orig_query );
			$args['orderby']  = 'post__in';

			// Modify displayed results.
			add_filter( 'acf/fields/relationship/result', array( $this, 'acf_sticky_post_title' ), 10, 4 );
		}

		return $args;
	}

	/**
	 * Add note to ACF user-favorited related course/resource titles.
	 *
	 * @since 1.0.0.0
	 *
	 * @param string  $title   Post title.
	 * @param WP_Post $post    Post object.
	 * @param array   $field   Field object.
	 * @param int     $post_id The current post ID.
	 *
	 * @return string          Post title.
	 */
	public function acf_sticky_post_title( $title, $post, $field, $post_id ) {
		if ( in_array( $post->ID, $this->get_favorite_posts(), true ) ) {
			$title = '<strong>' . $title . '</strong>  — Favorite';
		}

		return $title;
	}

	/**
	 * Disable some ACF fields for non-admins.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_User $user User being edited.
	 *
	 * @return void
	 */
	public function maybe_load_admin_js( $user ) {
		require_once ABSPATH . '/wp-includes/pluggable.php';
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_enqueue_script( 'bridge-library-admin' );
			wp_add_inline_script( 'bridge-library-admin', 'var disabledAcfFields = ' . wp_json_encode( $this->read_only_fields ) . ';', 'before' );
		}
	}

	/**
	 * Force circulation_data to be returned as a string.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value ACF field value.
	 *
	 * @return string      ACF field value.
	 */
	public function force_circulation_data_string( $value ) {
		if ( is_array( $value ) ) {
			$value = wp_json_encode( $value );
		}

		return $value;
	}

	/**
	 * Add user’s institution to body class.
	 *
	 * @since 1.0.0
	 *
	 * @param array $classes Body classes.
	 *
	 * @return array         Body classes.
	 */
	public function body_class( $classes ) {
		$domain = $this->get_domain();

		$classes[] = 'institution-' . str_replace( '.edu', '', $domain );

		return $classes;
	}
}
