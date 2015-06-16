<?php
/**
 * E-commerce Module
 *
 * @author Mike Ems
 * @package WordPressHTTPS
 *
 */

class WordPressHTTPS_Module_Ecommerce extends Mvied_Plugin_Module {

	/**
	 * Initialize
	 *
	 * @param none
	 * @return void
	 */
	public function init() {
		if ( class_exists('Woocommerce') ) {
			add_filter('force_ssl', array(&$this, 'secure_woocommerce'), 40, 3);
		}
		if ( defined('WPSC_VERSION') ) {
			add_filter('force_ssl', array(&$this, 'secure_wpecommerce'), 40, 3);
		}
		if ( defined('JIGOSHOP_VERSION') ) {
			add_filter('force_ssl', array(&$this, 'secure_jigoshop'), 40, 3);
		}
	}

	/**
	 * Secure WooCommerce
	 * WordPress HTTPS Filter - force_ssl
	 *
	 * @param boolean $force_ssl
	 * @param int $post_id
	 * @param string $url
	 * @return boolean $force_ssl
	 */
	public function secure_woocommerce( $force_ssl, $post_id = 0, $url = '' ) {
		if ( !is_admin() && $post_id > 0 ) {
			$woocommerce_checkout_pages = array(
				get_option('woocommerce_checkout_page_id'),
				get_option('woocommerce_pay_page_id')
			);
			$woocommerce_account_pages = array(
				get_option('woocommerce_myaccount_page_id'),
				get_option('woocommerce_edit_address_page_id'),
				get_option('woocommerce_view_order_page_id'),
				get_option('woocommerce_change_password_page_id')
			);
			if ( get_option('woocommerce_force_ssl_checkout') == 'yes' ) {
				$secure_pages = array_merge($woocommerce_checkout_pages, $woocommerce_account_pages);
				if ( in_array($post_id, $secure_pages) ) {
					$force_ssl = true;
				} else if ( get_option('woocommerce_unforce_ssl_checkout') === 'yes' && !in_array($post_id, $secure_pages) ) {
					$force_ssl = false;
				}
			}
		}
		return $force_ssl;
	}

	/**
	 * Secure Jigoshop
	 * WordPress HTTPS Filter - force_ssl
	 *
	 * @param boolean $force_ssl
	 * @param int $post_id
	 * @param string $url
	 * @return boolean $force_ssl
	 */
	public function secure_jigoshop( $force_ssl, $post_id = 0, $url = '' ) {
		if ( !is_admin() && $post_id > 0 ) {
			if ( get_option('jigoshop_force_ssl_checkout') === 'yes' && ( $post_id == get_option('jigoshop_checkout_page_id') || strpos($url, 'admin-ajax.php?action=jigoshop-checkout') !== false ) ) {
				$force_ssl = true;
			}
		}
		return $force_ssl;
	}

	/**
	 * Secure WP E-commerce
	 * WordPress HTTPS Filter - force_ssl
	 *
	 * @param boolean $force_ssl
	 * @param int $post_id
	 * @param string $url
	 * @return boolean $force_ssl
	 */
	public function secure_wpecommerce( $force_ssl, $post_id = 0, $url = '' ) {
		global $wp_query;

		if ( !is_admin() && $post_id > 0 ) {
			if ( get_option('wpsc_force_ssl') === '1' && strpos( $wp_query->post->post_content, '[shoppingcart]' ) !== false ) {
				$force_ssl = true;
			}
		}
		return $force_ssl;
	}

}