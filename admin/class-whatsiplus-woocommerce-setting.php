<?php
use WhatsiAPI_WC\Helpers\Utils;
use WhatsiAPI_WC\Migrations\MigrateSendSMSPlugin;
use WhatsiAPI_WC\Migrations\MigrateWoocommercePlugin;

class Whatsiplus_WooCommerce_Setting implements Whatsiplus_Register_Interface {

	private $settings_api;
    private $log;

	function __construct() {
		$this->settings_api = new WONFW_Settings_API;
        $this->log = new Whatsiplus_WooCommerce_Logger();
	}

	public function register() {
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'admin_init', array( $this, 'initialise_default_recipient_setting' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'whatsiplus_setting_fields_custom_html', array( $this, 'whatsiplus_wc_not_activated' ), 10, 1 );

        add_action( 'init', array( $this, 'schedule_check_domain' ) );
        add_action( 'whatsiplus_check_domain', array( $this, 'check_domain_reachability' ) );

        add_filter( 'whatsiplus_setting_fields', array( $this, 'add_custom_order_status' ) );
	}

	function admin_init() {
		$this->settings_api->set_sections( $this->get_settings_sections() );
		$this->settings_api->set_fields( $this->get_settings_fields() );
		$this->settings_api->admin_init();
	}

	function admin_menu() {
		add_options_page( 'Whatsiplus WooCommerce', 'Whatsiplus Settings', 'manage_options', 'whatsiplus-woocommerce-setting',
            array($this, 'plugin_page')
        );
	}

	function get_settings_sections() {
		$sections = array(
			array(
				'id'    => 'whatsiplus_setting',
				'title' => __( 'Whatsiplus Settings', 'whatsiplus-order-notification-for-woocommerce' )
			),
			array(
				'id'    => 'whatsiplus_admin_setting',
				'title' => __( 'Admin Settings', 'whatsiplus-order-notification-for-woocommerce' ),
                'submit_button' => class_exists("woocommerce") ? null : '',
			),
			array(
                'id'    => 'whatsiplus_customer_setting',
				'title' => __( 'Customer Settings', 'whatsiplus-order-notification-for-woocommerce' ),
                'submit_button' => class_exists("woocommerce") ? null : '',
			)
		);

		$sections = apply_filters( 'whatsiplus_setting_section', $sections );

		return $sections;
	}

	function get_settings_fields() {
		global $woocommerce;
        $countries =  $this->get_countries();

        $default_country_code = whatsiplus_get_options('whatsiplus_woocommerce_country_code', 'whatsiplus_setting');
        $apikey = whatsiplus_get_options('whatsiplus_woocommerce_api_key', 'whatsiplus_setting');
        
        $country_code = '';

        if (!empty($default_country_code) && !empty($apikey)) {
            $dialing_country_code = $this->get_country_dialing_code($default_country_code);

            $lang = get_locale();
            if (strpos($lang, 'fa_IR') === 0) {
                $api_url = "http://api.whatsiplus.ir/serviceSettings/{$apikey}?countryCode={$dialing_country_code}";
            } else {
                $api_url = "https://api.whatsiplus.com/serviceSettings/{$apikey}?countryCode={$dialing_country_code}";
            }
                        
            $args = array(
                'timeout' => 20,
            );
        
            $response = wp_remote_get($api_url, $args);
        
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $this->log->add("Whatsiplus", "Error occurred while sending data to API: " . $error_message);
            } else {
                $response_body = wp_remote_retrieve_body($response);
            }
        }        

		$additional_billing_fields = '';
		$additional_billing_fields_desc  = '';
		$additional_billing_fields_array = $this->get_additional_billing_fields();
		foreach ( $additional_billing_fields_array as $field ) {
			$additional_billing_fields .= ', [' . $field . ']';
		}
		if ( $additional_billing_fields ) {
			$additional_billing_fields_desc = '<br />Custom tags: ' . substr( $additional_billing_fields, 2 );
		}

