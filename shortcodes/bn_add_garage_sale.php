<?php

function shortcode_bn_add_garage_sale( $atts, $content = '' ) {
	ob_start();
	
	$args = array(
		'post_id'		=> 'new_post',
		'post_title'	=> false,
		'post_content'	=> false,
		'new_post'		=> array(
			'post_type'		=> 'garage_sale',
			'post_status'	=> 'pending'
		),
		'field_groups' => array(
			'group_59c2a873e6567' // Garage Sale Details
		),
		'submit_value'    => 'Add Garage Sale',
		'updated_message' => 'Listing created successfully',
		'uploader' => 'basic',
	);
	
	acf_form($args);
	
	return ob_get_clean();
}
add_shortcode( 'bn_add_garage_sale', 'shortcode_bn_add_garage_sale' );