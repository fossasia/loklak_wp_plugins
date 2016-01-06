<?php
/*
Plugin Name: WP Multibyte Patch
Description: Multibyte functionality enhancement for the WordPress Japanese package.
Version: 2.5
Plugin URI: http://eastcoder.com/code/wp-multibyte-patch/
Author: Seisuke Kuraishi
Author URI: http://tinybit.co.jp/
License: GPLv2
Text Domain: wp-multibyte-patch
Domain Path: /languages
*/

/**
 * Multibyte functionality enhancement for the WordPress Japanese package.
 *
 * @package WP_Multibyte_Patch
 * @version 2.5
 * @author Seisuke Kuraishi <210pura@gmail.com>
 * @copyright Copyright (c) 2015 Seisuke Kuraishi, Tinybit Inc.
 * @license http://opensource.org/licenses/gpl-2.0.php GPLv2
 * @link http://eastcoder.com/code/wp-multibyte-patch/
 */

/**
 * @package WP_Multibyte_Patch
 */
class multibyte_patch {

	// Do not edit this section. Use wpmp-config.php instead.
	var $conf = array(
		'excerpt_mblength' => 110,
		'excerpt_more' => ' [&hellip;]',
		'comment_excerpt_mblength' => 40,
		'dashboard_recent_drafts_mblength' => 40,
		'patch_wp_mail' => false,
		'patch_incoming_trackback' => false,
		'patch_incoming_pingback' => false,
		'patch_wp_trim_excerpt' => true,
		'patch_wp_trim_words' => false,
		'patch_get_comment_excerpt' => true,
		'patch_dashboard_recent_drafts' => true,
		'patch_process_search_terms' => false,
		'patch_admin_custom_css' => false,
		'patch_wplink_js' => true,
		'patch_force_character_count' => false,
		'patch_force_twentytwelve_open_sans_off' => false,
		'patch_force_twentythirteen_google_fonts_off' => false,
		'patch_force_twentyfourteen_google_fonts_off' => false,
		'patch_force_twentyfifteen_google_fonts_off' => false,
		'patch_force_twentysixteen_google_fonts_off' => false,
		'patch_sanitize_file_name' => true,
		'patch_bp_create_excerpt' => false,
		'bp_excerpt_mblength' => 110,
		'bp_excerpt_more' => ' [&hellip;]'
	);

	var $blog_encoding = 'UTF-8';
	var $has_mbfunctions = false;
	var $mbfunctions_required = false;
	var $has_mb_strlen = false;
	var $debug_suffix = '';
	var $textdomain = 'wp-multibyte-patch';
	var $lang_dir = 'languages';
	var $required_version = '4.3-RC3';
	var $query_based_vars = array();

	// For fallback purpose only. (1.6)
	function guess_encoding( $string, $encoding = '' ) {
		$blog_encoding = $this->blog_encoding;

		if ( !$encoding && seems_utf8( $string ) )
			return 'UTF-8';
		elseif ( !$encoding )
			return $blog_encoding;
		else
			return $encoding;
	}

	// For fallback purpose only. (1.6)
	function convenc( $string, $to_encoding, $from_encoding = '' ) {
		$blog_encoding = $this->blog_encoding;

		if ( '' == $from_encoding )
			$from_encoding = $blog_encoding;

		if ( strtoupper( $to_encoding ) == strtoupper( $from_encoding ) )
			return $string;
		else
			return mb_convert_encoding( $string, $to_encoding, $from_encoding );
	}

	function incoming_trackback( $commentdata ) {
		if ( 'trackback' != $commentdata['comment_type'] )
			return $commentdata;

		if ( false === $this->conf['patch_incoming_trackback'] )
			return $commentdata;

		$title = isset( $_POST['title'] ) ? wp_unslash( $_POST['title'] ) : '';
		$excerpt = isset( $_POST['excerpt'] ) ? wp_unslash( $_POST['excerpt'] ) : '';
		$blog_name = isset( $_POST['blog_name'] ) ? wp_unslash( $_POST['blog_name'] ) : '';
		$blog_encoding = $this->blog_encoding;

		$from_encoding = isset( $_POST['charset'] ) ? $_POST['charset'] : '';

		if ( !$from_encoding )
			$from_encoding = ( preg_match( "/^.*charset=([a-zA-Z0-9\-_]+).*$/i", $_SERVER['CONTENT_TYPE'], $matched ) ) ? $matched[1] : '';

		$from_encoding = str_replace( array( ',', ' ' ), '', strtoupper( trim( $from_encoding ) ) );
		$from_encoding = $this->guess_encoding( $excerpt . $title . $blog_name, $from_encoding );

		$title = $this->convenc( $title, $blog_encoding, $from_encoding );
		$blog_name = $this->convenc( $blog_name, $blog_encoding, $from_encoding );
		$excerpt = $this->convenc( $excerpt, $blog_encoding, $from_encoding );

		$title = strip_tags( $title );
		$excerpt = strip_tags( $excerpt );

		$title = ( strlen( $title ) > 250 ) ? mb_strcut( $title, 0, 250, $blog_encoding ) . '&#8230;' : $title;
		$excerpt = ( strlen( $excerpt ) > 255 ) ? mb_strcut( $excerpt, 0, 252, $blog_encoding ) . '&#8230;' : $excerpt;

		$commentdata['comment_author'] = wp_slash( $blog_name );
		$commentdata['comment_content'] = wp_slash( "<strong>$title</strong>\n\n$excerpt" );

		return $commentdata;
	}

