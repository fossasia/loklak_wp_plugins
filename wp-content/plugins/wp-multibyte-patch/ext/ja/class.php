<?php
/**
 * WP Multibyte Patch Japanese Locale Extension
 *
 * @package WP_Multibyte_Patch
 * @version 2.4
 * @author Seisuke Kuraishi <210pura@gmail.com>
 * @copyright Copyright (c) 2015 Seisuke Kuraishi, Tinybit Inc.
 * @license http://opensource.org/licenses/gpl-2.0.php GPLv2
 * @link http://eastcoder.com/code/wp-multibyte-patch/
 */

/**
 * This class extends multibyte_patch.
 *
 * @package WP_Multibyte_Patch
 */
if ( class_exists( 'multibyte_patch' ) ) :
	class multibyte_patch_ext extends multibyte_patch {

	function get_jis_name() {
		if ( function_exists( 'mb_list_encodings' ) ) {
			$list = "\t" . implode( "\t", mb_list_encodings() ) . "\t";
			return ( preg_match( "/\tISO-2022-JP-MS\t/i", $list ) ) ? 'ISO-2022-JP-MS' : 'ISO-2022-JP';
		}
		else
			return 'ISO-2022-JP';
	}

	function UTF8toJIS( $string ) {
		return $this->convenc( $string, $this->get_jis_name(), 'UTF-8' );
	}

	function JIStoUTF8( $string ) {
		return $this->convenc( $string, 'UTF-8', $this->get_jis_name() );
	}

	function encode_mimeheader_b_uncut( $string = '', $charset = 'UTF-8' ) {
		if ( 0 == strlen( $string ) || strlen( $string ) == mb_strlen( $string, $charset ) )
			return $string;

		return "=?$charset?B?" . base64_encode( $string ) . '?=';
	}

	function get_phpmailer_properties( $phpmailer ) {
		$array = (array) $phpmailer;
		$new = array();
		foreach ( $array as $key => $value ) {
			$key = preg_replace( "/^\\0[^\\0]+\\0/", "", $key );
			$new[$key] = $value;
		}
		return $new;
	}

	function wp_mail( $phpmailer ) {
		$blog_encoding = $this->blog_encoding;

		$phpmailer->FromName = preg_replace( "/[\r\n]/", "", trim( $phpmailer->FromName ) );
		$phpmailer->FromName = $this->convenc( $phpmailer->FromName, 'UTF-8', $blog_encoding );
		$phpmailer->Subject = preg_replace( "/[\r\n]/", "", trim( $phpmailer->Subject ) );
		$phpmailer->Subject = $this->convenc( $phpmailer->Subject, 'UTF-8', $blog_encoding );
		$phpmailer->Body = $this->convenc( $phpmailer->Body, 'UTF-8', $blog_encoding );

		if ( 'UTF-8' == strtoupper( trim( $this->conf['mail_mode'] ) ) )
			$mode = 'UTF-8';
		elseif ( 'JIS' == strtoupper( trim( $this->conf['mail_mode'] ) ) )
			$mode = 'JIS';
		else { // Check unmappable characters and decide what to do.
			$test_str_before = $phpmailer->FromName . $phpmailer->Subject . $phpmailer->Body;
			$test_str_after = $this->UTF8toJIS( $test_str_before );
			$test_str_after = $this->JIStoUTF8( $test_str_after );
			$mode = ( $test_str_after != $test_str_before ) ? 'UTF-8' : 'JIS';
		}

		$phpmailer_props = $this->get_phpmailer_properties( $phpmailer );
		$recipient_methods = array( 'to' => array( 'add' => 'AddAddress', 'clear' => 'ClearAddresses' ), 'cc' => array( 'add' => 'AddCC', 'clear' => 'ClearCCs' ), 'bcc' => array( 'add' => 'AddBCC', 'clear' => 'ClearBCCs' ) );

		if ( 'UTF-8' == $mode ) {
			$phpmailer->CharSet = 'UTF-8';
			$phpmailer->Encoding = 'base64';
			$phpmailer->AddCustomHeader( 'Content-Disposition: inline' );
			$phpmailer->FromName = $this->encode_mimeheader_b_uncut( $phpmailer->FromName, 'UTF-8' );
			$phpmailer->Subject = $this->encode_mimeheader_b_uncut( $phpmailer->Subject, 'UTF-8' );

			foreach ( $recipient_methods as $name => $method ) {
				if ( isset( $phpmailer_props[$name][0] ) ) {
					$phpmailer->{$method['clear']}();
					foreach ( $phpmailer_props[$name] as $recipient ) {
						$recipient[1] = $this->encode_mimeheader_b_uncut( $recipient[1], 'UTF-8' );
						$phpmailer->{$method['add']}( $recipient[0], $recipient[1] );
					}
				}
			}
		}
		elseif ( 'JIS' == $mode ) {
			$phpmailer->CharSet = 'ISO-2022-JP';
			$phpmailer->Encoding = '7bit';
			$phpmailer->FromName = $this->UTF8toJIS( $phpmailer->FromName );
			$phpmailer->FromName = $this->encode_mimeheader_b_uncut( $phpmailer->FromName, 'ISO-2022-JP' );
			$phpmailer->Subject = $this->UTF8toJIS( $phpmailer->Subject );
			$phpmailer->Subject = $this->encode_mimeheader_b_uncut( $phpmailer->Subject, 'ISO-2022-JP' );
			$phpmailer->Body = $this->UTF8toJIS( $phpmailer->Body );

			foreach ( $recipient_methods as $name => $method ) {
				if ( isset( $phpmailer_props[$name][0] ) ) {
					$phpmailer->{$method['clear']}();
					foreach ( $phpmailer_props[$name] as $recipient ) {
						$recipient[1] = $this->UTF8toJIS( $recipient[1] );
						$recipient[1] = $this->encode_mimeheader_b_uncut( $recipient[1], 'ISO-2022-JP' );
						$phpmailer->{$method['add']}( $recipient[0], $recipient[1] );
					}
				}
			}
		}
	}

	function process_search_terms() {
		$blog_encoding = $this->blog_encoding;

		if ( isset( $_GET['s'] ) ) {
			$_GET['s'] = wp_unslash( $_GET['s'] );
			$_GET['s'] = mb_convert_kana( $_GET['s'], 's', $blog_encoding );
			$_GET['s'] = preg_replace( "/ +/", " ", $_GET['s'] );
			$_GET['s'] = wp_slash( $_GET['s'] );
		}
	}

	function guess_encoding( $string, $encoding = '' ) {
		$guess_list = 'UTF-8, eucJP-win, SJIS-win';

		if ( preg_match( "/^utf-8$/i", $encoding ) )
			return 'UTF-8';
		elseif ( preg_match( "/^euc-jp$/i", $encoding ) )
			return 'eucJP-win';
		elseif ( preg_match( "/^(sjis|shift_jis)$/i", $encoding ) )
			return 'SJIS-win';
		elseif ( !$encoding )
			return mb_detect_encoding( $string, $guess_list );
		else
			return $encoding;
	}

	function admin_custom_css() {
		if ( empty( $this->conf['admin_custom_css_url'] ) )
			$url = plugin_dir_url( __FILE__ ) . 'admin.css';
		else
			$url = $this->conf['admin_custom_css_url'];

		wp_enqueue_style( 'wpmp-admin-custom', $url, array(), '20131223' );
	}

	function wp_trim_words( $text = '', $num_words = 110, $more = '', $original_text = '' ) {
		if (
			$this->is_wp_required_version( '4.3-RC1' ) && 0 !== strpos( _x( 'words', 'Word count type. Do not translate!' ), 'characters' ) || 
			!$this->is_wp_required_version( '4.3-RC1' ) && 'characters' != _x( 'words', 'word count: words or characters?' )
		)
			return $text;

		// If the caller is wp_dashboard_recent_drafts()
		if ( false !== $this->conf['patch_dashboard_recent_drafts'] && 10 === $num_words && is_admin() && strpos( wp_debug_backtrace_summary(), 'wp_dashboard_recent_drafts' ) )
			$num_words = $this->conf['dashboard_recent_drafts_mblength'];

		$text = $original_text;
		$text = wp_strip_all_tags( $text );
		$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );

		if ( mb_strlen( $text, $this->blog_encoding ) > $num_words )
			$text = mb_substr( $text, 0, $num_words, $this->blog_encoding ) . $more;

		return $text;
	}

	function __construct() {
		// mbstring functions are always required for ja.
		$this->mbfunctions_required = true;

		$this->conf['patch_wp_mail'] = true;
		$this->conf['patch_incoming_trackback'] = true;
		$this->conf['patch_incoming_pingback'] = true;
		$this->conf['patch_process_search_terms'] = true;
		$this->conf['patch_admin_custom_css'] = true;
		$this->conf['patch_force_character_count'] = true;
		$this->conf['patch_force_twentytwelve_open_sans_off'] = true;
		$this->conf['patch_wp_trim_words'] = true;
		// auto, JIS, UTF-8
		$this->conf['mail_mode'] = 'JIS';
		$this->conf['admin_custom_css_url'] = '';

		parent::__construct();
	}
}
endif;
