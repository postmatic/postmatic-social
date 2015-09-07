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
            "title" => '<i class="fa fa-cogs"></i> ' . __("Plugin Settings", IXWP_SOCIAL_COMMENTS_NAME),
            "fields" => array(
                Ixwp_Generic_Settings_Tab::$PLUGIN_STATUS => array(
                    'title' => __('Working Mode', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'select',
                    'default_value' => 'off',
                    'possible_values' => array(
                        'off' => __('Disable comments authentication', IXWP_SOCIAL_COMMENTS_NAME),
                        'on_all' => __('Enable comments authentication for all posts', IXWP_SOCIAL_COMMENTS_NAME),
                        'on_custom' => __('Enable comments authentication for the posts selected below', IXWP_SOCIAL_COMMENTS_NAME),
                    )
                ),
                Ixwp_Generic_Settings_Tab::$POSTS_ID => array(
                    'title' => __('Selected Pages', IXWP_SOCIAL_COMMENTS_NAME),
                    'type' => 'custom',
                    'default_value' => array()
                ),
            )
        );
    }

    function render_settings_admin_page()
    {

        $default_settings = $this->get_default_settings();
        $settings = $this->get_settings();

        echo '<table class="form-table"><tbody>';

        foreach ($default_settings["fields"] as $field_id => $field_meta) {
            $field_value = $settings[$field_id];
            $this->render_form_field($field_id, $field_value, $field_meta);
        }

        echo '<tr>';
        echo '<th scope="row">' . $default_settings['fields'][Ixwp_Generic_Settings_Tab::$POSTS_ID]['title'] . '</th>';
        echo '<td>';

        $query_args = apply_filters('ixwp_social_comments_posts_query_args', array(
            'post_type' => 'post',
            'post_status' => 'publish'
        ));

        $selected_posts = $settings[Ixwp_Generic_Settings_Tab::$POSTS_ID];
        query_posts($query_args);
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                $post_id = get_the_ID();
                $post_title = get_the_title();
                $checked_attr = in_array($post_id, $selected_posts) ? ' checked="checked"' : '';
                echo '<label for="ixwp_sc_' . $post_id . '"><input type="checkbox" name="' . Ixwp_Generic_Settings_Tab::$POSTS_ID . '[]" id="ixwp_sc_' . $post_id . '" value="' . $post_id . '"' . $checked_attr . '> ' . $post_title . '</label><br>';
            }
        }
        wp_reset_query();

        echo '</td>';
        echo '</tr>';

        echo '</tbody></table>';
    }

}