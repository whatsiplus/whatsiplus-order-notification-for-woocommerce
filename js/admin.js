let caretPosition = 0;
jQuery(function ($) {
    $("#whatsiplus_setting_form").on("submit", function (event) {
        var sender_id = $("#whatsiplus_setting\\[whatsiplus_woocommerce_sms_from\\]").val().trim();
        if ($.isNumeric(sender_id) && sender_id.length > 15) {
            alert('Message From is too long, max 15 digits for numeric message sender.');
            event.preventDefault();
            return;
        } else if (!$.isNumeric(sender_id) && sender_id.length > 11) {
            alert('Message From is too long, max 11 characters for alphanumeric message sender.');
            event.preventDefault();
            return;
        }
        $("#whatsiplus_setting\\[whatsiplus_woocommerce_sms_from\\]").val(sender_id);

    });

    $("#whatsiplus_admin_setting\\[whatsiplus_woocommerce_admin_sms_recipients\\]").focusout(function () {
        var admin_mobile_no = $("#whatsiplus_admin_setting\\[whatsiplus_woocommerce_admin_sms_recipients\\]").val().trim();
        var admin_mobile_no_array = new Array();
        var counter;
        if (admin_mobile_no != '') {
            admin_mobile_no_array = admin_mobile_no.split(",");
            for (counter = 0; counter < admin_mobile_no_array.length; counter++) {
                admin_mobile_no_array[counter] = admin_mobile_no_array[counter].trim();
                if (!$.isNumeric(admin_mobile_no_array[counter])) {
                    alert('Invalid mobile number, must be numeric.');
                    break;
                }
                // else if (admin_mobile_no_array[counter].substring(0, 1) == '0') {
                //     alert('Mobile number must include country code, e.g. 60123456789, 6545214889.');
                //     break;
                // }
            }
        }
    });

    const setupPhoneHelper = function () {
        let selectedValue = $("#whatsiplus_multivendor_setting\\[whatsiplus_multivendor_selected_plugin\\]").val();
        let phoneFieldLocation = '[edit profile page > Whatsiplus WooCommerce > phone]';
        if (selectedValue === 'dokan') {
            phoneFieldLocation = '[vendor dashboard > Settings > Store > Phone No]';
        } else if (selectedValue === 'wc_marketplace') {
            phoneFieldLocation = '[vendor dashboard > Store Settings > Storefront > Phone]';
        } else if (selectedValue === 'wcfm_marketplace') {
            phoneFieldLocation = '[store manager > Settings > Store > Store Phone';
        }

        let helperText = `<strong>Vendor are required to fill up their phone on <span style="color: #ff0000;">${phoneFieldLocation}</span> in order to receive sms</strong>`;
        $("#whatsiplus_multivendor_setting\\[multivendor_helper_desc\\]").html(helperText);
    };

    $("#whatsiplus_multivendor_setting\\[whatsiplus_multivendor_selected_plugin\\]").change(setupPhoneHelper);
    setupPhoneHelper();

    $('.modal').css("height", "auto");
    $('.modal').css("overflow-x", "unset");
    $('.modal').css("overflow-y", "unset");
    $('#whatsi_sms\\[open-keywords\\]').click(function (e) {
        const type = $(e.target).attr('data-attr-type');
        const target = $(e.target).attr('data-attr-target');

        caretPosition = document.getElementById(target).selectionStart;

        let shopKeywords;
        if (type === 'multivendor') {
            shopKeywords = ['shop_name', 'shop_email', 'shop_url', 'vendor_shop_name'];
        } else {
            shopKeywords = ['shop_name', 'shop_email', 'shop_url'];
        }
        const orderKeywords = ['order_id', 'order_currency', 'order_amount', 'order_latest_cust_note', 'order_product_with_qty', 'order_product', 'order_status'];
        let billingKeywords = ['billing_first_name', 'billing_last_name', 'billing_phone', 'billing_email', 'billing_company', 'billing_address', 'billing_country', 'billing_city', 'billing_state', 'billing_postcode', 'payment_method'];

        if ($('#whatsiplus_new_billing_field') && $('#whatsiplus_new_billing_field').val() !== '') {
            let newFields = $('#whatsiplus_new_billing_field').val().split(',');
            for (let i in newFields) {
                billingKeywords.push(newFields[i]);
            }
        }

        const buildTable = function (keywords) {
            const chunkedKeywords = keywords.array_chunk(3);

            let tableCode = '';
            chunkedKeywords.forEach(function (row, rowIndex) {
                if (rowIndex === 0) {
                    tableCode += '<table class="widefat fixed striped"><tbody>';
                }

                tableCode += '<tr>';
                row.forEach(function (col) {
                    tableCode += `<td class="column"><button class="button-link" onclick="whatsiplus_bind_text_to_field('${target}', '[${col}]')">[${col}]</button></td>`;
                });
                tableCode += '</tr>';

                if (rowIndex === chunkedKeywords.length - 1) {
                    tableCode += '</tbody></table>';
                }
            });

            return tableCode;
        };

        $('#whatsi_sms\\[keyword-modal\\]').off();
        $('#whatsi_sms\\[keyword-modal\\]').on($.modal.AFTER_CLOSE, function () {
            document.getElementById(target).focus();
            document.getElementById(target).setSelectionRange(caretPosition, caretPosition);
        });

        let mainTable = '';
        mainTable += '<h2>Shop</h2>';
        mainTable += buildTable(shopKeywords);

        mainTable += '<h2>Order</h2>';
        mainTable += buildTable(orderKeywords);

        mainTable += '<h2>Billing</h2>';
        mainTable += buildTable(billingKeywords);

        mainTable += '<div style="margin-top: 10px"><small>*Press on keyword to add to message template</small></div>';

        $('#whatsi_sms\\[keyword-modal\\]').html(mainTable);
        $('#whatsi_sms\\[keyword-modal\\]').modal();
    });

    $('#whatsi_sms\\[open-keywords-low-product-stock\\]').click(function (e) {
        const type = $(e.target).attr('data-attr-type');
        const target = $(e.target).attr('data-attr-target');

        caretPosition = document.getElementById(target).selectionStart;

        let shopKeywords;
        if (type === 'multivendor') {
            shopKeywords = ['shop_name', 'shop_email', 'shop_url', 'vendor_shop_name'];
        } else {
            shopKeywords = ['shop_name', 'shop_email', 'shop_url'];
        }
        let productKeywords = ['product_id', 'product_name', 'produce_price', 'product_description', 'product_short_description', 'product_sale_price', 'product_stock_quantity' ];

        if ($('#whatsiplus_new_billing_field') && $('#whatsiplus_new_billing_field').val() !== '') {
            let newFields = $('#whatsiplus_new_billing_field').val().split(',');
            for (let i in newFields) {
                billingKeywords.push(newFields[i]);
            }
        }

        const buildTable = function (keywords) {
            const chunkedKeywords = keywords.array_chunk(3);

            let tableCode = '';
            chunkedKeywords.forEach(function (row, rowIndex) {
                if (rowIndex === 0) {
                    tableCode += '<table class="widefat fixed striped"><tbody>';
                }

                tableCode += '<tr>';
                row.forEach(function (col) {
                    tableCode += `<td class="column"><button class="button-link" onclick="whatsiplus_bind_text_to_field('${target}', '[${col}]')">[${col}]</button></td>`;
                });
                tableCode += '</tr>';

                if (rowIndex === chunkedKeywords.length - 1) {
                    tableCode += '</tbody></table>';
                }
            });

            return tableCode;
        };

        $('#whatsi_sms\\[keyword-modal\\]').off();
        $('#whatsi_sms\\[keyword-modal\\]').on($.modal.AFTER_CLOSE, function () {
            document.getElementById(target).focus();
            document.getElementById(target).setSelectionRange(caretPosition, caretPosition);
        });

        let mainTable = '';
        mainTable += '<h2>Shop</h2>';
        mainTable += buildTable(shopKeywords);

        mainTable += '<h2>Product</h2>';
        mainTable += buildTable(productKeywords);

        mainTable += '<div style="margin-top: 10px"><small>*Press on keyword to add to message template</small></div>';

        $('#whatsi_sms\\[keyword-modal\\]').html(mainTable);
        $('#whatsi_sms\\[keyword-modal\\]').modal();
    });
});

function whatsiplus_bind_text_to_field(target, keyword) {
     var s = document.getElementById(target);
    if (document.all)
        if (s.createTextRange && s.caretPos) {
            var i = s.caretPos;
            i.text = " " == i.text.charAt(i.text.length - 1) ? keyword + " " : keyword
        } else s.value = s.value + e;
    else if (s.setSelectionRange) {
        var r = s.selectionStart,
            o = s.selectionEnd,
            n = s.value.substring(0, r),
            l = s.value.substring(o);
        s.value = n + keyword + l
    } else alert("This version of Mozilla based browser does not support setSelectionRange")
}

Object.defineProperty(Array.prototype, 'array_chunk', {
    value: function (chunkSize) {
        const array = this;
        return [].concat.apply([],
            array.map(function (elem, i) {
                return i % chunkSize ? [] : [array.slice(i, i + chunkSize)];
            })
        );
    }
});
