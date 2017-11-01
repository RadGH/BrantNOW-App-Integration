<?php

if ( !defined('ABSPATH') ) die('This file should not be accessed directly.');

function bn_register_garage_sale_post_type() {	
	$labels = array(
		'name'                  => 'Garage Sales',
		'singular_name'         => 'Garage Sale',
		'menu_name'             => 'Garage Sales',
		'name_admin_bar'        => 'Garage Sale',
		'archives'              => 'Garage Sale Archives',
		'parent_item_colon'     => 'Parent Garage Sale:',
		'all_items'             => 'All Garage Sales',
		'add_new_item'          => 'Add New Garage Sale',
		'add_new'               => 'Add Garage Sale',
		'new_item'              => 'New Garage Sale',
		'edit_item'             => 'Edit Garage Sale',
		'update_item'           => 'Update Garage Sale',
		'view_item'             => 'View Garage Sale',
		'search_items'          => 'Search Garage Sale',
		'not_found'             => 'No garage Sales found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Add into Garage Sale',
		'uploaded_to_this_item' => 'Uploaded to this Garage Sale',
		'items_list'            => 'Garage Sale list',
		'items_list_navigation' => 'Garage Sale list navigation',
		'filter_items_list'     => 'Filter Garage Sale list',
	);
	
	$args = array(
		'label'                 => 'Garage Sale',
		'description'           => 'Garage Sales can be submitted through a form and approved by an admin.',
		'labels'                => $labels,
		'supports'              => array( 'content', 'revisions' ),
		'taxonomies'            => array(),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_icon'             => 'dashicons-archive',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'rewrite'               => true,
	);
	
	register_post_type( 'garage_sale', $args );
	
	
	$labels = array(
		'name'                  => 'Events',
		'singular_name'         => 'Event',
		'menu_name'             => 'Events',
		'name_admin_bar'        => 'Event',
		'archives'              => 'Event Archives',
		'parent_item_colon'     => 'Parent Event:',
		'all_items'             => 'All Events',
		'add_new_item'          => 'Add New Event',
		'add_new'               => 'Add Event',
		'new_item'              => 'New Event',
		'edit_item'             => 'Edit Event',
		'update_item'           => 'Update Event',
		'view_item'             => 'View Event',
		'search_items'          => 'Search Event',
		'not_found'             => 'No events found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Add into Event',
		'uploaded_to_this_item' => 'Uploaded to this Event',
		'items_list'            => 'Event list',
		'items_list_navigation' => 'Event list navigation',
		'filter_items_list'     => 'Filter Event list',
	);
	
	$args = array(
		'label'                 => 'Event',
		'description'           => 'Events can be submitted through a form and approved by an admin.',
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
	
	register_post_type( 'event', $args );
}
add_action( 'init', 'bn_register_garage_sale_post_type' );


/**
 * When saving an item, assign the first photo from the gallery to be the featured image for the item.
 *
 * @param $post_id
 */
function bn_garage_sale_and_event_save_featured_image( $post_id ) {
	if ( get_post_type( $post_id ) != 'event' && get_post_type( $post_id ) != 'garage_sale' ) return;
	
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
add_action( 'save_post', 'bn_garage_sale_and_event_save_featured_image', 30 );

/**
 * When saving an item, set the name of the post to the date/address.
 *
 * @param $post_id
 */
function bn_garage_sale_save_title( $post_id ) {
	if ( get_post_type( $post_id ) != 'garage_sale' ) return;
	
	$address = bn_get_location_street_address($post_id);
	$oh_date = bn_garage_sale_and_event_get_date( $post_id, 'start_date', true, true );
	
	$title = "";
	
	// Start with the date: "Sep 23rd"
	if ( $oh_date ) {
		$title .= $oh_date;
	}
	
	// Separate location
	if ( $title && $address ) {
		$title .= " - ";
	}
	
	// Add the address: "Sep 23rd - 1234 Example St"
	if ( $address ) {
		$title .= $address;
	}
	
	// Remove trailing whitespace
	$title = trim($title);
	
	// Assign an empty title name if none is given
	if ( empty($title) ) $title = "(No title)";
	
	$args = array(
		'ID' => $post_id,
		'post_title' => $title,
		'post_name' => str_replace('•', '', $title),
	);
	
	remove_action( 'save_post', 'bn_garage_sale_save_title', 30 );
	remove_action( 'acf/save_post', 'bn_garage_sale_save_title', 30 );
	wp_update_post( $args );
	add_action( 'save_post', 'bn_garage_sale_save_title', 30 );
	add_action( 'acf/save_post', 'bn_garage_sale_save_title', 30 );
	
}
add_action( 'save_post', 'bn_garage_sale_save_title', 30 );
add_action( 'acf/save_post', 'bn_garage_sale_save_title', 30 );

/**
 * When saving an item, save the date's timestamps as metadata. This will allow sorting by these date, in addition to publish date.
 *
 * @param $post_id
 */
function bn_garage_sale_and_event_save_event_date( $post_id ) {
	if ( get_post_type( $post_id ) != 'event' && get_post_type( $post_id ) != 'garage_sale' ) return;
	
	$oh_start_timestamp = bn_garage_sale_and_event_get_date( $post_id, 'start_date', false );
	$oh_end_timestamp = bn_garage_sale_and_event_get_date( $post_id, 'end_date', false );
	
	if ( !empty($oh_start_timestamp) && ($oh_start_timestamp > current_time('timestamp')) ) {
		// Date is set, and it's in the future. Save it.
		update_post_meta( $post_id, 'start_date_timestamp', $oh_start_timestamp );
	}else{
		update_post_meta( $post_id, 'start_date_timestamp', '' );
	}
	
	if ( !empty($oh_end_timestamp) && ($oh_end_timestamp > current_time('timestamp')) ) {
		// Date is set, and it's in the future. Save it.
		update_post_meta( $post_id, 'end_date_timestamp', $oh_end_timestamp );
	}else{
		update_post_meta( $post_id, 'end_date_timestamp', '' );
	}
}
add_action( 'acf/save_post', 'bn_garage_sale_and_event_save_event_date', 30 );
add_action( 'save_post', 'bn_garage_sale_and_event_save_event_date', 30 );

/**
 * Returns a formatted date for the open house.
 *
 * @param $post_id
 * @param string $meta_key
 * @param bool $formatted
 * @param bool $date_only (only applied when $formatted = true)
 *
 * @return array|bool
 */
function bn_garage_sale_and_event_get_date( $post_id, $meta_key = 'start_date', $formatted = true, $date_only = false ) {
	$oh_date = get_field( $meta_key, $post_id );
	
	if ( empty($oh_date[0]['date']) || empty($oh_date[0]['time']) ) return false;
	
	$timestamp = strtotime( $oh_date[0]['date'] . ' ' . $oh_date[0]['time']); // September 15, 2017 6:30 pm, turned into a timestamp
	
	if ( !$formatted ) return $timestamp;
	if ( $date_only ) return date( "M jS, Y", $timestamp ); // Oct 24th, 2017
	return date( "M jS, Y g:ia", $timestamp ); // Oct 24th, 2017 10:59am
}

/**
 * Sort items in RSS FEEDS by event date, and hide expired entries.
 *
 * @param $query
 */
function bn_garage_sale_and_event_sort_rss_by_event_date( $query ) {
	if ( !is_feed() ) return;
	if ( $query->get('post_type') != 'event' && $query->get('post_type') != 'garage_sale' ) return;
	
	if ( !($query instanceof WP_Query) ) return;
	
	if ( $query->get('acf_field_key') ) return;
	
	$query->set('order', 'ASC');
	$query->set('orderby', 'meta_value_num');
	$query->set('meta_key', 'start_date_timestamp');
	
	$query->set('meta_query', array(
		array(
			'key' => 'end_date_timestamp',
			'value' => strtotime('-6 Hours'), // Do not show posts from several hours earlier
			'compare' => '>=',
			'type' => 'NUMERIC',
		)
	));
	
}
add_action( 'pre_get_posts', 'bn_garage_sale_and_event_sort_rss_by_event_date' );


/**
 * Sort open house in ADMIN AREA by open house date.
 *
 * @param $query
 */
function bn_garage_sale_and_event_sort_admin_by_event_date( $query ) {
	if ( !is_admin() ) return;
	if ( $query->get('post_type') != 'event' && $query->get('post_type') != 'garage_sale' ) return;
	if ( isset($_REQUEST['orderby']) ) return; // Don't sort if a sort method is specifically provided.
	
	if ( !($query instanceof WP_Query) ) return;
	
	$query->set('order', 'ASC');
	$query->set('orderby', 'meta_value_num');
	$query->set('meta_key', 'start_date_timestamp');
}
add_action( 'pre_get_posts', 'bn_garage_sale_and_event_sort_admin_by_event_date' );

/**
 * Displays if an open house date has expired as a post status indicator
 *
 * @param $post_states
 * @param $post
 *
 * @return mixed
 */
function bn_garage_sale_and_event_expired_indcator( $post_states, $post ) {
	if ( get_post_type( $post->ID ) != 'event' && get_post_type( $post->ID ) != 'garage_sale' ) return $post_states;
	
	$date = get_post_meta( $post->ID, 'start_date_timestamp', true );
	
	if ( !$date || $date < current_time('timestamp' ) ) {
		$post_states[] = 'Expired';
	}
	
	return $post_states;
}
add_filter( 'display_post_states', 'bn_garage_sale_and_event_expired_indcator', 15, 2 );

function bn_event_date_as_publish_date_rss( $time, $d, $gmt ) {
	if ( !did_action('rss2_head') ) return $time;
	if ( get_post_type() != 'event' ) return $time;
	
	$event_date = bn_garage_sale_and_event_get_date( get_the_ID() );
	if ( !$event_date ) return $time;
	
	return date( $d, strtotime($event_date) );
}
add_filter( 'get_post_time', 'bn_event_date_as_publish_date_rss', 10, 3 );


/** Adds various fields to the content for an event
 *
 * @param $content
 *
 * @return mixed
 */
function bn_add_event_data_to_content( $content ) {
	if ( is_admin() ) return $content;
	if ( get_post_type() != 'event' ) return $content;
	
	$post_id = get_the_ID();
	
	$fields = array(
		'full_address'     => bn_get_location_address( $post_id ), // Used for map
		'latlng'           => bn_get_location_latlng( $post_id ), // Used for map
		'start_date'       => get_field( 'start_date', $post_id ),
		'end_date'         => get_field( 'end_date', $post_id ),
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
	// --------------
	// Event Dates
	echo '<p>';
	if ( $fields['start_date'][0]['date'] && $fields['start_date'][0]['time'] ) echo '<strong>Event Starts:</strong> ', esc_html($fields['start_date'][0]['date'] . ' at ' . $fields['start_date'][0]['time']) . '<br>';
	if ( $fields['end_date'][0]['date'] && $fields['end_date'][0]['time'] ) echo '<strong>Event Ends:</strong> ', esc_html($fields['end_date'][0]['date'] . ' at ' . $fields['end_date'][0]['time']) . '<br>';
	echo '<strong>Posted on:</strong> ', date( 'M jS, Y \a\t g:ia', get_the_date('U') );
	echo '</p>';
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
add_filter( 'the_content', 'bn_add_event_data_to_content', 80 );
add_filter( 'the_content_feed', 'bn_add_event_data_to_content', 80 );


/** Adds various fields to the content for a garage sale
 *
 * @param $content
 *
 * @return mixed
 */
function bn_add_garage_sale_data_to_content( $content ) {
	if ( is_admin() ) return $content;
	if ( get_post_type() != 'garage_sale' ) return $content;
	
	$post_id = get_the_ID();
	
	$fields = array(
		'address'          => bn_get_location_street_address( $post_id ), // Used as title
		'full_address'     => bn_get_location_address( $post_id ), // Used for map
		'latlng'           => bn_get_location_latlng( $post_id ), // Used for map
		'start_date'       => get_field( 'start_date', $post_id ),
		'end_date'         => get_field( 'end_date', $post_id ),
		'description'      => get_field( 'description', $post_id ),
		'featured_image'   => get_field( 'featured_image', $post_id, false ),
		'photo_gallery'    => get_field( 'photo_gallery', $post_id ),
	);
	
	ob_start();
	?>
	
	<?php
	// --------------
	// Address
	if ( $fields['address'] ) {
		?>
		<h1><?php echo esc_html($fields['address']); ?></h1>
		<?php
	}
	?>
	
	
	
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
	// --------------
	// Garage sale Dates
	echo '<p>';
	if ( $fields['start_date'][0]['date'] && $fields['start_date'][0]['time'] ) echo '<strong>Starts:</strong> ', esc_html($fields['start_date'][0]['date'] . ' at ' . $fields['start_date'][0]['time']) . '<br>';
	if ( $fields['end_date'][0]['date'] && $fields['end_date'][0]['time'] ) echo '<strong>Ends:</strong> ', esc_html($fields['end_date'][0]['date'] . ' at ' . $fields['end_date'][0]['time']) . '<br>';
	echo '<strong>Posted on:</strong> ', date( 'M jS, Y \a\t g:ia', get_the_date('U') );
	echo '</p>';
	?>
	
	<?php
	// ---------------
	// Description
	if ( $fields['description'] ) {
		echo wpautop($fields['description']);
	}
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
add_filter( 'the_content', 'bn_add_garage_sale_data_to_content', 80 );
add_filter( 'the_content_feed', 'bn_add_garage_sale_data_to_content', 80 );


function bn_allow_guest_uploads_on_event_and_garage_sale_pages() {
	if ( is_admin() || is_feed() ) return;
	
	global $post;
	if ( !$post || empty($post->post_content) ) return;
	
	if ( !strstr($post->post_content, '[bn_add_garage_sale') && !strstr($post->post_content, '[bn_add_event') ) return;
	
	wp_enqueue_media();
	
	add_filter( 'user_has_cap', 'bn_allow_guest_uploads', 50, 3 );
}
add_action( 'wp', 'bn_allow_guest_uploads_on_event_and_garage_sale_pages' );

function bn_allow_guest_uploads( $capabilities, $cap, $args ) {
	if ( array_search( 'upload_files', $cap ) === false ) return $capabilities;
	
	if ( !isset($capabilities['upload_files']) ) {
		// we have to give a plethora of permissions temporarily.
		$capabilities['edit_pages'] = true;
		$capabilities['edit_others_pages'] = true;
		$capabilities['edit_posts'] = true;
		$capabilities['edit_others_posts'] = true;
		$capabilities['edit_published_pages'] = true;
		$capabilities['edit_published_posts'] = true;
		$capabilities['upload_files'] = true;
	}
	
	return $capabilities;
}