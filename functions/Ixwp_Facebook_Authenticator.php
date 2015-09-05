<?php

class Ixwp_Facebook_Authenticator extends Ixwp_Social_Network_Authenticator{

    private static $ENABLED = 'ixwp_enabled';
    private static $API_URL = 'ixwp_api_url';

    public function __construct()
    {
        parent::__construct();
    }

    function get_default_settings()
    {
        return array("id" => "facebook",
            "title" => '<i class="fa fa-facebook"></i> Facebook',
            "fields" => array(
                Ixwp_Facebook_Authenticator::$ENABLED => array(
                    'title' => __('Status', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'select',
                    'default_value' => 'off',
                    'possible_values' => array(
                        'on' => __('Enabled', IXWP_SOCIAL_COMMENTS_NAME),
                        'off' => __('Disabled', IXWP_SOCIAL_COMMENTS_NAME)
                    )
                ),
                Ixwp_Facebook_Authenticator::$API_URL => array(
                    'title' => __('API URL', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'text',
                    'default_value' => 'https://facebook.com/'
                ),
            )
        );
    }

    function render_settings_admin_page()
    {
        // TODO: Implement render_settings_admin_page() method.
    }

    protected function process_token_request()
    {
        // TODO: Implement process_token_request() method.
    }

    protected function process_access_token_request()
    {
        // TODO: Implement process_access_token_request() method.
    }

    function get_auth_button($settings = array())
    {
        return '';
    }
}