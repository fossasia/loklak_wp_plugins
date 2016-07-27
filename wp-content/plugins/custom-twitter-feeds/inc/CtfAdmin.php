<?php
/**
 * Class CtfAdmin
 *
 * Uses the Settings API to create easily customizable settings pages and tabs
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

class CtfAdmin
{
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'options_page_init' ) );
    }

    public function add_menu()
    {
        add_menu_page(
            'Twitter Feeds',
            'Twitter Feeds',
            'manage_options',
            'custom-twitter-feeds',
            array( $this, 'create_options_page' ),
            '',
            99
        );

        add_submenu_page(
            'custom-twitter-feeds',
            'Customize',
            'Customize',
            'manage_options',
            'custom-twitter-feeds-customize',
            array( $this, 'create_submenu_page_customize' )
        );

        add_submenu_page(
            'custom-twitter-feeds',
            'Style',
            'Style',
            'manage_options',
            'custom-twitter-feeds-style',
            array( $this, 'create_submenu_page_style' )
        );
    }

    public static function get_active_tab( $tab = '' )
    {
        switch ( $tab ) {
            case 'customize':
                return 'customize';
            case 'style':
                return 'style';
            case 'display':
                return 'display';
            case 'support':
                return 'support';
            default:
                return 'configure';
        }
    }

    public function create_options_page()
    {
        require_once CTF_URL . '/views/admin/main.php';
    }

    public function create_submenu_page_customize()
    {
        $tab = 'customize';

        require_once CTF_URL . '/views/admin/main.php';
    }

    public function create_submenu_page_style()
    {
        $tab = 'style';

        require_once CTF_URL . '/views/admin/main.php';
    }

    public function general_section_text()
    {
        // no explanation needed
    }

    public function access_token_button()
    {
        $this->the_admin_access_token_configure_html( $_GET );
        $options = get_option( 'ctf_options' );
        $option_checked = ( isset( $options['have_own_tokens'] ) ) ? $options['have_own_tokens'] : false;
        ?>
        <input name="<?php echo 'ctf_options'.'[have_own_tokens]'; ?>" id="ctf_have_own_tokens" type="checkbox" <?php if ( $option_checked ) echo "checked"; ?> />
        <label for="ctf_have_own_tokens" class="ctf_checkbox"><?php _e( 'Or, manually enter my own Twitter app information' ); ?></label>
        <span class="ctf-tooltip-wrap">
            <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
            <p class="ctf-tooltip ctf-more-info"><?php _e( 'Check this box if you would like to manually enter the information from your own <a href="https://smashballoon.com/custom-twitter-feeds/docs/create-twitter-app/" target="_blank">Twitter app</a>', 'custom-twitter-feeds' ); ?>.</p>
        </span>
        <?php
    }

    /**
     * generates the html for the access token retrieving button
     *
     * @param $access_token_data array      the $_GET data if it exists
     */
    private function the_admin_access_token_configure_html( $access_token_data ) {
        ?>

        <div id="ctf_config">

            <?php if ( isset( $access_token_data['oauth_token'] ) ) : ?>
                <a href="<?php echo OAUTH_PROCESSOR_URL . admin_url( 'admin.php?page=custom-twitter-feeds' ); ?>" id="ctf-get-token"><i class="fa fa-twitter"></i><?php _e( 'Log in to Twitter and get my Access Token and Secret' ); ?></a>
                <a class="ctf-tooltip-link" href="https://smashballoon.com/custom-twitter-feeds/token/" target="_blank"><?php _e( "Button not working?", 'custom-twitter-feeds' ); ?></a>

                <input type="hidden" id="ctf-retrieved-access-token" value="<?php echo esc_html( sanitize_text_field( $access_token_data['oauth_token'] ) ); ?>">
                <input type="hidden" id="ctf-retrieved-access-token-secret" value="<?php echo esc_html( sanitize_text_field( $access_token_data['oauth_token_secret'] ) ); ?>">
                <input type="hidden" id="ctf-retrieved-default-screen-name" value="<?php echo esc_html( sanitize_text_field( $access_token_data['screen_name'] ) ); ?>">

            <?php elseif ( isset( $access_token_data['error'] ) && ! isset( $access_token_data['oauth_token'] ) ) : ?>

                <p class="ctf_notice"><?php _e( 'There was an error with retrieving your access tokens. Please <a href="https://smashballoon.com/custom-twitter-feeds/token/" target="_blank">use this tool</a> to get your access token and secret.' ); ?></p><br>
                <a href="<?php echo OAUTH_PROCESSOR_URL . admin_url( 'admin.php?page=custom-twitter-feeds' ); ?>" id="ctf-get-token"><i class="fa fa-twitter"></i><?php _e( 'Log in to Twitter and get my Access Token and Secret' ); ?></a>
                <a class="ctf-tooltip-link" href="https://smashballoon.com/custom-twitter-feeds/token/" target="_blank"><?php _e( "Button not working?", 'custom-twitter-feeds' ); ?></a>

            <?php else : ?>

                <a href="<?php echo OAUTH_PROCESSOR_URL . admin_url( 'admin.php?page=custom-twitter-feeds' ); ?>" id="ctf-get-token"><i class="fa fa-twitter"></i><?php _e( 'Log in to Twitter and get my Access Token and Secret' ); ?></a>
                <a class="ctf-tooltip-link" href="https://smashballoon.com/custom-twitter-feeds/token/" target="_blank"><?php _e( "Button not working?", 'custom-twitter-feeds' ); ?></a>

            <?php endif; ?>

        </div>
        <?php
    }

    public function options_page_init()
    {
        /*
         * "Configure" Tab
         */

        register_setting(
            'ctf_options', // name of the option that gets called in "get_option()"
            'ctf_options', // matches the options name
            array( $this, 'validate_ctf_options' ) // callback function to validate and clean data
        );

        add_settings_section(
            'ctf_options_connect', // matches the section name
            'Configuration',
            array( $this, 'access_token_button' ), // callback function to explain the section
            'ctf_options_connect' // matches the section name
        );

        // Consumer Key
        $this->create_settings_field( array(
            'name' => 'consumer_key',
            'title' => '<label for="ctf_consumer_key">Consumer Key</label>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_connect', // matches the section name
            'section' => 'ctf_options_connect', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-toggle-consumer', // class for the wrapper and input field
            'whatis' => 'A Consumer Key and a Consumer Secret are both needed if you want to use credentials from your own Twitter App. You can create these <a href="https://smashballoon.com/custom-twitter-feeds/docs/create-twitter-app/" target="_blank">here</a>', // what is this? text
            'size' => '27'
        ) );

        // Consumer Secret
        $this->create_settings_field( array(
            'name' => 'consumer_secret',
            'title' => '<label for="ctf_consumer_secret">Consumer Secret</label>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_connect', // matches the section name
            'section' => 'ctf_options_connect', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-toggle-consumer', // class for the wrapper and input field
            'whatis' => 'A Consumer Key and a Consumer Secret are both needed if you want to use credentials from your own Twitter App. You can create these <a href="https://smashballoon.com/custom-twitter-feeds/docs/create-twitter-app/" target="_blank">here</a>', // what is this? text
            'size' => '57'
        ) );

        // Access Token
        $this->create_settings_field( array(
            'name' => 'access_token',
            'title' => '<label for="ctf_access_token">Access Token</label>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_connect', // matches the section name
            'section' => 'ctf_options_connect', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-toggle-access', // class for the wrapper and input field
            'whatis' => "This will allow the plugin to connect to the Twitter API", // "what is this?" text
            'size' => '57'
        ) );

        // Access Token Secret
        $this->create_settings_field( array(
            'name' => 'access_token_secret',
            'title' => '<label for="ctf_access_token_secret">Access Token Secret</label>', // label for the input field
            'callback'  => 'access_token_secret', // name of the function that outputs the html
            'page' => 'ctf_options_connect', // matches the section name
            'section' => 'ctf_options_connect', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-toggle-access', // class for the wrapper and input field
            'whatis' => "This will allow the plugin to connect to the Twitter API", // "what is this?" text
            'size' => '57'
        ));

        add_settings_section(
            'ctf_options_feed_settings', // matches the section name
            'Feed Settings',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_feed_settings' // matches the section name
        );

        // User Timeline Radio
        $this->create_settings_field( array(
            'name' => 'usertimeline',
            'title' => '<label for="ctf_feed_type">Feed Type</label><code class="ctf_shortcode">type
                            Eg: screenname=gopro
                            Eg: home=true
                            Eg: hashtag=#cats</code>', // label for the input field
            'callback'  => 'feed_settings_radio', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-radio', // class for the wrapper and input field
            'whatis' => "Select this option and enter any screen name to create a user timeline feed", // what is this? text
            'label' => "User Timeline:",
            'has_input' => true,
            'has_replies' => true
        ));

        // Home Timeline Radio
        $this->create_settings_field( array(
            'name' => 'hometimeline',
            'title' => '<label></label>', // label for the input field
            'callback'  => 'feed_settings_radio', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-radio', // class for the wrapper and input field
            'whatis' => 'Select this option to display tweets from yourself and those you follow', // what is this? text
            'label' => "Home Timeline",
            'has_input' => false,
            'has_replies' => true
        ));
        
        // Search Radio
        $search_label = apply_filters( 'ctf_admin_search_label', '' );
        $search_whatis = apply_filters( 'ctf_admin_search_whatis', '' );
        $this->create_settings_field( array(
            'name' => 'search',
            'title' => '<label></label>', // label for the input field
            'callback'  => 'feed_settings_radio_search', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'ctf-radio', // class for the wrapper and input field
            'whatis' => $search_whatis, // what is this? text
            'label' => $search_label,
            'has_input' => true,
            'extra' => true
        ) );

        do_action( 'ctf_admin_endpoints', $this );

        // Number of Tweets
        $this->create_settings_field( array(
            'name' => 'num',
            'title' => '<label for="ctf_num">How Many Tweets to Display</label><code class="ctf_shortcode">num
            Eg: num=10</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text', // class for the wrapper and input field
            'whatis' => "Enter the number of tweets you would like to display when the feed first loads", // what is this? text
            'type' => 'number', // input field "type" attribute
            'default' => 5
        ));

        // time unit for cache
        $this->create_settings_field( array(
            'name' => 'cache_time',
            'title' => '<label for="ctf_cache_time">How Many Tweets to Display</label>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text', // class for the wrapper and input field
            'whatis' => "Enter the number of tweets you would like to display when the feed first loads", // what is this? text
            'type' => 'number' // input field "type" attribute
        ));

        // check for new tweets
        $this->create_settings_field( array(
            'name' => 'cache_time',
            'title' => '<label for="ctf_cache_time">Check for new tweets every</label>', // label for the input field
            'callback'  => 'cache_time', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'short-text', // class for the wrapper and input field
            'whatis' => "Your Tweets are temporarily cached by the plugin in your WordPress database. You can choose how long the posts should be cached for. If you set the time to 1 hour then the plugin will clear the cache after that length of time and check Instagram for posts again" // what is this? text
        ) );

        // preserve settings
        $this->create_settings_field( array(
            'name' => 'preserve_settings',
            'title' => '<label for="ctf_preserve_settings">Preserve settings when plugin is removed</label>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "When removing the plugin your settings are automatically erased. Checking this box will prevent any settings from being deleted. This means that you can uninstall and reinstall the plugin without losing your settings"
        ));

        // ajax theme
        $this->create_settings_field( array(
            'name' => 'ajax_theme',
            'title' => '<label for="ctf_ajax_theme">Are you using an Ajax powered theme?</label>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_feed_settings', // matches the section name
            'section' => 'ctf_options_feed_settings', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "When navigating your site, if your theme uses Ajax to load content into your pages (meaning your page doesn't refresh) then check this setting. If you're not sure then please check with the theme author"
        ));

        /*
         * "Customize" tab
         */

        add_settings_section(
            'ctf_options_general', // matches the section name
            'General',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_general' // matches the section name
        );

        // width
        $this->create_settings_field( array(
            'name' => 'width',
            'title' => '<label for="ctf_width">Width of Feed</label><code class="ctf_shortcode">width
            Eg: width=500</code>', // label for the input field
            'callback'  => 'width_and_height_settings', // name of the function that outputs the html
            'page' => 'ctf_options_general', // matches the section name
            'section' => 'ctf_options_general', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text',
            'default' => '100',
            'default_unit' => '%'
        ));

        // height
        $this->create_settings_field( array(
            'name' => 'height',
            'title' => '<label for="ctf_height">Height of Feed</label><code class="ctf_shortcode">height
            Eg: height=1000</code>', // label for the input field
            'callback'  => 'width_and_height_settings', // name of the function that outputs the html
            'page' => 'ctf_options_general', // matches the section name
            'section' => 'ctf_options_general', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text',
            'default_unit' => 'px'
        ));

        // class
        $this->create_settings_field( array(
            'name' => 'class',
            'title' => '<label for="ctf_class">Add Custom CSS Class</label><code class="ctf_shortcode">class
            Eg: class="my-class"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_general', // matches the section name
            'section' => 'ctf_options_general', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text',
            'type' => 'text',
            'whatis' => "You can add your own CSS classes to the feed here. To add multiple classes separate each with a space, Eg. classone classtwo classthree"
        ));

        add_settings_section(
            'ctf_options_showandhide', // matches the section name
            'Show/Hide',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_showandhide' // matches the section name
        );

        // show/hide
        $show_hide_list = array(
            0 => array( 'include_retweeter', 'Retweeted text' ),
            1 => array( 'include_avatar', 'Avatar image' ),
            2 => array( 'include_author', 'Author name' ),
            3 => array( 'include_text', 'Tweet text' ),
            4 => array( 'include_date', 'Date' ),
            5 => array( 'include_actions', 'Tweet actions (reply, retweet, like)' ),
            6 => array( 'include_twitterlink', '"Twitter" link' ),
            7 => array( 'include_linkbox', 'Quoted tweet box' )
        );
        $show_hide_list = apply_filters( 'ctf_admin_show_hide_list', $show_hide_list );

        $this->create_settings_field( array(
            'name' => 'showandhide',
            'title' => '<label>Include the Following in Tweets <em>(when applicable)</em></label><code class="ctf_shortcode">include exclude
            Eg: include=author,date
            Eg: exclude=actions
            Options: avatar, author, text,
            date, actions, linkbox </code>', // label for the input field
            'callback'  => 'include_exclude_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_showandhide', // matches the section name
            'section' => 'ctf_options_showandhide', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'fields' => $show_hide_list,
            'class' => ''
        ));

        // show header
        $this->create_settings_field( array(
            'name' => 'showheader',
            'title' => '<label for="ctf_showheader">Show Header</label><code class="ctf_shortcode">showheader
            Eg: showheader=true</code>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_showandhide', // matches the section name
            'section' => 'ctf_options_showandhide', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The header is displayed above your tweets with some basic information about the feed"
        ));

        // load more button
        $this->create_settings_field( array(
            'name' => 'showbutton',
            'title' => '<label for="ctf_showbutton">Show the "Load More" Button</label><code class="ctf_shortcode">showbutton
            Eg: showbutton=true</code>', // label for the input field
            'callback'  => 'reverse_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_showandhide', // matches the section name
            'section' => 'ctf_options_showandhide', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "Show the Load More Button",
        ));


        // credit ctf
        $this->create_settings_field( array(
            'name' => 'creditctf',
            'title' => '<label for="ctf_creditctf">Add Custom Twitter Feeds Credit</label><code class="ctf_shortcode">creditctf
            Eg: creditctf=true</code>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_showandhide', // matches the section name
            'section' => 'ctf_options_showandhide', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "Help us keep this plugin great! Add a link below your feed to credit Custom Twitter Feeds by Smash Balloon"
        ));

        do_action( 'ctf_admin_customize_option', $this );

        add_settings_section(
            'ctf_options_misc', // matches the section name
            'Misc',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_misc' // matches the section name
        );

        // Custom CSS
        $this->create_settings_field( array(
            'name' => 'custom_css',
            'title' => '<label for="ctf_custom_css">Custom CSS</label>', // label for the input field
            'callback'  => 'custom_code', // name of the function that outputs the html
            'page' => 'ctf_options_misc', // matches the section name
            'section' => 'ctf_options_misc', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'description' => 'Enter your own custom CSS in the box below'
        ));

        // Custom JS
        $this->create_settings_field( array(
            'name' => 'custom_js',
            'title' => '<label for="ctf_custom_js">Custom Javascript*</label>', // label for the input field
            'callback'  => 'custom_code', // name of the function that outputs the html
            'page' => 'ctf_options_misc', // matches the section name
            'section' => 'ctf_options_misc', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'description' => 'Enter your own custom Javascript/JQuery in the box below',
            'extra' => '*will be fired every time more tweets are loaded'
        ));

        add_settings_section(
            'ctf_options_advanced', // matches the section name
            'Advanced',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_advanced' // matches the section name
        );

        // Request Method
        $this->create_settings_field( array(
            'name' => 'request_method',
            'title' => '<label for="ctf_request_method">Request Method</label>', // label for the input field
            'callback'  => 'default_select', // name of the function that outputs the html
            'page' => 'ctf_options_advanced', // matches the section name
            'section' => 'ctf_options_advanced', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'fields' => array(
                1 => array( 'auto', 'Auto' ),
                2 => array( 'curl', 'cURL' ),
                3 => array( 'file_get_contents', 'file_get_contents()' ),
                4 => array( 'wp_http', 'WP_Http' )
            ),
            'whatis' => "Explicitly set the request method. You would only want to change this if you are unable to connect to the Twitter API" // what is this? text
        ) );

        // force cache to clear on interval
        $this->create_settings_field( array(
            'name' => 'cron_cache_clear',
            'title' => '<label for="ctf_cron_cache_clear">Force cache to clear on interval</label>', // label for the input field
            'callback'  => 'default_select', // name of the function that outputs the html
            'page' => 'ctf_options_advanced', // matches the section name
            'section' => 'ctf_options_advanced', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'fields' => array(
                1 => array( 'unset', '-' ),
                2 => array( 'yes', 'Yes' ),
                3 => array( 'no', 'No' )
            ),
            'whatis' => "If you're experiencing an issue with the plugin not auto-updating then you can set this to 'Yes' to run a scheduled event behind the scenes which forces the plugin cache to clear on a regular basis and retrieve new data from Twitter" // what is this? text
        ) );

        // tweet multiplier
        $this->create_settings_field( array(
            'name' => 'multiplier',
            'title' => '<label for="ctf_multiplier">Tweet Multiplier</label><code class="ctf_shortcode">multiplier
            Eg: multiplier=1.5</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_advanced', // matches the section name
            'section' => 'ctf_options_advanced', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'small-text', // class for the wrapper and input field
            'whatis' => "If your feed excludes reply tweets (this is automatic in hashtag/search feeds), the correct number of tweets may now show up. Increasing this number will increase the number of tweets retrieved but will also increase the load time for the feed as well", // what is this? text
            'type' => 'number', // input field "type" attribute
            'min' => 1,
            'max' => 3,
            'step' => 'any',
            'default' => 1.25
        ));

        /**
         *  "Style" tab
         */

        add_settings_section(
            'ctf_options_general_style', // matches the section name
            'General',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_general_style' // matches the section name
        );

        // background color
        $this->create_settings_field( array(
            'name' => 'bgcolor',
            'title' => '<label for="ctf_bgcolor">Feed Background Color</label><code class="ctf_shortcode">bgcolor
            Eg: bgcolor=#eee</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_general_style', // matches the section name
            'section' => 'ctf_options_general_style', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The background color of the feed"
        ));

        // tweet background color
        $this->create_settings_field( array(
            'name' => 'tweetbgcolor',
            'title' => '<label for="ctf_tweetbgcolor">Tweet Background Color</label><code class="ctf_shortcode">tweetbgcolor
            Eg: tweetbgcolor=#eee</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_general_style', // matches the section name
            'section' => 'ctf_options_general_style', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The background color of each tweet"
        ));

        add_settings_section(
            'ctf_options_header', // matches the section name
            'Header',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_header' // matches the section name
        );

        // show bio
        $this->create_settings_field( array(
            'name' => 'showbio',
            'title' => '<label for="ctf_showbio">Show Bio</label><code class="ctf_shortcode">showbio
            Eg: showbio=false</code>', // label for the input field
            'callback'  => 'reverse_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_header', // matches the section name
            'section' => 'ctf_options_header', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => "Show the bio text description on the header of the feed"
        ));

        // header background color
        $this->create_settings_field( array(
            'name' => 'headerbgcolor',
            'title' => '<label for="ctf_headerbgcolor">Header Background Color</label><code class="ctf_shortcode">headerbgcolor
            Eg: headerbgcolor=#ee0</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_header', // matches the section name
            'section' => 'ctf_options_header', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => ''
        ));

        // header text color
        $this->create_settings_field( array(
            'name' => 'headertextcolor',
            'title' => '<label for="ctf_headertextcolor">Header Text Color</label><code class="ctf_shortcode">headertextcolor
            Eg: headertextcolor=#444</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_header', // matches the section name
            'section' => 'ctf_options_header', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => ''
        ));


        // custom header text
        $this->create_settings_field( array(
            'name' => 'headertext',
            'title' => '<label for="ctf_headertext">Custom Header Text</label><code class="ctf_shortcode">headertext
            Eg: headertext="Tweets from @SmashBalloon"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_header', // matches the section name
            'section' => 'ctf_options_header', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => 'This will replace the default text displayed inside the optional header of the feed'  // "what is this?" text
        ));

        add_settings_section(
            'ctf_options_date', // matches the section name
            'Date',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_date' // matches the section name
        );

        // Timezone
        $this->create_settings_field( array(
            'name' => 'timezone',
            'title' => '<label for="ctf_timezone">Timezone</label>', // label for the input field
            'callback'  => 'feed_settings_timezone', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => "Select a timezone for displaying date and timestamps of tweets" // what is this? text
        ));

        // Date Format
        $this->create_settings_field( array(
            'name' => 'dateformat',
            'title' => '<label for="ctf_date_format">Date Format</label><code class="ctf_shortcode">dateformat
            Eg: dateformat=3</code>', // label for the input field
            'callback'  => 'customize_date_format', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => "Select the format you would like for dates in tweets" // what is this? text
        ));

        // Custom Date Format
        $this->create_settings_field( array(
            'name' => 'customdateformat',
            'title' => '<label for="ctf_custom_date_format">Custom Format</label><code class="ctf_shortcode">datecustom
            Eg: datecustom="D M jS, Y"</code>', // label for the input field
            'callback'  => 'customize_custom_date_format', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // Custom Time Translations
        $this->create_settings_field( array(
            'name' => 'custom_time_translations',
            'title' => '<label>Custom Time Translations</label><code class="ctf_shortcode">mtime, htime,
            nowtime
            Eg: mtime="M"
            Eg: htime="S"
            Eg: nowtime="Jetzt"</code>', // label for the input field
            'callback'  => 'customize_custom_time_translations', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // date Text Size
        $this->create_settings_field( array(
            'name' => 'datetextsize',
            'title' => '<label for="ctf_datetextsize">Date Text Size</label><code class="ctf_shortcode">datetextsize
            Eg: datetextsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // date text weight
        $this->create_settings_field( array(
            'name' => 'datetextweight',
            'title' => '<label for="ctf_datetextweight">Date Text Weight</label><code class="ctf_shortcode">datetextweight
            Eg: datetextweight=bold</code>', // label for the input field
            'callback'  => 'text_weight', // name of the function that outputs the html
            'page' => 'ctf_options_date', // matches the section name
            'section' => 'ctf_options_date', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        add_settings_section(
            'ctf_options_author', // matches the section name
            'Author',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_author' // matches the section name
        );

        // Author Text Size
        $this->create_settings_field( array(
            'name' => 'authortextsize',
            'title' => '<label for="ctf_authortextsize">Author Text Size</label><code class="ctf_shortcode">authortextsize
            Eg: authortextsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_author', // matches the section name
            'section' => 'ctf_options_author', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // author text weight
        $this->create_settings_field( array(
            'name' => 'authortextweight',
            'title' => '<label for="ctf_authortextcolor">Author Text Weight</label><code class="ctf_shortcode">authortextweight
            Eg: authortextweight=bold</code>', // label for the input field
            'callback'  => 'text_weight', // name of the function that outputs the html
            'page' => 'ctf_options_author', // matches the section name
            'section' => 'ctf_options_author', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        add_settings_section(
            'ctf_options_text', // matches the section name
            'Tweet Text',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_text' // matches the section name
        );

        // Tweet Text Size
        $this->create_settings_field( array(
            'name' => 'tweettextsize',
            'title' => '<label for="ctf_tweettextsize">Tweet Text Size</label><code class="ctf_shortcode">tweettextsize
            Eg: tweettextsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_text', // matches the section name
            'section' => 'ctf_options_text', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // tweet text weight
        $this->create_settings_field( array(
            'name' => 'tweettextweight',
            'title' => '<label for="ctf_tweettextweight">Tweet Text Weight</label><code class="ctf_shortcode">tweettextweight
            Eg: tweettextweight=bold</code>', // label for the input field
            'callback'  => 'text_weight', // name of the function that outputs the html
            'page' => 'ctf_options_text', // matches the section name
            'section' => 'ctf_options_text', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        // text color
        $this->create_settings_field( array(
            'name' => 'textcolor',
            'title' => '<label for="ctf_textcolor">Text Color</label><code class="ctf_shortcode">textcolor
            Eg: textcolor=#333</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_text', // matches the section name
            'section' => 'ctf_options_text', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        // custom retweeted text
        $this->create_settings_field( array(
            'name' => 'retweetedtext',
            'title' => '<label for="ctf_retweetedtext">Translation for "Retweeted"</label><code class="ctf_shortcode">retweetedtext
            Eg: retweetedtext="retuiteó"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_text', // matches the section name
            'section' => 'ctf_options_text', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'whatis' => 'This will replace the default text displayed for retweeted texts',
            'default' => 'Retweeted'// "what is this?" text
        ));

        add_settings_section(
            'ctf_options_links', // matches the section name
            'Links',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_links' // matches the section name
        );

        // disable links
        $this->create_settings_field( array(
            'name' => 'disablelinks',
            'title' => '<label for="ctf_disablelinks">Disable Links in Tweet Text</label><code class="ctf_shortcode">disablelinks
            Eg: disablelinks=true</code>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_links', // matches the section name
            'section' => 'ctf_options_links', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "By default, links, hashtags, and mentions are turned into links inside the tweet text"
        ));

        // link text to twitter
        $this->create_settings_field( array(
            'name' => 'linktexttotwitter',
            'title' => '<label for="ctf_linktexttotwitter">Link Tweet Text to Twitter</label><code class="ctf_shortcode">linktexttotwitter
            Eg: linktexttotwitter=true</code>', // label for the input field
            'callback'  => 'default_checkbox', // name of the function that outputs the html
            'page' => 'ctf_options_links', // matches the section name
            'section' => 'ctf_options_links', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "Clicking on the text of the tweet will link to the tweet on Twitter"
        ));

        // link text color
        $this->create_settings_field( array(
            'name' => 'linktextcolor',
            'title' => '<label for="ctf_linktextcolor">Links in Tweets Text Color</label><code class="ctf_shortcode">linktextcolor
            Eg: linktextcolor=#00e</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_links', // matches the section name
            'section' => 'ctf_options_links', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        add_settings_section(
            'ctf_options_quoted', // matches the section name
            'Retweet Boxes',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_quoted' // matches the section name
        );

        // quoted author Size
        $this->create_settings_field( array(
            'name' => 'quotedauthorsize',
            'title' => '<label for="ctf_quotedauthorsize">Quoted Author Size</label><code class="ctf_shortcode">quotedauthorsize
            Eg: quotedauthorsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_quoted', // matches the section name
            'section' => 'ctf_options_quoted', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // quoted author weight
        $this->create_settings_field( array(
            'name' => 'quotedauthorweight',
            'title' => '<label for="ctf_quotedauthorweight">Quoted Author Weight</label><code class="ctf_shortcode">quotedauthorweight
            Eg: quotedauthorweight=bold</code>', // label for the input field
            'callback'  => 'text_weight', // name of the function that outputs the html
            'page' => 'ctf_options_quoted', // matches the section name
            'section' => 'ctf_options_quoted', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));

        add_settings_section(
            'ctf_options_actions', // matches the section name
            'Tweets Actions',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_actions' // matches the section name
        );

        // icon Size
        $this->create_settings_field( array(
            'name' => 'iconsize',
            'title' => '<label for="ctf_iconsize">Icon Size</label><code class="ctf_shortcode">iconsize
            Eg: iconsize=16</code>', // label for the input field
            'callback'  => 'text_size', // name of the function that outputs the html
            'page' => 'ctf_options_actions', // matches the section name
            'section' => 'ctf_options_actions', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
        ));

        // icon color
        $this->create_settings_field( array(
            'name' => 'iconcolor',
            'title' => '<label for="ctf_iconcolor">Icon Color</label><code class="ctf_shortcode">iconcolor
            Eg: iconcolor=green</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_actions', // matches the section name
            'section' => 'ctf_options_actions', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
        ));


        // view on twitter text
        $this->create_settings_field( array(
            'name' => 'twitterlinktext',
            'title' => '<label for="ctf_twitterlinktext">Custom Text for "Twitter" Link</label><code class="ctf_shortcode">twitterlinktext
            Eg: twitterlinktext="View this Tweet"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_actions', // matches the section name
            'section' => 'ctf_options_actions', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'default' => 'Twitter'
        ));

        add_settings_section(
            'ctf_options_load', // matches the section name
            '"Load More" Button',
            array( $this, 'general_section_text' ), // callback function to explain the section
            'ctf_options_load' // matches the section name
        );

        // button background color
        $this->create_settings_field( array(
            'name' => 'buttoncolor',
            'title' => '<label for="ctf_buttoncolor">Button Background Color</label><code class="ctf_shortcode">buttoncolor
            Eg: buttoncolor=#f33</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_load', // matches the section name
            'section' => 'ctf_options_load', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The color of the background of the load more button"
        ));

        // button text color
        $this->create_settings_field( array(
            'name' => 'buttontextcolor',
            'title' => '<label for="ctf_buttontextcolor">Button Text Color</label><code class="ctf_shortcode">buttontextcolor
            Eg: buttontextcolor=#444</code>', // label for the input field
            'callback'  => 'default_color', // name of the function that outputs the html
            'page' => 'ctf_options_load', // matches the section name
            'section' => 'ctf_options_load', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => '',
            'whatis' => "The color of the text of the load more button"
        ));

        // button text
        $this->create_settings_field( array(
            'name' => 'buttontext',
            'title' => '<label for="ctf_buttontext">Button Text</label><code class="ctf_shortcode">buttontext
            Eg: buttontext="More"</code>', // label for the input field
            'callback'  => 'default_text', // name of the function that outputs the html
            'page' => 'ctf_options_load', // matches the section name
            'section' => 'ctf_options_load', // matches the section name
            'option' => 'ctf_options', // matches the options name
            'class' => 'default-text', // class for the wrapper and input field
            'default' => 'Load More...'
        ));

        do_action( 'ctf_admin_style_option', $this );
    }

    public function create_settings_field( $args=array() )
    {
        add_settings_field(
            $args['name'],
            $args['title'],
            array( $this, $args['callback'] ),
            $args['page'],
            $args['section'],
            $args
        );
    }

    public function default_text( $args )
    {
        $options = get_option( $args['option'] );
        $default = isset( $args['default'] ) ? $args['default'] : '';
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $default;
        $type = ( isset( $args['type'] ) ) ? ' type="'. $args['type'].'"' : ' type="text"';
        $size = ( isset( $args['size'] ) ) ? ' size="'. $args['size'].'"' : '';
        $min = ( isset( $args['min'] ) ) ? ' min="'. $args['min'].'"' : '';
        $max = ( isset( $args['max'] ) ) ? ' max="'. $args['max'].'"' : '';
        $step = ( isset( $args['step'] ) ) ? ' step="'. $args['step'].'"' : '';
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>"<?php echo $type; ?><?php echo $size; ?><?php echo $min; ?><?php echo $max; ?><?php echo $step; ?> value="<?php echo $option_string; ?>" />
        <?php if ( isset( $args['example'] ) ) : ?>
        <span><?php echo $args['example']; ?></span>
    <?php endif; ?>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function default_select( $args )
    {
        $options = get_option( $args['option'] );
        $selected = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>">
            <?php foreach ( $args['fields'] as $field ) : ?>
                <option value="<?php echo $field[0]; ?>" id="ctf-<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>"<?php if( $selected == $field[0] ) { echo ' selected'; } ?>><?php _e( $field[1], 'custom-twitter-feeds' ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function default_color( $args )
    {
        $options = get_option( $args['option'] );
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" value="#<?php esc_attr_e( str_replace('#', '', $option_string ) ); ?>" class="ctf-colorpicker" />
        <?php
    }

    public function default_checkbox( $args )
    {
        $options = get_option( $args['option'] );
        $option_checked = ( isset( $options[ $args['name'] ] ) ) ? $options[ $args['name'] ] : false;
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" type="checkbox" <?php if ( $option_checked ) echo "checked"; ?> />
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function reverse_checkbox( $args )
    {
        $options = get_option( $args['option'] );
        $option_checked = isset( $options[ $args['name'] ] ) ? $options[ $args['name'] ] : true;
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" type="checkbox" <?php if ( $option_checked ) echo "checked"; ?> />
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function access_token_secret( $args )
    {
        $options = get_option( $args['option'] );
        $default = isset( $args['default'] ) ? $args['default'] : '';
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $default;
        $option_checked = ( isset( $options['use_own_consumer'] ) ) ? $options['use_own_consumer'] : false;
        $type = ( isset( $args['type'] ) ) ? ' type="'. $args['type'].'"' : ' type="text"';
        $size = ( isset( $args['size'] ) ) ? ' size="'. $args['size'].'"' : '';
        $min = ( isset( $args['min'] ) ) ? ' min="'. $args['min'].'"' : '';
        $max = ( isset( $args['max'] ) ) ? ' max="'. $args['max'].'"' : '';
        $step = ( isset( $args['step'] ) ) ? ' step="'. $args['step'].'"' : '';
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>"<?php echo $type; ?><?php echo $size; ?><?php echo $min; ?><?php echo $max; ?><?php echo $step; ?> value="<?php echo $option_string; ?>" />
        <?php if ( isset( $args['example'] ) ) : ?>
        <span><?php echo $args['example']; ?></span>
    <?php endif; ?>

        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>

        <?php
    }

    public function feed_settings_radio( $args )
    {
        $options = get_option( $args['option'] );
        $option_checked = ( ( ! isset( $options[ 'type' ] ) && $args['name'] == 'usertimeline' ) || ( isset( $options[ 'type' ] ) && $options[ 'type' ] == $args['name'] ) ) ? true : false;
        $show_replies = ( isset( $options[ $args['name'].'_includereplies' ] ) ) ? $options[ $args['name'].'_includereplies' ] : false;
        $option_string = ( isset( $options[ $args['name'].'_text' ] ) ) ? esc_attr( $options[ $args['name'].'_text' ] ) : '';
        ?>
        <input type="radio" name="<?php echo $args['option'].'[type]'; ?>" class="ctf-feed-settings-radio" id="ctf_<?php echo $args['name'].'_radio'; ?>" value="<?php echo $args['name']; ?>" <?php if ( $option_checked ) echo "checked"; ?> />
        <label class="ctf-radio-label" for="ctf_<?php echo $args['name'].'_radio'; ?>"><?php _e( $args['label'], 'custom-twitter-feeds' ); ?></label>
        <?php if ( $args['has_input'] ) : ?>
        <input name="<?php echo $args['option'].'['.$args['name'].'_text'.']'; ?>" id="ctf_<?php echo $args['name'].'_text'; ?>" type="text" value="<?php esc_attr_e( $option_string ); ?>" size="25" />
    <?php endif; ?>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php if ( $args['has_replies'] ) : ?>
        <span class="ctf_include_replies_toggle ctf_pro">
            <input name="<?php echo $args['option'].'['.$args['name'].'_includereplies]'; ?>" id="ctf_include_replies" type="checkbox" <?php if ( $show_replies ) echo "checked"; ?> />
            <label class="ctf-radio-label" for="ctf_include_replies"><?php _e( 'Include replies', 'custom-twitter-feeds' ); ?></label>
            <?php do_action( 'ctf_admin_upgrade_note' ); ?>
        </span>
    <?php endif; ?>
        <?php
        do_action( 'ctf_admin_feed_settings_radio_extra', $args );
    }

    public function feed_settings_radio_search( $args )
    {
        $options = get_option( $args['option'] );
        $option_checked = ( ( ! isset( $options[ 'type' ] ) && $args['name'] == 'usertimeline' ) || ( isset( $options[ 'type' ] ) && $options[ 'type' ] == $args['name'] ) ) ? true : false;
        $option_string = ( isset( $options[ $args['name'].'_text' ] ) ) ? esc_attr( $options[ $args['name'].'_text' ] ) : '';
        ?>
        <input type="radio" name="<?php echo $args['option'].'[type]'; ?>" class="ctf-feed-settings-radio" id="ctf_<?php echo $args['name'].'_radio'; ?>" value="<?php echo $args['name']; ?>" <?php if ( $option_checked ) echo "checked"; ?> />
        <label class="ctf-radio-label" for="ctf_<?php echo $args['name'].'_radio'; ?>"><?php echo $args['label']; ?></label>
        <?php if ( $args['has_input'] ) : ?>
        <input name="<?php echo $args['option'].'['.$args['name'].'_text'.']'; ?>" id="ctf_<?php echo $args['name'].'_text'; ?>" type="text" value="<?php esc_attr_e( $option_string ); ?>" size="25" />
    <?php endif; ?>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
        do_action( 'ctf_admin_feed_settings_search_extra' );
    }

    public function width_and_height_settings( $args )
    {
        $options = get_option( $args['option'] );
        $default = isset( $args['default'] ) ? $args['default'] : '';
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $default;
        $selected = ( isset( $options[ $args['name'] . '_unit' ] ) ) ? esc_attr( $options[ $args['name'] . '_unit' ] ) : $args['default_unit'];
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>" type="number" value="<?php echo $option_string; ?>" />
        <select name="<?php echo $args['option'].'['.$args['name'].'_unit]'; ?>" id="ctf_<?php echo $args['name'].'_unit'; ?>">
            <option value="px" <?php if ( $selected == "px" ) echo 'selected="selected"' ?> >px</option>
            <option value="%" <?php if ( $selected == "%" ) echo 'selected="selected"' ?> >%</option>
        </select>

        <?php if ( $args['name'] == 'width' ) :
        $checked = ( isset( $options[ $args['name'] . '_mobile_no_fixed' ] ) ) ? esc_attr( $options[ $args['name'] . '_mobile_no_fixed' ] ) : false; ?>
        <div id="ctf_width_options">
            <input name="<?php echo $args['option'].'[width_mobile_no_fixed]'; ?>" type="checkbox" id="ctf_width_mobile_no_fixed" <?php if ( $checked == true ) { echo "checked"; }?> /><label for="ctf_width_mobile_no_fixed"><?php _e('Set to be 100% width on mobile?', 'custom-twitter-feeds'); ?></label>
            <a class="ctf-tooltip-link" href="JavaScript:void(0);"><?php _e('What does this mean?', 'custom-facebook-feed'); ?></a>
            <p class="ctf-tooltip ctf-more-info"><?php _e("If you set a width on the feed then this will be used on mobile as well as desktop. Check this setting to set the feed width to be 100% on mobile so that it is responsive.", 'custom-twitter-feeds'); ?></p>
        </div>
    <?php endif; ?>
        <?php
    }

    public function cache_time( $args )
    {
        $options = get_option( $args['option'] );
        $default = 1;
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : $default;
        $selected = ( isset( $options[ $args['name'] . '_unit' ] ) ) ? esc_attr( $options[ $args['name'] . '_unit' ] ) : '3600';
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" class="<?php echo $args['class']; ?>" type="number" value="<?php echo $option_string; ?>" />
        <select name="<?php echo $args['option'].'['.$args['name'].'_unit]'; ?>">
            <option value="60" <?php if ( $selected == "60" ) echo 'selected="selected"' ?> ><?php esc_attr_e( 'Minutes' ); ?></option>
            <option value="3600" <?php if ( $selected == "3600" ) echo 'selected="selected"' ?> ><?php esc_attr_e( 'Hours' ); ?></option>
            <option value="86400" <?php if ( $selected == "86400" ) echo 'selected="selected"' ?> ><?php esc_attr_e( 'Days' ); ?></option>
        </select>&nbsp;
        <input id="ctf-clear-cache" class="button-secondary" style="margin-top: 1px;" type="submit" value="<?php esc_attr_e( 'Clear Twitter Cache' ); ?>" />
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( 'Clicking this button will clear all cached data for your Twitter feeds', 'custom-twitter-feeds' ); ?>.</p>
        <?php
    }

    public function customize_date_format( $args )
    {
        $options = get_option( $args['option'] );
        $ctf_date_formatting = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        $original = strtotime( '2016-02-25T17:30:00+0000' );
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>">
            <option value="1" <?php if ( $ctf_date_formatting == "1" ) echo 'selected="selected"'; ?> ><?php _e( '2h / 25 Feb' ); ?></option>
            <option value="2" <?php if ( $ctf_date_formatting == "2" ) echo 'selected="selected"'; ?> ><?php echo date( 'F j', $original ); ?></option>
            <option value="3" <?php if ( $ctf_date_formatting == "3" ) echo 'selected="selected"'; ?> ><?php echo date( 'F j, Y', $original ); ?></option>
            <option value="4" <?php if ( $ctf_date_formatting == "4" ) echo 'selected="selected"'; ?> ><?php echo date( 'm.d', $original ); ?></option>
            <option value="5" <?php if ( $ctf_date_formatting == "5" ) echo 'selected="selected"'; ?> ><?php echo date( 'm.d.y', $original ); ?></option>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function customize_custom_date_format( $args )
    {
        $options = get_option( $args['option'] );
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <input name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" type="text" value="<?php esc_attr_e( $option_string ); ?>" size="10" placeholder="Eg. F jS, Y" />
        <a href="https://smashballoon.com/custom-twitter-feeds/docs/date/" class="cff-external-link" target="_blank"><?php _e( 'Examples' , 'custom-twitter-feeds'); ?></a>
        <?php
    }

    public function customize_custom_time_translations( $args )
    {
        $options = get_option( $args['option'] );
        $option_m = ( isset( $options['mtime'] ) ) ? esc_attr( $options['mtime'] ) : '';
        $option_h = ( isset( $options['htime'] ) ) ? esc_attr( $options['htime'] ) : '';
        $option_now = ( isset( $options['nowtime'] ) ) ? esc_attr( $options['nowtime'] ) : '';

        ?>
        <input name="<?php echo $args['option'].'[mtime]'; ?>" id="ctf_translate_minute" type="text" value="<?php esc_attr_e( $option_m ); ?>" size="5" />
        <label for=ctf_translate_minute"><?php _e( 'translation for "m" (minutes)', 'custom-twitter-feeds' ); ?></label><br>
        <input name="<?php echo $args['option'].'[htime]'; ?>" id="ctf_translate_hour" type="text" value="<?php esc_attr_e( $option_h ); ?>" size="5" />
        <label for=ctf_translate_hour"><?php _e( 'translation for "h" (hours)', 'custom-twitter-feeds' ); ?></label><br>
        <input name="<?php echo $args['option'].'[nowtime]'; ?>" id="ctf_translate_now" type="text" value="<?php esc_attr_e( $option_now ); ?>" size="5" />
        <label for=ctf_translate_now"><?php _e( 'translation for "now"', 'custom-twitter-feeds' ); ?></label><br>
        <?php
    }

    public function include_exclude_checkbox( $args )
    {
        $options = get_option( $args['option'] );
        foreach ( $args['fields'] as $field ) {
            $option_checked = isset(  $options[$field[0]] ) ?  $options[$field[0]] : true;
            ?>
            <input name="<?php echo $args['option'] . '[' . $field[0] . ']'; ?>"
                   id="ctf_<?php echo $field[0]; ?>" type="checkbox"
                <?php if ( $option_checked ) {
                    echo "checked";
                } ?> />
            <label for=ctf_<?php echo $field[0]; ?>"><?php _e( $field[1], 'custom-twitter-feeds' ); ?></label><br>
            <?php
        } // end foreach
    }

    public function text_size( $args )
    {
        $options = get_option( $args['option'] );
        $ctf_text_size = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>">
            <option value="inherit" <?php if ( $ctf_text_size == "inherit" ) echo 'selected="selected"' ?> >Inherit</option>
            <option value="10" <?php if ( $ctf_text_size == "10" ) echo 'selected="selected"' ?> >10px</option>
            <option value="11" <?php if ( $ctf_text_size == "11" ) echo 'selected="selected"' ?> >11px</option>
            <option value="12" <?php if ( $ctf_text_size == "12" ) echo 'selected="selected"' ?> >12px</option>
            <option value="13" <?php if ( $ctf_text_size == "13" ) echo 'selected="selected"' ?> >13px</option>
            <option value="14" <?php if ( $ctf_text_size == "14" ) echo 'selected="selected"' ?> >14px</option>
            <option value="16" <?php if ( $ctf_text_size == "16" ) echo 'selected="selected"' ?> >16px</option>
            <option value="18" <?php if ( $ctf_text_size == "18" ) echo 'selected="selected"' ?> >18px</option>
            <option value="20" <?php if ( $ctf_text_size == "20" ) echo 'selected="selected"' ?> >20px</option>
            <option value="24" <?php if ( $ctf_text_size == "24" ) echo 'selected="selected"' ?> >24px</option>
            <option value="28" <?php if ( $ctf_text_size == "28" ) echo 'selected="selected"' ?> >28px</option>
            <option value="32" <?php if ( $ctf_text_size == "32" ) echo 'selected="selected"' ?> >32px</option>
            <option value="36" <?php if ( $ctf_text_size == "36" ) echo 'selected="selected"' ?> >36px</option>
            <option value="42" <?php if ( $ctf_text_size == "42" ) echo 'selected="selected"' ?> >42px</option>
            <option value="48" <?php if ( $ctf_text_size == "48" ) echo 'selected="selected"' ?> >48px</option>
            <option value="54" <?php if ( $ctf_text_size == "54" ) echo 'selected="selected"' ?> >54px</option>
            <option value="60" <?php if ( $ctf_text_size == "60" ) echo 'selected="selected"' ?> >60px</option>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function text_weight( $args )
    {
        $options = get_option( $args['option'] );
        $ctf_text_weight = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>">
            <option value="inherit" <?php if ( $ctf_text_weight == "inherit" ) echo 'selected="selected"'; ?> >Inherit</option>
            <option value="normal" <?php if ( $ctf_text_weight == "normal" ) echo 'selected="selected"'; ?> >Normal</option>
            <option value="bold" <?php if ( $ctf_text_weight == "bold" ) echo 'selected="selected"'; ?> >Bold</option>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function feed_settings_timezone( $args )
    {
        $options = get_option( $args['option'] );
        $ctf_timezone = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <select name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" style="width: 300px;">
            <option value="default" <?php if( $ctf_timezone == "default" ) echo 'selected="selected"' ?> ><?php _e( 'default from Twitter' ) ?></option>
            <option value="Pacific/Midway" <?php if( $ctf_timezone == "Pacific/Midway" ) echo 'selected="selected"' ?> ><?php _e( '(GMT11:00) Midway Island, Samoa' ) ?></option>
            <option value="America/Adak" <?php if( $ctf_timezone == "America/Adak" ) echo 'selected="selected"' ?> ><?php _e( '(GMT10:00) HawaiiAleutian' ) ?></option>
            <option value="Etc/GMT+10" <?php if( $ctf_timezone == "Etc/GMT+10" ) echo 'selected="selected"' ?> ><?php _e( '(GMT10:00) Hawaii' ) ?></option>
            <option value="Pacific/Marquesas" <?php if( $ctf_timezone == "Pacific/Marquesas" ) echo 'selected="selected"' ?> ><?php _e( '(GMT09:30) Marquesas Islands' ) ?></option>
            <option value="Pacific/Gambier" <?php if( $ctf_timezone == "Pacific/Gambier" ) echo 'selected="selected"' ?> ><?php _e( '(GMT09:00) Gambier Islands' ) ?></option>
            <option value="America/Anchorage" <?php if( $ctf_timezone == "America/Anchorage" ) echo 'selected="selected"' ?> ><?php _e( '(GMT09:00) Alaska' ) ?></option>
            <option value="America/Ensenada" <?php if( $ctf_timezone == "America/Ensenada" ) echo 'selected="selected"' ?> ><?php _e( '(GMT08:00) Tijuana, Baja California' ) ?></option>
            <option value="Etc/GMT+8" <?php if( $ctf_timezone == "Etc/GMT+8" ) echo 'selected="selected"' ?> ><?php _e( '(GMT08:00) Pitcairn Islands' ) ?></option>
            <option value="America/Los_Angeles" <?php if( $ctf_timezone == "America/Los_Angeles" ) echo 'selected="selected"' ?> ><?php _e( '(GMT08:00) Pacific Time (US & Canada)' ) ?></option>
            <option value="America/Denver" <?php if( $ctf_timezone == "America/Denver" ) echo 'selected="selected"' ?> ><?php _e( '(GMT07:00) Mountain Time (US & Canada)' ) ?></option>
            <option value="America/Chihuahua" <?php if( $ctf_timezone == "America/Chihuahua" ) echo 'selected="selected"' ?> ><?php _e( '(GMT07:00) Chihuahua, La Paz, Mazatlan' ) ?></option>
            <option value="America/Dawson_Creek" <?php if( $ctf_timezone == "America/Dawson_Creek" ) echo 'selected="selected"' ?> ><?php _e( '(GMT07:00) Arizona' ) ?></option>
            <option value="America/Belize" <?php if( $ctf_timezone == "America/Belize" ) echo 'selected="selected"' ?> ><?php _e( '(GMT06:00) Saskatchewan, Central America' ) ?></option>
            <option value="America/Cancun" <?php if( $ctf_timezone == "America/Cancun" ) echo 'selected="selected"' ?> ><?php _e( '(GMT06:00) Guadalajara, Mexico City, Monterrey' ) ?></option>
            <option value="Chile/EasterIsland" <?php if( $ctf_timezone == "Chile/EasterIsland" ) echo 'selected="selected"' ?> ><?php _e( '(GMT06:00) Easter Island' ) ?></option>
            <option value="America/Chicago" <?php if( $ctf_timezone == "America/Chicago" ) echo 'selected="selected"' ?> ><?php _e( '(GMT06:00) Central Time (US & Canada)' ) ?></option>
            <option value="America/New_York" <?php if( $ctf_timezone == "America/New_York" ) echo 'selected="selected"' ?> ><?php _e( '(GMT05:00) Eastern Time (US & Canada)' ) ?></option>
            <option value="America/Havana" <?php if( $ctf_timezone == "America/Havana" ) echo 'selected="selected"' ?> ><?php _e( '(GMT05:00) Cuba' ) ?></option>
            <option value="America/Bogota" <?php if( $ctf_timezone == "America/Bogota" ) echo 'selected="selected"' ?> ><?php _e( '(GMT05:00) Bogota, Lima, Quito, Rio Branco' ) ?></option>
            <option value="America/Caracas" <?php if( $ctf_timezone == "America/Caracas" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:30) Caracas' ) ?></option>
            <option value="America/Santiago" <?php if( $ctf_timezone == "America/Santiago" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Santiago' ) ?></option>
            <option value="America/La_Paz" <?php if( $ctf_timezone == "America/La_Paz" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) La Paz' ) ?></option>
            <option value="Atlantic/Stanley" <?php if( $ctf_timezone == "Atlantic/Stanley" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Faukland Islands' ) ?></option>
            <option value="America/Campo_Grande" <?php if( $ctf_timezone == "America/Campo_Grande" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Brazil' ) ?></option>
            <option value="America/Goose_Bay" <?php if( $ctf_timezone == "America/Goose_Bay" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Atlantic Time (Goose Bay)' ) ?></option>
            <option value="America/Glace_Bay" <?php if( $ctf_timezone == "America/Glace_Bay" ) echo 'selected="selected"' ?> ><?php _e( '(GMT04:00) Atlantic Time (Canada)' ) ?></option>
            <option value="America/St_Johns" <?php if( $ctf_timezone == "America/St_Johns" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:30) Newfoundland' ) ?></option>
            <option value="America/Araguaina" <?php if( $ctf_timezone == "America/Araguaina" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) UTC3' ) ?></option>
            <option value="America/Montevideo" <?php if( $ctf_timezone == "America/Montevideo" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Montevideo' ) ?></option>
            <option value="America/Miquelon" <?php if( $ctf_timezone == "America/Miquelon" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Miquelon, St. Pierre' ) ?></option>
            <option value="America/Godthab" <?php if( $ctf_timezone == "America/Godthab" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Greenland' ) ?></option>
            <option value="America/Argentina/Buenos_Aires" <?php if( $ctf_timezone == "America/Argentina/Buenos_Aires" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Buenos Aires' ) ?></option>
            <option value="America/Sao_Paulo" <?php if( $ctf_timezone == "America/Sao_Paulo" ) echo 'selected="selected"' ?> ><?php _e( '(GMT03:00) Brasilia' ) ?></option>
            <option value="America/Noronha" <?php if( $ctf_timezone == "America/Noronha" ) echo 'selected="selected"' ?> ><?php _e( '(GMT02:00) MidAtlantic' ) ?></option>
            <option value="Atlantic/Cape_Verde" <?php if( $ctf_timezone == "Atlantic/Cape_Verde" ) echo 'selected="selected"' ?> ><?php _e( '(GMT01:00) Cape Verde Is.' ) ?></option>
            <option value="Atlantic/Azores" <?php if( $ctf_timezone == "Atlantic/Azores" ) echo 'selected="selected"' ?> ><?php _e( '(GMT01:00) Azores' ) ?></option>
            <option value="Europe/Belfast" <?php if( $ctf_timezone == "Europe/Belfast" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Greenwich Mean Time : Belfast' ) ?></option>
            <option value="Europe/Dublin" <?php if( $ctf_timezone == "Europe/Dublin" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Greenwich Mean Time : Dublin' ) ?></option>
            <option value="Europe/Lisbon" <?php if( $ctf_timezone == "Europe/Lisbon" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Greenwich Mean Time : Lisbon' ) ?></option>
            <option value="Europe/London" <?php if( $ctf_timezone == "Europe/London" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Greenwich Mean Time : London' ) ?></option>
            <option value="Africa/Abidjan" <?php if( $ctf_timezone == "Africa/Abidjan" ) echo 'selected="selected"' ?> ><?php _e( '(GMT) Monrovia, Reykjavik' ) ?></option>
            <option value="Europe/Amsterdam" <?php if( $ctf_timezone == "Europe/Amsterdam" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna' ) ?></option>
            <option value="Europe/Belgrade" <?php if( $ctf_timezone == "Europe/Belgrade" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) Belgrade, Bratislava, Budapest, Ljubljana, Prague' ) ?></option>
            <option value="Europe/Brussels" <?php if( $ctf_timezone == "Europe/Brussels" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) Brussels, Copenhagen, Madrid, Paris' ) ?></option>
            <option value="Africa/Algiers" <?php if( $ctf_timezone == "Africa/Algiers" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) West Central Africa' ) ?></option>
            <option value="Africa/Windhoek" <?php if( $ctf_timezone == "Africa/Windhoek" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+01:00) Windhoek' ) ?></option>
            <option value="Asia/Beirut" <?php if( $ctf_timezone == "Asia/Beirut" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Beirut' ) ?></option>
            <option value="Africa/Cairo" <?php if( $ctf_timezone == "Africa/Cairo" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Cairo' ) ?></option>
            <option value="Asia/Gaza" <?php if( $ctf_timezone == "Asia/Gaza" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Gaza' ) ?></option>
            <option value="Africa/Blantyre" <?php if( $ctf_timezone == "Africa/Blantyre" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Harare, Pretoria' ) ?></option>
            <option value="Asia/Jerusalem" <?php if( $ctf_timezone == "Asia/Jerusalem" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Jerusalem' ) ?></option>
            <option value="Europe/Minsk" <?php if( $ctf_timezone == "Europe/Minsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Minsk' ) ?></option>
            <option value="Asia/Damascus" <?php if( $ctf_timezone == "Asia/Damascus" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+02:00) Syria' ) ?></option>
            <option value="Europe/Moscow" <?php if( $ctf_timezone == "Europe/Moscow" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+03:00) Moscow, St. Petersburg, Volgograd' ) ?></option>
            <option value="Africa/Addis_Ababa" <?php if( $ctf_timezone == "Africa/Addis_Ababa" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+03:00) Nairobi' ) ?></option>
            <option value="Asia/Tehran" <?php if( $ctf_timezone == "Asia/Tehran" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+03:30) Tehran' ) ?></option>
            <option value="Asia/Dubai" <?php if( $ctf_timezone == "Asia/Dubai" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+04:00) Abu Dhabi, Muscat' ) ?></option>
            <option value="Asia/Yerevan" <?php if( $ctf_timezone == "Asia/Yerevan" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+04:00) Yerevan' ) ?></option>
            <option value="Asia/Kabul" <?php if( $ctf_timezone == "Asia/Kabul" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+04:30) Kabul' ) ?></option>
            <option value="Asia/Yekaterinburg" <?php if( $ctf_timezone == "Asia/Yekaterinburg" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+05:00) Ekaterinburg' ) ?></option>
            <option value="Asia/Tashkent" <?php if( $ctf_timezone == "Asia/Tashkent" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+05:00) Tashkent' ) ?></option>
            <option value="Asia/Kolkata" <?php if( $ctf_timezone == "Asia/Kolkata" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+05:30) Chennai, Kolkata, Mumbai, New Delhi' ) ?></option>
            <option value="Asia/Katmandu" <?php if( $ctf_timezone == "Asia/Katmandu" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+05:45) Kathmandu' ) ?></option>
            <option value="Asia/Dhaka" <?php if( $ctf_timezone == "Asia/Dhaka" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+06:00) Astana, Dhaka' ) ?></option>
            <option value="Asia/Novosibirsk" <?php if( $ctf_timezone == "Asia/Novosibirsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+06:00) Novosibirsk' ) ?></option>
            <option value="Asia/Rangoon" <?php if( $ctf_timezone == "Asia/Rangoon" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+06:30) Yangon (Rangoon)' ) ?></option>
            <option value="Asia/Bangkok" <?php if( $ctf_timezone == "Asia/Bangkok" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+07:00) Bangkok, Hanoi, Jakarta' ) ?></option>
            <option value="Asia/Krasnoyarsk" <?php if( $ctf_timezone == "Asia/Krasnoyarsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+07:00) Krasnoyarsk' ) ?></option>
            <option value="Asia/Hong_Kong" <?php if( $ctf_timezone == "Asia/Hong_Kong" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+08:00) Beijing, Chongqing, Hong Kong, Urumqi' ) ?></option>
            <option value="Asia/Irkutsk" <?php if( $ctf_timezone == "Asia/Irkutsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+08:00) Irkutsk, Ulaan Bataar' ) ?></option>
            <option value="Australia/Perth" <?php if( $ctf_timezone == "Australia/Perth" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+08:00) Perth' ) ?></option>
            <option value="Australia/Eucla" <?php if( $ctf_timezone == "Australia/Eucla" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+08:45) Eucla' ) ?></option>
            <option value="Asia/Tokyo" <?php if( $ctf_timezone == "Asia/Tokyo" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:00) Osaka, Sapporo, Tokyo' ) ?></option>
            <option value="Asia/Seoul" <?php if( $ctf_timezone == "Asia/Seoul" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:00) Seoul' ) ?></option>
            <option value="Asia/Yakutsk" <?php if( $ctf_timezone == "Asia/Yakutsk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:00) Yakutsk' ) ?></option>
            <option value="Australia/Adelaide" <?php if( $ctf_timezone == "Australia/Adelaide" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:30) Adelaide' ) ?></option>
            <option value="Australia/Darwin" <?php if( $ctf_timezone == "Australia/Darwin" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+09:30) Darwin' ) ?></option>
            <option value="Australia/Brisbane" <?php if( $ctf_timezone == "Australia/Brisbane" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+10:00) Brisbane' ) ?></option>
            <option value="Australia/Hobart" <?php if( $ctf_timezone == "Australia/Hobart" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+10:00) Sydney' ) ?></option>
            <option value="Asia/Vladivostok" <?php if( $ctf_timezone == "Asia/Vladivostok" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+10:00) Vladivostok' ) ?></option>
            <option value="Australia/Lord_Howe" <?php if( $ctf_timezone == "Australia/Lord_Howe" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+10:30) Lord Howe Island' ) ?></option>
            <option value="Etc/GMT11" <?php if( $ctf_timezone == "Etc/GMT11" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+11:00) Solomon Is., New Caledonia' ) ?></option>
            <option value="Asia/Magadan" <?php if( $ctf_timezone == "Asia/Magadan" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+11:00) Magadan' ) ?></option>
            <option value="Pacific/Norfolk" <?php if( $ctf_timezone == "Pacific/Norfolk" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+11:30) Norfolk Island' ) ?></option>
            <option value="Asia/Anadyr" <?php if( $ctf_timezone == "Asia/Anadyr" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+12:00) Anadyr, Kamchatka' ) ?></option>
            <option value="Pacific/Auckland" <?php if( $ctf_timezone == "Pacific/Auckland" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+12:00) Auckland, Wellington' ) ?></option>
            <option value="Etc/GMT12" <?php if( $ctf_timezone == "Etc/GMT12" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+12:00) Fiji, Kamchatka, Marshall Is.' ) ?></option>
            <option value="Pacific/Chatham" <?php if( $ctf_timezone == "Pacific/Chatham" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+12:45) Chatham Islands' ) ?></option>
            <option value="Pacific/Tongatapu" <?php if( $ctf_timezone == "Pacific/Tongatapu" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+13:00) Nuku\'alofa' ) ?></option>
            <option value="Pacific/Kiritimati" <?php if( $ctf_timezone == "Pacific/Kiritimati" ) echo 'selected="selected"' ?> ><?php _e( '(GMT+14:00) Kiritimati' ) ?></option>
        </select>
        <?php if ( isset( $args['whatis'] ) ) : ?>
        <a class="ctf-tooltip-link" href="JavaScript:void(0);"><i class="fa fa-question-circle" aria-hidden="true"></i></a>
        <p class="ctf-tooltip ctf-more-info"><?php _e( $args['whatis'], 'custom-twitter-feeds' ); ?>.</p>
    <?php endif; ?>
        <?php
    }

    public function custom_code( $args )
    {
        $options = get_option( $args['option'] );
        $option_string = ( isset( $options[ $args['name'] ] ) ) ? esc_attr( $options[ $args['name'] ] ) : '';
        ?>
        <p><?php _e( $args['description'], 'custom-twitter-feeds' ) ; ?></p>
        <textarea name="<?php echo $args['option'].'['.$args['name'].']'; ?>" id="ctf_<?php echo $args['name']; ?>" style="width: 70%;" rows="7"><?php esc_attr_e( stripslashes( $option_string ) ); ?></textarea>
        <?php if ( isset( $args['extra'] ) ) { _e( '<p class="ctf_note">'.$args['extra'].'</p>', 'custom-twitter-feeds' ); } ?>
        <?php
    }

    public function validate_ctf_options( $input )
    {
        $ctf_options = get_option( 'ctf_options' );

        if ( isset( $input['usertimeline_text'] ) ) {

            $feed_types = apply_filters( 'ctf_admin_feed_type_list', '' );
            $cron_clear_cache = isset( $input['cron_cache_clear'] ) ? $input['cron_cache_clear'] : 'no';
            $ctf_options['ajax_theme'] = false;
            $ctf_options['have_own_tokens'] = false;
            $ctf_options['use_own_consumer'] = false;
            $ctf_options['preserve_settings'] = false;

            foreach ( $input as $key => $val ) {
                if ( $key == 'search_text' || $key == 'usertimeline_text' ) {
                    $ctf_options[$key] = apply_filters( 'ctf_admin_validate_' . $key, $val );
                } elseif ( in_array( $key, $feed_types ) ) {
                    $ctf_options[$key] = apply_filters( 'ctf_admin_validate_include_replies', $val, $input['type'] );
                } elseif ( $key == 'ajax_theme' || $key == 'use_own_consumer' || $key == 'have_own_tokens' || $key == 'preserve_settings' ) {
                    if ( $val == 'on' ) {
                        $ctf_options[$key] = true;
                    }
                } else {
                    $ctf_options[$key] = sanitize_text_field( $val );
                }
            }

            $ctf_options['includereplies'] = apply_filters( 'ctf_admin_set_include_replies', $ctf_options );

            // delete feeds cached in transients
            ctf_clear_cache();

            // process force cache to clear on interval
            $cache_time = isset( $input['cache_time'] ) ? (int) $input['cache_time'] : 1;
            $cache_time_unit = isset( $input['cache_time_unit'] ) ? (int) $input['cache_time_unit'] : 3600;

            if ( $cron_clear_cache == 'no' ) {
                wp_clear_scheduled_hook( 'ctf_cron_job' );
            } elseif ( $cron_clear_cache == 'yes' ) {
                //Clear the existing cron event
                wp_clear_scheduled_hook( 'ctf_cron_job' );

                //Set the event schedule based on what the caching time is set to
                if ( $cache_time_unit == 3600 && $cache_time > 5 ) {
                    $ctf_cron_schedule = 'twicedaily';
                } elseif ( $cache_time_unit == 86400 ) {
                    $ctf_cron_schedule = 'daily';
                } else {
                    $ctf_cron_schedule = 'hourly';
                }

                wp_schedule_event( time(), $ctf_cron_schedule, 'ctf_cron_job' );
            }
        } elseif ( isset( $input['class'] ) ) {

            $cron_clear_cache = isset( $input['cron_cache_clear'] ) ? $input['cron_cache_clear'] : 'no';
            $checkbox_settings = array( 'width_mobile_no_fixed', 'include_retweeter', 'include_avatar', 'include_author', 'include_text',
                'include_date', 'include_actions', 'include_twitterlink', 'include_linkbox', 'creditctf', 'showbutton',
                'disablelinks', 'linktexttotwitter', 'showheader' );
            $checkbox_settings = apply_filters( 'ctf_admin_customize_checkbox_settings', $checkbox_settings );
            $leave_spaces = array( 'headertext', 'translate_minute', 'translate_hour' );

            foreach ( $checkbox_settings as $checkbox_setting ) {
                $ctf_options[$checkbox_setting] = false;
            }

            foreach ( $input as $key => $val ) {
                if ( in_array( $key, $checkbox_settings ) ) {
                    if ( $val == 'on' ) {
                        $ctf_options[$key] = true;
                    }
                } else {
                    if ( in_array( $key, $leave_spaces ) ) {
                        $ctf_options[$key] = $val;
                    } else {
                        $ctf_options[$key] = sanitize_text_field( $val );
                    }
                }
            }

            // delete feeds cached in transients
            ctf_clear_cache();

            // process force cache to clear on interval
            $cache_time = isset( $input['cache_time'] ) ? (int) $input['cache_time'] : 1;
            $cache_time_unit = isset( $input['cache_time_unit'] ) ? (int) $input['cache_time_unit'] : 3600;

            if ( $cron_clear_cache == 'no' ) {
                wp_clear_scheduled_hook( 'ctf_cron_job' );
            } elseif ( $cron_clear_cache == 'yes' ) {
                //Clear the existing cron event
                wp_clear_scheduled_hook( 'ctf_cron_job' );

                //Set the event schedule based on what the caching time is set to
                if ( $cache_time_unit == 3600 && $cache_time > 5 ) {
                    $ctf_cron_schedule = 'twicedaily';
                } elseif ( $cache_time_unit == 86400 ) {
                    $ctf_cron_schedule = 'daily';
                } else {
                    $ctf_cron_schedule = 'hourly';
                }

                wp_schedule_event( time(), $ctf_cron_schedule, 'ctf_cron_job' );
            }
        } else {
            $checkbox_settings = array( 'showbio' );
            $checkbox_settings = apply_filters( 'ctf_admin_style_checkbox_settings', $checkbox_settings );
            $leave_spaces = array( 'headertext' );

            foreach ( $checkbox_settings as $checkbox_setting ) {
                $ctf_options[$checkbox_setting] = false;
            }

            foreach ( $input as $key => $val ) {
                if ( in_array( $key, $checkbox_settings ) ) {
                    if ( $val == 'on' ) {
                        $ctf_options[$key] = true;
                    }
                } else {
                    if ( in_array( $key, $leave_spaces ) ) {
                        $ctf_options[$key] = $val;
                    } else {
                        $ctf_options[$key] = sanitize_text_field( $val );
                    }
                }
            }
        }


        return $ctf_options;
    }
}