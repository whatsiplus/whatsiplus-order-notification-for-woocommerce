<?php

/*
Plugin Name: Whatsiplus Order Notification for WooCommerce
Plugin URI:  https://whatsiplus.com
Description: Send WhatsApp notifications to WordPress and WooCommerce users
Version:     1.0.0
Author:      whatsiplus
Author URI:  https://whatsiplus.com
License:     GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Text Domain: whatsiplus-order-notification-for-woocommerce
*/

use WhatsiAPI_WC\Loader;

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'whatsi_fs' ) ) {
    // Create a helper function for easy SDK access.
    
    // Init Freemius.
   
    // Signal that SDK was initiated.
    

}

define("WHATSIPLUS_PLUGIN_URL", plugin_dir_url(__FILE__));
define("WHATSIPLUS_PLUGIN_DIR", plugin_dir_path(__FILE__));
define("WHATSIPLUS_INC_DIR", WHATSIPLUS_PLUGIN_DIR . "includes/");
define("WHATSIPLUS_ADMIN_VIEW", WHATSIPLUS_PLUGIN_DIR . "admin/");
define("WHATSIPLUS_TEXT_DOMAIN", "whatsiplus-woocommerce");
define("WHATSI_DB_TABLE_NAME", "whatsiplus_wc_send_sms_outbox");

require_once WHATSIPLUS_PLUGIN_DIR . 'lib/action-scheduler/action-scheduler.php';

add_action( 'plugins_loaded', 'whatsiplus_woocommerce_init', PHP_INT_MAX );

function whatsiplus_install() {

    include_once WHATSIPLUS_PLUGIN_DIR . '/install.php';
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $create_sms_send );
}

register_activation_hook(__FILE__, 'whatsiplus_install');

function whatsiplus_cleanup() {
    delete_option("whatsiplus_plugin_version");
    delete_option("whatsiplus_domain_reachable");
}

register_deactivation_hook(__FILE__, 'whatsiplus_cleanup');

function whatsiplus_woocommerce_init() {
    require_once(plugin_dir_path(__FILE__) . '/vendor/autoload.php');
	require_once ABSPATH . '/wp-admin/includes/plugin.php';
	require_once ABSPATH . '/wp-includes/pluggable.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'interfaces/Whatsiplus_PluginInterface.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/contracts/class-whatsiplus-register-interface.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/class-whatsiplus-helper.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/class-whatsiplus-woocommerce-frontend-scripts.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/class-whatsiplus-woocommerce-hook.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/class-whatsiplus-woocommerce-register.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/class-whatsiplus-woocommerce-logger.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/class-whatsiplus-woocommerce-notification.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/class-whatsiplus-woocommerce-widget.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/class-whatsiplus-download-log.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/class-whatsiplus-sendsms.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'includes/multivendor/class-whatsiplus-multivendor.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'lib/WhatsiPLUS.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'lib/class.settings-api.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'admin/class-whatsiplus-woocommerce-setting.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'admin/sendsms.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'admin/smsoutbox.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'admin/automation.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'admin/logs.php';
	require_once WHATSIPLUS_PLUGIN_DIR . 'admin/help.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiS2Member.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiARMemberLite.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiARMemberPremium.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiMemberPress.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiMemberMouse.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiSimpleMembership.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiRestaurantReservation.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiQuickRestaurantReservation.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiBookIt.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiLatePoint.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiFATService.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiWpERP.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiJetpackCRM.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiFluentCRM.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiGroundhoggCRM.php';
    require_once WHATSIPLUS_PLUGIN_DIR . 'includes/plugins/WhatsiSupportedPlugin.php';

    // load all Forms integrations
    Loader::load();

	//create notification instance
	$whatsiplus_notification = new Whatsiplus_WooCommerce_Notification();

	//register hooks and settings
	$registerInstance = new Whatsiplus_WooCommerce_Register();
	$registerInstance->add( new Whatsiplus_WooCommerce_Hook( $whatsiplus_notification ) )
	                 ->add( new Whatsiplus_WooCommerce_Setting() )
	                 ->add( new Whatsiplus_WooCommerce_Widget() )
	                 ->add( new Whatsiplus_WooCommerce_Frontend_Scripts() )
	                 ->add( new Whatsiplus_Multivendor() )
	                 ->add( new Whatsiplus_Download_log() )
	                 ->add( new WhatsiPLUS_SendSMS_View() )
	                 ->add( new WhatsiPLUS_Automation_View() )
	                 ->add( new WhatsiPLUS_SMSOutbox_View() )
	                 ->add( new WhatsiPLUS_Logs_View() )
	                 ->add( new WhatsiPLUS_Help_View() )
	                 ->load();
}

