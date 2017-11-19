<?php

function shortcode_bn_add_restaurant( $atts, $content = '' ) {
	ob_start();
	
	$args = array(
		'post_id'		=> 'new_post',
		'post_title'	=> true,
		'post_content'	=> false,
		'new_post'		=> array(
			'post_type'		=> 'restaurant',
			'post_status'	=> 'pending'
		),
		'field_groups' => array(
			'group_5a118d6d8172c' // Restaurant Details
		),
		'submit_value'    => 'Add Event',
		'updated_message' => 'Restaurant created successfully',
		'uploader' => 'basic',
	);
	
	acf_form($args);
	
	return ob_get_clean();
}
add_shortcode( 'bn_add_restaurant', 'shortcode_bn_add_restaurant' );