		$settings_fields = array(
			'whatsiplus_setting' => array(
                array(
					'name'  => 'whatsiplus_woocommerce_account_balance',
					'label' => __( 'API KEY Status:', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'  => __( 'Your WhatsiAPI account balance', 'whatsiplus-order-notification-for-woocommerce' ),
					'type'  => 'custom_html',
					'custom_html'  => array($this, "display_account_balance"),
				),
				array(
					'name'  => 'whatsiplus_woocommerce_api_key',
					'label' => __( 'API Key', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'  => __( 'Your WhatsApp API. Account can be registered <a href="https://whatsiplus.com/go/?url=apikey" target="blank">here</a>', 'whatsiplus-order-notification-for-woocommerce' ),
					'type'  => 'text',
				),
				array(
					'name'    		=> 'whatsiplus_woocommerce_country_code',
					'label'   		=> __( 'Default country', 'whatsiplus-order-notification-for-woocommerce' ),
					'class'     	=> array('chzn-drop'),
					'placeholder'	=> __( 'Select a Country', 'whatsiplus-order-notification-for-woocommerce'),
					'desc'    		=> 'Selected country will be use as default country info for mobile number when country info is not provided.',
					'type'    		=> 'select',
					'options' 		=> $countries,
                    'default'       => !empty($default_country_code) ? $default_country_code : $country_code,
				),
				array(
					'name'  => 'export_whatsiplus_log',
					'label' => 'Export Log',
					'desc'  => '<a href="' . admin_url( 'admin.php?page=whatsiplus-download-file&file=Whatsiplus' ) . '" class="button button-secondary">Export</a><div id="whatsi_sms[keyword-modal]" class="modal"></div>',
					'type'  => 'html'
				)
			),
			'whatsiplus_admin_setting'     => array(
				array(
                    'name'    => 'whatsiplus_woocommerce_admin_suborders_send_sms',
                    'label'   => __( 'Enable Suborders WhatsApp Notifications', 'whatsiplus-order-notification-for-woocommerce' ),
                    'desc'    => ' ' . __( 'Enable', 'whatsiplus-order-notification-for-woocommerce' ),
                    'type'    => 'checkbox',
                    'default' => 'off'
                ),                
				array(
					'name'    => 'whatsiplus_woocommerce_admin_send_sms_on',
					'label'   => __( 'Send notification on', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => __( 'Choose when to send a status notification message to your admin <br> Set <strong>low stock threshold</strong> for each product under <strong>WooCommerce Product -> Product Data -> Inventory -> Low Stock Threshold</strong>', 'whatsiplus-order-notification-for-woocommerce' ),
					'type'    => 'multicheck',
					'default' => array(
						'on-hold'    => 'on-hold',
						'processing' => 'processing'
					),
					'options' => array(
						'pending'           => ' Pending',
						'on-hold'           => ' On-hold',
						'processing'        => ' Processing',
						'completed'         => ' Completed',
						'cancelled'         => ' Cancelled',
						'refunded'          => ' Refunded',
						'failed'            => ' Failed',
						'low_stock_product' => ' Low stock product ',
					)
				),
				array(
					'name'  => 'whatsiplus_woocommerce_admin_sms_recipients',
					'label' => __( 'Mobile Number', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'  => __( 'Mobile number to receive new order notification. To send to multiple receivers, separate each entry with comma such as 0123456789, 0167888945', 'whatsiplus-order-notification-for-woocommerce' ),
					'type'  => 'text',
				),
                array(
					'name'  => 'whatsiplus_formatter_link_button',
					'label' => __( 'Visual Message Formatter', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'  => '',
					'type'  => 'custom_html',
					'custom_html' => function () {
						echo '<p><a href="https://whatsiplus.com/upload/wordpress/whatsapp-text-formatter/" target="_blank" rel="noopener noreferrer" class="button button-primary">' .
							esc_html__('Open WhatsApp Message Formatter', 'whatsiplus-order-notification-for-woocommerce') .
							'</a></p>';
					},
				),
				array(
					'name'    => 'whatsiplus_woocommerce_admin_sms_template',
					'label'   => __( 'Admin message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="admin" data-attr-target="whatsiplus_admin_setting[whatsiplus_woocommerce_admin_sms_template]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : You have a new order with order ID [order_id] and order amount [order_currency] [order_amount]. The order is now [order_status].', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_admin_sms_template_secondary',
					'label'   => __( 'Admin message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="admin" data-attr-target="whatsiplus_admin_setting[whatsiplus_woocommerce_admin_sms_template_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				),
				array(
					'name'    => 'whatsiplus_woocommerce_admin_sms_template_low_stock_product',
					'label'   => __( 'Low Stock Product Admin message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords-low-product-stock]" data-attr-type="admin" data-attr-target="whatsiplus_admin_setting[whatsiplus_woocommerce_admin_sms_template_low_stock_product]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Your product [product_name] has low stock. Current quantity: [product_stock_quantity]. Please restock soon.', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_admin_sms_template_low_stock_product_secondary',
					'label'   => __( 'Low Stock Product Admin message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords-low-product-stock]" data-attr-type="admin" data-attr-target="whatsiplus_admin_setting[whatsiplus_woocommerce_admin_sms_template_low_stock_product_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				),
			),
			'whatsiplus_customer_setting'  => array(
				array(
					'name'    => 'whatsiplus_woocommerce_suborders_send_sms',
					'label'   => __( 'Enable Suborders WhatsApp Notifications', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => ' ' . __( 'Enable', 'whatsiplus-order-notification-for-woocommerce' ),
					'type'    => 'checkbox',
					'default' => 'off'
				),
				array(
					'name'    => 'whatsiplus_woocommerce_send_sms_to',
					'label'   => __( 'Send message to', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => ' ' . __( 'Choose who to Send message to', 'whatsiplus-order-notification-for-woocommerce' ),
					'type'    => 'multicheck',
                    'default' => array(
						'billing-recipient'  => 'billing-recipient',
					),
					'options' => array(
                        'billing-recipient'  => 'Billing Recipient',
						'shipping-recipient' => 'Shipping Recipient',
					)
				),
				array(
					'name'    => 'whatsiplus_woocommerce_send_sms',
					'label'   => __( 'Send notification on', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => __( 'Choose when to send a status notification message to your customer', 'whatsiplus-order-notification-for-woocommerce' ),
					'type'    => 'multicheck',
                    'default' => array(
						'on-hold'    => 'on-hold',
						'processing' => 'processing',
						'completed'  => 'completed',
					),
					'options' => array(
						'pending'    => ' Pending',
						'on-hold'    => ' On-hold',
						'processing' => ' Processing',
						'completed'  => ' Completed',
						'cancelled'  => ' Cancelled',
						'refunded'   => ' Refunded',
						'failed'     => ' Failed'
					)
				),
                array(
					'name'  => 'whatsiplus_formatter_link_button',
					'label' => __( 'Visual Message Formatter', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'  => '',
					'type'  => 'custom_html',
					'custom_html' => function () {
						echo '<p><a href="https://whatsiplus.com/upload/wordpress/whatsapp-text-formatter/" target="_blank" rel="noopener noreferrer" class="button button-primary">' .
							esc_html__('Open WhatsApp Message Formatter', 'whatsiplus-order-notification-for-woocommerce') .
							'</a></p>';
					},
				),
				array(
                'name'    => 'whatsiplus_woocommerce_secondary_language',
                'label'   => __( 'Secondary Language Code', 'whatsiplus-order-notification-for-woocommerce' ),
                'desc'    => __( 'Select the secondary language - When this option is enabled, messages in the secondary language will be automatically delivered to customers based on their phone number information, Leave blank if you dont need this feature.', 'whatsiplus-order-notification-for-woocommerce' ),
                'type'    => 'select',
                'options' => array(
                    ''       => __( 'Select a Language', 'whatsiplus-order-notification-for-woocommerce' ),
                    'fa_IR'  => 'Persian (fa_IR)',
                    'fr_FR'  => 'French (fr_FR)',
                    'es_ES'  => 'Spanish (es_ES)',
                    'de_DE'  => 'German (de_DE)',
                    'ar_AR'  => 'Arabic (ar_AR)',
                    'zh_CN'  => 'Chinese (zh_CN)',
                    'ru_RU'  => 'Russian (ru_RU)',
                    'pt_BR'  => 'Portuguese (pt_BR)',
                    'it_IT'  => 'Italian (it_IT)',
                    'ja_JP'  => 'Japanese (ja_JP)',
                    'tr_TR'  => 'Turkish (tr_TR)',
                    'nl_NL'  => 'Dutch (nl_NL)',
                    'es_MX'  => 'Spanish - Mexico (es_MX)',
                    'es_AR'  => 'Spanish - Argentina (es_AR)',
                ),
                'default' => '',
                'class'   => array('chzn-drop')
            ),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_default',
					'label'   => __( 'Default Customer message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="default" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_default]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_default_secondary',
					'label'   => __( 'Default Customer message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="default" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_default_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_pending',
					'label'   => __( 'Pending message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="pending" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_pending]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_pending_secondary',
					'label'   => __( 'Pending message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="pending" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_pending_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_on-hold',
					'label'   => __( 'On-hold message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="on_hold" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_on-hold]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_on-hold_secondary',
					'label'   => __( 'On-hold message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="on_hold" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_on-hold_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_processing',
					'label'   => __( 'Processing message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="processing" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_processing]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_processing_secondary',
					'label'   => __( 'Processing message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="processing" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_processing_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_completed',
					'label'   => __( 'Completed message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="completed" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_completed]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_completed_secondary',
					'label'   => __( 'Completed message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="completed" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_completed_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_cancelled',
					'label'   => __( 'Cancelled message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="cancelled" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_cancelled]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_cancelled_secondary',
					'label'   => __( 'Cancelled message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="cancelled" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_cancelled_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_refunded',
					'label'   => __( 'Refunded message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="refunded" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_refunded]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_refunded_secondary',
					'label'   => __( 'Refunded message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="refunded" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_refunded_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_failed',
					'label'   => __( 'Failed message', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="failed" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_failed]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', 'whatsiplus-order-notification-for-woocommerce' )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_failed_secondary',
					'label'   => __( 'Failed message (Secondary Language)', 'whatsiplus-order-notification-for-woocommerce' ),
					'desc'    => 'Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="failed" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_failed_secondary]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => ''
				)
			)
		);

        if(!class_exists('woocommerce')) {
            unset($settings_fields['whatsiplus_admin_setting']);
            unset($settings_fields['whatsiplus_customer_setting']);
        }

		$settings_fields = apply_filters( 'whatsiplus_setting_fields', $settings_fields );

		return $settings_fields;
	}

    public function add_custom_order_status($setting_fields)
    {
        $log = new Whatsiplus_WooCommerce_Logger();
        $default_statuses = [
            'wc-pending',
            'wc-processing',
            'wc-on-hold',
            'wc-completed',
            'wc-cancelled',
            'wc-refunded',
            'wc-failed',
            'wc-checkout-draft'
        ];

        $fields_to_iterate = ['whatsiplus_admin_setting', 'whatsiplus_customer_setting', 'whatsiplus_multivendor_setting'];

        $all_wc_statuses = function_exists("wc_get_order_statuses") ? wc_get_order_statuses() : [];

        $custom_wc_statuses = array_diff_key($all_wc_statuses, array_flip($default_statuses));

        $processed_wc_statuses = [];

        foreach($custom_wc_statuses as $key => $value) {
            $trimmed_key = ltrim($key, 'wc-');
            $processed_wc_statuses[$trimmed_key] = $value;
        }

        foreach($fields_to_iterate as $field) {
            if(array_key_exists($field, $setting_fields)) {
                for( $i=0; $i<count($setting_fields[$field]); $i++ ) {
                    if(array_key_exists('options', $setting_fields[$field][$i])) {
                        $field_name = $setting_fields[$field][$i]['name'] ?? '';

                        if (!in_array($field_name, [
                            'whatsiplus_woocommerce_send_sms',
                            'whatsiplus_woocommerce_admin_send_sms_on',
                            'whatsiplus_woocommerce_vendor_send_sms_on'
                        ])) {
                            continue;
                        }

                        foreach($processed_wc_statuses as $processed_key => $processed_value) {
                            if( ! array_key_exists($processed_key, $setting_fields[$field][$i]['options']) ) {
                                $processed_value_text = " " . $processed_value;
                                $setting_fields[$field][$i]['options'][$processed_key] = $processed_value_text;

                                if($field == 'whatsiplus_customer_setting') {
                                    $message_label = $processed_value . ' Customer message';
                                    $message_label_secondary = $processed_value . ' Customer message (Secondary Language)';
                                    $message_default = "Your {$processed_value} message template";

                                    $setting_fields[$field][] = array(
                                        'name'    => "whatsiplus_woocommerce_sms_template_{$processed_key}",
                                        'label'   => $message_label,
                                        'desc'    => sprintf('Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="default" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_%s]" class="button button-secondary">Keywords</button>', $processed_key),
                                        'type'    => 'textarea',
                                        'rows'    => '8',
                                        'cols'    => '500',
                                        'css'     => 'min-width:350px;',
                                        'default' => $message_default
                                    );
                                    $setting_fields[$field][] = array(
                                        'name'    => "whatsiplus_woocommerce_sms_template_{$processed_key}_secondary",
                                        'label'   => $message_label_secondary,
                                        'desc'    => sprintf('Customize your secondary language message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="default" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_%s_secondary]" class="button button-secondary">Keywords</button>', $processed_key),
                                        'type'    => 'textarea',
                                        'rows'    => '8',
                                        'cols'    => '500',
                                        'css'     => 'min-width:350px;',
                                        'default' => ''
                                    );
                                }
                            }
                        }
                        break;
                    }
                }
                continue;
            }
        }
        
        return $setting_fields;
    }

	function plugin_page() {
		$this->settings_api->show_navigation();
		$this->settings_api->show_forms();
		echo '<input type="hidden" value="' . esc_attr( join(",", $this->get_additional_billing_fields()) ) . '" id="whatsiplus_new_billing_field" />';
		echo '</div>';
	}

	function get_pages() {
		$pages         = get_pages();
		$pages_options = array();
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$pages_options[ $page->ID ] = $page->post_title;
			}
		}
		return $pages_options;
	}

    public function initialise_default_recipient_setting() {
        if( !get_option("whatsiplus_customer_setting") ) {
            return;
        }

        $default_setting = [
            'billing-recipient' => 'billing-recipient'
        ];

        $option_setting = whatsiplus_get_options("whatsiplus_woocommerce_send_sms_to", "whatsiplus_customer_setting");

        if(empty($option_setting)) {
            return whatsiplus_update_options("whatsiplus_woocommerce_send_sms_to", $default_setting, "whatsiplus_customer_setting");
        }

        $send_to_billing_recipient = "";
        $send_to_shipping_recipient = "";

        if( isset($option_setting['billing-recipient']) ) {
            $send_to_billing_recipient = $option_setting['billing-recipient'];
        }

        if( isset($option_setting['shipping-recipient']) ) {
            $send_to_shipping_recipient = $option_setting['shipping-recipient'];
        }

        if( empty($send_to_billing_recipient) && empty($send_to_shipping_recipient) ) {
            return whatsiplus_update_options("whatsiplus_woocommerce_send_sms_to", $default_setting, "whatsiplus_customer_setting");
        }
    }

    public function check_domain_reachability()
    {
        
    }

    public function schedule_check_domain()
    {
        $latest_plugin_version = get_plugin_data(WHATSIPLUS_PLUGIN_DIR . "whatsiplus-woocommerce.php")['Version'];
        $current_plugin_version = get_option("whatsiplus_plugin_version");

        if(!empty($current_plugin_version)) {
            if(version_compare( $current_plugin_version, $latest_plugin_version ) < 0) {
                as_unschedule_all_actions("whatsiplus_check_domain");
                update_option("whatsiplus_plugin_version", $latest_plugin_version);
            }
        } else {
            update_option("whatsiplus_plugin_version", '1.3.0');
            $this->schedule_check_domain();
        }
        if ( false === as_has_scheduled_action( 'whatsiplus_check_domain' ) ) {
            as_schedule_recurring_action( strtotime( 'now' ), DAY_IN_SECONDS, 'whatsiplus_check_domain' );
        }
    }

    public function display_account_balance()
    {
        $log = new Whatsiplus_WooCommerce_Logger();
        try {
            $api_key = whatsiplus_get_options("whatsiplus_woocommerce_api_key", "whatsiplus_setting");
            
            $whatsiplus_rest = new WhatsiPLUS($api_key, "");
            $rest_response = $whatsiplus_rest->accountBalance();

            $rest_response = json_decode($rest_response);

            if($rest_response->{'status'} == "true"){
                $acc_balance = $rest_response->{'connectionStatus'};
                $countryCode = @$rest_response->{'countrycode'};
                $wNumber = $rest_response->{'whatsAppNumber'};  

            } else {
                $acc_balance = "Invalid API KEY";
            }

        } catch (Exception $e) {
            $log->add("Whatsiplus", print_r($e->getMessage(), 1));
            $acc_balance = 'Failed to retrieve status for API KEY';
        }

        if ($acc_balance === "Invalid API KEY")
        {
            $translated_url = esc_url( __('https://whatsiplus.com/go?url=apikey', 'whatsiplus-order-notification-for-woocommerce') );

            echo '<p style="color: red;"><b>' . esc_html__('Invalid API KEY', 'whatsiplus-order-notification-for-woocommerce') . '</b></p>' .
            '<h3>' . esc_html__('To access a free API KEY and unlock all the plugins features, please follow the link provided below.', 'whatsiplus-order-notification-for-woocommerce') . '</h3>' .
            '<h2><a href="' . $translated_url . '" target="_blank">' . esc_html__('Get an API key', 'whatsiplus-order-notification-for-woocommerce') . '</a></h2>';
        }
        else{
            if($acc_balance === "Connected to WhatsApp")
            {
                $acc_balance = sprintf(__('Status: %s', 'whatsiplus-order-notification-for-woocommerce'), $acc_balance);
                
                echo '<p style="color: green;"><b>' . esc_html__('API KEY is Valid', 'whatsiplus-order-notification-for-woocommerce') . '</b></p>';
                echo '<p style="color: green;"><b>' . esc_html($acc_balance) . '</b></p>';
                echo '<p style="color: green;"><b>' . esc_html($wNumber) . '</b></p>';
                echo '<p>' . esc_html__('Default country code:', 'whatsiplus-order-notification-for-woocommerce') . ' ' . esc_html($countryCode) . '</p>';
                echo '<p><a href="' . esc_url('https://whatsiplus.com/go?url=apikey') . '" target="_blank">' . esc_html__('Manage your service', 'whatsiplus-order-notification-for-woocommerce') . '</a></p>';

            }
            else if($acc_balance === "Not connected to WhatsApp")
            {
                $acc_balance = sprintf(__('API KEY is valid but status: %s', 'whatsiplus-order-notification-for-woocommerce'), $acc_balance);

                echo '<p style="color: red;"><b>' . esc_html($acc_balance) . '</b></p>';
                echo '<h3>' . esc_html__('To link the service with WhatsApp, please click on the provided link below.', 'whatsiplus-order-notification-for-woocommerce') . '</h3>';
                echo '<h2><a href="' . esc_url('https://whatsiplus.com/go?url=apikey') . '" target="_blank">' . esc_html__('Whatsiplus', 'whatsiplus-order-notification-for-woocommerce') . '</a></h2>';
                echo '<p>' . esc_html__('Default country code:', 'whatsiplus-order-notification-for-woocommerce') . ' ' . esc_html($countryCode) . '</p>';

            }
            else
            {
                echo '<p style="color: red;"><b>'. esc_html($acc_balance). '</b></p>';
            }
            
        }
            
    }

	function get_additional_billing_fields() {
		$default_billing_fields   = array(
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_state',
			'billing_country',
			'billing_postcode',
			'billing_phone',
			'billing_email'
		);
		$additional_billing_field = array();
		$billing_fields           = array_filter( get_option( 'wc_fields_billing', array() ) );
		foreach ( $billing_fields as $field_key => $field_info ) {
			if ( ! in_array( $field_key, $default_billing_fields ) && $field_info['enabled'] ) {
				array_push( $additional_billing_field, $field_key );
			}
		}
		return $additional_billing_field;
	}

    public function whatsiplus_wc_not_activated($form_id)
    {
        if(class_exists('woocommerce')) { return; }
        if(!($form_id === 'whatsiplus_admin_setting' || $form_id === 'whatsiplus_customer_setting')) { return; }
        ?>
        <div class="wrap">
            <h1>Whatsiplus WooCommerce Order Notification</h1>
            <p>This feature requires WooCommerce to be activated</p>
        </div>
        <?php
    }

    public function get_countries()
    {
        return array(
            "AF" => "Afghanistan",
            "AL" => "Albania",
            "DZ" => "Algeria",
            "AS" => "American Samoa",
            "AD" => "Andorra",
            "AO" => "Angola",
            "AI" => "Anguilla",
            "AQ" => "Antarctica",
            "AG" => "Antigua and Barbuda",
            "AR" => "Argentina",
            "AM" => "Armenia",
            "AW" => "Aruba",
            "AU" => "Australia",
            "AT" => "Austria",
            "AZ" => "Azerbaijan",
            "BS" => "Bahamas",
            "BH" => "Bahrain",
            "BD" => "Bangladesh",
            "BB" => "Barbados",
            "BY" => "Belarus",
            "BE" => "Belgium",
            "BZ" => "Belize",
            "BJ" => "Benin",
            "BM" => "Bermuda",
            "BT" => "Bhutan",
            "BO" => "Bolivia",
            "BA" => "Bosnia and Herzegovina",
            "BW" => "Botswana",
            "BV" => "Bouvet Island",
            "BR" => "Brazil",
            "IO" => "British Indian Ocean Territory",
            "BN" => "Brunei Darussalam",
            "BG" => "Bulgaria",
            "BF" => "Burkina Faso",
            "BI" => "Burundi",
            "KH" => "Cambodia",
            "CM" => "Cameroon",
            "CA" => "Canada",
            "CV" => "Cape Verde",
            "KY" => "Cayman Islands",
            "CF" => "Central African Republic",
            "TD" => "Chad",
            "CL" => "Chile",
            "CN" => "China",
            "CX" => "Christmas Island",
            "CC" => "Cocos (Keeling) Islands",
            "CO" => "Colombia",
            "KM" => "Comoros",
            "CG" => "Congo",
            "CD" => "Congo, the Democratic Republic of the",
            "CK" => "Cook Islands",
            "CR" => "Costa Rica",
            "CI" => "Cote D'Ivoire",
            "HR" => "Croatia",
            "CU" => "Cuba",
            "CY" => "Cyprus",
            "CZ" => "Czech Republic",
            "DK" => "Denmark",
            "DJ" => "Djibouti",
            "DM" => "Dominica",
            "DO" => "Dominican Republic",
            "EC" => "Ecuador",
            "EG" => "Egypt",
            "SV" => "El Salvador",
            "GQ" => "Equatorial Guinea",
            "ER" => "Eritrea",
            "EE" => "Estonia",
            "ET" => "Ethiopia",
            "FK" => "Falkland Islands (Malvinas)",
            "FO" => "Faroe Islands",
            "FJ" => "Fiji",
            "FI" => "Finland",
            "FR" => "France",
            "GF" => "French Guiana",
            "PF" => "French Polynesia",
            "TF" => "French Southern Territories",
            "GA" => "Gabon",
            "GM" => "Gambia",
            "GE" => "Georgia",
            "DE" => "Germany",
            "GH" => "Ghana",
            "GI" => "Gibraltar",
            "GR" => "Greece",
            "GL" => "Greenland",
            "GD" => "Grenada",
            "GP" => "Guadeloupe",
            "GU" => "Guam",
            "GT" => "Guatemala",
            "GN" => "Guinea",
            "GW" => "Guinea-Bissau",
            "GY" => "Guyana",
            "HT" => "Haiti",
            "HM" => "Heard Island and Mcdonald Islands",
            "VA" => "Holy See (Vatican City State)",
            "HN" => "Honduras",
            "HK" => "Hong Kong",
            "HU" => "Hungary",
            "IS" => "Iceland",
            "IN" => "India",
            "ID" => "Indonesia",
            "IR" => "Iran, Islamic Republic of",
            "IQ" => "Iraq",
            "IE" => "Ireland",
            "IL" => "Israel",
            "IT" => "Italy",
            "JM" => "Jamaica",
            "JP" => "Japan",
            "JO" => "Jordan",
            "KZ" => "Kazakhstan",
            "KE" => "Kenya",
            "KI" => "Kiribati",
            "KP" => "Korea, Democratic People's Republic of",
            "KR" => "Korea, Republic of",
            "KW" => "Kuwait",
            "KG" => "Kyrgyzstan",
            "LA" => "Lao People's Democratic Republic",
            "LV" => "Latvia",
            "LB" => "Lebanon",
            "LS" => "Lesotho",
            "LR" => "Liberia",
            "LY" => "Libyan Arab Jamahiriya",
            "LI" => "Liechtenstein",
            "LT" => "Lithuania",
            "LU" => "Luxembourg",
            "MO" => "Macao",
            "MK" => "Macedonia, the Former Yugoslav Republic of",
            "MG" => "Madagascar",
            "MW" => "Malawi",
            "MY" => "Malaysia",
            "MV" => "Maldives",
            "ML" => "Mali",
            "MT" => "Malta",
            "MH" => "Marshall Islands",
            "MQ" => "Martinique",
            "MR" => "Mauritania",
            "MU" => "Mauritius",
            "YT" => "Mayotte",
            "MX" => "Mexico",
            "FM" => "Micronesia, Federated States of",
            "MD" => "Moldova, Republic of",
            "MC" => "Monaco",
            "MN" => "Mongolia",
            "MS" => "Montserrat",
            "MA" => "Morocco",
            "MZ" => "Mozambique",
            "MM" => "Myanmar",
            "NA" => "Namibia",
            "NR" => "Nauru",
            "NP" => "Nepal",
            "NL" => "Netherlands",
            "AN" => "Netherlands Antilles",
            "NC" => "New Caledonia",
            "NZ" => "New Zealand",
            "NI" => "Nicaragua",
            "NE" => "Niger",
            "NG" => "Nigeria",
            "NU" => "Niue",
            "NF" => "Norfolk Island",
            "MP" => "Northern Mariana Islands",
            "NO" => "Norway",
            "OM" => "Oman",
            "PK" => "Pakistan",
            "PW" => "Palau",
            "PS" => "Palestinian Territory, Occupied",
            "PA" => "Panama",
            "PG" => "Papua New Guinea",
            "PY" => "Paraguay",
            "PE" => "Peru",
            "PH" => "Philippines",
            "PN" => "Pitcairn",
            "PL" => "Poland",
            "PT" => "Portugal",
            "PR" => "Puerto Rico",
            "QA" => "Qatar",
            "RE" => "Reunion",
            "RO" => "Romania",
            "RU" => "Russian Federation",
            "RW" => "Rwanda",
            "SH" => "Saint Helena",
            "KN" => "Saint Kitts and Nevis",
            "LC" => "Saint Lucia",
            "PM" => "Saint Pierre and Miquelon",
            "VC" => "Saint Vincent and the Grenadines",
            "WS" => "Samoa",
            "SM" => "San Marino",
            "ST" => "Sao Tome and Principe",
            "SA" => "Saudi Arabia",
            "SN" => "Senegal",
            "CS" => "Serbia and Montenegro",
            "SC" => "Seychelles",
            "SL" => "Sierra Leone",
            "SG" => "Singapore",
            "SK" => "Slovakia",
            "SI" => "Slovenia",
            "SB" => "Solomon Islands",
            "SO" => "Somalia",
            "ZA" => "South Africa",
            "GS" => "South Georgia and the South Sandwich Islands",
            "ES" => "Spain",
            "LK" => "Sri Lanka",
            "SD" => "Sudan",
            "SR" => "Suriname",
            "SJ" => "Svalbard and Jan Mayen",
            "SZ" => "Swaziland",
            "SE" => "Sweden",
            "CH" => "Switzerland",
            "SY" => "Syrian Arab Republic",
            "TW" => "Taiwan, Province of China",
            "TJ" => "Tajikistan",
            "TZ" => "Tanzania, United Republic of",
            "TH" => "Thailand",
            "TL" => "Timor-Leste",
            "TG" => "Togo",
            "TK" => "Tokelau",
            "TO" => "Tonga",
            "TT" => "Trinidad and Tobago",
            "TN" => "Tunisia",
            "TR" => "Turkey",
            "TM" => "Turkmenistan",
            "TC" => "Turks and Caicos Islands",
            "TV" => "Tuvalu",
            "UG" => "Uganda",
            "UA" => "Ukraine",
            "AE" => "United Arab Emirates",
            "GB" => "United Kingdom",
            "US" => "United States",
            "UM" => "United States Minor Outlying Islands",
            "UY" => "Uruguay",
            "UZ" => "Uzbekistan",
            "VU" => "Vanuatu",
            "VE" => "Venezuela",
            "VN" => "Viet Nam",
            "VG" => "Virgin Islands, British",
            "VI" => "Virgin Islands, U.s.",
            "WF" => "Wallis and Futuna",
            "EH" => "Western Sahara",
            "YE" => "Yemen",
            "ZM" => "Zambia",
            "ZW" => "Zimbabwe"
        );
    }

    public function get_country_dialing_code($country_code)
    {
        $country_code=strtoupper($country_code);
        $country_codes= array(
            "AF" => "93",
            "AL" => "355",
            "DZ" => "213",
            "AS" => "1684",
            "AD" => "376",
            "AO" => "244",
            "AI" => "1264",
            "AQ" => "672",
            "AG" => "1268",
            "AR" => "54",
            "AM" => "374",
            "AW" => "297",
            "AU" => "61",
            "AT" => "43",
            "AZ" => "994",
            "BS" => "1242",
            "BH" => "973",
            "BD" => "880",
            "BB" => "1246",
            "BY" => "375",
            "BE" => "32",
            "BZ" => "501",
            "BJ" => "229",
            "BM" => "1441",
            "BT" => "975",
            "BO" => "591",
            "BA" => "387",
            "BW" => "267",
            "BV" => "47",
            "BR" => "55",
            "IO" => "246",
            "BN" => "673",
            "BG" => "359",
            "BF" => "226",
            "BI" => "257",
            "KH" => "855",
            "CM" => "237",
            "CA" => "1",
            "CV" => "238",
            "KY" => "1345",
            "CF" => "236",
            "TD" => "235",
            "CL" => "56",
            "CN" => "86",
            "CX" => "61",
            "CC" => "61",
            "CO" => "57",
            "KM" => "269",
            "CG" => "242",
            "CD" => "243",
            "CK" => "682",
            "CR" => "506",
            "CI" => "225",
            "HR" => "385",
            "CU" => "53",
            "CY" => "357",
            "CZ" => "420",
            "DK" => "45",
            "DJ" => "253",
            "DM" => "1767",
            "DO" => "1809",
            "EC" => "593",
            "EG" => "20",
            "SV" => "503",
            "GQ" => "240",
            "ER" => "291",
            "EE" => "372",
            "ET" => "251",
            "FK" => "500",
            "FO" => "298",
            "FJ" => "679",
            "FI" => "358",
            "FR" => "33",
            "GF" => "594",
            "PF" => "689",
            "TF" => "262",
            "GA" => "241",
            "GM" => "220",
            "GE" => "995",
            "DE" => "49",
            "GH" => "233",
            "GI" => "350",
            "GR" => "30",
            "GL" => "299",
            "GD" => "1473",
            "GP" => "590",
            "GU" => "1671",
            "GT" => "502",
            "GN" => "224",
            "GW" => "245",
            "GY" => "592",
            "HT" => "509",
            "HM" => "672",
            "VA" => "379",
            "HN" => "504",
            "HK" => "852",
            "HU" => "36",
            "IS" => "354",
            "IN" => "91",
            "ID" => "62",
            "IR" => "98",
            "IQ" => "964",
            "IE" => "353",
            "IL" => "972",
            "IT" => "39",
            "JM" => "1876",
            "JP" => "81",
            "JO" => "962",
            "KZ" => "7",
            "KE" => "254",
            "KI" => "686",
            "KP" => "850",
            "KR" => "82",
            "KW" => "965",
            "KG" => "996",
            "LA" => "856",
            "LV" => "371",
            "LB" => "961",
            "LS" => "266",
            "LR" => "231",
            "LY" => "218",
            "LI" => "423",
            "LT" => "370",
            "LU" => "352",
            "MO" => "853",
            "MK" => "389",
            "MG" => "261",
            "MW" => "265",
            "MY" => "60",
            "MV" => "960",
            "ML" => "223",
            "MT" => "356",
            "MH" => "692",
            "MQ" => "596",
            "MR" => "222",
            "MU" => "230",
            "YT" => "262",
            "MX" => "52",
            "FM" => "691",
            "MD" => "373",
            "MC" => "377",
            "MN" => "976",
            "MS" => "1664",
            "MA" => "212",
            "MZ" => "258",
            "MM" => "95",
            "NA" => "264",
            "NR" => "674",
            "NP" => "977",
            "NL" => "31",
            "AN" => "599",
            "NC" => "687",
            "NZ" => "64",
            "NI" => "505",
            "NE" => "227",
            "NG" => "234",
            "NU" => "683",
            "NF" => "672",
            "MP" => "1670",
            "NO" => "47",
            "OM" => "968",
            "PK" => "92",
            "PW" => "680",
            "PS" => "970",
            "PA" => "507",
            "PG" => "675",
            "PY" => "595",
            "PE" => "51",
            "PH" => "63",
            "PN" => "870",
            "PL" => "48",
            "PT" => "351",
            "PR" => "1787",
            "QA" => "974",
            "RE" => "262",
            "RO" => "40",
            "RU" => "7",
            "RW" => "250",
            "SH" => "290",
            "KN" => "1869",
            "LC" => "1758",
            "PM" => "508",
            "VC" => "1784",
            "WS" => "685",
            "SM" => "378",
            "ST" => "239",
            "SA" => "966",
            "SN" => "221",
            "CS" => "381",
            "SC" => "248",
            "SL" => "232",
            "SG" => "65",
            "SK" => "421",
            "SI" => "386",
            "SB" => "677",
            "SO" => "252",
            "ZA" => "27",
            "GS" => "500",
            "ES" => "34",
            "LK" => "94",
            "SD" => "249",
            "SR" => "597",
            "SJ" => "47",
            "SZ" => "268",
            "SE" => "46",
            "CH" => "41",
            "SY" => "963",
            "TW" => "886",
            "TJ" => "992",
            "TZ" => "255",
            "TH" => "66",
            "TL" => "670",
            "TG" => "228",
            "TK" => "690",
            "TO" => "676",
            "TT" => "1868",
            "TN" => "216",
            "TR" => "90",
            "TM" => "993",
            "TC" => "1649",
            "TV" => "688",
            "UG" => "256",
            "UA" => "380",
            "AE" => "971",
            "GB" => "44",
            "US" => "1",
            "UM" => "1800",
            "UY" => "598",
            "UZ" => "998",
            "VU" => "678",
            "VE" => "58",
            "VN" => "84",
            "VG" => "1284",
            "VI" => "1340",
            "WF" => "681",
            "EH" => "212",
            "YE" => "967",
            "ZM" => "260",
            "ZW" => "263"
        );

        if (array_key_exists($country_code, $country_codes)) {
            return $country_codes[$country_code];
        } else {
            return "0";
        }
    }
}
?>