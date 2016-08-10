<?php
/*
 * Stuff specific for User Role Editor WordPress plugin
 * Author: Vladimir Garagulya
 * Author email: support@role-editor.com
 * Author URI: https://www.role-editor.com
 * 
*/


/**
 * This class contains general stuff for usage at WordPress plugins
 */
class Ure_Lib extends URE_Base_Lib {

    protected $roles = null;
    protected $notification = '';   // notification message to show on page
    protected $apply_to_all = 0;
    protected $current_role = '';
    protected $capabilities_to_save = null;
    protected $wp_default_role = '';
    protected $current_role_name = '';
    protected $user_to_edit = '';
    protected $show_deprecated_caps = false;
    protected $caps_readable = false;
    protected $hide_pro_banner = false;
    protected $full_capabilities = false;
    protected $ure_object = 'role';  // what to process, 'role' or 'user'      
    protected $advert = null;
    protected $role_additional_options = null;
    protected $bbpress = null; // reference to the URE_bbPress class instance
    
    // when allow_edit_users_to_not_super_admin option is turned ON, we set this property to true 
    // when we raise single site admin permissions up to the superadmin for the 'Add new user' new-user.php page
    // User_Role_Editor::allow_add_user_as_superadmin()
    protected $raised_permissions = false; 
 
    protected $debug = false;
 
  
  
    /** class constructor
     * 
     * @param string $options_id
     * 
     */
    protected function __construct($options_id) {
                                           
        parent::__construct($options_id); 
        $this->debug = defined('URE_DEBUG') && (URE_DEBUG==1 || URE_DEBUG==true);
 
        $this->bbpress = URE_bbPress::get_instance($this);
        
        $this->upgrade();
    }
    // end of __construct()
    
    
    public static function get_instance($options_id = '') {
        
        if (self::$instance === null) {
            if (empty($options_id)) {
                throw new Exception('URE_Lib::get_inctance() - Error: plugin options ID string is required');
            }
            // new static() will work too
            self::$instance = new URE_Lib($options_id);
        }

        return self::$instance;
    }
    // end of get_instance()
        
    
    protected function upgrade() {
        
        $ure_version = $this->get_option('ure_version', '0');
        if (version_compare( $ure_version, URE_VERSION, '<' ) ) {
            // for upgrade to 4.18 and higher from older versions
            $this->init_ure_caps();
            $this->put_option('ure_version', URE_VERSION, true);
        }
        
    }
    // end of upgrade()
    
    
    /**
     * Is this the Pro version?
     * @return boolean
     */ 
    public function is_pro() {
        return false;
    }
    // end of is_pro()    
    
    
    public function get_ure_object() {
        
        return $this->ure_object;
    }
    // end of get_ure_object();
    
    
    
    public function set_notification($value) {
        
        $this->notification = $value;
        
    }
    // end of esc_html()
    
    
    public function set_apply_to_all($value) {
        
        
        $this->apply_to_all = !empty($value) ? 1 : 0;
        
    }
    // end of set_apply_to_all()


    public function set_raised_permissions($value) {
        
        $this->raised_permissions = !empty($value) ? true : false;
        
    }
    // end of set_raised_permissions()
    
    
    public function get_ure_caps() {
        
        $ure_caps = array(
            'ure_edit_roles' => 1,
            'ure_create_roles' => 1,
            'ure_delete_roles' => 1,
            'ure_create_capabilities' => 1,
            'ure_delete_capabilities' => 1,
            'ure_manage_options' => 1,
            'ure_reset_roles' => 1
        );        
        
        return $ure_caps;
    }
    // end of get_ure_caps()
    
                    
    public function init_ure_caps() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        if (!isset($wp_roles->roles['administrator'])) {
            return;
        }
        
        // Do not turn on URE caps for local administrator by default under multisite, as there is a superadmin.
        $turn_on = !$this->multisite;   
        
