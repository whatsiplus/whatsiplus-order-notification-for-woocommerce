<?php

class WhatsiSimpleMembership implements Whatsiplus_PluginInterface, Whatsiplus_Register_Interface {
    /*
    Plugin Name: Simple Membership
    Plugin Link: https://wordpress.org/plugins/simple-membership/
    */

    public static $plugin_identifier = 'simple-membership';
    private $plugin_name;
    private $plugin_medium;
    private $hook_action;
    private $log;
    private $option_id;

    public function __construct() {
        $this->log = new Whatsiplus_WooCommerce_Logger();
        $this->option_id = "whatsiplus_{$this::$plugin_identifier}";
        $this->plugin_name = 'Simple Membership';
        $this->plugin_medium = 'wp_' . str_replace( ' ', '_', strtolower($this->plugin_name));
        $this->hook_action = "whatsiplus_send_reminder_{$this::$plugin_identifier}";

    }

    public static function plugin_activated() {
        $log = new Whatsiplus_WooCommerce_Logger();
        if(! is_plugin_active(sprintf("%s/simple-wp-membership.php", self::$plugin_identifier)) ) { return false; }
        try {
            require_once SIMPLE_WP_MEMBERSHIP_PATH . "classes/class.swpm-utils-member.php";
            require_once SIMPLE_WP_MEMBERSHIP_PATH . "classes/class.swpm-utils-membership-level.php";
            return true;
        } catch (Exception $e) {
            $log->add("Whatsiplus", "Failed to import classes/class.swpm-utils-member.php");
            $log->add("Whatsiplus", "Failed to import classes/class.swpm-utils-membership-level");
            return false;
        }

    }

    public function register() {
        add_action('swpm_payment_ipn_processed',          array($this, 'send_sms_on'));
        add_action('swpm_recurring_payment_received',     array($this, 'send_sms_on_rec_payment'));
        add_action('swpm_subscription_payment_cancelled', array($this, 'send_sms_on_payment_cancelled'));
        add_action( $this->hook_action,                   array($this, 'send_sms_reminder'), 10, 3);
    }

    public function get_option_id()
    {
        return $this->option_id;
    }

    public function get_setting_section_data() {
        return array(
            'id'    => $this->get_option_id(),
            'title' => __( 'Simple Membership', 'WHATSIPLUS_TEXT_DOMAIN' ),
        );
    }

