<?php
/**
 * Bridge Library admin class.
 *
 * @package bridge-library
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bridge Library admin class.
 *
 * @since 1.0.0
 */
class Bridge_Library_Admin {

	/**
	 * Class instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Return only one instance of this class.
	 *
	 * @return Bridge_Library_Admin class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new Bridge_Library_Admin();
		}

		return self::$instance;
	}

	/**
	 * Load actions, hooks, other classes.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Add menu pages.
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );

		// Register assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register admin assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_script( 'bridge-library-admin', BL_PLUGIN_DIR_URL . 'assets/js/admin.js', array( 'jquery' ), BL_PLUGIN_VERSION, true );

		wp_register_script( 'select2-bridge-library', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', array( 'jquery' ), 4, true );
		wp_register_style( 'select2-bridge-library', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css', array(), 4 );
	}

	/**
	 * Add admin menu pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_menu_pages() {

		// Add top-level page.
		add_menu_page(
			__( 'Bridge Library Tools', 'bridge-library' ),
			__( 'Bridge Library Tools', 'bridge-library' ),
			'manage_options_bridge_library',
			'bridge_library_settings',
			array( $this, 'top_settings_page' ),
			'dashicons-admin-generic'
		);

		// Add LibGuides import page.
		add_submenu_page(
			'bridge_library_settings',
			__( 'Import LibGuides Assets to Course', 'bridge-library' ),
			__( 'Import LibGuides Assets to Course', 'bridge-library' ),
			'manage_options_bridge_library',
			'bridge_library_import_libguides',
			array( $this, 'import_libguides_page' ),
			2
		);

		// Add Copy Resources page.
		add_submenu_page(
			'bridge_library_settings',
			__( 'Copy Resources to Another Course', 'bridge-library' ),
			__( 'Copy Resources to Another Course', 'bridge-library' ),
			'manage_options_bridge_library',
			'bridge_library_copy_resources',
			array( $this, 'copy_resources_page' ),
			2
		);

		// Add default resources.
		acf_add_options_page(
			array(
				'page_title'  => 'Default Resources',
				'capability'  => 'manage_options_bridge_library',
				'parent_slug' => 'bridge_library_settings',
			)
		);

		// Add ACF site options page.
		acf_add_options_page(
			array(
				'page_title'  => 'Site Options',
				'capability'  => 'manage_options_bridge_library',
				'parent_slug' => 'bridge_library_settings',
			)
		);
	}

	/**
	 * Display top-level settings page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function top_settings_page() {

		// Verify permissions.
		if ( ! current_user_can( 'manage_options_bridge_library' ) ) {
			echo '<p>' . __( 'Sorry, you’re not allowed to do that.', 'bridge-library' ) . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die();
		}

		wp_enqueue_script( 'bridge-library-admin' );

		echo '<div class="wrap">';
		echo '<h2>' . __( 'Bridge Library Tools', 'bridge-library' ) . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		/**
		 * Run actions.
		 */
		do_action( 'bridge_library_admin_settings' );

