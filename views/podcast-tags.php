<?php

if (!function_exists('mm_sanitize_podcast_text')) {
    /**
     * Sanitizes feed data to ensure validity
     * @param $text String the text to sanitize
     * @return string The sanitized text
     */
    function mm_sanitize_podcast_text($text)
    {
        $text = strip_tags($text);
        $text = convert_chars($text);
        $text = preg_replace('/&#?[a-z0-9]{2,8};/i', '', $text);
        return $text;
    }
}

if (!function_exists('mm_force_http')) {
    function mm_force_http($url)
    {
        return preg_replace('/^https/i', 'http', $url);
    }
}

if (!function_exists('mm_format_date')) {
    function mm_format_date($date, $format = 'j-m-y')
    {
        $parts = explode('-', $date);
        if ($parts >= 3) {
            $date = mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
            return date($format, $date);
        }
        return $date;
    }
}

if (!function_exists('mm_get_the_podcast_title')) {
    function mm_get_the_podcast_title()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-title', get_the_title_rss()));
    }
}

if (!function_exists('mm_the_podcast_title')) {
    function mm_the_podcast_title()
    {
        echo mm_get_the_podcast_title();
    }
}

if (!function_exists('mm_get_the_podcast_link')) {
    function mm_get_the_podcast_link()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-link', home_url('/')));
    }
}

if (!function_exists('mm_the_podcast_link')) {
    function mm_the_podcast_link()
    {
        echo mm_get_the_podcast_link();
    }
}

if (!function_exists('mm_get_the_podcast_language')) {
    function mm_get_the_podcast_language()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-language', get_bloginfo_rss('language')));
    }
}

if (!function_exists('mm_the_podcast_language')) {
    function mm_the_podcast_language()
    {
        echo mm_get_the_podcast_language();
    }
}

if (!function_exists('mm_get_the_podcast_copyright')) {
    function mm_get_the_podcast_copyright()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-copyright', '&#x2117; &amp; &#xA9; ' . get_bloginfo_rss('name')));
    }
}

if (!function_exists('mm_the_podcast_copyright')) {
    function mm_the_podcast_copyright()
    {
        echo mm_get_the_podcast_copyright();
    }
}

if (!function_exists('mm_get_the_podcast_subtitle')) {
    function mm_get_the_podcast_subtitle()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-subtitle', get_bloginfo_rss('description')));
    }
}

if (!function_exists('mm_the_podcast_subtitle')) {
    function mm_the_podcast_subtitle()
    {
        echo mm_get_the_podcast_subtitle();
    }
}


if (!function_exists('mm_get_the_podcast_author')) {
    function mm_get_the_podcast_author()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-author', get_bloginfo_rss('name')));
    }
}

if (!function_exists('mm_the_podcast_author')) {
    function mm_the_podcast_author()
    {
        echo mm_get_the_podcast_author();
    }
}


if (!function_exists('mm_get_the_podcast_summary')) {
    function mm_get_the_podcast_summary()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-summary', get_bloginfo_rss('description')));
    }
}

if (!function_exists('mm_the_podcast_summary')) {
    function mm_the_podcast_summary()
    {
        echo mm_get_the_podcast_summary();
    }
}

if (!function_exists('mm_get_the_podcast_description')) {
    function mm_get_the_podcast_description()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-description', get_bloginfo_rss('description')));
    }
}

if (!function_exists('mm_the_podcast_description')) {
    function mm_the_podcast_description()
    {
        echo mm_get_the_podcast_description();
    }
}

if (!function_exists('mm_get_the_podcast_owner_name')) {
    function mm_get_the_podcast_owner_name()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-owner-name', get_bloginfo_rss('name')));
    }
}

if (!function_exists('mm_the_podcast_owner_name')) {
    function mm_the_podcast_owner_name()
    {
        echo mm_get_the_podcast_owner_name();
    }
}

if (!function_exists('mm_get_the_podcast_owner_email')) {
    function mm_get_the_podcast_owner_email()
    {
        return mm_sanitize_podcast_text(Message_Manager_Options::get_instance()->get('podcast-owner-email', get_bloginfo_rss('admin_email')));
    }
}

if (!function_exists('mm_the_podcast_owner_email')) {
    function mm_the_podcast_owner_email()
    {
        echo mm_get_the_podcast_owner_email();
    }
}

if (!function_exists('mm_get_the_podcast_image')) {
    function mm_get_the_podcast_image()
    {
        $image = Message_Manager_Options::get_instance()->get('podcast-image');
        if (!empty($image)) {
            $src = wp_get_attachment_image_src($image['id'], 'full');
            if (!empty($src)) {
                return esc_url(mm_force_http($src[0]));
            }
        }
    }
}

