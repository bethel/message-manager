jQuery(document).ready(function ($) {

    var parent;
    var mediaFrame;
    var lastFormat;

    function bind_media_buttons() {
        $('.media_button').click(function (e) {
            e.preventDefault();

            parent = $(this).parent();

            var format = $(this).data('format');
            var title = $(this).data('title');
            var select = $(this).data('select');

            // If the media frame already exists, reopen it.
            if (mediaFrame && lastFormat === format) {
                mediaFrame.open();
                return;
            }

            lastFormat = format;

            mediaFrame = wp.media.frames.file_frame = wp.media({
                title: title,
                button: {
                    text: select
                },
                library: { type: format },
                multiple: false
            });

            mediaFrame.on('select', function () {
                var attachment = mediaFrame.state().get('selection').first().toJSON();
                parent.find('input[type="hidden"]').val(attachment.id);
                parent.find('input[type="text"]').val(attachment.url);
            });

            mediaFrame.open();
        });
    }

    bind_media_buttons();

    $.wpalchemy.bind('wpa_copy', function (the_clone) {
        bind_media_buttons();
    });

});