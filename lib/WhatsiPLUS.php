<?php

/*
 * Integration with WhatsiPLUS RESTful API
 *
 * Class methods:
 *      sendSMS($to, $from, $message, $message_type, $dlr_url, $udh)
 *      receiveDLR()
 *      receiveMO()
 *      messageStatus($msgid)
 *      accountBalance()
 *      accountPricing($mcc, $mnc)
 */

class WhatsiPLUS {

    // Account credentials
    // Account credentials
    private $api_key = '';
    private $api_secret = '';
    private $log;

    // REST API URL
    public $rest_base_url = "https://api.whatsiplus.com/";
    public $actual_api_url;

    private $rest_commands = array (
        'send_sms' => array('url' => '/sendMsg/', 'method' => 'POST'),
        'get_message_status' => array('url' => '', 'method' => 'GET'),
        'get_balance' => array('url' => '/serviceSettings/', 'method' => 'GET'),
        'get_pricing' => array('url' => '', 'method' => 'GET')
    );

    public $response_format = 'json';

    public $message_type_option = array('7-bit' => 1, '8-bit' => 2, 'Unicode' => 3);

    public function __construct($api_key = null, $api_secret = null)
    {
        $this->log = new Whatsiplus_WooCommerce_Logger();
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
        $this->actual_api_url = $this->rest_base_url;
    }

    public function setApiUrl($use_domain = true)
    {
        $this->actual_api_url = $this->rest_base_url;
    }
    

    public function sendSMS($from, $to, $message, $medium, $message_type = null, $dlr_url = null, $udh = null)
    {
        $this->log->add("Whatsiplus", "Message Sent to {$to}: {$message} ");
        // Send request to WhatsiPLUS gateway

        $params = array(
            'phonenumber' => $to,
            'message' => $message
        );
        return $this->invokeApi('send_sms', $params);
    }

    public function receiveDLR($data)
    {
        $delivery_status = array(1 => 'Success', 2 => 'Failed', 3 => 'Expired');

        $delivery_report_data = new stdClass();
        $delivery_report_data->from = $data['whatsi-from'];
        $delivery_report_data->to = $data['whatsi-to'];
        $delivery_report_data->dlr_status = $delivery_status[$data['whatsi-dlr-status']];
        $delivery_report_data->msgid = $data['whatsi-msgid'];
        $delivery_report_data->error_code = $data['whatsi-error-code'];
        $delivery_report_data->dlr_received_time = gmdate('Y-m-d H:i:s');


        return $delivery_report_data;
    }

    public function receiveMO($data)
    {
        $mo_message = new stdClass();
        $mo_message->from = $data['whatsi-from'];
        $mo_message->to = $data['whatsi-to'];
        $mo_message->keyword = $data['whatsi-keyword'];
        $mo_message->text = $data['whatsi-text'];
        $mo_message->coding = $data['whatsi-coding'];
        $mo_message->time = $data['whatsi-time'];

        if($mo_message->coding == $this->message_type_option['Unicode']) {
            $mo_message->keyword = $this->utf16HexToUtf8($mo_message->keyword);
            $mo_message->text = $this->utf16HexToUtf8($mo_message->text);
        }

        return $mo_message;
    }

    public function messageStatus($msgid)
    {
        $params = array('whatsi-msgid' => $msgid);
        return $this->invokeApi ('get_message_status', $params);
    }

    public function accountBalance()
    {
        return $this->invokeApi ('get_balance');
    }

    public function accountPricing($mcc = null, $mnc = null)
    {
        $params = array();
        if($mcc) {
            $params['whatsi-mcc'] = $mcc;
        }
        if($mnc) {
            $params['whatsi-mnc'] = $mnc;
        }
        return $this->invokeApi ('get_pricing', $params);
    }

    private function invokeApi($command, $params = array())
{
    if (get_option("whatsiplus_domain_reachable")) {
        $this->setApiUrl(true);
    } else {
        $this->setApiUrl(false);
    }

    // Get REST URL and HTTP method
    $command_info = $this->rest_commands[$command];
    $url = $this->actual_api_url;
    $method = $command_info['method'];

    // Build the request URL
    $request_url = $url . $command_info['url'] . $this->api_key;

    // Set up request parameters
    $request_args = array(
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded', // Adjust content type if needed
        ),
    );

    if ($method === 'POST') {
        $request_args['body'] = $params;
    } else {
        $request_url .= '?' . http_build_query($params);
    }

    // Make the request using wp_remote_get()
    $response = wp_remote_get($request_url, $request_args);

    // Check for errors
    if (is_wp_error($response)) {
        throw new Exception('WP error: ' . $response->get_error_message());
    }

    // Get the response body
    $rest_response = wp_remote_retrieve_body($response);

    return $rest_response;
}

    private function utf16HexToUtf8($string)
    {
        if(strlen($string) % 4) {
            $string = '00'.$string;
        }

        $converted_string = '';
        $string_length = strlen($string);
        for($counter = 0; $counter < $string_length; $counter += 4) {
            $converted_string .= "&#".hexdec(substr($string, $counter, 4)).";";
        }
        $converted_string = mb_convert_encoding($converted_string, "UTF-8", "HTML-ENTITIES");

        return $converted_string;
    }
}
?>
