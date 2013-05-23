<?php
/*
Plugin Name: Message Manager
Description: Manage audio and video sermon content as well as speakers, series, attachements, Bible verses and more.
Version: 2.0.0
Author: Chris Roemmich
Author URI: https:cr-wd.com
License: GPLv3
*/

/** Constants */
define('MM_VERSION', '2.0.0');
define('MM_PATH', plugin_dir_path(realpath(__FILE__)));
define('MM_URL', plugin_dir_url(realpath(__FILE__)));
define('MM_CSS_URL', MM_URL . 'css/');
define('MM_IMG_URL', MM_URL . 'img/');
define('MM_JS_URL', MM_URL . 'js/');
define('MM_INC_PATH', MM_PATH . 'inc/');
define('MM_VENDOR_URL', MM_URL . 'vendor/');
define('MM_VENDOR_PATH', MM_PATH . 'vendor/');
define('MM_VIEWS_URL', MM_URL . 'views/');
define('MM_VIEWS_PATH', MM_PATH . 'views/');

define('MM_CPT_MESSAGE', 'mm_cpt_message');
define('MM_TAX_SPEAKER', 'mm_tax_speaker');
define('MM_TAX_SERIES', 'mm_tax_series');
define('MM_TAX_TOPICS', 'mm_tax_topics');
define('MM_TAX_VENUES', 'mm_tax_venues');
define('MM_TAX_BOOKS', 'mm_tax_books');

define('MM_META_PREFIX', '_mm_meta_');

class Message_Manager
{

    /**
     * @var Message_Manager singleton instance of the message manager
     */
    private static $instance;

    /**
     * @var MMWPAlchemy_MetaBox The message details meta box
     */
    public $message_details_mb;

    /**
     * @var MMWPAlchemy_MetaBox The message media meta box
     */
    public $message_media_mb;

    /**
     * @var MMWPAlchemy_MetaBox The message attachments meta box
     */
    public $message_attachments_mb;

    /**
     * @return Message_Manager instance of the message manager
     */
    public static function get_instance()
    {
        if (empty(static::$instance)) {
            static::$instance = new Message_Manager();
        }
        return static::$instance;
    }

    /**
     * Construct a new Message Manager
     */
    private function __construct()
    {
        // register the custom post types and taxonomies
        add_action('init', array($this, 'register_post_types'));

        // override default templates
        //add_filter('single_template', array($this, 'template_filter'));
        //add_filter('archive_template', array($this, 'template_filter'));
        //add_filter('category_template', array($this, 'template_filter'));

        // enqueue css and javascript
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_backend_scripts'));

        // set up permalinks
        add_filter('request', array($this, 'filter_request'));
        add_filter('rewrite_rules_array', array($this, 'filter_rewrite_rules_array'));
        add_filter('post_link', array($this, 'filter_post_link'), 10, 2);
        add_filter('post_type_link', array($this, 'filter_post_link'), 10, 2);
        add_filter('term_link', array($this, 'filter_term_link'), 10, 2);

        // sort messages by the message details date metadata
        add_action('pre_get_posts', array($this, 'sort_messages_by_details_date'));

        // loads the template tags
        $this->load_template_tags();

        // setup the options page
        $this->setup_admin_page();

        // setup the message meta boxes
        $this->setup_message_meta_boxes();

        // setup the meta boxes for the series taxonomy
        $this->setup_series_meta_boxes();

        // setup file downloads
        $this->setup_downloads();

        // set up the podcast
        remove_all_actions('do_feed_podcast');
        add_action('do_feed_podcast', array($this, 'do_feed_podcast'));
        add_filter('post_limits', array($this, 'remove_query_limit'), 1, 10);
//
//		// register the widget
//		add_action('widgets_init', array($this, 'register_widget'));
    }

