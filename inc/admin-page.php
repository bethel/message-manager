<?php

define('MM_OPTION_GROUP', 'message_manager');

class Message_Manager_Options
{

    /**
     * @var singleton instance of the message manager downloads
     */
    private static $instance;

    /**
     * @return Message_Manager_Options instance of the message manager
     */
    public static function get_instance()
    {
        if (empty(static::$instance)) {
            static::$instance = new Message_Manager_Options();
        }
        return static::$instance;
    }

    private function __construct()
    {
        if (!is_admin()) return;

        require_once MM_VENDOR_PATH . 'admin-page-class/admin-page-class.php';

        $options_panel = new MM_Admin_Page_Class(array(
            'menu' => 'edit.php?post_type=' . MM_CPT_MESSAGE,
            'page_title' => 'Settings',
            'capability' => 'manage_options',
            'option_group' => MM_OPTION_GROUP,
            'id' => 'message-manager-options',
            'fields' => array(),
            'use_with_theme' => MM_VENDOR_URL . 'admin-page-class',
        ));
        $options_panel->OpenTabs_container('');

        /**
         * define your admin page tabs listing
         */
        $options_panel->TabsListing(array(
            'links' => array(
                'general' => __('General', 'message-manager'),
                'video' => __('Video', 'message-manager'),
                'podcasting' => __('Podcasting', 'message-manager'),
            )
        ));

        // the general tab
        $options_panel->OpenTab('general');
        $options_panel->Title("General");
        $options_panel->addText('slug', array('name' => 'Base Slug', 'std' => $this->get('slug'), 'desc' => 'Enter the location of where messages should show up on your site. For example, the current value will display the messages at ' . get_site_url() . '/' . $this->get('slug')));
        $options_panel->addImage('default-message-image', array('name' => 'Default Message Image (16:9)', 'preview_height' => '124px', 'preview_width' => '220px', 'desc' => 'The default image to be displayed if a message does not have a featured image. For best results, the image should be 16:9.'));
        //$options_panel->addImage('default-message-image-square', array('name'=> 'Default Message Image (Square)','preview_height' => '220px', 'preview_width' => '220px', 'desc'=>'The default image to be displayed in the message archive if a message does not have a featured image. For best results, the image should be square(1:1).'));
        $options_panel->addImage('default-series-image', array('name' => 'Default Series Image (Square)', 'preview_height' => '124px', 'preview_width' => '124px', 'desc' => 'The default image to be displayed if a series does not have an image. For best results, the image should be square.'));
        $options_panel->CloseTab();

        // the embedded video tab
        $options_panel->OpenTab('video');
        $options_panel->Title("Video");
        $options_panel->addText('video-width', array('name' => 'Video Width', 'std' => $this->get('video-width'), 'desc' => 'The sermon video\'s width in pixels.'));
        $options_panel->addText('video-height', array('name' => 'Video Height', 'std' => $this->get('video-height'), 'desc' => 'The sermon video\'s height in pixels.'));
        $options_panel->addText('vimeo-url-options', array('vimeo-url-options' => 'Vimeo URL Options', 'std' => $this->get('vimeo-url-options'), 'desc' => 'URL options to append to the Vimeo embedded video url. e.g. ?portrait=0&color=333'));
        $options_panel->CloseTab();

        // the podcasting tab
        $options_panel->OpenTab('podcasting');
        $options_panel->Title("Podcasting");
        $options_panel->addText('podcast-title', array('name' => 'Channel Title', 'std' => $this->get('podcast-title'), 'desc' => 'The title of your podcast channel.'));
        $options_panel->addText('podcast-subtitle', array('name' => 'Channel Subtitle', 'std' => $this->get('podcast-subtitle'), 'desc' => 'The subtitle of your podcast channel.'));
        $options_panel->addText('podcast-link', array('name' => 'Channel Link', 'std' => $this->get('podcast-link'), 'desc' => 'The URL of your site or message area.'));
        $options_panel->addText('podcast-language', array('name' => 'Language', 'std' => $this->get('podcast-language'), 'desc' => 'The language of your padcast channel. See <a href="http://www.rssboard.org/rss-language-codes">here</a> for valid language codes.'));
        $options_panel->addText('podcast-copyright', array('name' => 'Copyright', 'std' => $this->get('podcast-copyright'), 'desc' => 'The copyright information of your podcast content.'));
        $options_panel->addText('podcast-author', array('name' => 'Author', 'std' => $this->get('podcast-author')));
        $options_panel->addText('podcast-keywords', array('name' => 'Keywords', 'std' => $this->get('podcast-keywords'), 'desc' => 'A comma seperated list of keywords for your channel.'));
        $options_panel->addTextarea('podcast-description', array('name' => 'Description', 'std' => $this->get('podcast-description')));
        $options_panel->addText('podcast-owner-name', array('name' => 'Owner Name', 'std' => $this->get('podcast-owner-name'), 'desc' => 'The name of the podcast owner. Will likly be the same as Author.'));
        $options_panel->addText('podcast-owner-email', array('name' => 'Owner Email', 'std' => $this->get('podcast-owner-email'), 'desc' => 'The copyright information of your podcast content.'));
        $options_panel->addImage('podcast-image', array('name' => 'Image ', 'preview_height' => '200px', 'preview_width' => '200px', 'desc' => 'The podcast channel\'s image. For best results, the image should be square(1:1).'));
        $options_panel->addTextarea('podcast-categories', array('name' => 'Categories', 'std' => $this->get('podcast-categories'), 'desc' => 'Specify each top category as a new line. You may add subcategories by using the => operator and then specifying a comma seperated list.'));
        $options_panel->CloseTab();
    }

    public function set($name, $value)
    {
        $options = get_option(MM_OPTION_GROUP, array());
        $options[$name] = $value;
        return update_option(MM_OPTION_GROUP, $options);
    }

    public function get($name, $default = false)
    {
        $options = get_option(MM_OPTION_GROUP, array());

        if (!isset($options[$name])) {

            $defaults = array(
                'slug' => 'messages',
                'video-width' => '640',
                'video-height' => '385',
                'vimeo-url-options' => '?title=1&amp;byline=1&amp;portrait=0',
                'podcast-title' => get_bloginfo_rss('name'),
                'podcast-subtitle' => get_bloginfo_rss('description'),
                'podcast-link' => home_url('/'),
                'podcast-language' => get_bloginfo_rss('language'),
                'podcast-copyright' => '&#x2117; &amp; &#xA9; ' . get_bloginfo_rss('name'),
                'podcast-author' => get_bloginfo_rss('name'),
                'podcast-description' => get_bloginfo_rss('description'),
                'podcast-owner-name' => get_bloginfo_rss('name'),
                'podcast-owner-email' => get_bloginfo_rss('admin_email'),
                'podcast-categories' => 'Religion & Spirituality => Christianity, Spirituality'
            );

            if (isset($defaults[$name])) {
                return $defaults[$name];
            }
        } else {
            return $options[$name];
        }

        return $default;
    }

    public function delete($name)
    {
        $options = get_option(MM_OPTION_GROUP, array());
        unset($options[$name]);

        if (!empty($options)) {
            return update_option(MM_OPTION_GROUP, $options);

        } else {
            return delete_option(MM_OPTION_GROUP);
        }
    }
}

$message_manager_options = Message_Manager_Options::get_instance();