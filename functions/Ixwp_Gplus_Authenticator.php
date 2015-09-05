<?php

require_once('Ixwp_Social_Network_Authenticator.php');

class Ixwp_Gplus_Authenticator extends Ixwp_Social_Network_Authenticator
{

    private static $ENABLED = 'ixwp_enabled';
    private static $API_URL = 'ixwp_api_url';
    private static $CLIENT_ID = 'ixwp_client_id';

    public function __construct()
    {
        parent::__construct();
    }

    function get_default_settings()
    {
        return array("id" => "gplus",
            "title" => '<i class="fa fa-google-plus"></i> Google+',
            "fields" => array(
                Ixwp_Gplus_Authenticator::$ENABLED => array(
                    'title' => __('Status', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'switch',
                    'default_value' => 'off'
                ),
                Ixwp_Gplus_Authenticator::$API_URL => array(
                    'title' => __('API URL', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'text',
                    'default_value' => 'https://www.googleapis.com/'
                ),
                Ixwp_Gplus_Authenticator::$CLIENT_ID => array(
                    'title' => __('Client ID', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'text',
                    'default_value' => ''
                ),
            )
        );
    }

    function render_settings_admin_page()
    {
        $default_settings = $this->get_default_settings();
        $sc_id = $default_settings['id'];
        $settings = $this->get_settings();
        echo '<table class="form-table"><tbody>';

        echo '<tr>';
        echo '<th><label>' . __('Documentation', IXWP_SOCIAL_COMMENTS_NAME) . '</label></th>';
        echo '<td><a href="' . IXWP_SOCIAL_COMMENTS_HELP_URL . '#' . $sc_id . '-config" target="_blank">' . IXWP_SOCIAL_COMMENTS_HELP_URL . '#' . $sc_id . '-config</a></td>';
        echo '</tr>';

        foreach ($default_settings["fields"] as $field_id => $field_meta) {
            $field_value = $settings[$field_id];
            $this->render_form_field($field_id, $field_value, $field_meta);
        }

        echo '</tbody></table>';
    }

    protected function process_token_request()
    {
        //implemented by Google API using popup
    }

    protected function process_access_token_request()
    {
        global $ixwp_sc_post_protected;
        global $ixwp_sc_session;
        if (array_key_exists('access_token', $_REQUEST) && array_key_exists('post_id', $_REQUEST)) {
            $post_id = intval($_REQUEST['post_id']);
            $access_token = $_REQUEST['access_token'];
            $user_details = $this->get_user_details($access_token);
            $ixwp_sc_session[IXWP_SOCIAL_COMMENTS_SESSION_USER] = $user_details;
            $ixwp_sc_post_protected = true;
            comment_form(array(), $post_id);
            die();
        }
    }

    protected function get_user_details($access_token)
    {
        $settings = $this->get_settings();
        $api_url = $settings[Ixwp_Gplus_Authenticator::$API_URL];
        $user_details_url = $api_url . 'plus/v1/people/me';
        $response = wp_remote_get($user_details_url,
            array('timeout' => 120,
                'headers' => array('Authorization' => 'Bearer ' . $access_token),
                'sslverify' => false));
        if (is_wp_error($response)) {
            $error_string = $response->get_error_message();
            throw new Exception($error_string);
        } else {
            $response_body = json_decode($response['body'], true);
            if ($response_body && is_array($response_body) && array_key_exists('displayName', $response_body)) {
                $email = '';
                if (array_key_exists('emails', $response_body) &&
                    is_array($response_body['emails']) &&
                    count($response_body['emails']) > 0
                ) {
                    $emails = $response_body['emails'];
                    $email = $emails[0]['value'];
                }
                return array(
                    'display_name' => $response_body['displayName'],
                    'username' => $response_body['name']['givenName'],
                    'email' => $email,
                    'avatar_url' => $response_body['image']['url'],
                    'profile_url' => $response_body['url']
                );
            } else {
                throw new Exception(__('Could not get the user details', IXWP_SOCIAL_COMMENTS_NAME));
            }
        }
    }

    function custom_footer()
    {
        $settings = $this->get_settings();
        $client_id = $settings[Ixwp_Gplus_Authenticator::$CLIENT_ID];
        $server_name = apply_filters('ixwp_social_comments_server_name', IXWP_SOCIAL_COMMENTS_HTTP_PROTOCOL . '://' . $_SERVER['SERVER_NAME']);
        parent::custom_footer();
        echo "<script type=\"text/javascript\">";
        echo "function ixwpscRenderGooglePlusSigninButton() {
    gapi.signin.render('ixwp-sc-googleplus-button', {
      'callback': 'ixwpscGooglePlusSigninCallback',
      'clientid': '$client_id',
      'cookiepolicy': '$server_name',
      'scope': 'email',
      'approvalprompt': 'force'
    });
}";
        echo "</script>";
        echo '<script src="https://apis.google.com/js/client:platform.js?onload=ixwpscRenderGooglePlusSigninButton" async defer></script>';
    }

    function get_auth_button($settings = array())
    {
        $oauth_callback = $this->get_oauth_callback();
        $btn = '<div id="ixwp-sc-googleplus-button" class="ixwp-sc-button ixwp-sc-googleplus-button" data-post-id="' . get_the_ID() . '" data-access-token-request-url="' . esc_attr($oauth_callback) . '"><i class="fa fa-google-plus"></i>Google+</div>';
        return $btn;
    }
    /**
     * 1 create app
     * 2 configure Consent screen
     * 3 enable Google+ API
     */
}