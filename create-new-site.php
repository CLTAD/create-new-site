<?php
/*
Plugin Name: Create New Site
Description: Add a new blog site in a Buddypress installation. For existing users only. 
Version: 0.1
Author: Mike Kelly
Licence: GPL3
*/

/*
Copyright (C) 2013 Mike Kelly

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once(dirname(__FILE__) . '/plugin.php');

class CreateNewSite extends CreateNewSite_Plugin {

    public function __construct(){
        $this->register_plugin('create-new-site', __FILE__);
        $this->add_hooks();
    }

    public function add_hooks(){
        $this->add_action('bp_setup_nav');
        $this->add_action('admin_bar_init');
        $this->add_action('wp_enqueue_scripts');
        $this->add_action('wp_ajax_create_new_site');
    }

    public function bp_setup_nav(){
        if (!is_user_logged_in() || is_admin()){
            return;
        }
        global $bp;
        $user_id = get_current_user_id();
        if (!isset($bp->displayed_user->id) || $bp->displayed_user->id != $user_id) {
            return;
        }
        $blogsslug = bp_get_blogs_slug();
        $blogs_link = $bp->loggedin_user->domain . $blogsslug . '/';
        bp_core_new_subnav_item( array(
                'name' => __('Create New Site'),
                'slug' => 'create-new-site',
                'parent_slug' => $blogsslug,
                'parent_url' => $blogs_link,
                'screen_function' => array(&$this, 'create_new_site_page_template'),
                'position' => 20,
                'user_has_access' => true,
                'link' => $blogs_link . 'create-new-site/'
        ) );
    }

    public function admin_bar_init(){
        if (is_user_logged_in() && is_admin_bar_showing()) {
            $this->add_action('admin_bar_menu', '', 1000);
        }
    }

    public function admin_bar_menu(){
        global $wp_admin_bar, $bp;
        $user_id = get_current_user_id();
        $blogs_link = $bp->loggedin_user->domain . $bp->blogs->slug . '/create-new-site/';

        $args = array(
                    'id'    => 'create-new-site1',
                    'parent' => 'my-sites',
                    'title' => 'Create New Site',
                    'href' => $blogs_link,
                    'meta'  => array(
                        'title' => __('Create New Site'),
                    ),
                );
        $args2 = array(
                    'id'    => 'create-new-site2',
                    'parent' => 'my-account-blogs',
                    'title' => 'Create New Site',
                    'href' => $blogs_link,
                    'meta'  => array(
                        'title' => __('Create New Site'),
                    ),
                );

        if ($user_id && is_user_logged_in() && count(get_blogs_of_user($user_id)) > 0){
            $wp_admin_bar->add_node($args);
        }
        $wp_admin_bar->add_node($args2);
    }

    public function create_new_site_page_template(){
        add_action( 'bp_template_content', array(&$this, 'view_create_new_site_page' ));
        bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
    }

    public function view_create_new_site_page(){        
        if (!is_user_logged_in()){
            return;
        }
        global $bp;
        $user_id = get_current_user_id();
        if ($bp->displayed_user->id != $user_id) {
            return;
        }
        $vars = $this->get_user_sites_information($user_id);
        $this->render_admin('create-new-site-form', $vars);
    }

    function get_user_sites_information($user_id){
        $ldap_login = get_user_meta($user_id, 'ldap_login');  
        $count_all_user_blogs = bp_get_total_blog_count_for_user( bp_loggedin_user_id() );
        $count_is_admin_all_sites = $this->get_non_subscriber_sites($user_id);
        $count_is_admin_group_sites = $this->get_admin_group_sites($user_id);
        $is_ldap_user = $ldap_login;
        $sites_per_user = defined('WP_BLOGS_PER_USER')? WP_BLOGS_PER_USER : 1;
        $sites_remaining = $sites_per_user - ($count_is_admin_all_sites - $count_is_admin_group_sites);
        if ($sites_remaining < 0){
            $sites_remaining = 0;
        }

        $vars['data'] =  compact('count_is_admin_all_sites',
                'count_is_admin_group_sites',
                'count_all_user_blogs',
                'sites_per_user',
                'sites_remaining',
                'is_ldap_user');

        return $vars;
    }

    function wp_ajax_create_new_site(){
        if ( isset($_REQUEST['action']) && $_REQUEST['action'] == 'create_new_site') {
            if (!wp_verify_nonce($_REQUEST['nonce'], 'ajax-create-new-site')){
                exit('There was a security problem. No new site was created.');
            }

            $user_id = get_current_user_id();
            $vars = $this->get_user_sites_information($user_id);

            $blogurl = $_REQUEST['blogurl'];
            $domain = '';
            if (preg_match('|^([a-zA-Z0-9-])+$|', $blogurl)){
                $domain = strtolower($blogurl);
            }

            if (strlen($domain) > 50){
                $vars['message'] = __('Url was too long. Please try again.');
                die(json_encode($vars));
            }

            $blogtitle = $_REQUEST['blogtitle'];

            if (strlen($blogtitle) > 255){
                $vars['message'] = __('Site title was too long. Please try again.');
                die(json_encode($vars));
            }

            // perform same validation as when signing up for an account and creating blog
            $result = wpmu_validate_blog_signup($blogurl, $blogtitle);

            if ($result['errors']->get_error_code()){
                $msg = '';
                foreach( $result['errors']->get_error_messages() as $message ){
                    $msg.= $message . '<br />';
                }
                $vars['message'] = $msg;
                die(json_encode($vars));
            } else {
                // update to sanitized versions
                $blogurl = $result['blogname'];
                $blogtitle = $result['blog_title'];
            }

            global $current_site, $wpdb;
            $current_user = wp_get_current_user();
            $user_id = $current_user->ID;
            
            if (is_subdomain_install()) {
                $newdomain = $domain . '.' . preg_replace( '|^www\.|', '', $current_site->domain );
                $path      = $current_site->path;
            } else {
                $newdomain = $current_site->domain;
                $path      = $current_site->path . $domain . '/';
            }

            $wpdb->hide_errors();
            $password = 'N/A';
            $id = wpmu_create_blog( $newdomain, $path, $blogtitle, $user_id, array( 'public' => 1 ), $current_site->id );
            $wpdb->show_errors();
            if ( !is_wp_error( $id ) ) {
                if ( !is_super_admin( $user_id ) && !get_user_option( 'primary_blog', $user_id ) ){
                    update_user_option( $user_id, 'primary_blog', $id, true );
                }
                $content_mail = sprintf( __( 'New site created by %1$s
Address: %2$s
Name: %3$s' ), $current_user->user_login , get_site_url( $id ), stripslashes( $blogtitle ) );
                wp_mail( get_site_option('admin_email'), sprintf( __( '[%s] New Site Created' ), $current_site->site_name ), $content_mail, 'From: "Site Admin" <' . get_site_option( 'admin_email' ) . '>' );
                wpmu_welcome_notification( $id, $user_id, $password, $blogtitle, array( 'public' => 1 ) );             
                
                $vars = $this->get_user_sites_information($user_id);
                $vars['message'] = __('Site created successfully: <a href="' . get_blogaddress_by_id($id) . '">View Site</a> or <a href="' . get_blogaddress_by_id($id) . 'wp-admin">Edit Site</a>.');
                die(json_encode($vars));
            } else {
                $vars['message'] = $id->get_error_message();
                die(json_encode($vars));
            }
        }    
    }

    private function get_non_subscriber_sites($user_id){
        $sites = get_blogs_of_user($user_id); //get all blogs of user

        // Subscribers have user level 0, so that is not entered in the user meta, author:2, editor:7,Admin:10
        $count=0;
        foreach($sites as $site){
            if($this->is_user_blog_admin($user_id, $site->userblog_id)){
                $count++;
            }
        }
        return $count;
    }

    //check if the user is blog admin
    private function is_user_blog_admin($user_id,$blog_id){
        global $wpdb;
        $meta_key = "wp_".$blog_id."_capabilities"; //.."_user_level";
        $role_sql = "select user_id,meta_value from {$wpdb->usermeta} where meta_key=%s";
        $role = $wpdb->get_results($wpdb->prepare($role_sql, $meta_key), ARRAY_A);
        //clean the role
        $all_user = array_map(array(&$this, 'serialize_roles'), $role); //we are unserializing the role to make that as an array

        foreach($all_user as $key => $user_info){
            if(isset($user_info['meta_value']['administrator']) && $user_info['meta_value']['administrator'] == 1 && $user_info['user_id'] == $user_id) {
                //if the role is admin
                return true;
            }
        }
        return false;
    }

    private function serialize_roles($roles){
        $roles['meta_value'] = maybe_unserialize($roles['meta_value']);
        return $roles;
    }

    private function get_admin_group_sites($user_id){
        $user_login = '';
        $countAdmin = 0;
        if (bp_has_groups('user_id=' . $user_id)) :
            while ( bp_groups() ) : bp_the_group();
                if ($blog_id = get_groupblog_blog_id( bp_get_group_id()) ) {
                    $blogexists = get_blog_details($blog_id, false);
                    if ($blogexists && switch_to_blog($blog_id)){
                        $user_role = bp_groupblog_get_user_role( $user_id, $user_login, $blog_id );
                        if ($user_role == "administrator") {
                            $countAdmin++;
                        }
                        restore_current_blog();
                    }
                }
            endwhile;
        endif;
        return $countAdmin;
    }

    function wp_enqueue_scripts(){
        global $bp;
            if ($bp->current_action != $this->plugin_name ){
                return false;
            }
        wp_enqueue_script('cns-ajax', plugin_dir_url(__FILE__) . '/js/create-new-site-ajax.js', array('jquery'));
    }
} // close CNS class

$GLOBALS['create-new-site'] = new CreateNewSite();
