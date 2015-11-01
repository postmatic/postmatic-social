<?php
/*
Plugin Name: Postmatic Social
Plugin URI: https://wordpress.org/plugins/postmatic-social/
Description: This plugin allows you to add authentication using social media networks (Twitter, WordPress, Google+, etc.) for the users who want to comment on your blog. To get started: 1) Click the "Activate" link at the left of this description, 2) Click the "Social Login" link under the Comments left menu.
Author: Postmatic
Author URI: https://gopostmatic.com/
Version: 1.0
* Text Domain: postmatic-social
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */
class Postmatic_Social {
    /**
	* Holds the class instance.
	*
	* @since 1.0.0
	* @access static
	* @var Postmatic_Social $instance
	*/
	private static $instance = null;
	
	/**
	* Retrieve a class instance.
	*
	* Retrieve a class instance.
	*
	* @since 5.0.0 
	* @access static
	*
	* @return MPSUM_Updates_Manager Instance of the class.
	*/
	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	} //end get_instance
	
	/**
	* Retrieve the plugin basename.
	*
	* Retrieve the plugin basename.
	*
	* @since 1.0.0
	* @access static
	*
	* @return string plugin basename
	*/
	public static function get_plugin_basename() {
		return plugin_basename( __FILE__ );	
	}
	
	/**
	* Class constructor.
	*
	* Set up internationalization, auto-loader, and plugin initialization.
	*
	* @since 1.0.0
	* @access private
	*
	*/
	private function __construct() {
		/* Localization Code */
		load_plugin_textdomain( 'postmatic-social', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
				
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
	} //end constructor

	/**
	* Return the absolute path to an asset.
	*
	* Return the absolute path to an asset based on a relative argument.
	*
	* @since 1.0.0
	* @access static
	*
	* @param string  $path Relative path to the asset.
	* @return string Absolute path to the relative asset.
	*/
	public static function get_plugin_dir( $path = '' ) {
		$dir = rtrim( plugin_dir_path(__FILE__), '/' );
		if ( !empty( $path ) && is_string( $path) )
			$dir .= '/' . ltrim( $path, '/' );
		return $dir;		
	}
	
	/**
	* Return the web path to an asset.
	*
	* Return the web path to an asset based on a relative argument.
	*
	* @since 1.0.0
	* @access static
	*
	* @param string  $path Relative path to the asset.
	* @return string Web path to the relative asset.
	*/
	public static function get_plugin_url( $path = '' ) {
		$dir = rtrim( plugin_dir_url(__FILE__), '/' );
		if ( !empty( $path ) && is_string( $path) )
			$dir .= '/' . ltrim( $path, '/' );
		return $dir;	
	}   
    
	
	/**
	* Initialize the plugin and its dependencies.
	*
	* Initialize the plugin and its dependencies.
	*
	* @since 1.0.0 
	* @access public
	* @see __construct
	* @internal Uses plugins_loaded action
	*
	*/
	public function plugins_loaded() {
		$GLOBALS[ 'pms_post_protected' ] = false;
        $GLOBALS[ 'pms_session' ] = Postmatic_Social_Comments_Session::get_instance();
        $GLOBALS[ 'postmatic-social' ] = new Postmatic_Social_Comments_Plugin( array( 'wordpress', 'gplus', 'twitter', 'facebook' ) );	
        add_action( 'comment_form_after_fields', array( $this, 'twitter_extra_fields' ) );
        add_filter( 'pre_comment_author_email', array( $this, 'twitter_author' ) );
            
        }
        
        public function twitter_author( $email ) {
            if ( empty( $_POST ) || empty( $_COOKIE ) ) return $email;
            
            if ( isset( $_COOKIE[ 'comment_author_email' . COOKIEHASH ] ) ) {
                $email = $_COOKIE[ 'comment_author_email' . COOKIEHASH ];
                return $email; 
            } else {
                $comment_cookie_lifetime = apply_filters( 'comment_cookie_lifetime', 30000000 );
                setcookie( 'comment_author_email_' . COOKIEHASH, $_POST[ 'email' ], time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN );
                setcookie( 'pms_comment_author_email', $_POST[ 'email' ], time() + $comment_cookie_lifetime, COOKIEPATH, COOKIE_DOMAIN );
                return $_POST[ 'email' ];
            }
            return $email;
            
            
        }
        
        public function twitter_extra_fields() {
            echo '<div class="comment-form-pms-twitter-extra">';
            echo '<div class="pms-optin">';
            printf( '<input type="checkbox" name="pms_comment_subscribe" value="1" id="pms_comment_subscribe">&nbsp;&nbsp;' );
            printf( '<label for="pms_comment_subscribe">%s</label>', esc_html__( 'Participate in this conversation via email', 'postmatic-social' ) );
            echo '</div><!-- .pms-optin -->';
            echo '<div class="pms-optin-form">';
            esc_html_e( 'Please enter an E-mail Address (Optional)', 'postmatic-social' );
            echo '<input type="text"name="pms-email" value="" />';
            echo '</div><!-- .pms-optin-form -->';
            echo '</div><!-- .pms-opttin -->';
        }
	
}


define( 'POSTMATIC_SOCIAL_SESSION_USER', 'user' );
//todo - Update this link
define( 'POSTMATIC_SOCIAL_HELP_URL', 'http://docs.gopostmatic.com/article/185-setup' );

require_once( Postmatic_Social::get_plugin_dir( '/functions/Postmatic_Social_Comments_Session.php' ) );
require_once( Postmatic_Social::get_plugin_dir( '/functions/Postmatic_Social_Comments_Plugin.php') );

Postmatic_Social::get_instance();