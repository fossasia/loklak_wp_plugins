<?php
/**
 * User capabilities View class to output HTML with capabilities assigne to the user
 *
 * @package    User-Role-Editor
 * @subpackage Admin
 * @author     Vladimir Garagulya <support@role-editor.com>
 * @copyright  Copyright (c) 2010 - 2016, Vladimir Garagulya
 **/
class URE_User_View extends URE_View {
 
    private $lib = null;
    private $user_to_edit = null;
    
    
    public function __construct() {
        
        parent::__construct();
        $this->lib = URE_Lib::get_instance();
        $this->user_to_edit = $this->lib->get('user_to_edit');
        
    }
    // end of __construct()
    
    
    public function display_edit_dialogs() {
    
    }
    // end of display_edit_dialogs()
    
    
    /**
     * output HTML code to create URE toolbar
     * 
     * @param boolean $role_delete
     * @param boolean $capability_remove
     */
    public function toolbar() {
?>	
            <div id="ure_toolbar" >
                <div id="ure_update">
                    <button id="ure_update_role" class="ure_toolbar_button button-primary">Update</button> 
<?php
                do_action('ure_user_edit_toolbar_update');
?>                                           
              	 </div>	 

            </div>    
        <?php
    }
    // end of toolbar()

    
    private function get_user_info() {
        $switch_to_user = '';
        if (!is_multisite() || current_user_can('manage_network_users')) {
            $anchor_start = '<a href="' . wp_nonce_url("user-edit.php?user_id={$this->user_to_edit->ID}", "ure_user_{$this->user_to_edit->ID}") . '" >';
            $anchor_end = '</a>';
            if (class_exists('user_switching') && current_user_can('switch_to_user', $this->user_to_edit->ID)) {
                $switch_to_user_link = user_switching::switch_to_url($this->user_to_edit);
                $switch_to_user = '<a href="' . esc_url($switch_to_user_link) . '">' . esc_html__('Switch&nbsp;To', 'user-switching') . '</a>';
            }
        } else {
            $anchor_start = '';
            $anchor_end = '';
        }
        $user_info = ' <span style="font-weight: bold;">' . $anchor_start . $this->user_to_edit->user_login;
        if ($this->user_to_edit->display_name !== $this->user_to_edit->user_login) {
            $user_info .= ' (' . $this->user_to_edit->display_name . ')';
        }
        $user_info .= $anchor_end . '</span>';
        if (is_multisite() && is_super_admin($this->user_to_edit->ID)) {
            $user_info .= '  <span style="font-weight: bold; color:red;">' . esc_html__('Network Super Admin', 'user-role-editor') . '</span>';
        }

        if (!empty($switch_to_user)) {
            $user_info .= '&nbsp;&nbsp;&nbsp;&nbsp;' . $switch_to_user;
        }
        
        return $user_info;
    }
    // end of build_user_info()
    
    
    private function show_primary_role_dropdown_list($user_roles) {
?>        
        <select name="primary_role" id="primary_role">
<?php
        // Compare user role against currently editable roles
        $user_roles = array_intersect( array_values( $user_roles ), array_keys( get_editable_roles() ) );
        $user_primary_role  = array_shift( $user_roles );

        // print the full list of roles with the primary one selected.
        wp_dropdown_roles($user_primary_role);

        // print the 'no role' option. Make it selected if the user has no role yet.        
        $selected = ( empty($user_primary_role) ) ? 'selected="selected"' : '';
        echo '<option value="" '. $selected.'>' . esc_html__('&mdash; No role for this site &mdash;') . '</option>';
?>
        </select>
<?php        
    }
    // end of show_primary_role_dropdown_list()
    
    
    private function show_secondary_roles() {
        $show_admin_role = $this->lib->show_admin_role_allowed();
        $values = array_values($this->user_to_edit->roles);
        $primary_role = array_shift($values);  // get 1st element from roles array
        $roles = $this->lib->get('roles');
        foreach ($roles as $role_id => $role) {
            if (($show_admin_role || $role_id != 'administrator') && ($role_id !== $primary_role)) {
                if ($this->lib->user_can($role_id)) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = '';
                }
                echo '<label for="wp_role_' . $role_id . '"><input type="checkbox"	id="wp_role_' . $role_id .
                     '" name="wp_role_' . $role_id . '" value="' . $role_id . '"' . $checked . ' />&nbsp;' .
                esc_html__($role['name'], 'user-role-editor') . '</label><br />';
            }
        }
    }
    // end of show_secondary_roles()
    
    
    public function display() {        
        $caps_readable = $this->lib->get('caps_readable');
        $show_deprecated_caps = $this->lib->get('show_deprecated_caps');
        $edit_user_caps_mode = $this->lib->get_edit_user_caps_mode();
        $caps_access_restrict_for_simple_admin = $this->lib->get_option('caps_access_restrict_for_simple_admin', 0);        
        $user_info = $this->get_user_info();        
?>

<div class="has-sidebar-content">
<?php
        $this->display_box_start(esc_html__('Change capabilities for user', 'user-role-editor'). $user_info, 'min-width:1000px;');
 
?>
<table cellpadding="0" cellspacing="0" style="width: 100%;">
	<tr>
		<td>&nbsp;</td>		
		<td style="padding-left: 10px; padding-bottom: 5px;">
  <?php    
    if (is_super_admin() || !$this->multisite || !class_exists('User_Role_Editor_Pro') || !$caps_access_restrict_for_simple_admin) {  
        if ($caps_readable) {
            $checked = 'checked="checked"';
        } else {
            $checked = '';
        }
?>  
		<input type="checkbox" name="ure_caps_readable" id="ure_caps_readable" value="1" 
      <?php echo $checked; ?> onclick="ure_turn_caps_readable(<?php echo $this->user_to_edit->ID; ?>);"  />
    <label for="ure_caps_readable"><?php esc_html_e('Show capabilities in human readable form', 'user-role-editor'); ?></label>&nbsp;&nbsp;&nbsp;
<?php
    if ($show_deprecated_caps) {
      $checked = 'checked="checked"';
    } else {
      $checked = '';
    }
?>
    <input type="checkbox" name="ure_show_deprecated_caps" id="ure_show_deprecated_caps" value="1" 
        <?php echo $checked; ?> onclick="ure_turn_deprecated_caps(<?php echo $this->user_to_edit->ID; ?>);"/>
    <label for="ure_show_deprecated_caps"><?php esc_html_e('Show deprecated capabilities', 'user-role-editor'); ?></label>      
<?php
    }
?>
		</td>
	</tr>	
	<tr>
		<td class="ure-user-roles">
			<div style="margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Primary Role:', 'user-role-editor'); ?></div>
<?php 
    $this->show_primary_role_dropdown_list($this->user_to_edit->roles);

    if (function_exists('bbp_filter_blog_editable_roles') ) {  // bbPress plugin is active
?>	
	<div style="margin-top: 5px;margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('bbPress Role:', 'user-role-editor'); ?></div>
<?php
        $dynamic_roles = bbp_get_dynamic_roles();
        $bbp_user_role = bbp_get_user_role($this->user_to_edit->ID);
        if (!empty($bbp_user_role)) {
            echo $dynamic_roles[$bbp_user_role]['name']; 
        }
    }
?>
			<div style="margin-top: 5px;margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Other Roles:', 'user-role-editor'); ?></div>
<?php 	
    $this->show_secondary_roles();    
?>
		</td>
		<td style="padding-left: 5px; padding-top: 5px; border-top: 1px solid #ccc;">  	
    <?php $this->display_caps(false, $edit_user_caps_mode ); ?>
		</td>
	</tr>
</table>
  <input type="hidden" name="object" value="user" />
  <input type="hidden" name="user_id" value="<?php echo $this->user_to_edit->ID; ?>" />
<?php
  $this->display_box_end();
?>
  
</div>        
<?php
    }
    // end of display()
        

}
// end of class URE_User_View