	function pre_remote_source( $linea, $pagelinkedto ) {
		$this->pingback_ping_linea = $linea;
		$this->pingback_ping_pagelinkedto = $pagelinkedto;
		return $linea;
	}

	function incoming_pingback( $commentdata ) {
		if ( 'pingback' != $commentdata['comment_type'] )
			return $commentdata;

		if ( false === $this->conf['patch_incoming_pingback'] )
			return $commentdata;

		$pagelinkedto = $this->pingback_ping_pagelinkedto;
		$linea = $this->pingback_ping_linea;

		$linea = preg_replace( "/" . preg_quote( '<!DOC', '/' ) . "/i", '<DOC', $linea );
		$linea = preg_replace( "/[\r\n\t ]+/", ' ', $linea );
		$linea = preg_replace( "/<\/*(h1|h2|h3|h4|h5|h6|p|th|td|li|dt|dd|pre|caption|input|textarea|button|body)[^>]*>/i", "\n\n", $linea );

		preg_match( '|<title>([^<]*?)</title>|is', $linea, $matchtitle );
		$title = $matchtitle[1];

		preg_match( "/<meta[^<>]+charset=\"*([a-zA-Z0-9\-_]+)\"*[^<>]*>/i", $linea, $matches );
		$charset = isset( $matches[1] ) ? $matches[1] : '';
		$from_encoding = $this->guess_encoding( strip_tags( $linea ), $charset );
		$blog_encoding = $this->blog_encoding;

		$linea = strip_tags( $linea, '<a>' );
		$linea = $this->convenc( $linea, $blog_encoding, $from_encoding );
		$p = explode( "\n\n", $linea );

		foreach ( $p as $para ) {
			if ( strpos( $para, $pagelinkedto ) !== false && preg_match( "/^([^<>]*)(\<a[^<>]+[\"']" . preg_quote( $pagelinkedto, '/' ) . "[\"'][^<>]*\>)([^<>]+)(\<\/a\>)(.*)$/i", $para, $context ) )
				break;
		}

		if ( !$context )
			return $commentdata;

		$context[1] = strip_tags( $context[1] );
		$context[5] = strip_tags( $context[5] );
		$len_max = 250;
		$len_c3 = strlen( $context[3] );

		if ( $len_c3 > $len_max ) {
			$excerpt = mb_strcut( $context[3], 0, 250, $blog_encoding );
		} else {
			$len_c1 = strlen( $context[1] );
			$len_c5 = strlen( $context[5] );
			$len_left = $len_max - $len_c3;
			$len_left_even = ceil( $len_left / 2 );

			if ( $len_left_even > $len_c1 ) {
				$context[5] = mb_strcut( $context[5], 0, $len_left - $len_c1, $blog_encoding );
			}
			elseif ( $len_left_even > $len_c5 ) {
				$context[1] .= "\t\t\t\t\t\t";
				$context[1] = mb_strcut( $context[1], $len_c1 - ( $len_left - $len_c5 ), $len_c1 + 6, $blog_encoding );
				$context[1] = preg_replace( "/\t*$/", '', $context[1] );
			}
			else {
				$context[1] .= "\t\t\t\t\t\t";
				$context[1] = mb_strcut( $context[1], $len_c1 - $len_left_even, $len_c1 + 6, $blog_encoding );
				$context[1] = preg_replace( "/\t*$/", '', $context[1] );
				$context[5] = mb_strcut( $context[5], 0, $len_left_even, $blog_encoding );
			}

			$excerpt = $context[1] . $context[3] . $context[5];
		}

		$commentdata['comment_content'] = '[&#8230;] ' . esc_html( $excerpt ) . ' [&#8230;]';
		$commentdata['comment_content'] = wp_slash( $commentdata['comment_content'] );
		$commentdata['comment_author'] = $this->convenc( $title, $blog_encoding, $from_encoding );
		$commentdata['comment_author'] = wp_slash( $commentdata['comment_author'] );

		return $commentdata;
	}

