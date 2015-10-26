<?php

require_once('Ixwp_Social_Network_Authenticator.php');

class Ixwp_Gplus_Authenticator extends Ixwp_Social_Network_Authenticator
{
    public $network = "gplus";

    private static $ENABLED = 'ixwp_enabled';
    private static $CLIENT_ID = 'ixwp_client_id';
    private static $CLIENT_SECRET = 'ixwp_client_secret';

    private static $REQUEST_URL = 'https://accounts.google.com/o/oauth2/auth';
    private static $ACCESS_URL = 'https://www.googleapis.com/oauth2/v3/token';
    private static $API_URL = 'https://www.googleapis.com/plus/v1/people/me';

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
                    'title' => __('Status', 'postmatic-social'),
                    'type' => 'switch',
                    'default_value' => 'off'
                ),
                Ixwp_Gplus_Authenticator::$CLIENT_ID => array(
                    'title' => __('Client ID', 'postmatic-social'),
                    'type' => 'text',
                    'default_value' => ''
                ),
                Ixwp_Gplus_Authenticator::$CLIENT_SECRET => array(
                    'title' => __('Client Secret', 'postmatic-social'),
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
        echo '<th><label>' . __('Documentation', 'postmatic-social') . '</label></th>';
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
        $settings = $this->get_settings();
        $url = Ixwp_Gplus_Authenticator::$REQUEST_URL;
        $client_id = $settings[Ixwp_Gplus_Authenticator::$CLIENT_ID];
        $query_string = $this->to_query_string(array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $this->get_oauth_callback(),
            'scope' => 'email',
        ));
        $authorize_url = $url . '?' . $query_string;
        header('Location: ' . $authorize_url);
    }

    protected function process_access_token_request()
    {
        if (array_key_exists('code', $_REQUEST) && array_key_exists('post_id', $_REQUEST)) {

            global $ixwp_sc_post_protected;
            global $ixwp_sc_session;

            $post_id = intval($_REQUEST['post_id']);
            $settings = $this->get_settings();
            $url = Ixwp_Gplus_Authenticator::$ACCESS_URL;
            $client_id = $settings[Ixwp_Gplus_Authenticator::$CLIENT_ID];
            $client_secret = $settings[Ixwp_Gplus_Authenticator::$CLIENT_SECRET];
            $query_string = $this->to_query_string(array(
                'code' => $_REQUEST['code'] ,
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri' => $this->get_oauth_callback(),
                'grant_type' => 'authorization_code'
            ));
            $response = wp_remote_post($url, array(
                'body' => $query_string,
                'sslverify' => false));

            if (is_wp_error($response)) {
                $error_string = $response->get_error_message();
                throw new Exception($error_string);
            } else {
                $response_body = json_decode($response['body'], true);
                if ($response_body && is_array($response_body) && array_key_exists('access_token', $response_body)
                ) {
                    $access_token = $response_body['access_token'];
                    $user_details = $this->get_user_details($access_token);
                    $ixwp_sc_session['user'] = $user_details;
                    $ixwp_sc_post_protected = true;
                    comment_form(array(), $post_id);
                    die();
                } else {
                    throw new Exception(__('Missing the access_token parameter', 'postmatic-social'));
                }
            }
        } else {
            die();
        }
    }

    protected function get_user_details($access_token)
    {
        // global $ixwp_sc_session;
        $settings = $this->get_settings();
        $url = Ixwp_Gplus_Authenticator::$API_URL;
        $response = wp_remote_get($url,
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
                    // By FK include network
                    'network' => 'Google Plus',
                    'display_name' => $response_body['displayName'],
                    'username' => $response_body['name']['givenName'],
                    'email' => $email,
                    'avatar_url' => $response_body['image']['url'],
                    'profile_url' => $response_body['url']
                );
            } else {
                throw new Exception(__('Could not get the user details', 'postmatic-social'));
            }
        }
    }
/*
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
*/
    function get_auth_button($settings = array())
    {
        $oauth_callback = $this->get_oauth_callback();
        $website_url = admin_url('admin-ajax.php') . '?action=ixwp-gplus-request-token';
        $btn = '<div id="postmatic-sc-googleplus-button" data-sc-id="gplus" class="postmatic-sc-button postmatic-sc-googleplus-button" data-post-id="' . get_the_ID() . '" data-access-token-request-url="' . esc_attr($oauth_callback) . '" href="'.$website_url.'"><i class="fa fa-google-plus"></i></div>';
        return $btn;
    }
    /**
     * 1 create app
     * 2 configure Consent screen
     * 3 enable Google+ API
     */
}