    /**
     * Registers the post types and taxonomies (action: init)
     */
    public function register_post_types()
    {
        require_once(MM_INC_PATH . 'cpt-message.php');
        require_once(MM_INC_PATH . 'tax-speaker.php');
        require_once(MM_INC_PATH . 'tax-series.php');
        require_once(MM_INC_PATH . 'tax-topics.php');
        require_once(MM_INC_PATH . 'tax-venues.php');
        require_once(MM_INC_PATH . 'tax-books.php');
    }

    /**
     * Locates the path of a view file
     * @param $view The view to locate
     * @return string The path of the view
     */
    public function locate_view_path($view)
    {
        if (file_exists(get_stylesheet_directory() . '/message-manager/' . $view)) {
            return get_stylesheet_directory() . '/message-manager/' . $view;
        } else if (file_exists(get_template_directory() . '/message-manager/' . $view)) {
            return get_template_directory() . '/message-manager/' . $view;
        } else {
            return MM_VIEWS_PATH . $view;
        }
    }

    /**
     * Locates the url of a view file
     * @param $view The view to locate
     * @return string The url of the view
     */
    public function locate_view_url($view)
    {
        if (file_exists(get_stylesheet_directory() . '/message-manager/' . $view)) {
            return get_stylesheet_directory_uri() . '/message-manager/' . $view;
        } else if (file_exists(get_template_directory() . '/message-manager/' . $view)) {
            return get_template_directory_uri() . '/message-manager/' . $view;
        } else {
            return MM_VIEWS_URL . $view;
        }
    }

    /**
     * Loads template tags from the stylesheet path, template path, and plugin.
     */
    public function load_template_tags()
    {
        $tag_files = array(trailingslashit(get_stylesheet_directory()) . 'message-manager/template-tags.php', trailingslashit(get_template_directory()) . 'message-manager/template-tags.php', MM_VIEWS_PATH . 'template-tags.php');
        foreach ($tag_files as $file) {
            if (file_exists($file)) {
                require_once($file);
            }
        }
    }

    /**
     * Filters the template location to include plugin templates (filters: single_template, category_template, archive_template)
     * @param $template The default template
     * @return string The filtered template
     */
    public function template_filter($template)
    {
        if (is_singular(MM_CPT_MESSAGE)) {
            $file = $this->locate_view_path('message.php');
        } else if (is_post_type_archive(MM_CPT_MESSAGE)) {
            $file = $this->locate_view_path('messages.php');
        } else if (is_tax(MM_TAX_SERIES)) {
            $file = $this->locate_view_path('series.php');
        } else if (is_tax(MM_TAX_SPEAKERS)) {
            $file = $this->locate_view_path('speaker.php');
        } else if (is_tax(MM_TAX_TOPICS)) {
            $file = $this->locate_view_path('topic.php');
        } else if (is_tax(MM_TAX_VENUES)) {
            $file = $this->locate_view_path('venue.php');
        } else if (is_tax(MM_TAX_BOOKS)) {
            $file = $this->locate_view_path('book.php');
        }
        return !empty($file) ? $file : $template;
    }

    /**
     * Gets a Message Manager Option
     * @param $option The option name
     * @param bool $default A default value
     * @return mixed|void The option or false
     */
    public function get_option($option, $default = false)
    {

        return (MM_META_PREFIX . $option);
        return get_option(MM_META_PREFIX . $option, $default);
    }

    /**
     * Sets/Creates a Message Manager Option
     * @param $option The option name
     * @param $value The value of the option
     * @param string $autoload If the option should be autoloaded with wordpress
     * @return bool True if set, false otherwise
     */
    public function set_option($option, $value, $autoload = 'no')
    {
        if ($return = add_option(MM_META_PREFIX . $option, $value, null, $autoload) === false) {
            $return = update_option(MM_META_PREFIX . $option, $value);
        }
        return $return;
    }