	function preprocess_comment( $commentdata ) {
		if ( $commentdata['comment_type'] == 'trackback' )
			return $this->incoming_trackback( $commentdata );
		elseif ( $commentdata['comment_type'] == 'pingback' )
			return $this->incoming_pingback( $commentdata );
		else
			return $commentdata;
	}

	function trim_multibyte_excerpt( $text = '', $length = 110, $more = ' [&hellip;]', $encoding = 'UTF-8' ) {
		$text = strip_shortcodes( $text );
		$text = str_replace( ']]>', ']]&gt;', $text );
		$text = strip_tags( $text );
		$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );

		if ( $this->mb_strlen( $text, $encoding ) > $length )
			$text = mb_substr( $text, 0, $length, $encoding ) . $more;

		return $text;
	}

	function bp_create_excerpt( $text = '' ) {
		return $this->trim_multibyte_excerpt( $text, $this->conf['bp_excerpt_mblength'], $this->conf['bp_excerpt_more'], $this->blog_encoding );
	}

	function bp_get_activity_content_body( $content = '' ) {
		return preg_replace( "/<a [^<>]+>([^<>]+)<\/a>(" . preg_quote( $this->conf['bp_excerpt_more'], '/' ) . "<\/p>)$/", "$1$2", $content );
	}

	function get_comment_excerpt( $excerpt = '', $comment_ID = 0, $comment = '' ) {
		$blog_encoding = $this->blog_encoding;
		$excerpt = strip_tags( str_replace( array( "\n", "\r" ), ' ', $comment->comment_content ) );

		if ( $this->mb_strlen( $excerpt, $blog_encoding ) > $this->conf['comment_excerpt_mblength'] )
			$excerpt = mb_substr( $excerpt, 0, $this->conf['comment_excerpt_mblength'], $blog_encoding ) . '&hellip;';

		return $excerpt;
	}

	function excerpt_mblength() {
		if ( isset( $this->query_based_vars['excerpt_mblength'] ) && (int) $this->query_based_vars['excerpt_mblength'] )
			$length = (int) $this->query_based_vars['excerpt_mblength'];
		else
			$length = (int) $this->conf['excerpt_mblength'];

		return apply_filters( 'excerpt_mblength', $length );
	}

	function excerpt_more() {
		if ( isset( $this->query_based_vars['excerpt_more'] ) )
			return $this->query_based_vars['excerpt_more'];
		else
			return $this->conf['excerpt_more'];
	}

	function sanitize_file_name( $name ) {
		$info = pathinfo( $name );
		$ext = !empty( $info['extension'] ) ? '.' . $info['extension'] : '';
		$name = str_replace( $ext, '', $name );
		$name_enc = rawurlencode( $name );
		$name = ( $name == $name_enc ) ? $name . $ext : md5( $name ) . $ext;
		return $name;
	}

	function wplink_js( &$scripts ) {
		$script_required_version = '4.4-RC1';

		if ( !$this->is_wp_required_version( $script_required_version ) )
			$scripts->add( 'wplink', plugin_dir_url( __FILE__ ) . "js/20150818/wplink{$this->debug_suffix}.js", array( 'jquery' ), '20150818', 1 );
		else
			$scripts->add( 'wplink', plugin_dir_url( __FILE__ ) . "js/wplink{$this->debug_suffix}.js", array( 'jquery' ), '20151207', 1 );
	}

	function force_character_count( $translations = '', $text = '', $context = '' ) {
		if ( 'word count: words or characters?' == $context && 'words' == $text )
			return 'characters';

		if ( 'Word count type. Do not translate!' == $context && 'words' == $text )
			return 'characters_including_spaces';

		return $translations;
	}

	function force_twentytwelve_open_sans_off() {
		wp_dequeue_style( 'twentytwelve-fonts' );
	}

	function force_twentythirteen_google_fonts_off() {
		wp_dequeue_style( 'twentythirteen-fonts' );
	}

	function force_twentyfourteen_google_fonts_off() {
		wp_dequeue_style( 'twentyfourteen-lato' );
	}

	function force_twentyfifteen_google_fonts_off() {
		wp_dequeue_style( 'twentyfifteen-fonts' );
	}

	function force_twentysixteen_google_fonts_off() {
		wp_dequeue_style( 'twentysixteen-fonts' );
	}

	function remove_editor_style( $file = '' ) {
		global $editor_styles;

		if ( ! is_admin() || empty( $editor_styles ) || ! is_array( $editor_styles ) )
			return;

		foreach ( $editor_styles as $key => $value ) {
			if( $file === $value )
				unset( $editor_styles[$key] );
		}
	}

	function query_based_settings() {
		$is_query_funcs = array( 'is_feed', 'is_404', 'is_search', 'is_tax', 'is_front_page', 'is_home', 'is_attachment', 'is_single', 'is_page', 'is_category', 'is_tag', 'is_author', 'is_date', 'is_archive', 'is_paged' );

		foreach ( $is_query_funcs as $func ) {
			if ( isset( $this->conf['excerpt_mblength.' . $func] ) && !isset( $this->query_based_vars['excerpt_mblength'] ) && $func() )
				$this->query_based_vars['excerpt_mblength'] = $this->conf['excerpt_mblength.' . $func];

			if ( isset( $this->conf['excerpt_more.' . $func] ) && !isset( $this->query_based_vars['excerpt_more'] ) && $func() )
				$this->query_based_vars['excerpt_more'] = $this->conf['excerpt_more.' . $func];
		}
	}

	// The fallback only works with UTF-8 blog.
	function mb_strlen( $str = '', $encoding = 'UTF-8' ) {
		if ( $this->has_mb_strlen )
			return mb_strlen( $str, $encoding );
		else
			return preg_match_all( "/./us", $str, $match );
	}

	function is_wp_required_version( $required_version ) {
		global $wp_version;
		return version_compare( $wp_version, $required_version, '<' ) ? false : true;
	}

	function filters_after_setup_theme() {
		// add filter
		if ( false !== $this->conf['patch_force_character_count']) {
			if ( 'characters_including_spaces' != _x( 'words', 'Word count type. Do not translate!' ) )
			add_filter( 'gettext_with_context', array( $this, 'force_character_count' ), 10, 3 );
		}

		if ( false !== $this->conf['patch_force_twentytwelve_open_sans_off'] && 'twentytwelve' == get_template() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'force_twentytwelve_open_sans_off' ), 99 );
			add_action( 'admin_print_styles-appearance_page_custom-header', array( $this, 'force_twentytwelve_open_sans_off' ), 99 );
		}

		if ( false !== $this->conf['patch_force_twentythirteen_google_fonts_off'] && 'twentythirteen' == get_template() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'force_twentythirteen_google_fonts_off' ), 99 );
			add_action( 'admin_print_styles-appearance_page_custom-header', array( $this, 'force_twentythirteen_google_fonts_off' ), 99 );
		}

		if ( false !== $this->conf['patch_force_twentyfourteen_google_fonts_off'] && 'twentyfourteen' == get_template() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'force_twentyfourteen_google_fonts_off' ), 99 );
			add_action( 'admin_print_scripts-appearance_page_custom-header', array( $this, 'force_twentyfourteen_google_fonts_off' ), 99 );
		}

		if ( false !== $this->conf['patch_force_twentyfifteen_google_fonts_off'] && 'twentyfifteen' == get_template() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'force_twentyfifteen_google_fonts_off' ), 99 );

			if ( function_exists( 'twentyfifteen_fonts_url' ) )
				$this->remove_editor_style( twentyfifteen_fonts_url() );
		}

		if ( false !== $this->conf['patch_force_twentysixteen_google_fonts_off'] && 'twentysixteen' == get_template() ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'force_twentysixteen_google_fonts_off' ), 99 );

			if ( function_exists( 'twentysixteen_fonts_url' ) )
				$this->remove_editor_style( twentysixteen_fonts_url() );
		}
	}

	function filters() {
		// add filter
		add_filter( 'preprocess_comment', array( $this, 'preprocess_comment' ), 99 );

		if ( false !== $this->conf['patch_incoming_pingback'] )
			add_filter( 'pre_remote_source', array( $this, 'pre_remote_source' ), 10, 2 );

		if ( false !== $this->conf['patch_wp_trim_excerpt'] ) {
			add_filter( 'excerpt_length', array( $this, 'excerpt_mblength' ), 99 );
			add_filter( 'excerpt_more', array( $this, 'excerpt_more' ), 9 );
		}

		if ( false !== $this->conf['patch_get_comment_excerpt'] )
			add_filter( 'get_comment_excerpt', array( $this, 'get_comment_excerpt' ), 10, 3 );

		if ( false !== $this->conf['patch_sanitize_file_name'] )
			add_filter( 'sanitize_file_name', array( $this, 'sanitize_file_name' ) );

		if ( false !== $this->conf['patch_bp_create_excerpt'] ) {
			add_filter( 'bp_create_excerpt', array( $this, 'bp_create_excerpt' ), 99 );
			add_filter( 'bp_get_activity_content_body', array( $this, 'bp_get_activity_content_body' ), 99 );
		}

		if ( method_exists( $this, 'wp_trim_words' ) && false !== $this->conf['patch_wp_trim_words'] )
			add_filter( 'wp_trim_words', array( $this, 'wp_trim_words' ), 99, 4 );

		// add action
		add_action( 'wp', array( $this, 'query_based_settings' ) );

		if ( method_exists( $this, 'process_search_terms' ) && false !== $this->conf['patch_process_search_terms'] )
			add_action( 'sanitize_comment_cookies', array( $this, 'process_search_terms' ) );

		if ( method_exists( $this, 'wp_mail' ) && false !== $this->conf['patch_wp_mail'] )
			add_action( 'phpmailer_init', array( $this, 'wp_mail' ) );

		if ( method_exists( $this, 'admin_custom_css' ) && false !== $this->conf['patch_admin_custom_css'] ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_custom_css' ), 99 );
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'admin_custom_css' ), 99 );
		}

		if ( false !== $this->conf['patch_wplink_js'] )
			add_action( 'wp_default_scripts', array( $this, 'wplink_js' ), 9 );

		add_action( 'after_setup_theme', array( $this, 'filters_after_setup_theme' ), 99 );
	}

	function mbfunctions_exist() {
		return (
			function_exists( 'mb_convert_encoding' ) &&
			function_exists( 'mb_convert_kana' ) &&
			function_exists( 'mb_detect_encoding' ) &&
			function_exists( 'mb_strcut' ) &&
			function_exists( 'mb_strlen' )
		) ? true : false;
	}

	function activation_check() {
		$required_version = $this->required_version;

		if ( !$this->is_wp_required_version( $required_version ) ) {
			deactivate_plugins( __FILE__ );
			exit( sprintf( __( 'Sorry, WP Multibyte Patch requires WordPress %s or later.', 'wp-multibyte-patch' ), $required_version ) );
		}
		elseif ( !$this->has_mbfunctions && $this->mbfunctions_required ) {
			deactivate_plugins( __FILE__ );
			exit( __( 'Sorry, WP Multibyte Patch requires <a href="http://www.php.net/manual/en/mbstring.installation.php" target="_blank">mbstring</a> functions.', 'wp-multibyte-patch' ) );
		}
	}

	function load_conf() {
		$wpmp_conf = array();

		if ( file_exists( WP_CONTENT_DIR . '/wpmp-config.php' ) )
			require_once WP_CONTENT_DIR . '/wpmp-config.php';

		if ( is_multisite() ) {
			$blog_id = get_current_blog_id();
			if ( file_exists( WP_CONTENT_DIR . '/wpmp-config-blog-' . $blog_id . '.php' ) )
				require_once WP_CONTENT_DIR . '/wpmp-config-blog-' . $blog_id . '.php';
		}

		$this->conf = array_merge( $this->conf, $wpmp_conf );
	}

	function __construct() {
		$this->load_conf();
		$this->blog_encoding = get_option( 'blog_charset' );
		if ( empty( $this->blog_encoding ) )
			$this->blog_encoding = 'UTF-8';

		// mbstring functions are required for non UTF-8 blog.
		if ( !preg_match( "/^utf-?8$/i", $this->blog_encoding ) )
			$this->mbfunctions_required = true;

		$this->has_mbfunctions = $this->mbfunctions_exist();
		$this->has_mb_strlen = function_exists( 'mb_strlen' );
		$this->debug_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		load_plugin_textdomain( $this->textdomain, false, dirname( plugin_basename( __FILE__ ) ) . '/' . $this->lang_dir );
		register_activation_hook( __FILE__, array( $this, 'activation_check' ) );
		$this->filters();
	}
}

if ( defined( 'WP_PLUGIN_URL' ) ) {
	if ( file_exists( dirname( __FILE__ ) . '/ext/' . get_locale() . '/class.php' ) ) {
		require_once dirname( __FILE__ ) . '/ext/' . get_locale() . '/class.php';
		$GLOBALS['wpmp'] = new multibyte_patch_ext();
	}
	elseif ( file_exists( dirname( __FILE__ ) . '/ext/default/class.php' ) ) {
		require_once dirname( __FILE__ ) . '/ext/default/class.php';
		$GLOBALS['wpmp'] = new multibyte_patch_ext();
	}
	else
		$GLOBALS['wpmp'] = new multibyte_patch();
}