if (!function_exists('mm_the_podcast_image')) {
    function mm_the_podcast_image()
    {
        if ($image = mm_get_the_podcast_image()) {
            echo sprintf('<itunes:image href="%s"/>', $image);
        }
    }
}

if (!function_exists('mm_get_the_podcast_keywords')) {
    function mm_get_the_podcast_keywords()
    {
        $keywords = Message_Manager_Options::get_instance()->get('podcast-keywords');
        if (!empty($keywords)) {
            return mm_sanitize_podcast_text($keywords);
        }
    }
}

if (!function_exists('mm_the_podcast_keywords')) {
    function mm_the_podcast_keywords()
    {
        if ($keywords = mm_get_the_podcast_keywords()) {
            echo sprintf('<itunes:keywords>%s</itunes:keywords>', $keywords);
        }
    }
}

if (!function_exists('mm_get_the_podcast_categories')) {
    function mm_get_the_podcast_categories()
    {
        $cats = array();
        $categories = Message_Manager_Options::get_instance()->get('podcast-categories', 'Religion & Spirituality => Christianity, Spirituality');
        if ($categories) {

            $categories = preg_split("/(\r\n|\n|\r)/", $categories);
            foreach ($categories as $category) {
                $pieces = explode('=>', $category);
                $category = trim($pieces[0]);

                if (count($pieces) > 1) {
                    $cats[$category] = explode(',', $pieces[1]);
                } else {
                    $cats[$category] = array();
                }
            }
        }
        return $cats;
    }
}

if (!function_exists('mm_the_podcast_categories')) {
    function mm_the_podcast_categories()
    {
        $categories = mm_get_the_podcast_categories();
        if (!empty($categories)) {
            foreach ($categories as $category => $subcats) {
                if (!empty($category)) {
                    echo sprintf('<itunes:category text="%s">', mm_sanitize_podcast_text(trim($category)));
                    foreach ($subcats as $subcat) {
                        echo sprintf('<itunes:category text="%s"/>', mm_sanitize_podcast_text(trim($subcat)));
                    }
                    echo '</itunes:category>';
                }
            }
        }
    }
}

if (!function_exists('mm_get_the_podcast_audio')) {
    function mm_get_the_podcast_audio()
    {
        $mb = Message_Manager::get_instance()->message_media_mb;
        $mb->the_meta();

        $attachment_id = $mb->get_the_value('audio-url-id');
        $url = $mb->get_the_value('audio-url');

        if (empty($attachment_id) && empty($url)) return;

        if (!empty($attachment_id)) {
            $info = Message_Manager_Downloads::get_instance()->get_id3_info($attachment_id);
            $url = wp_get_attachment_url($attachment_id);
        } else {
            $info = Message_Manager_Downloads::get_instance()->get_id3_info($url);
        }

        $url = mm_force_http($url);

        $duration = false;
        if (!empty($info['playtime_seconds'])) {
            $duration = gmdate("H:i:s", round($info['playtime_seconds']));
        }

        $size = false;
        if (!empty($info['filesize'])) {
            $size = $info['filesize'];
        }

        $mime = 'audio/mpeg';
        if (!empty($info['mime_type'])) {
            $mime = $info['mime_type'];
        }

        return array('url' => $url, 'url-id' => $attachment_id, 'duration' => $duration, 'size' => $size, 'mime' => $mime);
    }
}

if (!function_exists('mm_get_the_podcast_attachments')) {
    function mm_get_the_podcast_attachments()
    {
        $pdf_attachments = array();

        $attachments = get_post_meta(get_the_ID(), Message_Manager::get_instance()->message_attachments_mb->get_the_id(), TRUE);
        if (empty($attachments['attachment'])) return $pdf_attachments;
        $attachments = $attachments['attachment'];

        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                if (empty($attachment['url-id']) && empty($attachment['url'])) continue;

                if (!empty($attachment['url-id'])) {
                    $attachment['url'] = wp_get_attachment_url($attachment['url-id']);
                }

                $attachment['url'] = mm_force_http($attachment['url']);

                $pathinfo = pathinfo($attachment['url']);
                if (!empty($pathinfo['extension'])) {
                    if (!strtolower($pathinfo['extension']) == 'pdf') {
                        continue;
                    }

                    if (!empty($attachment['url-id'])) {
                        $attachment['size'] = Message_Manager_Downloads::get_instance()->get_file_size($attachment['url-id']);
                    } else {
                        $attachment['size'] = Message_Manager_Downloads::get_instance()->get_file_size($attachment['url']);
                    }

                    $attachment['mime'] = 'application/pdf';
                    $pdf_attachments[] = $attachment;
                }
            }

        }
        return $pdf_attachments;
    }
}

