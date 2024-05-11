<?php

class WhatsiARMemberPremium implements Whatsiplus_PluginInterface, Whatsiplus_Register_Interface {
    /*
    Plugin Name: ARMember – Membership Plugin, Content Restriction, Member Levels, User Profile & User signup
    Plugin Link: https://codecanyon.net/item/armember-complete-wordpress-membership-system/17785056
    */

    public static $plugin_identifier = 'armember';
    private $plugin_name;
    private $plugin_medium;
    private $hook_action;
    private $log;
    private $option_id;

    public function __construct() {
        $this->log = new Whatsiplus_WooCommerce_Logger();
        $this->option_id = "whatsiplus_{$this::$plugin_identifier}";
        $this->plugin_name = 'AR Member Premium';
        $this->plugin_medium = 'wp_' . str_replace( ' ', '_', strtolower($this->plugin_name));
        $this->hook_action = "whatsiplus_send_reminder_{$this::$plugin_identifier}";
    }

    public static function plugin_activated()
    {
        $log = new Whatsiplus_WooCommerce_Logger();
        if( ! is_plugin_active(sprintf("%s/%s.php", self::$plugin_identifier, self::$plugin_identifier))) { return false; }
        try {
            require_once MEMBERSHIP_CLASSES_DIR . "/class.arm_subscription_plans.php";
            return true;
        } catch (Exception $e) {
            $log->add("Whatsiplus", "Failed to import /class.arm_subscription_plans.php");
            return false;
        }
    }

    public function register()
    {
        add_action( 'arm_cancel_subscription_gateway_action', array( $this, 'send_sms_on_status_cancel_subscription'), 10, 2);
        add_action( 'arm_after_user_plan_change',             array( $this, 'send_sms_on_status_after_user_plan_change'), 10, 2);
        add_action( 'arm_after_user_plan_change_by_admin',    array( $this, 'send_sms_on_status_after_user_plan_change'), 10, 2);
        add_action( 'arm_after_user_plan_renew',              array( $this, 'send_sms_on_status_after_user_plan_renew'), 10, 2);
        add_action( 'arm_after_user_plan_renew_by_admin',     array( $this, 'send_sms_on_status_after_user_plan_renew'), 10, 2);
        add_action( $this->hook_action,                       array( $this, 'send_sms_reminder'), 10, 3);
    }

    public function get_option_id()
    {
        return $this->option_id;
    }

    public function get_setting_section_data()
    {
        return array(
            'id'    => $this->get_option_id(),
            'title' => __( 'AR Member Premium', 'WHATSIPLUS_TEXT_DOMAIN' ),
        );
    }

    public function get_setting_field_data()
    {
        $setting_fields = array(
			$this->get_enable_notification_fields(),
			$this->get_send_from_fields(),
			$this->get_send_on_fields(),
		);
        foreach($this->get_reminder_fields() as $reminder) {
            $setting_fields[] = $reminder;
        }
        foreach($this->get_sms_reminder_template_fields() as $sms_reminder) {
            $setting_fields[] = $sms_reminder;
        }
        foreach($this->get_sms_template_fields() as $sms_templates) {
            $setting_fields[] = $sms_templates;
        }
        return $setting_fields;
    }

