<?php
add_filter( 'ctf_admin_search_label', 'ctf_return_string_hashtag' );
function ctf_return_string_hashtag( $val ) {
    return 'Hashtag:';
}

add_filter( 'ctf_admin_search_whatis', 'ctf_return_string_instructions' );
function ctf_return_string_instructions( $val ) {
    return 'Select this option and enter any single hashtag for a hashtag feed';
}

add_filter( 'ctf_admin_validate_search_text', 'ctf_validate_search_text', 10, 1 );
function ctf_validate_search_text( $val ) {
    preg_match( "/^[\p{L}][\p{L}0-9_]+|^#+[\p{L}][\p{L}0-9_]+/u", trim( $val ), $hashtags );

    $hashtags = preg_replace( "/#{2,}/", '', $hashtags );

    $new_val = ! empty( $hashtags ) ? $new_val = $hashtags[0] : '';

    if ( substr( $new_val, 0, 1 ) != '#' && $new_val != '' ) {
        $new_val = '#' . $new_val;
    }

    return $new_val;
}

add_filter( 'ctf_admin_validate_usertimeline_text', 'ctf_validate_usertimeline_text', 10, 1 );
function ctf_validate_usertimeline_text( $val ) {
    preg_match( "/^[\p{L}][\p{L}0-9_]{1,16}/u" , str_replace( '@', '', trim( $val ) ), $screenname );

    $new_val = isset( $screenname[0] ) ? $screenname[0] : '';

    return $new_val;
}

add_filter( 'ctf_admin_validate_include_replies', 'ctf_validate_include_replies', 10, 1 );
function ctf_validate_include_replies( $val ) {
    return false;
}

add_filter( 'ctf_admin_set_include_replies', 'ctf_set_include_replies', 10, 1 );
function ctf_set_include_replies( $new_input ) {
    return false;
}

add_filter( 'ctf_admin_feed_type_list', 'ctf_return_feed_types' );
function ctf_return_feed_types( $val ) {
    return array( 'hometimelineinclude_replies', 'usertimelineinclude_replies' );
}

add_action( 'ctf_admin_upgrade_note', 'ctf_update_note' );
function ctf_update_note() {
    ?>
    <span class="ctf_note"> - <a href="https://smashballoon.com/custom-twitter-feeds/" target="_blank">Available in Pro version</a></span>
    <?php
}

add_action( 'ctf_admin_feed_settings_radio_extra', 'ctf_usertimeline_error_message' );
function ctf_usertimeline_error_message( $args )
{ //sbi_notice sbi_user_id_error
    if ( $args['name'] == 'usertimeline') : ?>
        <div class="ctf_notice ctf_usertimeline_error">
            <?php _e( "<p>Please use a single screenname or Twitter handle of numbers and letters. If you would like to use more than one screen name for your feed, please upgrade to our <a href='https://smashballoon.com/custom-twitter-feeds/' target='_blank'>Pro version</a>.</p>" ); ?>
        </div>
    <?php endif;
}

add_action( 'ctf_admin_feed_settings_search_extra', 'ctf_hashtag_error_message' );
function ctf_hashtag_error_message() {
    ?>
    <div class="ctf_notice ctf_search_error">
        <?php _e( "<p>Please use a single hashtag of numbers and letters. If you would like to use more than one hashtag or use search terms for your feed, please upgrade to our <a href='https://smashballoon.com/custom-twitter-feeds/' target='_blank'>Pro version</a>.</p>" ); ?>
    </div>
    <?php
}

add_filter( 'ctf_admin_customize_quick_links', 'ctf_return_customize_quick_links' );
function ctf_return_customize_quick_links() {
    return array(
        0 => array( 'general', 'General' ),
        1 => array( 'showhide', 'Show/Hide' ),
        2 => array( 'misc', 'Misc' ),
        3 => array( 'advanced', 'Advanced' )
    );
}

