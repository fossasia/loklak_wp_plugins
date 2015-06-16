<?php
/**
 * WordPress 日本語版用 WP Multibyte Patch 設定ファイル
 *
 * このファイルを利用してデフォルトの設定値を任意に上書きすることができます。
 * 設定を有効化するには、下記の場所へこのファイルを変名コピーして内容を編集してください。
 *
 * /wp-content/wpmp-config.php
 *
 * マルチサイトインストール環境では、下記ファイル名にすることで各ブログごとに設定ファイルを持つことができます。
 *
 * /wp-content/wpmp-config-blog-{BLOG ID}.php
 *
 * 設定の詳細は {@link http://eastcoder.com/code/wp-multibyte-patch/} を参照してください。
 *
 * @package WP_Multibyte_Patch
 */

/**
 * 投稿抜粋の最大文字数
 *
 * この設定は the_excerpt() とその関連の抜粋系関数に適用されます。
 * この設定は $wpmp_conf['patch_wp_trim_excerpt'] が false の場合は無効となります。
 */
$wpmp_conf['excerpt_mblength'] = 110;

/**
 * 投稿抜粋末尾に出力される more 文字列
 *
 * この設定は the_excerpt() とその関連の抜粋系関数に適用されます。
 * この設定は $wpmp_conf['patch_wp_trim_excerpt'] が false の場合は無効となります。
 */
$wpmp_conf['excerpt_more'] = ' [&hellip;]';

/**
 * get_comment_excerpt() 抜粋の最大文字数
 *
 * この設定は comment_excerpt() (ダッシュボード > アクティビティ > コメント の抜粋で利用) に適用されます。
 * この設定は $wpmp_conf['patch_get_comment_excerpt'] が false の場合は無効となります。
 */
$wpmp_conf['comment_excerpt_mblength'] = 40;

/**
 * ダッシュボード「下書き」抜粋の最大文字数
 *
 * この設定は、ダッシュボード > クイックドラフト > 下書き の抜粋に適用されます。
 * この設定は $wpmp_conf['patch_dashboard_recent_drafts'] が false の場合は無効となります。
 */
$wpmp_conf['dashboard_recent_drafts_mblength'] = 40;

/**
 * wp_mail() の文字エンコーディング
 *
 * この設定は WordPress から wp_mail() を通して送信されるメールに適用されます。
 * 指定可能な値は、'JIS'、'UTF-8'、'auto' です。
 * この設定は $wpmp_conf['patch_wp_mail'] が false の場合は無効となります。
 */
$wpmp_conf['mail_mode'] = 'JIS';

/**
 * 管理パネルカスタム CSS の URL
 *
 * 管理パネルで読み込まれる CSS の URL を任意で指定することができます。
 * 未指定の場合は、デフォルトの CSS が読み込まれます。
 * この設定は $wpmp_conf['patch_admin_custom_css'] が false の場合は無効となります。
 */
$wpmp_conf['admin_custom_css_url'] = '';

/**
 * BuddyPress bp_create_excerpt() 抜粋の最大文字数
 *
 * この設定は BuddyPress の bp_create_excerpt() (アクティビティストリームの抜粋で利用) に適用されます。
 * この設定は $wpmp_conf['patch_bp_create_excerpt'] が false の場合は無効となります。
 */
$wpmp_conf['bp_excerpt_mblength'] = 110;

/**
 * BuddyPress bp_create_excerpt() 抜粋末尾に出力される more 文字列
 *
 * この設定は BuddyPress の bp_create_excerpt() (アクティビティストリームの抜粋で利用) に適用されます。
 * この設定は $wpmp_conf['patch_bp_create_excerpt'] が false の場合は無効となります。
 */
$wpmp_conf['bp_excerpt_more'] = ' [&hellip;]';


/* 機能を個別に有効化、無効化できます。有効化するには true を、無効化するには false を指定してください。 */
$wpmp_conf['patch_wp_mail'] = true;
$wpmp_conf['patch_incoming_trackback'] = true;
$wpmp_conf['patch_incoming_pingback'] = true;
$wpmp_conf['patch_wp_trim_excerpt'] = true;
$wpmp_conf['patch_wp_trim_words'] = true;
$wpmp_conf['patch_get_comment_excerpt'] = true;
$wpmp_conf['patch_dashboard_recent_drafts'] = true;
$wpmp_conf['patch_process_search_terms'] = true;
$wpmp_conf['patch_admin_custom_css'] = true;
$wpmp_conf['patch_wplink_js'] = true;
$wpmp_conf['patch_word_count_js'] = true;
$wpmp_conf['patch_force_character_count'] = true;
$wpmp_conf['patch_force_twentytwelve_open_sans_off'] = true;
$wpmp_conf['patch_force_twentythirteen_google_fonts_off'] = false;
$wpmp_conf['patch_force_twentyfourteen_google_fonts_off'] = false;
$wpmp_conf['patch_force_twentyfifteen_google_fonts_off'] = false;
$wpmp_conf['patch_sanitize_file_name'] = true;
$wpmp_conf['patch_bp_create_excerpt'] = false;
