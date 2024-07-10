<?php

class WhatsiPLUS_Help_View implements Whatsiplus_Register_Interface {

    private $settings_api;

    function __construct() {
        $this->settings_api = new WONFW_Settings_API;
    }

    public function register() {
        add_filter( 'whatsiplus_setting_section', array($this, 'set_help_setting_section') );
        add_filter( 'whatsiplus_setting_fields', array($this, 'set_help_setting_field') );
        add_action( 'whatsiplus_setting_fields_custom_html', array($this, 'display_help_page'), 10, 1);
    }

    public function set_help_setting_section( $sections ) {
        $sections[] = array(
            'id'            => 'whatsiplus_help_setting',
            'title'         => __( 'Help', 'whatsiplus-order-notification-for-woocommerce' ),
            'submit_button' => '',
        );

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public function set_help_setting_field( $setting_fields ) {
        return $setting_fields;
    }

    public function display_help_page($form_id) {
        if($form_id !== 'whatsiplus_help_setting') { return; }
    ?>
        <br>
        <h1><?php esc_html_e('What is Whatsiplus Notification for WooCommerce?', 'whatsiplus-order-notification-for-woocommerce'); ?></h1>
        <h2><a href="<?php echo esc_url( __('https://whatsiplus.com/', 'whatsiplus-order-notification-for-woocommerce') ); ?>" target="_blank"><?php esc_html_e('Whatsiplus', 'whatsiplus-order-notification-for-woocommerce'); ?></a> <?php esc_html_e('is a WhatsApp API service that allows business owners to communicate with their users through WhatsApp.', 'whatsiplus-order-notification-for-woocommerce'); ?></h2>
        <h1><?php esc_html_e('How to get an API KEY?', 'whatsiplus-order-notification-for-woocommerce'); ?></h1>
        <h2><?php esc_html_e('If you want to use the plugin, you need to generate an API key. You can do this by creating an account', 'whatsiplus-order-notification-for-woocommerce'); ?> <a href="<?php echo esc_url( __('https://panel.whatsiplus.com/index.php?rp=/store/whatsapp', 'whatsiplus-order-notification-for-woocommerce') ); ?>" target="_blank"><strong><?php esc_html_e('here', 'whatsiplus-order-notification-for-woocommerce'); ?></strong></a>. <?php esc_html_e('Creating an account is free, you can use all the services for an unlimited period of time up to 10 days.', 'whatsiplus-order-notification-for-woocommerce'); ?></h2>
        <h1><?php esc_html_e('Have questions?', 'whatsiplus-order-notification-for-woocommerce'); ?></h1>
        <h2><?php esc_html_e('You can see frequently asked questions in this', 'whatsiplus-order-notification-for-woocommerce'); ?> <a href="<?php echo esc_url( __('https://whatsiplus.com/faq/all/', 'whatsiplus-order-notification-for-woocommerce') ); ?>" target="_blank"><strong><?php esc_html_e('link', 'whatsiplus-order-notification-for-woocommerce'); ?></strong></a>.</h2>
        <h1><?php esc_html_e('Contact', 'whatsiplus-order-notification-for-woocommerce'); ?></h1>
        <h2><?php esc_html_e('If you have any questions, you can contact the support section of the site', 'whatsiplus-order-notification-for-woocommerce'); ?> <a href="<?php echo esc_url( __('https://panel.whatsiplus.com/submitticket.php?step=2&deptid=2', 'whatsiplus-order-notification-for-woocommerce') ); ?>" target="_blank"><strong><?php esc_html_e('Contact us', 'whatsiplus-order-notification-for-woocommerce'); ?></strong></a>.</h2>
    <?php
    }    
}

?>
