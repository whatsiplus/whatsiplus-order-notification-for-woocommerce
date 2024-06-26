<?php

class WhatsiPLUS_Help_View implements Whatsiplus_Register_Interface {

	private $settings_api;

	function __construct() {
		$this->settings_api = new WONFW_Settings_API;
	}

	public function register() {
        add_filter( 'whatsiplus_setting_section',     array($this, 'set_help_setting_section' ) );
		add_filter( 'whatsiplus_setting_fields',      array($this, 'set_help_setting_field' ) );
        add_action( 'whatsiplus_setting_fields_custom_html', array($this, 'display_help_page'), 10, 1);
	}

	public function set_help_setting_section( $sections ) {
		$sections[] = array(
            'id'               => 'whatsiplus_help_setting',
            'title'            => __( 'Help', 'whatsiplus-order-notification-for-woocommerce' ),
            'submit_button'    => '',
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
        <h1>What is Whatsiplus Notification for WooCommerce?</h1>
        <h2><a href="https://whatsiplus.com/" target="_blank">Whatsiplus</a> is a WhatsApp API service that allows business owners to communicate with their users through WhatsApp.</h2>
        <h1>How to get an API KEY?</h1>
        <h2>If you want to use the plugin, you need to generate an API key. You can do this by creating an account <a href="https://panel.whatsiplus.com/index.php?rp=/store/whatsapp" target="_blank"><strong>here</strong></a>.  Creating an account is free, you can use all the services for an unlimited period of time up to 10 days.</h2>
        <h1>Have questions?</h1>
        <h2>You can see frequently asked questions in this <a href="https://whatsiplus.com/faq/all/" target="_blank"><strong>link</strong></a>.</h2>
		<h1>Contact</h1>
		<h2>If you have any questions, you can contact the support section of the site <a href="https://panel.whatsiplus.com/submitticket.php?step=2&deptid=2" target="_blank"><strong>Contact us</strong></a>.</h2>
    <?php
    }


}

?>
