<?php

/*
 * Plugin Name: WP SendGrid
 * Description: SendGrid integration for WordPress
 * Version: 2.1.0
 * Plugin URI: http://github.com/codeawhile/wp-sendgrid
 * Author: Will Anderson
 * Author URI: http://codeawhile.com/
 * License: GPLv2
 */

class WP_SendGrid {

	public static function start() {
		WP_SendGrid::load_include( 'sendgrid-settings.php' );
		$options = WP_SendGrid_Settings::get_settings();
		if ( WP_SendGrid_Settings::API_REST == $options['api'] && !function_exists( 'wp_mail' ) ) {
				self::load_include( 'wp-mail.php' );
		} else {
			add_action( 'phpmailer_init', array( __CLASS__, 'configure_smtp' ) );
		}
		add_action( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );
	}

	function plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( __FILE__ ) ) {
			$settings_link = '<a href="options-general.php?page=' . WP_SendGrid_Settings::SETTINGS_PAGE_SLUG . '">' . __( 'Settings', 'wp_mail_smtp' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}

	public static function configure_smtp( &$phpmailer ) {
		$settings = WP_SendGrid_Settings::get_settings();

		$phpmailer->Mailer = 'smtp';
		$phpmailer->SMTPSecure = $settings['secure'] ? 'ssl' : 'none';
		$phpmailer->Host = 'smtp.sendgrid.net';
		$phpmailer->Port = $settings['secure'] ? 465 : 587;
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $settings['username'];
		$phpmailer->Password = $settings['password'];
	}

	public static function load_include( $include ) {
		require_once plugin_dir_path( __FILE__ ) . 'includes/' . $include;
	}

	public static function load_view( $view, $vars = array() ) {
		extract( $vars );

		include plugin_dir_path( __FILE__ ) . 'views/' . $view;
	}

	public static function plugin_url( $path ) {
		return plugins_url( $path, __FILE__ );
	}
}


WP_SendGrid::start();
