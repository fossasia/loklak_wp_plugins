<?php
/**
 * Role capabilities View class to output HTML with role capabilities
 *
 * @package    User-Role-Editor
 * @subpackage Admin
 * @author     Vladimir Garagulya <support@role-editor.com>
 * @copyright  Copyright (c) 2010 - 2016, Vladimir Garagulya
 **/
class URE_Role_View extends URE_View {
 
    private $lib = null;
    public $role_default_html = '';
    private $role_to_copy_html = '';
    private $role_select_html = '';
    private $role_delete_html = '';
    private $capability_remove_html = '';
    
    
    public function __construct() {        
        
        parent::__construct();
        $this->lib = URE_Lib::get_instance();        
        
    }
    // end of __construct()
    
    
    public function role_default_prepare_html($select_width=200) {
                        
        $roles = $this->lib->get('roles');
        if (!isset($roles) || !$roles) {
            // get roles data from database
            $roles = $this->lib->get_user_roles();
        }
        
        $caps_access_restrict_for_simple_admin = $this->lib->get_option('caps_access_restrict_for_simple_admin', 0);
        $show_admin_role = $this->lib->show_admin_role_allowed();
        if ($select_width>0) {
            $select_style = 'style="width: '. $select_width .'px"';
        } else {
            $select_style = '';
        }
        $wp_default_role = $this->lib->get('wp_default_role');
        $this->role_default_html = '<select id="default_user_role" name="default_user_role" '. $select_style .'>';
        foreach ($roles as $key => $value) {
            $selected = $this->lib->option_selected($key, $wp_default_role);
            $disabled = ($key==='administrator' && $caps_access_restrict_for_simple_admin && !is_super_admin()) ? 'disabled' : '';
            if ($show_admin_role || $key != 'administrator') {
                $translated_name = esc_html__($value['name'], 'user-role-editor');  // get translation from URE language file, if exists
                if ($translated_name === $value['name']) { // get WordPress internal translation
                    $translated_name = translate_user_role($translated_name);
                }
                $translated_name .= ' (' . $key . ')';                
                $this->role_default_html .= '<option value="' . $key . '" ' . $selected .' '. $disabled .'>' . $translated_name . '</option>';
            }
        }
        $this->role_default_html .= '</select>';
        
    }
    // end of role_default_prepare_html()
    
    
    private function role_select_copy_prepare_html($select_width=200) {
        
        global $current_user;
        
        $key_capability = $this->lib->get_key_capability();
        $user_is_ure_admin = current_user_can($key_capability);
        $role_to_skip = ($user_is_ure_admin) ? '':$current_user->roles[0];
        
        $caps_access_restrict_for_simple_admin = $this->lib->get_option('caps_access_restrict_for_simple_admin', 0);
        $show_admin_role = $this->lib->show_admin_role_allowed();
        $this->role_to_copy_html = '<select id="user_role_copy_from" name="user_role_copy_from" style="width: '. $select_width .'px">
            <option value="none" selected="selected">' . esc_html__('None', 'user-role-editor') . '</option>';
        $this->role_select_html = '<select id="user_role" name="user_role" onchange="ure_role_change(this.value);">';        
        $current_role = $this->lib->get('current_role');
        $roles = $this->lib->get_editable_user_roles();
        foreach ($roles as $key => $value) {
            if ($key===$role_to_skip) { //  skip role of current user if he does not have full access to URE
                continue;
            }            
            $selected1 = $this->lib->option_selected($key, $current_role);
            $disabled = ($key==='administrator' && $caps_access_restrict_for_simple_admin && !is_super_admin()) ? 'disabled' : '';
            if ($show_admin_role || $key != 'administrator') {
                $translated_name = esc_html__($value['name'], 'user-role-editor');  // get translation from URE language file, if exists
                if ($translated_name === $value['name']) { // get WordPress internal translation
                    $translated_name = translate_user_role($translated_name);
                }
                $translated_name .= ' (' . $key . ')';                
                $this->role_select_html .= '<option value="' . $key . '" ' . $selected1 .' '. $disabled .'>' . $translated_name . '</option>';
                $this->role_to_copy_html .= '<option value="' . $key .'" '. $disabled .'>' . $translated_name . '</option>';
            }
        }
        $this->role_select_html .= '</select>';
        $this->role_to_copy_html .= '</select>';
    }
    // end of role_select_copy_prepare_html()


