<?php

class WhatsiPLUS_SendSMS_View implements Whatsiplus_Register_Interface {

	private $settings_api;
    private $log;

	function __construct() {
        $this->log = new Whatsiplus_WooCommerce_Logger();
		$this->settings_api = new WONFW_Settings_API;

        add_action('wp_enqueue_scripts', array($this, 'my_custom_scripts3'));
        add_action('admin_enqueue_scripts', array($this, 'my_custom_scripts3'));
	}

	public function register() {
        add_filter( 'whatsiplus_setting_section',     array($this, 'set_sendsms_setting_section' ) );
		add_filter( 'whatsiplus_setting_fields',      array($this, 'set_sendsms_setting_field' ) );
		add_action( 'whatsiplus_load_javascripts',    array($this, 'load_scripts' ) );
        add_action( 'register_form',                 array($this, 'mapi_display_phone_field'));
        add_action( 'register_form',                 array($this, 'mapi_display_country_field'));
        add_action( 'register_post',                 array($this, 'mapi_validate_fields'),10,3);
        add_action( 'user_register',                 array($this, 'mapi_register_additional_fields'));
        add_action( 'show_user_profile',             array($this, 'mapi_show_additional_profile_fields') );
        add_action( 'edit_user_profile',             array($this, 'mapi_show_additional_profile_fields') );
        add_action( 'personal_options_update',       array($this, 'mapi_save_additional_profile_fields') );
        add_action( 'edit_user_profile_update',      array($this, 'mapi_save_additional_profile_fields') );
        add_action( 'user_profile_update_errors',    array($this, 'validate_additional_fields'), 10, 3 );
		add_action( 'admin_post_whatsiplus_sms_form', array($this, 'mapi_send_sms' ) );
        add_action( 'admin_notices',                 array($this, 'display_send_sms_success') );
        add_filter( 'removable_query_args',          array($this, 'add_removable_arg') );
	}

    public function sanitize_recursive($input) {
        if (is_array($input)) {
            return array_map(array($this, 'sanitize_recursive'), $input);
        } else {
            return sanitize_text_field($input);
        }
    }
    