    public function get_setting_field_data() {
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

    public function get_plugin_settings($with_identifier = false) {
        $settings = array(
            "whatsiplus_automation_enable_notification"                     => whatsiplus_get_options("whatsiplus_automation_enable_notification", $this->get_option_id()),
            "whatsiplus_send_from"                                          => whatsiplus_get_options('whatsiplus_automation_send_from', $this->get_option_id()),
            "whatsiplus_automation_send_on"                                 => whatsiplus_get_options("whatsiplus_automation_send_on", $this->get_option_id()),
            "whatsiplus_automation_reminder"                                => whatsiplus_get_options("whatsiplus_automation_reminder", $this->get_option_id()),
            "whatsiplus_automation_reminder_custom_time"                    => whatsiplus_get_options("whatsiplus_automation_reminder_custom_time", $this->get_option_id()),
            "whatsiplus_automation_sms_template_rem_1"                      => whatsiplus_get_options("whatsiplus_automation_sms_template_rem_1", $this->get_option_id()),
            "whatsiplus_automation_sms_template_rem_2"                      => whatsiplus_get_options("whatsiplus_automation_sms_template_rem_2", $this->get_option_id()),
            "whatsiplus_automation_sms_template_rem_3"                      => whatsiplus_get_options("whatsiplus_automation_sms_template_rem_3", $this->get_option_id()),
            "whatsiplus_automation_sms_template_custom"                     => whatsiplus_get_options("whatsiplus_automation_sms_template_custom", $this->get_option_id()),
            "whatsiplus_automation_sms_template_recurring_payment_received" => whatsiplus_get_options("whatsiplus_automation_sms_template_recurring_payment_received", $this->get_option_id()),
            "whatsiplus_automation_sms_template_payment_cancelled"          => whatsiplus_get_options("whatsiplus_automation_sms_template_payment_cancelled", $this->get_option_id()),
        );

        if ($with_identifier) {
            return array(
                self::$plugin_identifier => $settings,
            );
        }

        return $settings;
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
                'recurring_payment_received' => 'Recurring payment received',
                'payment_cancelled'          => 'Membership cancellation or end of term',
            )
        );
    }

    private function get_sms_template_fields() {
        return array(
            array(
                'name'    => 'whatsiplus_automation_sms_template_recurring_payment_received',
                'label'   => __( 'Recurring payment received', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[whatsiplus_automation_sms_template_recurring_payment_received]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your subscription of [total_amount] for [membership_level_name] via [payment_gateway] is [payment_status].', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
            array(
                'name'    => 'whatsiplus_automation_sms_template_payment_cancelled',
                'label'   => __( 'Membership cancellation or end of term', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[whatsiplus_automation_sms_template_payment_cancelled]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your membership for [membership_level_name] has expired or cancelled. Renew now to retain access.', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
        );
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
                'default' => __( 'Hi [first_name], your [membership_level_name] subscription will expire in 1 Day, renew now to keep access.', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
            array(
                'name'    => 'whatsiplus_automation_sms_template_rem_2',
                'label'   => __( '2 days reminder message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[whatsiplus_automation_sms_template_rem_2]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [membership_level_name] subscription will expire in 2 Days, renew now to keep access.', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
            array(
                'name'    => 'whatsiplus_automation_sms_template_rem_3',
                'label'   => __( '3 days reminder message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[whatsiplus_automation_sms_template_rem_3]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [membership_level_name] subscription will expire in 3 Days, renew now to keep access.', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
            array(
                'name'    => 'whatsiplus_automation_sms_template_custom',
                'label'   => __( 'Custom time reminder message', 'WHATSIPLUS_TEXT_DOMAIN' ),
                'desc'    => sprintf('Customize your message with <button type="button" id="whatsiplus-open-keyword-%1$s-[dummy]" data-attr-type="pending" data-attr-target="%1$s[whatsiplus_automation_sms_template_custom]" class="button button-secondary">Keywords</button>', $this->get_option_id() ),
                'type'    => 'textarea',
                'rows'    => '8',
                'cols'    => '500',
                'css'     => 'min-width:350px;',
                'default' => __( 'Hi [first_name], your [membership_level_name] subscription will expire in [reminder_custom_time] Days, renew now to keep access. - custom', 'WHATSIPLUS_TEXT_DOMAIN' )
            ),
        );
    }

    public function get_keywords_field() {
        return array(
            'member' => array(
                'first_name',
                'last_name',
                'email',
                'phone',
                'member_id',
                'membership_level_name',
                'account_state',
                'address_street',
                'address_city',
                'address_state',
                'address_zipcode',
                'country',
                'gender',
                'subscription_start',
            ),
            'payment_gateway' => array(
                'total_amount',
                'payer_email',
                'txn_id',
                'subscr_id',
                'ip_address',
                'payment_gateway',
                'payment_status',
            ),
        );

    }

    private function schedule_reminders($member, $ipn_data, $status) {
        $send_custom_reminder_flag = true;
        $settings = $this->get_plugin_settings();
        $format = get_option("date_format");

        $this->log->add("Whatsiplus", "schedule_reminders: successfully retrieved plugin settings");
        $this->log->add("Whatsiplus", "Member ID: {$member->member_id}");

        if( $member->account_state != 'active') {
            $this->log->add("Whatsiplus", "member status is not active. member status: {$member->account_state}");
            $this->log->add("Whatsiplus", "Aborting...");
            return;
        }

        $membership_expiry_timestamp = SwpmMemberUtils::get_expiry_date_timestamp_by_user_id($member->member_id);

        if( $membership_expiry_timestamp == PHP_INT_MAX) {
            // life time account
            $this->log->add("Whatsiplus", "This is a lifetime account. Aborting");
            return;
        }

        // do our reminder stuff
        $as_group = "{$this::$plugin_identifier}_{$member->member_id}";

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

        $this->log->add("Whatsiplus", "Membership expiry timestamp: {$membership_expiry_timestamp}");
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

        $action_id_15 = as_schedule_single_action($reminder_date_1, $this->hook_action, array($member->member_id, $ipn_data, 'rem_1'), $as_group );
        $action_id_30 = as_schedule_single_action($reminder_date_2, $this->hook_action, array($member->member_id, $ipn_data, 'rem_2'), $as_group );
        $action_id_60 = as_schedule_single_action($reminder_date_3, $this->hook_action, array($member->member_id, $ipn_data, 'rem_3'), $as_group );
        $this->log->add("Whatsiplus", "Send Message Reminder scheduled, action_id_15 = {$action_id_15}");
        $this->log->add("Whatsiplus", "Send Message Reminder scheduled, action_id_30 = {$action_id_30}");
        $this->log->add("Whatsiplus", "Send Message Reminder scheduled, action_id_60 = {$action_id_60}");

        if($send_custom_reminder_flag) {
            $reminder_date_custom = $reminder_booking_date_custom->modify("-{$custom_reminder_time} minutes")->getTimestamp();
            $this->log->add("Whatsiplus", "Custom Reminder timestamp: {$reminder_date_custom}");
            $action_id_custom = as_schedule_single_action($reminder_date_custom, $this->hook_action, array($member->member_id, $ipn_data, 'custom'), $as_group );
            $this->log->add("Whatsiplus", "Send Message Reminder scheduled, action_id_custom = {$action_id_custom}");
        }

    }

    public function send_sms_reminder($member_id, $ipn_data, $status)
    {
        $this->log->add("Whatsiplus", "User ID: {$member_id}");
        $this->log->add("Whatsiplus", "Status: {$status}");

        $member = SwpmMemberUtils::get_user_by_id($member_id);

        if( $member->account_state != 'active') {
            $this->log->add("Whatsiplus", "member status is not active. member status: {$member->account_state}");
            $this->log->add("Whatsiplus", "Aborting send_sms_reminder");
            return;
        }

        $membership_expiry_timestamp = SwpmMemberUtils::get_expiry_date_timestamp_by_user_id($member->member_id);

        $now_timestamp = current_datetime()->getTimestamp();

        // membership already expired
        if($now_timestamp >= $membership_expiry_timestamp) {
            $this->log->add("Whatsiplus", "membership expiry date is in the past");
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
                    $this->send_customer_notification($member, $ipn_data, $status);
                }
            }
        }
    }

    public function send_sms_on($ipn_data)
    {
        $this->log->add("Whatsiplus", "Universal payment data received");
        $this->log->add("Whatsiplus", print_r($ipn_data, true));

        // stripe
        if( $ipn_data['gateway'] == 'stripe-sca-subs' ) {
            $this->send_sms_on_rec_payment($ipn_data);
        }
    }

    public function send_sms_on_rec_payment($ipn_data)
    {
        $this->log->add("Whatsiplus", "Subscription payment data received");
        $this->log->add("Whatsiplus", print_r($ipn_data, true));

        // stripe
        if( !empty($ipn_data['swpm_id']) ) {
            $member_id = $ipn_data['swpm_id'];
        }
        // paypal
        else if ( !empty($ipn_data['member_id']) ) {
            $member_id = $ipn_data['member_id'];
        }
        else {
            $this->log->add("Whatsiplus", "member_id is empty, this is a new account, nothing to do here");
            return;
        }

        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['whatsiplus_automation_enable_notification'];
        $send_on = $plugin_settings['whatsiplus_automation_send_on'];

        $this->log->add("Whatsiplus", "member_id: {$member_id}");
        $status = 'recurring_payment_received';

        if($enable_notifications === "on") {
            $this->log->add("Whatsiplus", "enable_notifications: {$enable_notifications}");
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $this->log->add("Whatsiplus", "enable_notifications for {$status}: on");
                    $member = SwpmMemberUtils::get_user_by_id($member_id);
                    $this->schedule_reminders($member, $ipn_data, $status);
                    $this->send_customer_notification($member, $ipn_data, $status);
                }
            }
        }
    }

    public function send_sms_on_payment_cancelled($ipn_data)
    {
        $this->log->add("Whatsiplus", "Cancelled or Expired Payment");
        $this->log->add("Whatsiplus", print_r($ipn_data, true));

        $plugin_settings = $this->get_plugin_settings();
        $enable_notifications = $plugin_settings['whatsiplus_automation_enable_notification'];
        $send_on = $plugin_settings['whatsiplus_automation_send_on'];

        if( empty($ipn_data['member_id']) ) {
            $this->log->add("Whatsiplus", "member_id is empty, aborting...");
            return;
        }

        $member_id = $ipn_data['member_id'];
        $status = 'payment_cancelled';
        $this->log->add("Whatsiplus", "member_id: {$member_id}");
        $this->log->add("Whatsiplus", "status: {$status}");

        if($enable_notifications === "on"){
            $this->log->add("Whatsiplus", "enable_notifications: {$enable_notifications}");
            if(!empty($send_on) && is_array($send_on)) {
                if(array_key_exists($status, $send_on)) {
                    $this->log->add("Whatsiplus", "enable_notifications for {$status}: on");
                    $member = SwpmMemberUtils::get_user_by_id($member_id);
                    $this->send_customer_notification($member, $ipn_data, $status);
                }
            }
        }
    }

    public function send_customer_notification($member, $ipn_data, $status)
    {
        $settings = $this->get_plugin_settings();
        $sms_from = $settings['whatsiplus_automation_send_from'];
        $phone_no = '';

        // get number from user
        // first check if the $user is an instance of WP_User object
        // else if it is a member's object.

        $whatsiplus_seting = new Whatsiplus_WooCommerce_Setting();
        $countries = $whatsiplus_seting->get_countries();

        if( !empty($member->phone) ) {
            $phone_no = $member->phone;
            if( !empty($member->country) ) {
                $country = array_search($member->country, $countries);
                $phone_no = WhatsiPLUS_SendSMS_Sms::get_formatted_number($phone_no, $country);
            }
        }
        $this->log->add("Whatsiplus", "phone_no: {$phone_no}");

        // get message template from status
        $msg_template = $settings["whatsiplus_automation_sms_template_{$status}"];
        $message = $this->replace_keywords_with_value($member, $ipn_data, $msg_template);
        WhatsiPLUS_SendSMS_Sms::send_sms($sms_from, $phone_no, $message, $this->plugin_medium);
    }

    /*
        returns the message with keywords replaced to original value it points to
        eg: [name] => 'customer name here'
    */
    protected function replace_keywords_with_value($member, $ipn_data, $message)
    {
        $kw_mapper = array(
            '[first_name]'            => $member->first_name,
            '[last_name]'             => $member->last_name,
            '[email]'                 => $member->email,
            '[phone]'                 => $member->phone,
            '[member_id]'             => $member->member_id,
            '[membership_level_name]' => SwpmMembershipLevelUtils::get_membership_level_name_by_level_id($member->membership_level),
            '[account_state]'         => $member->account_state,
            '[address_street]'        => $member->address_street,
            '[address_city]'          => $member->address_city,
            '[address_state]'         => $member->address_state,
            '[address_zipcode]'       => $member->address_zipcode,
            '[country]'               => $member->country,
            '[gender]'                => $member->gender,
            '[subscription_starts]'   => $member->subscription_starts,

            '[total_amount]'          => $ipn_data['mc_gross'],
            '[payer_email]'           => $ipn_data['payer_email'],
            '[txn_id]'                => $ipn_data['txn_id'],
            '[subscr_id]'             => $ipn_data['subscr_id'],
            '[ip_address]'            => $ipn_data['ip'],
            '[payment_gateway]'       => $ipn_data['gateway'],
            '[payment_status]'        => $ipn_data['status'],
        );

        $mapper_to_use = $kw_mapper;

        if( isset($ipn_data['parent_txn_id']) ) {
            // we do this because... we only get 3 data from the hook
            // so we query plugin's database to get get more data.
            global $wpdb;
            $subscr_id = $ipn_data['subscr_id'];

            $query_db = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}swpm_payments_tbl WHERE subscr_id = %s OR subscr_id LIKE %s", $subscr_id, $subscr_id.'|%' ), OBJECT );
            $stripe_mapper = array(
                '[first_name]'            => $member->first_name,
                '[last_name]'             => $member->last_name,
                '[email]'                 => $member->email,
                '[phone]'                 => $member->phone,
                '[member_id]'             => $member->member_id,
                '[membership_level_name]' => SwpmMembershipLevelUtils::get_membership_level_name_by_level_id($member->membership_level),
                '[account_state]'         => $member->account_state,
                '[address_street]'        => $member->address_street,
                '[address_city]'          => $member->address_city,
                '[address_state]'         => $member->address_state,
                '[address_zipcode]'       => $member->address_zipcode,
                '[country]'               => $member->country,
                '[gender]'                => $member->gender,
                '[subscription_starts]'   => $member->subscription_starts,
                '[total_amount]'          => $query_db->payment_amount,
                '[payer_email]'           => $query_db->email,
                '[txn_id]'                => $query_db->txn_id,
                '[subscr_id]'             => $query_db->subscr_id,
                '[ip_address]'            => $query_db->ip_address,
                '[payment_gateway]'       => $query_db->gateway,
                '[payment_status]'        => $query_db->status,
            );

            $mapper_to_use = $stripe_mapper;
        }
        return str_replace(array_keys($mapper_to_use), array_values($mapper_to_use), $message);
    }

}