    private function role_delete_prepare_html() {
        $roles_can_delete = $this->lib->get_roles_can_delete();
        if ($roles_can_delete && count($roles_can_delete) > 0) {
            $this->role_delete_html = '<select id="del_user_role" name="del_user_role" width="200" style="width: 200px">';
            foreach ($roles_can_delete as $key => $value) {
                $this->role_delete_html .= '<option value="' . $key . '">' . esc_html__($value, 'user-role-editor') . '</option>';
            }
            $this->role_delete_html .= '<option value="-1" style="color: red;">' . esc_html__('Delete All Unused Roles', 'user-role-editor') . '</option>';
            $this->role_delete_html .= '</select>';
        } else {
            $this->role_delete_html = '';
        }
    }
    // end of role_delete_prepare_html()
    
    
    /**
     * Build HTML for select drop-down list from capabilities we can remove
     * 
     * @return string
     **/
    protected function caps_to_remove_prepare_html() {
        
        $caps_to_remove = $this->lib->get_caps_to_remove();
        if (!empty($caps_to_remove) && is_array($caps_to_remove) && count($caps_to_remove) > 0) {
            $html = '<select id="remove_user_capability" name="remove_user_capability" width="200" style="width: 200px">';
            foreach (array_keys($caps_to_remove) as $key) {
                $html .= '<option value="' . $key . '">' . $key . '</option>';
            }
            $html .= '</select>';
        } else {
            $html = '';
        }

        $this->capability_remove_html = $html;
    }
    // end of caps_to_remove_prepare_html()

    
    public function role_edit_prepare_html($select_width=200) {
        
        $this->role_select_copy_prepare_html($select_width);
        $multisite = $this->lib->get('multisite');
        if ($multisite && !is_network_admin()) {
            $this->role_default_prepare_html($select_width);
        }        
        $this->role_delete_prepare_html();                
        $this->caps_to_remove_prepare_html();
    }
    // end of role_edit_prepare_html()

    
    public function display_edit_dialogs() {
        $multisite = $this->lib->get('multisite');
        $current_role = $this->lib->get('current_role');
        $current_role_name = $this->lib->get('current_role_name');
?>        
<script language="javascript" type="text/javascript">

  var ure_current_role = '<?php echo $current_role; ?>';
  var ure_current_role_name  = '<?php echo $current_role_name; ?>';

</script>

<!-- popup dialogs markup -->
<div id="ure_add_role_dialog" class="ure-modal-dialog" style="padding: 10px;">
  <form id="ure_add_role_form" name="ure_add_role_form" method="POST">    
    <div class="ure-label"><?php esc_html_e('Role name (ID): ', 'user-role-editor'); ?></div>
    <div class="ure-input"><input type="text" name="user_role_id" id="user_role_id" size="25"/></div>
    <div class="ure-label"><?php esc_html_e('Display Role Name: ', 'user-role-editor'); ?></div>
    <div class="ure-input"><input type="text" name="user_role_name" id="user_role_name" size="25"/></div>
    <div class="ure-label"><?php esc_html_e('Make copy of: ', 'user-role-editor'); ?></div>
    <div class="ure-input"><?php echo $this->role_to_copy_html; ?></div>        
  </form>
</div>

<div id="ure_rename_role_dialog" class="ure-modal-dialog" style="padding: 10px;">
  <form id="ure_rename_role_form" name="ure_rename_role_form" method="POST">
    <div class="ure-label"><?php esc_html_e('Role name (ID): ', 'user-role-editor'); ?></div>
    <div class="ure-input"><input type="text" name="ren_user_role_id" id="ren_user_role_id" size="25" disabled /></div>
    <div class="ure-label"><?php esc_html_e('Display Role Name: ', 'user-role-editor'); ?></div>
    <div class="ure-input"><input type="text" name="ren_user_role_name" id="ren_user_role_name" size="25"/></div>    
  </form>
</div>

<div id="ure_delete_role_dialog" class="ure-modal-dialog">
  <div style="padding:10px;">
    <div class="ure-label"><?php esc_html_e('Select Role:', 'user-role-editor');?></div>
    <div class="ure-input"><?php echo $this->role_delete_html; ?></div>
  </div>
</div>

<?php
if ($multisite && !is_network_admin()) {
?>
<div id="ure_default_role_dialog" class="ure-modal-dialog">
  <div style="padding:10px;">
    <?php echo $this->role_default_html; ?>
  </div>  
</div>
<?php
}
?>

<div id="ure_delete_capability_dialog" class="ure-modal-dialog">
  <div style="padding:10px;">
    <div class="ure-label"><?php esc_html_e('Delete:', 'user-role-editor');?></div>
    <div class="ure-input"><?php echo $this->capability_remove_html; ?></div>
  </div>  
</div>

<div id="ure_add_capability_dialog" class="ure-modal-dialog">
  <div style="padding:10px;">
    <div class="ure-label"><?php esc_html_e('Capability name (ID): ', 'user-role-editor'); ?></div>
    <div class="ure-input"><input type="text" name="capability_id" id="capability_id" size="25"/></div>
  </div>  
</div>     

<?php        
        
    }
    // end of output_role_edit_dialogs()

    
    /**
     * output HTML code to create URE toolbar
     * 
     * @param string $this->current_role
     * @param boolean $role_delete
     * @param boolean $capability_remove
     */
    public function toolbar() {
        $caps_access_restrict_for_simple_admin = $this->lib->get_option('caps_access_restrict_for_simple_admin', 0);
        if ($caps_access_restrict_for_simple_admin) {
            $add_del_role_for_simple_admin = $this->lib->get_option('add_del_role_for_simple_admin', 1);
        } else {
            $add_del_role_for_simple_admin = 1;
        }
        $super_admin = is_super_admin();
        $multisite = $this->lib->get('multisite');
        
?>	
        <div id="ure_toolbar" >
               <div id="ure_update">
                <button id="ure_update_role" class="ure_toolbar_button button-primary" >Update</button> 
<?php
            do_action('ure_role_edit_toolbar_update');
?>                                   
               </div>
<?php
            if (!$multisite || $super_admin || $add_del_role_for_simple_admin) { // restrict single site admin
?>
               <hr />               
<?php 
                if (current_user_can('ure_create_roles')) {
?>
               <button id="ure_add_role" class="ure_toolbar_button">Add Role</button>
<?php
                }
?>
               <button id="ure_rename_role" class="ure_toolbar_button">Rename Role</button>   
<?php
            }   // restrict single site admin
            if (!$multisite || $super_admin || !$caps_access_restrict_for_simple_admin) { // restrict single site admin
                if (current_user_can('ure_create_capabilities')) {
?>
               <button id="ure_add_capability" class="ure_toolbar_button">Add Capability</button>
<?php
                }
            }   // restrict single site admin
            
            if (!$multisite || $super_admin || $add_del_role_for_simple_admin) { // restrict single site admin
                if (!empty($this->role_delete_html) && current_user_can('ure_delete_roles')) {
?>  
                   <button id="ure_delete_role" class="ure_toolbar_button">Delete Role</button>
<?php
                }
            } // restrict single site admin
            
            if (!$multisite || $super_admin || !$caps_access_restrict_for_simple_admin) { // restrict single site admin            
                if (!empty($this->capability_remove_html) && current_user_can('ure_delete_capabilities')) {
?>
                   <button id="ure_delete_capability" class="ure_toolbar_button">Delete Capability</button>
<?php
                }
                if ($multisite && !is_network_admin()) {  // Show for single site for WP multisite only
?>
               <hr />
               <button id="ure_default_role" class="ure_toolbar_button">Default Role</button>
               <hr />
<?php
                }
?>
               <div id="ure_service_tools">
<?php
                do_action('ure_role_edit_toolbar_service');
                if (!$multisite || (is_main_site( get_current_blog_id()) || (is_network_admin() && is_super_admin()))) {
                    if (current_user_can('ure_reset_roles')) {
?>                   
                  <button id="ure_reset_roles_button" class="ure_toolbar_button" style="color: red;" title="Reset Roles to its original state">Reset</button> 
<?php
                    }
                }
?>
               </div>
<?php
            }   // restrict single site admin
            ?>           
        </div>  
<?php
        
    }
    // end of toolbar()
    
    
    public function display() {
        
        $multisite = $this->lib->get('multisite');
        $active_for_network = $this->lib->get('active_for_network');
?>

<div class="has-sidebar-content">
  			<div class="postbox" style="float: left; min-width:850px;">
        	<h3>&nbsp;<?php esc_html_e('Select Role and change its capabilities:', 'user-role-editor'); ?> <?php echo $this->role_select_html; ?></h3>         
        	<div class="inside">        
<?php
        $caps_readable = $this->lib->get('caps_readable');
        if ($caps_readable) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }
        $caps_access_restrict_for_simple_admin = $this->lib->get_option('caps_access_restrict_for_simple_admin', 0);
        if (is_super_admin() || !$multisite || !$this->lib->is_pro() || !$caps_access_restrict_for_simple_admin) {
?>              
            <input type="checkbox" name="ure_caps_readable" id="ure_caps_readable" value="1" 
                <?php echo $checked; ?> onclick="ure_turn_caps_readable(0);"/>
            <label for="ure_caps_readable"><?php esc_html_e('Show capabilities in human readable form', 'user-role-editor'); ?></label>&nbsp;&nbsp;
<?php
            $show_deprecated_caps = $this->lib->get('show_deprecated_caps');
            if ($show_deprecated_caps) {
                $checked = 'checked="checked"';
            } else {
                $checked = '';
            }
?>
            <input type="checkbox" name="ure_show_deprecated_caps" id="ure_show_deprecated_caps" value="1" 
                <?php echo $checked; ?> onclick="ure_turn_deprecated_caps(0);"/>
            <label for="ure_show_deprecated_caps"><?php esc_html_e('Show deprecated capabilities', 'user-role-editor'); ?></label>              
<?php
        }
        if ($multisite && $active_for_network && !is_network_admin() && is_main_site(get_current_blog_id()) && is_super_admin()) {
            $hint = esc_html__('If checked, then apply action to ALL sites of this Network');
            $apply_to_all = $this->lib->get('apply_to_all');
            if ($apply_to_all) {
                $checked = 'checked="checked"';
                $fontColor = 'color:#FF0000;';
            } else {
                $checked = '';
                $fontColor = '';
            }
?>
              <div style="float: right; margin-left:10px; margin-right: 20px; <?php echo $fontColor;?>" id="ure_apply_to_all_div">
                  <input type="checkbox" name="ure_apply_to_all" id="ure_apply_to_all" value="1" 
                      <?php echo $checked; ?> title="<?php echo $hint;?>" onclick="ure_applyToAllOnClick(this)"/>
                  <label for="ure_apply_to_all" title="<?php echo $hint;?>"><?php esc_html_e('Apply to All Sites', 'user-role-editor');?></label>
              </div>
<?php
        }
?>
<br /><br />
<hr />
    <div style="display:table-inline; float: right; margin-right: 12px;"></div>	

<?php 
    $this->display_caps(); ?>
    
<?php 
    $ao = $this->lib->get('role_additional_options');
    $current_role = $this->lib->get('current_role');
    $ao->show($current_role);
?>
    <input type="hidden" name="object" value="role" />
<?php
  $this->display_box_end();
?>  
    <div style="clear: left; float: left; width: 800px;"></div>    
</div>
<?php        
        
    }
    // end of display()    
    
}
// end of class URE_Role_View