add_filter( 'ctf_admin_style_quick_links', 'ctf_return_style_quick_links' );
function ctf_return_style_quick_links() {
    return array(
        0 => array( 'general', 'General' ),
        1 => array( 'header', 'Header' ),
        2 => array( 'date', 'Date' ),
        3 => array( 'author', 'Author' ),
        4 => array( 'text', 'Tweet Text' ),
        5 => array( 'links', 'Links' ),
        6 => array( 'quoted', 'Retweet Boxes' ),
        7 => array( 'actions', 'Tweet Actions' ),
        8 => array( 'load', 'Load More' )
    );
}

/*
 * Pro Options ----------------------------------------
 */

add_action( 'ctf_admin_endpoints', 'ctf_add_mentionstimeline_options', 10, 1 );
function ctf_add_mentionstimeline_options( $admin ) {
    $admin->create_settings_field( array(
        'name' => 'mentionstimeline',
        'title' => '<label></label>', // label for the input field
        'callback'  => 'feed_settings_radio', // name of the function that outputs the html
        'page' => 'ctf_options_feed_settings', // matches the section name
        'section' => 'ctf_options_feed_settings', // matches the section name
        'option' => 'ctf_options', // matches the options name
        'class' => 'ctf-radio ctf_pro', // class for the wrapper and input field
        'whatis' => 'Select this option to display tweets that @mention your twitter handle', // what is this? text
        'label' => "Mentions",
        'has_input' => false,
        'has_replies' => false
    ));
}

add_filter( 'ctf_admin_show_hide_list', 'ctf_show_hide_list', 10, 1 );
function ctf_show_hide_list( $show_hide_list ) {
    $show_hide_list[8] = array( 'include_replied_to', 'In reply to text' );
    $show_hide_list[9] = array( 'include_media', 'Media (images, videos, gifs)' );
    $show_hide_list[10] = array( 'include_twittercards', 'Twitter Cards' );
    return $show_hide_list;
}

