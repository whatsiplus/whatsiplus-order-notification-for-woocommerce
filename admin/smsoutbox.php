<?php

class WhatsiPLUS_SMSOutbox_View implements Whatsiplus_Register_Interface {

	private $settings_api;

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;
	}

	public function register() {
        add_filter( 'whatsiplus_setting_section', array($this, 'set_smsoutbox_setting_section' ) );
		add_filter( 'whatsiplus_setting_fields',  array($this, 'set_smsoutbox_setting_field' ) );
        add_action( 'whatsiplus_setting_fields_custom_html', array($this, 'display_smsoutbox_page'), 10, 1);
	}

	public function set_smsoutbox_setting_section( $sections ) {
		$sections[] = array(
            'id'               => 'whatsiplus_smsoutbox_setting',
            'title'            => __( 'Message Outbox', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'submit_button'    => '',
            // 'action'           => 'whatsiplus_sms_form',
            // 'action_url'       => admin_url('admin-post.php'),
		);

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public function set_smsoutbox_setting_field( $setting_fields ) {
		return $setting_fields;
	}

    public function display_smsoutbox_page($form_id) {
        if($form_id != 'whatsiplus_smsoutbox_setting') { return; }
        global $wpdb;
    ?>
        <br>
        <div class="bootstrap-wrapper">
            <span>List of 30 last sent messages</span>
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col" id='date'>Date</th>
                        <th scope="col" id='sender'>Sender</th>
                        <th scope="col" id='recipient'>Recipient</th>
                        <th scope="col" id='message'>Message</th>
                        <th scope="col" id='message'>Status</th>
                    </tr>
                </thead>
            <tbody id="the-list" data-wp-lists='list:id'>
            <?php
            global $wpdb;
            $result = $wpdb->get_results( 
                "SELECT * FROM whatsiplus_wc_send_sms_outbox ORDER BY id DESC"
            );
            foreach ( $result as $print ) {
                ?>
                <tr>
                    <td><?php echo esc_attr( $print->date ); ?></td>
                    <td><?php echo esc_attr( $print->sender ); ?></td>
                    <td><?php echo esc_attr( $print->recipient ); ?></td>
                    <td><?php echo esc_attr( $print->message ); ?></td>
                    <td><?php echo esc_attr( $print->status ); ?></td>
                </tr>
                <?php
            }
            ?>

            </tbody>

            </table>
        </div>

    <?php
    }


}

?>
