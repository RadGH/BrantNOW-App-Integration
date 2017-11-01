<?php

function shortcode_bn_manage_open_houses( $atts, $content = '' ) {
	$open_house_action = isset($_REQUEST['oh_action']) ? stripslashes($_REQUEST['oh_action']) : false;
	$open_house_id = isset($_REQUEST['oh_id']) ? (int) $_REQUEST['oh_id'] : false;
	$open_house_msg = isset($_REQUEST['oh_msg']) ? stripslashes($_REQUEST['oh_msg']) : false;
	$user_id = get_current_user_id();
	
	if ( !$user_id ) {
		return 'You must be <a href="'.esc_attr(wp_login_url($_SERVER['REQUEST_URI'])).'">logged in</a> to continue.';
	}
	
	ob_start();
	
	if ( $open_house_msg ) {
		$message = false;
		if ( $open_house_msg == 'deleted_ok' ) $message =  '<strong>Notice:</strong> Property has been deleted.';
		
		if ( $message ) {
			?>
			<p><?php echo $message; ?></p>
			<?php
		}
	}
	
	switch( $open_house_action ) {
		case 'edit':
			bn_manage_open_house_edit_page( $user_id, $open_house_id );
			break;
			
		case 'delete':
			bn_manage_open_house_delete_page( $user_id, $open_house_id );
			break;
			
		case 'delete_confirm':
			bn_manage_open_house_delete_confirmation( $user_id, $open_house_id );
			break;
			
		case 'add':
			bn_manage_open_house_add_page( $user_id );
			break;
			
		default:
			bn_manage_open_house_listing_page( $user_id );
			break;
	}
	return ob_get_clean();
}
add_shortcode( 'bn_manage_open_houses', 'shortcode_bn_manage_open_houses' );

function bn_manage_open_house_listing_page( $user_id ) {
	$paged = isset($request['oh_page']) ? (int) $request['oh_page'] : 0;
	
	$args = array(
		'post_type' => 'open_house',
	    'post_status' => 'any',
	    'author__in' => array( get_current_user_id() ),
	    'posts_per_page' => 20,
	    'paged' => max( 0, $paged )
	);
	
	if ( bn_is_user_broker( $user_id ) ) {
		$agency_id = bn_get_user_agency_id( $user_id );
		$agency_user_ids = bn_get_users_of_estate_agency( $agency_id, array( 'fields' => 'ID', 'number' => 1000 ) );
		$args['author__in'] = (array) $agency_user_ids->results;
	}
	
	$properties = new WP_Query($args);
	
	$manage_property_url = get_permalink();
	
	?>
	<p><a href="<?php echo esc_attr(add_query_arg( array('oh_action' => 'add'), $manage_property_url )); ?>">Add New Property</a></p>
	<?php
	
	if ( $properties->have_posts() ) {
		?>
		<div class="bn-property-list">
			<?php
			while ($properties->have_posts()): $properties->the_post();
				$edit_url = add_query_arg( array( 'oh_action' => 'edit', 'oh_id' => get_the_ID() ), $manage_property_url );
				$delete_url = add_query_arg( array( 'oh_action' => 'delete', 'oh_id' => get_the_ID() ), $manage_property_url );
				?>
				<div class="bn-property-item">
					
					<p><strong><a href="<?php echo esc_attr(get_permalink()); ?>"><?php echo the_title(); ?></a></strong> &ndash; <a href="<?php echo esc_attr($edit_url); ?>">Edit</a> | <a href="<?php echo esc_attr($delete_url); ?>">Delete</a></p>
					
					<?php if ( bn_is_user_broker( $user_id ) ) { ?>
						<p>Posted by <?php the_author(); ?> on <?php echo get_the_date(); ?></p>
					<?php } ?>
					
				</div>
				<?php
			endwhile;
			wp_reset_postdata();
			?>
		</div>
		<?php
	}else{
		?>
		<p><em>No properties are available.</em></p>
		<?php
	}
}

function bn_manage_open_house_delete_page( $user_id, $open_house_id ) {
	$confirm_delete_url = add_query_arg( array( 'oh_action' => 'delete_confirm', 'oh_id' => $open_house_id ), get_permalink() );
	$cancel_url = remove_query_arg(array( 'oh_action', 'oh_id' ));
	?>
	<p>Are you sure you want to delete this property?</p>
	
	<p><strong><?php echo esc_html( get_the_title( $open_house_id ) ); ?></strong></p>
	
	<p><a href="<?php echo esc_attr($confirm_delete_url); ?>">Delete property</a> or <a href="<?php echo esc_attr($cancel_url); ?>">Cancel</a></p>
	<?php
}

function bn_manage_open_house_delete_confirmation( $user_id, $open_house_id ) {
	if ( !bn_can_user_delete_open_house( $user_id, $open_house_id ) ) {
		?>
		<p><em>You do not have permission to delete this property.</em></p>
		<?php
		return;
	}
	
	wp_delete_post( $open_house_id );
	?>
	<p>Removing property&hellip;</p>
	<script>window.location.href = <?php echo json_encode(add_query_arg( array( 'oh_msg' => 'deleted_ok' ), get_permalink() )); ?></script>
	<?php
}

function bn_manage_open_house_add_page( $user_id, $_override_args = array() ) {
	$args = array(
		'post_id'		=> 'new_post',
		'post_title'	=> false,
		'post_content'	=> false,
		'new_post'		=> array(
			'post_type'		=> 'open_house',
			'post_status'	=> 'publish'
		),
	    'field_groups' => array(
            'group_59bae2a7e074a' // Open House Details
		),
	    'submit_value'    => 'Add Listing',
	    'updated_message' => 'Listing created successfully',
		'uploader' => 'basic',
	    'return'          => add_query_arg( array( 'oh_action' => 'edit', 'oh_id' => '%post_id%', 'updated' => 1), get_permalink() ),
	);
	
	if ( !empty($_override_args) ) {
		$args = array_merge( $args, $_override_args );
	}
	
	?>
	<p><a href="<?php echo esc_attr(get_permalink()); ?>">&laquo; Back to all listings</a></p>
	<?php
	
	if ( $args['post_id'] == 'new_post' ) {
		echo '<h2>Create new Open House Property</h2>';
	}else{
		echo '<h2>Editing Open House Property</h2>';
	}
	
	if ( current_user_can('publish_open_houses') ) {
		acf_form($args);
	}else{
		?>
		<p>Sorry, you do not have the ability to create new open house listings. You may need to <a href="http://brantnow.ca/register/real-estate-agent/">become a member</a> or
			<a href="http://brantnow.ca/account/?action=subscriptions">renew your subscription</a> before continuing.</p>
		<?php
	}
}

function bn_manage_open_house_edit_page( $user_id, $open_house_id ) {
	$args = array(
		'post_id' => $open_house_id,
		'submit_value' => 'Update Listing',
		'updated_message' => 'Listing saved successfully',
		'return' => add_query_arg( array( 'oh_action' => 'edit', 'oh_id' => '%post_id%', 'updated' => 1), get_permalink() ),
	);
	
	bn_manage_open_house_add_page( $user_id, $args );
}