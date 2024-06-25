<?php
/**
 * Created by VsCode.
 * User: whatsi
 * Date: 2/18/2019
 * Time: 3:25 PM.
 */

class Whatsiplus_Multivendor_Notification extends Whatsiplus_WooCommerce_Notification {
	/* @var Abstract_Whatsiplus_Multivendor $whatsiplus_multivendor */
	private $whatsiplus_multivendor;
	private $medium;
	private $defaultHooks = array(
		'pending',
		'on-hold',
		'processing',
		'completed',
		'cancelled',
		'refunded',
		'failed'
	);

	public function __construct( $medium = 'wordpress_woocommerce_multivendor', $whatsiplus_multivendor = null, Whatsiplus_WooCommerce_Logger $log = null ) {
		parent::__construct( $log );
		if ( $whatsiplus_multivendor === null ) {
			$whatsiplus_multivendor = Whatsiplus_Multivendor_Factory::make();
		}
		$this->whatsiplus_multivendor = $whatsiplus_multivendor;
		$this->medium                = $medium;
	}

    public function send_sms_woocommerce_vendor_custom_order_status($order_id, $old_status, $new_status)
    {
        $default_statuses = [
            'pending',
            'processing',
            'on-hold',
            'completed',
            'cancelled',
            'refunded',
            'failed',
            'checkout-draft'
        ];

        if(in_array($new_status, $default_statuses)) { return; }
        $this->send_to_vendors( $order_id, $new_status );

    }

	public function send_to_vendors( $order_id, $status ) {
		if ( whatsiplus_get_options( 'whatsiplus_multivendor_vendor_send_sms', 'whatsiplus_multivendor_setting', 'off' ) === 'off' ) {
			return;
		}
		$send_sms_flag = true;

		//Checking if multivendor is "wc_marketplace" but do not have suborder
		if (Whatsiplus_Multivendor_Factory::$activatedPlugin == "wc_marketplace")
		{
			// if order id is not vendor order
			$is_suborder = (get_wcmp_suborders( $order_id, false, false) ? true : false);
			if( $is_suborder ) {
				//Do not send sms when it's sub order
				$send_sms_flag = false;
			}
		}

		if($send_sms_flag){
			// check for specific hook if sms should be send
			$activatedHooks = whatsiplus_get_options( 'whatsiplus_multivendor_vendor_send_sms_on', 'whatsiplus_multivendor_setting', $this->defaultHooks );
			if ( ! in_array( $status, $activatedHooks ) ) {
				$this->log->add( 'Whatsiplus', 'not sending, current hook: ["' . $status . '"] activated hooks: ' . wp_json_encode( $activatedHooks ) );
				return;
			}

			$this->log->add( 'Whatsiplus', '3rd party plugin setting: ' . whatsiplus_get_options( 'whatsiplus_multivendor_selected_plugin', 'whatsiplus_multivendor_setting', 'auto' ) );
			if ( ! $this->whatsiplus_multivendor ) {
				$this->log->add( 'Whatsiplus', 'error: no multivendor plugin detected' );
				return;
			}
			$this->log->add( 'Whatsiplus', 'activated plugin: ' . Whatsiplus_Multivendor_Factory::$activatedPlugin );

			$order_details = wc_get_order( $order_id );
			$message       = whatsiplus_get_options( 'whatsiplus_multivendor_vendor_sms_template', 'whatsiplus_multivendor_setting', '' );
			//Get default country v1.1.17
			$default_country = whatsiplus_get_options('whatsiplus_woocommerce_country_code', 'whatsiplus_setting', '' );

			$vendor_data_list = $this->whatsiplus_multivendor->get_vendor_data_list_from_order( $order_id );
			if ( ! $vendor_data_list ) {
			    $this->log->add( 'Whatsiplus', "Failed to retrieve vendor data list from order id. Exiting..." );
				return;
			}

			foreach ( $vendor_data_list as $phone_number => $vendor_datas ) {
				$phone_number = $this->phone_number_processing( $phone_number );
				$this->log->add( 'Whatsiplus', 'Original template: ' . $message );
				$processed_msg = $this->replace_vendor_order_keyword( $message, $order_details, $vendor_datas );
				//Country Code v1.1.17
				$vendor_country = $this->whatsiplus_multivendor->get_vendor_country_from_vendor_data( $vendor_datas );
                $this->log->add( 'Whatsiplus', "Vendor country: {$vendor_country}" );
                $this->log->add( 'Whatsiplus', "Default country: {$default_country}" );
				if(empty($vendor_country)){
                    $vendor_country = $default_country;
                    $this->log->add( 'Whatsiplus', "Country field being used: Default Country" );
				} else {
                    $this->log->add( 'Whatsiplus', "Country field being used: Vendor Country" );
                }

				$phone_with_country_code = $this->check_and_get_phone_number($phone_number, $vendor_country);
				if ( $phone_with_country_code !== false ) {
					$this->log->add( 'Whatsiplus', 'Vendor\'s phone number (' . $phone_number . ') in country (' . $vendor_country . ') converted to ' . $phone_with_country_code );
				}else {
					$phone_with_country_code = $phone_number;
				}
				WhatsiPLUS_SendSMS_Sms::send_sms('', $phone_with_country_code, $processed_msg);
			}
		}
	}