    private function get_enable_notification_fields() {
        return array(
            'name'    => 'whatsiplus_automation_enable_notification',
            'label'   => __( 'Enable WhatsApp Notifications', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'desc'    => ' ' . __( 'Enable', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'type'    => 'checkbox',
            'default' => 'off'
        );
    }

    private function get_send_from_fields() {
        return array(
            'name'  => 'whatsiplus_automation_send_from',
            'label' => __( 'Send from', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'desc'  => __( 'To display in the Message Outbox section of the plugin', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'type'  => 'text',
        );
    }

    private function get_send_on_fields() {
        return array(
            'name'    => 'whatsiplus_automation_send_on',
            'label'   => __( 'Send notification on', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'desc'    => __( 'Choose when to send the notification message to your customer', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'type'    => 'multicheck',
            'options' => array(
                'cancel_subscription'     => 'Cancel subscription',
                'after_user_plan_change'  => 'After user plan changed',
                'after_user_plan_renew'   => 'After user plan renewed',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'whatsiplus_automation_sms_template_cancel_subscription',
                'label'   => __( 'Cancel subscription message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="cancel_subscription" data-attr-target="%1$s[whatsiplus_automation_sms_template_cancel_subscription]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [name] subscription has been cancelled', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
            array(
                'name'    => 'whatsiplus_automation_sms_template_after_user_plan_change',
                'label'   => __( 'After user plan changed message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="after_user_plan_change" data-attr-target="%1$s[whatsiplus_automation_sms_template_after_user_plan_change]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your subscription has been changed to [name]', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
            array(
                'name'    => 'whatsiplus_automation_sms_template_after_user_plan_renew',
                'label'   => __( 'After user plan renewed message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="after_user_plan_renew" data-attr-target="%1$s[whatsiplus_automation_sms_template_after_user_plan_renew]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [name] subscription has been renewed at [amount]', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
        );
    }

    public function get_plugin_settings($with_identifier = false)
    {
        $settings = array(
            "whatsiplus_automation_enable_notification"                   => whatsiplus_get_options("whatsiplus_automation_enable_notification", $this->get_option_id()),
            "whatsiplus_send_from"                                        => whatsiplus_get_options('whatsiplus_automation_send_from', $this->get_option_id()),
            "whatsiplus_automation_send_on"                               => whatsiplus_get_options("whatsiplus_automation_send_on", $this->get_option_id()),
            "whatsiplus_automation_reminder"                              => whatsiplus_get_options("whatsiplus_automation_reminder", $this->get_option_id()),
            "whatsiplus_automation_reminder_custom_time"                  => whatsiplus_get_options("whatsiplus_automation_reminder_custom_time", $this->get_option_id()),
            "whatsiplus_automation_sms_template_rem_1"                    => whatsiplus_get_options("whatsiplus_automation_sms_template_rem_1", $this->get_option_id()),
            "whatsiplus_automation_sms_template_rem_2"                    => whatsiplus_get_options("whatsiplus_automation_sms_template_rem_2", $this->get_option_id()),
            "whatsiplus_automation_sms_template_rem_3"                    => whatsiplus_get_options("whatsiplus_automation_sms_template_rem_3", $this->get_option_id()),
            "whatsiplus_automation_sms_template_custom"                   => whatsiplus_get_options("whatsiplus_automation_sms_template_custom", $this->get_option_id()),
            "whatsiplus_automation_sms_template_cancel_subscription"      => whatsiplus_get_options("whatsiplus_automation_sms_template_cancel_subscription", $this->get_option_id()),
            "whatsiplus_automation_sms_template_after_user_plan_change"   => whatsiplus_get_options("whatsiplus_automation_sms_template_after_user_plan_change", $this->get_option_id()),
            "whatsiplus_automation_sms_template_after_user_plan_renew"    => whatsiplus_get_options("whatsiplus_automation_sms_template_after_user_plan_renew", $this->get_option_id()),
        );

        if ($with_identifier) {
            return array(
                self::$plugin_identifier => $settings,
            );
        }

        return $settings;
    }

    private function get_reminder_fields() {
    return array(
        array(
            'name'    => 'whatsiplus_automation_reminder',
            'label'   => __( 'Send reminder to renew membership', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'desc'    => __( 'Description for the reminder field', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'type'    => 'multicheck',
            'options' => array(
                'rem_1'  => __( '1 day before membership expiry', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'rem_2'  => __( '2 days before membership expiry', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'rem_3'  => __( '3 days before membership expiry', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'custom' => __( 'Custom time before membership expiry', 'WHATSIPLUS_TEXT_DOMAIN' ),
            )
        ),
        array(
            'name'  => 'whatsiplus_automation_reminder_custom_time',
            'label' => __( 'Custom Reminder Time', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'desc'  => __( 'Enter the custom time you want to remind your customer before membership expires (in minutes). Choose when to send a reminder message to your customer. Please set your timezone in settings. You must set up a cron job <a href="https://whatsiplus.com/go?url=cron" target="_blank">here</a>.', 'WHATSIPLUS_TEXT_DOMAIN' ),
            'type'  => 'number',
        ),
    );
}


    private function get_sms_reminder_template_fields() {
        return array(
            array(
                'name'    => 'whatsiplus_automation_sms_template_rem_1',
                'label'   => __( '1 day reminder message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[whatsiplus_automation_sms_template_rem_1]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [name] subscription will expire in 1 Day, renew now to keep access.', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
            array(
                'name'    => 'whatsiplus_automation_sms_template_rem_2',
                'label'   => __( '2 days reminder message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[whatsiplus_automation_sms_template_rem_2]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [name] subscription will expire in 2 Days, renew now to keep access.', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
            array(
                'name'    => 'whatsiplus_automation_sms_template_rem_3',
                'label'   => __( '3 days reminder message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[whatsiplus_automation_sms_template_rem_3]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [name] subscription will expire in 3 Days, renew now to keep access.', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
            array(
                'name'    => 'whatsiplus_automation_sms_template_custom',
                'label'   => __( 'Custom time reminder message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[whatsiplus_automation_sms_template_custom]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [name] subscription will expire in [reminder_custom_time] Days, renew now to keep access. - custom', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
        );
    }

    public function get_keywords_field()
    {
        return array(
            'user' => array(
                'email',
                'first_name',
                'last_name',
                'phone',
                'country',
            ),
            'ar_plan' => array(
                'name',
                'amount',
                'description',
            ),
            'whatsiplus' => array(
                'reminder_custom_time',
            ),
        );

    }

    private function schedule_reminders($user_id, $plan_id) {
        $user = new WP_User($user_id);
        $send_custom_reminder_flag = true;
        $settings = $this->get_plugin_settings();
        $this->log->add("Whatsiplus", "schedule_reminders: successfully retrieved plugin settings");
        $this->log->add("Whatsiplus", "User ID: {$user->ID}");
        $this->log->add("Whatsiplus", "Plan ID: {$plan_id}");
        $planData = get_user_meta($user->ID, "arm_user_plan_{$plan_id}", true);
        $membership_expiry_timestamp = isset($planData['arm_expire_plan']) ? $planData['arm_expire_plan'] : '';

        if(empty($membership_expiry_timestamp)) {
            // maybe is lifetime account
            $this->log->add("Whatsiplus", "membership expiry date is empty");
            return;
        }

        // do our reminder stuff
        $as_group = "{$this::$plugin_identifier}_{$user->ID}";
        $format = get_option("date_format");
        $membership_expiry_date = date_i18n($format, $membership_expiry_timestamp);

        // Create date from timestamp
        $reminder_booking_date_1 = DateTime::createFromFormat('U', $membership_expiry_timestamp);
        $reminder_booking_date_1->setTimezone(wp_timezone());

        $reminder_booking_date_2 = DateTime::createFromFormat('U', $membership_expiry_timestamp);
        $reminder_booking_date_2->setTimezone(wp_timezone());

        $reminder_booking_date_3 = DateTime::createFromFormat('U', $membership_expiry_timestamp);
        $reminder_booking_date_3->setTimezone(wp_timezone());

        $reminder_booking_date_custom = DateTime::createFromFormat('U', $membership_expiry_timestamp);
        $reminder_booking_date_custom->setTimezone(wp_timezone());

        // current local time
        $current_time = date_i18n('Y-m-d H:i:s O');
        $now_date = DateTime::createFromFormat('Y-m-d H:i:s O', $current_time, wp_timezone())->format($format);
        $now_timestamp = DateTime::createFromFormat('Y-m-d H:i:s O', $current_time, wp_timezone())->getTimestamp();
        // $now_timestamp = strtotime("+1 minute", $now_timestamp);

        $this->log->add("Whatsiplus", "Membership expiry date: {$membership_expiry_date}");
        $this->log->add("Whatsiplus", "Current Local Date: {$now_date}");
        $this->log->add("Whatsiplus", "Current Local Timestamp: {$now_timestamp}");

        $custom_reminder_time = $settings['whatsiplus_automation_reminder_custom_time'];
        if(!ctype_digit($custom_reminder_time)) {
            $this->log->add("Whatsiplus", "reminder time (in minutes) is not digit");
            $send_custom_reminder_flag = false;
        }

        $reminder_date_1 = $reminder_booking_date_1->modify("-1 day")->getTimestamp();
        $reminder_date_2 = $reminder_booking_date_2->modify("-2 days")->getTimestamp();
        $reminder_date_3 = $reminder_booking_date_3->modify("-3 days")->getTimestamp();

        $this->log->add("Whatsiplus", "1 Day Reminder timestamp: {$reminder_date_1}");
        $this->log->add("Whatsiplus", "2 Days Reminder timestamp: {$reminder_date_2}");
        $this->log->add("Whatsiplus", "3 Days Reminder timestamp: {$reminder_date_3}");

        $this->log->add("Whatsiplus", "Unscheduling all message reminders for Group: {$as_group}");
        as_unschedule_all_actions('', array(), $as_group);
        $action_id_15 = as_schedule_single_action($reminder_date_1, $this->hook_action, array($user, $plan_id, 'rem_1'), $as_group );
        $action_id_30 = as_schedule_single_action($reminder_date_2, $this->hook_action, array($user, $plan_id, 'rem_2'), $as_group );
        $action_id_60 = as_schedule_single_action($reminder_date_3, $this->hook_action, array($user, $plan_id, 'rem_3'), $as_group );
        $this->log->add("Whatsiplus", "Send message Reminder scheduled, action_id_15 = {$action_id_15}");
        $this->log->add("Whatsiplus", "Send message Reminder scheduled, action_id_30 = {$action_id_30}");
        $this->log->add("Whatsiplus", "Send message Reminder scheduled, action_id_60 = {$action_id_60}");

        if($send_custom_reminder_flag) {
            $reminder_date_custom = $reminder_booking_date_custom->modify("-{$custom_reminder_time} minutes")->getTimestamp();
            $this->log->add("Whatsiplus", "Custom Reminder timestamp: {$reminder_date_custom}");
            $action_id_custom = as_schedule_single_action($reminder_date_custom, $this->hook_action, array($user, $plan_id, 'custom'), $as_group );
            $this->log->add("Whatsiplus", "Send message Reminder scheduled, action_id_custom = {$action_id_custom}");
        }

    }

    public function send_sms_reminder($user, $plan_id, $status)
    {
        if(! $user instanceof WP_User) {
            $this->log->add("Whatsiplus", '$user not an instance of WP_User');
            $user = new WP_User($user['ID']);
        }
        $this->log->add("Whatsiplus", 'Converted $user to an instance of WP_User');

        $this->log->add("Whatsiplus", "User ID: {$user->ID}");
        $this->log->add("Whatsiplus", "send_sms_reminder plan_id: {$plan_id}");
        $this->log->add("Whatsiplus", "Status: {$status}");

        // membership already expired
        $planData = get_user_meta($user->ID, "arm_user_plan_{$plan_id}", true);
        $membership_expiry_timestamp = isset($planData['arm_expire_plan']) ? $planData['arm_expire_plan'] : '';
        $now_timestamp = current_datetime()->getTimestamp();

        // membership already expired
        if($now_timestamp >= $membership_expiry_timestamp) {
            $this->log->add("Whatsiplus", "membership expiry date is in the past");
            return;
        }

        $arm_active_status = get_user_meta($user->ID, 'arm_primary_status', true);
        if($arm_active_status != '1') {
            $this->log->add("Whatsiplus", "Member Status is not active");
            return;
        }

        $settings = $this->get_plugin_settings();

        $enable_notifications = $settings['whatsiplus_automation_enable_notification'];
        $reminder = $settings['whatsiplus_automation_reminder'];

        $this->log->add("Whatsiplus", "Successfully retrieved plugin settings");

        if($enable_notifications === "on"){
            $this->log->add("Whatsiplus", "enable_notifications: {$enable_notifications}");
            if(!empty($reminder) && is_array($reminder)) {
                if(array_key_exists($status, $reminder)) {
                    $this->log->add("Whatsiplus", "Sending reminder now");
                    $this->send_customer_notification($user, $plan_id, $status);
                }
            }
        }
    }

    public function send_sms_on($user_id, $plan_id, $status)
    {
        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['whatsiplus_automation_enable_notification'];
        $send_on = $plugin_settings['whatsiplus_automation_send_on'];

        if($enable_notifications === "on") {
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $user = new WP_User($user_id);
                    $this->send_customer_notification($user, $plan_id, $status);
                }
            }
        }

        return false;
    }

    public function send_sms_on_status_cancel_subscription($user_id, $plan_id) {
        $status = 'cancel_subscription';
        $as_group = "{$this::$plugin_identifier}_{$user_id}";
        as_unschedule_all_actions('', array(), $as_group);
        $this->send_sms_on($user_id, $plan_id, $status);
	}

    public function send_sms_on_status_after_user_plan_change($user_id, $plan_id) {
        $status = 'after_user_plan_change';
        $this->schedule_reminders($user_id, $plan_id);
        $this->send_sms_on( $user_id, $plan_id, $status);
	}

    public function send_sms_on_status_after_user_plan_renew($user_id, $plan_id) {
        $status = 'after_user_plan_renew';
        $this->schedule_reminders($user_id, $plan_id);
        $this->send_sms_on( $user_id, $plan_id, $status);
	}

    public function send_customer_notification($user, $plan_id, $status)
    {
        $this->log->add("Whatsiplus", "send_customer_notification status: {$status}");
        $settings = $this->get_plugin_settings();
        $sms_from = $settings['whatsiplus_automation_send_from'];

        $plan = new ARM_Plan($plan_id);

        // get number from user
        $validated_user = WhatsiPLUS_SendSMS_Sms::getValidatedPhoneNumbers($user);
        if(empty($validated_user))
            return false;
        $phone_no = $validated_user->phone;
        $this->log->add("Whatsiplus", "phone_no: {$phone_no}");
        // get message template from status
        $msg_template = $settings["whatsiplus_automation_sms_template_{$status}"];
        $message = $this->replace_keywords_with_value($user, $plan, $msg_template);

        WhatsiPLUS_SendSMS_Sms::send_sms($sms_from, $phone_no, $message, $this->plugin_medium);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'customer name here'
    */
    protected function replace_keywords_with_value($user, $plan, $message)
    {
        // use regex to match all [stuff_inside]
        // return the message
        preg_match_all('/\[(.*?)\]/', $message, $keywords);

        if(!empty($keywords)) {
            foreach($keywords[1] as $keyword) {
                if($user->has_prop($keyword)) {
                    $message = str_replace("[{$keyword}]", $user->$keyword, $message);
                }
                else if(property_exists($plan, $keyword)) {
                    $message = str_replace("[{$keyword}]", $plan->$keyword, $message);
                }
                else if($keyword == 'reminder_custom_time') {
                    $settings = $this->get_plugin_settings();
                    $reminder_time = $settings['whatsiplus_automation_reminder_custom_time'];
                    $message = str_replace("[{$keyword}]", $this->seconds_to_days($reminder_time), $message);
                }
                else {
                    $message = str_replace("[{$keyword}]", "", $message);
                }
            }
        }
        return $message;
    }


    private function seconds_to_days($seconds) {

        if(!ctype_digit($seconds)) {
            $this->log->add("Whatsiplus", 'seconds_to_days: $seconds is not a valid digit');
            return '';
        }

        $ret = "";

        $days = intval(intval($seconds) / (3600*24));
        if($days> 0)
        {
            $ret .= "{$days}";
        }

        return $ret;
    }



}
