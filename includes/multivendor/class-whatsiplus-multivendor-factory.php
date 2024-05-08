<?php
/**
 * Created by VsCode.
 * User: whatsi
 * Date: 4/8/2019
 * Time: 10:45 AM.
 */

class Whatsiplus_Multivendor_Factory {
	/* @var Whatsiplus_WooCommerce_Logger $logger */
	public $log;
	public static $activatedPlugin = '';

	protected $manager_dir = 'managers';
	protected $mapper = [
		'product_vendors'  => [
			'file'  => 'class-whatsiapi-multivendor-woocommerce-product-vendors-manager',
			'class' => 'Whatsiapi_Multivendor_WooCommerce_Product_Vendors_Manager'
		],
		'dokan'            => [
			'file'  => 'class-whatsiapi-multivendor-dokan-manager',
			'class' => 'Whatsiapi_Multivendor_Dokan_Manager'
		],
		'wc_vendors'       => [
			'file'  => 'class-whatsiapi-multivendor-wc-vendors-manager',
			'class' => 'Whatsiapi_Multivendor_Wc_Vendors_Manager'
		],
		'wc_marketplace'   => [
			'file'  => 'class-whatsiapi-multivendor-wc-marketplace-manager',
			'class' => 'Whatsiapi_Multivendor_WC_Marketplace_Manager'
		],
		'wcfm_marketplace' => [
			'file'  => 'class-whatsiapi-multivendor-wcfm-marketplace-manager',
			'class' => 'Whatsiapi_Multivendor_WCFM_Marketplace_Manager'
		],
		'yith'             => [
			'file'  => 'class-whatsiapi-multivendor-yith-manager',
			'class' => 'Whatsiapi_Multivendor_Yith_Manager'
		]
	];

	private function __construct( Whatsiplus_WooCommerce_Logger $log = null ) {
		if ( $log === null ) {
			$log = new Whatsiplus_WooCommerce_Logger();
		}

		$this->log = $log;
	}

	/**
	 * @return Abstract_Whatsiplus_Multivendor|bool
	 */
	public static function make() {
		$factory = new self();

		return $factory->getMultivendorManager();
	}

	protected function getMultivendorManager() {
		$selected_setting = whatsiplus_get_options( 'whatsiplus_multivendor_selected_plugin', 'whatsiplus_multivendor_setting', 'auto' );

		if ( $selected_setting === 'auto' ) {
			//attempt to detect plugin
			$is_plugin_detected = $this->detectPlugin();
			if ( ! $is_plugin_detected ) {
				return false;
			}
            // $this->log->add("Whatsiplus", sprintf("auto detected multivendor plugin: %s", self::$activatedPlugin));
		} else {
			self::$activatedPlugin = $selected_setting;
		}

		//required the file
		require_once __DIR__ . '/' . $this->manager_dir . '/' . $this->mapper[ self::$activatedPlugin ]['file'] . '.php';

		return new $this->mapper[self::$activatedPlugin]['class'];
	}

	protected function detectPlugin() {
		if ( is_plugin_active( 'woocommerce-product-vendors/woocommerce-product-vendors.php' ) ) {
			self::$activatedPlugin = 'product_vendors';
			return true;
		}

		if ( is_plugin_active( 'dokan-lite/dokan.php' ) || is_plugin_active( 'dokan-pro/dokan.php' ) ) {
			self::$activatedPlugin = 'dokan';

			return true;
		}

		if ( is_plugin_active( 'wc-vendors/class-wc-vendors.php' ) ) {
			self::$activatedPlugin = 'wc_vendors';

			return true;
		}

		if ( is_plugin_active( 'dc-woocommerce-multi-vendor/dc_product_vendor.php' ) ) {
			self::$activatedPlugin = 'wc_marketplace';

			return true;
		}

		if ( is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' ) && is_plugin_active( 'wc-frontend-manager/wc_frontend_manager.php' ) ) {
			self::$activatedPlugin = 'wcfm_marketplace';

			return true;
		}

		if ( is_plugin_active( 'yith-woocommerce-product-vendors/init.php' ) ) {
			self::$activatedPlugin = 'yith';

			return true;
		}

		return false;
	}
}