        $old_use_db = $wp_roles->use_db;
        $wp_roles->use_db = true;
        $administrator = $wp_roles->role_objects['administrator'];
        $ure_caps = $this->get_ure_caps();
        foreach(array_keys($ure_caps) as $cap) {
            if (!$administrator->has_cap($cap)) {
                $administrator->add_cap($cap, $turn_on);
            }
        }
        $wp_roles->use_db = $old_use_db;        
    }
    // end of init_ure_caps()
    
        
    /**
     * get options for User Role Editor plugin
     * User Role Editor stores its options at the main blog/site only and applies them to the all network
     * 
     */
    protected function init_options($options_id) {
        
        global $wpdb;
        
        if ($this->multisite) { 
            if ( ! function_exists( 'is_plugin_active_for_network' ) ) {    // Be sure the function is defined before trying to use it
                require_once( ABSPATH . '/wp-admin/includes/plugin.php' );                
            }
            $this->active_for_network = is_plugin_active_for_network(URE_PLUGIN_BASE_NAME);
        }
        $current_blog = $wpdb->blogid;
        if ($this->multisite && $current_blog!=$this->main_blog_id) {   
            if ($this->active_for_network) {   // plugin is active for whole network, so get URE options from the main blog
                switch_to_blog($this->main_blog_id);  
            }
        }
        
        $this->options_id = $options_id;
        $this->options = get_option($options_id);
        
        if ($this->multisite && $current_blog!=$this->main_blog_id) {
            if ($this->active_for_network) {   // plugin is active for whole network, so return back to the current blog
                restore_current_blog();
            }
        }

    }
    // end of init_options()
    
    
    /**
     * saves options array into WordPress database wp_options table
     */
    public function flush_options() {

        global $wpdb;
        
        $current_blog = $wpdb->blogid;
        if ($this->multisite && $current_blog!==$this->main_blog_id) {
            if ($this->active_for_network) {   // plugin is active for whole network, so get URE options from the main blog
                switch_to_blog($this->main_blog_id);  // in order to save URE options to the main blog
            }
        }
        
        update_option($this->options_id, $this->options);
        
        if ($this->multisite && $current_blog!==$this->main_blog_id) {            
            if ($this->active_for_network) {   // plugin is active for whole network, so return back to the current blog
                restore_current_blog();
            }
        }
        
    }
    // end of flush_options()
    
    
    public function get_main_blog_id() {
        
        return $this->main_blog_id;
        
    }
    

    /**
     * return key capability to have access to User Role Editor Plugin
     * 
     * @return string
     */
    public function get_key_capability() {
        
        if (!$this->multisite) {
            $key_capability = URE_KEY_CAPABILITY;
        } else {
            $enable_simple_admin_for_multisite = $this->get_option('enable_simple_admin_for_multisite', 0);
            if ( (defined('URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE') && URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE == 1) || 
                 $enable_simple_admin_for_multisite) {
                $key_capability = URE_KEY_CAPABILITY;
            } else {
                $key_capability = 'manage_network_plugins';
            }
        }
                
        return $key_capability;
    }
    // end of get_key_capability()

    
    public function get_settings_capability() {
        
        if (!$this->multisite) {
                $settings_access = 'ure_manage_options';
        } else {
            $enable_simple_admin_for_multisite = $this->get_option('enable_simple_admin_for_multisite', 0);
            if ( (defined('URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE') && URE_ENABLE_SIMPLE_ADMIN_FOR_MULTISITE == 1) || 
                 $enable_simple_admin_for_multisite) {
                $settings_access = 'ure_manage_options';
            } else {
                $settings_access = $this->get_key_capability();
            }
        }
        
        return $settings_access;
    }
    // end of get_settings_capability()
    

    /**
     *  return front-end according to the context - role or user editor
     */
    public function editor() {

        if (!$this->editor_init0()) {
            $this->show_message(esc_html__('Error: wrong request', 'user-role-editor'));
            return false;
        }                
        $this->process_user_request();
        $this->editor_init1();
        $this->show_editor();
        
    }
    // end of editor()
    

    protected function show_editor() {
        $container_width = ($this->ure_object == 'user') ? 1400 : 1200;

        $this->show_message($this->notification);
        if ($this->ure_object == 'user') {
            $view = new URE_User_View();
        } else {
            $this->set_current_role();
            $view = new URE_Role_View();
            $view->role_edit_prepare_html();
        }
        ?>
        <div class="wrap">
        		  <div id="ure-icon" class="icon32"><br/></div>
            <h1><?php _e('User Role Editor', 'user-role-editor'); ?></h1>
            <div id="ure_container" style="min-width: <?php echo $container_width; ?>px;">
                <div class="ure-sidebar" >
        <?php
        if (!$this->is_pro()) {
            $view->advertise_commercials();
        }
        ?>            
                </div>

                <div class="has-sidebar" >
                    <form id="ure_form" method="post" action="<?php echo URE_WP_ADMIN_URL . URE_PARENT . '?page=users-' . URE_PLUGIN_FILE; ?>" >			
                        <div id="ure_form_controls">
        <?php
        $view->display();
        wp_nonce_field('user-role-editor', 'ure_nonce');
        ?>
                            <input type="hidden" name="action" value="update" />
                        </div>      
                    </form>		      
        <?php
        if (!$this->is_pro()) {
            $view->advertise_pro();
        }
        $view->display_edit_dialogs();
        do_action('ure_dialogs_html');
        $view->output_confirmation_dialog();
        ?>
                </div>          
            </div>
        </div>
        <?php
    }
    // end of show_editor()
     
    
    // validate information about user we intend to edit
    protected function check_user_to_edit() {

        if ($this->ure_object == 'user') {
            if (!isset($_REQUEST['user_id'])) {
                return false; // user_id value is missed
            }
            $user_id = $_REQUEST['user_id'];
            if (!is_numeric($user_id)) {
                return false;
            }
            if (!$user_id) {
                return false;
            }
            $this->user_to_edit = get_user_to_edit($user_id);
            if (empty($this->user_to_edit)) {
                return false;
            }
        }
        
        return true;
    }
    // end of check_user_to_edit()
    
    
    protected function init_current_role_name() {
        
        if (!isset($this->roles[$_POST['user_role']])) {
            $mess = esc_html__('Error: ', 'user-role-editor') . esc_html__('Role', 'user-role-editor') . ' <em>' . esc_html($_POST['user_role']) . '</em> ' . 
                    esc_html__('does not exist', 'user-role-editor');
            $this->current_role = '';
            $this->current_role_name = '';
        } else {
            $this->current_role = $_POST['user_role'];
            $this->current_role_name = $this->roles[$this->current_role]['name'];
            $mess = '';
        }
        
        return $mess;
        
    }
    // end of init_current_role_name()

    
    /**
     *  prepare capabilities from user input to save at the database
     */
    protected function prepare_capabilities_to_save() {
        $this->capabilities_to_save = array();
        foreach ($this->full_capabilities as $available_capability) {
            $cap_id = str_replace(' ', URE_SPACE_REPLACER, $available_capability['inner']);
            if (isset($_POST[$cap_id])) {
                $this->capabilities_to_save[$available_capability['inner']] = true;
            }
        }
    }
    // end of prepare_capabilities_to_save()
    

    /**
     *  save changes to the roles or user
     *  @param string $mess - notification message to the user
     *  @return string - notification message to the user
     */
    protected function permissions_object_update($mess) {

        if ($this->ure_object == 'role') {  // save role changes to database
            if ($this->update_roles()) {
                if ($mess) {
                    $mess .= '<br/>';
                }
                if (!$this->apply_to_all) {
                    $mess = esc_html__('Role is updated successfully', 'user-role-editor');
                } else {
                    $mess = esc_html__('Roles are updated for all network', 'user-role-editor');
                }
            } else {
                if ($mess) {
                    $mess .= '<br/>';
                }
                $mess = esc_html__('Error occured during role(s) update', 'user-role-editor');
            }
        } else {
            if ($this->update_user($this->user_to_edit)) {
                if ($mess) {
                    $mess .= '<br/>';
                }
                $mess = esc_html__('User capabilities are updated successfully', 'user-role-editor');
            } else {
                if ($mess) {
                    $mess .= '<br/>';
                }
                $mess = esc_html__('Error occured during user update', 'user-role-editor');
            }
        }
        return $mess;
    }
    // end of permissions_object_update()

    
    /**
     * Process user request
     */
    protected function process_user_request() {

        $this->notification = '';
        if (isset($_POST['action'])) {
            if (empty($_POST['ure_nonce']) || !wp_verify_nonce($_POST['ure_nonce'], 'user-role-editor')) {
                echo '<h3>Wrong nonce. Action prohibitied.</h3>';
                exit;
            }

            $action = $_POST['action'];
            
            if ($action == 'reset') {
                $this->reset_user_roles();
                exit;
            } else if ($action == 'add-new-role') {
                // process new role create request
                $this->notification = $this->add_new_role();
            } else if ($action == 'rename-role') {
                // process rename role request
                $this->notification = $this->rename_role();    
            } else if ($action == 'delete-role') {
                $this->notification = $this->delete_role();
            } else if ($action == 'change-default-role') {
                $this->notification = $this->change_default_role();
            } else if ($action == 'caps-readable') {
                if ($this->caps_readable) {
                    $this->caps_readable = 0;					
                } else {
                    $this->caps_readable = 1;
                }
                set_site_transient( 'ure_caps_readable', $this->caps_readable, 600 );
            } else if ($action == 'show-deprecated-caps') {
                if ($this->show_deprecated_caps) {
                    $this->show_deprecated_caps = 0;
                } else {
                    $this->show_deprecated_caps = 1;
                }
                set_site_transient( 'ure_show_deprecated_caps', $this->show_deprecated_caps, 600 );
            } else if ($action == 'hide-pro-banner') {
                $this->hide_pro_banner = 1;
                $this->put_option('ure_hide_pro_banner', 1);	
                $this->flush_options();				
            } else if ($action == 'add-new-capability') {
                $this->notification = $this->add_new_capability();
            } else if ($action == 'delete-user-capability') {
                $this->notification = $this->delete_capability();
            } else if ($action == 'roles_restore_note') {
                $this->notification = esc_html__('User Roles are restored to WordPress default values. ', 'user-role-editor');
            } else if ($action == 'update') {
                $this->roles = $this->get_user_roles();
                $this->init_full_capabilities();
                if (isset($_POST['user_role'])) {
                    $this->notification = $this->init_current_role_name();                    
                }
                $this->prepare_capabilities_to_save();
                $this->notification = $this->permissions_object_update($this->notification);                                  
            } else {
                do_action('ure_process_user_request');
            } // if ($action
        }
        
    }
    // end of process_user_request()

	
	protected function set_apply_to_all_from_post() {
    if (isset($_POST['ure_apply_to_all'])) {
        $this->apply_to_all = 1;
    } else {
        $this->apply_to_all = 0;
    }
}
	// end of set_apply_to_all_from_post()
	

    public function get_default_role() {
        $this->wp_default_role = get_option('default_role');
    }
    // end of get_default_role()
    

    protected function editor_init0() {
        $this->caps_readable = get_site_transient('ure_caps_readable');
        if (false === $this->caps_readable) {
            $this->caps_readable = $this->get_option('ure_caps_readable');
            set_site_transient('ure_caps_readable', $this->caps_readable, 600);
        }
        $this->show_deprecated_caps = get_site_transient('ure_show_deprecated_caps');
        if (false === $this->show_deprecated_caps) {
            $this->show_deprecated_caps = $this->get_option('ure_show_deprecated_caps');
            set_site_transient('ure_caps_readable', $this->caps_readable, 600);
        }

        $this->hide_pro_banner = $this->get_option('ure_hide_pro_banner', 0);
        $this->get_default_role();

        // could be sent as by POST, as by GET
        if (isset($_REQUEST['object'])) {
            $this->ure_object = $_REQUEST['object'];
            if (!$this->check_user_to_edit()) {
                return false;
            }
        } else {
            $this->ure_object = 'role';
        }

        $this->set_apply_to_all_from_post();

        return true;
    }
    // end of editor_init0()

    
    public function editor_init1() {

        $this->roles = $this->get_user_roles();
        $this->init_full_capabilities();
        if (empty($this->role_additional_options)) {
            $this->role_additional_options = URE_Role_Additional_Options::get_instance($this);
        }
        
        if (!$this->is_pro()) {
            require_once(URE_PLUGIN_DIR . 'includes/classes/advertisement.php');
        }
        
    }
    // end of editor_init1()


    /**
     * return id of role last in the list of sorted roles
     * 
     */
    protected function get_last_role_id() {
        
        // get the key of the last element in roles array
        $keys = array_keys($this->roles);
        $last_role_id = array_pop($keys);
        
        return $last_role_id;
    }
    // end of get_last_role_id()
    
    
    public function get_usermeta_table_name() {
        global $wpdb;
        
        $table_name = (!$this->multisite && defined('CUSTOM_USER_META_TABLE')) ? CUSTOM_USER_META_TABLE : $wpdb->usermeta;
        
        return $table_name;
    }
    // end of get_usermeta_table_name()
    
    
    /**
     * Check if user has "Administrator" role assigned
     * 
     * @global wpdb $wpdb
     * @param int $user_id
     * @return boolean returns true is user has Role "Administrator"
     */
    public function has_administrator_role($user_id) {
        global $wpdb;

        if (empty($user_id) || !is_numeric($user_id)) {
            return false;
        }

        $table_name = $this->get_usermeta_table_name();
        $meta_key = $wpdb->prefix . 'capabilities';
        $query = "SELECT count(*)
                FROM $table_name
                WHERE user_id=$user_id AND meta_key='$meta_key' AND meta_value like '%administrator%'";
        $has_admin_role = $wpdb->get_var($query);
        if ($has_admin_role > 0) {
            $result = true;
        } else {
            $result = false;
        }
        // cache checking result for the future use
        $this->lib->user_to_check[$user_id] = $result;

        return $result;
    }

    // end of has_administrator_role()

  
    /**
     * Checks if user is allowed to use User Role Editor
     * 
     * @global int $current_user
     * @param int $user_id
     * @return boolean true 
     */
    public function user_is_admin($user_id = false) {
        global $current_user;

        $ure_key_capability = $this->get_key_capability();
        if (empty($user_id)) {                    
            $user_id = $current_user->ID;
        }
        $result = user_can($user_id, $ure_key_capability);
        
        return $result;
    }
    // end of user_is_admin()

        
    
  /**
     * return array with WordPress user roles
     * 
     * @global WP_Roles $wp_roles
     * @global type $wp_user_roles
     * @return array
     */
    public function get_user_roles() {

        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }                

        if (!empty($this->bbpress)) {  // bbPress plugin is active
            $this->roles = $this->bbpress->get_roles();
        } else {
            $this->roles = $wp_roles->roles;
        }        
        
        if (is_array($this->roles) && count($this->roles) > 0) {
            asort($this->roles);
        }

        return $this->roles;
    }
    // end of get_user_roles()
    
    
    /**
     * Respect 'editable_roles' filter, when needed
     * @return array
     */
    public function get_editable_user_roles() {
                
        if (empty($this->roles)) {
            $this->get_user_roles();
        }
        if ($this->bbpress!==null) {
            remove_filter('editable_roles', 'bbp_filter_blog_editable_roles');
        }
        $roles = apply_filters('editable_roles', $this->roles);
        if ($this->bbpress!==null) {
            add_filter('editable_roles', 'bbp_filter_blog_editable_roles');
        }
        
        return $roles;

    }
    // end of get_editable_user_roles()
    
     