    /**
     * Enqueues the required backend scripts (action: admin_enqueue_scripts)
     */
    public function enqueue_backend_scripts()
    {
        global $post;
        if (!empty($post)) {
            if ($post->post_type == MM_CPT_MESSAGE) {
                wp_enqueue_style('jquery-ui-css', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/themes/smoothness/jquery-ui.min.css', array(), '1.10.3');
                wp_enqueue_style('mm-admin-css', MM_CSS_URL . 'admin.css', array(), MM_VERSION);
                wp_enqueue_script('mm-admin-js', MM_JS_URL . 'admin.js', array('jquery-ui-datepicker'), MM_VERSION);
            }
        }
    }

    /**
     * Enqueues the required frontend scripts (action: wp_enqueue_scripts)
     */
    public function enqueue_frontend_scripts()
    {
        if (is_singular(MM_CPT_MESSAGE) || is_post_type_archive(MM_CPT_MESSAGE)) {
            wp_enqueue_style('mm-mediaelement-css', MM_VENDOR_URL . 'mediaelement/mediaelementplayer.min.css', array(), '2.11.3');
            wp_enqueue_style('mm-mediaelement-skins', MM_VENDOR_URL . 'mediaelement/mejs-skins.css', array('mm-mediaelement-css'), '2.11.3');
            wp_enqueue_style('mm-styles', $this->locate_view_url('styles.css'), array('mm-mediaelement-skins'), MM_VERSION);

            wp_enqueue_script('mm-mediaelement-js', MM_VENDOR_URL . 'mediaelement/mediaelement-and-player.min.js', array('jquery'), '2.11.3');
            wp_enqueue_script('mm-mediaelement-ga-js', MM_VENDOR_URL . 'mediaelement/mep-feature-googleanalytics.js', array('mm-mediaelement-js'), '2.11.3');
        }
    }

    /**
     * Returns the base slug option
     * @param string $sub Sub-path of the base slug
     * @return string The base slug
     */
    public function get_base_slug($sub = '')
    {
        $slug = Message_Manager_Options::get_instance()->get('slug', 'messages');
        if (!empty($sub)) {
            return trailingslashit($slug) . $sub;
        }
        return untrailingslashit($slug);
    }

    /**
     * Filter request for series/message hierarchy (filter: request)
     * @param $request The unfiltered request
     * @return mixed The filtered request
     */
    public function filter_request($request)
    {
        if (!empty($request[MM_TAX_SERIES])) {
            $slug = $request[MM_TAX_SERIES];

            $term = get_term_by('slug', $slug, MM_TAX_SERIES);
            if ($term === false) {
                global $wpdb;
                $post = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type= %s", $slug, MM_CPT_MESSAGE));

                // if the message has a series, redirect the correct url
                $series = get_the_terms($post, MM_TAX_SERIES);
                if (!empty($series)) {
                    wp_redirect(get_permalink($post));
                    exit;
                } else {
                    // treat the request like a message
                    unset($request[MM_TAX_SERIES]);
                    $request['page'] = null;
                    $request[MM_CPT_MESSAGE] = $slug;
                    $request['post_type'] = MM_CPT_MESSAGE;
                    $request['name'] = $slug;
                }
            }
        }

        return $request;
    }

    /**
     * Modifies the post permalink to allow for series/message hierarchy (filters: post_link, post_type_link)
     * @param $link The unfiltered permalink
     * @param $post The post
     * @return string The filtered permalink
     */
    public function filter_post_link($link, $post)
    {
        // only modify for message post type
        if ($post->post_type != MM_CPT_MESSAGE) return $link;

        // return unless permalinks are enabled
        if (!get_option('permalink_structure')) return $link;

        $message_base = $this->get_base_slug();
        $base = home_url('/' . $message_base);

        if ($series = get_the_terms($post->ID, MM_TAX_SERIES)) {
            $link = $base . '/' . array_pop($series)->slug . '/' . $post->post_name;
        } else {
            $link = $base . '/' . $post->post_name;
        }

        return $link;
    }

    /**
     * Modifies a term permalink (filter: term_link)
     * @param $link The unfiltered permalink
     * @param $term The term
     * @return string The filtered permalink
     */
    public function filter_term_link($link, $term)
    {
        // only modify for series taxonomy
        if ($term->taxonomy != MM_TAX_SERIES) return $link;

        // return unless permalinks are enabled
        if (!get_option('permalink_structure')) return $link;

        $message_base = $this->get_base_slug();
        $base = home_url('/' . $message_base);

        return $base . '/' . $term->slug;
    }

    /**
     * Adds a number of rewrite rules used by Message Manager (filter: rewrite_rules_array)
     * @param $rules Rules to filter
     * @return array Filtered rules
     */
    public function filter_rewrite_rules_array($rules)
    {
        $base = $this->get_base_slug();

        $mm_rules = array(
            // podcast rules
            $base . '/podcast/?$' => 'index.php?post_type=' . MM_CPT_MESSAGE . '&feed=podcast',
            $base . '/feed/podcast/?$' => 'index.php?post_type=' . MM_CPT_MESSAGE . '&feed=podcast',
            $base . '/podcast.rss/?$' => 'index.php?post_type=' . MM_CPT_MESSAGE . '&feed=podcast',
            $base . '/feed/podcast.rss/?$' => 'index.php?post_type=' . MM_CPT_MESSAGE . '&feed=podcast',

            // {base}/{series}
            $base . '/([^/]+)/feed/podcast/?$' => 'index.php?' . MM_TAX_SERIES . '=$matches[1]&feed=$matches[2]',
            $base . '/([^/]+)/podcast/?$' => 'index.php?' . MM_TAX_SERIES . '=$matches[1]&feed=$matches[2]',
            $base . '/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?' . MM_TAX_SERIES . '=$matches[1]&paged=$matches[2]',
            $base . '/([^/]+)/?$' => 'index.php?' . MM_TAX_SERIES . '=$matches[1]',

            // {base}/{series}/{message}
            $base . '/[^/]+/([^/]+)/trackback/?$' => 'index.php?' . MM_CPT_MESSAGE . '=$matches[1]&tb=1',
            $base . '/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom|podcast)/?$' => 'index.php?' . MM_CPT_MESSAGE . '=$matches[1]&feed=$matches[2]',
            $base . '/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom|podcast)/?$' => 'index.php?' . MM_CPT_MESSAGE . '=$matches[1]&feed=$matches[2]',
            $base . '/[^/]+/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?' . MM_CPT_MESSAGE . '=$matches[1]&paged=$matches[2]',
            $base . '/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$' => 'index.php?' . MM_CPT_MESSAGE . '=$matches[1]&cpage=$matches[2]',
            $base . '/[^/]+/([^/]+)(/[0-9]+)?/?$' => 'index.php?' . MM_CPT_MESSAGE . '=$matches[1]&page=$matches[2]',
        );

        return $mm_rules + $rules;
    }

    /**
     * Sets up the admin page
     */
    function setup_admin_page()
    {
        require_once MM_INC_PATH . 'admin-page.php';
    }

    /**
     * Sets up media downloads
     */
    public function setup_downloads()
    {
        require_once(MM_INC_PATH . 'downloads.php');
    }

    /**
     * Modifies the query to sort messages by the details date meta key
     * @param $query WP_Query the query
     * @return mixed
     */
    function sort_messages_by_details_date($query)
    {
        if ($query->is_main_query() && !is_admin() && is_post_type_archive(MM_CPT_MESSAGE)) {
            $meta_key = MM_META_PREFIX . 'details_date';
            $query->set('meta_key', $meta_key);
            $query->set('meta_value', date('yy-mm-dd'));
            $query->set('meta_compare', '>=');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'DESC');
        }
        return $query;
    }