add_action( 'ctf_admin_style_option', 'ctf_add_masonry_autoscroll_options', 5, 1 );
function ctf_add_masonry_autoscroll_options( $admin )
{
    // custom in reply to text
    $admin->create_settings_field( array(
        'name' => 'inreplytotext',
        'title' => '<label for="ctf_inreplytotext">Translation for "In reply to"</label><code class="ctf_shortcode">inreplytotext
            Eg: inreplytotext="Als Antwort an"</code>', // label for the input field
        'callback'  => 'default_text', // name of the function that outputs the html
        'page' => 'ctf_options_text', // matches the section name
        'section' => 'ctf_options_text', // matches the section name
        'option' => 'ctf_options', // matches the options name
        'class' => 'default-text ctf_pro', // class for the wrapper and input field
        'whatis' => 'This will replace the default text displayed for "In reply to"',
        'default' => 'In reply to'// "what is this?" text
    ));

    add_settings_section(
        'ctf_options_masonry', // matches the section name
        '<span class="ctf_pro_header">Masonry Columns</span><p class="ctf_pro_section_note"><a href="https://smashballoon.com/custom-twitter-feeds/" target="_blank">Upgrade to Pro to enable Masonry layouts</a></p>',
        array( $admin, 'general_section_text' ), // callback function to explain the section
        'ctf_options_masonry' // matches the section name
    );

    // masonry default
    // $admin->create_settings_field( array(
    //     'name' => 'masonry',
    //     'title' => '<label for="ctf_masonry">Set Masonry Columns as Default</label><code class="ctf_shortcode">masonry
    //         Eg: masonry=true</code>', // label for the input field
    //     'callback'  => 'default_checkbox', // name of the function that outputs the html
    //     'page' => 'ctf_options_masonry', // matches the section name
    //     'section' => 'ctf_options_masonry', // matches the section name
    //     'option' => 'ctf_options', // matches the options name
    //     'class' => 'ctf_pro',
    //     'whatis' => "This will make every Twitter feed show as masonry style columns by default"
    // ));

    // // masonry desktop columns
    // $admin->create_settings_field( array(
    //     'name' => 'masonrycols',
    //     'title' => '<label for="ctf_masonrycols">Desktop Columns</label><code class="ctf_shortcode">masonrycols
    //         Eg: masonrycols=5</code>', // label for the input field
    //     'callback'  => 'default_select', // name of the function that outputs the html
    //     'page' => 'ctf_options_masonry', // matches the section name
    //     'section' => 'ctf_options_masonry', // matches the section name
    //     'option' => 'ctf_options', // matches the options name
    //     'class' => 'ctf_pro', // class for the wrapper and input field
    //     'fields' => array(
    //         0 => array( '3', 3 ),
    //         1 => array( '4', 4 ),
    //         2 => array( '5', 5 ),
    //         3 => array( '6', 6 )
    //     ),
    //     'whatis' => "Number of vertical columns the masonry feed will use when the screen is viewed on wide screens" // what is this? text
    // ) );

    // // masonry mobile columns
    // $admin->create_settings_field( array(
    //     'name' => 'masonrymobilecols',
    //     'title' => '<label for="ctf_masonrymobilecols">Mobile Columns</label><code class="ctf_shortcode">masonrymobilecols
    //         Eg: masonrymobilecols=2</code>', // label for the input field
    //     'callback'  => 'default_select', // name of the function that outputs the html
    //     'page' => 'ctf_options_masonry', // matches the section name
    //     'section' => 'ctf_options_masonry', // matches the section name
    //     'option' => 'ctf_options', // matches the options name
    //     'class' => 'ctf_pro', // class for the wrapper and input field
    //     'fields' => array(
    //         0 => array( '1', 1 ),
    //         1 => array( '2', 2 )
    //     ),
    //     'whatis' => "Number of vertical columns the masonry feed will use when the screen is viewed on small screens" // what is this? text
    // ) );

    add_settings_section(
        'ctf_options_autoscroll', // matches the section name
        '<span class="ctf_pro_header">Autoscroll Loading</span><p class="ctf_pro_section_note"><a href="https://smashballoon.com/custom-twitter-feeds/" target="_blank">Upgrade to Pro to enable Autoscroll loading</a></p>',
        array( $admin, 'general_section_text' ), // callback function to explain the section
        'ctf_options_autoscroll' // matches the section name
    );

    // // autoscroll default
    // $admin->create_settings_field( array(
    //     'name' => 'autoscroll',
    //     'title' => '<label for="ctf_autoscroll">Set Load More on Scroll as Default</label><code class="ctf_shortcode">autoscroll
    //         Eg: autoscroll=true</code>', // label for the input field
    //     'callback'  => 'default_checkbox', // name of the function that outputs the html
    //     'page' => 'ctf_options_autoscroll', // matches the section name
    //     'section' => 'ctf_options_autoscroll', // matches the section name
    //     'option' => 'ctf_options', // matches the options name
    //     'class' => 'ctf_pro',
    //     'whatis' => "This will make every Twitter feed load more Tweets as the user gets to the bottom of the feed"
    // ));

    // // masonry mobile columns
    // $admin->create_settings_field( array(
    //     'name' => 'autoscrolldistance',
    //     'title' => '<label for="ctf_masonrymobilecols">Auto Scroll Trigger Distance</label><code class="ctf_shortcode">autoscrolldistance
    //         Eg: autoscrolldistance=2</code>', // label for the input field
    //     'callback'  => 'default_text', // name of the function that outputs the html
    //     'page' => 'ctf_options_autoscroll', // matches the section name
    //     'section' => 'ctf_options_autoscroll', // matches the section name
    //     'option' => 'ctf_options', // matches the options name
    //     'class' => 'default-text ctf_pro', // class for the wrapper and input field
    //     'whatis' => 'This is the distance in pixels from the bottom of the page the user must scroll to to trigger the loading of more tweets',
    //     'default' => '200',// "what is this?" text
    // ) );
}

