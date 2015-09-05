<?php

require_once('Ixwp_Social_Network_Authenticator.php');

class Ixwp_Wordpress_Authenticator extends Ixwp_Social_Network_Authenticator
{

    private static $ENABLED = 'ixwp_enabled';
    private static $API_URL = 'ixwp_api_url';
    private static $CLIENT_ID = 'ixwp_client_id';
    private static $CLIENT_SECRET = 'ixwp_client_secret';

    public function __construct()
    {
        parent::__construct();
    }

    protected function process_token_request()
    {
        $settings = $this->get_settings();
        $api_url = $settings[Ixwp_Wordpress_Authenticator::$API_URL];
        $client_id = $settings[Ixwp_Wordpress_Authenticator::$CLIENT_ID];

        $query_string = $this->to_query_string(array(
            'client_id' => $client_id,
            'redirect_uri' => $this->get_oauth_callback(),
            'response_type' => 'code',
            'scope' => 'global',
        ));
        $authorize_url = $api_url . 'oauth2/authorize?' . $query_string;
        header('Location: ' . $authorize_url);
    }

    protected function process_access_token_request()
    {
        if (array_key_exists('code', $_REQUEST) && array_key_exists('post_id', $_REQUEST)) {
            global $ixwp_sc_post_protected;
            global $ixwp_sc_session;
            $post_id = intval($_REQUEST['post_id']);
            $settings = $this->get_settings();
            $api_url = $settings[Ixwp_Wordpress_Authenticator::$API_URL];
            $client_id = $settings[Ixwp_Wordpress_Authenticator::$CLIENT_ID];
            $client_secret = $settings[Ixwp_Wordpress_Authenticator::$CLIENT_SECRET];
            $request_token_url = $api_url . 'oauth2/token';

            $query_string = $this->to_query_string(array(
                'client_id' => $client_id,
                'redirect_uri' => $this->get_oauth_callback(),
                'client_secret' => $client_secret,
                'code' => $_REQUEST['code'],
                'grant_type' => 'authorization_code'
            ));
            $response = wp_remote_post($request_token_url, array(
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
                    throw new Exception(__('Missing the access_token parameter', IXWP_SOCIAL_COMMENTS_NAME));
                }
            }
        } else {
            die();
        }
    }

    protected function get_user_details($access_token)
    {
        $settings = $this->get_settings();
        $api_url = $settings[Ixwp_Wordpress_Authenticator::$API_URL];
        $user_details_url = $api_url . 'rest/v1/me/';
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
                    'display_name' => $response_body['display_name'],
                    'username' => $response_body['username'],
                    'email' => $response_body['email'],
                    'avatar_url' => $response_body['avatar_URL'],
                    'profile_url' => $response_body['profile_URL']
                );
            } else {
                throw new Exception(__('Could not get the user details', IXWP_SOCIAL_COMMENTS_NAME));
            }
        }
    }


    function get_default_settings()
    {
        return array("id" => "wordpress",
            "title" => '<i class="fa fa-wordpress"></i> WordPress',
            "fields" => array(
                Ixwp_Wordpress_Authenticator::$ENABLED => array(
                    'title' => __('Status', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'switch',
                    'default_value' => 'off'
                ),
                Ixwp_Wordpress_Authenticator::$API_URL => array(
                    'title' => __('API URL', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'text',
                    'default_value' => 'https://public-api.wordpress.com/'
                ),
                Ixwp_Wordpress_Authenticator::$CLIENT_ID => array(
                    'title' => __('Client ID', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'text',
                    'default_value' => ''
                ),
                Ixwp_Wordpress_Authenticator::$CLIENT_SECRET => array(
                    'title' => __('Client Secret', IXWP_SOCIAL_COMMENTS_NAME),
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

        echo '<tr>';
        echo '<th><label>' . __('Documentation', IXWP_SOCIAL_COMMENTS_NAME) . '</label></th>';
        echo '<td><a href="' . IXWP_SOCIAL_COMMENTS_HELP_URL . '#' . $sc_id . '-config" target="_blank">' . IXWP_SOCIAL_COMMENTS_HELP_URL . '#' . $sc_id . '-config</a></td>';
        echo '</tr>';

        $oauth_callback = $this->get_oauth_callback();
        echo '<tr>';
        echo '<th><label>' . __('Redirection URL', IXWP_SOCIAL_COMMENTS_NAME) . '</label></th>';
        echo '<td><strong>' . htmlentities($oauth_callback) . '</strong></td>';
        echo '</tr>';

        foreach ($default_settings["fields"] as $field_id => $field_meta) {
            $field_value = $settings[$field_id];
            $this->render_form_field($field_id, $field_value, $field_meta);
        }

        echo '</tbody></table>';
    }

    function get_auth_button($settings = array())
    {
        $default_settings = $this->get_default_settings();
        $website_url = admin_url('admin-ajax.php') . '?action=ixwp-wordpress-request-token';
        $btn = '<a class="ixwp-sc-button ixwp-sc-wordpress-button" data-sc-id="' . $default_settings['id'] . '" data-post-id="' . get_the_ID() . '" name="WordPress" href="' . $website_url . '"><i class="fa fa-wordpress"></i>WordPress</a>';
        return $btn;
    }

}