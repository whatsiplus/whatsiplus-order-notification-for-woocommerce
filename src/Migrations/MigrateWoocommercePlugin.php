<?php

namespace WhatsiAPI_WC\Migrations;

class MigrateWoocommercePlugin {
    public static function migrate()
    {
        $setting_ids_to_iterate = ["whatsiplus_setting", "whatsiplus_admin_setting", "whatsiplus_customer_setting", "whatsiplus_multivendor_setting"];

        foreach($setting_ids_to_iterate as $setting_id) {
            // check if order notifciation plugin setting is set
            $setting = get_option($setting_id);
            if(empty($setting)) {
                // check whatsiapi-sendsms
                $sendsms_setting_id = preg_replace("/whatsiplus_/", "whatsiapi_", $setting_id, 1);
                $sendsms_setting = get_option($sendsms_setting_id);
                if(!empty($sendsms_setting)) {
                    // if user have whatsiapi-sendsms setting, we overwrite it to order notification
                    $new_option = [];
                    foreach($sendsms_setting as $key => $value) {
                        $new_key = preg_replace("/whatsiapi_/", "whatsiplus_woocommerce_", $key, 1);
                        $new_option[$new_key] = $value;

                    }
                    update_option($setting_id, $new_option);
                }
            }
        }


    }
}