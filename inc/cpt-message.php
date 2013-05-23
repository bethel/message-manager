<?php

$labels = array(
    'name' => _x('Messages', 'Post Type General Name', 'message-manager'),
    'singular_name' => _x('Message', 'Post Type Singular Name', 'message-manager'),
    'menu_name' => __('Messages', 'message-manager'),
    'parent_item_colon' => __('Parent Message', 'message-manager'),
    'all_items' => __('All Messages', 'message-manager'),
    'view_item' => __('View Message', 'message-manager'),
    'add_new_item' => __('Add New Message', 'message-manager'),
    'add_new' => __('New Message', 'message-manager'),
    'edit_item' => __('Edit Message', 'message-manager'),
    'update_item' => __('Update Message', 'message-manager'),
    'search_items' => __('Search messages', 'message-manager'),
    'not_found' => __('No messages found', 'message-manager'),
    'not_found_in_trash' => __('No messages found in trash', 'message-manager'),
);

$rewrite = array(
    'slug' => Message_Manager::get_instance()->get_base_slug(),
    'with_front' => true,
    'pages' => true,
    'feeds' => true,
);

$args = array(
    'labels' => $labels,
    'supports' => array('title', 'editor', 'thumbnail', 'comments', 'revisions',),
    'hierarchical' => false,
    'public' => true,
    'show_ui' => true,
    'show_in_menu' => true,
    'show_in_nav_menus' => true,
    'show_in_admin_bar' => true,
    'menu_position' => 5,
    'menu_icon' => MM_IMG_URL . 'nav_icon.png',
    'can_export' => true,
    'has_archive' => true,
    'exclude_from_search' => false,
    'publicly_queryable' => true,
    'rewrite' => $rewrite,
    'capability_type' => 'page',
);

register_post_type(MM_CPT_MESSAGE, $args);

add_image_size(MM_CPT_MESSAGE, '220', '124', true);