<?php
/**
 * Created by VsCode.
 * User: whatsi
 * Date: 4/10/2019
 * Time: 2:47 PM.
 */

class Whatsiplus_Multivendor implements Whatsiplus_Register_Interface {
	public function register() {
		$this->required_files();
		//create notification instance
		$whatsiplus_notification = new Whatsiplus_Multivendor_Notification( 'Wordpress-Woocommerce-Multivendor-Extension-' . Whatsiplus_Multivendor_Factory::$activatedPlugin );

		$registerInstance = new Whatsiplus_WooCommerce_Register();
		$registerInstance->add( new Whatsiplus_Multivendor_Hook( $whatsiplus_notification ) )
		                 ->add( new Whatsiplus_Multivendor_Setting() )
		                 ->load();
	}

	protected function required_files() {
		require_once __DIR__ . '/admin/class-whatsiplus-multivendor-setting.php';
		require_once __DIR__ . '/abstract/abstract-whatsiplus-multivendor.php';
		require_once __DIR__ . '/contracts/class-whatsiplus-multivendor-interface.php';
		require_once __DIR__ . '/class-whatsiplus-multivendor-factory.php';
		require_once __DIR__ . '/class-whatsiplus-multivendor-hook.php';
		require_once __DIR__ . '/class-whatsiplus-multivendor-notification.php';
	}
}