add_action( 'ctf_admin_customize_option', 'ctf_add_customize_general_options', 20, 1 );
function ctf_add_customize_general_options( $admin ) {

    // Disable the lightbox
    $admin->create_settings_field( array(
        'name' => 'disablelightbox',
        'title' => '<label for="ctf_disablelightbox">Disable the lightbox</label><code class="ctf_shortcode">disablelightbox
            Eg: disablelightbox=true</code>', // label for the input field
        'callback'  => 'default_checkbox', // name of the function that outputs the html
        'page' => 'ctf_options_general', // matches the section name
        'section' => 'ctf_options_general', // matches the section name
        'option' => 'ctf_options', // matches the options name
        'class' => 'default-text ctf_pro', // class for the wrapper and input field
        'whatis' => 'Disable the popup lightbox for media in the feed'
    ) );
}


add_action( 'ctf_admin_customize_option', 'ctf_add_filter_options', 10, 1 );
function ctf_add_filter_options( $admin ) {

    add_settings_section(
        'ctf_options_filter', // matches the section name
        '<span class="ctf_pro_header">Moderation</span><p class="ctf_pro_section_note"><a href="https://smashballoon.com/custom-twitter-feeds/" target="_blank">Upgrade to Pro to enable Tweet Moderation</a></p>',
        array( $admin, 'general_section_text' ), // callback function to explain the section
        'ctf_options_filter' // matches the section name
    );

    // includewords
    // $admin->create_settings_field( array(
    //     'name' => 'includewords',
    //     'title' => '<label for="ctf_includewords">Show Tweets containing these words or hashtags</label><code class="ctf_shortcode">includewords
    //         Eg: includewords="#puppy,#cute"</code>', // label for the input field
    //     'callback'  => 'default_text', // name of the function that outputs the html
    //     'page' => 'ctf_options_filter', // matches the section name
    //     'section' => 'ctf_options_filter', // matches the section name
    //     'option' => 'ctf_options', // matches the options name
    //     'class' => 'large-text ctf_pro', // class for the wrapper and input field
    //     'default' => '',
    //     'example' => '"includewords" separate words by comma'
    // ));

    // // excludewords
    // $admin->create_settings_field( array(
    //     'name' => 'excludewords',
    //     'title' => '<label for="ctf_excludewords">Remove Tweets containing these words or hashtags</label><code class="ctf_shortcode">excludewords
    //         Eg: excludewords="#ugly,#bad"</code>', // label for the input field
    //     'callback'  => 'default_text', // name of the function that outputs the html
    //     'page' => 'ctf_options_filter', // matches the section name
    //     'section' => 'ctf_options_filter', // matches the section name
    //     'option' => 'ctf_options', // matches the options name
    //     'class' => 'large-text ctf_pro', // class for the wrapper and input field
    //     'default' => '',
    //     'example' => '"excludewords" separate words by comma'
    // ));

    // // operator
    // $admin->create_settings_field( array(
    //     'name' => 'filteroperator',
    //     'title' => '', // label for the input field
    //     'callback'  => 'ctf_filter_operator', // name of the function that outputs the html
    //     'page' => 'ctf_options_filter', // matches the section name
    //     'section' => 'ctf_options_filter', // matches the section name
    //     'option' => 'ctf_options', // matches the options name
    //     'class' => 'ctf_pro', // class for the wrapper and input field
    // ));

    // add_settings_field(
    //     'filteroperator',
    //     '',
    //     'ctf_filter_operator',
    //     'ctf_options_filter',
    //     'ctf_options_filter',
    //     array(
    //         'option' => 'ctf_options',
    //         'class' => 'ctf_pro'
    //     )
    // );

    // add_settings_field(
    //     'remove_by_id',
    //     '<label for="ctf_remove_by_id">Hide Specific Tweets</label>',
    //     'ctf_remove_by_id',
    //     'ctf_options_filter',
    //     'ctf_options_filter',
    //     array(
    //         'option' => 'ctf_options',
    //         'extra' => 'separate IDs by comma',
    //         'name' => 'remove_by_id',
    //         'whatis' => 'These are the specific ID numbers associated with a tweet. (link to example)',
    //         'class' => 'ctf_pro'
    //     )
    // );

    add_settings_field(
        'clear_tc_cache_button',
        '<label for="ctf_clear_tc_cache_button">Clear Twitter Card Cache</label>',
        'ctf_clear_tc_cache_button',
        'ctf_options_advanced',
        'ctf_options_advanced',
        array( 'class' => 'ctf_pro')
    );
}

