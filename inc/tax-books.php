<?php

$labels = array(
    'name' => _x('Books of the Bible', 'Taxonomy General Name', 'message-manager'),
    'singular_name' => _x('Book of the Bible', 'Taxonomy Singular Name', 'message-manager'),
    'menu_name' => __('Books of the Bible', 'message-manager'),
    'all_items' => __('All Books of the Bible', 'message-manager'),
    'parent_item' => __('Parent Book of the Bible', 'message-manager'),
    'parent_item_colon' => __('Parent Book of the Bible:', 'message-manager'),
    'new_item_name' => __('New Book of the Bible', 'message-manager'),
    'add_new_item' => __('Add New Book of the Bible', 'message-manager'),
    'edit_item' => __('Edit Book of the Bible', 'message-manager'),
    'update_item' => __('Update Book of the Bible', 'message-manager'),
    'separate_items_with_commas' => __('Separate books of the Bible with commas', 'message-manager'),
    'search_items' => __('Search books of the Bible', 'message-manager'),
    'add_or_remove_items' => __('Add or remove books of the Bible', 'message-manager'),
    'choose_from_most_used' => __('Choose from the most used books of the Bible', 'message-manager'),
);

$rewrite = array(
    'slug' => Message_Manager::get_instance()->get_base_slug('books'),
    'with_front' => true,
    'hierarchical' => true,
);

$args = array(
    'labels' => $labels,
    'hierarchical' => false,
    'public' => true,
    'show_ui' => true,
    'show_admin_column' => true,
    'show_in_nav_menus' => true,
    'show_tagcloud' => false,
    'rewrite' => $rewrite,
);

register_taxonomy(MM_TAX_BOOKS, MM_CPT_MESSAGE, $args);