    /**
     * Sets up the meta boxes for the message custom post type
     */
    function setup_message_meta_boxes()
    {
        require_once(MM_VENDOR_PATH . 'wpalchemy/MetaBox.php');

        $this->message_details_mb = new MMWPAlchemy_MetaBox(array(
            'id' => MM_META_PREFIX . 'details',
            'title' => 'Details',
            'template' => MM_INC_PATH . 'meta-message-details.php',
            'types' => array(MM_CPT_MESSAGE),
            'mode' => WPALCHEMY_MODE_EXTRACT,
            'prefix' => MM_META_PREFIX . 'details_',
            'view' => WPALCHEMY_VIEW_ALWAYS_OPENED,
            'hide_screen_option' => true,
            'hide_editor' => false,
            'lock' => WPALCHEMY_LOCK_AFTER_POST_TITLE,
            //'save_filter' => array($this, 'validate_message_details'),
        ));

        $this->message_media_mb = new MMWPAlchemy_MetaBox(array(
            'id' => MM_META_PREFIX . 'media',
            'title' => 'Media',
            'template' => MM_INC_PATH . 'meta-message-media.php',
            'types' => array(MM_CPT_MESSAGE),
            'prefix' => MM_META_PREFIX . 'media_',
            'view' => WPALCHEMY_VIEW_ALWAYS_OPENED,
            'hide_screen_option' => true,
            'hide_editor' => false,
            'lock' => WPALCHEMY_LOCK_AFTER_POST_TITLE,
            //'save_filter' => array($this, 'validate_message_media'),
        ));

        $this->message_attachments_mb = new MMWPAlchemy_MetaBox(array(
            'id' => MM_META_PREFIX . 'attachments',
            'title' => 'Attachments',
            'template' => MM_INC_PATH . 'meta-message-attachments.php',
            'types' => array(MM_CPT_MESSAGE),
            'prefix' => MM_META_PREFIX . 'attachments_',
            'view' => WPALCHEMY_VIEW_ALWAYS_OPENED,
            'hide_screen_option' => true,
            'hide_editor' => false,
            'lock' => WPALCHEMY_LOCK_AFTER_POST_TITLE,
            //'save_filter' => array($this, 'validate_message_attachments'),
        ));
    }

