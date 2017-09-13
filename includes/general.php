<?php

if ( !defined( 'ABSPATH' ) ) die( 'This file should not be accessed directly.' );

function bn_save_latlng_keys( $value, $post_id, $field ) {
	if ( empty( $value ) ) {
		delete_post_meta( $post_id, 'lat' );
		delete_post_meta( $post_id, 'lng' );
	}else{
		update_post_meta( $post_id, 'lat', $value['lat'] );
		update_post_meta( $post_id, 'lng', $value['lng'] );
	}
	
	return $value;
}
add_action( 'acf/update_value/key=field_596c486d856d8', 'bn_save_latlng_keys', 10, 3 ); // Open House - Map