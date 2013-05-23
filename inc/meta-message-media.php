<?php $uploader = Message_Manager::get_instance()->media_uploader; ?>
<div class="mm_admin_box mm_admin_media">
    <p>The media section allows you to upload or reference audio and video to be displayed along side the message
        content above. It can be displayed on the sermon page, using shortcodes, and in podcasts.</p>
    <h4>Video</h4>
    <table class="form-table">
        <tbody>
        <?php $mb->the_field('video-type'); ?>
        <tr id="mm-video-type">
            <th scope="row"><label for="<?php $mb->the_name(); ?>">Type:</label></th>
            <td>
                <select id="mm-video-type-select" name="<?php $mb->the_name(); ?>">
                    <option value="url"<?php $mb->the_select_state('url'); ?>>Media File/URL</option>
                    <option value="vimeo"<?php $mb->the_select_state('vimeo'); ?>>Vimeo</option>
                    <option value="youtube"<?php $mb->the_select_state('youtube'); ?>>YouTube</option>
                    <option value="embedded"<?php $mb->the_select_state('embedded'); ?>>Embedded</option>
                </select>
            </td>
        </tr>

        <?php $mb->the_field('video-url'); ?>
        <tr id="mm-video-url">
            <th scope="row"><label>URL:</label></th>
            <td>
                <input type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"
                       class="regular-text"/>
                <input class="button media_button" type="button" value="Upload Video" data-format="video"
                       data-title="Choose Video" data-select="Select Video"/>

                <?php $mb->the_field('video-url-id'); ?>
                <input name="<?php $mb->the_name() ?>" type="hidden" value="<?php $mb->the_value() ?>"/>
            </td>
        </tr>

        <?php $mb->the_field('video-embedded'); ?>
        <tr id="mm-video-embedded">
            <th scope="row"><label for="<?php $mb->the_name(); ?>">Embedded Code:</label></th>
            <td><textarea name="<?php $mb->the_name(); ?>" rows="5"
                          class="large-text"><?php $mb->the_value(); ?></textarea>

                <p class="description">Paste any embedding code here.</p>
            </td>
        </tr>
        <?php $mb->the_field('video-attachment'); ?>
        <tr id="mm-video-attachment">
            <th scope="row"><label for="<?php $mb->the_name(); ?>">Show In Attachments:</label></th>
            <td><select name="<?php $mb->the_name(); ?>">
                    <option value="yes"<?php $mb->the_select_state('yes'); ?>>Yes, show in attachments.</option>
                    <option value="no"<?php $mb->the_select_state('no'); ?>>No, don't show.</option>
                </select>

                <p class="description">Also shows the video in attachments for direct download.</p>
            </td>
        </tr>
        </tbody>
    </table>

    <h4>Audio</h4>
    <table class="form-table">
        <tbody>
        <?php $mb->the_field('audio-url'); ?>
        <tr>
            <th scope="row"><label>URL:</label></th>
            <td>
                <input type="text" name="<?php $mb->the_name(); ?>" value="<?php $mb->the_value(); ?>"
                       class="regular-text"/>
                <input class="button media_button" type="button" value="Upload Audio" data-format="audio"
                       data-title="Choose Audio" data-select="Select Audio"/>

                <p class="description">Enter the URL to an audio file, upload a file from your computer, or choose a
                    file already in the media library.</p>

                <?php $mb->the_field('audio-url-id'); ?>
                <input name="<?php $mb->the_name() ?>" type="hidden" value="<?php $mb->the_value() ?>"/>
            </td>
        </tr>
        <?php $mb->the_field('audio-attachment'); ?>
        <tr>
            <th scope="row"><label for="<?php $mb->the_name(); ?>">Show In Attachments:</label></th>
            <td><select name="<?php $mb->the_name(); ?>">
                    <option value="yes"<?php $mb->the_select_state('yes'); ?>>Yes, show in attachments.</option>
                    <option value="no"<?php $mb->the_select_state('no'); ?>>No, don't show.</option>
                </select>

                <p class="description">Also shows the video in attachments for direct download.</p>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<script type="text/javascript">
    //<![CDATA[
    jQuery(document).ready(function ($) {

        $("#mm-video-type-select").change(function () {
            toggleFields();
        });

        function toggleFields() {
            var type = $('#mm-video-type-select').val();

            if (type == 'url') {
                $('#mm-video-embedded').hide();
                $('#mm-video-url label').text('URL:');
                $('#mm-video-url .description').text('Enter the URL to a video, upload a video from your computer, or select a video from the media library. e.g. http://www.example.com/myvideo.mp4');
                $('#mm-video-url').fadeIn('fast');
                $('#mm-video-url .media_button').fadeIn('fast');
                $('#mm-video-attachment').fadeIn('fast');
            } else if (type == 'vimeo') {
                $('#mm-video-attachment').hide();
                $('#mm-video-embedded').hide();
                $('#mm-video-url label').text('Vimeo URL:');
                $('#mm-video-url .description').text('Enter the URL to a vimeo video. e.g. http://vimeo.com/26391477');
                $('#mm-video-url').fadeIn('fast');
                $('#mm-video-url .media_button').fadeOut('fast');
            } else if (type == 'youtube') {
                $('#mm-video-attachment').hide();
                $('#mm-video-embedded').hide();
                $('#mm-video-url label').text('YouTube URL:');
                $('#mm-video-url .description').text('Enter the URL to a YouTube video. e.g. http://www.youtube.com/watch?v=lKKVRBuAGCk');
                $('#mm-video-url').fadeIn('fast');
                $('#mm-video-url .media_button').fadeOut('fast');
            } else if (type == 'embedded') {
                $('#mm-video-attachment').hide();
                $('#mm-video-url').hide();
                $('#mm-video-embedded').fadeIn('fast');
            }
        }

        toggleFields();

    });
    //]]>
</script>