    /**
     * Sets up the meta boxes for the series taxonomy
     */
    function setup_series_meta_boxes()
    {
        require_once MM_INC_PATH . 'meta-tax-series.php';
    }

    /**
     * Responds to the podcast feed type (action: do_feed_podcast)
     */
    function do_feed_podcast()
    {
        load_template($this->locate_view_path('feed-podcast.php'));
    }

    /**
     * Removes the query limit from the podcast to display all messages (filter: post_limits)
     * @param $limit
     * @return string
     */
    function remove_query_limit($limit)
    {
        if (is_admin()) return $limit;

        if (is_feed('podcast')) {
            return 'LIMIT 0, 99999';
        }

        return $limit;
    }

    /** BASIC API */

    /**
     * Helper function for setting message details without WP Alchemy
     * @param $message_id
     * @param $meta_name
     * @param $value
     */
    private function set_message_detail($message_id, $meta_name, $value)
    {
        // update the value
        update_post_meta($message_id, $meta_name, $value);

        // fields value for wpalchemy extract mode
        $fields = get_post_meta($message_id, MM_META_PREFIX . 'details_fields', true);
        $fields[] = $meta_name;
        update_post_meta($message_id, MM_META_PREFIX . 'details_fields', array_unique($fields));
    }

    /**
     * Sets the date of a message
     * @param $message_id the message id
     * @param $date the date in the format yyyy-mm-dd
     */
    public function set_message_date($message_id, $date)
    {
        $meta_name = MM_META_PREFIX . 'details_date';
        $this->set_message_detail($message_id, $meta_name, $date);
    }

    /**
     * Sets the verses of a message
     * @param $message_id the message id
     * @param $verses Semi-colon seperated list of verses
     */
    public function set_message_verses($message_id, $verses)
    {
        $meta_name = MM_META_PREFIX . 'details_verses';
        $this->set_message_detail($message_id, $meta_name, $verses);
    }

