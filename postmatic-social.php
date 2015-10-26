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
		
		spl_autoload_register( array( $this, 'loader' ) );
		
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
	* Auto-loads classes.
	*
	* Auto-load classes that belong to this plugin.
	*
	* @since 1.0.0
	* @access private
	*
	* @param string  $class_name The name of the class.
	*/
	private function loader( $class_name ) {
		if ( class_exists( $class_name, false ) || false === strpos( $class_name, 'POSTMATIC-SOCIAL' ) ) {
			return;
		}
		$file = Postmatic_Social::get_plugin_dir( "includes/{$class_name}.php" );
		if ( file_exists( $file ) ) {
			include_once( $file );
		}	
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
		$GLOBALS['ixwp_sc_post_protected'] = false;
        $GLOBALS['ixwp_sc_session'] = Ixwp_Social_Comments_Session::get_instance();
        load_plugin_textdomain('postmatic-social', false, 'languages');
        $GLOBALS['postmatic-social'] = new Ixwp_Social_Comments_Plugin(array("wordpress", "gplus", "twitter","facebook"));	
	}
}

define('POSTMATIC_SOCIAL_SESSION_USER', 'user');
define('POSTMATIC_SOCIAL_HELP_URL', 'http://docs.gopostmatic.com/article/185-setup');

require_once( Postmatic_Social::get_plugin_dir( '/functions/Ixwp_Social_Comments_Session.php' ) );
require_once( Postmatic_Social::get_plugin_dir( '/functions/Ixwp_Social_Comments_Plugin.php') );

Postmatic_Social::get_instance();