<?php

class WhatsiPLUS_Logs_View implements Whatsiplus_Register_Interface {

    private $settings_api;
    private $logger;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API;
        $this->logger = new Whatsiplus_WooCommerce_Logger(); // Instantiate the logger
    }

    public function register() {
        add_filter( 'whatsiplus_setting_section', array($this, 'set_logs_setting_section' ) );
        add_filter( 'whatsiplus_setting_fields',  array($this, 'set_logs_setting_field' ) );
        add_action( 'whatsiplus_setting_fields_custom_html', array($this, 'display_logs_page'), 10, 1);
    }

    public function set_logs_setting_section( $sections ) {
        $sections[] = array(
            'id'               => 'whatsiplus_logs_setting',
            'title'            => __( 'Logs', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'submit_button'    => '',
        );

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public function set_logs_setting_field( $setting_fields ) {
        // No settings fields for logs in this example, you can add if needed
        return $setting_fields;
    }

    public function display_logs_page($form_id) {
        if($form_id !== 'whatsiplus_logs_setting') { return; }

        $customer_logs = $this->logger->get_log_file("Whatsiplus"); // Get logs from the logger

        ?>
        <div class="bootstrap-wrapper">
            <div id="setting-error-settings_updated" class="border border-primary" style="padding:4px;width:1200px;height:600px;overflow:auto">
                <pre><strong><?php echo esc_html($customer_logs); ?></strong></pre>
            </div>
        </div>
        <?php
    }
}

?>