    /**
     * Sets the summary of a message
     * @param $message_id
     * @param $summary
     */
    public function set_message_summary($message_id, $summary)
    {
        $meta_name = MM_META_PREFIX . 'details_summary';
        $this->set_message_detail($message_id, $meta_name, $summary);
    }

    /**
     * Sets the featured images of the message
     * @param $message_id
     * @param $attachment_id
     */
    public function set_message_image($message_id, $attachment_id)
    {
        update_post_meta($message_id, '_thumbnail_id', $attachment_id);
    }

    /**
     * Sets the description of a series
     * @param $series_slug
     * @param $description
     */
    public function set_series_description($series_slug, $description)
    {
        $term = get_term_by('slug', $series_slug, MM_TAX_SERIES);
        $term_id = $term->term_id;

        wp_update_term($term_id, MM_TAX_SERIES, array('description' => $description));
    }

    /**
     * Sets the featured image for a series
     * @param $series_slug
     * @param $attachment_id
     */
    public function set_series_image($series_slug, $attachment_id)
    {
        $term = get_term_by('slug', $series_slug, MM_TAX_SERIES);
        $term_id = $term->term_id;

        update_tax_meta($term_id, MM_META_PREFIX . 'series_image_id', $attachment_id);
    }

    public function get_series_image($series_slug)
    {
        $term = get_term_by('slug', $series_slug, MM_TAX_SERIES);
        $term_id = $term->term_id;

        return get_tax_meta($term_id, MM_META_PREFIX . 'series_image_id', false);
    }

    /**
     * Sets the audio for a message
     * @param $message_id
     * @param $url
     * @param boolean $show_in_attachments
     */
    public function set_message_audio($message_id, $url, $show_in_attachments = true)
    {
        $meta_name = MM_META_PREFIX . 'media';

        $meta = get_post_meta($message_id, $meta_name, true);

        if (!is_array($meta)) {
            $meta = array();
        }

        $meta['audio-url'] = $url;
        if ($show_in_attachments) {
            $meta['audio-attachment'] = 'yes';
        } else {
            $meta['audio-attachment'] = 'no';
        }

        update_post_meta($message_id, $meta_name, $meta);
    }

    /**
     * Sets the video for a mesasge
     * @param $message_id
     * @param $type url|vimeo|youtube|embedded
     * @param $value
     * @param boolean $show_in_attachments
     */
    public function set_message_video($message_id, $type, $value, $show_in_attachments = true)
    {
        $meta_name = MM_META_PREFIX . 'media';

        $meta = get_post_meta($message_id, $meta_name, true);
        if (!is_array($meta)) {
            $meta = array();
        }

        $meta['video-type'] = $type;

        switch ($type) {
            case 'url':
            case 'vimeo':
            case 'youtube':
                $meta['video-url'] = $value;
                if ($type == 'url') {
                    if ($show_in_attachments) {
                        $meta['video-attachment'] = 'yes';
                    } else {
                        $meta['video-attachment'] = 'no';
                    }
                }
            case 'embbeded':
                $meta['video-embedded'] = $value;
        }

        update_post_meta($message_id, $meta_name, $meta);
    }

    /**
     * Adds an attachment to a message
     * @param $message_id
     * @param $url
     * @param $title
     * @param $description
     */
    public function add_message_attachment($message_id, $url, $title = null, $description = null)
    {
        $meta_name = MM_META_PREFIX . 'attachments';
        $meta = get_post_meta($message_id, $meta_name, true);

        if (empty($meta['attachment'])) {
            $meta['attachment'] = array();
        }

        $meta['attachment'][] = array('url' => $url, 'title' => $title, 'description' => $description);

        $meta_name = MM_META_PREFIX . 'attachments';
        update_post_meta($message_id, $meta_name, $meta);
    }

}

// initialize the plugin
$message_manager = Message_Manager::get_instance();

function do_not_cache_feeds(&$feed)
{
    $feed->enable_cache(false);
}

add_action('wp_feed_options', 'do_not_cache_feeds');