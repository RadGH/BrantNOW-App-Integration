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
		'not_found'             => 'No open houses found',
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
		'description'           => 'Open Houses can be added by real estate agents and brokers.',
		'labels'                => $labels,
		'supports'              => array( 'author', 'revisions' ),
		'taxonomies'            => array(),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_icon'             => 'dashicons-location-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'rewrite'               => true,
		'map_meta_cap'          => true,
		'capability_type'       => 'open_house', // edit_open_house, edit_published_open_houses, etc.
	);
	
	register_post_type( 'open_house', $args );
}
add_action( 'init', 'bn_register_open_house_post_type' );

/**
 * When saving an open house item, assign the first photo from the gallery to be the featured image for the open house item.
 *
 * @param $post_id
 */
function bn_open_house_save_featured_image( $post_id ) {
	if ( get_post_type( $post_id ) != 'open_house' ) return;
	
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
add_action( 'save_post', 'bn_open_house_save_featured_image', 30 );

/**
 * When saving an open house item, set the name of the post to the date/price/address.
 *
 * @param $post_id
 */
function bn_open_house_save_title( $post_id ) {
	if ( get_post_type( $post_id ) != 'open_house' ) return;
	
	$address = bn_get_location_street_address($post_id);
	$price = get_field( 'price', $post_id );
	$oh_date = bn_open_house_get_date( $post_id, true, true );
	
	$title = "";
	
	// Start with the open house date: "Sep 23rd"
	if ( $oh_date ) {
		$title .= $oh_date;
	}
	
	// Separate price
	if ( $title && $price ) {
		$title .= " • ";
	}
	
	// Add the price: "$149,000"
	if ( $price ) {
		$title .= bn_format_price($price);
	}
	
	// Separate location
	if ( $title && $address ) {
		$title .= " – ";
	}
	
	// Add the address: "Sep 23rd • $149,000 – 1234 Example St"
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
	
	remove_action( 'save_post', 'bn_open_house_save_title', 30 );
	remove_action( 'acf/save_post', 'bn_open_house_save_title', 30 );
	wp_update_post( $args );
	add_action( 'save_post', 'bn_open_house_save_title', 30 );
	add_action( 'acf/save_post', 'bn_open_house_save_title', 30 );
	
}
add_action( 'save_post', 'bn_open_house_save_title', 30 );
add_action( 'acf/save_post', 'bn_open_house_save_title', 30 );

/**
 * When saving an open house item, save the date of the open house as a metadata. This will allow sorting by open house date, in addition to publish date.
 *
 * @param $post_id
 */
function bn_open_house_save_open_house_date( $post_id ) {
	if ( get_post_type( $post_id ) != 'open_house' ) return;
	
	$oh_date = bn_open_house_get_date( $post_id, false );
	$oh_timestamp = strtotime( $oh_date[0]['date'] . ' ' . $oh_date[0]['start_time'] );
	
	if ( !empty($oh_timestamp) && ($oh_timestamp > current_time('timestamp')) ) {
		// Date is set, and it's in the future. Save it.
		update_post_meta( $post_id, 'open_house_date_timestamp', $oh_timestamp );
	}else{
		update_post_meta( $post_id, 'open_house_date_timestamp', '' );
	}
	
}
add_action( 'save_post', 'bn_open_house_save_open_house_date', 30 );

/**
 * Sort open house in RSS FEEDS by open house date, and hide expired entries.
 *
 * @param $query
 */
function bn_open_house_sort_rss_by_open_house_date( $query ) {
	if ( !is_feed() ) return;
	if ( $query->get('post_type') != 'open_house' ) return;
	
	if ( !($query instanceof WP_Query) ) return;
	
	if ( $query->get('acf_field_key') ) return;
	
	$query->set('order', 'ASC');
	$query->set('orderby', 'meta_value_num');
	$query->set('meta_key', 'open_house_date_timestamp');

	$query->set('meta_query', array(
		array(
			'key' => 'open_house_date_timestamp',
			'value' => strtotime('-6 Hours'), // Do not show posts from several hours earlier
			'compare' => '>=',
			'type' => 'NUMERIC',
		)
	));
	
}
add_action( 'pre_get_posts', 'bn_open_house_sort_rss_by_open_house_date' );

/**
 * Sort open house in ADMIN AREA by open house date.
 *
 * @param $query
 */
function bn_open_house_sort_admin_by_open_house_date( $query ) {
	if ( !is_admin() ) return;
	if ( $query->get('post_type') != 'open_house' ) return;
	if ( isset($_REQUEST['orderby']) ) return; // Don't sort if a sort method is specifically provided.
	
	if ( !($query instanceof WP_Query) ) return;
	
	$query->set('order', 'ASC');
	$query->set('orderby', 'meta_value_num');
	$query->set('meta_key', 'open_house_date_timestamp');
}
add_action( 'pre_get_posts', 'bn_open_house_sort_admin_by_open_house_date' );

/**
 * Displays if an open house date has expired as a post status indicator
 *
 * @param $post_states
 * @param $post
 *
 * @return mixed
 */
function bn_open_house_expired_indcator( $post_states, $post ) {
	if ( $post->post_type != 'open_house') return $post_states;
	
	$date = get_post_meta( $post->ID, 'open_house_date_timestamp', true );
	
	if ( !$date || $date < current_time('timestamp' ) ) {
		$post_states[] = 'Expired';
	}
	
	return $post_states;
}
add_filter( 'display_post_states', 'bn_open_house_expired_indcator', 15, 2 );

/** Adds various fields to the content for an open house item
 *
 * @param $content
 *
 * @return mixed
 */
function bn_add_open_house_data_to_content( $content ) {
	global $post;
	if ( is_admin() ) return $content;
	if ( get_post_type() != 'open_house' ) return $content;
	
	$post_id = get_the_ID();
	
	$fields = array(
		'address'          => bn_get_location_street_address( $post_id ), // Used as title
		'full_address'     => bn_get_location_address( $post_id ), // Used for map
		'latlng'           => bn_get_location_latlng( $post_id ), // Used for map
		'open_house_date'  => bn_open_house_get_date( $post_id ),
		'description'      => get_field( 'description', $post_id ),
		'featured_image'   => get_field( 'featured_image', $post_id, false ),
		'photo_gallery'    => get_field( 'photo_gallery', $post_id ),
		'price'            => get_field( 'price', $post_id ),
	);
	
	// These fields are all used in the same area.
	$details = array(
	    'bedrooms'         => get_field( 'bedrooms', $post_id ),
	    'bathrooms'        => get_field( 'bathrooms', $post_id ),
	    'sqft'             => get_field( 'sqft', $post_id ),
	    'amenities_nearby' => get_field( 'amenities_nearby', $post_id ),
	    'features'         => get_field( 'features', $post_id ),
	    'parking'          => get_field( 'parking', $post_id ),
	    'website_link'     => get_field( 'website_link', $post_id ),
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
	
	// --------------
	// Price and Open House Date
		echo '<p>';
		if ( $fields['price'] ) echo '<strong>Price:</strong> ', esc_html(bn_format_price($fields['price']));
		if ( $fields['price'] && $fields['open_house_date'] ) echo "<br>\n";
		if ( $fields['open_house_date'] ) echo '<strong>Open House:</strong> ', esc_html($fields['open_house_date']);
		if ( $fields['price'] || $fields['open_house_date'] ) echo "<br>\n";
		echo '<strong>Posted on:</strong> ', date( 'M jS, Y \a\t g:ia', get_the_date('U') );
		echo '</p>';
	?>
	
	<hr class="sep" style="margin-left: auto; margin-right: auto;" />
	
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
	// Google Map Location
	if ( $fields['full_address'] && $fields['latlng'] ) {
		echo bn_generate_map( $fields['full_address'], $fields['latlng'][0], $fields['latlng'][1] );
	}
	?>
	
	<?php
	// ---------------
	// Property Description
	if ( $fields['description'] ) {
		echo wpautop($fields['description']);
	}
	?>
	
	<?php
	// --------------
	// Property Details
	$details = array_filter($details); // Remove empty details
	
	if ( $details ) {
		?>
		<hr class="sep" style="margin-left: auto; margin-right: auto;" />
		
		<h2>Property Details</h2>
		
		<?php
		$i = 0;
		echo '<p>';
		foreach( $details as $key => $value ) {
			$i++;
			
			$label = $key;
			switch( $key ) {
				case 'price':            $label = 'Price';            break;
				case 'bedrooms':         $label = 'Beds';             break;
				case 'bathrooms':        $label = 'Baths';            break;
				case 'sqft':             $label = 'Sqft';             break;
				case 'amenities_nearby': $label = 'Amenities Nearby'; break;
				case 'features':         $label = 'Features';         break;
				case 'parking':          $label = 'Parking';          break;
				case 'website_link':     $label = 'Website';          break;
			}
			
			// Display the value. For the website link, make it a clickable URL.
			if ( $key === 'website_link' ) {
				echo '<a href="'.esc_attr($value).'" target="_blank">Visit property website &raquo;</a>';
			}else{
				echo '<strong>', esc_html($label), ':</strong> ';
				echo esc_html($value);
			}
			
			if ( $i < count($details) ) echo "<br>\n";
		}
		echo '</p>';
	}
	?>
	
	<?php
	// --------------
	// Agent (and Agency) details
	$agent_id = $post->post_author;
	
	if ( $agent_id ) {
		$agent_user = get_user_by( 'id', $agent_id );
		
		$avatar = get_avatar_data($agent_id, array('size' => 100));
		$display_name = $agent_user->get('display_name');
		$title = get_user_meta( $agent_id, 'mepr_title', true );
		$phone = get_user_meta( $agent_id, 'mepr_phone', true );
		
		$agency_id = bn_get_user_agency_id( $agent_id );
		$display_agency = $agency_id && get_field('display_agency', $agency_id);
		?>
		<hr class="sep" style="margin-left: auto; margin-right: auto;" />
		
		
		<h2>Agent Information</h2>
		
		<p>
			<span style="max-width: 24%; height: auto; float: left; margin-right: 10px;"><img src="<?php echo esc_attr($avatar['url']); ?>" width="<?php echo esc_attr($avatar['width']); ?>" height="<?php echo esc_attr($avatar['height']); ?>" style="width: auto; height: auto;" alt="Gravatar for <?php echo esc_attr($display_name); ?>"></span>
			
			<span style="display: block; overflow: hidden;">
				<strong><?php echo esc_html($display_name); ?></strong>
				<?php if ( $title ) echo '<br>' . esc_html($title); ?>
				<?php if ( $phone ) echo '<br>Phone: ' . esc_html($phone); ?>
			</span>
			
			<span style="display: block; clear: both;"></span>
		</p>
		
		<?php
		if ( $display_agency ) {
			$logo = get_field( 'logo', $agency_id, false );
			$website = get_field( 'website', $agency_id, false );
			?>
			<hr class="sep" style="margin-left: auto; margin-right: auto;" />
			
			<p>
				<?php if ( $logo ) { ?>
					<span style="max-width: 24%; height: auto; float: left; margin-right: 10px;">
					<?php if ( $website ) echo '<a href="'. esc_attr($website) .'" target="_blank" rel="external nofollow">'; ?>
					<?php echo wp_get_attachment_image( $logo, 'medium', false, array( 'style' => 'width: auto; height: auto; max-width: 100%;' ) ); ?>
					<?php if ( $website ) echo '</a>'; ?>
					</span>
				<?php } ?>
				
				<span style="display: block; overflow: hidden;">
					<strong><?php echo esc_html(get_the_title($agency_id)); ?></strong>
					<?php if ( $website ) echo '<br>' . bn_format_external_link($website); ?>
				</span>
				
				<span style="display: block; clear: both;"></span>
			</p>
			<?php
		}
		?>
		
		<span style="display: block; clear: both;"></span>
		
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
		
	}
	
	$content = ob_get_clean();
	
	return $content;
}
add_filter( 'the_content', 'bn_add_open_house_data_to_content', 80 );
add_filter( 'the_content_feed', 'bn_add_open_house_data_to_content', 80 );

/**
 * Returns a formatted date for the open house.
 *
 * @param $post_id
 * @param bool $formatted
 * @param bool $date_only (only applied when $formatted = true)
 *
 * @return array|bool
 */
function bn_open_house_get_date( $post_id, $formatted = true, $date_only = false ) {
	$oh_date = get_field( 'open_house_date', $post_id );
	
	if ( empty($oh_date[0]['date']) || empty($oh_date[0]['start_time']) || empty($oh_date[0]['end_time']) ) return false;
	
	if ( $formatted ) {
		$date = strtotime( $oh_date[0]['date'] ); // September 15, 2017 6:30 pm, turned into a timestamp
		
		// 8:00 AM -> 8am
		// 8:30pm -> 8:30pm
		$start_time = str_replace(array(':00', ' am', ' pm'), array('', 'am', 'pm'), strtolower($oh_date[0]['start_time']) );
		$end_time = str_replace(array(':00', ' am', ' pm'), array('', 'am', 'pm'), strtolower($oh_date[0]['end_time']) );
		
		// Date: Oct 24th, 2017
		$formatted_date = date( "M jS, Y", $date );
		
		// Add time if necessary.
		if ( !$date_only ) {
			$formatted_date  .= " from ";
			
			// Add the time range: 10am-3pm
			$formatted_date .= $start_time . ' to ' . $end_time;
		}
		
		return $formatted_date;
	}
	
	return $oh_date;
}

/**
 * Returns true if the user can delete the provided open house.
 *
 * @param $user_id
 * @param $open_house_id
 *
 * @return bool
 */
function bn_can_user_delete_open_house( $user_id, $open_house_id ) {
	$post = get_post( $open_house_id );
	$user = get_user_by( 'id', $user_id );
	
	$can_delete = false;
	
	if ( $post->post_type != 'open_house' ) die('Invalid post type');
	
	// Check if post author is the same as the user. Also test permission to delete open houses.
	if ( $post->post_author == $user && (current_user_can('delete_open_houses') || current_user_can('delete_open_house')) ) {
		$can_delete = true;
	}
	
	// Check if user is a broker, and author is one of their agents.
	if ( !$can_delete && bn_is_user_broker( $user_id ) ) {
		$agency_id = bn_get_user_agency_id( $user_id );
		$agency_users = bn_get_users_of_estate_agency( $agency_id, array( 'fields' => 'ID', 'number' => 1000 ) );
		
		if ( $agency_users->results ) {
			$agency_user_ids = array_map( 'intval', $agency_users->results );
			
			if ( in_array( (int) $post->post_author, $agency_user_ids ) && (current_user_can('delete_others_open_houses') || current_user_can('delete_others_open_house')) ) {
				$can_delete = true;
			}
		}
	}
	
	if ( !$can_delete && current_user_can( 'manage_options' ) ) {
		$can_delete = true;
	}
	
	return $can_delete;
}