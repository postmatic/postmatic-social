<?php

require_once('Ixwp_Social_Network_Authenticator.php');

class Ixwp_Twitter_Authenticator extends Ixwp_Social_Network_Authenticator
{
    private static $ENABLED = 'ixwp_enabled';
    private static $API_URL = 'ixwp_api_url';
    private static $API_VERSION = '1.1';
    private static $CONSUMER_KEY = 'ixwp_consumer_key';
    private static $CONSUMER_SECRET = 'ixwp_consumer_secret';

    public function __construct()
    {
        parent::__construct();
    }

    private function build_signature_data($baseURI, $method, $params)
    {
        $args = array();
        ksort($params);
        foreach ($params as $key => $value) {
            $args[] = $key . '=' . rawurlencode($value);
        }
        return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $args));
    }

    private function build_authorization_header($oauth)
    {
        $values = array();
        foreach ($oauth as $key => $value) {
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }
        return 'OAuth ' . implode(', ', $values);
    }

    protected function process_token_request()
    {
        $settings = $this->get_settings();
        $api_url = $settings[Ixwp_Twitter_Authenticator::$API_URL];
        $consumer_key = $settings[Ixwp_Twitter_Authenticator::$CONSUMER_KEY];
        $consumer_secret = $settings[Ixwp_Twitter_Authenticator::$CONSUMER_SECRET];
        $request_token_url = $api_url . 'oauth/request_token';
        $authenticate_url = $api_url . 'oauth/authenticate';

        $oauth_request_params = array(
            'oauth_callback' => $this->get_oauth_callback(),
            'oauth_consumer_key' => $consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0');

        $oauth_signature_data = $this->build_signature_data($request_token_url, 'POST', $oauth_request_params);
        $oauth_signature_key = rawurlencode($consumer_secret) . '&';
        $oauth_signature = base64_encode(hash_hmac('sha1', $oauth_signature_data, $oauth_signature_key, true));
        $oauth_request_params['oauth_signature'] = $oauth_signature;

        $oauth_request_header = array(
            'Authorization' => $this->build_authorization_header($oauth_request_params),
            'Expect' => ''
        );
        $response = wp_remote_post($request_token_url,
            array('timeout' => 120,
                'headers' => $oauth_request_header,
                'sslverify' => false));
        if (is_wp_error($response)) {
            $error_string = $response->get_error_message();
            throw new Exception($error_string);
        } else {
            $response_body = $response['body'];
            parse_str($response_body, $response_body_arguments);
            if (array_key_exists('oauth_token', $response_body_arguments)) {
                $oauth_token = $response_body_arguments['oauth_token'];
                header('Location: ' . $authenticate_url . '?oauth_token=' . $oauth_token);
            } else {
                throw new Exception(__('Missing the oauth_token parameter', IXWP_SOCIAL_COMMENTS_NAME));
            }
        }
    }

    protected function process_access_token_request()
    {
        if (array_key_exists('oauth_token', $_REQUEST) &&
            array_key_exists('oauth_verifier', $_REQUEST) &&
            array_key_exists('post_id', $_REQUEST)
        ) {
            global $ixwp_sc_session;
            global $ixwp_sc_post_protected;
            $oauth_token = $_REQUEST['oauth_token'];
            $oauth_verifier = $_REQUEST['oauth_verifier'];
            $post_id = $_REQUEST['post_id'];
            $twitter_settings = $this->get_settings();
            $api_url = $twitter_settings[Ixwp_Twitter_Authenticator::$API_URL];
            $consumer_key = $twitter_settings[Ixwp_Twitter_Authenticator::$CONSUMER_KEY];
            $consumer_secret = $twitter_settings[Ixwp_Twitter_Authenticator::$CONSUMER_SECRET];
            $access_token_url = $api_url . 'oauth/access_token';

            $oauth_request_params = array(
                'oauth_token' => $oauth_token,
                'oauth_consumer_key' => $consumer_key,
                'oauth_nonce' => time(),
                'oauth_signature_method' => 'HMAC-SHA1',
                'oauth_timestamp' => time(),
                'oauth_version' => '1.0');

            $oauth_signature_data = $this->build_signature_data($access_token_url, 'POST', $oauth_request_params);
            $oauth_signature_key = rawurlencode($consumer_secret) . '&';
            $oauth_signature = base64_encode(hash_hmac('sha1', $oauth_signature_data, $oauth_signature_key, true));
            $oauth_request_params['oauth_signature'] = $oauth_signature;

            $oauth_request_header = array(
                'Authorization' => $this->build_authorization_header($oauth_request_params),
                'Expect' => ''
            );
            $response = wp_remote_post($access_token_url,
                array('timeout' => 120,
                    'headers' => $oauth_request_header,
                    'body' => array(
                        'oauth_verifier' => $oauth_verifier
                    ),
                    'sslverify' => false));
            if (is_wp_error($response)) {
                $error_string = $response->get_error_message();
                throw new Exception($error_string);
            } else {
                $response_body = $response['body'];
                parse_str($response_body, $response_body_arguments);
                if (array_key_exists('oauth_token', $response_body_arguments) &&
                    array_key_exists('oauth_token_secret', $response_body_arguments) &&
                    array_key_exists('user_id', $response_body_arguments)
                ) {
                    $oauth_token = $response_body_arguments['oauth_token'];
                    $oauth_token_secret = $response_body_arguments['oauth_token_secret'];
                    $user_details = $this->get_user_details($oauth_token, $oauth_token_secret);
                    $ixwp_sc_session['user'] = $user_details;
                    $ixwp_sc_post_protected = true;
                    comment_form(array(), $post_id);
                } else {
                    throw new Exception(__('Missing the oauth_token or oauth_verifier parameters', IXWP_SOCIAL_COMMENTS_NAME));
                }
            }
        } else {
            die();
        }
    }

    protected function get_user_details($oauth_token, $oauth_token_secret)
    {
        $twitter_settings = $this->get_settings();
        $api_url = $twitter_settings[Ixwp_Twitter_Authenticator::$API_URL];
        $api_version = Ixwp_Twitter_Authenticator::$API_VERSION;
        $consumer_key = $twitter_settings[Ixwp_Twitter_Authenticator::$CONSUMER_KEY];
        $consumer_secret = $twitter_settings[Ixwp_Twitter_Authenticator::$CONSUMER_SECRET];
        $verify_credentials_url = $api_url . $api_version . '/account/verify_credentials.json';

        $oauth_request_params = array(
            'oauth_token' => $oauth_token,
            'oauth_consumer_key' => $consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0');


        $oauth_signature_data = $this->build_signature_data($verify_credentials_url, 'GET', $oauth_request_params);
        $oauth_signature_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $oauth_signature_data, $oauth_signature_key, true));
        $oauth_params['oauth_signature'] = $oauth_signature;

        $oauth_header = array(
            'Authorization' => $this->build_authorization_header($oauth_params),
            'Expect' => ''
        );

        $verify_credentials_url_params = array();
        foreach ($oauth_request_params as $key => $value) {
            $verify_credentials_url_params[] = $key . '=' . rawurlencode($value);
        }

        $response = wp_remote_get($verify_credentials_url . '?' . implode('&', $verify_credentials_url_params),
            array('timeout' => 120,
                'headers' => $oauth_header,
                'sslverify' => false));
        if (is_wp_error($response)) {
            $error_string = $response->get_error_message();
            throw new Exception($error_string);
        } else {
            $response_body = json_decode($response['body'], true);
            if ($response_body && is_array($response_body)) {
                return array(
                    'display_name' => $response_body['screen_name'],
                    'username' => $response_body['name'],
                    'email' => '',
                    'avatar_url' => $response_body['profile_image_url'],
                    'profile_url' => $response_body['url']
                );
            } else {
                throw new Exception(__('Could not get the user details', IXWP_SOCIAL_COMMENTS_NAME));
            }
        }
    }

    function get_default_settings()
    {
        return array("id" => "twitter",
            "title" => '<i class="fa fa-twitter"></i> Twitter',
            "fields" => array(
                Ixwp_Twitter_Authenticator::$ENABLED => array(
                    'title' => __('Status', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'switch',
                    'default_value' => 'off'
                ),
                Ixwp_Twitter_Authenticator::$API_URL => array(
                    'title' => __('API URL', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'text',
                    'default_value' => 'https://api.twitter.com/'
                ),
                Ixwp_Twitter_Authenticator::$CONSUMER_KEY => array(
                    'title' => __('Consumer Key (API Key)', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'text',
                    'default_value' => ''
                ),
                Ixwp_Twitter_Authenticator::$CONSUMER_SECRET => array(
                    'title' => __('Consumer Secret (API Secret)', IXWP_SOCIAL_COMMENTS_NAME),
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

    function get_auth_button($settings = array())
    {
        $default_settings = $this->get_default_settings();
        $website_url = admin_url('admin-ajax.php') . '?action=ixwp-twitter-request-token';
        $btn = '<a class="ixwp-sc-button ixwp-sc-twitter-button" data-sc-id="' . $default_settings['id'] . '" data-post-id="' . get_the_ID() . '" name="Twitter" href="' . $website_url . '"><i class="fa fa-twitter"></i>Twitter</a>';
        return $btn;

    }
}