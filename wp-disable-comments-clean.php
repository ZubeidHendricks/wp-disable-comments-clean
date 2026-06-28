<?php
/**
 * Plugin Name:       Disable Comments Clean
 * Plugin URI:        https://zubeidhendricks.dev/wp-plugins/disable-comments-clean
 * Description:        Completely disable comments and pingbacks site-wide, and remove every trace of them from the admin — no settings marathon required.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * Author:            Zubeid Hendricks
 * Author URI:        https://zubeidhendricks.dev
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       disable-comments-clean
 *
 * @package DisableCommentsClean
 */

defined( 'ABSPATH' ) || exit;

define( 'DISABLE_COMMENTS_CLEAN_VERSION', '1.0.0' );

require_once __DIR__ . '/includes/factory-core.php';

/**
 * Disable Comments Clean.
 */
final class DisableCommentsClean extends ZubFactory_Plugin {

	protected function configure() {
		$this->slug    = 'disable-comments-clean';
		$this->title   = 'Disable Comments';
		$this->version = DISABLE_COMMENTS_CLEAN_VERSION;
	}

	protected function settings_fields() {
		return array(
			'scope'        => array(
				'label'   => __( 'Disable on', 'disable-comments-clean' ),
				'type'    => 'select',
				'options' => array(
					'everywhere' => __( 'Everywhere', 'disable-comments-clean' ),
					'posts'      => __( 'Posts only', 'disable-comments-clean' ),
					'pages'      => __( 'Pages only', 'disable-comments-clean' ),
				),
				'default' => 'everywhere',
			),
			'hide_existing' => array(
				'label'    => __( 'Existing comments', 'disable-comments-clean' ),
				'type'     => 'checkbox',
				'cb_label' => __( 'Hide existing comments from the front end', 'disable-comments-clean' ),
				'default'  => 1,
			),
			'remove_menu'   => array(
				'label'    => __( 'Admin menu', 'disable-comments-clean' ),
				'type'     => 'checkbox',
				'cb_label' => __( 'Remove the Comments menu and toolbar item', 'disable-comments-clean' ),
				'default'  => 1,
			),
		);
	}

	protected function hooks() {
		add_action( 'init', array( $this, 'close_post_types' ), 100 );
		add_filter( 'comments_open', array( $this, 'filter_open' ), 20, 2 );
		add_filter( 'pings_open', array( $this, 'filter_open' ), 20, 2 );

		if ( $this->option( 'hide_existing', 1 ) ) {
			add_filter( 'comments_array', '__return_empty_array', 20 );
			add_filter( 'get_comments_number', '__return_zero', 20 );
		}

		if ( $this->option( 'remove_menu', 1 ) ) {
			add_action( 'admin_menu', array( $this, 'remove_menu' ) );
			add_action( 'wp_before_admin_bar_render', array( $this, 'remove_toolbar' ) );
			add_action( 'admin_init', array( $this, 'redirect_comment_pages' ) );
			add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widget' ) );
		}
	}

	/** Which post types are in scope? */
	private function in_scope( $post_type ) {
		switch ( $this->option( 'scope', 'everywhere' ) ) {
			case 'posts':
				return 'post' === $post_type;
			case 'pages':
				return 'page' === $post_type;
			default:
				return true;
		}
	}

	/** Remove comment support from in-scope post types. */
	public function close_post_types() {
		foreach ( get_post_types() as $type ) {
			if ( $this->in_scope( $type ) && post_type_supports( $type, 'comments' ) ) {
				remove_post_type_support( $type, 'comments' );
				remove_post_type_support( $type, 'trackbacks' );
			}
		}
	}

	/** Force comments/pings closed for in-scope content. */
	public function filter_open( $open, $post_id ) {
		$post = get_post( $post_id );
		if ( $post && $this->in_scope( $post->post_type ) ) {
			return false;
		}
		return $open;
	}

	public function remove_menu() {
		remove_menu_page( 'edit-comments.php' );
	}

	public function remove_toolbar() {
		if ( is_admin_bar_showing() ) {
			$GLOBALS['wp_admin_bar']->remove_node( 'comments' );
		}
	}

	public function remove_dashboard_widget() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	/** Bounce anyone who lands on the comments admin screen. */
	public function redirect_comment_pages() {
		global $pagenow;
		if ( 'edit-comments.php' === $pagenow ) {
			wp_safe_redirect( admin_url() );
			exit;
		}
	}
}

add_action(
	'plugins_loaded',
	function () {
		( new DisableCommentsClean( __FILE__ ) )->boot();
	}
);
