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

// Webhook URL copy button
$(document).on('click', '.opwc-copy-webhook-url', function (e) {
    e.preventDefault();
    var url = $(this).data('opwc-copy-url');
    if (!url) return;

    var $btn = $(this);
    var $icon = $btn.find('.dashicons');

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () {
            $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes-alt');
            $btn.contents().last().replaceWith('Copied!');
            setTimeout(function () {
                $icon.removeClass('dashicons-yes-alt').addClass('dashicons-clipboard');
                $btn.contents().last().replaceWith(opwcUploadI18n.copyLabel || 'Copy');
            }, 2000);
        });
    } else {
        // Fallback for older browsers
        var ta = document.createElement('textarea');
        ta.value = url;
        ta.style.position = 'fixed';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        $icon.removeClass('dashicons-clipboard').addClass('dashicons-yes-alt');
        $btn.contents().last().replaceWith('Copied!');
        setTimeout(function () {
            $icon.removeClass('dashicons-yes-alt').addClass('dashicons-clipboard');
            $btn.contents().last().replaceWith('Copy');
        }, 2000);
    }
});
