<?php

class WhatsiPLUS_Automation_View implements Whatsiplus_Register_Interface {

    private $settings_api;
    private $activated_plugins;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API;
    }

    public function register() {
        add_filter('whatsiplus_setting_section', array($this, 'set_automation_setting_section'));
        add_filter('whatsiplus_setting_fields', array($this, 'set_automation_setting_field'));
        add_action('admin_enqueue_scripts', array($this, 'load_scripts1')); // Updated hook
        // Loop through all activated plugins and register their hooks / filters
        $this->activated_plugins = WhatsiSupportedPlugin::get_activated_plugins();
        foreach ($this->activated_plugins as $plugin_class) {
            $plugin = new $plugin_class();
            $plugin->register();
        }
    }

    public function set_automation_setting_section($sections) {
        $children = array();
        $activated_plugins = $this->activated_plugins;
        foreach ($this->activated_plugins as $plugin_class) {
            $plugin = new $plugin_class();
            $children[] = $plugin->get_setting_section_data();
        }
        $sections[] = array(
            'id'             => 'whatsiplus_automation_setting',
            'title'          => __('Automation', 'WHATSIPLUS_TEXT_DOMAIN'),
            'submit_button'  => '',
            'children'       => $children,
        );

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public function set_automation_setting_field($setting_fields) {
        $activated_plugins = $this->activated_plugins;
        foreach ($activated_plugins as $plugin_class) {
            $plugin = new $plugin_class();
            $setting_fields[$plugin->get_option_id()] = $plugin->get_setting_field_data();
        }

        return $setting_fields;
    }

    public function load_scripts1($hook_suffix) {
        // Ensure to only load scripts on your specific admin page
        if ($hook_suffix !== 'your_plugin_page') {
            return;
        }

        wp_register_script('custom-script-automation', plugin_dir_url(__DIR__) . 'js/custom-script2.js', array('jquery'), null, true);

        $activated_plugins = $this->activated_plugins;
        $plugins = array();
        foreach ($activated_plugins as $plugin_class) {
            $plugin = new $plugin_class();
            $plugins[$plugin->get_option_id()] = $plugin->get_keywords_field();
        }

        wp_localize_script('custom-script-automation', 'whatsiplusPlugins', array(
            'plugins' => $plugins,
        ));

        wp_enqueue_script('custom-script-automation');
    }

}

?>