    public function mapi_send_sms()
    {
        $nonce = isset( $_POST['whatsiplus_nonce'] ) ? sanitize_text_field(wp_unslash( $_POST['whatsiplus_nonce']) ) : '';
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'whatsiplus_send_sms_action' ) ) {
            //return;
        }

        $from='';$message_to='';$message='';$users='';$recipients='';$country='';$roles=[];
        //$post_data = $_POST['whatsiplus_sendsms_setting'];
        //$post_data = isset($_POST['whatsiplus_sendsms_setting']) ? array_map('sanitize_text_field', wp_unslash($_POST['whatsiplus_sendsms_setting'])) : array();

        $post_data = isset($_POST['whatsiplus_sendsms_setting']) ? $this->sanitize_recursive(wp_unslash($_POST['whatsiplus_sendsms_setting'])) : array();


        $from = "Whatsi";

            //$from = esc_attr($from);
        if(isset($post_data['whatsiplus_sendsms_message_to']))
            $message_to = sanitize_text_field(wp_unslash($post_data['whatsiplus_sendsms_message_to']));
            //$message_to = esc_attr($message_to);
        if(isset($post_data['whatsiplus_sendsms_message']))
            $message = sanitize_text_field(wp_unslash($post_data['whatsiplus_sendsms_message']));
            //$message = esc_textarea($message);
        if(isset($post_data['whatsiplus_sendsms_users'])){
            $users = array();
            foreach ($post_data['whatsiplus_sendsms_users'] as $value) {
                $users[] = sanitize_text_field($value);
            }
        }
            //$users = array_map( 'esc_attr', $users );
        if(isset($post_data['whatsiplus_sendsms_recipients']))
            $recipients = sanitize_text_field(wp_unslash($post_data['whatsiplus_sendsms_recipients']));
            //$recipients = esc_textarea($recipients);
        // if(isset($post_data['whatsiplus_sendsms_country']))
        //     $country = sanitize_text_field($post_data['whatsiplus_sendsms_country']);

        if(isset($post_data['whatsiplus_sendsms_filters']))
            $filters = sanitize_text_field(wp_unslash($post_data['whatsiplus_sendsms_filters']));
        if(isset($post_data['whatsiplus_sendsms_criteria'])) {
            $criteria = sanitize_text_field(wp_unslash($post_data['whatsiplus_sendsms_criteria']));
        }

        $numbers = WhatsiPLUS_SendSMS_Sms::getPhoneNumber($message_to, $users, $recipients, $country, $filters, $criteria);
        // write_log('numbers :' . wp_json_encode($numbers));

        $medium = 'wp_wordpress';

        if($numbers){
            if(is_array($numbers)){
                foreach($numbers as $number){
                    if($number instanceof WP_User) {
                        $user = $number;
                        $send_sms = WhatsiPLUS_SendSMS_Sms::send_sms($from, $user->phone, $message, $medium);
                    }
                    else {
                        $send_sms = WhatsiPLUS_SendSMS_Sms::send_sms($from, $number, $message, $medium);
                    }
                }
            }else{
                if($numbers instanceof WP_User) {
                    $user = $numbers;
                    $send_sms = WhatsiPLUS_SendSMS_Sms::send_sms($from, $user->phone, $message, $medium);
                }
                else {
                    $send_sms = WhatsiPLUS_SendSMS_Sms::send_sms($from, $numbers, $message, $medium);
                }
            }
        }
        wp_redirect(admin_url('options-general.php?page=whatsiplus-woocommerce-setting&sms_sent='.$send_sms)); exit;
    }

	public function set_sendsms_setting_section( $sections ) {
		$sections[] = array(
            'id'               => 'whatsiplus_sendsms_setting',
            'title'            => __( 'Send WhatsApp Message', 'whatsiplus-order-notification-for-woocommerce' ),
            'submit_button'    => get_submit_button('Send Message', 'primary large', 'sendMessage', true ,array('id' => 'sendMessage')),
            'action'           => 'whatsiplus_sms_form',
            'action_url'       => admin_url('admin-post.php'),
		);

		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public function set_sendsms_setting_field( $setting_fields ) {

        $users = get_users();
        $filtered_user = array();

        foreach($users as $user) {
            $filtered_user[$user->ID] = $user->user_login;
        }

		$setting_fields['whatsiplus_sendsms_setting'] = array(
			array(
				'name'    => 'whatsiplus_sendsms_message_to',
				'label'   => __( 'To', 'whatsiplus-order-notification-for-woocommerce' ),
				'desc'    => 'Select the recipients you wish to broadcast your message',
				'type'    => 'select',
				'default' => 'customer_all',
				'options' => array(
					'customer_all'     => 'All users',
					'customer'         => 'Specific users',
					'phones'           => 'Specific phone number',
					'spec_group_ppl'   => 'Specific Group of People',
				)
			),
			array(
				'name'    => 'whatsiplus_sendsms_users',
				'label'   => __( 'Users', 'whatsiplus-order-notification-for-woocommerce' ),
				'desc'    => 'Note: Please ensure <b>Mobile Number</b> field at <b>Additional profile information</b> is not empty for selected users.<br />',
				'type'    => 'selectm',
				'default' => 'auto',
				'options' => $filtered_user
			),

			array(
				'name'    => 'whatsiplus_sendsms_recipients',
				'label'   => __( 'Recipients', 'whatsiplus-order-notification-for-woocommerce' ),
				'desc'    => '(Please insert country code along with mobile numbers,<br>e.g. 15303776310,15303776310)',
				'type'    => 'textarea',
				'rows'    => '8',
				'cols'    => '500',
				'css'     => 'min-width:350px',
			),
            array(
				'name'    => 'whatsiplus_sendsms_filters',
				'label'   => __( 'Filter By', 'whatsiplus-order-notification-for-woocommerce' ),
				'desc'    => 'Select the recipients you wish to filter by<br />',
				'type'    => 'select',
				'default' => '-1',
				'options' => array(
                    '-1'          => "Select Filter",
                    'roles'       => "roles",
                    'country'     => "country",
                )
			),
            array(
				'name'    => 'whatsiplus_sendsms_criteria',
				'label'   => __( 'Criteria', 'whatsiplus-order-notification-for-woocommerce' ),
				'desc'    => 'Select the criteria you wish to filter by<br />',
				'type'    => 'select',
                // 'css'     => 'min-width:350px;',
				'default' => '-1',
				'options' => array(),

			),
			array(
				'name'    => 'whatsiplus_sendsms_message',
				'label'   => __( 'Message', 'whatsiplus-order-notification-for-woocommerce' ),
				'desc'    => "When sending outbound messages via our API service, please ensure that the total message size remains below the channel's maximum size limit.",
				'type'    => 'textarea',
				'rows'    => '8',
				'cols'    => '500',
				'css'     => 'min-width:350px;',
            ),

		);

		return $setting_fields;
	}

    public function mapi_getCountryList(){
        $countries = array();
        $countries[] = array("code"=>"AF","name"=>"Afghanistan","d_code"=>"+93");
        $countries[] = array("code"=>"AL","name"=>"Albania","d_code"=>"+355");
        $countries[] = array("code"=>"DZ","name"=>"Algeria","d_code"=>"+213");
        $countries[] = array("code"=>"AS","name"=>"American Samoa","d_code"=>"+1");
        $countries[] = array("code"=>"AD","name"=>"Andorra","d_code"=>"+376");
        $countries[] = array("code"=>"AO","name"=>"Angola","d_code"=>"+244");
        $countries[] = array("code"=>"AI","name"=>"Anguilla","d_code"=>"+1");
        $countries[] = array("code"=>"AG","name"=>"Antigua","d_code"=>"+1");
        $countries[] = array("code"=>"AR","name"=>"Argentina","d_code"=>"+54");
        $countries[] = array("code"=>"AM","name"=>"Armenia","d_code"=>"+374");
        $countries[] = array("code"=>"AW","name"=>"Aruba","d_code"=>"+297");
        $countries[] = array("code"=>"AU","name"=>"Australia","d_code"=>"+61");
        $countries[] = array("code"=>"AT","name"=>"Austria","d_code"=>"+43");
        $countries[] = array("code"=>"AZ","name"=>"Azerbaijan","d_code"=>"+994");
        $countries[] = array("code"=>"BH","name"=>"Bahrain","d_code"=>"+973");
        $countries[] = array("code"=>"BD","name"=>"Bangladesh","d_code"=>"+880");
        $countries[] = array("code"=>"BB","name"=>"Barbados","d_code"=>"+1");
        $countries[] = array("code"=>"BY","name"=>"Belarus","d_code"=>"+375");
        $countries[] = array("code"=>"BE","name"=>"Belgium","d_code"=>"+32");
        $countries[] = array("code"=>"BZ","name"=>"Belize","d_code"=>"+501");
        $countries[] = array("code"=>"BJ","name"=>"Benin","d_code"=>"+229");
        $countries[] = array("code"=>"BM","name"=>"Bermuda","d_code"=>"+1");
        $countries[] = array("code"=>"BT","name"=>"Bhutan","d_code"=>"+975");
        $countries[] = array("code"=>"BO","name"=>"Bolivia","d_code"=>"+591");
        $countries[] = array("code"=>"BA","name"=>"Bosnia and Herzegovina","d_code"=>"+387");
        $countries[] = array("code"=>"BW","name"=>"Botswana","d_code"=>"+267");
        $countries[] = array("code"=>"BR","name"=>"Brazil","d_code"=>"+55");
        $countries[] = array("code"=>"IO","name"=>"British Indian Ocean Territory","d_code"=>"+246");
        $countries[] = array("code"=>"VG","name"=>"British Virgin Islands","d_code"=>"+1");
        $countries[] = array("code"=>"BN","name"=>"Brunei","d_code"=>"+673");
        $countries[] = array("code"=>"BG","name"=>"Bulgaria","d_code"=>"+359");
        $countries[] = array("code"=>"BF","name"=>"Burkina Faso","d_code"=>"+226");
        $countries[] = array("code"=>"MM","name"=>"Burma Myanmar" ,"d_code"=>"+95");
        $countries[] = array("code"=>"BI","name"=>"Burundi","d_code"=>"+257");
        $countries[] = array("code"=>"KH","name"=>"Cambodia","d_code"=>"+855");
        $countries[] = array("code"=>"CM","name"=>"Cameroon","d_code"=>"+237");
        $countries[] = array("code"=>"CA","name"=>"Canada","d_code"=>"+1");
        $countries[] = array("code"=>"CV","name"=>"Cape Verde","d_code"=>"+238");
        $countries[] = array("code"=>"KY","name"=>"Cayman Islands","d_code"=>"+1");
        $countries[] = array("code"=>"CF","name"=>"Central African Republic","d_code"=>"+236");
        $countries[] = array("code"=>"TD","name"=>"Chad","d_code"=>"+235");
        $countries[] = array("code"=>"CL","name"=>"Chile","d_code"=>"+56");
        $countries[] = array("code"=>"CN","name"=>"China","d_code"=>"+86");
        $countries[] = array("code"=>"CO","name"=>"Colombia","d_code"=>"+57");
        $countries[] = array("code"=>"KM","name"=>"Comoros","d_code"=>"+269");
        $countries[] = array("code"=>"CK","name"=>"Cook Islands","d_code"=>"+682");
        $countries[] = array("code"=>"CR","name"=>"Costa Rica","d_code"=>"+506");
        $countries[] = array("code"=>"CI","name"=>"Côte d'Ivoire" ,"d_code"=>"+225");
        $countries[] = array("code"=>"HR","name"=>"Croatia","d_code"=>"+385");
        $countries[] = array("code"=>"CU","name"=>"Cuba","d_code"=>"+53");
        $countries[] = array("code"=>"CY","name"=>"Cyprus","d_code"=>"+357");
        $countries[] = array("code"=>"CZ","name"=>"Czech Republic","d_code"=>"+420");
        $countries[] = array("code"=>"CD","name"=>"Democratic Republic of Congo","d_code"=>"+243");
        $countries[] = array("code"=>"DK","name"=>"Denmark","d_code"=>"+45");
        $countries[] = array("code"=>"DJ","name"=>"Djibouti","d_code"=>"+253");
        $countries[] = array("code"=>"DM","name"=>"Dominica","d_code"=>"+1");
        $countries[] = array("code"=>"DO","name"=>"Dominican Republic","d_code"=>"+1");
        $countries[] = array("code"=>"EC","name"=>"Ecuador","d_code"=>"+593");
        $countries[] = array("code"=>"EG","name"=>"Egypt","d_code"=>"+20");
        $countries[] = array("code"=>"SV","name"=>"El Salvador","d_code"=>"+503");
        $countries[] = array("code"=>"GQ","name"=>"Equatorial Guinea","d_code"=>"+240");
        $countries[] = array("code"=>"ER","name"=>"Eritrea","d_code"=>"+291");
        $countries[] = array("code"=>"EE","name"=>"Estonia","d_code"=>"+372");
        $countries[] = array("code"=>"ET","name"=>"Ethiopia","d_code"=>"+251");
        $countries[] = array("code"=>"FK","name"=>"Falkland Islands","d_code"=>"+500");
        $countries[] = array("code"=>"FO","name"=>"Faroe Islands","d_code"=>"+298");
        $countries[] = array("code"=>"FM","name"=>"Federated States of Micronesia","d_code"=>"+691");
        $countries[] = array("code"=>"FJ","name"=>"Fiji","d_code"=>"+679");
        $countries[] = array("code"=>"FI","name"=>"Finland","d_code"=>"+358");
        $countries[] = array("code"=>"FR","name"=>"France","d_code"=>"+33");
        $countries[] = array("code"=>"GF","name"=>"French Guiana","d_code"=>"+594");
        $countries[] = array("code"=>"PF","name"=>"French Polynesia","d_code"=>"+689");
        $countries[] = array("code"=>"GA","name"=>"Gabon","d_code"=>"+241");
        $countries[] = array("code"=>"GE","name"=>"Georgia","d_code"=>"+995");
        $countries[] = array("code"=>"DE","name"=>"Germany","d_code"=>"+49");
        $countries[] = array("code"=>"GH","name"=>"Ghana","d_code"=>"+233");
        $countries[] = array("code"=>"GI","name"=>"Gibraltar","d_code"=>"+350");
        $countries[] = array("code"=>"GR","name"=>"Greece","d_code"=>"+30");
        $countries[] = array("code"=>"GL","name"=>"Greenland","d_code"=>"+299");
        $countries[] = array("code"=>"GD","name"=>"Grenada","d_code"=>"+1");
        $countries[] = array("code"=>"GP","name"=>"Guadeloupe","d_code"=>"+590");
        $countries[] = array("code"=>"GU","name"=>"Guam","d_code"=>"+1");
        $countries[] = array("code"=>"GT","name"=>"Guatemala","d_code"=>"+502");
        $countries[] = array("code"=>"GN","name"=>"Guinea","d_code"=>"+224");
        $countries[] = array("code"=>"GW","name"=>"Guinea-Bissau","d_code"=>"+245");
        $countries[] = array("code"=>"GY","name"=>"Guyana","d_code"=>"+592");
        $countries[] = array("code"=>"HT","name"=>"Haiti","d_code"=>"+509");
        $countries[] = array("code"=>"HN","name"=>"Honduras","d_code"=>"+504");
        $countries[] = array("code"=>"HK","name"=>"Hong Kong","d_code"=>"+852");
        $countries[] = array("code"=>"HU","name"=>"Hungary","d_code"=>"+36");
        $countries[] = array("code"=>"IS","name"=>"Iceland","d_code"=>"+354");
        $countries[] = array("code"=>"IN","name"=>"India","d_code"=>"+91");
        $countries[] = array("code"=>"ID","name"=>"Indonesia","d_code"=>"+62");
        $countries[] = array("code"=>"IR","name"=>"Iran","d_code"=>"+98");
        $countries[] = array("code"=>"IQ","name"=>"Iraq","d_code"=>"+964");
        $countries[] = array("code"=>"IE","name"=>"Ireland","d_code"=>"+353");
        $countries[] = array("code"=>"IL","name"=>"Israel","d_code"=>"+972");
        $countries[] = array("code"=>"IT","name"=>"Italy","d_code"=>"+39");
        $countries[] = array("code"=>"JM","name"=>"Jamaica","d_code"=>"+1");
        $countries[] = array("code"=>"JP","name"=>"Japan","d_code"=>"+81");
        $countries[] = array("code"=>"JO","name"=>"Jordan","d_code"=>"+962");
        $countries[] = array("code"=>"KZ","name"=>"Kazakhstan","d_code"=>"+7");
        $countries[] = array("code"=>"KE","name"=>"Kenya","d_code"=>"+254");
        $countries[] = array("code"=>"KI","name"=>"Kiribati","d_code"=>"+686");
        //$countries[] = array("code"=>"XK","name"=>"Kosovo","d_code"=>"+381");
        $countries[] = array("code"=>"KW","name"=>"Kuwait","d_code"=>"+965");
        $countries[] = array("code"=>"KG","name"=>"Kyrgyzstan","d_code"=>"+996");
        $countries[] = array("code"=>"LA","name"=>"Laos","d_code"=>"+856");
        $countries[] = array("code"=>"LV","name"=>"Latvia","d_code"=>"+371");
        $countries[] = array("code"=>"LB","name"=>"Lebanon","d_code"=>"+961");
        $countries[] = array("code"=>"LS","name"=>"Lesotho","d_code"=>"+266");
        $countries[] = array("code"=>"LR","name"=>"Liberia","d_code"=>"+231");
        $countries[] = array("code"=>"LY","name"=>"Libya","d_code"=>"+218");
        $countries[] = array("code"=>"LI","name"=>"Liechtenstein","d_code"=>"+423");
        $countries[] = array("code"=>"LT","name"=>"Lithuania","d_code"=>"+370");
        $countries[] = array("code"=>"LU","name"=>"Luxembourg","d_code"=>"+352");
        $countries[] = array("code"=>"MO","name"=>"Macau","d_code"=>"+853");
        $countries[] = array("code"=>"MK","name"=>"Macedonia","d_code"=>"+389");
        $countries[] = array("code"=>"MG","name"=>"Madagascar","d_code"=>"+261");
        $countries[] = array("code"=>"MW","name"=>"Malawi","d_code"=>"+265");
        $countries[] = array("code"=>"MY","name"=>"Malaysia","d_code"=>"+60");
        $countries[] = array("code"=>"MV","name"=>"Maldives","d_code"=>"+960");
        $countries[] = array("code"=>"ML","name"=>"Mali","d_code"=>"+223");
        $countries[] = array("code"=>"MT","name"=>"Malta","d_code"=>"+356");
        $countries[] = array("code"=>"MH","name"=>"Marshall Islands","d_code"=>"+692");
        $countries[] = array("code"=>"MQ","name"=>"Martinique","d_code"=>"+596");
        $countries[] = array("code"=>"MR","name"=>"Mauritania","d_code"=>"+222");
        $countries[] = array("code"=>"MU","name"=>"Mauritius","d_code"=>"+230");
        $countries[] = array("code"=>"YT","name"=>"Mayotte","d_code"=>"+262");
        $countries[] = array("code"=>"MX","name"=>"Mexico","d_code"=>"+52");
        $countries[] = array("code"=>"MD","name"=>"Moldova","d_code"=>"+373");
        $countries[] = array("code"=>"MC","name"=>"Monaco","d_code"=>"+377");
        $countries[] = array("code"=>"MN","name"=>"Mongolia","d_code"=>"+976");
        $countries[] = array("code"=>"ME","name"=>"Montenegro","d_code"=>"+382");
        $countries[] = array("code"=>"MS","name"=>"Montserrat","d_code"=>"+1");
        $countries[] = array("code"=>"MA","name"=>"Morocco","d_code"=>"+212");
        $countries[] = array("code"=>"MZ","name"=>"Mozambique","d_code"=>"+258");
        $countries[] = array("code"=>"NA","name"=>"Namibia","d_code"=>"+264");
        $countries[] = array("code"=>"NR","name"=>"Nauru","d_code"=>"+674");
        $countries[] = array("code"=>"NP","name"=>"Nepal","d_code"=>"+977");
        $countries[] = array("code"=>"NL","name"=>"Netherlands","d_code"=>"+31");
        $countries[] = array("code"=>"AN","name"=>"Netherlands Antilles","d_code"=>"+599");
        $countries[] = array("code"=>"NC","name"=>"New Caledonia","d_code"=>"+687");
        $countries[] = array("code"=>"NZ","name"=>"New Zealand","d_code"=>"+64");
        $countries[] = array("code"=>"NI","name"=>"Nicaragua","d_code"=>"+505");
        $countries[] = array("code"=>"NE","name"=>"Niger","d_code"=>"+227");
        $countries[] = array("code"=>"NG","name"=>"Nigeria","d_code"=>"+234");
        $countries[] = array("code"=>"NU","name"=>"Niue","d_code"=>"+683");
        $countries[] = array("code"=>"NF","name"=>"Norfolk Island","d_code"=>"+672");
        $countries[] = array("code"=>"KP","name"=>"North Korea","d_code"=>"+850");
        $countries[] = array("code"=>"MP","name"=>"Northern Mariana Islands","d_code"=>"+1");
        $countries[] = array("code"=>"NO","name"=>"Norway","d_code"=>"+47");
        $countries[] = array("code"=>"OM","name"=>"Oman","d_code"=>"+968");
        $countries[] = array("code"=>"PK","name"=>"Pakistan","d_code"=>"+92");
        $countries[] = array("code"=>"PW","name"=>"Palau","d_code"=>"+680");
        $countries[] = array("code"=>"PS","name"=>"Palestine","d_code"=>"+970");
        $countries[] = array("code"=>"PA","name"=>"Panama","d_code"=>"+507");
        $countries[] = array("code"=>"PG","name"=>"Papua New Guinea","d_code"=>"+675");
        $countries[] = array("code"=>"PY","name"=>"Paraguay","d_code"=>"+595");
        $countries[] = array("code"=>"PE","name"=>"Peru","d_code"=>"+51");
        $countries[] = array("code"=>"PH","name"=>"Philippines","d_code"=>"+63");
        $countries[] = array("code"=>"PL","name"=>"Poland","d_code"=>"+48");
        $countries[] = array("code"=>"PT","name"=>"Portugal","d_code"=>"+351");
        $countries[] = array("code"=>"PR","name"=>"Puerto Rico","d_code"=>"+1");
        $countries[] = array("code"=>"QA","name"=>"Qatar","d_code"=>"+974");
        $countries[] = array("code"=>"CG","name"=>"Republic of the Congo","d_code"=>"+242");
        $countries[] = array("code"=>"RE","name"=>"Réunion" ,"d_code"=>"+262");
        $countries[] = array("code"=>"RO","name"=>"Romania","d_code"=>"+40");
        $countries[] = array("code"=>"RU","name"=>"Russia","d_code"=>"+7");
        $countries[] = array("code"=>"RW","name"=>"Rwanda","d_code"=>"+250");
        //$countries[] = array("code"=>"BL","name"=>"Saint Barthélemy" ,"d_code"=>"+590");
        $countries[] = array("code"=>"SH","name"=>"Saint Helena","d_code"=>"+290");
        $countries[] = array("code"=>"KN","name"=>"Saint Kitts and Nevis","d_code"=>"+1");
        //$countries[] = array("code"=>"MF","name"=>"Saint Martin","d_code"=>"+590");
        $countries[] = array("code"=>"PM","name"=>"Saint Pierre and Miquelon","d_code"=>"+508");
        $countries[] = array("code"=>"VC","name"=>"Saint Vincent and the Grenadines","d_code"=>"+1");
        $countries[] = array("code"=>"WS","name"=>"Samoa","d_code"=>"+685");
        $countries[] = array("code"=>"SM","name"=>"San Marino","d_code"=>"+378");
        $countries[] = array("code"=>"ST","name"=>"São Tomé and Príncipe" ,"d_code"=>"+239");
        $countries[] = array("code"=>"SA","name"=>"Saudi Arabia","d_code"=>"+966");
        $countries[] = array("code"=>"SN","name"=>"Senegal","d_code"=>"+221");
        $countries[] = array("code"=>"RS","name"=>"Serbia","d_code"=>"+381");
        $countries[] = array("code"=>"SC","name"=>"Seychelles","d_code"=>"+248");
        $countries[] = array("code"=>"SL","name"=>"Sierra Leone","d_code"=>"+232");
        $countries[] = array("code"=>"SG","name"=>"Singapore","d_code"=>"+65");
        $countries[] = array("code"=>"SK","name"=>"Slovakia","d_code"=>"+421");
        $countries[] = array("code"=>"SI","name"=>"Slovenia","d_code"=>"+386");
        $countries[] = array("code"=>"SB","name"=>"Solomon Islands","d_code"=>"+677");
        $countries[] = array("code"=>"SO","name"=>"Somalia","d_code"=>"+252");
        $countries[] = array("code"=>"ZA","name"=>"South Africa","d_code"=>"+27");
        $countries[] = array("code"=>"KR","name"=>"South Korea","d_code"=>"+82");
        $countries[] = array("code"=>"ES","name"=>"Spain","d_code"=>"+34");
        $countries[] = array("code"=>"LK","name"=>"Sri Lanka","d_code"=>"+94");
        $countries[] = array("code"=>"LC","name"=>"St. Lucia","d_code"=>"+1");
        $countries[] = array("code"=>"SD","name"=>"Sudan","d_code"=>"+249");
        $countries[] = array("code"=>"SR","name"=>"Suriname","d_code"=>"+597");
        $countries[] = array("code"=>"SZ","name"=>"Swaziland","d_code"=>"+268");
        $countries[] = array("code"=>"SE","name"=>"Sweden","d_code"=>"+46");
        $countries[] = array("code"=>"CH","name"=>"Switzerland","d_code"=>"+41");
        $countries[] = array("code"=>"SY","name"=>"Syria","d_code"=>"+963");
        $countries[] = array("code"=>"TW","name"=>"Taiwan","d_code"=>"+886");
        $countries[] = array("code"=>"TJ","name"=>"Tajikistan","d_code"=>"+992");
        $countries[] = array("code"=>"TZ","name"=>"Tanzania","d_code"=>"+255");
        $countries[] = array("code"=>"TH","name"=>"Thailand","d_code"=>"+66");
        $countries[] = array("code"=>"BS","name"=>"The Bahamas","d_code"=>"+1");
        $countries[] = array("code"=>"GM","name"=>"The Gambia","d_code"=>"+220");
        $countries[] = array("code"=>"TL","name"=>"Timor-Leste","d_code"=>"+670");
        $countries[] = array("code"=>"TG","name"=>"Togo","d_code"=>"+228");
        $countries[] = array("code"=>"TK","name"=>"Tokelau","d_code"=>"+690");
        $countries[] = array("code"=>"TO","name"=>"Tonga","d_code"=>"+676");
        $countries[] = array("code"=>"TT","name"=>"Trinidad and Tobago","d_code"=>"+1");
        $countries[] = array("code"=>"TN","name"=>"Tunisia","d_code"=>"+216");
        $countries[] = array("code"=>"TR","name"=>"Turkey","d_code"=>"+90");
        $countries[] = array("code"=>"TM","name"=>"Turkmenistan","d_code"=>"+993");
        $countries[] = array("code"=>"TC","name"=>"Turks and Caicos Islands","d_code"=>"+1");
        $countries[] = array("code"=>"TV","name"=>"Tuvalu","d_code"=>"+688");
        $countries[] = array("code"=>"UG","name"=>"Uganda","d_code"=>"+256");
        $countries[] = array("code"=>"UA","name"=>"Ukraine","d_code"=>"+380");
        $countries[] = array("code"=>"AE","name"=>"United Arab Emirates","d_code"=>"+971");
        $countries[] = array("code"=>"GB","name"=>"United Kingdom","d_code"=>"+44");
        $countries[] = array("code"=>"US","name"=>"United States","d_code"=>"+1");
        $countries[] = array("code"=>"UY","name"=>"Uruguay","d_code"=>"+598");
        $countries[] = array("code"=>"VI","name"=>"US Virgin Islands","d_code"=>"+1");
        $countries[] = array("code"=>"UZ","name"=>"Uzbekistan","d_code"=>"+998");
        $countries[] = array("code"=>"VU","name"=>"Vanuatu","d_code"=>"+678");
        $countries[] = array("code"=>"VA","name"=>"Vatican City","d_code"=>"+39");
        $countries[] = array("code"=>"VE","name"=>"Venezuela","d_code"=>"+58");
        $countries[] = array("code"=>"VN","name"=>"Vietnam","d_code"=>"+84");
        $countries[] = array("code"=>"WF","name"=>"Wallis and Futuna","d_code"=>"+681");
        $countries[] = array("code"=>"YE","name"=>"Yemen","d_code"=>"+967");
        $countries[] = array("code"=>"ZM","name"=>"Zambia","d_code"=>"+260");
        $countries[] = array("code"=>"ZW","name"=>"Zimbabwe","d_code"=>"+263");

        return $countries;
    }

    /**
    * Add additional custom field
    */

    public function mapi_show_additional_profile_fields ( $user )
    {
    ?>
        <h3>Additional profile information</h3>
        <table class="form-table">
            <tr>
                <th><label for="phone">Mobile number</label></th>
                <td>
                <input type="hidden" name="whatsiplus_nonce" value="<?php echo esc_attr( wp_create_nonce( 'whatsiplus_send_sms_action' ) ); ?>" />
                    <input type="text" name="phone" placeholder="1234567890" id="phone" value="<?php echo esc_attr( get_the_author_meta( 'phone', $user->ID ) ); ?>" class="regular-text" /><br />
                    <span class="description">Please enter your phone number.</span>
                </td>
            </tr>
            <tr>
                <th><label for="country">Country</label></th>
                <td>
                    <select name="country" class="specific_number_prefix">
                        <?php foreach ($this->mapi_getCountryList() as $country) { ?>
                        <option data-country-code="<?php echo esc_attr(strtolower($country['d_code'])); ?>"
                                value="<?php echo esc_attr(strtolower($country['code'])); ?>"
                                <?php
                                    if(!empty(get_the_author_meta('country', $user->ID))) {
                                        echo (
                                            strtolower($country['code'])==get_the_author_meta('country', $user->ID)
                                            ) ? esc_attr('selected=selected') : '';
                                    }
                                    else {
                                        echo (!empty($country['code']) && $country['code'] == 'US') ? esc_attr('selected=selected') : '';
                                    }
                                ?> >
                                <?php echo esc_attr($country['name']) ?>
                        </option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
        </table>
    <?php
    }


    public function mapi_save_additional_profile_fields( $user_id )
    {
        if ( !current_user_can( 'edit_user', $user_id ) )
            return false;

        $nonce = isset( $_POST['whatsiplus_nonce'] ) ? sanitize_text_field( wp_unslash($_POST['whatsiplus_nonce']) ) : '';
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'whatsiplus_send_sms_action' ) ) {
            return;
        }

        /* Copy and paste this line for additional fields. Make sure to change 'phone' to the field ID. */
        if(isset($_POST['phone']) && isset($_POST['country'])) {
            $post_phone = sanitize_text_field(wp_unslash($_POST['phone']));
            $post_country = sanitize_text_field(wp_unslash($_POST['country']));

            if(!empty($post_phone) && ctype_digit($post_phone))
                update_user_meta( $user_id, 'phone', $post_phone );

            if(!empty($post_country))
                update_user_meta( $user_id, 'country', $post_country );
        }
    }


    public function validate_additional_fields($errors,$update,$user){

        // Check if user has authority to change this
        if(!current_user_can('edit_user',$user->ID)){
            $errors->add("permission_denied","You do not have permission to update this page");
        }

        $nonce = isset( $_POST['whatsiplus_nonce'] ) ? sanitize_text_field( wp_unslash($_POST['whatsiplus_nonce']) ) : '';
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'whatsiplus_send_sms_action' ) ) {
            return;
        }


        // Validate Phone Number
        if(isset($_POST['phone'])) {
            $phone_number1 = sanitize_text_field(wp_unslash( $_POST['phone']) );
            if(empty($phone_number1)){
                $errors->add("phone","Mobile number - Cannot be empty");
            }
            else if (!is_numeric( $phone_number1 ))
                $errors->add("phone", "Mobile Number - Please enter digits only");

            }

        if(isset($_POST['country'])) {
            if(empty($_POST['country']))
                $errors->add("country", "Country - Please select country");
        }

    }

    public function mapi_display_phone_field()
    {
    ?>
        <p>
        <label>Mobile number<br/>
        <input type="hidden" name="whatsiplus_nonce" value="<?php echo esc_attr( wp_create_nonce( 'whatsiplus_send_sms_action' ) ); ?>" />
        <input id="phone" type="text" placeholder="1234567890" tabindex="30" size="25" name="phone" />
        </label>
        </p>
    <?php
    }

    public function mapi_display_country_field() {
    ?>
        <p>
        <label>Country<br/>
        <input type="hidden" name="whatsiplus_nonce" value="<?php echo esc_attr(wp_create_nonce( 'whatsiplus_send_sms_action' )); ?>" />
            <select name="country" class="specific_number_prefix">
                <?php foreach ($this->mapi_getCountryList() as $country) { ?>
                <option data-country-code="<?php echo esc_attr(strtolower($country['d_code'])); ?>"
                        value="<?php echo esc_attr(strtolower($country['code'])); ?>"
                        <?php
                            if(!empty(whatsiplus_get_options('whatsiplus_woocommerce_country_code', 'whatsiplus_setting', '' ))) {
                                echo (
                                    strtolower($country['code'])==whatsiplus_get_options('whatsiplus_woocommerce_country_code', 'whatsiplus_setting', '' )
                                    ) ? esc_attr('selected=selected') : '';
                            }
                            else {
                                echo (!empty($country['code']) && $country['code'] == 'US') ? esc_attr('selected=selected') : '';
                            }
                        ?> >
                <?php echo esc_html( $country['name'] ); ?>
                </option>
                <?php } ?>
            </select>
        </label>
        </p>
    <?php
    }

    public function mapi_validate_fields ( $login, $email, $errors )
    {
        $nonce = isset( $_POST['whatsiplus_nonce'] ) ? sanitize_text_field( wp_unslash($_POST['whatsiplus_nonce']) ) : '';
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'whatsiplus_send_sms_action' ) ) {
            return;
        }
        

        global $whatsiplus_phone;
        if(isset($_POST['phone'])){

            $post_phone = sanitize_text_field(wp_unslash($_POST['phone']));
            if ( $post_phone == '' )
            {
                $errors->add( 'empty_realname', "<strong>ERROR</strong>: Please Enter your phone number" );
            }
            else
            {
                $whatsiplus_phone = $post_phone;
            }
        }
    }

    public function mapi_register_additional_fields ( $user_id, $password = "", $meta = array() )
    {
        $nonce = isset( $_POST['whatsiplus_nonce'] ) ? sanitize_text_field(wp_unslash( $_POST['whatsiplus_nonce'] )) : '';
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'whatsiplus_send_sms_action' ) ) {
            return;
        }
        
        $post_phone = sanitize_text_field(wp_unslash($_POST['phone']));
        update_user_meta( $user_id, 'phone', $post_phone );
        $post_country = sanitize_text_field(wp_unslash($_POST['country']));
        update_user_meta( $user_id, 'country', $post_country );
    }

    public function display_send_sms_success()
    {
        $nonce = isset( $_POST['whatsiplus_nonce'] ) ? sanitize_text_field( wp_unslash($_POST['whatsiplus_nonce'] )) : '';
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'whatsiplus_send_sms_action' ) ) {
            return;
        }

        if( !isset($_GET['sms_sent']) ) { return; }
        ?>
        <div class="notice notice-success is-dismissible">
        <p><?php esc_html_e( 'Message Sent!', 'whatsiplus-order-notification-for-woocommerce' ); ?></p>
        </div>
        <?php
    }

    public function add_removable_arg($args)
    {
        array_push($args, 'sms_sent');
        return $args;
    }

    function enqueue_admin_custom_scripts() {
        wp_register_script( 'admin-split-sms-js', plugins_url( 'js/split-sms.js', __FILE__ ), array(), '0.1.7', true );
        wp_enqueue_script( 'admin-split-sms-js' );
    }
    

    public function my_custom_scripts3() {
        // Register the script
        wp_register_script('custom-script3', plugin_dir_url(__DIR__) . 'js/custom-script1.js', array('jquery'), null, true);

        // Prepare PHP data to pass to the script
        $roles_arr = array();
        $um_arr = array();
        $pmpro_arr = array();
        $country_arr = array();
        $available_filters = array("roles", "country");

        // Populate roles
        foreach (get_editable_roles() as $key => $value) {
            $roles_arr[$key] = $value['name'];
        }

        foreach ($this->mapi_getCountryList() as $country) {
            $country_arr[$country['code']] = $country['name'];
        }

        // Populate ultimate member status
        if (function_exists('is_ultimatemember')) {
            $available_filters[] = "status";
            $cache_key = 'um_account_status';
            $um_arr = wp_cache_get($cache_key, 'um_account_status');

            if (!$um_arr) {
                $user_meta_query = new WP_User_Query(array(
                    'meta_key' => 'account_status',
                    'fields'   => 'ID',
                    'orderby'  => 'meta_value',
                    'order'    => 'ASC',
                ));
                $users = $user_meta_query->get_results();

                $um_arr = array();
                foreach ($users as $user_id) {
                    $status = get_user_meta($user_id, 'account_status', true);
                    if (!empty($status)) {
                        $um_arr[$status] = $status;
                    }
                }

                wp_cache_set($cache_key, $um_arr, 'um_account_status');
            }
        }

        // Populate PMPro
        if (function_exists('pmpro_hasMembershipLevel')) {
            $available_filters[] = "membership_level";
            $cache_key = 'pmpro_membership_levels';
            $pmpro_arr = wp_cache_get($cache_key, 'pmpro_membership_levels');

            if (!$pmpro_arr) {
                $levels = pmpro_getAllLevels(true, true);
                $pmpro_arr = array();
                foreach ($levels as $level) {
                    $pmpro_arr[$level->id] = $level->name;
                }

                wp_cache_set($cache_key, $pmpro_arr, 'pmpro_membership_levels');
            }
        }

        // Localize the script with new data
        $script_data = array(
            'filter_by_arr' => $available_filters,
            'criteria_array' => array(
                'roles' => $roles_arr,
                'country' => $country_arr,
                'status' => $um_arr,
                'membership_level' => $pmpro_arr,
            ),
            'prefixCheckEnabled' => get_option('smsbump_PhoneNumberPrefix') === 'yes',
            'prefixCheck' => get_option('smsbump_StrictNumberPrefix')
        );

        wp_localize_script('custom-script3', 'wp_localize_script_data', $script_data);

        // Enqueue the script
        wp_enqueue_script('custom-script3');
    }
    

    public function load_scripts()
    {

        add_action( 'admin_enqueue_scripts', 'enqueue_admin_custom_scripts' );

    }

}

?>
