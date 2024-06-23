jQuery(function ($) {

    var filter_by_arr = wp_localize_script_data.filter_by_arr;
    var criteria_array = wp_localize_script_data.criteria_array;

    function populatedSecondaryFields(filteredField, stateElementId) {

        var selectedFilter = document.getElementById(filteredField).value; // roles

        var criteriaElement = document.getElementById(stateElementId);

        criteriaElement.length = 0;
        criteriaElement.selectedIndex = 0;

        var crit_arr = criteria_array[selectedFilter];

        for (let [key, value] of Object.entries(crit_arr)) {
            criteriaElement.options[criteriaElement.length] = new Option(value.replace('_', ' '), key);
        }

    }

    function populateFilters(filterElementId, filterValueElementId) {
        // given the id of the <select> tag as function argument, it inserts <option> tags
        var filterElement = document.getElementById(filterElementId);
        filterElement.length = 0;
        filterElement.options[0] = new Option('Select Filter', '-1');
        filterElement.selectedIndex = 0;
        for (var i = 0; i < filter_by_arr.length; i++) {
            filterElement.options[filterElement.length] = new Option(filter_by_arr[i].replace('_', ' '), filter_by_arr[i]);
        }

        if (filterElementId) {
            filterElement.onchange = function () {
                populatedSecondaryFields(filterElementId, filterValueElementId);
            };
        }
    }

    populateFilters("whatsiplus_sendsms_setting[whatsiplus_sendsms_filters]", "whatsiplus_sendsms_setting[whatsiplus_sendsms_criteria]");

    countCharactersAndSMS('textarea#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_message\\]', 'text-bulksms-characters', 'text-bulksms-sms');

    function countCharactersAndSMS(selector, charCounter, smsCounter){

        var box = $(selector+'');

        $(selector+'').keyup(function(e) {
            var info = window.splitter.split(box.val());
            //this can read the bytes of last sms
            countBytes = JSON.stringify(info.parts[info.parts.length-1].bytes);
            totalBytes = JSON.stringify(info.bytes);
            remainingChar = JSON.stringify(info.remainingInPart);
            characterSet = JSON.stringify(info.characterSet);
            $('#'+charCounter+'').html(countBytes+' / '+remainingChar);
            //this can read the total number of sms
            $('#'+smsCounter+'').html(info.parts.length);

        });
        $('#'+selector+'').trigger('keyup');
    }

    $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_users\\]').closest("tr").hide();
    $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_recipients\\]').closest("tr").hide();
    $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_filters\\]').closest("tr").hide();
    $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_criteria\\]').closest("tr").hide();

    $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_message_to\\]').on('change', function() {
        if($(this).val()=="customer_all") {
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_users\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_recipients\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_filters\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_criteria\\]').closest("tr").hide();
        }
        else if($(this).val()=="customer") {
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_users\\]').closest("tr").show();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_recipients\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_filters\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_criteria\\]').closest("tr").hide();
        }

        else if($(this).val()=="phones") {
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_recipients\\]').closest("tr").show();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_users\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_filters\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_criteria\\]').closest("tr").hide();
        }
        else if($(this).val()=="spec_group_ppl") {
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_filters\\]').closest("tr").show();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_criteria\\]').closest("tr").show();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_users\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_recipients\\]').closest("tr").hide();
        } else {
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_users\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_recipients\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_filters\\]').closest("tr").hide();
            $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_criteria\\]').closest("tr").hide();
        }
    });

    $('select[name="type"]').on('change', function() {
        if($(this).val()=="mms") {
            $('.media_upload').show();
        } else {
            $('.media_upload').hide();
        }
    });

    var error = '';
    var validate = function () {
        if(!($('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_users\\]').val())
            && $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_message_to\\]').val() == 'customer') {
            error = "users";
            return false;
        }

        if(!($('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_recipients\\]').val())
            && $('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_message_to\\]').val() == 'phones') {
            error = "recipients";
            return false;
        }

        if(!($('#whatsiplus_sendsms_setting\\[whatsiplus_sendsms_message\\]').val())) {
            error = "message";
            return false;
        }
        return true;
    };

    $('#sendMessage').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if(validate()) {
            $("#whatsiplus_sendsms_setting form").submit();
        } else {
            if(error == 'users'){
                alert("Please enter user you wish to send to!");
            }else if(error == 'recipients'){
                alert("Please enter your recipients!");
            }else if(error == 'message'){
                alert("Please enter message!");
            }
        }
    });

    $("#count_me").characterCounter({
        counterFormat: '%1 written characters.',
        counterWrapper: 'div',
        counterCssClass: 'message_counter'
    });

    $('#recipients').keypress(function(e) {
        var a = [];
        var k = e.which;

        if($('#recipients').val() !== '')
            a.push(44);

        for (i = 48; i < 58; i++)
            a.push(i);

        if (!(a.indexOf(k)>=0))
            e.preventDefault();

        // $('span').text('KeyCode: '+k);
    });

});

function sendCheck(phone) {
    if(wp_localize_script_data.prefixCheckEnabled) {
        phone = phone.replace(/[\s\-\(\)]/g, '');
        var numberCheck = phone.replace(/^(\+|0)+/, '');
        var prefixCheck = wp_localize_script_data.prefixCheck;
        var formattedNumber = '';
        if(!isNaN(phone)){
            if((phone.indexOf('+') === 0 || phone.indexOf('00') === 0) && numberCheck.indexOf(prefixCheck) === 0 ){
                formattedNumber = '+' + numberCheck;
            } else if ((phone.indexOf('+') === 0 || phone.indexOf('00') === 0) && numberCheck.indexOf(prefixCheck) !== 0){
                formattedNumber = false;
            } else if (numberCheck.indexOf(prefixCheck) === -1){
                formattedNumber = prefix + numberCheck;
            } else if (phone.indexOf('+') !== 0 && phone.indexOf('00') !== 0 && numberCheck.indexOf(prefixCheck) === 0 ){
                formattedNumber = '+' + phone;
            } else {
                formattedNumber = false;
            }
        }
        return formattedNumber;
    }
    return phone;
}
