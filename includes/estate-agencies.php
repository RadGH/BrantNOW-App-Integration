<?php

if ( !defined('ABSPATH') ) die('This file should not be accessed directly.');

function bn_register_agency_post_type() {
	$labels = array(
		'name'                  => 'Real Estate Agencies',
		'singular_name'         => 'Real Estate Agency',
		'menu_name'             => 'Real Estate Agencies',
		'name_admin_bar'        => 'Real Estate Agency',
		'archives'              => 'Real Estate Agency Archives',
		'parent_item_colon'     => 'Parent Real Estate Agency:',
		'all_items'             => 'Agencies',
		'add_new_item'          => 'Add New Agency',
		'add_new'               => 'Add Agency',
		'new_item'              => 'New Agency',
		'edit_item'             => 'Edit Agency',
		'update_item'           => 'Update Agency',
		'view_item'             => 'View Agency',
		'search_items'          => 'Search Real Estate Agencies',
		'not_found'             => 'No open houses found',
		'not_found_in_trash'    => 'Not found in Trash',
		'featured_image'        => 'Featured Image',
		'set_featured_image'    => 'Set featured image',
		'remove_featured_image' => 'Remove featured image',
		'use_featured_image'    => 'Use as featured image',
		'insert_into_item'      => 'Add into Agency',
		'uploaded_to_this_item' => 'Uploaded to this Agency',
		'items_list'            => 'Agency list',
		'items_list_navigation' => 'Agency list navigation',
		'filter_items_list'     => 'Filter Agency list',
	);
	
	$args = array(
		'label'                 => 'Real Estate Agency',
		'description'           => 'Users can be assigned to agencies, allowing Brokers to manage the agency.',
		'labels'                => $labels,
		'supports'              => array( 'title', 'author', 'revisions' ),
		'taxonomies'            => array(),
		'hierarchical'          => false,
		'public'                => false,
		'show_ui'               => true,
		'show_in_menu'          => 'edit.php?post_type=open_house',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => false,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   => true,
		'publicly_queryable'    => true,
		'rewrite'               => false,
		'map_meta_cap'          => true,
		'capability_type'       => array( 'agency', 'agencies' ), // edit_agency, edit_published_agencies, etc.
	    
	    'register_meta_box_cb' => 'bn_agency_register_meta_boxes',
	);
	
	register_post_type( 'agency', $args );
}
add_action( 'init', 'bn_register_agency_post_type' );

/**
 * Register meta box(es).
 * Called through register_post_type: "register_meta_box_cb"
 */
function bn_agency_register_meta_boxes() {
	add_meta_box( 'agency_members', 'Members', 'bn_agency_members_metabox_callback', 'agency', 'normal', 'high' );
}

/**
 * Meta box display callback.
 *
 * @param WP_Post $post Current post object.
 */
