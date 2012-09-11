<?php
class Message_Manager_Options {
	
	public static $option_group = 'message_manager';
	
	public static $defaults = array();
	
	function __construct() {
		require_once  Message_Manager::$path . 'includes/admin-page-class/admin-page-class.php';
		
		$options_panel = new MM_Admin_Page_Class(array(
			'menu'=> 'edit.php?post_type='.Message_Manager::$cpt_message,
			'page_title' => 'Settings',
			'capability' => 'manage_options',
			'option_group' => Message_Manager_Options::$option_group,
			'id' => 'message-manager-options',
			'fields' => array()
		));
		$options_panel->OpenTabs_container('');
		
		/**
		 * define your admin page tabs listing
		 */
		$options_panel->TabsListing(array(
			'links' => array(
				'general' =>  __('General'),
				'podcasting' =>  __('Podcasting'),
			)
		));
		
		// the general tab
		$options_panel->OpenTab('general');
		$options_panel->Title("General");
		$options_panel->addText('slug', array('name'=> 'Base Slug', 'std'=> Message_Manager_Options::get('slug'), 'desc'=>'Enter the location of where messages should show up on your site. For example, the current value will display the messages at ' . get_site_url() .'/'.Message_Manager_Options::get('slug')));
		$options_panel->addImage('default-message-image', array('name'=> 'Default Message Image (16:9)','preview_height' => '124px', 'preview_width' => '220px', 'desc'=>'The default image to be displayed if a message does not have a featured image. For best results, the image should be 16:9.'));
		$options_panel->addImage('default-message-image-square', array('name'=> 'Default Message Image (Square)','preview_height' => '220px', 'preview_width' => '220px', 'desc'=>'The default image to be displayed in the message archive if a message does not have a featured image. For best results, the image should be square(1:1).'));
		$options_panel->addImage('default-series-image', array('name'=> 'Default Series Image (Square)','preview_height' => '220px', 'preview_width' => '220px', 'desc'=>'The default image to be displayed if a series does not have an image. For best results, the image should be square(1:1).'));
		$options_panel->CloseTab();
		
		// the podcasting tab
		$options_panel->OpenTab('podcasting');
		$options_panel->Title("Podcasting");
		$options_panel->addText('podcast-title', array('name'=> 'Channel Title', 'std'=> Message_Manager_Options::get('podcast-title'), 'desc'=>'The title of your podcast channel.'));
		$options_panel->addText('podcast-subtitle', array('name'=> 'Channel Subtitle', 'std'=> Message_Manager_Options::get('podcast-subtitle'), 'desc'=>'The subtitle of your podcast channel.'));
		$options_panel->addText('podcast-link', array('name'=> 'Channel Link', 'std'=> Message_Manager_Options::get('podcast-link'), 'desc'=>'The URL of your site or message area.'));
		$options_panel->addText('podcast-language', array('name'=> 'Language', 'std'=> Message_Manager_Options::get('podcast-language'), 'desc'=>'The language of your padcast channel. See <a href="http://www.rssboard.org/rss-language-codes">here</a> for valid language codes.'));
		$options_panel->addText('podcast-copyright', array('name'=> 'Copyright', 'std'=> Message_Manager_Options::get('podcast-copyright'), 'desc'=>'The copyright information of your podcast content.'));
		$options_panel->addText('podcast-author', array('name'=> 'Author', 'std'=> Message_Manager_Options::get('podcast-author')));
		$options_panel->addText('podcast-keywords', array('name'=> 'Keywords', 'std'=> Message_Manager_Options::get('podcast-keywords'), 'desc'=>'A comma seperated list of keywords for your channel.'));
		$options_panel->addTextarea('podcast-description', array('name'=> 'Description', 'std'=> Message_Manager_Options::get('podcast-description')));
		$options_panel->addText('podcast-owner-name', array('name'=> 'Owner Name', 'std'=> Message_Manager_Options::get('podcast-owner-name'), 'desc'=>'The name of the podcast owner. Will likly be the same as Author.'));
		$options_panel->addText('podcast-owner-email', array('name'=> 'Owner Email', 'std'=> Message_Manager_Options::get('podcast-owner-email'), 'desc'=>'The copyright information of your podcast content.'));
		$options_panel->addImage('podcast-image', array('name'=> 'Image ','preview_height' => '200px', 'preview_width' => '200px', 'desc'=>'The podcast channel\'s image. For best results, the image should be square(1:1).'));
		$options_panel->addTextarea('podcast-categories', array('name'=> 'Categories', 'std'=> Message_Manager_Options::get('podcast-categories'), 'desc'=>'Specify each top category as a new line. You may add subcategories by using the => operator and then specifying a comma seperated list.'));
		$options_panel->CloseTab();
	}
	
	public static function set($name, $value) {
		$options = get_option(Message_Manager_Options::$option_group, array());
		$options[$name] = $value;
		return update_option(Message_Manager_Options::$option_group, $options);
	}
	
	public static function get($name, $default = false) {
		$options = get_option(Message_Manager_Options::$option_group, array());
		
		if(!isset($options[$name])) {
			
			$defaults = array(
				'slug' => 'messages',
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
	
	public static function delete($name) {
		$options = get_option(Message_Manager_Options::$option_group, array());
		unset($options[$name]);
		
		if (!empty($options)) {
			return update_option(Message_Manager_Options::$option_group, $options);
			
		} else {
			return delete_option(Message_Manager_Options::$option_group);
		}
	}
}