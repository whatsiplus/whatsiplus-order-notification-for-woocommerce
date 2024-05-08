<?php

namespace WhatsiAPI_WC;

use WhatsiAPI_WC\Forms\Handlers\ContactForm7;
use WhatsiAPI_WC\Migrations\MigrateSendSMSPlugin;
use WhatsiAPI_WC\Migrations\MigrateWoocommercePlugin;

class Loader {

    public static function load()
    {
        new ContactForm7();

        // load Migrations
        MigrateWoocommercePlugin::migrate();
        MigrateSendSMSPlugin::migrate();
    }
}
