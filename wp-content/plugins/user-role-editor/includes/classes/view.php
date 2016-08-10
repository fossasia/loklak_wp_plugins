<?php
/**
 * View class to output any HTML used at User Role Editor
 *
 * @package    User-Role-Editor
 * @subpackage Admin
 * @author     Vladimir Garagulya <support@role-editor.com>
 * @copyright  Copyright (c) 2010 - 2016, Vladimir Garagulya
 **/
class URE_View {
 
    private $lib = null;
    
    
    public function __construct() {
        
        $this->lib = URE_Lib::get_instance();
        
    }
    // end of __construct()
    
    
    /**
     * display opening part of the HTML box with title and CSS style
     * 
     * @param string $title
     * @param string $style 
     */
    public function display_box_start($title, $style = '') {
        ?>
        			<div class="postbox" style="float: left; <?php echo $style; ?>">
        				<h3 style="cursor:default;"><span><?php echo $title ?></span></h3>
        				<div class="inside">
        <?php
    }
    // 	end of display_box_start()


    /**
     * close HTML box opened by display_box_start() call
     */
    public function display_box_end() {
        ?>
        				</div>
        			</div>
        <?php
    }
    // end of display_box_end()


    public function show_caps_groups() {
        $groups = URE_Capabilities_Groups_Manager::get_instance();
        $groups_list = $groups->get_groups_tree();
        $output = '<ul id="ure_caps_groups_list">'. PHP_EOL;
        foreach($groups_list as $group_id=>$group) {
            $spacer = $group_id=='all' ? '' : str_repeat('&nbsp;', $group['level']*2) .'- ';
            $output .= '<li id="ure_caps_group_'. $group_id .'">'. $spacer . $group['caption'] .'</li>'. PHP_EOL;
        }
        $output .= '</ul>'. PHP_EOL;
        
        echo $output;
    }
    // end of show_caps_groups()

    
    private function deprecated_show_and_color($cap_id, $builtin_wp_caps, &$label_style, &$hidden_class) {
        
        if (isset($builtin_wp_caps[$cap_id])) {
            if (in_array('deprecated', $builtin_wp_caps[$cap_id])) {
                $show_deprecated_caps = $this->lib->get('show_deprecated_caps');
                if (!$show_deprecated_caps) {
                    $hidden_class = 'hidden';
                }
                $label_style = 'style="color:#BBBBBB;"';
            }                
        }
        
    }
    // end of deprecated_show_and_color()


