<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsiPLUS_SendSMS_Sms {

	public static function send_sms($sms_from, $phone_no, $message, $medium='wordpress_order_notification') {
        if(empty($phone_no)) {
            return;
        }

        $medium='wordpress_order_notification';
	    $log = new Whatsiplus_WooCommerce_Logger();

	    $api_key = whatsiplus_get_options('whatsiplus_woocommerce_api_key', 'whatsiplus_setting');
	    $api_secret = whatsiplus_get_options('whatsiplus_woocommerce_api_secret', 'whatsiplus_setting');
	    $sms_sender = whatsiplus_get_options('whatsiplus_woocommerce_sms_from', 'whatsiplus_setting');

	    if($api_key == '' || $api_key == '') return;
        $sms_from = !empty($sms_from) ? $sms_from : (!empty($sms_sender) ? $sms_sender : "Whatsiplus");

	    //$log->add('Whatsiplus', 'Sending SMS to '.$phone_no.', message: '.$message);

	    try {
	        $whatsiplus_rest = new WhatsiPLUS($api_key, $api_secret);
	        $rest_response = $whatsiplus_rest->sendSMS($sms_from, $phone_no, $message, $medium);

	        self::insertToOutbox($sms_from, $phone_no, $message, "Message sent");

	        $log->add('Whatsiplus', 'response from Whatsiplus gateway: ' .$rest_response);

	  		return 'true';

	    } catch (Exception $e) {
	        $log->add('Whatsiplus', 'Failed sent SMS: ' . $e->getMessage());
	    }

	}

	public static function whatsiapi_get_account_balance($api_key, $api_secret){

	    $whatsiplus_rest = new WhatsiPLUS($api_key, $api_secret);
	    $rest_response = $whatsiplus_rest->accountBalance();

	    $rest_response = json_decode($rest_response);

	    if($rest_response->{'status'} == 0){
			return $rest_response->{'value'};
	    }
	}

	public static function getPhoneNumber($message_to, $customer, $phone, $country, $filters='', $criteria=''){
        // Validate phone numbers here

		switch($message_to) {
		    case "customer_all":
                $numbers = self::getValidatedPhoneNumbers(get_users());
		    	#$numbers = self::getAllUsersPhones();
		    	break;
		    case "customer":
		    	$numbers = self::getValidatedPhoneNumbers($customer);
		    	// $numbers = self::getSpecificCustomerPhones($customer);
		    	break;
		    case "spec_group_ppl":
		    	$numbers = self::getFilteredUsers($filters, $criteria);
		    	// $numbers = self::getSpecificCustomerPhones($customer);
		    	break;
		    case "phones":
		    	$numbers = self::getUsersPhones($phone);
		    	break;
		    default: break;
		}

		return $numbers;
	}

    public static function getFilteredUsers($filters, $criteria) {

        $filtered_users = array();

        // get all users
        // filter them using filters and criteria
        if($filters == 'roles') {

            $args = array(
                'role__in' => $criteria,
            );

            $filtered_users = get_users($args);

        }

        if($filters == 'country') {

            $args = array(
                'meta_key' => 'country',
                'meta_value' => $criteria,
            );

            $filtered_users = get_users($args);

        }

        if ($filters == 'status') {
            $args = array(
                'meta_key' => 'account_status',
                'meta_value' => $criteria,
            );

            $filtered_users = get_users($args);
        }

        if ($filters == 'membership_level') {
            global $wpdb;
            #$wpdb->prepare($sql_query, implode(', ', $criteria));
            $sql_query = ' SELECT user_id FROM wp_pmpro_memberships_users WHERE membership_id IN (%s) ';
            $results = $wpdb->get_results($wpdb->prepare($sql_query, implode(', ', $criteria)));

            foreach($results as $result) {
                $filtered_users[] = get_user_by("ID", $result->user_id);
            }

        }

        return self::getValidatedPhoneNumbers($filtered_users);
    }

    public static function getValidatedPhoneNumbers($users) {
        $validatedUsers = array();
        if($users) {
            if(is_array($users)) {
                foreach ($users as $user) {
                    if(!($user instanceof WP_User)) {
                        $user = get_user_by('ID', $user);
                    }

                    $phone = self::get_formatted_number($user->phone, $user->country);

                    if ($phone) {
                        $user->phone = $phone;
                        array_push($validatedUsers, $user);
                    }
                }
            }
            else {
                $phone = self::get_formatted_number($users->phone, $users->country);

                if($phone) {
                    $users->phone = $phone;
                    return $users;
                }
            }
        }

        return $validatedUsers;
    }

    public static function get_formatted_number($phone, $country = '') {
        $log = new Whatsiplus_WooCommerce_Logger();
        
        $settings_country = whatsiplus_get_options('whatsiplus_woocommerce_country_code', 'whatsiplus_setting', '' );
        if(empty($settings_country) && $country=='')
            return $phone;

        $country = !empty($country) ? $country : $settings_country;

        $country= self::get_country_dialing_code($country);
        
        /*
        $country_length = strlen($country);
        $phone_prefix = substr($phone, 0, $country_length);
        $log->add( 'Whatsiplus', 'phone_prefix: '.$phone_prefix );
        if ($phone_prefix === $country) {
            $FinalPhone = $phone;
            $FinalPhone = preg_replace('/\D/', '', $FinalPhone); //just number
            $log->add( 'Whatsiplus', 'FinalPhone: '.$FinalPhone );
		    return $FinalPhone;
        }
        */

        $FinalPhone = $phone;
        if (substr($phone, 0, 2) === '00') {
            $FinalPhone = substr($phone, 2); //remove 00
        } elseif (substr($phone, 0, 1) === '+') {
            $FinalPhone = substr($phone, 1); //remove +
        } elseif (substr($phone, 0, 1) === '0') {
            $phone = ltrim($phone, '0');
            $FinalPhone = $country . $phone;
        }

		//$log->add( 'Whatsiplus', 'Phone Created: '.$FinalPhone );
		$FinalPhone = preg_replace('/\D/', '', $FinalPhone); //just number
        //$log->add( 'Whatsiplus', 'FinalPhone: '.$FinalPhone );
		return $FinalPhone;

    }

	private static function insertToOutbox($sender,$recipient,$message,$status){
		global $wpdb;

		$db = $wpdb;

		return $db->insert(
			WHATSI_DB_TABLE_NAME,
			array(
				'sender'    => $sender,
				'message'   => $message,
				'recipient' => $recipient,
                'status'    => $status,
			)
		);
	}

	private static function getUsersPhones($phone_number)
	{
		$phone_number = explode(",", $phone_number);
		$phones = array();
		foreach ($phone_number as $phone) {
		 	$phones[] = $phone;
		}
		return $phones;
	}

    public static function get_country_dialing_code($country_code)
    {
        $country_code=strtoupper($country_code);
        $country_codes= array(
            "AF" => "93",
            "AL" => "355",
            "DZ" => "213",
            "AS" => "1684",
            "AD" => "376",
            "AO" => "244",
            "AI" => "1264",
            "AQ" => "672",
            "AG" => "1268",
            "AR" => "54",
            "AM" => "374",
            "AW" => "297",
            "AU" => "61",
            "AT" => "43",
            "AZ" => "994",
            "BS" => "1242",
            "BH" => "973",
            "BD" => "880",
            "BB" => "1246",
            "BY" => "375",
            "BE" => "32",
            "BZ" => "501",
            "BJ" => "229",
            "BM" => "1441",
            "BT" => "975",
            "BO" => "591",
            "BA" => "387",
            "BW" => "267",
            "BV" => "47",
            "BR" => "55",
            "IO" => "246",
            "BN" => "673",
            "BG" => "359",
            "BF" => "226",
            "BI" => "257",
            "KH" => "855",
            "CM" => "237",
            "CA" => "1",
            "CV" => "238",
            "KY" => "1345",
            "CF" => "236",
            "TD" => "235",
            "CL" => "56",
            "CN" => "86",
            "CX" => "61",
            "CC" => "61",
            "CO" => "57",
            "KM" => "269",
            "CG" => "242",
            "CD" => "243",
            "CK" => "682",
            "CR" => "506",
            "CI" => "225",
            "HR" => "385",
            "CU" => "53",
            "CY" => "357",
            "CZ" => "420",
            "DK" => "45",
            "DJ" => "253",
            "DM" => "1767",
            "DO" => "1809",
            "EC" => "593",
            "EG" => "20",
            "SV" => "503",
            "GQ" => "240",
            "ER" => "291",
            "EE" => "372",
            "ET" => "251",
            "FK" => "500",
            "FO" => "298",
            "FJ" => "679",
            "FI" => "358",
            "FR" => "33",
            "GF" => "594",
            "PF" => "689",
            "TF" => "262",
            "GA" => "241",
            "GM" => "220",
            "GE" => "995",
            "DE" => "49",
            "GH" => "233",
            "GI" => "350",
            "GR" => "30",
            "GL" => "299",
            "GD" => "1473",
            "GP" => "590",
            "GU" => "1671",
            "GT" => "502",
            "GN" => "224",
            "GW" => "245",
            "GY" => "592",
            "HT" => "509",
            "HM" => "672",
            "VA" => "379",
            "HN" => "504",
            "HK" => "852",
            "HU" => "36",
            "IS" => "354",
            "IN" => "91",
            "ID" => "62",
            "IR" => "98",
            "IQ" => "964",
            "IE" => "353",
            "IL" => "972",
            "IT" => "39",
            "JM" => "1876",
            "JP" => "81",
            "JO" => "962",
            "KZ" => "7",
            "KE" => "254",
            "KI" => "686",
            "KP" => "850",
            "KR" => "82",
            "KW" => "965",
            "KG" => "996",
            "LA" => "856",
            "LV" => "371",
            "LB" => "961",
            "LS" => "266",
            "LR" => "231",
            "LY" => "218",
            "LI" => "423",
            "LT" => "370",
            "LU" => "352",
            "MO" => "853",
            "MK" => "389",
            "MG" => "261",
            "MW" => "265",
            "MY" => "60",
            "MV" => "960",
            "ML" => "223",
            "MT" => "356",
            "MH" => "692",
            "MQ" => "596",
            "MR" => "222",
            "MU" => "230",
            "YT" => "262",
            "MX" => "52",
            "FM" => "691",
            "MD" => "373",
            "MC" => "377",
            "MN" => "976",
            "MS" => "1664",
            "MA" => "212",
            "MZ" => "258",
            "MM" => "95",
            "NA" => "264",
            "NR" => "674",
            "NP" => "977",
            "NL" => "31",
            "AN" => "599",
            "NC" => "687",
            "NZ" => "64",
            "NI" => "505",
            "NE" => "227",
            "NG" => "234",
            "NU" => "683",
            "NF" => "672",
            "MP" => "1670",
            "NO" => "47",
            "OM" => "968",
            "PK" => "92",
            "PW" => "680",
            "PS" => "970",
            "PA" => "507",
            "PG" => "675",
            "PY" => "595",
            "PE" => "51",
            "PH" => "63",
            "PN" => "870",
            "PL" => "48",
            "PT" => "351",
            "PR" => "1787",
            "QA" => "974",
            "RE" => "262",
            "RO" => "40",
            "RU" => "7",
            "RW" => "250",
            "SH" => "290",
            "KN" => "1869",
            "LC" => "1758",
            "PM" => "508",
            "VC" => "1784",
            "WS" => "685",
            "SM" => "378",
            "ST" => "239",
            "SA" => "966",
            "SN" => "221",
            "CS" => "381",
            "SC" => "248",
            "SL" => "232",
            "SG" => "65",
            "SK" => "421",
            "SI" => "386",
            "SB" => "677",
            "SO" => "252",
            "ZA" => "27",
            "GS" => "500",
            "ES" => "34",
            "LK" => "94",
            "SD" => "249",
            "SR" => "597",
            "SJ" => "47",
            "SZ" => "268",
            "SE" => "46",
            "CH" => "41",
            "SY" => "963",
            "TW" => "886",
            "TJ" => "992",
            "TZ" => "255",
            "TH" => "66",
            "TL" => "670",
            "TG" => "228",
            "TK" => "690",
            "TO" => "676",
            "TT" => "1868",
            "TN" => "216",
            "TR" => "90",
            "TM" => "993",
            "TC" => "1649",
            "TV" => "688",
            "UG" => "256",
            "UA" => "380",
            "AE" => "971",
            "GB" => "44",
            "US" => "1",
            "UM" => "1800",
            "UY" => "598",
            "UZ" => "998",
            "VU" => "678",
            "VE" => "58",
            "VN" => "84",
            "VG" => "1284",
            "VI" => "1340",
            "WF" => "681",
            "EH" => "212",
            "YE" => "967",
            "ZM" => "260",
            "ZW" => "263"
        );

        if (array_key_exists($country_code, $country_codes)) {
            return $country_codes[$country_code];
        } else {
            
            return "0";
        }
    }
}