if (!function_exists('mm_get_the_title_rss')) {
    function mm_get_the_title_rss($attachment = null)
    {
        $title = get_the_title_rss();
        if ($attachment) {
            if (!empty($attachment['title'])) {
                $title .= ' - ' . $attachment['title'];
            } else if (!empty($attachment['url'])) {
                $title .= ' - ' . basename($attachment['url']);
            }
        }
        return mm_sanitize_podcast_text($title);
    }
}

if (!function_exists('mm_the_title_rss')) {
    function mm_the_title_rss($attachment = null)
    {
        echo mm_get_the_title_rss($attachment);
    }
}

if (!function_exists('mm_get_the_date_rss')) {
    function mm_get_the_date_rss()
    {
        $mb = Message_Manager::get_instance()->message_details_mb;
        $mb->the_meta();

        $date = $mb->get_the_value('date');
        $new_date = mm_format_date($date, 'D, d M Y H:i:s O');
        if ($date == $new_date) {
            return mm_sanitize_podcast_text($new_date);
        }
    }
}

if (!function_exists('mm_the_date_rss')) {
    function mm_the_date_rss()
    {
        if ($date = mm_get_the_date_rss()) {
            echo sprintf('<pubDate>%s</pubDate>', $date);
        }
    }
}

if (!function_exists('mm_get_the_description_rss')) {
    function mm_get_the_description_rss($attachment = null)
    {
        if ($attachment) {
            if (!empty($attachment['description'])) {
                return mm_sanitize_podcast_text($attachment['description']);
            } else {
                return mm_sanitize_podcast_text('Attachment for message: ' . mm_get_the_title_rss());
            }
        } else {
            $mb = Message_Manager::get_instance()->message_details_mb;
            $mb->the_meta();

            $summary = $mb->get_the_value('summary');
            if (!$summary) {
                $summary = get_the_content();
            }
            return mm_sanitize_podcast_text(strip_tags($summary));
        }
    }
}

if (!function_exists('mm_the_description_rss')) {
    function mm_the_description_rss($attachment = null)
    {
        echo mm_get_the_description_rss($attachment);
    }
}


if (!function_exists('mm_get_the_author_rss')) {
    function mm_get_the_author_rss()
    {
        return mm_sanitize_podcast_text(get_the_term_list(get_the_ID(), MM_TAX_SPEAKER, '', ' &amp; ', ''));
    }
}

if (!function_exists('mm_the_author_rss')) {
    function mm_the_author_rss()
    {
        $author = mm_get_the_author_rss();
        if (!empty($author)) {
            echo sprintf('<itunes:author>%s</itunes:author>', $author);
        }
    }
}

if (!function_exists('mm_get_the_topics_rss')) {
    function mm_get_the_topics_rss()
    {
        return mm_sanitize_podcast_text(get_the_term_list(get_the_ID(), MM_TAX_TOPICS, '', ', ', ''));
    }
}

if (!function_exists('mm_the_topics_rss')) {
    function mm_the_topics_rss()
    {
        $topics = mm_get_the_topics_rss();
        if (!empty($topics)) {
            echo sprintf('<itunes:keywords>%s</itunes:keywords>', $topics);
        }
    }
}

if (!function_exists('mm_the_enclosure_rss')) {
    function mm_the_enclosure_rss($object)
    {
        if (empty($object)) return;

        echo sprintf('<enclosure url="%s" length="%s" type="%s" />', $object['url'], $object['size'], $object['mime']);
        echo sprintf('<guid>%s</guid>', $object['url']);

        if (!empty($object['duration'])) {
            echo sprintf('<itunes:duration>%s</itunes:duration>', $object['duration']);
        }
    }
}

if (!function_exists('mm_get_the_image_rss')) {
    function mm_get_the_image_rss()
    {
        global $post;

        $image = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
        if (!empty($image)) {
            if (is_array($image)) {
                $image = $image[0];
            }
            return $image;
        }

        $series = get_the_terms($post->ID, MM_TAX_SERIES);
        if (!empty($series)) {
            $image = Message_Manager::get_instance()->get_series_image(array_pop($series)->slug);
            if (!empty($image)) {
                return $image;
            }
        }

        return Message_Manager_Options::get_instance()->get('default-image');
    }
}

if (!function_exists('mm_the_image_rss')) {
    function mm_the_image_rss()
    {
        $image = mm_get_the_image_rss();
        if (!empty($image)) {
            echo sprintf('<itunes:image href="%s" />', mm_force_http($image));
        }
    }
}