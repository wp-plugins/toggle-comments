<?php

/**
 * Plugin Name: Global Toggle Comments
 * Plugin URI: http://www.jackreichert.com
 * Description: Turn WordPress comment functionality on or off.
 * Version: 0.1
 * Author: Jack Reichert
 * Author URI: http://www.jackreichert.com/
 * License: GPL2
 */

class Toggle_Comments {
	function __construct() {
		add_action( "admin_menu", array( $this, "setup_admin_menu" ) );
		add_action( 'admin_init', array( $this, 'settings_init' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );

		$options = get_option( 'tc_settings' );

		// Disable support for comments and trackbacks in post types
		if ( isset( $options['tc_disable_comment_trackbacks'] ) && $options['tc_disable_comment_trackbacks'] ) {
			add_action( 'admin_init', array( $this, 'tc_disable_comments_post_types_support' ) );
		}

		// Close comments on the front-end
		if ( isset( $options['tc_close_comments'] ) && $options['tc_close_comments'] ) {
			add_filter( 'comments_open', array( $this, 'tc_disable_comments_status' ), 20, 2 );
			add_filter( 'pings_open', array( $this, 'tc_disable_comments_status' ), 20, 2 );
		}

		// Hide existing comments
		if ( isset( $options['tc_hide_existing'] ) && $options['tc_hide_existing'] ) {
			add_filter( 'comments_array', array( $this, 'tc_disable_comments_hide_existing_comments' ), 10, 2 );
		}

		// Remove comments page in menu
		if ( isset( $options['tc_remove_menu'] ) && $options['tc_remove_menu'] ) {
			add_action( 'admin_menu', array( $this, 'tc_disable_comments_admin_menu' ) );
		}

		// Redirect any user trying to access comments page
		if ( isset( $options['tc_redirect_from_menu'] ) && $options['tc_redirect_from_menu'] ) {
			add_action( 'admin_init', array( $this, 'tc_disable_comments_admin_menu_redirect' ) );
		}

		// Remove comments metabox from dashboard
		if ( isset( $options['tc_remove_dashboard'] ) && $options['tc_remove_dashboard'] ) {
			add_action( 'admin_init', array( $this, 'tc_disable_comments_dashboard' ) );
		}

		// Remove comments links from admin bar
		if ( isset( $options['tc_remove_links'] ) && $options['tc_remove_links'] ) {
            add_action( 'admin_bar_menu', array( $this, 'tc_disable_comments_admin_bar'), 999 );
		}

	}

	public function enqueue( $hook ) {
		if ( 'settings_page_toggle-comments' != $hook ) {
			return;
		}

		wp_enqueue_style( 'bootstrap_css', plugins_url( 'toggle-comments.css', __FILE__ ) );
		wp_enqueue_style( 'bootstrap_toggle_css', '//gitcdn.github.io/bootstrap-toggle/2.2.0/css/bootstrap-toggle.min.css', array( 'bootstrap_css' ) );
		wp_enqueue_script( 'bootstrap_toggle_js', '//gitcdn.github.io/bootstrap-toggle/2.2.0/js/bootstrap-toggle.min.js' );
	}

	public function setup_admin_menu() {
		add_submenu_page( 'options-general.php',
			'Toggle Comments', 'Toggle Comments', 'manage_options',
			'toggle-comments', array( $this, 'toggle_comments_settings' ) );
	}

	public function settings_init() {
		register_setting( 'toggleCommentsPage', 'tc_settings' );
		add_settings_section(
			'tc_toggleCommentsPage_section',
			__( 'Toggle comment functionality on your site', 'wordpress' ),
			array( $this, 'tc_settings_section_callback' ),
			'toggleCommentsPage'
		);

		// Disable support for comments and trackbacks in post types
		add_settings_field(
			'tc_disable_comment_trackbacks',
			__( 'Disable support for comments and trackbacks in post types', 'wordpress' ),
			array( $this, 'tc_disable_comment_trackbacks_render' ),
			'toggleCommentsPage',
			'tc_toggleCommentsPage_section'
		);

		// Close comments on the front-end
		add_settings_field(
			'tc_close_comments',
			__( 'Close comments on the front-end', 'wordpress' ),
			array( $this, 'tc_close_comments_render' ),
			'toggleCommentsPage',
			'tc_toggleCommentsPage_section'
		);

		// Hide existing comments
		add_settings_field(
			'tc_hide_existing',
			__( 'Hide existing comments', 'wordpress' ),
			array( $this, 'tc_hide_existing_render' ),
			'toggleCommentsPage',
			'tc_toggleCommentsPage_section'
		);

		// Remove comments page in menu
		add_settings_field(
			'tc_remove_menu',
			__( 'Remove the admin comments page and comments menu', 'wordpress' ),
			array( $this, 'tc_remove_menu_render' ),
			'toggleCommentsPage',
			'tc_toggleCommentsPage_section'
		);

		// Redirect any user trying to access comments page
		add_settings_field(
			'tc_redirect_from_menu',
			__( 'Redirect any user trying to access the admin comments page', 'wordpress' ),
			array( $this, 'tc_redirect_from_menu_render' ),
			'toggleCommentsPage',
			'tc_toggleCommentsPage_section'
		);

		// Remove comments metabox from dashboard
		add_settings_field(
			'tc_remove_dashboard',
			__( 'Remove comments metaboxes from the admin dashboard', 'wordpress' ),
			array( $this, 'tc_remove_dashboard_render' ),
			'toggleCommentsPage',
			'tc_toggleCommentsPage_section'
		);

		// Remove comments links from admin bar
		add_settings_field(
			'tc_remove_links',
			__( 'Remove comments links from the admin bar', 'wordpress' ),
			array( $this, 'tc_remove_links_render' ),
			'toggleCommentsPage',
			'tc_toggleCommentsPage_section'
		);

	}

	// Disable support for comments and trackbacks in post types
	public function tc_disable_comment_trackbacks_render() {
		$options = get_option( 'tc_settings' ); ?>
		<input data-toggle="toggle" type='checkbox'
		       name='tc_settings[tc_disable_comment_trackbacks]' <?php checked( ( isset( $options['tc_disable_comment_trackbacks'] ) ? $options['tc_disable_comment_trackbacks'] : "" ), 1 ); ?>
		       value='1'>
	<?php
	}

	public function tc_disable_comments_post_types_support() {
		$post_types = get_post_types();
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
				remove_post_type_support( $post_type, 'trackbacks' );
			}
		}
	}

	// Close comments on the front-end
	public function tc_close_comments_render() {
		$options = get_option( 'tc_settings' ); ?>
		<input data-toggle="toggle" type='checkbox'
		       name='tc_settings[tc_close_comments]' <?php checked( ( isset( $options['tc_close_comments'] ) ? $options['tc_close_comments'] : "" ), 1 ); ?>
		       value='1'>
	<?php
	}

	public function tc_disable_comments_status() {
		return false;
	}

	// Hide existing comments
	public function tc_hide_existing_render() {
		$options = get_option( 'tc_settings' ); ?>
		<input data-toggle="toggle" type='checkbox'
		       name='tc_settings[tc_hide_existing]' <?php checked( ( isset( $options['tc_hide_existing'] ) ? $options['tc_hide_existing'] : "" ), 1 ); ?>
		       value='1'>
	<?php
	}

	public function tc_disable_comments_hide_existing_comments( $comments, $post_id ) {
		return array();
	}

	// Remove comments page in menu
	public function tc_remove_menu_render() {
		$options = get_option( 'tc_settings' ); ?>
		<input data-toggle="toggle" type='checkbox'
		       name='tc_settings[tc_remove_menu]' <?php checked( ( isset( $options['tc_remove_menu'] ) ? $options['tc_remove_menu'] : "" ), 1 ); ?>
		       value='1'>
	<?php
	}

	public function tc_disable_comments_admin_menu() {
		remove_menu_page( 'edit-comments.php' );
	}

	// Redirect any user trying to access comments page
	public function tc_redirect_from_menu_render() {
		$options = get_option( 'tc_settings' ); ?>
		<input data-toggle="toggle" type='checkbox'
		       name='tc_settings[tc_redirect_from_menu]' <?php checked( ( isset( $options['tc_redirect_from_menu'] ) ? $options['tc_redirect_from_menu'] : "" ), 1 ); ?>
		       value='1'>
	<?php
	}

	public function tc_disable_comments_admin_menu_redirect() {
		global $pagenow;
		if ( $pagenow === 'edit-comments.php' ) {
			wp_redirect( admin_url() );
			exit;
		}
	}

	// Remove comments metabox from dashboard
	public function tc_remove_dashboard_render() {
		$options = get_option( 'tc_settings' ); ?>
		<input data-toggle="toggle" type='checkbox'
		       name='tc_settings[tc_remove_dashboard]' <?php checked( ( isset( $options['tc_remove_dashboard'] ) ? $options['tc_remove_dashboard'] : "" ), 1 ); ?>
		       value='1'>
	<?php
	}

	public function tc_disable_comments_dashboard() {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		remove_meta_box( 'commentstatusdiv', 'dashboard', 'normal' );
		remove_meta_box( 'commentsdiv', 'dashboard', 'normal' );
	}

	// Remove comments links from admin bar
	public function tc_remove_links_render() {
		$options = get_option( 'tc_settings' ); ?>
		<input data-toggle="toggle" type='checkbox'
		       name='tc_settings[tc_remove_links]' <?php checked( ( isset( $options['tc_remove_links'] ) ? $options['tc_remove_links'] : "" ), 1 ); ?>
		       value='1'>
	<?php
	}

	public function tc_disable_comments_admin_bar( $wp_admin_bar ) {
        $wp_admin_bar->remove_node( 'comments' );
    }

	public function tc_settings_section_callback() {
		echo __( 'Inspired by comment 23 on trac ticket #27111 this plugin allows you to:', 'wordpress' );

	}

	public function toggle_comments_settings() {
		// Check that the user is allowed to update options
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'You do not have sufficient permissions to access this page.' );
		} ?>
		<form action='options.php' method='post'>
			<h2>Toggle Comments</h2>
			<?php
			settings_fields( 'toggleCommentsPage' );
			do_settings_sections( 'toggleCommentsPage' );
			submit_button();
			?>
		</form>
	<?php
	}

}

$toggle_comments = new Toggle_Comments();
