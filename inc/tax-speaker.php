<?php

$labels = array(
    'name' => _x('Speakers', 'Taxonomy General Name', 'message-manager'),
    'singular_name' => _x('Speaker', 'Taxonomy Singular Name', 'message-manager'),
    'menu_name' => __('Speaker', 'message-manager'),
    'all_items' => __('All Speakers', 'message-manager'),
    'parent_item' => __('Parent Speakers', 'message-manager'),
    'parent_item_colon' => __('Parent Speaker:', 'message-manager'),
    'new_item_name' => __('New Speaker', 'message-manager'),
    'add_new_item' => __('Add New Speaker', 'message-manager'),
    'edit_item' => __('Edit Speaker', 'message-manager'),
    'update_item' => __('Update Speaker', 'message-manager'),
    'separate_items_with_commas' => __('Separate speakers with commas', 'message-manager'),
    'search_items' => __('Search speakers', 'message-manager'),
    'add_or_remove_items' => __('Add or remove speakers', 'message-manager'),
    'choose_from_most_used' => __('Choose from the most frequent speakers', 'message-manager'),
);

$rewrite = array(
    'slug' => Message_Manager::get_instance()->get_base_slug('speaker'),
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

register_taxonomy(MM_TAX_SPEAKER, MM_CPT_MESSAGE, $args);