    private function blocked_for_single_admin_style($cap_id, &$label_style) {
    
        $blocked = false;
        $multisite = $this->lib->get('multisite');
        if ($multisite && $this->lib->block_cap_for_single_admin($cap_id, true)) {
            if (is_super_admin()) {
                if (!is_network_admin()) {
                    $label_style = 'style="color: red;"';
                }
            } else {
                $blocked = true;
            }
        }

        return $blocked;
    }
    // end of blocked_for_single_admin_style()
    
    
    /**
     * output HTML-code for capabilities list
     * @param boolean $for_role - if true, it is role capabilities list, else - user specific capabilities list
     * @param boolean $edit_mode - if false, capabilities checkboxes are shown as disable - readonly mode
     */
    public function show_capabilities($for_role = true, $edit_mode=true) {
        
        $onclick_for_admin = '';
        $multisite = $this->lib->get('multisite');
        $current_role = $this->lib->get('current_role');
        if (!($multisite && is_super_admin())) {  // do not limit SuperAdmin for multi-site
            if ('administrator'==$current_role) {
                $onclick_for_admin = 'onclick="ure_turn_it_back(this)"';
            }
        }
        $user_to_edit = $this->lib->get('user_to_edit');
        $roles = $this->lib->get('roles');
        $full_capabilities = $this->lib->get('full_capabilities');
        $built_in_wp_caps = $this->lib->get_built_in_wp_caps();        
        $caps_readable = $this->lib->get('caps_readable');
        $caps_groups_manager = URE_Capabilities_Groups_Manager::get_instance();
        
        $key_capability = $this->lib->get_key_capability();
        $user_is_ure_admin = current_user_can($key_capability);
        $ure_caps = $this->lib->get_ure_caps();
        
        $output = '';
        foreach ($full_capabilities as $capability) {    
            $cap_id = $capability['inner'];
            if (!$user_is_ure_admin) { 
                if (isset($ure_caps[$cap_id]) || 
                    ($this->multisite && $cap_id=='manage_network_plugins')) { 
                    // exclude URE caps if user does not have full access to URE
                    continue;
                }
            }
            $label_style = ''; 
            $hidden_class = '';
            
            $this->deprecated_show_and_color($cap_id, $built_in_wp_caps, $label_style, $hidden_class);            
            $blocked = $this->blocked_for_single_admin_style($cap_id, $label_style);
            $classes = array('ure-cap-div');
            if ($blocked) {
                $classes[] = 'blocked';
                $hidden_class = 'hidden';
            }                                                              
            if ($hidden_class) {
                $classes[] = $hidden_class;
            }
            
            $cap_groups = $caps_groups_manager->get_cap_groups($cap_id, $built_in_wp_caps);
            $classes = array_merge($classes, $cap_groups);
            
            $checked = '';
            $disabled = '';
            if ($for_role) {
                if (isset($roles[$current_role]['capabilities'][$cap_id]) &&
                    !empty($roles[$current_role]['capabilities'][$cap_id])) {
                    $checked = 'checked="checked"';
                }
            } else {
                if (empty($edit_mode)) {
                    $disabled = 'disabled="disabled"';
                } else {
                    $disabled = '';
                }
                if ($this->lib->user_can($cap_id)) {
                    $checked = 'checked="checked"';
                    if (!isset($user_to_edit->caps[$cap_id])) {
                        $disabled = 'disabled="disabled"';
                    }
                }
            }                        
            $class = 'class="' . implode(' ', $classes) .'"';
            $cap_id_esc = str_replace(' ', URE_SPACE_REPLACER, $cap_id);
            $cap_html = '<div id="ure_cap_div_'. $cap_id_esc .'" '. $class .'><input type="checkbox" name="' . $cap_id_esc . '" id="' . 
                    $cap_id_esc . '" value="' . $cap_id .'" '. $checked . ' ' . $disabled . ' ' . $onclick_for_admin . 
                    'class="ure-cap-cb">';
            

            if ($caps_readable) {
                $cap_ind = 'human';
                $cap_ind_alt = 'inner';
            } else {
                $cap_ind = 'inner';
                $cap_ind_alt = 'human';
            }
            $cap_html .= '<label for="' . $cap_id_esc . '" title="' . $capability[$cap_ind_alt] . '" ' . $label_style . ' > ' . 
                 $capability[$cap_ind] . '</label> </div>';
            
            $output .= $cap_html;
        }

        echo $output;
    }
    // end of show_capabilities()
    
    
    // content of User Role Editor Pro advertisement slot - for direct call
    public function advertise_pro() {        
        ?>		
        			<div id="ure_pro_advertisement" style="clear:left;display:block; float: left;">
        				<a href="https://www.role-editor.com?utm_source=UserRoleEditor&utm_medium=banner&utm_campaign=Plugins " target="_new" >
        <?php
        $hide_pro_banner = $this->lib->get_option('ure_hide_pro_banner', 0);
        if ($hide_pro_banner) {
            echo 'User Role Editor Pro: extended functionality, no advertisement - from $29.</a>';
        } else {
        ?>
            					<img src="<?php echo URE_PLUGIN_URL; ?>images/user-role-editor-pro-728x90.jpg" alt="User Role Editor Pro" 
            						 title="More functionality and premium support with Pro version of User Role Editor."/>
            				</a><br />
            				<label for="ure_hide_pro_banner">
            					<input type="checkbox" name="ure_hide_pro_banner" id="ure_hide_pro_banner" onclick="ure_hide_pro_banner();"/>&nbsp;Thanks, hide this banner.
            				</label>
            <?php
        }
            ?>
        			</div>  			
        <?php
    }
    // end of advertise_pro_version()
    
    
    public function advertise_commercials() {

        $this->advert = new URE_Advertisement();
        $this->advert->display();
    }
    // end of advertisement()


    public function output_confirmation_dialog() {
        ?>
        <div id="ure_confirmation_dialog" class="ure-modal-dialog">
            <div id="ure_cd_html" style="padding:10px;"></div>
        </div>
        <?php
    }
    // end of output_confirmation_dialog()
 
    
    public function display_caps($for_role = true, $edit_mode=true) {
?>        
    <table id="ure_role_caps" cellpadding="0" cellspacing="0">
        <tr> 
            <td id="ure_role_caps_groups_title"><?php esc_html_e('Group', 'user-role-editor');?></td>
            <td id="ure_role_caps_select">
                <input type="checkbox" id="ure_select_all_caps" name="ure_select_all_caps" value="ure_select_all_caps"/>
            </td>
            <td id="ure_role_caps_quick_filter">
                <?php esc_html_e('Quick filter:', 'user-role-editor'); ?>&nbsp;
                <input type="text" id="quick_filter" name="quick_filter" value="" size="20" onkeyup="ure_filter_capabilities(this.value);" />
            </td>
        </tr>    
        <tr>
            <td class="ure-caps-cell" style="padding:0 10px 0 10px;">
                <?php $this->show_caps_groups(); ?>
            </td>
            <td class="ure-caps-cell">
                <?php $this->show_capabilities($for_role, $edit_mode); ?>
            </td>
            <td class="ure-caps-cell">
                <?php $this->toolbar(); ?>
            </td>
        </tr>
    </table>        
<?php
    }
    // end of display_caps()
    
}
// end of class URE_View