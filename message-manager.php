<?php
/*
 Plugin Name: Message Manager
Description: Manage audio and video sermon content as well as speakers, series, attachements, Bible verses and more.
Version: 1.0.0
Author: Chris Roemmich
Author URI: https://cr-wd.com
License: GPLv3
*/

// avoid direct requests
if ( !function_exists( 'add_action' ) ) {
	die("Bad Request");
}

require_once 'includes/options-page.php';
require_once 'includes/tax-meta-class/tax-meta-class.php';
require_once 'includes/wpalchemy/MetaBox.php';
require_once 'includes/wpalchemy/MediaAccess.php';

$wpalchemy_media_access = new MMWPAlchemy_MediaAccess();

class Message_Manager {

	public static $version = '1.0.0';
	public static $path;
	public static $url;

	public static $cpt_message = 'mm_cpt_message';
	public static $tax_speaker = 'mm_tax_speaker';
	public static $tax_series = 'mm_tax_series';
	public static $tax_topics = 'mm_tax_topics';
	public static $tax_venues = 'mm_tax_venues';
	public static $tax_books = 'mm_tax_books';

	public static $meta_prefix = '_mm_meta_';

	private static $errors;
	
	public static $message_details_mb;
	public static $message_media_mb;
	public static $message_attachments_mb;
	
	public function __construct() {
		define("Message_Manager", $version);

		// set up the url and directory paths
		$this->init_paths();

		// register taxonomies first to allow for sub-rewriting
		add_action('init', array($this, 'register_speaker_taxonomy'));
		add_action('init', array($this, 'register_series_taxonomy'));
		add_action('init', array($this, 'register_topics_taxonomy'));
		add_action('init', array($this, 'register_venues_taxonomy'));
		add_action('init', array($this, 'register_book_taxonomy'));
		
		// register post type
		add_action('init', array($this, 'register_message_post_type'));

		// set up scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

		// set up error handling and validation
		Message_Manager::$errors = new WP_Error();
		add_action('shutdown', array($this, 'save_errors_to_transient'));
		add_action('admin_notices', array($this, 'display_admin_errors'));

		// set up templating
		add_filter('single_template', array($this, 'message_template_filter'), 1, 10);

		// set up the options page
		new Message_Manager_Options();

		$this->create_message_meta_boxes();
		$this->create_venue_taxonomy_meta_boxes();
		$this->create_series_taxonomy_meta_boxes();
	}

	function init_paths() {
		if (defined("ABSPATH")) {
			if (strpos(plugin_dir_path( __FILE__ ), ABSPATH) === 0) {
				// looks ok.. lets go with it
				Message_Manager::$path = plugin_dir_path( __FILE__ );
				Message_Manager::$url = plugin_dir_url( __FILE__ );
			} else {
				// assume the plugin is in the default spot
				Message_Manager::$path = ABSPATH . 'wp-content/plugins/message-manager/';
				Message_Manager::$url = site_url('/') . 'wp-content/plugins/message-manager/';
			}
		} else {
			// go with the "safe" values
			Message_Manager::$path = plugin_dir_path( __FILE__ );
			Message_Manager::$url = plugin_dir_url( __FILE__ );
		}
	}

	function register_message_post_type() {
		$labels = array(
			'name' => __( 'Messages', 'message-manager'),
			'singular_name' => __( 'Message', 'message-manager'),
			'add_new' => __( 'Add New', 'message-manager'),
			'add_new_item' => __('Add New Message', 'message-manager'),
			'edit_item' => __('Edit Message', 'message-manager'),
			'new_item' => __('New Message', 'message-manager'),
			'view_item' => __('View Message', 'message-manager'),
			'search_items' => __('Search Messages', 'message-manager'),
			'not_found' =>  __('No messages found', 'message-manager'),
			'not_found_in_trash' => __('No messages found in Trash', 'message-manager'),
			'parent_item_colon' => '',
			'menu_name' => 'Messages',
		);

		$args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'query_var' => true,
			'menu_icon' => Message_Manager::$url . 'img/nav_icon.png',
			'capability_type' => 'post',
			'has_archive' => true,
			'rewrite' => array('slug'=>Message_Manager_Options::get('message-slug', 'messages'), 'with_front' => false),
			'hierarchical' => false,
			'supports' => array('title', 'editor', 'comments', 'thumbnail', 'revisions')
		);