/*    
    // restores User Roles from the backup record
    protected function restore_user_roles() 
    {
        global $wpdb, $wp_roles;

        $error_message = 'Error! ' . __('Database operation error. Check log file.', 'user-role-editor');
        $option_name = $wpdb->prefix . 'user_roles';
        $backup_option_name = $wpdb->prefix . 'backup_user_roles';
        $query = "select option_value
              from $wpdb->options
              where option_name='$backup_option_name'
              limit 0, 1";
        $option_value = $wpdb->get_var($query);
        if ($wpdb->last_error) {
            $this->log_event($wpdb->last_error, true);
            return $error_message;
        }
        if ($option_value) {
            $query = "update $wpdb->options
                    set option_value='$option_value'
                    where option_name='$option_name'
                    limit 1";
            $record = $wpdb->query($query);
            if ($wpdb->last_error) {
                $this->log_event($wpdb->last_error, true);
                return $error_message;
            }
            $wp_roles = new WP_Roles();
            $reload_link = wp_get_referer();
            $reload_link = remove_query_arg('action', $reload_link);
            $reload_link = esc_url_raw(add_query_arg('action', 'roles_restore_note', $reload_link));
?>    
            <script type="text/javascript" >
              document.location = '<?php echo $reload_link; ?>';
            </script>  
            <?php
            $mess = '';
        } else {
            $mess = __('No backup data. It is created automatically before the first role data update.', 'user-role-editor');
        }
        if (isset($_REQUEST['user_role'])) {
            unset($_REQUEST['user_role']);
        }

        return $mess;
    }
    // end of restore_user_roles()
*/

    protected function convert_caps_to_readable($caps_name) 
    {

        $caps_name = str_replace('_', ' ', $caps_name);
        $caps_name = ucfirst($caps_name);

        return $caps_name;
    }
    // ure_ConvertCapsToReadable
    
            
    public function make_roles_backup() 
    {
        global $wpdb;

        // check if backup user roles record exists already
        $backup_option_name = $wpdb->prefix . 'backup_user_roles';
        $query = "select option_id
              from $wpdb->options
              where option_name='$backup_option_name'
          limit 0, 1";
        $option_id = $wpdb->get_var($query);
        if ($wpdb->last_error) {
            $this->log_event($wpdb->last_error, true);
            return false;
        }
        if (!$option_id) {
            $roles_option_name = $wpdb->prefix.'user_roles';
            $query = "select option_value 
                        from $wpdb->options 
                        where option_name like '$roles_option_name' limit 0,1";
            $serialized_roles = $wpdb->get_var($query);
            // create user roles record backup            
            $query = "insert into $wpdb->options
                (option_name, option_value, autoload)
                values ('$backup_option_name', '$serialized_roles', 'no')";
            $record = $wpdb->query($query);
            if ($wpdb->last_error) {
                $this->log_event($wpdb->last_error, true);
                return false;
            }
        }

        return true;
    }
    // end of ure_make_roles_backup()

    
    protected function role_contains_caps_not_allowed_for_simple_admin($role_id) {
        
        $result = false;
        $role = $this->roles[$role_id];
        if (!is_array($role['capabilities'])) {
            return false;
        }
        foreach (array_keys($role['capabilities']) as $cap) {
            if ($this->block_cap_for_single_admin($cap)) {
                $result = true;
                break;
            }
        }
        
        return $result;
    } 
    // end of role_contains_caps_not_allowed_for_simple_admin()
    
    /**
     * return array with roles which we could delete, e.g self-created and not used with any blog user
     * 
     * @global wpdb $wpdb   - WP database object
     * @return array 
     */
    public function get_roles_can_delete() {

        $default_role = get_option('default_role');
        $standard_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        $roles_can_delete = array();
        $users = count_users();
        foreach ($this->roles as $key => $role) {
            $can_delete = true;
            // check if it is default role for new users
            if ($key == $default_role) {
                $can_delete = false;
                continue;
            }
            // check if it is standard role            
            if (in_array($key, $standard_roles)) {
                continue;
            }
            // check if role has capabilities prohibited for the single site administrator
            if ($this->role_contains_caps_not_allowed_for_simple_admin($key)) {
                continue;
            }
                        
            if (!isset($users['avail_roles'][$key])) {
                $roles_can_delete[$key] = $role['name'] . ' (' . $key . ')';
            }
        }

        return $roles_can_delete;
    }
    // end of get_roles_can_delete()
    
    
    /**
     * return array of built-in WP capabilities (WP 3.1 wp-admin/includes/schema.php) 
     * 
     * @return array 
     */
    public function get_built_in_wp_caps() {
        
        $caps_groups = URE_Capabilities_Groups_Manager::get_instance();                
        $caps = $caps_groups->get_built_in_wp_caps();
        
        return $caps;
    }
    // end of get_built_in_wp_caps()

    
    /**
     * return the array of unused user capabilities
     * 
     * @global WP_Roles $wp_roles
     * @global wpdb $wpdb
     * @return array 
     */
    public function get_caps_to_remove() {
        global $wp_roles;

        // build full capabilities list from all roles except Administrator 
        $full_caps_list = array();
        foreach ($wp_roles->roles as $role) {
            // validate if capabilities is an array
            if (isset($role['capabilities']) && is_array($role['capabilities'])) {
                foreach ($role['capabilities'] as $capability => $value) {
                    if (!isset($full_caps_list[$capability])) {
                        $full_caps_list[$capability] = 1;
                    }
                }
            }
        }

        $caps_to_exclude = $this->get_built_in_wp_caps();
        $ure_caps = $this->get_ure_caps();
        $caps_to_exclude = array_merge($caps_to_exclude, $ure_caps);

        $caps_to_remove = array();
        foreach ($full_caps_list as $capability => $value) {
            if (!isset($caps_to_exclude[$capability])) {    // do not touch built-in WP caps
                // check roles
                $cap_in_use = false;
                foreach ($wp_roles->role_objects as $wp_role) {
                    if ($wp_role->name != 'administrator') {
                        if ($wp_role->has_cap($capability)) {
                            $cap_in_use = true;
                            break;
                        }
                    }
                }
                if (!$cap_in_use) {
                    $caps_to_remove[$capability] = 1;
                }
            }
        }

        return $caps_to_remove;
    }
    // end of get_caps_to_remove()
    
    
    /**
     * Return true if $capability is included to the list of capabilities allowed for the single site administrator
     * @param string $capability - capability ID
     * @param boolean $ignore_super_admin - if 
     * @return boolean
     */
    public function block_cap_for_single_admin($capability, $ignore_super_admin=false) {
        
        if (!$this->is_pro()) {    // this functionality is for the Pro version only.
            return false;
        }
        
        if (!$this->multisite) {    // work for multisite only
            return false;
        }
        if (!$ignore_super_admin && is_super_admin()) { // Do not block superadmin
            return false;
        }
        $caps_access_restrict_for_simple_admin = $this->get_option('caps_access_restrict_for_simple_admin', 0);
        if (!$caps_access_restrict_for_simple_admin) {
            return false;
        }
        $allowed_caps = $this->get_option('caps_allowed_for_single_admin', array());
        if (in_array($capability, $allowed_caps)) {
            $block_this_cap = false;
        } else {
            $block_this_cap = true;
        }
        
        return $block_this_cap;
    }
    // end of block_cap_for_single_admin()
    
    
    /**
     * return link to the capability according its name in $capability parameter
     * 
     * @param string $capability
     * @return string 
     */
    protected function capability_help_link($capability) {

        if (empty($capability)) {
            return '';
        }

        switch ($capability) {
            case 'activate_plugins':
                $url = 'http://www.shinephp.com/activate_plugins-wordpress-capability/';
                break;
            case 'add_users':
                $url = 'http://www.shinephp.com/add_users-wordpress-user-capability/';
                break;
            case 'create_users':
                $url = 'http://www.shinephp.com/create_users-wordpress-user-capability/';
                break;
            case 'delete_others_pages':
            case 'delete_others_posts':
            case 'delete_pages':
            case 'delete_posts':
            case 'delete_protected_pages':
            case 'delete_protected_posts':
            case 'delete_published_pages':
            case 'delete_published_posts':
                $url = 'http://www.shinephp.com/delete-posts-and-pages-wordpress-user-capabilities-set/';
                break;
            case 'delete_plugins':
                $url = 'http://www.shinephp.com/delete_plugins-wordpress-user-capability/';
                break;
            case 'delete_themes':
                $url = 'http://www.shinephp.com/delete_themes-wordpress-user-capability/';
                break;
            case 'delete_users':
                $url = 'http://www.shinephp.com/delete_users-wordpress-user-capability/';
                break;
            case 'edit_dashboard':
                $url = 'http://www.shinephp.com/edit_dashboard-wordpress-capability/';
                break;
            case 'edit_files':
                $url = 'http://www.shinephp.com/edit_files-wordpress-user-capability/';
                break;
            case 'edit_plugins':
                $url = 'http://www.shinephp.com/edit_plugins-wordpress-user-capability';
                break;
            case 'moderate_comments':
                $url = 'http://www.shinephp.com/moderate_comments-wordpress-user-capability/';
                break;
            case 'read':
                $url = 'http://shinephp.com/wordpress-read-capability/';
                break;
            case 'update_core':
                $url = 'http://www.shinephp.com/update_core-capability-for-wordpress-user/';
                break;
            case 'ure_edit_roles':
                $url = 'https://www.role-editor.com/user-role-editor-4-18-new-permissions/';
                break;
            default:
                $url = '';
        }
        // end of switch
        if (!empty($url)) {
            $link = '<a href="' . $url . '" title="' . esc_html__('read about', 'user-role-editor') .' '. $capability .' '. 
                    esc_html__('user capability', 'user-role-editor') .'" target="new"><img src="' . 
                    URE_PLUGIN_URL . 'images/help.png" alt="' . esc_html__('Help', 'user-role-editor') . '" /></a>';
        } else {
            $link = '';
        }

        return $link;
    }
    // end of capability_help_link()
    

    /**
     *  Go through all users and if user has non-existing role lower him to Subscriber role
     * 
     */   
    protected function validate_user_roles() {

        global $wp_roles;

        $default_role = get_option('default_role');
        if (empty($default_role)) {
            $default_role = 'subscriber';
        }
        $users_query = new WP_User_Query(array('fields' => 'ID'));
        $users = $users_query->get_results();
        foreach ($users as $user_id) {
            $user = get_user_by('id', $user_id);
            if (is_array($user->roles) && count($user->roles) > 0) {
                foreach ($user->roles as $role) {
                    $user_role = $role;
                    break;
                }
            } else {
                $user_role = is_array($user->roles) ? '' : $user->roles;
            }
            if (!empty($user_role) && !isset($wp_roles->roles[$user_role])) { // role doesn't exists
                $user->set_role($default_role); // set the lowest level role for this user
                $user_role = '';
            }

            if (empty($user_role)) {
                // Cleanup users level capabilities from non-existed roles
                $cap_removed = true;
                while (count($user->caps) > 0 && $cap_removed) {
                    foreach ($user->caps as $capability => $value) {
                        if (!isset($this->full_capabilities[$capability])) {
                            $user->remove_cap($capability);
                            $cap_removed = true;
                            break;
                        }
                        $cap_removed = false;
                    }
                }  // while ()
            }
        }  // foreach()
    }
    // end of validate_user_roles()

        
    protected function add_capability_to_full_caps_list($cap_id) {
        if (!isset($this->full_capabilities[$cap_id])) {    // if capability was not added yet
            $cap = array();
            $cap['inner'] = $cap_id;
            $cap['human'] = esc_html__($this->convert_caps_to_readable($cap_id), 'user-role-editor');
            if (isset($this->built_in_wp_caps[$cap_id])) {
                $cap['wp_core'] = true;
            } else {
                $cap['wp_core'] = false;
            }

            $this->full_capabilities[$cap_id] = $cap;
        }
    }
    // end of add_capability_to_full_caps_list()

    
    /**
     * Add capabilities from user roles save at WordPress database
     * 
     */
    protected function add_roles_caps() {
        foreach ($this->roles as $role) {
            // validate if capabilities is an array
            if (isset($role['capabilities']) && is_array($role['capabilities'])) {
                foreach (array_keys($role['capabilities']) as $cap) {
                    $this->add_capability_to_full_caps_list($cap);
                }
            }
        }
    }
    // end of add_roles_caps()
    

    /**
     * Add Gravity Forms plugin capabilities, if available
     * 
     */
    protected function add_gravity_forms_caps() {
        
        if (class_exists('GFCommon')) {
            $gf_caps = GFCommon::all_caps();
            foreach ($gf_caps as $gf_cap) {
                $this->add_capability_to_full_caps_list($gf_cap);
            }
        }        
        
    }
    // end of add_gravity_forms_caps()
    
    
    /**
     * Add bbPress plugin user capabilities (if available)
     */
    protected function add_bbpress_caps() {
    
        if (empty($this->bbpress)) {
            return;
        }
        
        $caps = $this->bbpress->get_caps();
        foreach ($caps as $cap) {
            $this->add_capability_to_full_caps_list($cap);
        }
    }
    // end of add_bbpress_caps()
        
    
    /**
     * Provide compatibility with plugins and themes which define their custom user capabilities using 
     * 'members_get_capabilities' filter from Members plugin 
     * 
     */
    protected function add_members_caps() {
        
        $custom_caps = array();
        $custom_caps = apply_filters( 'members_get_capabilities', $custom_caps );
        foreach ($custom_caps as $cap) {
           $this->add_capability_to_full_caps_list($cap);
        }        
        
    }
    // end of add_members_caps()
    

    /**
     * Add capabilities assigned directly to user, and not included into any role
     * 
     */
    protected function add_user_caps() {
        
        if ($this->ure_object=='user') {
            foreach(array_keys($this->user_to_edit->caps) as $cap)  {
                if (!isset($this->roles[$cap])) {   // it is the user capability, not role
                    $this->add_capability_to_full_caps_list($cap);
                }
            }
        }
        
    }
    // end of add_user_caps()
    

    /**
     * Add built-in WordPress caps in case some were not included to the roles for some reason
     * 
     */
    protected function add_wordpress_caps() {
                
        foreach (array_keys($this->built_in_wp_caps) as $cap) {            
            $this->add_capability_to_full_caps_list($cap);
        }        
        
    }
    // end of add_wordpress_caps()
    
    
    /**
     * Return all available post types except non-public WordPress built-in post types
     * 
     * @return array
     */
    public function _get_post_types() {
        $post_types = get_transient('ure_public_post_types');
        if (empty($post_types)) {
            $all_post_types = get_post_types();
            $internal_post_types = get_post_types(array('public'=>false, '_builtin'=>true));
            $post_types = array_diff($all_post_types, $internal_post_types);
            set_transient('ure_public_post_types', $post_types, 30);
        }
        
        return $post_types;
    }
    // end of _get_post_types()
    
    
    public function get_edit_post_capabilities() {
        $capabilities = array(
            'create_posts',
            'edit_posts',
            'edit_published_posts',
            'edit_others_posts',
            'edit_private_posts',
            'publish_posts',
            'read_private_posts',
            'delete_posts',
            'delete_private_posts',
            'delete_published_posts',
            'delete_others_posts'
        );
        
        return $capabilities;
    }
    // end of get_edit_post_capabilities();
    
    
    protected function add_custom_post_type_caps() {
               
        global $wp_roles;
        
        $capabilities = $this->get_edit_post_capabilities();        
        $post_types = get_post_types(array(), 'objects');
        $_post_types = $this->_get_post_types();
        // do not forget attachment post type as it may use the own capabilities set
        $attachment_post_type = get_post_type_object('attachment');
        if ($attachment_post_type->cap->edit_posts!=='edit_posts') {
            $post_types['attachment'] = $attachment_post_type;
        }
        
        foreach($post_types as $post_type) {
            if (!isset($_post_types[$post_type->name])) {
                continue;
            }
            if (!isset($post_type->cap)) {
                continue;
            }
            foreach($capabilities as $capability) {
                if (!isset($post_type->cap->$capability)) {
                    continue;                    
                }    
                $cap_to_check = $post_type->cap->$capability;
                $this->add_capability_to_full_caps_list($cap_to_check);
                if (!$this->multisite &&
                    isset($wp_roles->role_objects['administrator']) && 
                    !isset($wp_roles->role_objects['administrator']->capabilities[$cap_to_check])) {
                    // admin should be capable to edit any posts
                    $wp_roles->role_objects['administrator']->add_cap($cap_to_check, true);
                }                
            }                        
        }
        
        if (!$this->multisite && isset($wp_roles->role_objects['administrator'])) {
            foreach(array('post', 'page') as $post_type_name) {
                $post_type = get_post_type_object($post_type_name);
                if ($post_type->cap->create_posts!=='edit_'. $post_type->name .'s') {   // 'create' capability is active
                    if (!isset($wp_roles->role_objects['administrator']->capabilities[$post_type->cap->create_posts])) {
                        // admin should be capable to create posts and pages
                        $wp_roles->role_objects['administrator']->add_cap($post_type->cap->create_posts, true);
                    }
                }
            }   // foreach()
        }   // if ()
        
    }
    // end of add_custom_post_type_caps()

    
    /**
     * Add capabilities for URE permissions system in case some were excluded from Administrator role
     * 
     */
    protected function add_ure_caps() {
        $key_capability = $this->get_key_capability();
        if (!current_user_can($key_capability)) {
            return;
        }
        $ure_caps = $this->get_ure_caps();
        foreach(array_keys($ure_caps) as $cap) {
            $this->add_capability_to_full_caps_list($cap);
        }
        
    }
    // end of add_ure_caps()
    
    
    protected function init_full_capabilities() {
                
        $this->built_in_wp_caps = $this->get_built_in_wp_caps();
        $this->full_capabilities = array();
        $this->add_roles_caps();
        $this->add_gravity_forms_caps();
        $this->add_bbpress_caps();
        $this->add_members_caps();
        $this->add_user_caps();
        $this->add_wordpress_caps();
        $this->add_custom_post_type_caps();
        $this->add_ure_caps();
        
        unset($this->built_in_wp_caps);
        asort($this->full_capabilities);
        
        $this->full_capabilities = apply_filters('ure_full_capabilites', $this->full_capabilities);
        
    }
    // end of init_full_capabilities()


    /**
     * return WordPress user roles to its initial state, just like after installation
     * @global WP_Roles $wp_roles
     */
    protected function wp_roles_reinit() {
        global $wp_roles;
        
        $wp_roles->roles = array();
        $wp_roles->role_objects = array();
        $wp_roles->role_names = array();
        $wp_roles->use_db = true;

        require_once(ABSPATH . '/wp-admin/includes/schema.php');
        populate_roles();
        $wp_roles->reinit();
        
        $this->roles = $this->get_user_roles();
        
    }
    // end of wp_roles_reinit()
    
    /**
     * reset user roles to WordPress default roles
     */
    protected function reset_user_roles() {
        
        if (!current_user_can('ure_reset_roles')) {
            return esc_html__('Insufficient permissions to work with User Role Editor','user-role-editor');
        }
              
        $this->wp_roles_reinit();
        $this->init_ure_caps();
        if ($this->is_full_network_synch() || $this->apply_to_all) {
            $this->current_role = '';
            $this->direct_network_roles_update();
        }
        //$this->validate_user_roles();  // if user has non-existing role lower him to Subscriber role
        
        $reload_link = wp_get_referer();
        $reload_link = esc_url_raw(remove_query_arg('action', $reload_link));
        ?>    
        	<script type="text/javascript" >
             jQuery.ure_postGo('<?php echo $reload_link; ?>', 
                      { action: 'roles_restore_note', 
                        ure_nonce: ure_data.wp_nonce} );
        	</script>  
        <?php
    }
    // end of reset_user_roles()

    
    /**
     * if returns true - make full syncronization of roles for all sites with roles from the main site
     * else - only currently selected role update is replicated
     * 
     * @return boolean
     */
    public function is_full_network_synch() {
        
        $result = defined('URE_MULTISITE_DIRECT_UPDATE') && URE_MULTISITE_DIRECT_UPDATE == 1;
        
        return $result;
    }
    // end of is_full_network_synch()
    
    
    protected function last_check_before_update() {
        global $current_user;
        
        if (empty($this->roles) || !is_array($this->roles) || count($this->roles)==0) { // Nothing to save - something goes wrong - stop ...
            return false;
        }
        
        $key_capability = $this->get_key_capability();
        $user_is_ure_admin = current_user_can($key_capability);
        if (!$user_is_ure_admin) {
            if (in_array($this->current_role, $current_user->roles)) {
                // do not allow to a user update his own role if he does not have full access to the User Role Editor
                return false;
            }
        }
        
        return true;
    }
    // end of last_check_before_update()
    
    
    // Save Roles to database
    protected function save_roles() {
        global $wpdb;

        if (!$this->last_check_before_update()) {
            return false;
        }
        if (!isset($this->roles[$this->current_role])) {
            return false;
        }
        
        $this->capabilities_to_save = $this->remove_caps_not_allowed_for_single_admin($this->capabilities_to_save);
        $this->roles[$this->current_role]['capabilities'] = $this->capabilities_to_save;
        $option_name = $wpdb->prefix . 'user_roles';

        update_option($option_name, $this->roles);

        // save additional options for the current role
        if (empty($this->role_additional_options)) {
            $this->role_additional_options = URE_Role_Additional_Options::get_instance($this);
        }
        $this->role_additional_options->save($this->current_role);
        
        return true;
    }
    // end of save_roles()
    
    
    /**
     * Update roles for all network using direct database access - quicker in several times
     * 
     * @global wpdb $wpdb
     * @return boolean
     */
    public function direct_network_roles_update() {
        global $wpdb;

        if (!$this->last_check_before_update()) {
            return false;
        }
        if (!empty($this->current_role)) {
            if (!isset($this->roles[$this->current_role])) {
                $this->roles[$this->current_role]['name'] = $this->current_role_name;
            }
            $this->roles[$this->current_role]['capabilities'] = $this->capabilities_to_save;
        }        

        $serialized_roles = serialize($this->roles);
        foreach ($this->blog_ids as $blog_id) {
            $prefix = $wpdb->get_blog_prefix($blog_id);
            $options_table_name = $prefix . 'options';
            $option_name = $prefix . 'user_roles';
            $query = "update $options_table_name
                set option_value='$serialized_roles'
                where option_name='$option_name'
                limit 1";
            $wpdb->query($query);
            if ($wpdb->last_error) {
                $this->log_event($wpdb->last_error, true);
                return false;
            }
            // save role additional options
            
        }
        
        return true;
    }
    // end of direct_network_roles_update()

    
    public function restore_after_blog_switching($blog_id = 0) {
        
        if (!empty($blog_id)) {
            switch_to_blog($blog_id);
        }
        // cleanup blog switching data
        $GLOBALS['_wp_switched_stack'] = array();
        $GLOBALS['switched'] = ! empty( $GLOBALS['_wp_switched_stack'] );
    }
    // end of restore_after_blog_switching()
    
    
    protected function wp_api_network_roles_update() {
        global $wpdb;
        
        $result = true;
        $old_blog = $wpdb->blogid;
        foreach ($this->blog_ids as $blog_id) {
            switch_to_blog($blog_id);
            $this->roles = $this->get_user_roles();
            if (!isset($this->roles[$this->current_role])) { // add new role to this blog
                $this->roles[$this->current_role] = array('name' => $this->current_role_name, 'capabilities' => array('read' => true));
            }
            if (!$this->save_roles()) {
                $result = false;
                break;
            }
        }
        $this->restore_after_blog_switching($old_blog);
        $this->roles = $this->get_user_roles();
        
        return $result;
    }
    // end of wp_api_network_roles_update()
    
        
    /**
     * Update role for all network using WordPress API
     * 
     * @return boolean
     */
    protected function multisite_update_roles() {
        
        if ($this->debug) {
            $time_shot = microtime();
        }
        
        if ($this->is_full_network_synch()) {
            $result = $this->direct_network_roles_update();
        } else {
            $result = $this->wp_api_network_roles_update();            
        }

        if ($this->debug) {
            echo '<div class="updated fade below-h2">Roles updated for ' . ( microtime() - $time_shot ) . ' milliseconds</div>';
        }

        return $result;
    }
    // end of multisite_update_roles()

    
    /**
     * Process user request on update roles
     * 
     * @global wpdb $wpdb
     * @return boolean
     */
    protected function update_roles() {
        global $wp_roles;
        
        if ($this->multisite && is_super_admin() && $this->apply_to_all) {  // update Role for the all blogs/sites in the network (permitted to superadmin only)
            if (!$this->multisite_update_roles()) {
                return false;
            }
        } else {
            if (!$this->save_roles()) {
                return false;
            }
        }

        // refresh global $wp_roles
        $wp_roles = new WP_Roles();
        
        return true;
    }
    // end of update_roles()

    
    /**
     * Write message to the log file
     * 
     * @global type $wp_version
     * @param string $message
     * @param boolean $show_message
     */
    protected function log_event($message, $show_message = false) {
        global $wp_version, $wpdb;

        $file_name = URE_PLUGIN_DIR . 'user-role-editor.log';
        $fh = fopen($file_name, 'a');
        $cr = "\n";
        $s = $cr . date("d-m-Y H:i:s") . $cr .
                'WordPress version: ' . $wp_version . ', PHP version: ' . phpversion() . ', MySQL version: ' . $wpdb->db_version() . $cr;
        fwrite($fh, $s);
        fwrite($fh, $message . $cr);
        fclose($fh);

        if ($show_message) {
            $this->show_message('Error! ' . esc_html__('Error is occur. Please check the log file.', 'user-role-editor'));
        }
    }
    // end of log_event()

    
    /**
     * returns array without capabilities blocked for single site administrators
     * @param array $capabilities
     * @return array
     */
    protected function remove_caps_not_allowed_for_single_admin($capabilities) {
        
        foreach(array_keys($capabilities) as $cap) {
            if ($this->block_cap_for_single_admin($cap)) {
                unset($capabilities[$cap]);
            }
        }
        
        return $capabilities;
    }
    // end of remove_caps_not_allowed_for_single_admin()
    
    
    /**
     * process new role create request
     * 
     * @global WP_Roles $wp_roles
     * 
     * @return string   - message about operation result
     * 
     */
    protected function add_new_role() {

        global $wp_roles;

        if (!current_user_can('ure_create_roles')) {
            return esc_html__('Insufficient permissions to work with User Role Editor','user-role-editor');
        }
        $mess = '';
        $this->current_role = '';
        if (isset($_POST['user_role_id']) && $_POST['user_role_id']) {
            $user_role_id = utf8_decode($_POST['user_role_id']);
            // sanitize user input for security
            $valid_name = preg_match('/[A-Za-z0-9_\-]*/', $user_role_id, $match);
            if (!$valid_name || ($valid_name && ($match[0] != $user_role_id))) { // some non-alphanumeric charactes found!
                return esc_html__('Error: Role ID must contain latin characters, digits, hyphens or underscore only!', 'user-role-editor');
            }
            $numeric_name = preg_match('/[0-9]*/', $user_role_id, $match);
            if ($numeric_name && ($match[0] == $user_role_id)) { // numeric name discovered
                return esc_html__('Error: WordPress does not support numeric Role name (ID). Add latin characters to it.', 'user-role-editor');
            }
            
            if ($user_role_id) {
                $user_role_name = isset($_POST['user_role_name']) ? $_POST['user_role_name'] : false;
                if (!empty($user_role_name)) {
                    $user_role_name = sanitize_text_field($user_role_name);
                } else {
                    $user_role_name = $user_role_id;  // as user role name is empty, use user role ID instead
                }

                if (!isset($wp_roles)) {
                    $wp_roles = new WP_Roles();
                }
                if (isset($wp_roles->roles[$user_role_id])) {
                    return sprintf('Error! ' . esc_html__('Role %s exists already', 'user-role-editor'), $user_role_id);
                }
                $user_role_id = strtolower($user_role_id);
                $this->current_role = $user_role_id;

                $user_role_copy_from = isset($_POST['user_role_copy_from']) ? $_POST['user_role_copy_from'] : false;
                if (!empty($user_role_copy_from) && $user_role_copy_from != 'none' && $wp_roles->is_role($user_role_copy_from)) {
                    $role = $wp_roles->get_role($user_role_copy_from);
                    $capabilities = $this->remove_caps_not_allowed_for_single_admin($role->capabilities);
                } else {
                    $capabilities = array('read' => true, 'level_0' => true);
                }
                // add new role to the roles array      
                $result = add_role($user_role_id, $user_role_name, $capabilities);
                if (!isset($result) || empty($result)) {
                    $mess = 'Error! ' . esc_html__('Error is encountered during new role create operation', 'user-role-editor');
                } else {
                    $mess = sprintf(esc_html__('Role %s is created successfully', 'user-role-editor'), $user_role_name);
                }
            }
        }
        return $mess;
    }
    // end of new_role_create()            
    
    
    /**
     * process rename role request
     * 
     * @global WP_Roles $wp_roles
     * 
     * @return string   - message about operation result
     * 
     */
    protected function rename_role() {

        global $wp_roles;

        $mess = '';
        $user_role_id = filter_input(INPUT_POST, 'user_role_id', FILTER_SANITIZE_STRING);
        if (empty($user_role_id)) {
            return esc_html__('Error: Role ID is empty!', 'user-role-editor');
        }
        $user_role_id = utf8_decode($user_role_id);
        // sanitize user input for security
        $match = array();
        $valid_name = preg_match('/[A-Za-z0-9_\-]*/', $user_role_id, $match);
        if (!$valid_name || ($valid_name && ($match[0] != $user_role_id))) { // some non-alphanumeric charactes found!
            return esc_html__('Error: Role ID must contain latin characters, digits, hyphens or underscore only!', 'user-role-editor');
        }
        $numeric_name = preg_match('/[0-9]*/', $user_role_id, $match);
        if ($numeric_name && ($match[0] == $user_role_id)) { // numeric name discovered
            return esc_html__('Error: WordPress does not support numeric Role name (ID). Add latin characters to it.', 'user-role-editor');
        }

        $new_role_name = filter_input(INPUT_POST, 'user_role_name', FILTER_SANITIZE_STRING);
        if (!empty($new_role_name)) {
            $new_role_name = sanitize_text_field($new_role_name);
        } else {
            return esc_html__('Error: Empty role display name is not allowed.', 'user-role-editor');
        }

        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        if (!isset($wp_roles->roles[$user_role_id])) {
            return sprintf('Error! ' . esc_html__('Role %s does not exists', 'user-role-editor'), $user_role_id);
        }                
        $this->current_role = $user_role_id;
        $this->current_role_name = $new_role_name;

        $old_role_name = $wp_roles->roles[$user_role_id]['name'];
        $wp_roles->roles[$user_role_id]['name'] = $new_role_name;
        update_option( $wp_roles->role_key, $wp_roles->roles );
        $mess = sprintf(esc_html__('Role %s is renamed to %s successfully', 'user-role-editor'), $old_role_name, $new_role_name);
        
        return $mess;
    }
    // end of rename_role()

    
    /**
     * Deletes user role from the WP database
     */
    protected function delete_wp_roles($roles_to_del) {
        global $wp_roles;

        if (!current_user_can('ure_delete_roles')) {
            return esc_html__('Insufficient permissions to work with User Role Editor','user-role-editor');
        }
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        $result = false;
        foreach($roles_to_del as $role_id) {
            if (!isset($wp_roles->roles[$role_id])) {
                $result = false;
                break;
            }                                            
            if ($this->role_contains_caps_not_allowed_for_simple_admin($role_id)) { // do not delete
                continue;
            }
            unset($wp_roles->role_objects[$role_id]);
            unset($wp_roles->role_names[$role_id]);
            unset($wp_roles->roles[$role_id]);                
            $result = true;
        }   // foreach()
        if ($result) {
            update_option($wp_roles->role_key, $wp_roles->roles);
        }
        
        return $result;
    }
    // end of delete_wp_roles()
    
    
    protected function delete_all_unused_roles() {        
        
        $this->roles = $this->get_user_roles();
        $roles_to_del = array_keys($this->get_roles_can_delete());  
        $result = $this->delete_wp_roles($roles_to_del);
        $this->roles = null;    // to force roles refresh
        
        return $result;        
    }
    // end of delete_all_unused_roles()
    
    
    /**
     * process user request for user role deletion
     * @global WP_Roles $wp_roles
     * @return type
     */
    protected function delete_role() {        

        if (!current_user_can('ure_delete_roles')) {
            return esc_html__('Insufficient permissions to work with User Role Editor','user-role-editor');
        }
        $mess = '';        
        if (isset($_POST['user_role_id']) && $_POST['user_role_id']) {
            $role = $_POST['user_role_id'];
            if ($role==-1) { // delete all unused roles
                $result = $this->delete_all_unused_roles();
            } else {
                $result = $this->delete_wp_roles(array($role));
            }
            if (empty($result)) {
                $mess = 'Error! ' . esc_html__('Error encountered during role delete operation', 'user-role-editor');
            } elseif ($role==-1) {
                $mess = sprintf(esc_html__('Unused roles are deleted successfully', 'user-role-editor'), $role);
            } else {
                $mess = sprintf(esc_html__('Role %s is deleted successfully', 'user-role-editor'), $role);
            }
            unset($_POST['user_role']);
        }

        return $mess;
    }
    // end of ure_delete_role()

    
    /**
     * Change default WordPress role
     * @global WP_Roles $wp_roles
     * @return string
     */
    protected function change_default_role() {
        global $wp_roles;

        if (!$this->multisite || is_network_admin()) {
            return 'Try to misuse the plugin functionality';
        }
        $mess = '';
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        if (!empty($_POST['user_role_id'])) {
            $user_role_id = $_POST['user_role_id'];
            unset($_POST['user_role_id']);
            if (isset($wp_roles->role_objects[$user_role_id]) && $user_role_id !== 'administrator') {
                $result = update_option('default_role', $user_role_id);
                if (empty($result)) {
                    $mess = 'Error! ' . esc_html__('Error encountered during default role change operation', 'user-role-editor');
                } else {
                    $this->get_default_role();
                    $mess = sprintf(esc_html__('Default role for new users is set to %s successfully', 'user-role-editor'), $wp_roles->role_names[$user_role_id]);
                }
            } elseif ($user_role_id === 'administrator') {
                $mess = 'Error! ' . esc_html__('Can not set Administrator role as a default one', 'user-role-editor');
            } else {
                $mess = 'Error! ' . esc_html__('This role does not exist - ', 'user-role-editor') . esc_html($user_role_id);            
            }
        }

        return $mess;
    }
    // end of change_default_role()
    
    
    /**
     * Not really used in the plugin - just storage for the translation strings
     */
    protected function translation_data() {
// for the translation purpose
        if (false) {
// Standard WordPress roles
            __('Editor', 'user-role-editor');
            __('Author', 'user-role-editor');
            __('Contributor', 'user-role-editor');
            __('Subscriber', 'user-role-editor');
// Standard WordPress capabilities
            __('Switch themes', 'user-role-editor');
            __('Edit themes', 'user-role-editor');
            __('Activate plugins', 'user-role-editor');
            __('Edit plugins', 'user-role-editor');
            __('Edit users', 'user-role-editor');
            __('Edit files', 'user-role-editor');
            __('Manage options', 'user-role-editor');
            __('Moderate comments', 'user-role-editor');
            __('Manage categories', 'user-role-editor');
            __('Manage links', 'user-role-editor');
            __('Upload files', 'user-role-editor');
            __('Import', 'user-role-editor');
            __('Unfiltered html', 'user-role-editor');
            __('Edit posts', 'user-role-editor');
            __('Edit others posts', 'user-role-editor');
            __('Edit published posts', 'user-role-editor');
            __('Publish posts', 'user-role-editor');
            __('Edit pages', 'user-role-editor');
            __('Read', 'user-role-editor');
            __('Level 10', 'user-role-editor');
            __('Level 9', 'user-role-editor');
            __('Level 8', 'user-role-editor');
            __('Level 7', 'user-role-editor');
            __('Level 6', 'user-role-editor');
            __('Level 5', 'user-role-editor');
            __('Level 4', 'user-role-editor');
            __('Level 3', 'user-role-editor');
            __('Level 2', 'user-role-editor');
            __('Level 1', 'user-role-editor');
            __('Level 0', 'user-role-editor');
            __('Edit others pages', 'user-role-editor');
            __('Edit published pages', 'user-role-editor');
            __('Publish pages', 'user-role-editor');
            __('Delete pages', 'user-role-editor');
            __('Delete others pages', 'user-role-editor');
            __('Delete published pages', 'user-role-editor');
            __('Delete posts', 'user-role-editor');
            __('Delete others posts', 'user-role-editor');
            __('Delete published posts', 'user-role-editor');
            __('Delete private posts', 'user-role-editor');
            __('Edit private posts', 'user-role-editor');
            __('Read private posts', 'user-role-editor');
            __('Delete private pages', 'user-role-editor');
            __('Edit private pages', 'user-role-editor');
            __('Read private pages', 'user-role-editor');
            __('Delete users', 'user-role-editor');
            __('Create users', 'user-role-editor');
            __('Unfiltered upload', 'user-role-editor');
            __('Edit dashboard', 'user-role-editor');
            __('Update plugins', 'user-role-editor');
            __('Delete plugins', 'user-role-editor');
            __('Install plugins', 'user-role-editor');
            __('Update themes', 'user-role-editor');
            __('Install themes', 'user-role-editor');
            __('Update core', 'user-role-editor');
            __('List users', 'user-role-editor');
            __('Remove users', 'user-role-editor');
            __('Add users', 'user-role-editor');
            __('Promote users', 'user-role-editor');
            __('Edit theme options', 'user-role-editor');
            __('Delete themes', 'user-role-editor');
            __('Export', 'user-role-editor');
        }
    }
    // end of ure_TranslationData()

    
    /**
     * placeholder - realized at the Pro version
     */
    protected function check_blog_user($user) {
        
        return true;
    }
    // end of check_blog_user()
    
    /**
     * placeholder - realized at the Pro version
     */    
    protected function network_update_user($user) {
        
        return true;
    }
    // end of network_update_user()
    
    
    /**
     * Update user roles and capabilities
     * 
     * @global WP_Roles $wp_roles
     * @param WP_User $user
     * @return boolean
     */
    protected function update_user($user) {
        global $wp_roles;
                
        if ($this->multisite) {
            if (!$this->check_blog_user($user)) {
                return false;
            }
        }
        
        $primary_role = $_POST['primary_role'];  
        if (empty($primary_role) || !isset($wp_roles->roles[$primary_role])) {
            $primary_role = '';
        }
        if (function_exists('bbp_filter_blog_editable_roles')) {  // bbPress plugin is active
            $bbp_user_role = bbp_get_user_role($user->ID);
        } else {
            $bbp_user_role = '';
        }
        
        $edit_user_caps_mode = $this->get_edit_user_caps_mode();
        if (!$edit_user_caps_mode) {    // readonly mode
            $this->capabilities_to_save = $user->caps;
        }
        
        // revoke all roles and capabilities from this user
        $user->roles = array();
        $user->remove_all_caps();

        // restore primary role
        if (!empty($primary_role)) {
            $user->add_role($primary_role);
        }

        // restore bbPress user role if she had one
        if (!empty($bbp_user_role)) {
            $user->add_role($bbp_user_role);
        }

        // add other roles to user
        foreach ($_POST as $key => $value) {
            $result = preg_match('/^wp_role_(.+)/', $key, $match);
            if ($result === 1) {
                $role = $match[1];
                if (isset($wp_roles->roles[$role])) {
                    $user->add_role($role);
                    if (!$edit_user_caps_mode && isset($this->capabilities_to_save[$role])) {
                        unset($this->capabilities_to_save[$role]);
                    }
                }
            }
        }

        
        
        // add individual capabilities to user
        if (count($this->capabilities_to_save) > 0) {
            foreach ($this->capabilities_to_save as $key => $value) {
                $user->add_cap($key);
            }
        }
        $user->update_user_level_from_caps();
        do_action('profile_update', $user->ID, $user);  // in order other plugins may hook to the user permissions update
        
        if ($this->apply_to_all) { // apply update to the all network
            if (!$this->network_update_user($user)) {
                return false;
            }
        }
        
        return true;
    }
    // end of update_user()

    
    /**
     * Returns administrator role ID
     * 
     * @return string
     */        
    protected function get_admin_role() {
        
        if (isset($this->roles['administrator'])) {
            $admin_role_id = 'administrator';
        } else {        
            // go through all roles and select one with max quant of capabilities included
            $max_caps = -1;
            $admin_role_id = '';
            foreach(array_keys($this->roles) as $role_id) {
                $caps = count($this->roles[$role_id]['capabilities']);
                if ($caps>$max_caps) {
                    $max_caps = $caps;
                    $admin_role_id = $role_id;
                }
            }
        }        
        
        return $admin_role_id;
    }
    // end get_admin_role()
    
    
    /**
     * Add new capability
     * 
     * @global WP_Roles $wp_roles
     * @return string
     */
    protected function add_new_capability() {
        global $wp_roles;

        if (!current_user_can('ure_create_capabilities')) {
            return esc_html__('Insufficient permissions to work with User Role Editor','user-role-editor');
        }
        $mess = '';
        if (!isset($_POST['capability_id']) || empty($_POST['capability_id'])) {
            return 'Wrong Request';
        }
        
        $user_capability = $_POST['capability_id'];
        // sanitize user input for security
        $valid_name = preg_match('/[A-Za-z0-9_\-]*/', $user_capability, $match);
        if (!$valid_name || ($valid_name && ($match[0] != $user_capability))) { // some non-alphanumeric charactes found!    
            return esc_html__('Error: Capability name must contain latin characters and digits only!', 'user-role-editor');
        }

        $user_capability = strtolower($user_capability);                
        $this->get_user_roles();
        $this->init_full_capabilities();
        if (!isset($this->full_capabilities[$user_capability])) {
            $admin_role = $this->get_admin_role();            
            $wp_roles->use_db = true;
            $wp_roles->add_cap($admin_role, $user_capability);
            $mess = sprintf(esc_html__('Capability %s is added successfully', 'user-role-editor'), $user_capability);
        } else {
            $mess = sprintf(esc_html__('Capability %s exists already', 'user-role-editor'), $user_capability);
        }
        
        return $mess;
    }
    // end of add_new_capability()

    
    /**
     * Delete capability
     * 
     * @global wpdb $wpdb
     * @global WP_Roles $wp_roles
     * @return string - information message
     */
    protected function delete_capability() {
        global $wpdb, $wp_roles;

        
        if (!current_user_can('ure_delete_capabilities')) {
            return esc_html__('Insufficient permissions to work with User Role Editor','user-role-editor');
        }
        $mess = '';
        if (!empty($_POST['user_capability_id'])) {
            $capability_id = $_POST['user_capability_id'];
            $caps_to_remove = $this->get_caps_to_remove();
            if (!is_array($caps_to_remove) || count($caps_to_remove) == 0 || !isset($caps_to_remove[$capability_id])) {
                return sprintf(esc_html__('Error! You do not have permission to delete this capability: %s!', 'user-role-editor'), $capability_id);
            }

            // process users
            $usersId = $wpdb->get_col("SELECT $wpdb->users.ID FROM $wpdb->users");
            foreach ($usersId as $user_id) {
                $user = get_user_to_edit($user_id);
                if ($user->has_cap($capability_id)) {
                    $user->remove_cap($capability_id);
                }
            }

            // process roles
            foreach ($wp_roles->role_objects as $wp_role) {
                if ($wp_role->has_cap($capability_id)) {
                    $wp_role->remove_cap($capability_id);
                }
            }

            $mess = sprintf(esc_html__('Capability %s was removed successfully', 'user-role-editor'), $capability_id);
        }

        return $mess;
    }
    // end of remove_capability()

    
    /**
     * Returns text presentation of user roles
     * 
     * @param type $roles user roles list
     * @return string
     */
    public function roles_text($roles) {
        global $wp_roles;

        if (is_array($roles) && count($roles) > 0) {
            $role_names = array();
            foreach ($roles as $role) {
                $role_names[] = $wp_roles->roles[$role]['name'];
            }
            $output = implode(', ', $role_names);
        } else {
            $output = '';
        }

        return $output;
    }
    // end of roles_text()
    
    
    public function about() {
        if ($this->is_pro()) {
            return;
        }

?>		  
            <h2>User Role Editor</h2>         
            
            <strong><?php esc_html_e('Version:', 'user-role-editor');?></strong> <?php echo URE_VERSION; ?><br/><br/>
            <a class="ure_rsb_link" style="background-image:url(<?php echo URE_PLUGIN_URL . 'images/vladimir.png'; ?>);" target="_blank" href="http://www.shinephp.com/"><?php _e("Author's website", 'user-role-editor'); ?></a><br/>
            <a class="ure_rsb_link" style="background-image:url(<?php echo URE_PLUGIN_URL . 'images/user-role-editor-icon.png'; ?>);" target="_blank" href="https://www.role-editor.com"><?php _e('Plugin webpage', 'user-role-editor'); ?></a><br/>
            <a class="ure_rsb_link" style="background-image:url(<?php echo URE_PLUGIN_URL . 'images/user-role-editor-icon.png'; ?>);" target="_blank" href="https://www.role-editor.com/download-plugin"><?php _e('Plugin download', 'user-role-editor'); ?></a><br/>
            <a class="ure_rsb_link" style="background-image:url(<?php echo URE_PLUGIN_URL . 'images/changelog-icon.png'; ?>);" target="_blank" href="https://www.role-editor.com/changelog"><?php _e('Changelog', 'user-role-editor'); ?></a><br/>
            <a class="ure_rsb_link" style="background-image:url(<?php echo URE_PLUGIN_URL . 'images/faq-icon.png'; ?>);" target="_blank" href="http://www.shinephp.com/user-role-editor-wordpress-plugin/#faq"><?php _e('FAQ', 'user-role-editor'); ?></a><br/>
            <hr />
                <div style="text-align: center;">
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="encrypted" 
                               value="-----BEGIN PKCS7-----MIIHZwYJKoZIhvcNAQcEoIIHWDCCB1QCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYBME5QAQYFDddWBHA4YXI1x3dYmM77clH5s0CgokYnLVk0P8keOxMtYyNQo6xJs6pY1nJfE3tqNg8CZ3btJjmOUa6DsE+K8Nm6OxGHMQF45z8WAs+f/AvQWdSpPXD0eSMu9osNgmC3yv46hOT3B1J3rKkpeZzMThCdUfECqu+lluzELMAkGBSsOAwIaBQAwgeQGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIeMSZk/UuZnuAgcAort75TUUbtDhmdTi1N0tR9W75Ypuw5nBw01HkZFsFHoGezoT95c3ZesHAlVprhztPrizl1UzE9COQs+3p62a0o+BlxUolkqUT3AecE9qs9dNshqreSvmC8SOpirOroK3WE7DStUvViBfgoNAPTTyTIAKKX24uNXjfvx1jFGMQGBcFysbb3OTkc/B6OiU2G951U9R8dvotaE1RQu6JwaRgwA3FEY9d/P8M+XdproiC324nzFel5WlZ8vtDnMyuPxOgggOHMIIDgzCCAuygAwIBAgIBADANBgkqhkiG9w0BAQUFADCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wHhcNMDQwMjEzMTAxMzE1WhcNMzUwMjEzMTAxMzE1WjCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMFHTt38RMxLXJyO2SmS+Ndl72T7oKJ4u4uw+6awntALWh03PewmIJuzbALScsTS4sZoS1fKciBGoh11gIfHzylvkdNe/hJl66/RGqrj5rFb08sAABNTzDTiqqNpJeBsYs/c2aiGozptX2RlnBktH+SUNpAajW724Nv2Wvhif6sFAgMBAAGjge4wgeswHQYDVR0OBBYEFJaffLvGbxe9WT9S1wob7BDWZJRrMIG7BgNVHSMEgbMwgbCAFJaffLvGbxe9WT9S1wob7BDWZJRroYGUpIGRMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbYIBADAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAIFfOlaagFrl71+jq6OKidbWFSE+Q4FqROvdgIONth+8kSK//Y/4ihuE4Ymvzn5ceE3S/iBSQQMjyvb+s2TWbQYDwcp129OPIbD9epdr4tJOUNiSojw7BHwYRiPh58S1xGlFgHFXwrEBb3dgNbMUa+u4qectsMAXpVHnD9wIyfmHMYIBmjCCAZYCAQEwgZQwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tAgEAMAkGBSsOAwIaBQCgXTAYBgkqhkiG9w0BCQMxCwYJKoZIhvcNAQcBMBwGCSqGSIb3DQEJBTEPFw0xMTEyMTAwODU3MjdaMCMGCSqGSIb3DQEJBDEWBBSFh6YmkoVtYdMaDd5G6EN0dGcPpzANBgkqhkiG9w0BAQEFAASBgAB91K/+gsmpbKxILdCVXCkiOg1zSG+tfq2EZSNzf8z/R1E3HH8qPdm68OToILsgWohKFwE+RCwcQ0iq77wd0alnWoknvhBBoFC/U0yJ3XmA3Hkgrcu6yhVijY/Odmf6WWcz79/uLGkvBSECbjTY0GLxvhRlsh2nAioCfxAr1cFo-----END PKCS7-----">
                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">                        
                    </form>                        
                </div>
<?php         
    }
    // end of about()

    
    protected function set_current_role() {
        if (!isset($this->current_role) || !$this->current_role) {
            if (isset($_REQUEST['user_role']) && $_REQUEST['user_role'] && isset($this->roles[$_REQUEST['user_role']])) {
                $this->current_role = $_REQUEST['user_role'];
            } else {
                $this->current_role = $this->get_last_role_id();
            }
            $this->current_role_name = $this->roles[$this->current_role]['name'];
        }
    }
    // end of set_current_role()
    
    
    public function show_admin_role_allowed() {
        $show_admin_role = $this->get_option('show_admin_role', 0);
        $show_admin_role = ((defined('URE_SHOW_ADMIN_ROLE') && URE_SHOW_ADMIN_ROLE==1) || $show_admin_role==1) && $this->user_is_admin();
        
        return $show_admin_role;
    }
    // end of show_admin_role()
        
    
    // returns true if $user has $capability assigned through the roles or directly
    // returns true if user has role with name equal $capability
    public function user_can($capability) {
        
        if (isset($this->user_to_edit->caps[$capability])) {
            return true;
        }
        foreach ($this->user_to_edit->roles as $role) {
            if ($role===$capability) {
                return true;
            }
            if (!empty($this->roles[$role]['capabilities'][$capability])) {
                return true;
            }
        }
                
        return false;        
    }
    // end of user_can()           
    
    
    // returns true if current user has $capability assigned through the roles or directly
    // returns true if current user has role with name equal $cap
    public function user_has_capability($user, $cap) {

        global $wp_roles;

        if (!is_object($user) || empty($user->ID)) {
            return false;
        }
        if (is_multisite() && is_super_admin($user->ID)) {
            return true;
        }

        if (isset($user->caps[$cap])) {
            return true;
        }
        foreach ($user->roles as $role) {
            if ($role === $cap) {
                return true;
            }
            if (!empty($wp_roles->roles[$role]['capabilities'][$cap])) {
                return true;
            }
        }

        return false;
    }
    // end of user_has_capability()           
        
    
    public function show_other_default_roles() {
        $other_default_roles = $this->get_option('other_default_roles', array());
        foreach ($this->roles as $role_id => $role) {
            if ( $role_id=='administrator' || $role_id==$this->wp_default_role ) {			
                continue;
            }
            if ( in_array($role_id, $other_default_roles) ) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
            echo '<label for="wp_role_' . $role_id .'"><input type="checkbox"	id="wp_role_' . $role_id . 
                '" name="wp_role_' . $role_id . '" value="' . $role_id . '"' . $checked .' />&nbsp;' . 
                esc_html__($role['name'], 'user-role-editor') . '</label><br />';
          }		
           
    }
    // end of show_other_default_roles()
                   
    
    public function get_current_role() {
        
        return $this->current_role;
        
    }
    // end of get_current_role()
    
    
    public function get_edit_user_caps_mode() {
        if ($this->multisite && is_super_admin()) {
            return 1;
        }
        
        $edit_user_caps = $this->get_option('edit_user_caps', 1);
        
        return $edit_user_caps;
    }
    // end of get_edit_user_caps_mode()
    
    
    /**
     * Returns comma separated string of capabilities directly (not through the roles) assigned to the user
     * 
     * @global WP_Roles $wp_roles
     * @param object $user
     * @return string
     */
    public function get_edited_user_caps($user) {
        global $wp_roles;
        
        $output = '';
        foreach ($user->caps as $cap => $value) {
            if (!$wp_roles->is_role($cap)) {
                if ('' != $output) {
                    $output .= ', ';
                }
                $output .= $value ? $cap : sprintf(__('Denied: %s'), $cap);
            }
        }
        
        return $output;
    }
    // end of get_edited_user_caps()
            
    
    public function is_user_profile_extention_allowed() {
        // Check if we are not at the network admin center
        $result = stripos($_SERVER['REQUEST_URI'], 'network/user-edit.php') == false;
        
        return $result;
    }
    // end of is_user_profile_extention_allowed()

    
    // create assign_role object
    public function get_assign_role() {
        $assign_role = new URE_Assign_Role($this);
        
        return $assign_role;
    }
    // end of get_assign_role()
    
    
    public function get_ure_page_url() {
        $page_url = URE_WP_ADMIN_URL . URE_PARENT . '?page=users-' . URE_PLUGIN_FILE;
        $object = $this->get_request_var('object', 'get');
        $user_id = $this->get_request_var('user_id', 'get', 'int');
        if ($object=='user' && $user_id>0) {
            $page_url .= '&object=user&user_id='. $user_id;
        }
        
        return $page_url;
    }
    // end of get_ure_page_url()
}
// end of URE_Lib class