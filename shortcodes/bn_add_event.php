<?php

function shortcode_bn_add_event( $atts, $content = '' ) {
	ob_start();
	
	$args = array(
		'post_id'		=> 'new_post',
		'post_title'	=> true,
		'post_content'	=> false,
		'new_post'		=> array(
			'post_type'		=> 'event',
			'post_status'	=> 'pending'
		),
		'field_groups' => array(
			'group_59c2a8fd60859' // Event Details
		),
		'submit_value'    => 'Add Event',
		'updated_message' => 'Event created successfully',
		'uploader' => 'basic',
	);
	
	acf_form($args);
	
	return ob_get_clean();
}
add_shortcode( 'bn_add_event', 'shortcode_bn_add_event' );