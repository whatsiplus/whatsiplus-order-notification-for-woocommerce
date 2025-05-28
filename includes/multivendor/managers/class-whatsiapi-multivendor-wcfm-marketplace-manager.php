<?php
/**
 * Created by VsCode.
 * User: whatsi
 * Date: 2/18/2019
 * Time: 5:46 PM.
 */

class Whatsiapi_Multivendor_WCFM_Marketplace_Manager extends Whatsiplus_Abstract_Multivendor {
	public function __construct( Whatsiplus_WooCommerce_Logger $log = null ) {
		parent::__construct( $log );
	}

	public function setup_mobile_number_setting_field( $user ) {
		//not supported due to default available
	}

	public function save_mobile_number_setting( $user_id ) {
		//not supported due to default available
	}

	public function get_vendor_mobile_number_from_vendor_data( $vendor_data ) {
		if ( is_array( $vendor_data ) && isset( $vendor_data['vendor_profile']['phone'] ) ) {
			return $vendor_data['vendor_profile']['phone'];
		}
		$this->log->add( 'WhatsiPLUS_Multivendor', 'Invalid vendor data or missing phone: ' . print_r( $vendor_data, true ) );
		return '';
	}

	public function get_vendor_country_from_vendor_data($vendor_data){
		if ( is_array( $vendor_data ) && isset( $vendor_data['vendor_profile']['address']['country'] ) ) {
			return $vendor_data['vendor_profile']['address']['country'];
		}
		$this->log->add( 'WhatsiPLUS_Multivendor', 'Invalid vendor data or missing country: ' . print_r( $vendor_data, true ) );
		return '';
	}

	public function get_vendor_shop_name_from_vendor_data( $vendor_data ) {
		if ( is_array( $vendor_data ) && isset( $vendor_data['vendor_profile']['store_name'] ) ) {
			return $vendor_data['vendor_profile']['store_name'];
		}
		$this->log->add( 'WhatsiPLUS_Multivendor', 'Invalid vendor data or missing store name: ' . print_r( $vendor_data, true ) );
		return '';
	}

	public function get_vendor_id_from_item( WC_Order_Item $item ) {
		return $item->get_meta( '_vendor_id' );
	}

	public function get_vendor_profile_from_item( WC_Order_Item $item ) {
		$vendor_id = $this->get_vendor_id_from_item( $item );
		if ( $vendor_id ) {
			return get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
		}
		$this->log->add( 'WhatsiPLUS_Multivendor', 'Invalid vendor ID from item: ' . print_r( $item, true ) );
		return array();
	}

	public function get_vendor_data_list_from_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			$this->log->add( 'WhatsiPLUS_Multivendor', 'Invalid order ID: ' . print_r( $order_id, true ) );
			return array();
		}

		$items = $order->get_items();
		$vendor_data_list = array();

		foreach ( $items as $item ) {
			$vendor_data_list[] = array(
				'item'           => $item,
				'vendor_user_id' => $this->get_vendor_id_from_item( $item ),
				'vendor_profile' => $this->get_vendor_profile_from_item( $item )
			);
		}

		$this->log->add( 'WhatsiPLUS_Multivendor', 'Raw data: ' . wp_json_encode( $vendor_data_list ) );

		return $this->perform_grouping( $vendor_data_list );
	}
}
