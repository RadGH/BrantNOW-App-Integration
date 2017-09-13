<?php

if ( !defined('ABSPATH') ) die('This file should not be accessed directly.');

function bn_register_open_house_post_type() {
	$labels = array(
		'name'                  => 'Open Houses',
		'singular_name'         => 'Open House',
		'menu_name'             => 'Open Houses',
		'name_admin_bar'        => 'Open House',
		'archives'              => 'Open House Archives',
		'parent_item_colon'     => 'Parent Open House:',
		'all_items'             => 'All Open Houses',
		'add_new_item'          => 'Add New Open House',
		'add_new'               => 'Add Open House',
		'new_item'              => 'New Open House',
		'edit_item'             => 'Edit Open House',
		'update_item'           => 'Update Open House',
		'view_item'             => 'View Open House',
		'search_items'          => 'Search Open House',
		'not_found'             => 'Not found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Add into Open House',
		'uploaded_to_this_item' => 'Uploaded to this Open House',
		'items_list'            => 'Open House list',
		'items_list_navigation' => 'Open House list navigation',
		'filter_items_list'     => 'Filter Open House list',
	);
	
	$args = array(
		'label'                 => 'Open House',
		'description'           => '',
		'labels'                => $labels,
		'supports'              => array( 'title', 'author', 'revisions', ),
		'taxonomies'            => array(),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => '32.47002',
		'menu_icon'             => 'dashicons-location-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => 'open-house',
		'exclude_from_search'   => true,
		'publicly_queryable'    => false,
		'rewrite'               => false,
		'capability_type'       => 'open_house',
	);
	
	register_post_type( 'open_house', $args );
}
add_action( 'init', 'bn_register_open_house_post_type' );