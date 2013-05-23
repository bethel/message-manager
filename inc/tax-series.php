<?php

$labels = array(
    'name' => _x('Series', 'Taxonomy General Name', 'message-manager'),
    'singular_name' => _x('Series', 'Taxonomy Singular Name', 'message-manager'),
    'menu_name' => __('Series', 'message-manager'),
    'all_items' => __('All Series', 'message-manager'),
    'parent_item' => __('Parent Series', 'message-manager'),
    'parent_item_colon' => __('Parent Series:', 'message-manager'),
    'new_item_name' => __('New Series', 'message-manager'),
    'add_new_item' => __('Add New Series', 'message-manager'),
    'edit_item' => __('Edit Series', 'message-manager'),
    'update_item' => __('Update Series', 'message-manager'),
    'separate_items_with_commas' => __('Separate series with commas', 'message-manager'),
    'search_items' => __('Search series', 'message-manager'),
    'add_or_remove_items' => __('Add or remove series', 'message-manager'),
    'choose_from_most_used' => __('Choose from the most used series', 'message-manager'),
);

$rewrite = array(
    'slug' => Message_Manager::get_instance()->get_base_slug('series'),
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

register_taxonomy(MM_TAX_SERIES, MM_CPT_MESSAGE, $args);

add_image_size(MM_TAX_SERIES, '220', '124', true);