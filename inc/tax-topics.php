<?php

$labels = array(
    'name' => _x('Topics', 'Taxonomy General Name', 'message-manager'),
    'singular_name' => _x('Topic', 'Taxonomy Singular Name', 'message-manager'),
    'menu_name' => __('Topics', 'message-manager'),
    'all_items' => __('All Topics', 'message-manager'),
    'parent_item' => __('Parent Topic', 'message-manager'),
    'parent_item_colon' => __('Parent Topic:', 'message-manager'),
    'new_item_name' => __('New Topic', 'message-manager'),
    'add_new_item' => __('Add New Topic', 'message-manager'),
    'edit_item' => __('Edit Topic', 'message-manager'),
    'update_item' => __('Update Topic', 'message-manager'),
    'separate_items_with_commas' => __('Separate topics with commas', 'message-manager'),
    'search_items' => __('Search topics', 'message-manager'),
    'add_or_remove_items' => __('Add or remove topic', 'message-manager'),
    'choose_from_most_used' => __('Choose from the most used topics', 'message-manager'),
);

$rewrite = array(
    'slug' => Message_Manager::get_instance()->get_base_slug('topic'),
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

register_taxonomy(MM_TAX_TOPICS, MM_CPT_MESSAGE, $args);