function ctf_remove_by_id( $args ) {
    $options = get_option( $args['option'] );
    $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
    ?>
    <textarea name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" style="width: 70%;" rows="3"><?php esc_attr_e( stripslashes( $option_string ) ); ?></textarea>
    <?php if ( isset( $args['extra'] ) ) : ?><p><?php _e( $args['extra'], 'custom-twitter-feeds' ); ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <span class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</span>
        </p> <?php endif; ?>
    <?php
}

function ctf_clear_tc_cache_button() {
    ?>
    <input id="ctf-clear-tc-cache" class="button-secondary" style="margin-top: 1px;" type="submit" value="<?php esc_attr_e( 'Clear Twitter Cards' ); ?>" />
    <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
    <p class="ctf-tooltip ctf-more-info"><?php _e( 'Clicking this button will clear all cached data for your links that have Twitter Cards', 'custom-twitter-feeds' ); ?>.</p>
    <?php
}

function ctf_filter_operator( $args ) {
    $options = get_option( $args['option'] );
    $include_any_all = ( isset( $options['includeanyall'] ) ) ? esc_attr( $options['includeanyall'] ) : 'any';
    $filter_and_or = ( isset( $options['filterandor'] ) ) ? esc_attr( $options['filterandor'] ) : 'and';
    $exclude_any_all = ( isset( $options['excludeanyall'] ) ) ? esc_attr( $options['excludeanyall'] ) : 'any';

    ?>
    <p>Show Tweets that contain
        <select name="<?php echo $args['option'].'[includeanyall]'; ?>" id="ctf_includeanyall">
            <option value="any" <?php if ( $include_any_all == "any" ) echo 'selected="selected"'; ?> ><?php _e('any'); ?></option>
            <option value="all" <?php if ( $include_any_all == "all" ) echo 'selected="selected"'; ?> ><?php _e('all'); ?></option>
        </select>
        of the "includewords"
        <select name="<?php echo $args['option'].'[filterandor]'; ?>" id="ctf_filterandor">
            <option value="and" <?php if ( $filter_and_or == "and" ) echo 'selected="selected"'; ?> ><?php _e('and'); ?></option>
            <option value="or" <?php if ( $filter_and_or == "or" ) echo 'selected="selected"'; ?> ><?php _e('or'); ?></option>
        </select>
        do not contain
        <select name="<?php echo $args['option'].'[excludeanyall]'; ?>" id="ctf_excludeanyall">
            <option value="any" <?php if ( $exclude_any_all == "any" ) echo 'selected="selected"'; ?> ><?php _e('any'); ?></option>
            <option value="all" <?php if ( $exclude_any_all == "all" ) echo 'selected="selected"'; ?> ><?php _e('all'); ?></option>
        </select>
        of the "excludewords"
    </p>
    <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
    <?php
}

add_action( 'ctf_admin_add_settings_sections_to_customize', 'ctf_add_masonry_autoload_section_to_customize' );
function ctf_add_masonry_autoload_section_to_customize() {
    ?>
    <a id="masonry"></a>
    <?php do_settings_sections( 'ctf_options_masonry' ); ?>
    <hr>
    <a id="autoscroll"></a>
    <?php do_settings_sections( 'ctf_options_autoscroll' ); ?>
    <!-- <p class="submit"><input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p> -->
    <hr>
    <?php
}

add_action( 'ctf_admin_add_settings_sections_to_customize', 'ctf_add_filter_section_to_customize' );
function ctf_add_filter_section_to_customize() {
    echo '<a id="moderation"></a>';
    do_settings_sections( 'ctf_options_filter' ); // matches the section name
    echo '<hr>';
}