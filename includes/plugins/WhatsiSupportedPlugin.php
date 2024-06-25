<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
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

class WhatsiSupportedPlugin {

    public function __construct() {}

    public static function get_activated_plugins()
    {
        $supported_plugins = array();
        if(WhatsiS2Member::plugin_activated())
            $supported_plugins[] = WhatsiS2Member::class;
        if(WhatsiARMemberLite::plugin_activated())
            $supported_plugins[] = WhatsiARMemberLite::class;
        if(WhatsiARMemberPremium::plugin_activated())
            $supported_plugins[] = WhatsiARMemberPremium::class;
        if(WhatsiMemberPress::plugin_activated())
            $supported_plugins[] = WhatsiMemberPress::class;
        if(WhatsiMemberMouse::plugin_activated())
            $supported_plugins[] = WhatsiMemberMouse::class;
        if(WhatsiSimpleMembership::plugin_activated())
            $supported_plugins[] = WhatsiSimpleMembership::class;

        if(WhatsiRestaurantReservation::plugin_activated())
            $supported_plugins[] = WhatsiRestaurantReservation::class;
        if(WhatsiQuickRestaurantReservation::plugin_activated())
        $supported_plugins[] = WhatsiQuickRestaurantReservation::class;
        if(WhatsiBookIt::plugin_activated())
            $supported_plugins[] = WhatsiBookIt::class;
        if(WhatsiLatePoint::plugin_activated())
            $supported_plugins[] = WhatsiLatePoint::class;
        if(WhatsiFATService::plugin_activated())
            $supported_plugins[] = WhatsiFATService::class;

        if(WhatsiWpERP::plugin_activated())
            $supported_plugins[] = WhatsiWpERP::class;
        if(WhatsiJetpackCRM::plugin_activated())
            $supported_plugins[] = WhatsiJetpackCRM::class;
        if(WhatsiFluentCRM::plugin_activated())
            $supported_plugins[] = WhatsiFluentCRM::class;
        if(WhatsiGroundhoggCRM::plugin_activated())
            $supported_plugins[] = WhatsiGroundhoggCRM::class;

        return $supported_plugins;
    }


}