	public function replace_vendor_order_keyword( $message, WC_Order $order_details, $vendor_datas ) {
		$search  = array(
			'[shop_name]',
			'[shop_email]',
			'[shop_url]',
			'[vendor_shop_name]',
			'[order_id]',
			'[order_currency]',
			'[order_amount]',
			'[order_status]',
            '[order_latest_cust_note]',
			'[order_product]',
			'[order_product_with_qty]',
			'[billing_first_name]',
			'[billing_last_name]',
			'[billing_phone]',
			'[billing_email]',
			'[billing_company]',
			'[billing_address]',
			'[billing_country]',
			'[billing_city]',
			'[billing_state]',
			'[billing_postcode]',
			'[payment_method]'
		);
		$replace = array(
			get_bloginfo( 'name' ),
			get_bloginfo( 'admin_email' ),
			get_bloginfo( 'url' ),
			$this->whatsiplus_multivendor->get_vendor_shop_name_from_vendor_data( $vendor_datas ),
			$order_details->get_order_number(),
			$order_details->get_currency(),
			$vendor_datas['total_amount_for_vendor'],
			ucfirst( $order_details->get_status() ),
            $order_details->get_customer_order_notes()[0],
			$vendor_datas['item'],
			$vendor_datas['product_with_qty'],
			$order_details->get_billing_first_name(),
			$order_details->get_billing_last_name(),
			$order_details->get_billing_phone(),
			$order_details->get_billing_email(),
			$order_details->get_billing_company(),
			$order_details->get_billing_address_1(),
			$order_details->get_billing_country(),
			$order_details->get_billing_city(),
			$order_details->get_billing_state(),
			$order_details->get_billing_postcode(),
			$order_details->get_payment_method()
		);
		$message = str_replace( $search, $replace, $message, $total_replaced );

		// 2020-07-04 - Support additional billing field for Multivendor
		$additional_billing_fields_array = $this->get_additional_billing_fields();
		foreach ( $additional_billing_fields_array as $field ) {
			$post_data = get_post_meta( $order_details->get_order_number(), $field, true );
			$message   = str_replace( '[' . $field . ']', $post_data, $message );
		}

		$this->log->add( 'Whatsiplus', "Total replaced keyword: $total_replaced" );

		return $message;
	}

	// 2020-07-04 - Support additional billing field for Multivendor
	// Copied from class-whatsiplus-woocommerce-notification.php
	protected function get_additional_billing_fields() {
		$default_billing_fields = array(
			'billing_first_name',
			'billing_last_name',
			'billing_company',
			'billing_address', // added specially for Multivendor
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
}
