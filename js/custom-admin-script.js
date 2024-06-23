jQuery(document).ready(function($) {
    // Initiate Color Picker
    $('.wp-color-picker-field').wpColorPicker();

    // Switches option sections
    $('.group').hide();
    var activetab = '';
    var subtab = '';
    if (typeof(localStorage) != 'undefined' ) {
        activetab = localStorage.getItem("activetab");
    }
    if (activetab != '' && $(activetab).length ) {
        $(activetab).fadeIn();
    } else {
        $('.group:first').fadeIn();
    }
    $('.group .collapsed').each(function(){
        $(this).find('input:checked').parent().parent().parent().nextAll().each(function(){
            if ($(this).hasClass('last')) {
                $(this).removeClass('hidden');
                return false;
            }
            $(this).filter('.hidden').removeClass('hidden');
        });
    });

    if (activetab != '' && $(activetab + '-tab').length ) {
        $(activetab + '-tab').addClass('nav-tab-active');
    } else {
        $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
    }
    $('.nav-tab-wrapper a').click(function(evt) {
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active').blur();
        var clicked_group = $(this).attr('href');
        if (typeof(localStorage) != 'undefined' ) {
            localStorage.setItem("activetab", $(this).attr('href'));
        }
        $('.group').hide();
        $(clicked_group).fadeIn();
        evt.preventDefault();
    });

    $('.group .wrap .nav-tab-wrapper a').click(function(evt) {
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active').blur();
        var clicked_group = $(this).attr('href');
        if (typeof(localStorage) != 'undefined' ) {
            localStorage.setItem("subtab", $(this).attr('href'));
        }
        $('.group').hide();
        parent_id = $(this).parent().parent().parent().attr("id");
        $("#" + parent_id + "-tab").addClass('nav-tab-active').blur();
        $(this).parent().parent().parent().show();
        $(clicked_group).fadeIn();
        evt.preventDefault();
    });

    $('.wpsa-browse').on('click', function (event) {
        event.preventDefault();

        var self = $(this);

        // Create the media frame.
        var file_frame = wp.media.frames.file_frame = wp.media({
            title: self.data('uploader_title'),
            button: {
                text: self.data('uploader_button_text'),
            },
            multiple: false
        });

        file_frame.on('select', function () {
            attachment = file_frame.state().get('selection').first().toJSON();
            self.prev('.wpsa-url').val(attachment.url);
        });

        // Finally, open the modal
        file_frame.open();
    });
});