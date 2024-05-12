<?php
namespace WhatsiAPI_WC\Helpers;
class Utils {
    // Define a static function to fetch contents from a URL using wp_remote_get instead of cURL
    public static function wp_remote_get_file_contents($URL) {
        $response = wp_remote_get($URL);
        if (!is_wp_error($response) && $response['response']['code'] == 200) {
            return wp_remote_retrieve_body($response);
        } else {
            return "";
        }
    }
}

