<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$wpcf7 = WPCF7_ContactForm::get_current();
if (empty($wpcf7->id())) {
    echo '<h3>';
    esc_html_e('Please save your contact form 7 once.', 'whatsiplus-order-notification-for-woocommerce');
    echo '</h3>';
} else {
    $contact_form = WPCF7_ContactForm::get_instance($wpcf7->id());
    $form_fields  = $contact_form->scan_form_tags();

    $visitor_notification = $data['visitor_notification'];
    $visitor_mobile_field = $data['visitor_mobile_field'];
    $visitor_sms_template =$data['visitor_sms_template'];

    $admin_notification = $data['admin_notification'];
    $admin_mobile_numbers = $data['admin_mobile_numbers'];
    $admin_sms_template = $data['admin_sms_template'];
?>
    <h2>Visitor settings</h2>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="wpcf7whatsiapi-settings[visitor_notification]">notification</label></th>
                <td>
                    <fieldset>
                        <label for="wpcf7whatsiapi-settings[visitor_notification]">
                            <input type="hidden" name="wpcf7whatsiapi-settings[visitor_notification]" value="off">
                            <input
                                type="checkbox"
                                class="checkbox"
                                id="wpcf7whatsiapi-settings[visitor_notification]"
                                name="wpcf7whatsiapi-settings[visitor_notification]"
                                value="on"
                                <?php echo ($visitor_notification==="on") ? "checked" : "" ?>
                            >
                            Enable</label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpcf7whatsiapi-settings[visitor_mobile_field]">Mobile number field</label></th>
                <td>
                    <select class="regular" name="wpcf7whatsiapi-settings[visitor_mobile_field]" id="wpcf7whatsiapi-settings[visitor_mobile_field]">
                        <option value="">Select mobile field</option>
                        <?php
                        if (!empty($form_fields)) {
                            foreach ($form_fields as $form_field) {
                                $field = json_decode(wp_json_encode($form_field), true);
                                if ($field['name'] !== '') {
                                    $text = '';
                                    if($visitor_mobile_field === $field['name']) {
                                        $text = sprintf('<option value="%1$s" selected>%1$s</option>', $field['name']);
                                    } else {
                                        $text = sprintf('<option value="%1$s">%1$s</option>', $field['name']);
                                    }
                                    //echo $text;
                                    echo wp_kses( $text, array(
                                        'option' => array(
                                            'value'    => true,
                                            'selected' => true,
                                        ),
                                    ) );
                                }
                            }
                        }
                        ?>

                    </select>
                    <!-- <p class="description">Selected country will be use as default country info for mobile number when country info is not provided. </p> -->
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpcf7whatsiapi-settings[visitor_sms_template]">message</label></th>
                <td>
                    <textarea
                        rows="8"
                        cols="500"
                        class="regular-text"
                        style="min-width:350px;;"
                        id="wpcf7whatsiapi-settings[visitor_sms_template]"
                        name="wpcf7whatsiapi-settings[visitor_sms_template]"
                    ><?php echo esc_html($visitor_sms_template); ?></textarea>
                    <p class="description">Customize your message with <button type="button" id="whatsiplus-open-keyword[dummy]" data-attr-type="admin" data-attr-target="wpcf7whatsiapi-settings[visitor_sms_template]" class="button button-secondary">Keywords</button></p>
                </td>
            </tr>
        </tbody>
    </table>

    <h2>Admin settings</h2>
    <table class="form-table" role="presentation">
        <tbody>
            <tr>
                <th scope="row"><label for="wpcf7whatsiapi-settings[admin_notification]">notification</label></th>
                <td>
                    <fieldset>
                        <label for="wpcf7whatsiapi-settings[admin_notification]">
                            <input type="hidden" name="wpcf7whatsiapi-settings[admin_notification]" value="off">
                            <input
                                type="checkbox"
                                class="checkbox"
                                id="wpcf7whatsiapi-settings[admin_notification]"
                                name="wpcf7whatsiapi-settings[admin_notification]"
                                value="on"
                                <?php echo ($admin_notification==="on") ? "checked" : "" ?>
                            >
                            Enable</label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpcf7whatsiapi-settings[admin_mobile_numbers]">Mobile number</label></th>
                <td>
                    <input
                        type="text"
                        class="regular-text"
                        id="wpcf7whatsiapi-settings[admin_mobile_numbers]"
                        name="wpcf7whatsiapi-settings[admin_mobile_numbers]"
                        placeholder="15303776310,15303776310"
                        value="<?php echo esc_html($admin_mobile_numbers); ?>"
                    >
                    <p class="description">Comma separated mobile numbers including country code. Eg: 15303776310,15303776310</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wpcf7whatsiapi-settings[admin_sms_template]">message</label></th>
                <td>
                    <textarea
                        rows="8"
                        cols="500"
                        class="regular-text"
                        style="min-width:350px;;"
                        id="wpcf7whatsiapi-settings[admin_sms_template]"
                        name="wpcf7whatsiapi-settings[admin_sms_template]"
                    ><?php echo esc_html($admin_sms_template) ?></textarea>
                    <p class="description">Customize your message with <button type="button" id="whatsiplus-open-keyword[dummy]" data-attr-type="default" data-attr-target="wpcf7whatsiapi-settings[admin_sms_template]" class="button button-secondary">Keywords</button></p>,
                </td>
            </tr>
        </tbody>
    </table>

   <?php
    function whatsiplus_enqueue_keyword_modal_script() {
        wp_enqueue_script('keyword-modal-script', plugin_dir_url(__DIR__) . '/js/custom-contact7.js', array('jquery'), '1.0', true);
    }
    add_action('wp_enqueue_scripts', 'whatsiplus_enqueue_keyword_modal_script');
    
 } ?>