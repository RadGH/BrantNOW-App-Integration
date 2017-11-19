<?php

if ( !defined('ABSPATH') ) die('This file should not be accessed directly.');

function bn_register_restaurants_post_type() {
	$labels = array(
		'name'                  => 'Restaurants',
		'singular_name'         => 'Restaurant',
		'menu_name'             => 'Restaurants',
		'name_admin_bar'        => 'Restaurant',
		'archives'              => 'Restaurant Archives',
		'parent_item_colon'     => 'Parent Restaurant:',
		'all_items'             => 'All Restaurants',
		'add_new_item'          => 'Add New Restaurant',
		'add_new'               => 'Add Restaurant',
		'new_item'              => 'New Restaurant',
		'edit_item'             => 'Edit Restaurant',
		'update_item'           => 'Update Restaurant',
		'view_item'             => 'View Restaurant',
		'search_items'          => 'Search Restaurant',
		'not_found'             => 'No Restaurants found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Add into Restaurant',
		'uploaded_to_this_item' => 'Uploaded to this Restaurant',
		'items_list'            => 'Restaurant list',
		'items_list_navigation' => 'Restaurant list navigation',
		'filter_items_list'     => 'Filter Restaurant list',
	);
	
	$args = array(
		'label'                 => 'Restaurant',
		'description'           => 'Restaurants can be submitted through a form and approved by an admin.',
		'labels'                => $labels,
		'supports'              => array( 'title', 'content', 'revisions' ),
		'taxonomies'            => array(),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_icon'             => 'dashicons-calendar-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'rewrite'               => true,
	);
	
	register_post_type( 'restaurant', $args );
}
add_action( 'init', 'bn_register_restaurants_post_type' );


/**
 * When saving an item, assign the first photo from the gallery to be the featured image for the item.
 *
 * @param $post_id
 */
function bn_restaurant_save_featured_image( $post_id ) {
	if ( get_post_type( $post_id ) != 'restaurant' ) return;
	
	$featured_image_id = (int) get_field( 'featured_image', $post_id, false );
	
	// Make the featured image field in ACF update the post thumbnail.
	if ( $featured_image_id && get_post_type( $featured_image_id ) == 'attachment' ) {
		if ( set_post_thumbnail( $post_id, $featured_image_id ) ) {
			// Thumbnail updated successfully.
			return;
		}
	}
	
	// Did not update thumbnail, remove it.
	delete_post_meta( $post_id, '_thumbnail_id' );
}
add_action( 'save_post', 'bn_restaurant_save_featured_image', 30 );

/** Adds various fields to the content for an restaurant
 *
 * @param $content
 *
 * @return mixed
 */
function bn_add_restaurant_data_to_content( $content ) {
	if ( is_admin() ) return $content;
	if ( get_post_type() != 'restaurant' ) return $content;
	
	$post_id = get_the_ID();
	
	$fields = array(
		'full_address'     => bn_get_location_address( $post_id ), // Used for map
		'latlng'           => bn_get_location_latlng( $post_id ), // Used for map
		'description'      => get_field( 'description', $post_id ),
		'website'          => get_field( 'website', $post_id ),
		'tickets_url'      => get_field( 'tickets_url', $post_id ),
		'featured_image'   => get_field( 'featured_image', $post_id, false ),
		'photo_gallery'    => get_field( 'photo_gallery', $post_id ),
	);
	
	ob_start();
	?>
	
	<h1><?php the_title(); ?></h1>
	
	<?php
	// ---------------
	// Featured  Image
	if ( $fields['featured_image'] && get_post_type( $fields['featured_image'] ) == 'attachment' ) {
		$full_size = wp_get_attachment_image_src( $fields['featured_image'], 'full' );
		
		echo '<p>';
		echo '<a href="'. esc_attr($full_size[0]) . '" target="_blank" title="View full image">';
		echo wp_get_attachment_image( $fields['featured_image'], 'medium' );
		echo '</a>';
		echo '</p>';
		
		$fields['photo_gallery'] = array_filter( $fields['photo_gallery'] );
		
		if ( count($fields['photo_gallery']) > 1 ) {
			echo '<p><em>More pictures available at the bottom of the page.</em></p>';
		}
	}
	?>
	
	<?php
	// ---------------
	// Description
	if ( $fields['description'] ) {
		echo wpautop($fields['description']);
	}
	?>
	
	<?php
	// --------------
	// Website and Ticket URL
	if ( $fields['website'] ) echo '<p><strong>Website:</strong> <a href="'.esc_attr($fields['website']).'" target="_blank">Visit Website &raquo;</a></p>';
	if ( $fields['tickets_url'] ) echo '<p><strong>Tickets:</strong> <a href="'.esc_attr($fields['tickets_url']).'" target="_blank">Buy Tickets &raquo;</a></p>';
	?>
	
	<?php
	// ---------------
	// Google Map Location
	if ( $fields['full_address'] && $fields['latlng'] ) {
		echo bn_generate_map( $fields['full_address'], $fields['latlng'][0], $fields['latlng'][1] );
	}
	?>
	
	<?php
	// ---------------
	// Photo gallery
	if ( count($fields['photo_gallery']) > 1 ) {
		echo '<p>';
		
		foreach( $fields['photo_gallery'] as $i ) {
			$image_id = !empty($i['image']) ? $i['image'] : false;
			if ( !$image_id ) continue;
			if ( get_post_type($image_id) != 'attachment' ) continue;
			
			$full_size = wp_get_attachment_image_src( $image_id, 'full' );
			if ( !$full_size ) continue;
			
			echo '<a href="'. esc_attr($full_size[0]) . '" target="_blank" title="View full image">';
			echo wp_get_attachment_image( $image_id, 'medium' );
			echo '</a>';
		}
		
		echo '</p>';
	}
	?>
	
	<?php
	$content = ob_get_clean();
	
	return $content;
}
add_filter( 'the_content', 'bn_add_restaurant_data_to_content', 80 );
add_filter( 'the_content_feed', 'bn_add_restaurant_data_to_content', 80 );


function bn_allow_guest_uploads_on_restaurant_sale_pages() {
	if ( is_admin() || is_feed() ) return;
	
	global $post;
	if ( !$post || empty($post->post_content) ) return;
	
	if ( !strstr($post->post_content, '[bn_add_restaurant') ) return;
	
	wp_enqueue_media();
	
	add_filter( 'user_has_cap', 'bn_allow_guest_uploads', 50, 3 );
}
add_action( 'wp', 'bn_allow_guest_uploads_on_restaurant_sale_pages' );