function bn_agency_members_metabox_callback( $post ) {
	// Display code/markup goes here. Don't forget to include nonces!
	$args = array(
		'fields' => 'ID',
		'number' => 1000,
	);
	
	$users = bn_get_users_of_estate_agency( $post->ID, $args );
	
	if ( !$users || empty($users->results) ) {
		?>
		<p><em>No members belong to this agency. To add members to an agency, edit their user profiles on the <a href="users.php">Users screen</a>.</em></p>
		<p>To add members to an agency, edit their user profiles on the <a href="users.php">Users screen</a>.</p>
		<?php
	}else{
		?>
		<p><?php printf(_n('%d User', '%d Users', count($users->results)), count($users->results)); ?> are assigned to this agency.</p>
		
		<hr class="sep">
		
		<table class="bn-user-table">
			<thead>
			<tr>
				<th>Display Name</th>
				<th>User Role</th>
				<th>Email Address</th>
				<th>Properties</th>
				<th>Actions</th>
			</tr>
			</thead>
			
			<tbody>
			<?php
			foreach( $users->results as $user_id ) {
				$user = get_user_by('id', $user_id);
				$display_name = $user->get('display_name');
				$email = $user->get('user_email');
				
				$role_key = reset($user->roles);
				$role = isset(wp_roles()->roles[$role_key]) ? wp_roles()->roles[$role_key]['name'] : ucwords($role_key);
				
				// Remove redundant "Real Estate" from  role name
				$role = str_replace( 'Real Estate ', '', $role );
				
				$args = array(
					'post_type' => 'open_house',
				    'post_status' => 'any',
				    'author' => $user_id,
				    'posts_per_page' => 1, // We just need to get the number from found_posts, don't return a bunch of stuff.
				);
				$properties = new WP_Query($args);
				$property_count = $properties->found_posts;
				?>
				<tr>
					<td><?php echo esc_html($display_name); ?></td>
					<td><?php echo $role_key == 'bn_broker' ? '<strong>'.esc_html($role).'</strong>' : esc_html($role); ?></td>
					<td><a href="mailto:<?php echo esc_attr($email); ?>" target="_blank"><?php echo esc_html($email); ?></a></td>
					<td><a href="<?php echo esc_attr( admin_url('edit.php?post_type=open_house&author=' . $user_id ) ); ?>" target="_blank"><?php printf(_n('%d Property', '%d Properties', $property_count), $property_count); ?></a></td>
					<td><a href="<?php echo esc_attr( admin_url('user-edit.php?user_id=' . $user_id) ); ?>" target="_blank">Edit User</a></td>
				</tr>
				<?php
			}
			?>
			</tbody>
		</table>
		
		<hr class="sep">
		
		<p>To add members to an agency, edit their user profiles on the <a href="users.php">Users screen</a>.</p>
		<?php
	}
}

function bn_get_user_agency_id( $user_id ) {
	$agency_id = get_user_meta( $user_id, 'real_estate_agency', true );
	
	if ( get_post_type( $agency_id ) != 'agency' ) return false;
	
	return $agency_id;
}

/**
 * Checks if a user is a broker for an agency. They must be a broker, and they must be assigned to an agency.
 *
 * @param $user_id
 *
 * @return bool|mixed
 */
function bn_is_user_broker( $user_id ) {
	$agency_id = bn_get_user_agency_id( $user_id );
	
	// If the user is not part of an agency, return false
	if ( !$agency_id ) return false;
	
	// Check if the user is a broker by testing if they can edit other people's open houses.
	return user_can( $user_id, 'edit_others_open_houses' );
}

/**
 * Users who can edit other houses, but not edit other agencies, cannot see houses from other agencies either.
 * This applies to brokers, although we check permissions rather than role.
 *
 * @param $query
 */
function bn_agency_brokers_only_see_agency_properties( $query ) {
	if ( !is_admin() ) return;
	
	if ( $query->get('post_type') == 'open_house' ) {
		
		$agency_id = bn_get_user_agency_id( get_current_user_id() );
		
		if ( bn_is_user_broker( get_current_user_id() ) ) {
			
			if ( !$agency_id ) {
				// This broker is not assigned to an agency. Can only access their own properties.
				$query->set('author__in', get_current_user_id() );
			}else{
				// This broker is assigned to an agency and can access their own, and their agent's properties.
				$args = array(
					'fields' => 'ID',
					'number' => 1000,
				);
				
				$users = bn_get_users_of_estate_agency( $agency_id, $args );
				
				$query->set('author__in', $users->results);
			}
		}
		
	}
	
	$screen = get_current_screen();
}
add_action( 'pre_get_posts', 'bn_agency_brokers_only_see_agency_properties', 20 );

/**
 * Rewrite the views for "All (1) | Published (1) | Pending (0), in the Open Houses section.
 *
 * @param $views
 *
 * @return mixed
 */
