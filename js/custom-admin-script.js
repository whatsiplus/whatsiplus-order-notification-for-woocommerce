document.addEventListener('DOMContentLoaded', function() {
    // Initiate Color Picker
    jQuery('.wp-color-picker-field').wpColorPicker();

    // Switches option sections
    jQuery('.group').hide();
    var activetab = '';
    if (typeof(localStorage) !== 'undefined') {
        activetab = localStorage.getItem("activetab");
        var validTabs = jQuery('.nav-tab-wrapper a').map(function() {
            return jQuery(this).attr('href');
        }).get();

        if (!validTabs.includes(activetab)) {
            activetab = validTabs[0];
            localStorage.setItem("activetab", activetab);
        }
    }

    if (activetab && jQuery(activetab).length) {
        jQuery(activetab).fadeIn();
        jQuery(activetab + '-tab').addClass('nav-tab-active');
    } else {
        // Fallback to first tab
        jQuery('.group:first').fadeIn();
        jQuery('.nav-tab-wrapper a:first').addClass('nav-tab-active');
        if (typeof(localStorage) !== 'undefined') {
            localStorage.setItem("activetab", jQuery('.nav-tab-wrapper a:first').attr('href'));
        }
    }
    jQuery('.group .collapsed').each(function(){
        jQuery(this).find('input:checked').parent().parent().parent().nextAll().each(function(){
            if (jQuery(this).hasClass('last')) {
                jQuery(this).removeClass('hidden');
                return false;
            }
            jQuery(this).filter('.hidden').removeClass('hidden');
        });
    });

    jQuery('.nav-tab-wrapper a').click(function(evt) {
        jQuery('.nav-tab-wrapper a').removeClass('nav-tab-active');
        jQuery(this).addClass('nav-tab-active').blur();
        var clicked_group = jQuery(this).attr('href');
        if (typeof(localStorage) != 'undefined' ) {
            localStorage.setItem("activetab", jQuery(this).attr('href'));
        }
        jQuery('.group').hide();
        jQuery(clicked_group).fadeIn();
        evt.preventDefault();
    });

    jQuery('.group .wrap .nav-tab-wrapper a').click(function(evt) {
        jQuery('.nav-tab-wrapper a').removeClass('nav-tab-active');
        jQuery(this).addClass('nav-tab-active').blur();
        var clicked_group = jQuery(this).attr('href');
        if (typeof(localStorage) != 'undefined' ) {
            localStorage.setItem("subtab", jQuery(this).attr('href'));
        }
        jQuery('.group').hide();
        parent_id = jQuery(this).parent().parent().parent().attr("id");
        jQuery("#" + parent_id + "-tab").addClass('nav-tab-active').blur();
        jQuery(this).parent().parent().parent().show();
        jQuery(clicked_group).fadeIn();
        evt.preventDefault();
    });

    jQuery('.wpsa-browse').on('click', function (event) {
        event.preventDefault();

        var self = jQuery(this);

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