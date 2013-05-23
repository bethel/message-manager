<?php

define('MM_ADMIN_NONCE', 'mm-admin-nonce');

class Message_Manager_Meta_Details
{

    /**
     * @var singleton instance of the message manager downloads
     */
    private static $instance;

    /**
     * @return Message_Manager_Meta_Details instance of the message manager
     */
    public static function get_instance()
    {
        if (empty(static::$instance)) {
            static::$instance = new Message_Manager_Meta_Details();
        }
        return static::$instance;
    }

    private function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('admin_head', array($this, 'admin_head'));
        add_action('save_post', array($this, 'save_post'));
    }

    /**
     * Adds the meta boxes to the message post type (action: add_meta_boxes)
     */
    public function add_meta_boxes()
    {
        add_meta_box('mm-details', __('Details', 'message-manager'), array($this, 'details_metabox'), MM_CPT_MESSAGE, 'normal', 'high');
        add_meta_box('mm-media', __('Media', 'message-manager'), array($this, 'media_metabox'), MM_CPT_MESSAGE, 'normal', 'high');
        add_meta_box('mm-attachments', __('Attachments', 'message-manager'), array($this, 'attachments_metabox'), MM_CPT_MESSAGE, 'normal', 'high');

        // move the content box below details
        global $_wp_post_type_features;
        if (isset($_wp_post_type_features[MM_CPT_MESSAGE]['editor']) && $_wp_post_type_features[MM_CPT_MESSAGE]['editor']) {
            unset($_wp_post_type_features[MM_CPT_MESSAGE]['editor']);
            add_meta_box(
                'mm-editor',
                __('Content'),
                array($this, 'content_metabox'),
                MM_CPT_MESSAGE, 'normal', 'high'
            );
        }
    }

    /**
     * Makes sure the editor container background is white
     */
    public function admin_head()
    {
        ?>
        <style type="text/css">
            .wp-editor-container {
                background-color: #fff;
            }
        </style>
    <?php
    }

    /**
     * Called when a post is being saved (action: save_post)
     * @param $post_id The post id
     * @return null
     */
    public function save_post($post_id)
    {
        $post_id = $_POST['post_ID'];

        if ($_POST['post_type'] != MM_CPT_MESSAGE) return;

        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) return;

        if (!current_user_can('edit_post', $post_id)) return;

        if (empty($_POST[MM_ADMIN_NONCE]) || !wp_verify_nonce($_POST[MM_ADMIN_NONCE], MM_ADMIN_NONCE)) return;

        if (!empty($_POST[MM_META_PREFIX . 'details'])) {
            $details = $_POST[MM_META_PREFIX . 'details'];

            foreach ($details as $key => $value) {
                switch ($key) {
                    case 'summary':
                        break;
                    default:
                        $value = sanitize_text_field($value);
                }
                $this->set_meta($post_id, 'details_' . $key, $value);
            }
        }

        //return;

        // TODO:
    }

    /**
     * Returns the meta data for a post with the prefixed key
     * @param $post_id string post id
     * @param $key string metadata key
     * @param bool $single
     * @return mixed
     */
    public function get_meta($post_id, $key, $single = true)
    {
        return get_post_meta($post_id, MM_META_PREFIX . $key, $single);
    }

    /**
     * Sets the meta data for a post with the prefixed key
     * @param $post_id string post id
     * @param $key string metadata key
     * @param $value string metadata value
     * @return mixed
     */
    public function set_meta($post_id, $key, $value)
    {
        return update_post_meta($post_id, MM_META_PREFIX . $key, $value);
    }

    /**
     * Echos get_meta
     * @param $post_id string post id
     * @param $key string meta key
     * @param $single
     */
    public function the_meta($post_id, $key, $single = true)
    {
        echo $this->get_meta($post_id, $key, $single);
    }

    /**
     * Prints the HTML for the details metabox
     * @param $post The message
     */
    public function details_metabox($post)
    {
        wp_nonce_field(MM_ADMIN_NONCE, MM_ADMIN_NONCE);
        ?>
        <table class="form-table">
            <tbody>
            <?php $this->text_field('details[date]', 'mm-message-date', 'Date', $this->get_meta($post->ID, 'details_date'), 'Enter/Select the date the message was given on.'); ?>

            <?php $this->text_field('details[verses]', 'mm-message-verses', 'Bible Verses', $this->get_meta($post->ID, 'details_verses'), '(Optional) Enter one or more verses sperated by semi-colons that relate to the message. To see sepecific formatting details, look at the <a href="http://reftagger.com/#tagging" title="Reftagger Website" target="_blank">Reftagger website</a>.'); ?>

            <?php $this->text_area('details[summary]', 'mm-message-summary', 'Summary', $this->get_meta($post->ID, 'details_summary'), '(Optional) Type a brief plain-text description about this message for podcasts, search summaries, and intro pages. If a summary is not entered, it will be derived (not always well) from the content below.'); ?>
            </tbody>
        </table>

        <script type="text/javascript">
            //<![CDATA[
            jQuery(document).ready(function ($) {
                $("#mm-message-date").datepicker({
                    showOn: "both",
                    dateFormat: "yy-mm-dd",
                    buttonImage: "<?php echo MM_IMG_URL; ?>calendar.gif"
                });
            });
            //]]>
        </script>
    <?php
    }

    public function label($name, $title)
    {
        ?>
        <th scope="row"><label
                for="<?php echo MM_META_PREFIX . $name; ?>"><?php echo esc_attr(__($title, 'message-manager')); ?>
                :</label></th>
    <?php
    }

    public function description($description)
    {
        if (empty($description)) return;
        ?>
        <p class="description"><?php echo $description; ?></p>
    <?php
    }

    public function text_field($name, $id, $title, $value, $description)
    {
        ?>
        <tr>
            <?php $this->label($name, $title); ?>
            <td><input id="<?php echo esc_attr($id); ?>" type="text" name="<?php echo MM_META_PREFIX . $name; ?>"
                       value="<?php echo esc_attr($value); ?>" class="regular-text">
                <?php $this->description($description); ?>
            </td>
        </tr>
    <?php
    }

    public function text_area($name, $id, $title, $value, $description)
    {
        ?>
        <tr>
            <?php $this->label($name, $title); ?>
            <td>
                <textarea id="<?php echo esc_attr($id); ?>" name="<?php echo MM_META_PREFIX . $name; ?>" rows="4"
                          class="large-text"><?php echo esc_html($value); ?></textarea>
                <?php $this->description($description); ?>
            </td>
        </tr>
    <?php
    }

    public function select($name, $id, $title, $options, $value, $description = '')
    {
        ?>
        <tr id="mm-video-type">
            <?php $this->label($name, $title); ?>
            <td>
                <select id="<?php echo esc_attr($id); ?>" name="<?php echo MM_META_PREFIX . $name; ?>">
                    <?php
                    foreach ($options as $key => $option) {
                        if (empty($key)) $key = $option;
                        $option = esc_attr($option);
                        $key = esc_attr($key);
                        $selected = selected($key, $value, false);

                        echo "<option value=\"$key\"$selected>$option</option>";
                    }
                    ?>
                </select>
                <?php $this->description($description); ?>
            </td>
        </tr>
    <?php
    }


    /**
     * Prints the HTML for the media metabox
     * @param $post The message
     */
    public function media_metabox($post)
    {
        $defaults = array(
            'video-type' => 'url',
            'video-url' => '',
            'video-embedded' => '',
            'video-attachment' => 'no',
            'video-attachment-id' => '',

            'audio-url' => '',
            'audio-attachment' => 'yes',
            'audio-attachment-id' => '',
        );
        $meta = shortcode_atts($defaults, $this->get_meta($post->ID, 'media'));

        ?>
        <p>The media section allows you to upload or reference audio and video to be displayed along side the message
            content above. It can be displayed on the sermon page, using shortcodes, and in podcasts.</p>

        <h4>Video</h4>
        <table class="form-table">
            <tbody>
            <?php $this->select('media[video-type]', 'mm-video-type-select', 'Type',
                array('url' => 'Media File/URL', 'vimeo' => 'Vimeo', 'youtube' => 'YouTube', 'embedded' => 'Embedded'),
                $meta['video-type']); ?>

            <tr id="mm-video-url">
                <th scope="row"><label>URL:</label></th>
                <td>
                    <input type="text" class="mediafield-video-url-n" name="_mm_meta_media[video-url]" value=""> <a
                        href="media-upload.php?post_id=5367&amp;type=file&amp;TB_iframe=1&amp;width=640&amp;height=269"
                        class="mediabutton-video-url-n thickbox button {label:'Use File'}">Upload/Select Video</a>

                    <p class="description">Enter the URL to a video, upload a video from your computer, or select a
                        video from the media library. e.g. http://www.example.com/myvideo.mp4</p>
                </td>
            </tr>
            <tr id="mm-video-embedded" style="display: none;">
                <th scope="row"><label for="_mm_meta_media[video-embedded]">Embedded Code:</label></th>
                <td><textarea name="_mm_meta_media[video-embedded]" rows="5" class="large-text"></textarea>

                    <p class="description">Paste any embedding code here.</p>
                </td>
            </tr>
            <tr id="mm-video-attachment">
                <th scope="row"><label for="_mm_meta_media[video-attachment]">Show In Attachments:</label></th>
                <td><select name="_mm_meta_media[video-attachment]">
                        <option value="yes" selected="selected">Yes, show in attachments.</option>
                        <option value="no">No, don't show.</option>
                    </select>

                    <p class="description">Also shows the video in attachments for direct download.</p>
                </td>
            </tr>
            </tbody>
        </table>

        <h4>Audio</h4>
        <table class="form-table">
            <tbody>

            <tr>
                <th scope="row"><label>URL:</label></th>
                <td>
                    <input type="text" class="mediafield-audio-url-n" name="_mm_meta_media[audio-url]"
                           value="http://www.bethelfc.com/wp-content/uploads/20130512-Sermon.mp3"> <a
                        href="media-upload.php?post_id=5367&amp;type=file&amp;TB_iframe=1&amp;width=640&amp;height=269"
                        class="mediabutton-audio-url-n thickbox button {label:'Use File'}">Upload/Select Audio</a>

                    <p class="description">Enter the URL to an audio file, upload a file from your computer, or choose a
                        file already in the media library.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="_mm_meta_media[audio-attachment]">Show In Attachments:</label></th>
                <td><select name="_mm_meta_media[audio-attachment]">
                        <option value="yes" selected="selected">Yes, show in attachments.</option>
                        <option value="no">No, don't show.</option>
                    </select>

                    <p class="description">Also shows the video in attachments for direct download.</p>
                </td>
            </tr>
            </tbody>
        </table>
    <?php
    }

    /**
     * Prints the HTML for the attachments metabox
     * @param $post The message
     */
    public function attachments_metabox($post)
    {
        ?>
        <table class="form-table">
            <tbody>

            <tr class="mm-attachment-url">
                <th scope="row"><label>URL:</label></th>
                <td>
                    <a href="#" class="dodelete button" style="float: right; clear: both; display: none;">Remove
                        Attachment</a>
                    <input type="text" class="mediafield-url-n0" name="_mm_meta_attachments[attachment][0][url]"
                           value="http://www.bethelfc.com/wp-content/uploads/Homework-20130512.pdf"> <a
                        href="media-upload.php?post_id=5367&amp;type=file&amp;TB_iframe=1&amp;width=640&amp;height=269"
                        class="mediabutton-url-n0 thickbox button {label:'Use File'}">Upload/Select Attachment</a>

                    <p class="description">Enter the URL to an attachment, upload a file from your computer, or select a
                        file from the media library. e.g. http://www.example.com/homework.pdf</p>
                </td>
            </tr>
            <tr class="mm-attachment-title">
                <th scope="row"><label for="_mm_meta_attachments[attachment][0][title]">Title:</label></th>
                <td><input type="text" name="_mm_meta_attachments[attachment][0][title]" value="Growth Group Homework"
                           class="regular-text">

                    <p class="description">(Optional) Enter the title of the attachment. e.g. Growth Group Homework</p>
                </td>
            </tr>
            <tr class="mm-attachment-description">
                <th scope="row"><label for="_mm_meta_attachments[attachment][0][description]">Description:</label></th>
                <td><textarea name="_mm_meta_attachments[attachment][0][description]" rows="5"
                              class="large-text"></textarea>

                    <p class="description">(Optional) Enter a discription of the attachment content.</p>
                </td>
            </tr>
            </tbody>
        </table>
    <?php
    }

    /**
     * Prints the HTML for the content metabox
     * @param $post The message
     */
    public function content_metabox($post)
    {
        echo '<div class="wp-editor-wrap">';
        wp_editor($post->post_content, 'content', array('dfw' => true, 'tabindex' => 1));
        echo '</div>';
    }
}

$message_manager_meta_details = Message_Manager_Meta_Details::get_instance();