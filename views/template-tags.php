<?php
if (!function_exists('mm_get_latest_message')) {
    function mm_get_latest_message()
    {
        return Message_Manager::get_instance()->get_latest_message();
    }
}


if (!function_exists('mm_get_the_permalink')) {
    function mm_get_the_permalink($post = 0, $maybe_series = true)
    {
        $post = get_post($post);
        if ($maybe_series && is_post_type_archive(MM_CPT_MESSAGE)) {
            $series = Message_Manager::get_instance()->get_message_series($post);
            if (!empty($series)) {
                return get_term_link($series);
            }
        }
        return get_permalink($post);
    }
}


if (!function_exists('mm_the_permalink')) {
    function mm_the_permalink($maybe_series = true, $post = 0)
    {
        echo esc_url(apply_filters('the_permalink', mm_get_the_permalink($post, $maybe_series)));
    }
}

if (!function_exists('mm_get_the_thumbnail_internal')) {
    function mm_get_the_thumbnail_internal($post_thumbnail_id = 0, $post_id, $size = 'post-thumbnail', $attr = '')
    {
        if (empty($post_thumbnail_id)) return;

        $size = apply_filters('post_thumbnail_size', $size);
        if ($post_thumbnail_id) {
            do_action('begin_fetch_post_thumbnail_html', $post_id, $post_thumbnail_id, $size);
            if (in_the_loop())
                update_post_thumbnail_cache();
            $html = wp_get_attachment_image($post_thumbnail_id, $size, false, $attr);
            do_action('end_fetch_post_thumbnail_html', $post_id, $post_thumbnail_id, $size);
        } else {
            $html = '';
        }
        return apply_filters('post_thumbnail_html', $html, $post_id, $post_thumbnail_id, $size, $attr);
    }
}

if (!function_exists('mm_get_the_thumbnail')) {
    function mm_get_the_thumbnail($post = 0, $size = MM_CPT_MESSAGE, $attr = '', $maybe_series = true)
    {
        $post = get_post($post);

        $post_thumbnail_id = 0;

        if ($maybe_series && is_post_type_archive(MM_CPT_MESSAGE)) {
            $series = Message_Manager::get_instance()->get_message_series($post);
            if (!empty($series)) {
                $post_thumbnail_id = Message_Manager::get_instance()->get_series_thumbnail_id($series, false);
            }
        }

        if (!$post_thumbnail_id) {
            $post_thumbnail_id = Message_Manager::get_instance()->get_message_thumbnail_id($post, false);
        }

        if ($maybe_series && is_post_type_archive(MM_CPT_MESSAGE)) {
            $series = Message_Manager::get_instance()->get_message_series($post);
            if (!empty($series)) {
                $post_thumbnail_id = Message_Manager::get_instance()->get_series_thumbnail_id($series, true);
                if (!$post_thumbnail_id) {
                    $post_thumbnail_id = Message_Manager::get_instance()->get_default_series_thumbnail_id();
                }
            }
        }

        if (!$post_thumbnail_id) {
            $post_thumbnail_id = Message_Manager::get_instance()->get_default_message_thumbnail_id();
        }

        return mm_get_the_thumbnail_internal($post_thumbnail_id, $post, $size, $attr);
    }
}

if (!function_exists('mm_the_thumbnail')) {
    function mm_the_thumbnail($size = MM_CPT_MESSAGE, $attr = '', $maybe_series = true, $post = 0)
    {
        echo mm_get_the_thumbnail($post, $size, $attr, $maybe_series);
    }
}

if (!function_exists('mm_get_the_title')) {
    function mm_get_the_title($post = 0, $maybe_series = true)
    {
        $post = get_post($post);
        if ($maybe_series && is_post_type_archive(MM_CPT_MESSAGE)) {
            $series = Message_Manager::get_instance()->get_message_series($post);
            if (!empty($series)) {
                if (!empty($series->name)) {
                    return apply_filters('the_title', $series->name, 0);
                } else {
                    return __('Series: ' . get_the_title($post), 'message-manager');
                }
            }
        }
        return get_the_title($post);
    }
}

