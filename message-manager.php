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

require_once 'message-manager-widget.php';

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

	public static $var_download = 'mm_download';
	public static $var_download_url = 'mm_download_url';
	public static $var_download_message_id = 'mm_download_message_id';
	
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
		
		// set up images
		add_action('init', array($this, 'add_images'));

		// set up scripts and styles
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

		// set up error handling and validation
		Message_Manager::$errors = new WP_Error();
		add_action('shutdown', array($this, 'save_errors_to_transient'));
		add_action('admin_notices', array($this, 'display_admin_errors'));

		// set up templating
		add_filter('single_template', array($this, 'template_filter'), 1, 10);
		add_filter('archive_template', array($this, 'template_filter'), 1, 10);
		add_filter('post_limits', array($this, 'remove_query_limit'), 1, 10);
		
		// set up permalinks
		add_filter('request', array($this, 'request'));
		add_filter('query_vars', array($this, 'query_vars'));
		add_filter('rewrite_rules_array', array($this, 'rewrite_rules_array'));
		add_filter('post_link', array($this, 'post_type_link'), 10, 2);
		add_filter('post_type_link', array($this, 'post_type_link'), 10, 2);
		add_filter('term_link', array($this, 'term_link'), 10, 2);
		
		// set up the podcast
		remove_all_actions('do_feed_podcast');
		add_action('do_feed_podcast', array($this, 'do_feed_podcast'), 10, 1);
		add_action('pre_get_posts', array($this, 'sort_messages_by_sermon_date'));

		// set up downloads
		add_action('parse_request', array($this, 'download_action'));
		
		// register the widget
		add_action('widgets_init', array($this, 'register_widget'));
		
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
			'rewrite' => false,
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
			'rewrite' => false
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
			'rewrite' => false
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
			'rewrite' => false
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
			'rewrite' => false
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
			'rewrite' => false
		));
	}

	public static function find_theme_url($file) {
		if (file_exists(get_stylesheet_directory().'/message-manager/' . $file)) {
			return get_stylesheet_directory_uri() . '/message-manager/' . $file;
		} else {
			return Message_Manager::$url . 'theme/' . $file;
		}
	}

	public static function find_theme_path($file) {
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
		wp_enqueue_style('mm-mediaelement-css', Message_Manager::find_theme_url('mediaelement/mediaelementplayer.min.css'), Message_Manager::$version);
		wp_enqueue_style('mm-mediaelement-skins', Message_Manager::find_theme_url('mediaelement/mejs-skins.css'));
		wp_enqueue_style('message-manager-theme', Message_Manager::find_theme_url('styles.css'), Message_Manager::$version);
			
		wp_enqueue_script('mm-mediaelement-js', Message_Manager::$url.'includes/mediaelement/mediaelement-and-player.min.js', array('jquery'), '2.9.3');
		wp_enqueue_script('mm-mediaelement-ga-js', Message_Manager::$url.'includes/mediaelement/mep-feature-googleanalytics.js', array('mm-mediaelement-js'), '2.9.3');
	}

	function post_type_link($link, $post) {
		// only modify if permalinks are enabled
		if (get_option('permalink_structure') == '') return $link;
		
		// only modify for message post type
		if ($post->post_type != Message_Manager::$cpt_message) return $link;
		
		$message_base = Message_Manager_Options::get('message-base', 'messages');
		$base = home_url('/'.$message_base);
		
		if ($series = get_the_terms($post->ID, Message_Manager::$tax_series)) {
			$link = $base . '/' . array_pop($series)->slug . '/' . $post->post_name;
		} else {
			$link = $base . '/' . $post->post_name;
		}
		
		return $link;
	}
	
	function term_link($link, $term) {
		// only modify if permalinks are enabled
		if (get_option('permalink_structure') == '') return $link;
		
		$message_base = Message_Manager_Options::get('message-base', 'messages');
		$base = home_url('/'.$message_base);
		
		switch ($term->taxonomy) {
			case Message_Manager::$tax_series:
				return $base . '/' . $term->slug;
			case Message_Manager::$tax_speaker:
				return $base . '/speaker/' . $term->slug;
		}
		return $link;
	}
	
	function rewrite_rules_array($rules) {
		$mm_rules = array();
		
		$message_base = Message_Manager_Options::get('slug');
		
		// messages
		$mm_rules[$message_base.'/?$'] =  'index.php?post_type='.Message_Manager::$cpt_message;
		//$mm_rules[$message_base.'/page/?([0-9]{1,})/?$'] = 'index.php?post_type='.Message_Manager::$cpt_message.'&paged=$matches[1]';

		$mm_rules[$message_base.'/podcast.rss/?$'] = 'index.php?post_type='.Message_Manager::$cpt_message.'&feed=podcast';
		$mm_rules[$message_base.'/feed/podcast.rss/?$'] = 'index.php?post_type='.Message_Manager::$cpt_message.'&feed=podcast';
		$mm_rules[$message_base.'/(feed|rdf|rss|rss2|atom|podcast)/?$'] = 'index.php?post_type='.Message_Manager::$cpt_message.'&feed=$matches[1]';
		$mm_rules[$message_base.'/feed/(feed|rdf|rss|rss2|atom|podcast)/?$'] = 'index.php?post_type='.Message_Manager::$cpt_message.'&feed=$matches[1]';
		
		// downloads
		$mm_rules[$message_base.'/download/?$'] =  'index.php?'.Message_Manager::$var_download;
		
		// speakers
		$mm_rules[$message_base.'/speaker/([^/]+)/?$'] =  'index.php?'.Message_Manager::$tax_speaker.'=$matches[1]';
		$mm_rules[$message_base.'/speaker/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?'.Message_Manager::$tax_speaker.'=$matches[1]&paged=$matches[2]';
		
		// series
		$mm_rules[$message_base.'/([^/]+)/?$'] =  'index.php?'.Message_Manager::$tax_series.'=$matches[1]';
		//$mm_rules[$message_base.'/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?'.Message_Manager::$tax_series.'=$matches[1]&paged=$matches[2]';
		//$mm_rules[$message_base.'/([^/]+)/(feed|rdf|rss|rss2|atom|podcast)/?$'] = 'index.php?'.Message_Manager::$tax_series.'=$matches[1]&feed=$matches[2]';
		//$mm_rules[$message_base.'/([^/]+)/feed/(feed|rdf|rss|rss2|atom|podcast)/?$'] = 'index.php?'.Message_Manager::$tax_series.'=$matches[1]&feed=$matches[2]';
		
		// series/message
		$mm_rules[$message_base.'/([^/]+)/(.+?)/?$'] =  'index.php?'.Message_Manager::$cpt_message.'=$matches[2]';
				
		return $mm_rules + $rules;
	}
	
	// if the series does not exists treat the request like a message
	function request($request) {
		if (!empty($request[Message_Manager::$tax_series])) {
			$slug = $request[Message_Manager::$tax_series];
			
			$term = get_term_by('slug', $slug, Message_Manager::$tax_series);
			if ($term === false) {
				global $wpdb;
				$post = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type= %s", $slug, Message_Manager::$cpt_message));
				
				// if the message has a series, redirect the correct url
				$series = get_the_terms($post, Message_Manager::$tax_series);
				if (!empty($series)) {
					wp_redirect(get_permalink($post));
					die();
				} else {
					// treat the request like a message
					unset($request[Message_Manager::$tax_series]);
					$request['page'] = null;
					$request[Message_Manager::$cpt_message] = $slug;
					$request['post_type'] = Message_Manager::$cpt_message;
					$request['name'] = $slug;
				}
			}
		}
		
		return $request;
	}
	
	function query_vars($vars) {
		$vars[] = Message_Manager::$var_download;
		$vars[] = Message_Manager::$var_download_url;
		$vars[] = Message_Manager::$var_download_message_id;
		return $vars;
	}
	
	function download_action($request) {
		
		if (isset($request->query_vars)) {
			if (isset($request->query_vars[Message_Manager::$var_download])) {
								
				if (empty($_REQUEST[Message_Manager::$var_download_url])) {
					die("Bad Request");
				}
				
				if (empty($_REQUEST[Message_Manager::$var_download_message_id])) {
					$_REQUEST[Message_Manager::$var_download_message_id] = null;
				}
				
				$url = esc_url($_REQUEST[Message_Manager::$var_download_url]);
				$message_id = esc_attr($_REQUEST[Message_Manager::$var_download_url]);
				
				$attachment_id = Message_Manager::get_attachment_id_from_src($url);
				$path = get_attached_file($attachment_id, false);
				
				if (!empty($path) && file_exists($path)) {
					update_post_meta($attachment_id, 'downloads', get_post_meta($attachment_id, 'downloads', true) + 1);
					
					if(headers_sent()) die('Headers Sent');
					if(ini_get('zlib.output_compression')) ini_set('zlib.output_compression', 'Off');
					
					header('Content-Description: File Transfer');
				    header('Content-Type: application/octet-stream');
				    header('Content-Disposition: attachment; filename='.basename($path));
				    header('Content-Transfer-Encoding: binary');
				    header('Expires: 0');
				    header('Cache-Control: must-revalidate');
				    header('Pragma: public');
				    header('Content-Length: ' . filesize($path));
				    ob_clean();
				    flush();
				    readfile($path);
				    exit;
				} else {
					wp_redirect($url, 302);
					die();
				}
			}
		}

		return $request;
	}
	
	function do_feed_podcast($for_comments) {
		if (get_query_var( 'post_type' ) == Message_Manager::$cpt_message) {
			load_template(Message_Manager::find_theme_path('feed-podcast.php'));
		}
	}
	
	function remove_query_limit($limit) {
		if (is_admin()) return $limit;
		
		if (is_feed('podcast') || is_post_type_archive(Message_Manager::$cpt_message) || is_tax(Message_Manager::$tax_series)) {
			return 'LIMIT 0, 99999';
		}
		
		return $limit;
	}
	
	function sort_messages_by_sermon_date($query) {
		if($query->is_main_query() && !is_admin() && is_post_type_archive(Message_Manager::$cpt_message)) {			
			$meta_key = Message_Manager::$meta_prefix . 'details_date';
			$query->set('meta_key', $meta_key);
			$query->set('meta_value', date('yy-mm-dd'));
			$query->set('meta_compare', '>=');
			$query->set('orderby', 'meta_value');
			$query->set('order', 'DESC');
		}
		
		return $query;
	}
	
	function add_images() {
		add_image_size(Message_Manager::$cpt_message, '220', '124', true);
		add_image_size(Message_Manager::$tax_series, '220', '124', true);
	}
	
	function template_filter($template) {
		$temp = null;
		
		if (is_singular(Message_Manager::$cpt_message)) {
			$temp = Message_Manager::find_theme_path('message.php');
		} else if (is_tax(Message_Manager::$tax_series)) {
			$temp = Message_Manager::find_theme_path('series.php');
		} else if (is_tax(Message_Manager::$tax_speaker)) {
			$temp = Message_Manager::find_theme_path('speaker.php');
		} else if (is_post_type_archive(Message_Manager::$cpt_message)) {
			$temp = Message_Manager::find_theme_path('messages.php');	
		}
		
		if (file_exists($temp)) {
			return $temp;
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
	
	/** THEME FUNCTIONS */

	public static function get_the_image_rss($post_id = null, $size = 'full') {
		global $post;
		
		if ($post_id) {
			$post = get_post($post_id);
			$post_id = $post->ID;
		}
		
		$image = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), $size);
		if (!empty($image)) {
			if (is_array($image)) {
				$image = $image[0];
			}
			return $image;
		}
	
		$series = get_the_terms($post_id, Message_Manager::$tax_series);
		if (!empty($series)) {
			$image = Message_Manager::get_series_image(array_pop($series)->slug);
			if (!empty($image)) {
				return $image;
			}
		}
		
		return Message_Manager_Options::get('default-image');
	}
	
	public static function get_id3_info($url) {
		$transient_id = 'id3_'.sha1($url);
		
		// valid cache
		$info = get_transient($transient_id);
		if ($info) return $info;
		
		// no cache
		require_once 'includes/getid3/getid3.php';
		$getID3 = new getID3;
		
		$wp_upload_dir = wp_upload_dir();
		$baseurl = $wp_upload_dir['baseurl'];
		
		$filename = null;
		$pieces = explode($baseurl, $url);
		if (count($pieces) > 1) {
			$filename = $wp_upload_dir['basedir'] . $pieces[1];
		} 
		
		if (file_exists($filename)) {
			$info = $getID3->analyze($filename);
		} else {
			// remote file
			
			$dir = get_temp_dir();
			$filename = basename($filename);
			$filename = time();
			$filename = preg_replace('|\..*$|', '.tmp', $filename);
			$filename = $dir . wp_unique_filename($dir, $filename);
			touch($filename);			
			
			if (file_put_contents($filename, file_get_contents($url, false, null, 0))) {
				$info = $getID3->analyze($filename);
				unlink($filename);
			}
		}
		
		$new_info = array();
		if (!empty($info['playtime_seconds'])) {
			$new_info['playtime_seconds'] = $info['playtime_seconds'];
		}
		if (!empty($info['filesize'])) {
			$new_info['filesize'] = $info['filesize'];
		}
		
		set_transient($transient_id, $new_info);
		return $new_info;
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
	
	public static function get_series_image($series_slug) {
		$term = get_term_by('slug', $series_slug, Message_Manager::$tax_series);
		$term_id = $term->term_id;
		
		return get_tax_meta($term_id, Message_Manager::$meta_prefix.'series_image_id', false);
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
	
	public static $SERIES_OPT_DEFAULT = 0;
	public static $SERIES_OPT_ONLY = 1;
	public static $SERIES_OPT_NONE = 2;
		
	/** TEMPLATE API */
	public static function get_items_from_posts($series_opt = null, $posts = array()) {
		if ($series_opt == null) {
			$series_opt = Message_Manager::$SERIES_OPT_DEFAULT;
		}
		
		if (empty($posts)) {
			if (have_posts()) {
				while (have_posts()) {
					the_post();
				
					global $post;
					$posts[] = $post;
				}
			}
		}
		
		$items = array();
		$current = true;
		foreach ($posts as $post) {							
			$details_mb = Message_Manager::$message_details_mb;
			$details_mb->the_meta($post->ID);
			
			$terms = get_the_terms($post->ID, Message_Manager::$tax_series);
			if (!empty($terms) && $series_opt != Message_Manager::$SERIES_OPT_NONE) {
				foreach ($terms as $term) {
					if (empty($items[$term->slug])) {
						$new = get_object_vars($term);
						$new['type'] = Message_Manager::$tax_series;
						$new['messages'] = array();
						$new['link'] = get_term_link($term, Message_Manager::$tax_series);
						$new['current'] = $current;
						$items[$term->slug] = $new;
					}
					
					$message_date = $details_mb->get_the_value('date');
					
					if (empty($items[$term->slug]['start_date'])) {
						$items[$term->slug]['start_date'] = $message_date;
					} else if ($message_date < $items[$term->slug]['start_date']) {
						$items[$term->slug]['start_date'] = $message_date;
					}
					if (empty($items[$term->slug]['end_date'])) {
						$items[$term->slug]['end_date'] = $message_date;
					} else if ($message_date > $items[$term->slug]['end_date']) {
						$items[$term->slug]['end_date'] = $message_date;
					}
					
					if ($series_opt != Message_Manager::$SERIES_OPT_ONLY) {
						$items[$term->slug]['messages'][] = Message_Manager::process_message_item($post, $series_only, $details_mb, $current, $terms);
					}
				}
			} else {
				$items[$post->post_name] = Message_Manager::process_message_item($post, $series_only, $details_mb, $current);
			}
			$current = false;
		}
		
		return $items;
	}
	
	private static function process_message_item($post, $series_only, $details_mb, $current, $terms = array()) {
		$new = get_object_vars($post);
		$new['type'] = Message_Manager::$cpt_message;
		$new['current'] = $current;
		$new['date'] = $details_mb->get_the_value('date');
		$new['link'] = get_post_permalink($post->ID);
		$new['terms'] = $terms;
	
		if (!$series_only) {
			$new['verses'] = $details_mb->get_the_value('verses');
				
			$new['summary'] = $details_mb->get_the_value('summary');
			if (empty($new['summary'])) {
				$new['summary'] = get_the_excerpt($post->ID);
			}
				
			$new['media'] = get_post_meta($post->ID, Message_Manager::$message_media_mb->get_the_id(), TRUE);
			$attachments = get_post_meta($post->ID, Message_Manager::$message_attachments_mb->get_the_id(), TRUE);
			$new['attachments'] = $attachments['attachment'];
		}
	
		return $new;
	}
	
	public static function get_the_title($item) {
		switch($item['type']) {
			case Message_Manager::$cpt_message: return esc_html($item['post_title']);
			case Message_Manager::$tax_series: return esc_html($item['name']);
		}
	}
	
	public static function get_the_id($item) {
		if (!empty($item['ID'])) {
			return $item['ID'];
		}
		return null;
	}
	
	public static function the_id($item) {
		echo Message_Manager::get_the_id($item);
	}
	
	public static function the_title($item) {
		echo Message_Manager::get_the_title($item);
	}
	
	public static function get_the_link($item = null) {
		if (!empty($item)) {
			return $item['link'];
		}
		return home_url(Message_Manager_Options::get(slug));
	}
	
	public static function the_link($item = null) {
		echo Message_Manager::get_the_link($item);
	}
	
	public static function the_image($item, $params = array()) {
		$params = array_merge(array('size'=>null, 'align'=>null), $params);
		extract($params);
		
		$type = $item['type'];
		
		if (empty($size)) $size = $type;
		
		$alt = Message_Manager::get_the_title($item);
		$title = $alt;
		
		if ($type == Message_Manager::$cpt_message) {
			$attachment_id = get_post_thumbnail_id($item['ID']);
			if (!empty($attachment_id)) {
				echo get_image_tag($attachment_id, $alt, $title, $align, $size);
			} else {
				$image = Message_Manager_Options::get('default-message-image');
				if (!empty($image)) {
					echo get_image_tag($image['id'], $alt, $title, $align, $size);
				}
			}
		} else if ($type == Message_Manager::$tax_series) {
			$meta = get_tax_meta($item['term_id'], Message_Manager::$meta_prefix.'series_image');
			if (!empty($meta)) {
				echo get_image_tag($meta[id], $alt, $title, $align, $size);
			} else {
				$image = Message_Manager_Options::get('default-series-image');
				if (!empty($image)) {
					echo get_image_tag($image['id'], $alt, $title, $align, $size);
				}
			}
		}
		
	}
	
	public static function format_date($date, $format = 'j-m-y') {
		$parts = explode('-', $date);
		if ($parts >= 3) {
			$date = mktime(0, 0, 0, $parts[1], $parts[2], $parts[0]);
			return date($format, $date);
		}
		return $date;
	}
	
	public static function get_the_date($item, $format = null) {		
		switch($item['type']) {
			case Message_Manager::$cpt_message:
				if (empty($format))	$format = 'F j, Y';
				return Message_Manager::format_date($item['date'], $format);
			case Message_Manager::$tax_series: 
				if (empty($format))	$format = 'F Y';
				
				$start = $item['start_date'];
				$start_ym = Message_Manager::format_date($start, 'Ym');
				
				$end = $item['end_date'];
				$end_ym = Message_Manager::format_date($end, 'Ym');
				
				if ($end_ym == $start_ym) {
					return Message_Manager::format_date($start, $format);
				} else {
					return Message_Manager::format_date($start, $format).' - '.Message_Manager::format_date($end, $format);
				}
		}
	}
	
	public static function the_date($item) {
		echo Message_Manager::get_the_date($item);
	}
	
	public static function get_the_content($item) {
		switch($item['type']) {
			case Message_Manager::$cpt_message: 
				$content = $item['post_content'];
				if (empty($content)) {
					$content = $item['summary'];
				}
				return wpautop($content);
			case Message_Manager::$tax_series: 
				return wpautop($item['description']);
		}
	}
	
	public static function the_content($item) {
		echo Message_Manager::get_the_content($item);
	}
	
	public static function get_the_excerpt($item, $num_words = 55, $more = null) {
		$content = Message_Manager::get_the_content($item);
		
		if ($more == null) {
			$more = '&hellip; <a href="'.Message_Manager::get_the_link($item).'" title="'.Message_Manager::get_the_title($item).'">more</a>';
		}
		
		$excerpt = wpautop(wptexturize(wp_trim_words($content, $num_words, $more)));
		
		return $excerpt;
	}

	public static function the_excerpt($item, $num_words = 55, $more = null) {
		echo Message_Manager::get_the_excerpt($item, $num_words, $more);
	}
	
	public static function get_messages_in_series($series_item, $limit = 99999) {
		if (is_object($series_item)) {
			$series_item = get_object_vars($series_item);
		}
		
		if (!empty($series_item['terms'])) {
			$series_item = array_pop($series_item['terms']);
		}
		
		if (!empty($series_item['slug'])) {
			$meta_key = Message_Manager::$meta_prefix . 'details_date';
			
			$messages = get_posts(array(
				'numberposts' => $limit,
				'post_type' => Message_Manager::$cpt_message,
				'post_status' => 'publish',
				'meta_key' => $meta_key,
				'meta_value' => date('yy-mm-dd'),
				'meta_compare' => '>=',
				'orderby' => 'meta_value',
				'order' => 'DESC',
				
				Message_Manager::$tax_series => $series_item['slug']
			));
			
			return Message_Manager::get_items_from_posts(false, $messages);
		}
		return array();
	}
	
	public static function the_video($message) {
		if (Message_Manager::has_video($message)) {			
			$media = $message['media'];
			
			switch($media['video-type']) {
				case 'url':
					//todo:
					break;
				case 'vimeo':
					$video_id = Message_Manager::get_vimeo_video_id($media['video-url']);
					if (!empty($video_id)) {
						echo '<div class="flex-video widescreen vimeo">';
						echo "<iframe src=\"http://player.vimeo.com/video/$video_id?portrait=0&color=333\" width=\"640\" height=\"385\" frameborder=\"0\" webkitAllowFullScreen mozallowfullscreen allowFullScreen></iframe>";
						echo '</div>';
					}
					return null;
				case 'youtube':
					$video_id= Message_Manager::get_youtube_video_id($media['video-url']);
					if (!empty($video_id)) {
						echo '<div class="flex-video widescreen">';
						echo "<iframe class=\"youtube-player\" type=\"text/html\" width=\"640\" height=\"385\" src=\"http://www.youtube.com/embed/$video_id\" frameborder=\"0\"></iframe>";
						echo '</div>';
					}
					return null;
				case 'embedded':
					echo $media['video-embedded'];
			}
		}
	}
	
	public static function the_audio($message) {
		if (Message_Manager::has_audio($message)) {
			$audio_url = $message['media']['audio-url'];	
			$id = 'player_'.$message['ID'];
			echo "<audio id=\"#$id\" src=\"$audio_url\" type=\"audio/mp3\" controls=\"controls\"></audio>";
			echo "<script type=\"text/javascript\">
			//<![CDATA[
			jQuery(document).ready(function($) {
				$('#$id').mediaelementplayer({
					features: ['playpause','progress','current','duration','volume','googleanalytics'],
				});
			});
			//]]>
			</script>";
		}
	}
	
	public static function has_video($message) {
		if (!empty($message['media'])) {
			$media = $message['media'];
			if (!empty($media['video-url']) || !empty($media['video-embedded'])) {
				return true;
			}
		}
		return false;
	}
	
	public static function has_audio($message) {
		if (!empty($message['media'])) {
			$media = $message['media'];
			if (!empty($media['audio-url'])) {
				return true;
			}
		}
		return false;
	}
	
	public static function get_youtube_video_id($url) {
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
	
	public static function get_vimeo_video_id($url) {
		$id = preg_replace('~https?://vimeo\.com\/([0-9]{1,10})~ix', '$1', $url);
		if ($id == $url) return null;
		return $id;
	}
	
	public static function the_recent_series_list($series, $current_message = null, $args = array()) {
		$args = array_merge(array('limit'=>5, 'show_title'=>true, 'show_more_link'=> true, 'show_all_link'=>true), $args);
		extract($args);
		
		$init_limit = $limit;
		
		$series_items = Message_Manager::get_messages_in_series($series);
		if (count($series_items) > 0) {
			$series = array_shift($series_items);
			$messages = $series['messages'];
			
			$count = count($messages);
			if ($limit > $count) {
				$limit = $count;
			}
			
			$message_index = -1;
			$start = -1;
			$end = -1;
			if (!empty($current_message)) {
				for($i=0; $i < $count; $i++) {
					if ($messages[$i]['ID'] == $current_message['ID']) {
						$message_index = $i;
						$start = $message_index;
						$end = $message_index;
						while($limit > 0) {
							if ($end < $count && $limit > 0) {
								$end++;
								$limit--;
							}
							if ($start > 0 && $limit > 0) {
								$start--;
								$limit--;
							}
						}
					}
				}
			}

			if ($show_title) { ?>
				<a href="<?php Message_Manager::the_link($series); ?>" title="<?php Message_Manager::the_title($series); ?>"><h3><?php Message_Manager::the_title($series); ?></h3></a>
			<?php }
			
			echo '<ul class="message-manager-series-list">';
			for($i=0; $i < $count; $i++) {
				if (!empty($current_message) && ($i < $start || $i >= $end)) {
					continue;
				}
				$message = $messages[$i];
				?>
				<?php if ($i == $message_index): ?>
					<li class="active">
				<?php else: ?>
					<li>
				<?php endif; ?>
				<a href="<?php Message_Manager::the_link($message); ?>" title="<?php Message_Manager::the_title($message); ?>"><h4><?php Message_Manager::the_title($message); ?></h4><span><?php Message_Manager::the_date($message); ?></span></a>
				<?php
			}
			echo '</ul>';
			
			if ($show_more_link && $count > $init_limit) { ?>
				<a class="message-manager-more" href="<?php Message_Manager::the_link($series); ?>" title="<?php Message_Manager::the_title($series); ?>">More in this series</a>
			<?php }
		}
		if ($show_all_link) { ?>
			<h5 class="message-manager-all">&larr;<a href="<?php Message_Manager::the_link(); ?>" title="Return To Messages">Return to Messages</a></h5>	
		<?php }
	}
	
	private static function get_the_mime_class($url) {
		$mimes = array('ai','asf','bib','csv','deb','doc','docx','djvu','dmg','dwg','dwf','flac','gif','gz','indd','iso','jpg','log','m4v','midi','mkv','mov','mp3','mp4','mpeg','mpg','odp','ods','odt','oga','ogg','ogv','pdf','png','ppt','pptx','psd','ra','ram','rm','rpm','rv','skp','spx','tar','tex','tgz','txt','vob','wmv','xls','xlsx','xml','xpi','zip');
		
		$pathinfo = pathinfo($url);
		if (!empty($pathinfo['extension'])) {
			if(in_array($pathinfo['extension'], $mimes)) {
				return ' class="mm-mime-icon mm-mime-' . $pathinfo['extension'] . '"';
			}
		}
		return '';
	}
	
	public static function the_downloads($message) {
		echo '<ul class="message-manager-downloads">';
		
		if (!empty($message['attachments'])) {
			foreach($message['attachments'] as $attachment) {
				
				extract($attachment);
				
				if (empty($url)) continue;
								
				if (empty($title)) $title = basename($url);

				$mime_class = Message_Manager::get_the_mime_class($url);
				
				$url = Message_Manager::get_download_url($url, $message);
				
				echo "<li$mime_class>";
				echo "<a href=\"$url\" title=\"$title\">$title</a>";
				
				if (!empty($description)) {
					echo "<span>$description</span>";
				}
				echo '</li>';
			}
		}
		
		if (!empty($message['media'])) {
			if ($message['media']['audio-attachment'] == 'yes' && !empty($message['media']['audio-url'])) {
				$mime_class = Message_Manager::get_the_mime_class($message['media']['audio-url']);
				echo "<li$mime_class>";
				$title = "MP3 Audio";
				$url = Message_Manager::get_download_url($message['media']['audio-url'], $message);
				echo "<a href=\"$url\" title=\"$title\">$title</a>";
				echo '</li>';
			}
			
			if ($message['media']['video-attachment'] == 'yes' && $message['media']['video-type'] == 'url' && !empty($message['media']['video-url'])) {
				$mime_class = Message_Manager::get_the_mime_class($message['media']['video-url']);
				echo "<li$mime_class>";
				$title = "HQ Video";
				$url = Message_Manager::get_download_url($message['media']['video-url'], $message);
				echo "<a href=\"$url\" title=\"$title\">$title</a>";
				echo '</li>';
			}
		}
		
		echo '</ul>';
	}
	
	public static function the_speakers($message) {
		echo get_the_term_list($message['ID'], Message_Manager::$tax_speaker, '', ' &amp; ', '');
	}
	
	public static function the_topics($message) {
		echo get_the_term_list($message['ID'], Message_Manager::$tax_topics, '', ', ', '' );
	}
	
	public static function the_verse($message) {
		if (!empty($message['verses'])) {
			echo esc_html($message['verses']);	
		}
	}
	
	function register_widget() {
		register_widget( 'Message_Manager_Widget' );
	}
	
	public static function get_latest_message() {
		$meta_key = Message_Manager::$meta_prefix . 'details_date';
		
		$messages = get_posts(array(
			'numberposts' => 1,
			'post_type' => Message_Manager::$cpt_message,
			'post_status' => 'publish',
			'meta_key' => $meta_key,
			'meta_value' => date('yy-mm-dd'),
			'meta_compare' => '>=',
			'orderby' => 'meta_value',
			'order' => 'DESC',
		));
		
		$items = Message_Manager::get_items_from_posts(false, $messages);
		if (count($items) > 0) {
			return array_shift($items);	
		}
		return null;
	}
	
	public static function the_podcast_url() {
		echo home_url(Message_Manager_Options::get(slug).'/podcast');	
	}
	
	public static function get_download_url($url, $message = null) {
		if (is_array($message)) {
			if (!empty($message['ID'])) {
				$message = $message['ID'];
			}
		}
		
		return home_url(Message_Manager_Options::get(slug).'/download/?'.Message_Manager::$var_download_url.'='.urlencode($url).'&'.Message_Manager::$var_download_message_id.'='.urlencode($message));
	}
	
	public static function the_speaker_list($args = array()) {
		if (empty($args)) {
			$args = array('order'=>'DESC', 'orderby'=>'count','hide_empty' => true);
		}
		$terms = get_terms(Message_Manager::$tax_speaker, $args);
		
		if (empty($terms)) return;
		
		echo '<ul class="message-manager-speaker-list">';
		foreach ($terms as $term) {
			echo '<li>';
			echo '<a href="'.get_term_link($term).'" title="' . sprintf(__('View all messages by %s', 'my_localization_domain'), $term->name) . '">' . $term->name . '</a>';
			echo '</li>';
		}
		echo '</ul>';
	}
	
	public static function get_file_size($url) {
		
		$transient_id = 'filesize_'.sha1($url);
		
		$filesize = get_transient($transient_id);
		if ($filesize) return $filesize;
		
		$filesize = 0;
		
		$id = Message_Manager::get_attachment_id_from_src($url);
		if (!empty($id)) {
			$url = get_attached_file($id);
		}
		
		if (file_exists($url)) {
			$filesize = filesize($url);
		}
			
		// try curl
		if ($filesize == 0) {
			$uh = curl_init();
			curl_setopt($uh, CURLOPT_URL, $url);
			curl_setopt($uh, CURLOPT_NOBODY, 1);
			curl_setopt($uh, CURLOPT_HEADER, 0);
			curl_exec($uh);
			$filesize = curl_getinfo($uh,CURLINFO_CONTENT_LENGTH_DOWNLOAD);
			curl_close($uh);
		}
		
		if (!is_numeric($filesize)) {
			$filesize = 0;
		} else {
			set_transient($transient_id, $filesize, 60*60*24*7);	
		}
		
		return $filesize;
	}
}

// initialize the plugin
new Message_Manager();