<?php
/*
Plugin Name: Comments with Social Login
Plugin URI: https://wordpress.org/plugins/ixwp-comments-social-login/
Description: This plugin allows you to add authentication using social media networks (Twitter, WordPress, Google+, etc.) for the users who want to comment on your blog. To get started: 1) Click the "Activate" link at the left of this description, 2) Click the "Social Login" link under the Comments left menu.
Author: Ixtendo
Author URI: http://www.ixtendo.com
Version: 1.0
License: GPLv2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

define('IXWP_SOCIAL_COMMENTS_VERSION', '1.0');
define('IXWP_SOCIAL_COMMENTS_NAME', 'ixwp_social_comments');
define('IXWP_SOCIAL_COMMENTS_SETTINGS_NAME', 'ixwp_social_comments');
define('IXWP_SOCIAL_COMMENTS_PATH', plugin_dir_path(__FILE__));
define('IXWP_SOCIAL_COMMENTS_URL', plugin_dir_url(__FILE__));
define('IXWP_SOCIAL_COMMENTS_HTTP_PROTOCOL', is_ssl() ? 'https' : 'http');
define('IXWP_SOCIAL_COMMENTS_SESSION_USER', 'user');
define('IXWP_SOCIAL_COMMENTS_HELP_URL', 'http://www.ixtendo.com/comments-with-social-login-wp-plugin');

require_once(IXWP_SOCIAL_COMMENTS_PATH . '/functions/Ixwp_Social_Comments_Session.php');
require_once(IXWP_SOCIAL_COMMENTS_PATH . '/functions/Ixwp_Social_Comments_Plugin.php');

add_action('plugins_loaded', 'ixwp_social_comments_plugin_loaded');
if (!function_exists('ixwp_social_comments_plugin_loaded')) {
    function ixwp_social_comments_plugin_loaded()
    {
        $GLOBALS['ixwp_sc_post_protected'] = false;
        $GLOBALS['ixwp_sc_session'] = Ixwp_Social_Comments_Session::get_instance();
        load_plugin_textdomain(IXWP_SOCIAL_COMMENTS_NAME, false, 'languages');
        $GLOBALS[IXWP_SOCIAL_COMMENTS_NAME] = new Ixwp_Social_Comments_Plugin(array("wordpress", "gplus", "twitter"));
    }
}

/*
register_activation_hook(__FILE__, 'ixwp_comments_social_login_activate');
if (!function_exists('ixwp_comments_social_login_activate')) {
    function ixwp_comments_social_login_activate()
    {
        add_option(IXWP_SOCIAL_COMMENTS_NAME . '_plugin_status', 'on');
    }
}

add_action('admin_init', 'ixwp_comments_social_login_plugin_redirect');
if (!function_exists('ixwp_comments_social_login_plugin_redirect')) {
    function ixwp_comments_social_login_plugin_redirect()
    {
        if (is_admin() && get_option(IXWP_SOCIAL_COMMENTS_NAME . '_plugin_status') == 'on') {
            delete_option(IXWP_SOCIAL_COMMENTS_NAME . '_plugin_status');
            $plugin_url = admin_url('edit-comments.php?page=ixwp-comments-social-login');
            wp_redirect($plugin_url);
        }
    }
}
*/