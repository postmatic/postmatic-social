<?php

require_once('Ixwp_Social_Network_Authenticator.php');

class Ixwp_Facebook_Authenticator extends Ixwp_Social_Network_Authenticator
{
    public $network = "facebook";

    private static $ENABLED = 'ixwp_enabled';
    private static $API_URL = 'ixwp_api_url';
    private static $CLIENT_ID = 'ixwp_client_id';
    private static $CLIENT_SECRET = 'ixwp_client_secret';

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
                    'title' => __('Status', 'postmatic-social'),
                    'type' => 'switch',
                    'default_value' => 'off',
                    'possible_values' => array(
                        'on' => __('Enabled', 'postmatic-social'),
                        'off' => __('Disabled', 'postmatic-social')
                    )
                ),
                Ixwp_Facebook_Authenticator::$API_URL => array(
                    'title' => __('API URL', 'postmatic-social'),
                    'type' => 'text',
                    'default_value' => 'https://www.facebook.com/dialog/oauth'
                ),
                Ixwp_Facebook_Authenticator::$CLIENT_ID => array(
                    'title' => __('Client ID', 'postmatic-social'),
                    'type' => 'text',
                    'default_value' => ''
                ),
                Ixwp_Facebook_Authenticator::$CLIENT_SECRET => array(
                    'title' => __('Client Secret', 'postmatic-social'),
                    'type' => 'text',
                    'default_value' => ''
                )
            )
        );
    }

    function render_settings_admin_page()
    {
       $default_settings = $this->get_default_settings();
        $sc_id = $default_settings['id'];
        $settings = $this->get_settings();
        echo '<table class="form-table"><tbody>';

        // echo '<tr>';
        // echo '<th><label>' . __('Need help?', 'postmatic-social') . '</label></th>';
        // echo '<td><a href="http://docs.gopostmatic.com/article/185-setup">How to enable wordpress.com authentication.</a></td>';
        // echo '</tr>';

        $oauth_callback = $this->get_oauth_callback();
        echo '<tr>';
        echo '<th><label>' . __('Redirection URL', 'postmatic-social') . '</label></th>';
        echo '<td><strong>' . htmlentities($oauth_callback) . '</strong></td>';
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
        $api_url = $settings[Ixwp_Facebook_Authenticator::$API_URL];
        $client_id = $settings[Ixwp_Facebook_Authenticator::$CLIENT_ID];

        $query_string = $this->to_query_string(array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'redirect_uri' => $this->get_oauth_callback(),
            'scope' => 'user_about_me,email',
        ));
        $authorize_url = $api_url . '?' . $query_string;
        header('Location: ' . $authorize_url);
    }

    protected function process_access_token_request()
    {
        if (array_key_exists('code', $_REQUEST) && array_key_exists('post_id', $_REQUEST)) {
            global $ixwp_sc_post_protected;
            global $ixwp_sc_session;
            $post_id = intval($_REQUEST['post_id']);
            $settings = $this->get_settings();
            $client_id = $settings[Ixwp_Facebook_Authenticator::$CLIENT_ID];
            $client_secret = $settings[Ixwp_Facebook_Authenticator::$CLIENT_SECRET];
            $request_token_url = "https://graph.facebook.com/v2.4/oauth/access_token";

            $query_string = $this->to_query_string(array(
                'client_id' => $client_id,
                'redirect_uri' => $this->get_oauth_callback(),
                'client_secret' => $client_secret,
                'code' => $_REQUEST['code'] ,
                // 'grant_type' => 'authorization_code'
            ));
            $response = wp_remote_get($request_token_url . "?" . $query_string);
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
        $settings = $this->get_settings();
        $user_details_url = "https://graph.facebook.com/me?fields=id,name,email,picture{url}" ;
        $response = wp_remote_get($user_details_url,
            array('timeout' => 120,
                'headers' => array('Authorization' => 'Bearer ' . $access_token),
                'sslverify' => false));
        if (is_wp_error($response)) {
            $error_string = $response->get_error_message();
            throw new Exception($error_string);
        } else {
            $response_body = json_decode($response['body'], true);
            if ($response_body && is_array($response_body)) {
                return array(
                    'network' => "Facebook",
                    'display_name' => $response_body['name'],
                    'username' => $response_body['id'],
                    'email' => $response_body['email'],
                    'avatar_url' => $response_body['picture']['url'],
                );
            } else {
                throw new Exception(__('Could not get the user details', 'postmatic-social'));
            }
        }
    }

    function get_auth_button($settings = array())
    {
        $default_settings = $this->get_default_settings();
        $website_url = admin_url('admin-ajax.php') . '?action=postmatic-facebook-request-token';
        $btn = '<a class="postmatic-sc-button postmatic-sc-facebook-button" data-sc-id="' . $default_settings['id'] . '" data-post-id="' . get_the_ID() . '" name="Facebook" href="' . $website_url . '"><i class="fa fa-facebook"></i></a>';
        return $btn;
    }
}
