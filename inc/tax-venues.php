<?php

$labels = array(
    'name' => _x('Venues', 'Taxonomy General Name', 'message-manager'),
    'singular_name' => _x('Venue', 'Taxonomy Singular Name', 'message-manager'),
    'menu_name' => __('Venues', 'message-manager'),
    'all_items' => __('All Venues', 'message-manager'),
    'parent_item' => __('Parent Venue', 'message-manager'),
    'parent_item_colon' => __('Parent Venu:', 'message-manager'),
    'new_item_name' => __('New Venu', 'message-manager'),
    'add_new_item' => __('Add New Venue', 'message-manager'),
    'edit_item' => __('Edit Venue', 'message-manager'),
    'update_item' => __('Update Venue', 'message-manager'),
    'separate_items_with_commas' => __('Separate venues with commas', 'message-manager'),
    'search_items' => __('Search venues', 'message-manager'),
    'add_or_remove_items' => __('Add or remove venues', 'message-manager'),
    'choose_from_most_used' => __('Choose from the most used venues', 'message-manager'),
);

$rewrite = array(
    'slug' => Message_Manager::get_instance()->get_base_slug('venue'),
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

register_taxonomy(MM_TAX_VENUES, MM_CPT_MESSAGE, $args);