<?php

class WhatsiPLUS_Help_View implements Whatsiplus_Register_Interface {

	private $settings_api;

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;
	}

	public function register() {
        add_filter( 'whatsiplus_setting_section',     array($this, 'set_help_setting_section' ) );
		add_filter( 'whatsiplus_setting_fields',      array($this, 'set_help_setting_field' ) );
        add_action( 'whatsiplus_setting_fields_custom_html', array($this, 'display_help_page'), 10, 1);
	}

	public function set_help_setting_section( $sections ) {
		$sections[] = array(
            'id'               => 'whatsiplus_help_setting',
            'title'            => __( 'Help', WHATSIPLUS_TEXT_DOMAIN ),
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
        <h4>What is Whatsiplus Notification for WooCommerce?</h4>
        <p><a href="https://whatsiplus.com/" target="_blank">Whatsiplus</a> is a WhatsApp API service that allows business owners to communicate with their users through WhatsApp.</p>
        <h4>How to get an API KEY?</h4>
        <p>If you want to use the plugin, you need to generate an API key. You can do this by creating an account <a href="https://panel.whatsiplus.com/index.php?rp=/store/whatsapp" target="_blank"><strong>here</strong></a>.  Creating an account is free, you can use all the services for an unlimited period of time up to 10 days..</p>
        <h4>Have questions?</h4>
        <p>You can see frequently asked questions in this <a href="https://whatsiplus.com/faq/all/" target="_blank"><strong>link</strong></a>.</p>
		<h4>Contact</h4>
		<p>If you have any questions, you can contact the support section of the site <a href="https://panel.whatsiplus.com/submitticket.php?step=2&deptid=2" target="_blank"><strong>Contact us</strong></a>.</p>
    <?php
    }


}

?>