if (!function_exists('mm_the_title')) {
    function mm_the_title($maybe_series = true, $post = 0)
    {
        echo mm_get_the_title($post, $maybe_series);
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

if (!function_exists('mm_get_the_date')) {
    function mm_get_the_date($post = 0, $format = '', $maybe_series = true)
    {
        $post = get_post($post);
        $post_id = empty($post) ? 0 : $post->ID;

        if ($maybe_series && is_post_type_archive(MM_CPT_MESSAGE)) {
            $series = Message_Manager::get_instance()->get_message_series($post);
            if (!empty($series)) {
                if (empty($format)) $format = 'F Y';

                $series_info = Message_Manager::get_instance()->get_series_info($series->term_id);

                if (!empty($series_info->start_message_date)) {
                    $start = $series_info->start_message_date;
                    $end = $series_info->end_message_date;

                    $start = mm_format_date($start, $format);
                    $end = mm_format_date($end, $format);

                    if ($start == $end) {
                        return $start;
                    } else {
                        return $start . ' - ' . $end;
                    }
                }
            }
        }

        if (empty($format)) $format = 'F j, Y';

        Message_Manager::get_instance()->message_details_mb->the_meta($post_id);
        $date = Message_Manager::get_instance()->message_details_mb->get_the_value('date');
        if (empty($date)) {
            $date = get_post_modified_time('Y-m-d', false, $post, false);
        }

        return mm_format_date($date, $format);
    }
}

if (!function_exists('mm_the_date')) {
    function mm_the_date($format = '', $maybe_series = true, $post = 0)
    {
        echo mm_get_the_date($post, $format, $maybe_series);
    }
}

if (!function_exists('mm_get_the_term')) {
    function mm_get_the_term($term = 0, $taxonomy = MM_TAX_SERIES)
    {
        if (empty($term)) {
            $temp = get_queried_object();
            if (!empty($temp->term_id)) {
                $term = $temp;
            }
        }
        if (is_numeric($term)) {
            $term = get_term_by('id', $term, $taxonomy);
        } else if (is_string($term)) {
            $term = get_term_by('slug', $term, $taxonomy);
        }
        return $term;
    }
}

if (!function_exists('mm_get_the_term_title')) {
    function mm_get_the_term_title()
    {
        $term = mm_get_the_term();

        if (!empty($term)) {
            return $term->name;
        }
    }
}

if (!function_exists('mm_the_term_title')) {
    function mm_the_term_title()
    {
        echo esc_attr(mm_get_the_term_title());
    }
}

if (!function_exists('mm_get_the_term_description')) {
    function mm_get_the_term_description()
    {
        $term = mm_get_the_term();

        if (!empty($term)) {
            return $term->description;
        }
    }
}

if (!function_exists('mm_the_term_description')) {
    function mm_the_term_description()
    {
        echo mm_get_the_term_description();
    }
}

if (!function_exists('mm_get_the_series_image')) {
    function mm_get_the_series_image($series = 0, $size = MM_CPT_MESSAGE, $attr = '')
    {
        $series = mm_get_the_term($series);

        if (empty($series)) return;

        $thumbnail_id = Message_Manager::get_instance()->get_series_thumbnail_id($series, true);

        if (!$thumbnail_id) {
            $thumbnail_id = Message_Manager::get_instance()->get_default_series_thumbnail_id();
        }

        return mm_get_the_thumbnail_internal($thumbnail_id, 0, $size, $attr);
    }
}

if (!function_exists('mm_the_series_image')) {
    function mm_the_series_image($size = MM_CPT_MESSAGE, $attr = '')
    {
        echo mm_get_the_series_image(0, $size, $attr);
    }
}

if (!function_exists('mm_has_video')) {
    function mm_has_video($post = 0)
    {
        $post = get_post($post);

        $video = Message_Manager::get_instance()->get_message_video($post);

        return !(empty($video['url']) && empty($video['url-id']) && empty($video['embedded']));
    }
}

if (!function_exists('mm_get_the_youtube_id')) {
    function mm_get_the_youtube_id($url)
    {
        $id = preg_replace('~
				# Match non-linked youtube URL in the wild. (Rev:20111012)
				https?://         # Required scheme. Either http or https.
				(?:[0-9A-Z-]+\.)? # Optional subdomain.
				(?:               # Group host alternatives.
				youtu\.be/      # Either youtu.be,
				| youtube\.com    # or youtube.com followed by
				\S*             # Allow anything up to VIDEO_ID,
				[^\w\-\s]       # but char before ID is non-ID char.
		)                 # End host alternatives.
				([\w\-]{11})      # $1: VIDEO_ID is exactly 11 chars.
				(?=[^\w\-]|$)     # Assert next char is non-ID or EOS.
				(?!               # Assert URL is not pre-linked.
				[?=&+%\w]*      # Allow URL (query) remainder.
				(?:             # Group pre-linked alternatives.
				[\'"][^<>]*>  # Either inside a start tag,
				| </a>          # or inside <a> element text contents.
		)               # End recognized pre-linked alts.
		)                 # End negative lookahead assertion.
				[?=&+%\w-]*        # Consume any URL (query) remainder.
				~ix',
            '$1',
            $url);
        if ($id == $url) return null;
        return $id;
    }
}

if (!function_exists('mm_get_the_vimeo_id')) {
    function mm_get_the_vimeo_id($url)
    {
        $id = preg_replace('~https?://vimeo\.com\/([0-9]{1,10})~ix', '$1', $url);
        if ($id == $url) return null;
        return $id;
    }
}

if (!function_exists('mm_get_the_video')) {
    function mm_get_the_video($post = 0)
    {
        $post = get_post($post);
        $video = Message_Manager::get_instance()->get_message_video($post);

        if (!mm_has_video($post)) return;

        $height = htmlentities(preg_replace('/[^0-9]/', '', Message_Manager_Options::get_instance()->get('video-height')));
        $width = htmlentities(preg_replace('/[^0-9]/', '', Message_Manager_Options::get_instance()->get('video-width')));

        $html = '';

        $url = get_permalink($post);
        $scheme = parse_url($url, PHP_URL_SCHEME);
        switch ($video['type']) {
            case 'url':
                //todo:
                break;
            case 'vimeo':
                $video_id = mm_get_the_vimeo_id($video['url']);
                if (!empty($video_id)) {
                    $options = htmlentities(Message_Manager_Options::get_instance()->get('vimeo-url-options'));
                    $html .= '<div class="flex-video widescreen vimeo">';
                    $html .= "<iframe src=\"{$scheme}://player.vimeo.com/video/{$video_id}{$options}\" width=\"$width\" height=\"$height\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>";
                    $html .= '</div>';
                }
                break;
            case 'youtube':
                $video_id = mm_get_the_youtube_id($video['url']);
                if (!empty($video_id)) {
                    $html .= '<div class="flex-video widescreen">';
                    $html .= "<iframe class=\"youtube-player\" type=\"text/html\" width=\"$width\" height=\"$width\" src=\"{$scheme}://www.youtube.com/embed/$video_id\" frameborder=\"0\"></iframe>";
                    $html .= '</div>';
                }
                break;
            case 'embedded':
                $html .= $video['embedded'];
                break;
        }

        return $html;
    }
}

if (!function_exists('mm_the_video')) {
    function mm_the_video()
    {
        echo mm_get_the_video();
    }
}

if (!function_exists('mm_has_audio')) {
    function mm_has_audio($post = 0)
    {
        $post = get_post($post);

        $audio = Message_Manager::get_instance()->get_message_audio($post);

        return !(empty($audio['url']) && empty($audio['url-id']));
    }
}

if (!function_exists('mm_get_the_audio')) {
    function mm_get_the_audio($post = 0)
    {
        $post = get_post($post);
        $audio = Message_Manager::get_instance()->get_message_audio($post);

        if (!mm_has_audio($post)) return;

        if (!empty($audio['url-id'])) {
            $audio_url = wp_get_attachment_url($audio['url-id']);
        }
        if (empty($audio_url)) {
            $audio_url = $audio['url'];
        }
        $id = 'audio_' . $post->ID;
        $html = "<audio id=\"$id\" src=\"$audio_url\" type=\"audio/mp3\" controls=\"controls\"></audio>";
        $html .= "<script type=\"text/javascript\">
			//<![CDATA[
			jQuery(document).ready(function($) {
				$('#$id').mediaelementplayer({
					features: ['playpause','progress','current','duration','volume','googleanalytics']
				});
			});
			//]]>
			</script>";

        return $html;
    }
}

if (!function_exists('mm_the_audio')) {
    function mm_the_audio()
    {
        echo mm_get_the_audio();
    }
}

if (!function_exists('mm_get_the_meta')) {
    function mm_get_the_meta($post = 0)
    {
        $post = get_post($post);

        $items = array();

        $items[] = get_the_term_list($post->ID, MM_TAX_SPEAKER, '', ' &amp; ', '');
        $items[] = mm_get_the_date($post, 'F j, Y');
        Message_Manager::get_instance()->message_details_mb->the_meta($post);
        $items[] = Message_Manager::get_instance()->message_details_mb->get_the_value('verses');

        $html = '';

        for ($i = 0; $i < count($items); $i++) {
            $html .= $items[$i];
            if ($i + 1 < count($items) && !empty($items[$i + 1])) {
                $html .= ' / ';
            }
        }

        return $html;
    }
}

if (!function_exists('mm_the_meta')) {
    function mm_the_meta()
    {
        echo mm_get_the_meta();
    }
}

if (!function_exists('mm_get_the_content')) {
    function mm_get_the_content($post = 0)
    {
        $post = get_post($post);

        $content = get_the_content(null, false, $post);
        if (empty($content)) {
            Message_Manager::get_instance()->message_details_mb->the_meta($post->ID);
            $content = Message_Manager::get_instance()->message_details_mb->get_the_value('summary');
        }
        return $content;
    }
}

if (!function_exists('mm_the_content')) {
    function mm_the_content()
    {
        $post = get_post();
        $content = apply_filters('the_content', mm_get_the_content($post), $post->ID);
        echo str_replace(']]>', ']]&gt;', $content);
    }
}

if (!function_exists('mm_get_the_back_button')) {
    function mm_get_the_back_button($post = 0)
    {
        $post = get_post($post);

        $series = Message_Manager::get_instance()->get_message_series($post);

        if (empty($series)) {
            $url = get_post_type_archive_link(MM_CPT_MESSAGE);
            $html = "<h4>&larr; <a href=\"{$url}\" title=\"Return to Messages\">Return to Messages</a></h4>";
        } else {
            $url = get_term_link($series, MM_TAX_SPEAKER);
            $html = "<h4>&larr; <a href=\"{$url}\" title=\"Return to Series\">Return to Series</a></h4>";
        }

        return $html;
    }
}

if (!function_exists('mm_the_back_button')) {
    function mm_the_back_button()
    {
        echo mm_get_the_back_button();
    }
}

if (!function_exists('mm_get_the_series_list')) {
    function mm_get_the_series_list($post = 0, $show = 5)
    {
        $post = get_post($post);
        $series = Message_Manager::get_instance()->get_message_series($post);

        if (empty($series)) return;

        $series_name = esc_attr($series->name);
        $series_url = get_term_link($series, MM_TAX_SPEAKER);

        $html = '<div class="mm-series-list-widget">';
        $html .= "<h4>Series: <a href=\"$series_url\" title=\"$series_name\">$series_name</a></h4>";

        $messages = Message_Manager::get_instance()->get_messages_in_series($series);
        $count = count($messages);
        if ($show > $count) {
            $show = $count;
        }

        $message_index = -1;
        $start = -1;
        $end = -1;

        for ($i = 0; $i < $count; $i++) {
            if ($messages[$i]->ID == $post->ID) {
                $message_index = $i;
                $start = $message_index;
                $end = $message_index;
                while ($show > 0) {
                    if ($end < $count && $show > 0) {
                        $end++;
                        $show--;
                    }
                    if ($start > 0 && $show > 0) {
                        $start--;
                        $show--;
                    }
                }
            }
        }
        $html .= '<ul class="mm-series-list">';
        for ($i = 0; $i < $count; $i++) {
            if ($i < $start || $i >= $end)
                continue;

            $message = $messages[$i];

            $title = esc_attr(mm_get_the_title($message));
            $link = esc_url(mm_get_the_permalink($message));
            $date = esc_attr(mm_get_the_date($message));

            $html .= ($i == $message_index) ? '<li class="active">' : '<li>';
            $html .= "<a href=\"$link\" title=\"$title\">";
            $html .= '<h5>' . $title . '</h5>';
            $html .= '<span>' . $date . '</span>';
            $html .= '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('mm_the_series_list')) {
    function mm_the_series_list()
    {
        echo mm_get_the_series_list();
    }
}

if (!function_exists('mm_get_the_downloads')) {
    function mm_get_the_downloads($post = 0)
    {
        $post = get_post($post);

        $downloads = Message_Manager::get_instance()->get_message_downloads($post);

        if (empty($downloads)) return;

        $html = '<div class="mm-download-list-widget">';
        $html .= "<h4>Downloads</h4>";

        $html .= '<ul class="mm-download-list">';
        foreach ($downloads as $download) {
            if (empty($download['download_url'])) continue;

            $html .= empty($download['mime_type']) ? '<li>' : "<li class=\"mm-mime-icon mm-mime-{$download['mime_type']}\">";

            $title = '';
            switch ($download['type']) {
                case 'video':
                    $title = 'Video';
                    if (!empty($download['mime_type'])) {
                        $title = strtoupper($download['mime_type']) . ' ' . $title;
                    }
                    break;
                case 'audio':
                    $title = 'Audio';
                    if (!empty($download['mime_type'])) {
                        $title = strtoupper($download['mime_type']) . ' ' . $title;
                    }
                    break;
                case 'attachment':
                    if (!empty($download['title'])) {
                        $title = $download['title'];
                    } else {
                        $title = $download['base_name'];
                    }
                    break;
            }
            $title = esc_attr($title);

            $html .= "<a href=\"{$download['download_url']}\" title=\"$title\">" . $title . "</a>";

            $html .= '</li>';
        }
        $html .= '</ul>';

        $html .= '</div>';


        return $html;
    }
}

if (!function_exists('mm_the_downloads')) {
    function mm_the_downloads()
    {
        echo mm_get_the_downloads();
    }
}