		register_post_type(Message_Manager::$cpt_message, $args);
	}

	function register_speaker_taxonomy() {
		$labels = array(
			'name' => __( 'Speakers', 'message-manager'),
			'singular_name' => __( 'Speaker', 'message-manager' ),
			'menu_name' => __( 'Speakers', 'message-manager' ),
			'search_items' => __( 'Search speakers', 'message-manager' ),
			'popular_items' => __( 'Most frequent speakers', 'message-manager' ),
			'all_items' => __( 'All speakers', 'message-manager' ),
			'edit_item' => __( 'Edit speakers', 'message-manager' ),
			'update_item' => __( 'Update speakers', 'message-manager' ),
			'add_new_item' => __( 'Add new speaker', 'message-manager' ),
			'new_item_name' => __( 'New speaker name', 'message-manager' ),
			'separate_items_with_commas' => __( 'Separate multiple speakers with commas', 'message-manager' ),
			'add_or_remove_items' => __( 'Add or remove speakers', 'message-manager' ),
			'choose_from_most_used' => __( 'Choose from most frequent speakers', 'message-manager' ),
			'parent_item' => null,
			'parent_item_colon' => null,
		);

		register_taxonomy(Message_Manager::$tax_speaker, Message_Manager::$cpt_message, array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array ('slug'=>Message_Manager_Options::get('speaker-slug', 'messages/speakers'), 'with_front'=>false),
		));
	}

	function register_series_taxonomy() {
		$labels = array(
			'name' => __( 'Series', 'message-manager'),
			'graphic' => '',
			'singular_name' => __( 'Series', 'message-manager'),
			'menu_name' => __( 'Series', 'message-manager' ),
			'search_items' => __( 'Search series', 'message-manager' ),
			'popular_items' => __( 'Most frequent series', 'message-manager' ),
			'all_items' => __( 'All series', 'message-manager' ),
			'edit_item' => __( 'Edit series', 'message-manager' ),
			'update_item' => __( 'Update series', 'message-manager' ),
			'add_new_item' => __( 'Add new series', 'message-manager' ),
			'new_item_name' => __( 'New series name', 'message-manager' ),
			'separate_items_with_commas' => __( 'Separate series with commas', 'message-manager' ),
			'add_or_remove_items' => __( 'Add or remove series', 'message-manager' ),
			'choose_from_most_used' => __( 'Choose from most used series', 'message-manager' ),
			'parent_item' => null,
			'parent_item_colon' => null,
		);

		register_taxonomy(Message_Manager::$tax_series, Message_Manager::$cpt_message, array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array ('slug'=>Message_Manager_Options::get('series-slug', 'messages/series'), 'with_front'=>false),
		));
	}

	function register_topics_taxonomy() {
		$labels = array(
			'name' => __( 'Topics', 'message-manager'),
			'singular_name' => __( 'Topics', 'message-manager'),
			'menu_name' => __( 'Topics', 'message-manager' ),
			'search_items' => __( 'Search topics', 'message-manager' ),
			'popular_items' => __( 'Most popular topics', 'message-manager' ),
			'all_items' => __( 'All topics', 'message-manager' ),
			'edit_item' => __( 'Edit topic', 'message-manager' ),
			'update_item' => __( 'Update topic', 'message-manager' ),
			'add_new_item' => __( 'Add new topic', 'message-manager' ),
			'new_item_name' => __( 'New topic', 'message-manager' ),
			'separate_items_with_commas' => __( 'Separate topics with commas', 'message-manager' ),
			'add_or_remove_items' => __( 'Add or remove topics', 'message-manager' ),
			'choose_from_most_used' => __( 'Choose from most used topics', 'message-manager' ),
			'parent_item' => null,
			'parent_item_colon' => null,
		);

		register_taxonomy(Message_Manager::$tax_topics, Message_Manager::$cpt_message, array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array ('slug'=>Message_Manager_Options::get('topic-slug', 'messages/topics'), 'with_front'=>false),
		));
	}

	function register_venues_taxonomy() {
		$labels = array(
			'name' => __( 'Venues', 'message-manager'),
			'singular_name' => __( 'Venue', 'message-manager'),
			'menu_name' => __( 'Venues', 'message-manager' ),
			'search_items' => __( 'Search venues', 'message-manager' ),
			'popular_items' => __( 'Most popular venues', 'message-manager' ),
			'all_items' => __( 'All venues', 'message-manager' ),
			'edit_item' => __( 'Edit venue', 'message-manager' ),
			'update_item' => __( 'Update venue', 'message-manager' ),
			'add_new_item' => __( 'Add new venue', 'message-manager' ),
			'new_item_name' => __( 'New venue', 'message-manager' ),
			'separate_items_with_commas' => __( 'Separate venues with commas', 'message-manager' ),
			'add_or_remove_items' => __( 'Add or remove venues', 'message-manager' ),
			'choose_from_most_used' => __( 'Choose from most used venues', 'message-manager' ),
			'parent_item' => null,
			'parent_item_colon' => null,
		);

		register_taxonomy(Message_Manager::$tax_venues, Message_Manager::$cpt_message, array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array ('slug'=>Message_Manager_Options::get('venue-slug', 'messages/venues'), 'with_front'=>false),
		));
	}


	function register_book_taxonomy() {
		$labels = array(
			'name' => __( 'Books of the Bible', 'message-manager'),
			'singular_name' => __( 'Book of the Bible', 'message-manager'),
			'menu_name' => __( 'Books of the Bible', 'message-manager' ),
			'search_items' => __( 'Search books of the Bible', 'message-manager' ),
			'popular_items' => __( 'Most popular books of the Bible', 'message-manager' ),
			'all_items' => __( 'All books of the Bible', 'message-manager' ),
			'edit_item' => __( 'Edit book of the Bible', 'message-manager' ),
			'update_item' => __( 'Update book of the Bible', 'message-manager' ),
			'add_new_item' => __( 'Add new book of the Bible', 'message-manager' ),
			'new_item_name' => __( 'New book of the Bible', 'message-manager' ),
			'separate_items_with_commas' => __( 'Separate books of the Bible with commas', 'message-manager' ),
			'add_or_remove_items' => __( 'Add or remove books of the Bible', 'message-manager' ),
			'choose_from_most_used' => __( 'Choose from most used books of the Bible', 'message-manager' ),
			'parent_item' => null,
			'parent_item_colon' => null,
		);

		register_taxonomy(Message_Manager::$tax_books, Message_Manager::$cpt_message, array(
			'hierarchical' => false,
			'labels' => $labels,
			'show_ui' => true,
			'query_var' => true,
			'rewrite' => array ('slug'=>Message_Manager_Options::get('book-slug', 'messages/books'), 'with_front'=>false),
		));
	}

	function find_theme_url($file) {
		if (file_exists(get_stylesheet_directory().'/message-manager/' . $file)) {
			return get_stylesheet_directory_uri() . '/message-manager/' . $file;
		} else {
			return Message_Manager::$url . 'theme/' . $file;
		}
	}

	function find_theme_path($file) {
		if (file_exists(get_stylesheet_directory().'/message-manager/' . $file)) {
			return get_stylesheet_directory() . '/message-manager/' . $file;
		} else {
			return Message_Manager::$path . 'theme/' . $file;
		}
	}
	
	function enqueue_admin_scripts($hook) {
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			global $post;
			if ( Message_Manager::$cpt_message === $post->post_type ) {
				wp_enqueue_style('message-manager-admin-css', Message_Manager::$url . 'css/admin.css', Message_Manager::$version);
				wp_enqueue_style('message-manager-jquery-ui-css', Message_Manager::$url . 'includes/jquery-ui/jquery-ui-1.8.22.custom.css', '1.8.22');
					
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('message-manager-admin-js', Message_Manager::$url . 'js/admin.js', Message_Manager::$version);
			}
		}
	}
	
	function enqueue_frontend_scripts($hook) {
		if (get_post_type() == Message_Manager::$cpt_message || is_post_type_archive(Message_Manager::$cpt_message)) {
			$this->enqueue_front_styles();
		} else if (is_single()) {
			global $post;
			// check for shortcodes
			if (strpos($post->post_content, '[MM') !== false) {
				$this->enqueue_front_styles();
			}
		}
	}

	function enqueue_front_styles() {
		wp_enqueue_style('mm-mediaelement-css', $this->find_theme_url('mediaelement/mediaelementplayer.min.css'), Message_Manager::$version);
		wp_enqueue_style('mm-mediaelement-skins', $this->find_theme_url('mediaelement/mejs-skins.css'));
		wp_enqueue_style('message-manager-theme', $this->find_theme_url('styles.css'), Message_Manager::$version);
			
		wp_enqueue_script('mm-mediaelement-js', Message_Manager::$url.'includes/mediaelement/mediaelement-and-player.min.js', array('jquery'), Message_Manager::$version);
	}

	/*
	function generate_rewrite_rules($wp_rewrite) {
		$rules = array();
		
		$message_slug = $wp_rewrite->root . Message_Manager_Options::get('message-slug');
		
		// add basic slugs
		$rules[$message_slug.'/?$'] =  'index.php?post_type='.Message_Manager::$cpt_message;
		$rules[$message_slug.'/'.$wp_rewrite->pagination_base.'/([0-9]{1,})/?$'] = 'index.php?post_type='.Message_Manager::$cpt_message .'&paged='.$wp_rewrite->preg_index($i);
		
		// add feeds
		if ($wp_rewrite->feeds) {
			$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
			$rules[$message_slug.'/feed/'.$feeds.'/?$'] = 'index.php?post_type=' . Message_Manager::$cpt_message . '&feed='.$wp_rewrite->preg_index($i);
			$rules[$message_slug.'/'.$feeds.'/?$'] = 'index.php?post_type=' . Message_Manager::$cpt_message . '&feed='.$wp_rewrite->preg_index($i);
		}
		
		
		
		
		// add date slugs
		$dates = array(
			array('rule' => "([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})", 'vars' => array('year', 'monthnum', 'day')),
			array('rule' => "([0-9]{4})/([0-9]{1,2})", 'vars' => array('year', 'monthnum')),
			array('rule' => "([0-9]{4})",'vars' => array('year'))
		);
		
		foreach ($dates as $date) {
			$query = 'index.php?post_type='.Message_Manager::$cpt_message;
			$rule = $message_slug.'/'.$date['rule'];
			
			$i = 1;
			foreach ($date['vars'] as $var) {
				$query .= '&'.$var.'='.$wp_rewrite->preg_index($i);
				$i++;
			}
			
			$rules[$rule."/?$"] = $query;
			$rules[$rule."/feed/(feed|rdf|rss|rss2|atom)/?$"] = $query."&feed=".$wp_rewrite->preg_index($i);
			$rules[$rule."/(feed|rdf|rss|rss2|atom)/?$"] = $query."&feed=".$wp_rewrite->preg_index($i);
			$rules[$rule."/page/([0-9]{1,})/?$"] = $query."&paged=".$wp_rewrite->preg_index($i);	
		}		
		
		$wp_rewrite->rules = $rules + $wp_rewrite->rules;
		return $wp_rewrite;
	}
	*/

	function message_template_filter($template) {
		global $post;
		if ($post->post_type == Message_Manager::$cpt_message) {
			return $this->find_theme_path('sermon.php');
		}
		return $template;
	}
	
	function create_message_meta_boxes() {
		Message_Manager::$message_details_mb = new MMWPAlchemy_MetaBox(array(
			'id' => Message_Manager::$meta_prefix.'details',
			'title' => 'Details',
			'template' => Message_Manager::$path . 'includes/wpalchemy-templates/details.php',
			'types' => array(Message_Manager::$cpt_message),
			'mode' => WPALCHEMY_MODE_EXTRACT,
			'prefix' => Message_Manager::$meta_prefix . 'details_',
			'view' => WPALCHEMY_VIEW_ALWAYS_OPENED,
			'hide_screen_option' => true,
			'hide_editor' => false,
			'lock'=>WPALCHEMY_LOCK_AFTER_POST_TITLE,
			'save_filter' => array($this, 'validate_message_details'),
		));

		Message_Manager::$message_media_mb = new MMWPAlchemy_MetaBox(array(
			'id' => Message_Manager::$meta_prefix.'media',
			'title' => 'Media',
			'template' => Message_Manager::$path . 'includes/wpalchemy-templates/media.php',
			'types' => array(Message_Manager::$cpt_message),
			'prefix' => Message_Manager::$meta_prefix . 'media_',
			'view' => WPALCHEMY_VIEW_ALWAYS_OPENED,
			'hide_screen_option' => true,
			'hide_editor' => false,
			'save_filter' => array($this, 'validate_message_media'),
		));

		Message_Manager::$message_attachments_mb = new MMWPAlchemy_MetaBox(array(
			'id' => Message_Manager::$meta_prefix.'attachments',
			'title' => 'Attachments',
			'template' => Message_Manager::$path . 'includes/wpalchemy-templates/attachments.php',
			'types' => array(Message_Manager::$cpt_message),
			'prefix' => Message_Manager::$meta_prefix . 'attachments_',
			'view' => WPALCHEMY_VIEW_ALWAYS_OPENED,
			'hide_screen_option' => true,
			'hide_editor' => false,
			'save_filter' => array($this, 'validate_message_attachments'),
		));
	}

	function validate_message_details($meta, $post_id) {
		// TODO:
		return $meta;
	}

	function validate_message_media($meta, $post_id) {
		
		//TODO:
		return $meta;
	}

	function validate_message_attachments($meta, $post_id) {
		
		//TODO:
		return $meta;
	}

	function create_venue_taxonomy_meta_boxes() {
		require 'includes/tax-meta-class-templates/venue.php';
	}

	function create_series_taxonomy_meta_boxes() {
		require 'includes/tax-meta-class-templates/series.php';
	}

	function add_validation_message($message) {
		Message_Manager::add_error_message('mm-validate', $message);
	}

	public static function add_error_message($code, $message) {
		Message_Manager::$errors->add($code, $message);
	}

	function display_admin_errors() {
		$error_html = get_transient('mm-error-'.get_current_user_id());
		if (!empty($error_html)) {
			echo $error_html;
			return;
		}
		
		$error_html = $this->build_error_html();
		if (!empty($error_html)) {
			echo $error_html;
			Message_Manager::$errors = new WP_Error();
		}
	}

	function save_errors_to_transient() {
		if (is_admin()) {
			$codes = Message_Manager::$errors->get_error_codes();
			
			if (empty($codes)) {
				delete_transient('mm-error-'.get_current_user_id());
			} else {
				$html = $this->build_error_html();

				if (!empty($html)) {
					set_transient('mm-error-'.get_current_user_id(), $html, 60);
				} else {
					delete_transient('mm-error-'.get_current_user_id());
				}
			}
		}
	}
	
	
	function build_error_html() {
		$codes = Message_Manager::$errors->get_error_codes();
		
		$html = '';
		foreach ($codes as $code) {
			$messages = Message_Manager::$errors->get_error_messages($code);
			foreach ($messages as $message) {
				if (!empty($message)) {
					$html .= "<li>$message</li>";
				}
			}
		}
		
		if (!empty($html)) {
			return '<div id="mm-error" class="error"><ul>'.$html.'</ul></div>';
		} else {
			return;
		}
	}
	
	private static function set_message_detail($message_id, $meta_name, $value) {
		// update the value
		update_post_meta($message_id, $meta_name, $value);
		
		// fields value for wpalchemy extract mode
		$fields = get_post_meta($message_id, Message_Manager::$meta_prefix . 'details_fields', true);
		$fields[] = $meta_name;
		update_post_meta($message_id, Message_Manager::$meta_prefix . 'details_fields', array_unique($fields));
	}
	
	/** BASIC API */
	
	/**
	 * Sets the date of a message
	 * @param $message_id the message id
	 * @param $date the date in the format yyyy-mm-dd
	 */
	public static function set_message_date($message_id, $date) {
		$meta_name = Message_Manager::$meta_prefix . 'details_date';
		Message_Manager::set_message_detail($message_id, $meta_name, $date);
	}
	
	/**
	 * Sets the verses of a message
	 * @param $message_id the message id
	 * @param $verses Semi-colon seperated list of verses
	 */
	public static function set_message_verses($message_id, $verses) {
		$meta_name = Message_Manager::$meta_prefix . 'details_verses';
		Message_Manager::set_message_detail($message_id, $meta_name, $verses);
	}
	
	/**
	 * Sets the summary of a message
	 * @param $message_id
	 * @param $summary
	 */
	public static function set_message_summary($message_id, $summary) {
		$meta_name = Message_Manager::$meta_prefix . 'details_summary';
		Message_Manager::set_message_detail($message_id, $meta_name, $summary);
	}
	
	/**
	 * Sets the featured images of the message
	 * @param $message_id
	 * @param $attachment_id
	 */
	public static function set_message_image($message_id, $attachment_id) {
		update_post_meta($message_id, '_thumbnail_id', $attachment_id);
	}
	
	/**
	 * Sets the description of a series
	 * @param $series_slug
	 * @param $description
	 */
	public static function set_series_description($series_slug, $description) {
		$term = get_term_by('slug', $series_slug, Message_Manager::$tax_series);
		$term_id = $term->term_id;
		
		wp_update_term($term_id, Message_Manager::$tax_series, array('description'=>$description));
	}
	
	/**
	 * Sets the featured image for a series
	 * @param $series_slug
	 * @param $attachment_id
	 */
	public static function set_series_image($series_slug, $attachment_id) {
		$term = get_term_by('slug', $series_slug, Message_Manager::$tax_series);
		$term_id = $term->term_id;
		
		update_tax_meta($term_id, Message_Manager::$meta_prefix.'series_image_id', $attachment_id);
	}
	
	/**
	 * Sets the audio for a message
	 * @param $message_id
	 * @param $url
	 * @param boolean $show_in_attachments
	 */
	public static function set_message_audio($message_id, $url, $show_in_attachments = true) {
		$meta_name = Message_Manager::$meta_prefix.'media';
		
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
	public static function set_message_video($message_id, $type, $value, $show_in_attachments = true) {
		$meta_name = Message_Manager::$meta_prefix.'media';
		
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
	public static function add_message_attachment($message_id, $url, $title = null, $description = null) {
		$meta_name = Message_Manager::$meta_prefix.'attachments';
		$meta = get_post_meta($message_id, $meta_name, true);
		
		if (empty($meta['attachment'])) {
			$meta['attachment'] = array();
		}

		$meta['attachment'][] = array('url'=>$url, 'title'=>$title, 'description'=>$description);
		
		$meta_name = Message_Manager::$meta_prefix.'attachments';
		update_post_meta($message_id, $meta_name, $meta);
	}
	
	public static function get_attachment_id_from_src($attachment_src) {
		global $wpdb;
		$query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$attachment_src'";
		$id = $wpdb->get_var($query);
		return $id;
	}
}

// initialize the plugin
new Message_Manager();