<?php

require_once('Ixwp_Social_Comments_Tab.php');

class Ixwp_Generic_Settings_Tab extends Ixwp_Social_Comments_Tab
{
    static $ID = 'settings';
    static $PLUGIN_STATUS = 'ixwp_plugin_status';
    static $POSTS_ID = 'ixwp_posts_id';

    public function __construct()
    {
        parent::__construct();
    }

    function get_default_settings()
    {
        return array("id" => Ixwp_Generic_Settings_Tab::$ID,
            "title" => '<i class="fa fa-home"></i> ' . __("Introduction", 'postmatic-social'),
            "fields" => array()
        );
    }

    function render_settings_admin_page()
    {

        include_once(IXWP_SOCIAL_COMMENTS_PATH . '/templates/settings-intro.php');
        
        $default_settings = $this->get_default_settings();
        $settings = $this->get_settings();

        echo '<table class="form-table"><tbody>';

        echo '</tbody></table>';
    }

}