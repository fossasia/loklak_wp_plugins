=== User Role Editor ===
Contributors: shinephp
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=vladimir%40shinephp%2ecom&lc=RU&item_name=ShinePHP%2ecom&item_number=User%20Role%20Editor%20WordPress%20plugin&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: user, role, editor, security, access, permission, capability
Requires at least: 4.0
Tested up to: 4.6
Stable tag: 4.26.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

User Role Editor WordPress plugin makes user roles and capabilities changing easy. Edit/add/delete WordPress user roles and capabilities.

== Description ==

With User Role Editor WordPress plugin you can change user role (except Administrator) capabilities easy, with a few clicks.
Just turn on check boxes of capabilities you wish to add to the selected role and click "Update" button to save your changes. That's done. 
Add new roles and customize its capabilities according to your needs, from scratch of as a copy of other existing role. 
Unnecessary self-made role can be deleted if there are no users whom such role is assigned.
Role assigned every new created user by default may be changed too.
Capabilities could be assigned on per user basis. Multiple roles could be assigned to user simultaneously.
You can add new capabilities and remove unnecessary capabilities which could be left from uninstalled plugins.
Multi-site support is provided.

To read more about 'User Role Editor' visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/) at [shinephp.com](http://shinephp.com)

Short demo about 1st steps with User Role Editor:
https://www.youtube.com/watch?v=UmMtOmWGGxY

Do you need more functionality with quality support in the real time? Do you wish to remove advertisements from User Role Editor pages? 
[Buy Pro version](https://www.role-editor.com). 
[User Role Editor Pro](https://www.role-editor.com) includes extra modules:
<ul>
<li>Block selected admin menu items for role.</li>
<li>Block selected widgets under "Appearance" menu for role.</li>
<li>Show widgets at front-end for selected roles.</li>
<li>Block selected meta boxes (dashboard, posts, pages, custom post types) for role.</li>
<li>"Export/Import" module. You can export user roles to the local file and import them then to any WordPress site or other sites of the multi-site WordPress network.</li> 
<li>Roles and Users permissions management via Network Admin  for multisite configuration. One click Synchronization to the whole network.</li>
<li>"Other roles access" module allows to define which other roles user with current role may see at WordPress: dropdown menus, e.g assign role to user editing user profile, etc.</li>
<li>Manage user access to editing posts/pages/custom post type using posts/pages, authors, taxonomies ID list.</li>
<li>Per plugin users access management for plugins activate/deactivate operations.</li>
<li>Per form users access management for Gravity Forms plugin.</li>
<li>Shortcode to show enclosed content to the users with selected roles only.</li>
<li>Posts and pages view restrictions for selected roles.</li>
</ul>
Pro version is advertisement free. Premium support is included.

== Installation ==

Installation procedure:

1. Deactivate plugin if you have the previous version installed.
2. Extract "user-role-editor.zip" archive content to the "/wp-content/plugins/user-role-editor" directory.
3. Activate "User Role Editor" plugin via 'Plugins' menu in WordPress admin menu. 
4. Go to the "Users"-"User Role Editor" menu item and change your WordPress standard roles capabilities according to your needs.

== Frequently Asked Questions ==
- Does it work with WordPress in multi-site environment?
Yes, it works with WordPress multi-site. By default plugin works for every blog from your multi-site network as for locally installed blog.
To update selected role globally for the Network you should turn on the "Apply to All Sites" checkbox. You should have superadmin privileges to use User Role Editor under WordPress multi-site.
Pro version allows to manage roles of the whole network from the Netwok Admin.

To read full FAQ section visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/#faq) at [shinephp.com](shinephp.com).

== Screenshots ==
1. screenshot-1.png User Role Editor main form
2. screenshot-2.png Add/Remove roles or capabilities
3. screenshot-3.png User Capabilities link
4. screenshot-4.png User Capabilities Editor
5. screenshot-5.png Bulk change role for users without roles

To read more about 'User Role Editor' visit [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/) at [shinephp.com](shinephp.com).

= Translations =

If you wish to check available translations or help with plugin translation to your language visit this link
https://translate.wordpress.org/projects/wp-plugins/user-role-editor/

== Changelog ==

= [4.26.3] 25.07.2016 =
* Fix: Selecting a sub-group/list of caps does make the ure_select_all_caps checkbox select all within that group, but checking that box when at the "All" top-level group did not work.
* Fix: Notice: Undefined property: URE_Role_View::$apply_to_all

= [4.26.1] 14.07.2016 =
* Fix: some bugs, like 'undefined property' notices, etc.

= [4.26] 14.07.2016 =
* New: User capabilities were groupd by functionality for more convenience.
* Update: URE_KEY_CAPABILITY constant was changed from 'ure_edit_roles' to 'ure_manage_options'. To make possible for non-admin users access to the User Role Editor without access to the 'administrator' role and users with 'administrator' role.
* Update: User receives full access to User Role Editor under WordPress multisite if he has 'manage_network_plugins' capability instead of 'manager_network_users' as earlier. This allows to give user ability to edit network users without giving him access to the User Role Editor.
* Update: Multisite: use WordPress's global $current_site->blog_id to define main blog ID instead of selecting the 1st one from the sorted list of blogs.
* Update: use WP transients at URE_Lib::_get_post_types() to reduce response time.
* Update: various internal optimizations.

= [4.25.2] 03.05.2016 =
* Update: Enhanced inner processing of available custom post types list.
* Update: Uses 15 seconds transient cache in order to not count users without role twice when 'restrict_manage_users' action fires.
* Update: URE fires action 'profile_update' after direct update of user permissions in order other plugins may catch such change.
* Update: All URE's PHP classes files renamed and moved to the includes/classes subdirectory

= [4.25.1] 15.04.2016 =
* Fix: Selected role's capabilities list was returned back to old after click "Update" button. It was showed correctly according to the recent updates just after additional page refresh.
* Update: deprecated function get_current_user_info() call was replaced with wp_get_current_user().

= [4.25] 02.04.2016 =
* Important security update: Any registered user could get an administrator access. Thanks to [John Muncaster](http://johnmuncaster.com/) for discovering and wisely reporting it.
* URE pages title tag was replaced from h2 to h1, for compatibility with other WordPress pages.
* Fix: "Assign role to the users without role" feature ignored role selected by user.
* Fix: PHP fatal error (line 34) was raised at uninstall.php for WordPress multisite.
* Update: action priority 99 was added for role additional options hook action setup.

= [4.24] 17.03.2016 =
* Fix: PHP notice was generated by class-role-additional-options.php in case when some option does not exist anymore
* Enhance: 'Add Capability' button have added capability to the WordPress built-in administrator role by default. It did not work, if 'administrator' role did not exist.
  Now script selects automatically as an admin role a role with the largest quant of capabilities and adds new capability to the selected role.
* New: User capabilities page was integrated with "[User Switching](https://wordpress.org/plugins/user-switching/)" plugin - "Switch To" the editing user link iss added if "User Switching" plugin is available.
* Marked as compatible with WordPress 4.5.

= [4.23.2] 03.02.2016 =
* Fix: PHP warning "Strict Standards: Static function URE_Base_Lib::get_instance() should not be abstract" was generated

= [4.23.1] 01.02.2016 =
* Fix: 'get_called_class()' function call was excluded for the compatibility with PHP 5.2.*
* Fix: ure-users.js was loaded not only to the 'Users' page.

= [4.23] 31.01.2016 =
* Fix: "Users - Without Role" button showed empty roles drop down list on the 1st call. 
* Update: Own task queue was added, so code which should executed once after plugin activation is executed by the next request to WP and may use a selected WordPress action to fire with a needed priority.
* Update: Call of deprecated mysql_server_info() is replaced with $wpdb->db_version().
* Update: Singleton patern is applied to the URE_Lib class.
* Minor code enhancements

= [4.22] 15.01.2016 =
* Unused 'add_users' capability was removed from the list of core capabilities as it was removed from WordPress starting from version 4.4
* bbPress user capabilities are supported for use in the non-bbPress roles. You can not edit roles created by bbPress, as bbPress re-creates them dynamically for every request to the server. Full support for bbPress roles editing will be included into User Role Editor Pro version 4.22.
* Self-added "Other Roles" column removed from "Users" list, as WordPress started to show all roles assigned to the user in its own "Role" column.
* 'ure_show_additional_capabilities_section' filter allows to  hide 'Other Roles' section at the 'Add new user', 'Edit user' pages.


Click [here](https://www.role-editor.com/changelog)</a> to look at [the full list of changes](https://www.role-editor.com/changelog) of User Role Editor plugin.


== Additional Documentation ==

You can find more information about "User Role Editor" plugin at [this page](http://www.shinephp.com/user-role-editor-wordpress-plugin/)

I am ready to answer on your questions about plugin usage. Use [plugin page comments](http://www.shinephp.com/user-role-editor-wordpress-plugin/) for that.
