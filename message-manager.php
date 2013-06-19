<?php
/*
Plugin Name: Message Manager
Description: Manage audio and video sermon content as well as speakers, series, attachements, Bible verses and more.
Version: 2.0.0
Author: Chris Roemmich
Author URI: https:cr-wd.com
License: MIT
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
        add_filter('single_template', array($this, 'template_filter'));
        add_filter('archive_template', array($this, 'template_filter'));
        add_filter('taxonomy_template', array($this, 'template_filter'));

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
        add_action('pre_get_posts', array($this, 'pre_get_posts'));

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

        add_action('wp_ajax_nopriv_mm_ajax_load_more', array($this, 'ajax_load_more'));
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
            $this->load_template_tags();
            $file = $this->locate_view_path('message.php');
        } else if (is_post_type_archive(MM_CPT_MESSAGE)) {
            $this->load_template_tags();
            $file = $this->locate_view_path('messages.php');
        } else if (is_tax(MM_TAX_SERIES)) {
            $this->load_template_tags();
            $file = $this->locate_view_path('series.php');
        } else if (is_tax(MM_TAX_SPEAKER)) {
            $this->load_template_tags();
            $file = $this->locate_view_path('speaker.php');
        } else if (is_tax(MM_TAX_TOPICS)) {
            $this->load_template_tags();
            $file = $this->locate_view_path('topic.php');
        } else if (is_tax(MM_TAX_VENUES)) {
            $this->load_template_tags();
            $file = $this->locate_view_path('venue.php');
        } else if (is_tax(MM_TAX_BOOKS)) {
            $this->load_template_tags();
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
        if (is_singular(MM_CPT_MESSAGE) || $this->is_message_manager_archive()) {
            wp_enqueue_style('mm-mediaelement-css', MM_VENDOR_URL . 'mediaelement/mediaelementplayer.min.css', array(), '2.12.0');
            wp_enqueue_style('mm-mediaelement-skins', MM_VENDOR_URL . 'mediaelement/mejs-skins.css', array('mm-mediaelement-css'), '2.12.0');
            wp_enqueue_style('mm-styles', $this->locate_view_url('styles.css'), array('mm-mediaelement-skins'), MM_VERSION);

            wp_enqueue_script('mm-mediaelement-js', MM_VENDOR_URL . 'mediaelement/mediaelement-and-player.min.js', array('jquery'), '2.12.0');
            wp_enqueue_script('mm-mediaelement-ga-js', MM_VENDOR_URL . 'mediaelement/mep-feature-googleanalytics.js', array('mm-mediaelement-js'), '2.12.0');
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
            // message archive paging
            $base . '/page/?([0-9]{1,})/?$' => 'index.php?post_type=mm_cpt_message&paged=$matches[1]',

            // podcast rules
            $base . '/podcast/?$' => 'index.php?post_type=' . MM_CPT_MESSAGE . '&feed=podcast',
            $base . '/feed/podcast/?$' => 'index.php?post_type=' . MM_CPT_MESSAGE . '&feed=podcast',
            $base . '/podcast.rss/?$' => 'index.php?post_type=' . MM_CPT_MESSAGE . '&feed=podcast',
            $base . '/feed/podcast.rss/?$' => 'index.php?post_type=' . MM_CPT_MESSAGE . '&feed=podcast',

            // speakers
            $base . '/speaker/([^/]+)/feed/(feed|rdf|rss|rss2|atom|podcast)/?$' => 'index.php?mm_tax_speaker=$matches[1]&feed=$matches[2]',
            $base . '/speaker/([^/]+)/(feed|rdf|rss|rss2|atom|podcast)/?$' => 'index.php?mm_tax_speaker=$matches[1]&feed=$matches[2]',
            $base . '/speaker/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?mm_tax_speaker=$matches[1]&paged=$matches[2]',
            $base . '/speaker/([^/]+)/?$' => 'index.php?mm_tax_speaker=$matches[1]',

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
            $base . '/[^/]+/([^/]+)(/[0-9]+)?/?$' => 'index.php?' . MM_CPT_MESSAGE . '=$matches[1]&paged=$matches[2]',
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

    public function select_messages()
    {
        global $wp_query;

        var_dump($wp_query);
    }

    public function is_message_manager_archive()
    {
        return is_post_type_archive(MM_CPT_MESSAGE) || is_tax(MM_TAX_SERIES) || is_tax(MM_TAX_BOOKS) || is_tax(MM_TAX_TOPICS) || is_tax(MM_TAX_SPEAKER) || is_tax(MM_TAX_VENUES);
    }

    /**
     * Modifies the main message archive query to include only a single post per series and sort
     * by the details date meta key
     * @param $query WP_Query the query
     * @return mixed
     */
    function pre_get_posts($query)
    {
        if ($query->is_main_query() && !is_admin() && $this->is_message_manager_archive()) {

            $query->set('meta_key', $this->message_details_mb->get_the_name('date'));
            $query->set('meta_value', date('yy-mm-dd'));
            $query->set('meta_compare', '>=');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'DESC');

            if (!is_feed() && is_post_type_archive(MM_CPT_MESSAGE)) {
                $query->set('post__in', $this->get_filtered_message_ids());
            }

            if (!is_feed() && !is_tax(MM_TAX_SPEAKER)) {
                $query->set('posts_per_page', -1);
            }
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
     * Loads template tags from the stylesheet path, template path, and plugin.
     */
    public function load_podcast_tags()
    {
        $tag_files = array(trailingslashit(get_stylesheet_directory()) . 'message-manager/podcast-tags.php', trailingslashit(get_template_directory()) . 'message-manager/podcast-tags.php', MM_VIEWS_PATH . 'podcast-tags.php');
        foreach ($tag_files as $file) {
            if (file_exists($file)) {
                require_once($file);
            }
        }
    }

    /**
     * Responds to the podcast feed type (action: do_feed_podcast)
     */
    function do_feed_podcast()
    {
        $this->load_podcast_tags();

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

    /**
     * Gets the $num of most recent messages
     * @param int $num The number of messages
     * @return array
     */
    public function get_recent_messages($num = 5)
    {
        $messages = get_posts(array(
            'numberposts' => $num,
            'post_type' => MM_CPT_MESSAGE,
            'post_status' => 'publish',
            'meta_key' => MM_META_PREFIX . 'details_date',
            'meta_value' => date('yy-mm-dd'),
            'meta_compare' => '>=',
            'orderby' => 'meta_value',
            'order' => 'DESC',
        ));

        return is_wp_error($messages) ? array() : $messages;
    }

    /**
     * Gets the messages in the series
     * @param $series
     * @param $num
     * @return array
     */
    public function get_messages_in_series($series, $num = -1)
    {
        $messages = get_posts(array(
            'posts_per_page' => $num,
            'post_type' => MM_CPT_MESSAGE,
            MM_TAX_SERIES => $series->slug,
            'post_status' => 'publish',
            'meta_key' => MM_META_PREFIX . 'details_date',
            'meta_value' => date('yy-mm-dd'),
            'meta_compare' => '>=',
            'orderby' => 'meta_value',
            'order' => 'DESC',
        ));
        return $messages;
    }

    /**
     * @return mixed The latest message or null
     */
    public function get_latest_message()
    {
        $messages = $this->get_recent_messages(1);

        return empty($message) ? null : $messages[0];
    }

    /**
     * Returns all of the ids used to build the messages archive page
     * @return array
     */
    public function get_filtered_message_ids()
    {
        global $wpdb;

        $cpt_message = MM_CPT_MESSAGE;
        $tax_series = MM_TAX_SERIES;
        $date_meta = $this->message_details_mb->get_the_name('date');

        $query = "
            SELECT ids.ID FROM (
                (SELECT {$wpdb->posts}.ID FROM {$wpdb->posts}
                    WHERE {$wpdb->posts}.ID NOT IN (SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = '{$tax_series}')
                    AND {$wpdb->posts}.post_type = '{$cpt_message}' AND ({$wpdb->posts}.post_status = 'publish' OR {$wpdb->posts}.post_status = 'private'))
                UNION ALL
                (SELECT series_message_ids.ID FROM (
					SELECT {$wpdb->posts}.ID, term_id FROM wp_posts
                    LEFT OUTER JOIN (SELECT tr.object_id, tt.* FROM {$wpdb->term_relationships} AS tr INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = '{$tax_series}') as join1 ON join1.object_id=ID
                    LEFT OUTER JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key='{$date_meta}'
                    WHERE {$wpdb->posts}.ID IN (SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = '{$tax_series}')
                    AND {$wpdb->posts}.post_type = '{$cpt_message}' AND ({$wpdb->posts}.post_status = 'publish' OR {$wpdb->posts}.post_status = 'private')
                    ORDER BY {$wpdb->postmeta}.meta_value DESC
                    ) as series_message_ids
                    GROUP BY term_id)
            ) as ids
            GROUP BY ids.ID";

        $items = $wpdb->get_results($query, ARRAY_N);

        if (!is_wp_error($items)) {
            $ids = array();
            foreach ($items as $item) {
                $ids[] = $item[0];
            }
            return $ids;
        }
        return $items;
    }

    /**
     * Gets information about a series by id.
     * @param $term_id
     * @return mixed
     */
    public function get_series_info($term_id)
    {
        global $wpdb;

        $cpt_message = MM_CPT_MESSAGE;
        $tax_series = MM_TAX_SERIES;
        $meta_prefix = MM_META_PREFIX;

        $term_id = $wpdb->escape($term_id);

        $query =
            "
            SELECT * FROM (
                (SELECT * from {$wpdb->terms}
                WHERE {$wpdb->terms}.term_id = {$term_id}
                LIMIT 1) as terms,

                (SELECT ID as start_message_id, meta_value as start_message_date FROM {$wpdb->posts}
                LEFT OUTER JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key='{$meta_prefix}details_date'
                WHERE {$wpdb->posts}.id IN (SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = '{$tax_series}' AND tt.term_id={$term_id})
                AND {$wpdb->posts}.post_type = '{$cpt_message}' AND ({$wpdb->posts}.post_status = 'publish' OR {$wpdb->posts}.post_status = 'private') ORDER BY meta_value ASC LIMIT 1) as tmp1,

                (SELECT ID as end_message_id, meta_value as end_message_date FROM {$wpdb->posts}
                LEFT OUTER JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID AND {$wpdb->postmeta}.meta_key='{$meta_prefix}details_date'
                WHERE {$wpdb->posts}.id IN (SELECT tr.object_id FROM {$wpdb->term_relationships} AS tr INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy = '{$tax_series}' AND tt.term_id={$term_id})
                AND {$wpdb->posts}.post_type = '{$cpt_message}' AND ({$wpdb->posts}.post_status = 'publish' OR {$wpdb->posts}.post_status = 'private') ORDER BY meta_value DESC LIMIT 1) as tmp2
            )
            LEFT OUTER JOIN {$wpdb->term_taxonomy} ON {$wpdb->term_taxonomy}.term_id = terms.term_id
            LIMIT 1";

        $result = $wpdb->get_row($query);

        return $result;
    }

    /**
     * @param int|\WP_Post $post The post
     * @return bool|mixed The series (term) or false
     */
    public function get_message_series($post = 0)
    {
        $series = get_the_terms($post, MM_TAX_SERIES);
        if (!empty($series)) {
            return array_pop($series);
        }
        return false;
    }

    /**
     * @param $post WP_Post The post object
     * @param bool $derive_from_series When true, if the thumbnail id is empty, the series image will be used.
     * @return bool|int The thumbnail id of the post thumbnail or false
     */
    public function get_message_thumbnail_id($post, $derive_from_series = true)
    {
        if (empty($post)) return false;

        $post_thumbnail_id = get_post_thumbnail_id($post->ID);

        if (!$post_thumbnail_id && $derive_from_series) {
            $series = $this->get_message_series($post);
            $post_thumbnail_id = $this->get_series_thumbnail_id($series, false);
        }

        return $post_thumbnail_id;
    }

    /**
     * @return bool|int The thumbnail id of the default message thumbnail or false if not set.
     */
    public function get_default_message_thumbnail_id()
    {
        $default = Message_Manager_Options::get_instance()->get('default-message-image');
        if (!empty($default['id'])) {
            return $default['id'];
        }
        return false;
    }

    /**
     * @param $term The series
     * @param bool $derive_from_posts When true, if the series image is empty, the posts in the series will be used to
     * determine the id.
     * @return bool|int The thumbnail id of the series thumbnail or false if not set
     */
    public function get_series_thumbnail_id($term, $derive_from_posts = true)
    {
        if (empty($term)) return false;

        $post_thumbnail_id = false;

        $image = get_tax_meta($term->term_id, MM_META_PREFIX . 'series_image');
        if (!empty($image['id'])) {
            $post_thumbnail_id = $image['id'];
        }

        if (!$post_thumbnail_id && $derive_from_posts) {
            $posts = $this->get_messages_in_series($term);
            if (!empty($posts) && !is_wp_error($posts)) {
                foreach ($posts as $post) {
                    $post_thumbnail_id = $this->get_message_thumbnail_id($post, false);
                    if ($post_thumbnail_id) break;
                }
            }
        }

        return $post_thumbnail_id;
    }

    /**
     * @return bool|int The thumbnail id of the default series image or false if not set
     */
    public function get_default_series_thumbnail_id()
    {
        $default = Message_Manager_Options::get_instance()->get('default-series-image');
        if (!empty($default['id'])) {
            return $default['id'];
        }
        return false;
    }

    /**
     * Gets the message video meta data
     * @param $post
     * @return array
     */
    public function get_message_video($post)
    {
        $post = get_post($post);
        $post_id = $post ? $post->ID : 0;
        $meta_name = $this->message_media_mb->get_the_id();
        $meta = get_post_meta($post_id, $meta_name, true);

        return array(
            'type' => empty($meta['video-type']) ? null : $meta['video-type'],
            'url' => empty($meta['video-url']) ? null : $meta['video-url'],
            'url-id' => empty($meta['video-url-id']) ? null : $meta['video-url-id'],
            'embedded' => empty($meta['video-embedded']) ? null : $meta['video-embedded'],
            'attachment' => empty($meta['video-attachment']) ? 'yes' : $meta['video-attachment']
        );
    }

    /**
     * Gets the message audio meta data
     * @param $post
     * @return array
     */
    public function get_message_audio($post)
    {
        $post = get_post($post);
        $post_id = $post ? $post->ID : 0;
        $meta_name = $this->message_media_mb->get_the_id();
        $meta = get_post_meta($post_id, $meta_name, true);

        return array(
            'url' => empty($meta['audio-url']) ? null : $meta['audio-url'],
            'url-id' => empty($meta['audio-url-id']) ? null : $meta['audio-url-id'],
            'attachment' => empty($meta['audio-attachment']) ? 'yes' : $meta['audio-attachment']
        );
    }

    /**
     * Determine the mime type of a file
     * @param $file
     * @return string
     */
    public static function get_the_mime($file)
    {
        $mimes = array('ai', 'asf', 'bib', 'csv', 'deb', 'doc', 'docx', 'djvu', 'dmg', 'dwg', 'dwf', 'flac', 'gif', 'gz', 'indd', 'iso', 'jpg', 'log', 'm4v', 'midi', 'mkv', 'mov', 'mp3', 'mp4', 'mpeg', 'mpg', 'odp', 'ods', 'odt', 'oga', 'ogg', 'ogv', 'pdf', 'png', 'ppt', 'pptx', 'psd', 'ra', 'ram', 'rm', 'rpm', 'rv', 'skp', 'spx', 'tar', 'tex', 'tgz', 'txt', 'vob', 'wmv', 'xls', 'xlsx', 'xml', 'xpi', 'zip');

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!empty($ext)) {
            if (in_array($ext, $mimes)) {
                return $ext;
            }
        }
        return '';
    }

    /**
     * Get a list of the downloads associated with a message
     * @param $post
     * @return array
     */
    public function get_message_downloads($post)
    {
        $post = get_post($post);
        $post_id = $post ? $post->ID : 0;

        $attachments = array();

        // video attachments
        $video = $this->get_message_video($post);
        if ($video['type'] == 'url' && $video['attachment'] == 'yes' && (!empty($video['url']) || !empty($video['url-id']))) {
            $attachments[] = array('type' => 'video', 'url' => $video['url'], 'id' => $video['url-id']);
        }

        // audio attachments
        $audio = $this->get_message_audio($post);
        if ($audio['attachment'] == 'yes' && (!empty($audio['url']) || !empty($audio['url-id']))) {
            $attachments[] = array('type' => 'audio', 'url' => $audio['url'], 'id' => $audio['url-id']);
        }

        // file attachments
        $meta_name = $this->message_attachments_mb->get_the_id();
        $files = get_post_meta($post_id, $meta_name, true);
        if (!empty($files['attachment'])) {
            $files = $files['attachment'];
        }
        if (!empty($files)) {
            foreach ($files as $file) {
                if (!empty($file['url']) || !empty($file['url-id'])) {
                    $title = empty($file['title']) ? null : $file['title'];
                    $description = empty($file['description']) ? null : $file['description'];
                    $attachments[] = array('type' => 'attachment', 'url' => $file['url'], 'id' => $file['url-id'], 'title' => $title, 'description' => $description);
                }
            }
        }

        $downloads = array();

        // post process attachments
        foreach ($attachments as $attachment) {
            $url = Message_Manager_Downloads::get_instance()->get_download_url($attachment['id']);
            if (empty($url)) {
                $url = Message_Manager_Downloads::get_instance()->get_download_url($attachment['url']);
            }
            $local = Message_Manager_Downloads::get_instance()->get_local_file($attachment['id']);
            if (empty($local)) {
                $local = Message_Manager_Downloads::get_instance()->get_local_file($attachment['url']);
            }
            if (!empty($url)) {
                $mime_type = $this->get_the_mime($local);
                $base_name = basename($local);
                if (empty($mime_type)) {
                    $mime_type = $this->get_the_mime($url);
                    $base_name = basename($url);
                }
                $attachment['base_name'] = $base_name;
                $attachment['download_url'] = $url;
                $attachment['mime_type'] = $mime_type;

                $downloads[] = $attachment;
            }
        }

        return $downloads;
    }
}

// initialize the plugin
$message_manager = Message_Manager::get_instance();