=== Whatsiplus Order Notification for WooCommerce ===
Contributors: whatsiplus
Tags: whatsiplus, woocommerce, multivendor, notification, whatsapp
Requires at least: 3.8
Tested up to: 6.8
Stable tag: 1.1.6
Requires PHP: 5.6
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

WhatsApp notification plugin for WooCommerce:Notify buyers & sellers on order placement and status changes.send messages to users outside WooCommerce

== Description ==

This plugin sends order notifications via WhatsApp using the Whatsiplus service.

### Third-party Service ###
This plugin relies on an external service for sending WhatsApp messages. Therefore, it uses **[Whatsiplus Service API](https://whatsiplus.com)** to handle message delivery.

- **Service URL:** https://api.whatsiplus.com/
- **Links used in the plugin to send messages:** https://api.whatsiplus.com/sendMsg
- **Links used in the plugin to set default country code:** https://api.whatsiplus.com/serviceSettings
- **API documentation:** [docs.whatsiplus.com](https://docs.whatsiplus.com)
- **Terms of Service:** 
  - https://whatsiplus.com/terms-and-conditions/
  - https://whatsiplus.com/privacy-policy/
- **Main website:** [whatsiplus.com](https://whatsiplus.com/)

### WhatsApp Order Notification WooCommerce ###

**Send WhatsApp Notification**: This plugin serves as a WooCommerce add-on, but you can access its functionalities independently. For instance, you can send **WhatsApp messages** and **notifications** to various user groups.

This plugin helps you do your work with minimal effort and automatically send predefined WhatsApp messages to your customers.

With this plugin, you can automatically send WhatsApp order notifications to your customers (buyer) and managers (seller/employees) whenever a new order is placed. In addition, if the status of the order changes, the customer will immediately receive a WhatsApp message regarding the status of the order.

Try it for free. For 10 days, use all the features of the plugin, including sending unlimited WhatsApp messages to your users.

### Key Features ###

* Notify seller whenever a new order is placed.
* Inform buyer the current order status / whenever order status is changed.
* All WooCommerce order statuses are supported (including Custom WooCommerce Order Statuses)
* Message templates can be saved to reuse in the future.
* Placeholder tags are supported to personalize your message. For eg: [shop_name], [order_id], [order_amount], [order_status], [order_product], [payment_method], [bank_details]
* Custom checkout field added from Woo Checkout Field Editor Pro is supported.
* Notify vendor whenever there’s new order
* Notify vendor when sub order status changed
* Notify Admin when product stock is low
* Multivendor Plugins Supported / Integrated.
* Reservation / Booking / Appointment Plugins Supported
* Membership Plugins Supported
* CRM Plugins Supported
* Forms Plugins Supported

### Multivendor Plugins Supported / Integrated. ###

*   [Woocommerce Product Vendors](https://woocommerce.com/products/product-vendors/)
*   [MultivendorX formerly WC Marketplace](https://wordpress.org/plugins/dc-woocommerce-multi-vendor/)
*   [WC Vendors Marketplace](https://wordpress.org/plugins/wc-vendors/)
*   [WooCommerce Multivendor Marketplace (WCFM Marketplace)](https://wordpress.org/plugins/wc-multivendor-marketplace/)
*   [Dokan](https://wordpress.org/plugins/dokan-lite/)
*   [YITH WooCommerce Multi Vendor](https://wordpress.org/plugins/yith-woocommerce-product-vendors/)

You'll be able to Send WhatsApp Order Notification to vendors & buyers

### Integrations Supported ###

🥡 Supported Reservation / Booking / Appointment Plugins:

*   [Five Star Restaurant Reservations – WordPress Booking Plugin](https://wordpress.org/plugins/restaurant-reservations/).
*   [Booking Calendar | Appointment Booking | BookIt](https://wordpress.org/plugins/bookit/).
*   [Quick Restaurant Reservations](https://wordpress.org/plugins/quick-restaurant-reservations/).
*   [LatePoint - Appointment Booking & Reservation](https://codecanyon.net/item/latepoint-appointment-booking-reservation-plugin-for-wordpress/22792692)
*   [FAT Service Booking](https://codecanyon.net/item/fat-services-booking-automated-booking-and-online-scheduling/24214247)

You'll be able to send WhatsApp booking notifications to your customers when booking status changes.

🧑‍🤝‍🧑 Supported Membership Plugins:

*   [ARMember – Membership Plugin](https://wordpress.org/plugins/armember-membership/)
*   [MemberMouse](https://membermouse.com/)
*   [MemberPress](https://memberpress.com/)
*   [S2Members](https://wordpress.org/plugins/s2member/)
*   [Simple Membership](https://wordpress.org/plugins/simple-membership)

You'll be able to send membership WhatsApp notification & reminders to your members.

💰 Supported CRM Plugins:

*   [Jetpack CRM](https://wordpress.org/plugins/zero-bs-crm/)
*   [Groundhogg CRM](https://wordpress.org/plugins/groundhogg/)
*   [Fluent CRM](https://wordpress.org/plugins/fluent-crm/)
*   [WP ERP CRM](https://wordpress.org/plugins/erp/)

You'll be able to send notifications to your contacts / leads when their status changed.

📝 Supported Forms Plugins:

*   [Contact Form 7](https://wordpress.org/plugins/contact-form-7/)

You'll be able to send notifications to customer when a new form is submitted.

*   [Custom Order Status for WooCommerce](https://wordpress.org/plugins/custom-order-statuses-woocommerce/)
*   [Ni WooCommerce Custom Order Status](https://wordpress.org/plugins/ni-woocommerce-custom-order-status/)
*   [Ultimate Member – User Profile, User Registration, Login & Membership Plugin](https://wordpress.org/plugins/ultimate-member/)
*   [Members – Membership & User Role Editor Plugin](https://wordpress.org/plugins/members/)
*   [Paid Memberships Pro](https://wordpress.org/plugins/paid-memberships-pro/)


== Quick Setup ==

1. Go to the "Whatsiplus Settings" tab and paste your API Key in the provided field.
2. Navigate to the "Admin Settings and Customer Settings" tab.
3. Check the "Enable" box to activate notifications.
4. Select your desired notification types by checking the related options.
5. Click the "Save" button to apply the changes.
6. Customize your message content in the designated textboxes using supported shortcodes.


== WhatsApp Group Messaging for Vendors ==

Since version 1.1.0, this plugin supports automatic WhatsApp group message delivery for multivendor vendors.

Admins can now assign a WhatsApp group ID to each vendor using the whatsiplus_group_id meta field. If this field is set, any order notification message that would normally go to the vendor’s phone will also be sent to their assigned group.

== How It Works ==
*   Create a user meta field called whatsiplus_group_id for each vendor.
*   Enter a valid WhatsApp group ID for the vendor.
*   When a new order is placed, vendors will receive a message both:
*   on their personal WhatsApp number
*   and in the WhatsApp group, if whatsiplus_group_id is set.

== Notes ==
*   The WhatsApp number connected to your WhatsiPlus API must be a member of the vendor’s WhatsApp group.
*   You can retrieve your connected group list via this API call:
https://api.whatsiplus.com/getGroupList/YOUR_API_KEY

== Example ==

If a vendor has the following group ID stored:
120368574965432
They will receive their order alerts in that group in addition to their personal number.

== Multi-Language WhatsApp Messaging – Secondary Language Support ==

Since version 1.1.4, this plugin supports sending WhatsApp messages in two languages. The admin can select a **secondary language** from multiple options, including Persian, Arabic, French, Spanish, German, Chinese, Russian, Portuguese, Italian, Japanese, Turkish, Dutch, and various Spanish variants. Messages will be automatically sent in the correct language based on the customer’s phone number, providing a more personalized and localized communication experience.

== Supported Shortcodes ==
View and test all shortcodes visually here: **[Open WhatsApp Message Formatter](https://whatsiplus.com/upload/wordpress/whatsapp-text-formatter/)**

You can use the following shortcodes in your notification messages. These will be automatically replaced with relevant order and customer data:

[shop_name]  
[shop_email]  
[shop_url]  
[order_id]  
[order_currency]  
[order_amount]  
[order_status]  
[order_latest_cust_note]  
[order_product]  
[order_product_with_qty]  
[all_items]  
[billing_first_name]  
[billing_last_name]  
[billing_phone]  
[billing_email]  
[billing_company]  
[billing_address]
[billing_address_2]  
[billing_country]  
[billing_city]  
[billing_state]  
[billing_postcode]  
[payment_method]  
[shipping_address_1]  
[shipping_address_2]
[shipping_amount]
[shipping_method]
[order_product_links]

Only supported in the Multivendor tab:
[vendor_shop_name]
[order_note]
[product_options]


== Frequently Asked Questions ==

= How do I get started with Whatsiplus? =

1. **Register**: Sign up on our website at [panel.whatsiplus.com](https://panel.whatsiplus.com/).
2. **Select a Plan**: Choose the plan that best suits your needs from our available options.
3. **Connect WhatsApp**: Connect your WhatsApp account to WhatsiPLUS to start using its features.
4. **Insert API KEY**: Insert the provided API KEY into the Whatsiplus plugin settings within your WordPress admin panel.
5. **Customize Settings**: Tailor the plugin settings to your preferences and requirements.

= Can I use the WhatsApp Order Notifications Plugin without WooCommerce? =

Yes, you can utilize certain functionalities of the plugin, such as sending WhatsApp messages, even without the WooCommerce plugin installed on your WordPress site.

= How does the plugin enhance customer engagement? =

By sending instant WhatsApp notifications about order updates, the plugin keeps customers informed in real-time, fostering stronger relationships with the brand and reducing inquiries related to order status.

= Which integrations are supported by the Whatsiplus Notification Plugin? =

The plugin supports integrations with various plugins including reservation/booking, membership, CRM, and forms plugins, ensuring that important updates related to bookings, memberships, customer inquiries, and form submissions are communicated promptly and effectively.

= Is it free to download and use the Whatsiplus notification extension? =

Yes, downloading the plugin is free for all users and you will not pay anything for the plugin, also all users can use our website's free and unlimited subscription for 10 days.

== Installation ==

1. Search for "Whatsiplus Order Notification for WooCommerce" in "Add Plugin" page and activate it.

2. Configure the settings in Settings > Whatsiplus Settings.

3. Enjoy.

== Screenshots ==

1. Whatsiplus Settings
2. Admin Settings
3. Customer Settings
4. Multivendor Settings
5. Send WhatsApp Message to Users
6. Automation Settings
7. Message Outbox
8. Logs

== Changelog ==

= 1.1.6 =
* Added: New shortcode [order_note] introduced. Now you can use [order_note] (in addition to [order_latest_cust_note]) in customer and admin message templates to display the latest order note.

= 1.1.5 =
* Fixed: Admin notification messages issue resolved — now admin messages are sent correctly.

= 1.1.4 =
* The admin can now select a secondary language to send WhatsApp messages to customers.

* Secondary language messages are automatically sent based on the customer’s phone number information

* Secondary language settings have been added in the plugin settings with options to choose from various languages such as Persian, Arabic, French, Spanish, German, Chinese, Russian, Portuguese, Italian, Japanese, Turkish, Dutch, and different Spanish variants.

= 1.1.3 =
* Add the feature to create and display WordPress shortcodes and messages graphically

= 1.1.2 =
* Added: Full support for custom WooCommerce order statuses
* Improved: Automatically display custom statuses in Admin, Customer, and Multivendor settings
* Added: WhatsApp messages now trigger correctly when orders transition into any custom status

= 1.1.1 =
* Fixed: [order_product] and [order_product_with_qty] placeholders were not displaying correctly in vendor messages — now they show product names and quantities properly
* Added: New placeholder [order_product_links] to include product URLs in WhatsApp messages to vendors
* Improved: Placeholder processing logic in multivendor notifications to ensure accurate data replacement
* Internal cleanup: Removed unused empty placeholder values in the replacement array for better readability

= 1.1.0 =
* Improved plugin load speed and overall performance
* Added support for automatic WhatsApp group messaging to vendors in multivendor setups using the whatsiplus_group_id user meta field
* Automatic deletion of old and excessive log entries from the database
* Automatic cleanup of sent WhatsApp messages from the database to reduce clutter

= 1.0.9 =
* Improved support for using WordPress shortcodes in WhatsApp messages
* Added new shortcode: [shipping_method] to display selected shipping method in messages
* Dynamic API endpoint selection based on WordPress dashboard language
* Improved order listing display for vendors in multi-vendor setups
* Fixed minor caching-related bugs in the admin panel

= 1.0.8 =
* Added support for running WordPress shortcodes in WhatsApp messages.

= 1.0.7 =
* Fixed some bugs
* Added a new shortcode [billing_address_2]

= 1.0.6 =
* Fixed an issue causing blank settings page due to outdated browser cache (now uses dynamic versioning for scripts/styles)
* Added support for new shortcodes:
  - [vendor_shop_name] for Vendor shop name
  - [order_note] for WooCommerce customer note
  - [product_options] for custom product options (like size, color)
* Improved shortcode parsing reliability for multivendor messages
* Better compatibility with "Product Options for WooCommerce" plugin

= 1.0.5 =
* Fixed bugs in certain cases, including issues with multi-vendor message sending
* Added a new shortcode [shipping_amount] for including shipping amount in messages

= 1.0.4 =
* Preserved line breaks by setting correct content-type for raw input.

= 1.0.3 =
* Fixed an issue causing a blank page in certain cases.

= 1.0.2 =
* Added support for [all_items] to display all order products.
* Added [shipping_address_1] and [shipping_address_2] to display shipping address.

= 1.0.1 =
* Resolved an issue where tabs occasionally failed to display content due to browser caching.
* Improved script enqueue mechanism to ensure dynamic file versioning.

= 1.0.0 =
* Initial release with core features.

== Upgrade Notice ==

= 1.1.6 =
Added: New shortcode [order_note] introduced. Now you can use [order_note] (in addition to [order_latest_cust_note]) in customer and admin message templates to display the latest order note.

= 1.1.5 =
Fixed: Admin notification messages issue resolved — now admin messages are sent correctly.

= 1.1.4 =
The admin can now select a secondary language to send WhatsApp messages to customers.
econdary language messages are automatically sent based on the customer’s phone number information
Secondary language settings have been added in the plugin settings with options to choose from various languages such as Persian, Arabic, French, Spanish, German, Chinese, Russian, Portuguese, Italian, Japanese, Turkish, Dutch, and different Spanish variants.

= 1.1.3 =
Add the feature to create and display WordPress shortcodes and messages graphically

= 1.1.2 =
Added: Full support for custom WooCommerce order statuses
Improved: Automatically display custom statuses in Admin, Customer, and Multivendor settings
Added: WhatsApp messages now trigger correctly when orders transition into any custom status

= 1.1.1 =
Fixed: [order_product] and [order_product_with_qty] placeholders were not displaying correctly in vendor messages — now they show product names and quantities properly
Added: New placeholder [order_product_links] to include product URLs in WhatsApp messages to vendors
Improved: Placeholder processing logic in multivendor notifications to ensure accurate data replacement
Internal cleanup: Removed unused empty placeholder values in the replacement array for better readability

= 1.1.0 =
Improved plugin load speed and overall performance
Added support for automatic WhatsApp group messaging to vendors in multivendor setups using the whatsiplus_group_id user meta field
Automatic deletion of old and excessive log entries from the database
Automatic cleanup of sent WhatsApp messages from the database to reduce clutter

= 1.0.9 =
Improved support for using WordPress shortcodes in WhatsApp messages
Added new shortcode: [shipping_method] to display selected shipping method in messages
Dynamic API endpoint selection based on WordPress dashboard language
Improved order listing display for vendors in multi-vendor setups
Fixed minor caching-related bugs in the admin panel

= 1.0.8 =
Added support for running WordPress shortcodes in WhatsApp messages.

= 1.0.7 =
Added a new shortcode [billing_address_2]

= 1.0.6 =
This update adds new shortcodes for vendor messages, fixes fatal error with customer notes, improves plugin compatibility, and prevents blank settings pages by enhancing script loading.

= 1.0.5 =
Fixed bugs in certain cases, including issues with multi-vendor message sending
Added a new shortcode [shipping_amount] for including shipping amount in messages

= 1.0.4 =
Preserved line breaks by setting correct content-type for raw input.

= 1.0.3 =
Fixed an issue causing a blank page in certain cases

= 1.0.2 =
This update adds support for displaying all order products and enhances the shipping address fields. It is recommended to update for improved functionality.

= 1.0.1 =
This update fixes caching issues affecting tab content. It is highly recommended to update for smoother functionality.

= 1.0.0 =
Initial version released.