		echo '</div>';
	}

	/**
	 * Display settings page with LibGuides import utility.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function import_libguides_page() {

		// Verify permissions.
		if ( ! current_user_can( 'manage_options_bridge_library' ) ) {
			echo '<p>' . esc_html__( 'Sorry, you’re not allowed to do that.', 'bridge-library' ) . '</p>';
			wp_die();
		}

		wp_enqueue_script( 'bridge-library-admin' );
		?>
		<div class="wrap">
		<h2><?php esc_html_e( 'Import LibGuides Assets to Course', 'bridge-library' ); ?></h2>

		<p>Use this utility to import all the resources from the specified LibGuide guide and attach them to the course.</p>

		<?php
		if ( ! array_key_exists( 'course_id', $_GET ) || ! array_key_exists( 'nonce', $_GET ) || ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'import_libguides' ) ) {
			echo '<p>' . esc_html__( 'Please go to a specific course and click the button to start this process.', 'bridge-library' ) . '</p>';
		} else {
			$course = get_post( absint( $_GET['course_id'] ) );
			?>

			<form class="bridge-library-admin-ajax">
				<table class="form-table">
					<tr>
						<th>Import LibGuides Assets to Course</th>
						<td>
							<input type="hidden" name="action" value="import_libguides_to_course" />
							<?php wp_nonce_field( 'import_libguides_to_course', 'import_libguides_to_course_nonce' ); ?>

							<p class="messages"></p>

							<p>Course: <?php echo get_the_title( $course ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
							<input type="hidden" name="post_id" value="<?php echo esc_attr( $course->ID ); ?>" />

							<p><label for="libguides_guide_id">LibGuides Guide ID: <input type="text" name="libguides_guide_id" placeholder="14174" /></label></p>

							<p><input type="submit" class="button button-primary" value="Import All Assets" /></p>
						</td>
					</tr>
				</table>
			</form>

			<?php
		}
		?>

		</div>
		<?php
	}

	/**
	 * Display settings page with Copy Resources utility.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function copy_resources_page() {

		// Verify permissions.
		if ( ! current_user_can( 'manage_options_bridge_library' ) ) {
			echo '<p>' . esc_html__( 'Sorry, you’re not allowed to do that.', 'bridge-library' ) . '</p>';
			wp_die();
		}

		wp_enqueue_script( 'bridge-library-admin' );
		?>
		<div class="wrap">
		<h2><?php esc_html_e( 'Copy Resources to Another Course', 'bridge-library' ); ?></h2>

		<p><?php esc_html_e( 'Use this utility to import all the resources from the specified LibGuide guide and attach them to the course.', 'bridge-library' ); ?></p>

		<?php
		if ( ! array_key_exists( 'course_id', $_GET ) || ! array_key_exists( 'nonce', $_GET ) || ! wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'copy_resources' ) ) {
			echo '<p>' . esc_html__( 'Please go to a specific course and click the button to start this process.', 'bridge-library' ) . '</p>';
		} else {
			$course = get_post( absint( $_GET['course_id'] ) );

			// Add course code to title.
			$courses_class = Bridge_Library_Courses::get_instance();
			add_filter( 'list_pages', array( $courses_class, 'modify_course_acf_titles' ), 10, 2 );

			$dropdown_args = array(
				'post_type' => 'course',
				'name'      => 'destination_id',
			);

			wp_enqueue_style( 'select2-bridge-library' );
			wp_enqueue_script( 'select2-bridge-library' );
			wp_add_inline_script( 'select2-bridge-library', 'jQuery(document).ready(function() {jQuery("select[name=destination_id]").select2();});' );
			?>

			<form class="bridge-library-admin-ajax">
				<table class="form-table">
					<tr>
						<th><?php esc_html_e( 'Copy Resources to Another Course', 'bridge-library' ); ?></th>
						<td>
							<input type="hidden" name="action" value="copy_resources_to_course" />
							<?php wp_nonce_field( 'copy_resources_to_course', 'copy_resources_to_course_nonce' ); ?>

							<p class="messages"></p>

							<p><?php esc_html_e( 'Copy from Course:', 'bridge-library' ); ?> <?php echo get_the_title( $course ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
							<input type="hidden" name="source_id" value="<?php echo esc_attr( $course->ID ); ?>" />

							<p><label for="destination_id"><?php esc_html_e( 'Copy to Course:', 'bridge-library' ); ?> <?php wp_dropdown_pages( $dropdown_args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label></p>

							<p>
								<label for="all_resources"><input type="radio" name="which_resources" id="all_resources" value="all_resources" checked="checked"> <?php esc_html_e( 'All Resources', 'bridge-library' ); ?></label>
								<label for="core_resources"><input type="radio" name="which_resources" id="core_resources" value="core_resources"> <?php esc_html_e( 'Core Resources Only', 'bridge-library' ); ?></label>
								<label for="other_resources"><input type="radio" name="which_resources" id="other_resources" value="other_resources"> <?php esc_html_e( 'Other Resources Only', 'bridge-library' ); ?></label>
							</p>

							<p><input type="submit" class="button button-primary" value="Copy Resources" /></p>
						</td>
					</tr>
				</table>
			</form>

			<?php
		}
		?>

		</div>
		<?php

		remove_filter( 'list_pages', array( $courses_class, 'modify_course_acf_titles' ) );
	}
}
