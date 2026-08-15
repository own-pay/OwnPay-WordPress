jQuery(document).ready(function ($) {
    $('.opwc-upload-button').click(function (e) {
        e.preventDefault();
        var button = $(this);
        var inputId = button.data('input-id');
        var input = $('#' + inputId);
        var preview = $('#' + inputId + '-preview');

        // Create the media frame.
        // Strings are passed from PHP via wp_localize_script() to support translation.
        var file_frame = wp.media.frames.file_frame = wp.media({
            title: (typeof opwcUploadI18n !== 'undefined') ? opwcUploadI18n.mediaTitle : '',
            button: {
                text: (typeof opwcUploadI18n !== 'undefined') ? opwcUploadI18n.mediaButton : ''
            },
            multiple: false
        });

        // When an image is selected, run a callback.
        file_frame.on('select', function () {
            var attachment = file_frame.state().get('selection').first().toJSON();
            input.val(attachment.url);
            preview.attr('src', attachment.url).show();
        });

        // Finally, open the modal
        file_frame.open();
    });

    $('.opwc-clear-button').click(function (e) {
        e.preventDefault();
        var button = $(this);
        var inputId = button.data('input-id');
        $('#' + inputId).val('');
        $('#' + inputId + '-preview').attr('src', '').hide();
    });
});
