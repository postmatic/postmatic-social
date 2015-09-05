<?php

abstract class Ixwp_Social_Comments_Tab
{
    protected $admin_ajax_url;

    public function __construct()
    {
        $this->admin_ajax_url = admin_url('admin-ajax.php');
        $this->init();
    }

    protected function init()
    {
    }

    function get_settings()
    {
        $default_settings = $this->get_default_settings();
        $tab_id = $default_settings['id'];
        $all_settings = get_option(IXWP_SOCIAL_COMMENTS_SETTINGS_NAME);
        if (!isset($all_settings) || !is_array($all_settings)) {
            $all_settings = array();
        }
        if (!array_key_exists($tab_id, $all_settings)) {
            $sn_settings = array();
            $all_settings[$tab_id] = $sn_settings;
        } else {
            $sn_settings = $all_settings[$tab_id];
        }
        foreach ($default_settings["fields"] as $field_id => $field_meta) {
            if (!array_key_exists($field_id, $sn_settings)) {
                $sn_settings[$field_id] = $field_meta['default_value'];
            }
        }
        return apply_filters('ixwp_social_comments_settings', $sn_settings, $tab_id);
    }

    function save_settings()
    {
        $tab_id = $this->get_id();
        $default_settings = $this->get_default_settings();
        $settings = $this->get_settings();
        foreach ($default_settings['fields'] as $field_id => $field_meta) {
            if (array_key_exists($field_id, $_POST)) {
                $settings[$field_id] = $_POST[$field_id];
            } else {
                $settings[$field_id] = $field_meta['default_value'];
            }
        }
        $all_settings = get_option(IXWP_SOCIAL_COMMENTS_SETTINGS_NAME);
        if (!isset($all_settings) || !is_array($all_settings)) {
            $all_settings = array();
        }
        $all_settings[$tab_id] = $settings;
        update_option(IXWP_SOCIAL_COMMENTS_SETTINGS_NAME, $all_settings);
    }

    abstract function get_default_settings();

    function get_id()
    {
        $default_settings = $this->get_default_settings();
        return $default_settings['id'];
    }

    function get_title()
    {
        $default_settings = $this->get_default_settings();
        return $default_settings['title'];
    }

    abstract function render_settings_admin_page();

    protected function render_form_field($field_id, $field_value, $field_meta)
    {
        $field_type = $field_meta['type'];
        switch ($field_type) {
            case 'text':
            {
                echo '<tr>';
                echo '<th><label for="' . $field_id . '_id">' . $field_meta['title'] . '</label></th>';
                echo '<td><input type="text" id="' . $field_id . '_id" name="' . $field_id . '" value="' . $field_value . '" class="regular-text"></td>';
                echo '</tr>';
                break;
            }
            case 'switch':
            {
                echo '<tr>';
                echo '<th><label for="' . $field_id . '_id">' . $field_meta['title'] . '</label></th>';
                echo '<td><div class="ixwp-sc-toggle toggle-modern toggle" data-input-id="' . $field_id . '_id"></div><input type="hidden" id="' . $field_id . '_id" name="' . $field_id . '" value="' . $field_value . '"></td>';
                echo '</tr>';
                break;
            }
            case 'select':
            {
                echo '<tr>';
                echo '<th scope="row"><label for="' . $field_id . '_id">' . $field_meta['title'] . '</label></th>';
                echo '<td>';
                echo '<select id="' . $field_id . '_id" name="' . $field_id . '">';
                foreach ($field_meta['possible_values'] as $key => $val) {
                    $selected = ($field_value == $key) ? 'selected="selected"' : '';
                    echo "<option value=\"$key\" $selected>$val</option>";
                }
                echo '</select>';
                echo '</td>';
                echo '</tr>';
                break;
            }
        }
    }

} 