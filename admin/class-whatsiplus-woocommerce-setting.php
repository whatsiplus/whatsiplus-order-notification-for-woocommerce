<?php
use WhatsiAPI_WC\Helpers\Utils;
use WhatsiAPI_WC\Migrations\MigrateSendSMSPlugin;
use WhatsiAPI_WC\Migrations\MigrateWoocommercePlugin;

class Whatsiplus_WooCommerce_Setting implements Whatsiplus_Register_Interface {

	private $settings_api;
    private $log;

	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;
        $this->log = new Whatsiplus_WooCommerce_Logger();

        $this->prev_default_country_code = get_option('whatsiplus_prev_default_country_code');

	}

	public function register() {
        // if ( class_exists( 'woocommerce' ) ) {
            add_action( 'admin_init', array( $this, 'admin_init' ) );
            add_action( 'admin_init', array( $this, 'initialise_default_recipient_setting' ) );
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'whatsiplus_setting_fields_custom_html', array( $this, 'whatsiplus_wc_not_activated' ), 10, 1 );

            add_action( 'init', array( $this, 'schedule_check_domain' ) );
            add_action( 'whatsiplus_check_domain', array( $this, 'check_domain_reachability' ) );

            add_filter( 'whatsiplus_setting_fields', array( $this, 'add_custom_order_status' ) );

        // } else {
        //     add_action( 'admin_menu', array( $this, 'woocommerce_not_activated_menu_view' ) );
        // }
	}

	function admin_init() {
		//set the settings
		$this->settings_api->set_sections( $this->get_settings_sections() );
		$this->settings_api->set_fields( $this->get_settings_fields() );

		//initialize settings
		$this->settings_api->admin_init();
	}

	function admin_menu() {
		add_options_page( 'WhatsiPLUS WooCommerce', 'Whatsiplus Settings', 'manage_options', 'whatsiplus-woocommerce-setting',
            array($this, 'plugin_page')
        );
	}

	function get_settings_sections() {
		$sections = array(
			array(
				'id'    => 'whatsiplus_setting',
				'title' => __( 'Whatsiplus Settings', WHATSIPLUS_TEXT_DOMAIN )
			),
			array(
				'id'    => 'whatsiplus_admin_setting',
				'title' => __( 'Admin Settings', WHATSIPLUS_TEXT_DOMAIN ),
                'submit_button' => class_exists("woocommerce") ? null : '',
			),
			array(
                'id'    => 'whatsiplus_customer_setting',
				'title' => __( 'Customer Settings', WHATSIPLUS_TEXT_DOMAIN ),
                'submit_button' => class_exists("woocommerce") ? null : '',
			)
		);

		$sections = apply_filters( 'whatsiplus_setting_section', $sections );

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	function get_settings_fields() {
		//WooCommerce Country
		global $woocommerce;
        // $countries_obj = $this->get_countries();
    	// $countries_obj   = new WC_Countries();
		// $countries   = $countries_obj->__get('countries');
        $countries =  $this->get_countries();

        // country
        $default_country_code = whatsiplus_get_options('whatsiplus_woocommerce_country_code', 'whatsiplus_setting');
        $apikey = whatsiplus_get_options('whatsiplus_woocommerce_api_key', 'whatsiplus_setting');
        
        $country_code = '';
        if( empty($default_country_code) ) {
            $user_ip = $this->get_user_ip();
            if(!empty($user_ip) ) {
                $country_code = $this->get_country_code_from_ip($user_ip);
            }

            
        }

        if (!empty($default_country_code) && !empty($apikey)) {
            $dialing_country_code = $this->get_country_dialing_code($default_country_code);
            $api_url = "https://api.whatsiplus.com/serviceSettings/{$apikey}?countryCode={$dialing_country_code}";

            try {
                // Initialize cURL session
                $c = curl_init();

                // Set cURL options
                curl_setopt($c, CURLOPT_URL, $api_url);
                curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'GET'); // Set request method to POST if needed

                $response = curl_exec($c);

                if ($response === false) {
                    throw new Exception(curl_error($c), curl_errno($c));
                }
                curl_close($c);
                //$this->log->add("Whatsiplus", "URL: {$api_url}");
                //$this->log->add("Whatsiplus", "Response from API: {$response}");

            } catch (Exception $e) {
                // Handle exceptions
                $this->log->add("Whatsiplus", "Error occurred while sending data to API: " . $e->getMessage());
            }
        }



        //$country_code = '';
        //if( empty($default_country_code) ) {
        //    $country_code = $this->get_country_code_from_ip();
        //}

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
					'label' => __( 'API KEY Status:', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'  => __( 'Your WhatsiAPI account balance', WHATSIPLUS_TEXT_DOMAIN ),
					'type'  => 'custom_html',
					'custom_html'  => array($this, "display_account_balance"),
				),
				array(
					'name'  => 'whatsiplus_woocommerce_api_key',
					'label' => __( 'API Key', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'  => __( 'Your WhatsApp API. Account can be registered <a href="https://whatsiplus.com/go/?url=apikey" target="blank">here</a>', WHATSIPLUS_TEXT_DOMAIN ),
					'type'  => 'text',
				),
				array(//Get default country v1.1.17
					'name'    		=> 'whatsiplus_woocommerce_country_code',
					'label'   		=> __( 'Default country', WHATSIPLUS_TEXT_DOMAIN ),
					'class'     	=> array('chzn-drop'),
					'placeholder'	=> __( 'Select a Country', WHATSIPLUS_TEXT_DOMAIN),
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
					'label'   => __( 'Enable Suborders WhatsApp Notifications', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => ' ' . __( 'Enable',  ),
					'type'    => 'checkbox',
					'default' => 'off'
				),
				array(
					'name'    => 'whatsiplus_woocommerce_admin_send_sms_on',
					'label'   => __( '	Send notification on', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => __( 'Choose when to send a status notification message to your admin <br> Set <strong>low stock threshold</strong> for each product under <strong>WooCommerce Product -> Product Data -> Inventory -> Low Stock Threshold</strong>', WHATSIPLUS_TEXT_DOMAIN ),
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
					'label' => __( 'Mobile Number', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'  => __( 'Mobile number to receive new order notification. To send to multiple receivers, separate each entry with comma such as 0123456789, 0167888945', WHATSIPLUS_TEXT_DOMAIN ),
					'type'  => 'text',
				),
				array(
					'name'    => 'whatsiplus_woocommerce_admin_sms_template',
					'label'   => __( 'Admin message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="admin" data-attr-target="whatsiplus_admin_setting[whatsiplus_woocommerce_admin_sms_template]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : You have a new order with order ID [order_id] and order amount [order_currency] [order_amount]. The order is now [order_status].', WHATSIPLUS_TEXT_DOMAIN )
                ),
				array(
					'name'    => 'whatsiplus_woocommerce_admin_sms_template_low_stock_product',
					'label'   => __( 'Low Stock Product Admin message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords-low-product-stock]" data-attr-type="admin" data-attr-target="whatsiplus_admin_setting[whatsiplus_woocommerce_admin_sms_template_low_stock_product]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Your product [product_name] has low stock. Current quantity: [product_stock_quantity]. Please restock soon.', WHATSIPLUS_TEXT_DOMAIN )
                ),
			),
			'whatsiplus_customer_setting'  => array(
				array(
					'name'    => 'whatsiplus_woocommerce_suborders_send_sms',
					'label'   => __( 'Enable Suborders WhatsApp Notifications', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => ' ' . __( 'Enable', WHATSIPLUS_TEXT_DOMAIN ),
					'type'    => 'checkbox',
					'default' => 'off'
				),
				array(
					'name'    => 'whatsiplus_woocommerce_send_sms_to',
					'label'   => __( 'Send message to', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => ' ' . __( 'Choose who to Send message to', WHATSIPLUS_TEXT_DOMAIN ),
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
					'label'   => __( '	Send notification on', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => __( 'Choose when to send a status notification message to your customer', WHATSIPLUS_TEXT_DOMAIN ),
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
					'name'    => 'whatsiplus_woocommerce_sms_template_default',
					'label'   => __( 'Default Customer message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="default" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_default]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', WHATSIPLUS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_pending',
					'label'   => __( 'Pending message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="pending" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_pending]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', WHATSIPLUS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_on-hold',
					'label'   => __( 'On-hold message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="on_hold" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_on-hold]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', WHATSIPLUS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_processing',
					'label'   => __( 'Processing message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="processing" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_processing]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', WHATSIPLUS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_completed',
					'label'   => __( 'Completed message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="completed" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_completed]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', WHATSIPLUS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_cancelled',
					'label'   => __( 'Cancelled message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="cancelled" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_cancelled]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', WHATSIPLUS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_refunded',
					'label'   => __( 'Refunded message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="refunded" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_refunded]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', WHATSIPLUS_TEXT_DOMAIN )
				),
				array(
					'name'    => 'whatsiplus_woocommerce_sms_template_failed',
					'label'   => __( 'Failed message', WHATSIPLUS_TEXT_DOMAIN ),
					'desc'    => 'Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="failed" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_failed]" class="button button-secondary">Keywords</button>',
					'type'    => 'textarea',
					'rows'    => '8',
					'cols'    => '500',
					'css'     => 'min-width:350px;',
					'default' => __( '[shop_name] : Thank you for purchasing. Your order ([order_id]) is now [order_status].', WHATSIPLUS_TEXT_DOMAIN )
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
        // $log->add("Whatsiplus", print_r($custom_wc_statuses, 1));
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
                        foreach($processed_wc_statuses as $processed_key => $processed_value) {
                            if( ! array_key_exists($processed_key, $setting_fields[$field][$i]['options']) ) {
                                $setting_fields[$field][$i]['options'][$processed_key] = " {$processed_value}";
                                if($field == 'whatsiplus_customer_setting') {
                                    $setting_fields[$field][] = array(
                                        'name'    => "whatsiplus_woocommerce_sms_template_{$processed_key}",
                                        'label'   => __( "{$processed_value} Customer message", WHATSIPLUS_TEXT_DOMAIN ),
                                        'desc'    => sprintf('Customize your message with <button type="button" id="whatsi_sms[open-keywords]" data-attr-type="default" data-attr-target="whatsiplus_customer_setting[whatsiplus_woocommerce_sms_template_%s]" class="button button-secondary">Keywords</button>', $processed_key),
                                        'type'    => 'textarea',
                                        'rows'    => '8',
                                        'cols'    => '500',
                                        'css'     => 'min-width:350px;',
                                        'default' => __( "Your {$processed_value} message template", WHATSIPLUS_TEXT_DOMAIN )
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
		echo '<input type="hidden" value="' . join(",", $this->get_additional_billing_fields()) . '" id="whatsiplus_new_billing_field" />';

		echo '</div>';
	}

	/**
	 * Get all the pages
	 *
	 * @return array page names with key value pairs
	 */
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
            // this is because new users.
            // no settings to anything.
            return;
        }

        $default_setting = [
            'billing-recipient' => 'billing-recipient'
        ];

        $option_setting = whatsiplus_get_options("whatsiplus_woocommerce_send_sms_to", "whatsiplus_customer_setting");

        // no settings, usually after update plugin.
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

        // var_dump($option_setting);
        // var_dump($send_to_billing_recipient);
        // var_dump($send_to_shipping_recipient);
        if( empty($send_to_billing_recipient) && empty($send_to_shipping_recipient) ) {
            return whatsiplus_update_options("whatsiplus_woocommerce_send_sms_to", $default_setting, "whatsiplus_customer_setting");
        }
    }

    public function check_domain_reachability()
    {
        /*
        try {
            $this->log->add("Whatsiplus", "Running scheduled checking domain task.");
            $response_code = wp_remote_retrieve_response_code( wp_remote_get("https://rest.whatsiapi.com/rest/2/account/balance") );
            // successfully reached our domain
            if($response_code === 400) {
                update_option("whatsiplus_domain_reachable", true);
                $this->log->add("Whatsiplus", "Domain is reachable. Will be using domain.");
            }
            else {
                $this->log->add("Whatsiplus", "Exception thrown. Domain not reachable.");
                throw new Exception("Domain not reachable.");
            }
        } catch (Exception $e) {
            $this->log->add("Whatsiplus", "Domain not reachable. Using IP address");
            $this->log->add("Whatsiplus", "err msg: {$e->getMessage()}");
            update_option("whatsiplus_domain_reachable", false);
        }
        */
    }

    public function schedule_check_domain()
    {
        $latest_plugin_version = get_plugin_data(WHATSIPLUS_PLUGIN_DIR . "whatsiplus-woocommerce.php")['Version'];
        $current_plugin_version = get_option("whatsiplus_plugin_version");

        if(!empty($current_plugin_version)) {
            // if cur < lat = -1
            // if cur === lat = 0
            // if cur > lat = 1
            if(version_compare( $current_plugin_version, $latest_plugin_version ) < 0) {
                $this->log->add("Whatsiplus", "current plugin version: {$current_plugin_version}.");
                $this->log->add("Whatsiplus", "latest plugin version: {$latest_plugin_version}.");
                as_unschedule_all_actions("whatsiplus_check_domain");
                $this->log->add("Whatsiplus", "Successfully unscheduled domain reachability for initialization.");
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
            echo ('<p style="color: red;"><b>'. esc_html($acc_balance). '</b></p>'.
                    "<h3>To access a free API KEY and unlock all the plugin's features, please follow the link provided below.<h3>".
                    '<h2><a href="https://whatsiplus.com/go?url=apikey" target="_blank">Get an API key</a></h2>'
                ); 
        else{
            if($acc_balance === "Connected to WhatsApp")
            {
                $acc_balance = "Status: ". $acc_balance;
                echo '<p style="color: green;"><b>API KEY is Valid</b></p><p style="color: green;"><b>'. esc_html($acc_balance). '</b></p>';
                echo '<p style="color: green;"><b>'. esc_html($wNumber). '</b></p>';
                echo '<p>'.'Defualt country code: '.esc_html($countryCode). '</p>';
                echo '<p><a href="https://whatsiplus.com/go?url=apikey" target="_blank">Manage your service</a><p>';
            }
            else if($acc_balance === "Not connected to WhatsApp")
            {
                $acc_balance="API KEY is valid but status: ".$acc_balance;
                echo '<p style="color: red;"><b>'. esc_html($acc_balance). '</b></p>';
                echo "<h3>To link the service with WhatsApp, please click on the provided link below.<h3>";
                echo '<h2><a href="https://whatsiplus.com/go?url=apikey" target="_blank">Whatsiplus</a></h2>';
                echo '<p>'.'Defualt country code: '.esc_html($countryCode). '</p>';
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

    public function get_user_ip() {
        return Utils::curl_get_file_contents("https://ipecho.net/plain");
    }

    public function get_country_code_from_ip($ip_address)
    {
        $api_url = "https://www.iplocate.io/api/lookup/{$ip_address}";
        try {
            $c = curl_init();
            curl_setopt( $c , CURLOPT_URL , $api_url);
            curl_setopt( $c , CURLOPT_USERAGENT, "Mozilla/5.0 (Linux Centos 7;) Chrome/74.0.3729.169 Safari/537.36");
            curl_setopt( $c , CURLOPT_RETURNTRANSFER, true);
            curl_setopt( $c , CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $c , CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt( $c , CURLOPT_TIMEOUT, 10000); // 10 sec
            $response = json_decode(curl_exec($c), 1);
            curl_close($c);


            if(!empty($response['error'])) {
                $this->log->add("Whatsiplus", "Unable to get country code for IP address: {$ip_address}");
                $this->log->add("Whatsiplus", "Error from API request: {$response['error']}");
                return ''; // ''
            }

            $country_code = $response['country_code'];

            //$this->log->add("Whatsiplus", "Resolved {$ip_address} to country code: {$country_code}");
            return $country_code;

        } catch (Exception $e) {
            $this->log->add("Whatsiplus", "Error occured. Failed to get country code from ip address: {$ip_address}");
            $this->log->add("Whatsiplus", print_r($e->getMessage(), 1));
            return '';
        }
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