function bn_agency_brokers_filter_view_properties( $views ) {
	global $wpdb;
	
	// This should only happen for brokers.
	if ( !bn_is_user_broker( get_current_user_id () ) ) return $views;
	
	$agency_id = bn_get_user_agency_id( get_current_user_id() );
	if ( !$agency_id ) return $views;
	
	$args = array(
		'fields' => 'ID',
		'number' => 1000,
	);
	
	$users = bn_get_users_of_estate_agency( $agency_id, $args );
	$author_ids = $users->results;
	
	$sql_select = "SELECT COUNT(*) FROM $wpdb->posts WHERE ";
	$sql_condition = "post_author IN (". implode(',', $author_ids) .") AND post_type = 'open_house'";
	
	if ( isset($views['all']) ) {
		$all = $wpdb->get_var($sql_select . " (post_status = 'publish' OR post_status = 'draft' OR post_status = 'pending') AND (". $sql_condition .") ");
		$views['all'] = preg_replace( '/\(.+\)/U', '('.$all.')', $views['all'] );
	}
	
	if ( isset($views['publish']) ) {
		$publish = $wpdb->get_var( $sql_select . " post_status = 'publish' AND (". $sql_condition .")" );
		$views['publish'] = preg_replace( '/\(.+\)/U', '('.$publish.')', $views['publish'] );
	}
	
	if ( isset($views['draft']) ) {
		$draft = $wpdb->get_var( $sql_select .   " post_status = 'draft'   AND (". $sql_condition .")" );
		$views['draft'] = preg_replace( '/\(.+\)/U', '('.$draft.')', $views['draft'] );
	}
	
	if ( isset($views['pending']) ) {
		$pending = $wpdb->get_var( $sql_select . " post_status = 'pending' AND (". $sql_condition .")" );
		$views['pending'] = preg_replace( '/\(.+\)/U', '('.$pending.')', $views['pending'] );
	}
	
	if ( isset($views['trash']) ) {
		$trash = $wpdb->get_var( $sql_select . " post_status = 'trash' AND (". $sql_condition .")" );
		$views['trash'] = preg_replace( '/\(.+\)/U', '('.$trash.')', $views['trash'] );
	}
	
	return $views;
}
add_filter( 'views_edit-open_house', 'bn_agency_brokers_filter_view_properties' );

/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
function bn_agency_save_meta_box( $post_id ) {
	// Save logic goes here. Don't forget to include nonce checks!
}
add_action( 'save_post', 'bn_agency_save_meta_box' );

/**
 * Returns an array of users who belong to the given agency.
 * You can also supply additional arguments that work with WP_User_Query.
 *
 * @param $agency_id
 * @param null $other_args
 *
 * @return WP_User_Query
 */
function bn_get_users_of_estate_agency( $agency_id, $other_args = null ) {
	$args = array(
		'meta_query' => array(
			array(
				'key' => 'real_estate_agency',
			    'value' => (int) $agency_id,
			    'compare' => '==',
			    'type' => 'NUMERIC',
			)
		),
	);
	
	if ( $other_args != null ) {
		$args = array_merge( $other_args, $args );
	}
	
	$user_query = new WP_User_Query( $args );
	
	return $user_query;
}


function bn_agency_broker_author_dropdown_restriction( $args ) {
	if ( !is_admin() ) return $args;
	
	$screen = get_current_screen();
	if ( $screen->id != 'open_house' ) return $args;
	
	// Allow the author dropdown to show even if only one author is available.
	$args['hide_if_only_one_author'] = false;
	
	// Also allow the dropdown to show users including those who haven't written a post yet.
	$args['who'] = '';
	
	// Admins can always assign anyone
	if ( current_user_can( 'edit_users' ) ) return $args;
	
	$agency_id = bn_get_user_agency_id( get_current_user_id() );
	
	if ( $agency_id && bn_is_user_broker( get_current_user_id() ) ) {
		// For brokers, show a list of users in their agency.
		$users_of_agency = bn_get_users_of_estate_agency( $agency_id, array( 'fields' => 'ID', 'number' => 1000 ) );
		
		$agency_user_ids = $users_of_agency->results;
		
		$args['include'] = implode(',', $agency_user_ids );
		return $args;
	}else{
		// If not a broker for an agency, only show self
		$args['include'] = (string) get_current_user_id();
		return $args;
	}
}
add_filter( 'wp_dropdown_users_args', 'bn_agency_broker_author_dropdown_restriction', 10 );