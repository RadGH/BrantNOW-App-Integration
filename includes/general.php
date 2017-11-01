<?php

if ( !defined( 'ABSPATH' ) ) die( 'This file should not be accessed directly.' );

function bn_get_google_map_api_key() {
	$api_key = 'AIzaSyB5TtkfPKhA3LiltYwt_Miu5GCuV0dc9f4';
	return apply_filters( 'bn_google_map_api_key', $api_key );
}

function bn_get_google_geocoding_api_key() {
	$api_key = 'AIzaSyB5TtkfPKhA3LiltYwt_Miu5GCuV0dc9f4';
	return apply_filters( 'bn_google_geocoding_api_key', $api_key );
}

function bn_get_google_static_maps_api_key() {
	$api_key = 'AIzaSyB5TtkfPKhA3LiltYwt_Miu5GCuV0dc9f4';
	return apply_filters( 'bn_google_static_maps_api_key', $api_key );
}

function bn_acf_prepare_settings() {
	acf_update_setting( 'google_api_key', bn_get_google_map_api_key() );
}
add_action('acf/init', 'bn_acf_prepare_settings');

function bn_acf_do_form_header() {
	acf_form_head();
}
add_action( 'get_header', 'bn_acf_do_form_header', 3);

/**
 * Adds scripts/stylesheets to the admin page
 */
function bn_enqueue_admin_scripts() {
	global $BrantNOW;
	wp_enqueue_style( 'brantnow', $BrantNOW->plugin_url . '/assets/brantnow-admin.css', array(), $BrantNOW->version );
}
add_action( 'admin_enqueue_scripts', 'bn_enqueue_admin_scripts', 60 );

/**
 * Save the lat/lng values from ACF google map fields as separate keys.
 * If your google map field is named "location", your keys will be: "location_lat" and "location_lng"
 *
 * @param $value
 * @param $post_id
 * @param $field
 *
 * @return mixed
 */
function bn_save_latlng_keys_and_geocoded_address( $value, $post_id, $field ) {
	$meta_key = $field['name'];
	
	if ( empty( $value ) || empty($value['lat']) ) {
		// The address has been cleared, delete the internal values.
		delete_post_meta( $post_id, $meta_key . '_lat' );
		delete_post_meta( $post_id, $meta_key . '_lng' );
		delete_post_meta( $post_id, $meta_key . '_geocoded' );
	}else{
		// Address saved, update lat/lng
		update_post_meta( $post_id, $meta_key . '_lat', $value['lat'] );
		update_post_meta( $post_id, $meta_key . '_lng', $value['lng'] );
		
		// Also save a copy of the geocoded address, but only if the address changed
		$existing_geocode = get_post_meta( $post_id, $meta_key . '_geocoded', true );
		
		if ( empty($existing_geocode) || ($existing_geocode['full_address'] != $value['address']) ) {
			$geocoded_address = bn_geocode_address( $value['address'] );
			
			if ( !$geocoded_address || is_wp_error($geocoded_address) ) {
				delete_post_meta( $post_id, $meta_key . '_geocoded' );
			}else{
				$geocoded_address['full_address'] = $value['address'];
				update_post_meta( $post_id, $meta_key . '_geocoded', $geocoded_address );
			}
		}
	}
	
	return $value;
}
add_action( 'acf/update_value/type=google_map', 'bn_save_latlng_keys_and_geocoded_address', 10, 3 );

/**
 * Takes an address, returns an array:
 *      array(
 *          'lat'     => (float) 44.0521,
 *          'lng'     => (float) -123.0868,
 *          'address' => (string) "1234 Example Street",
 *          'city'    => (string) "Eugene",
 *          'state'   => (string) "Oregon",
 *          'zip'     => (string) "97404-1234"
 *      );
 *
 * If geocoding lookup returned invalid, returns a WP_Error object.
 * If no results found, returns false.
 *
 * @param $address
 *
 * @return array|false|WP_Error
 */
function bn_geocode_address( $address ) {
	$geocoding_api_key = bn_get_google_geocoding_api_key();
	$url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . '&key=' . urlencode($geocoding_api_key);
	
	$result = wp_remote_get( $url );
	
	// Connection / unknown error
	if ( is_wp_error($result) ) {
		return $result;
	}
	
	// Geocoding API error.
	if ( $result['response']['code'] != 200 ) {
		$url = str_replace( $geocoding_api_key, '**API_KEY_HIDDEN**', $url );
		return new WP_Error(
			'geocoding_error_' . $result['response']['code'],
			'Error processing API request.<br>Address:<br><code>'. esc_html($address) .'</code><br><br>API URL:<br><code>' . esc_html($url) . '</code><br><br>' . 'Error Message: <br>' . $result['response']['message'],
			array( 'address' => $address, 'result' => $result, 'apikey' => $geocoding_api_key )
		);
	}
	
	$json = json_decode( $result['body'], true );
	
	// Invalid response error.
	if ( !$json || empty($json) || !isset($json['status']) ) {
		return new WP_Error(
			'geocoding_response_invalid',
			'The Google Geocoding API returned an invalid response',
			array( 'address' => $address, 'result' => $result, 'apikey' => $geocoding_api_key )
		);
	}
	
	// Response not OK?
	if ( $json['status'] != 'OK' ) {
		return new WP_Error(
			'geocoding_response_' . $json['status'],
			'The Google Geocoding API returned an unsupported response: "'.$json['status'].'"',
			array( 'address' => $address, 'result' => $result, 'apikey' => $geocoding_api_key )
		);
	}
	
	// No results?
	if ( empty($json['results']) ) return false;
	
	// Get lat/lng
	$lat = isset($json['results'][0]['geometry']['location']['lat']) ? $json['results'][0]['geometry']['location']['lat'] : false;
	$lng = isset($json['results'][0]['geometry']['location']['lng']) ? $json['results'][0]['geometry']['location']['lng'] : false;
	
	// Return array of lat/lng, if valid
	if ( $lat === false && $lng === false ) {
		return new WP_Error(
			'geocoding_no_result',
			'The address supplied did not return a valid latitude/longitude position. Please verify that the address is correct.',
			array( 'address' => $address, 'result' => $result, 'apikey' => $geocoding_api_key )
		);
	}
	
	$address = _bn_geocoding_get_street_address( $json['results'][0] );
	$city = _bn_geocoding_get_city( $json['results'][0] );
	$state = _bn_geocoding_get_state( $json['results'][0] );
	$zip = _bn_geocoding_get_zip( $json['results'][0] );
	$country = _bn_geocoding_get_country( $json['results'][0] );
	
	return array(
		'lat'     => $lat,
		'lng'     => $lng,
		'address' => $address,
		'city'    => $city,
		'state'   => $state,
		'zip'     => $zip,
		'country'     => $country
	);
}


/*
 * Internal functions to process street address information from Google Geocoding API
 */
function _bn_geocoding_get_street_address( $result ) {
	$number = _bn_geocoding_property( $result, 'street_number' ); // 12345
	$name = _bn_geocoding_property( $result, 'route' ); // Example Avenue
	
	return implode(' ', array_filter( array( $number, $name ) ) );
}

function _bn_geocoding_get_city( $result ) {
	return _bn_geocoding_property( $result, 'locality' ); // Eugene
}

function _bn_geocoding_get_state( $result ) {
	return _bn_geocoding_property( $result, 'administrative_area_level_1' ); // Oregon
}

function _bn_geocoding_get_zip( $result ) {
	return _bn_geocoding_property( $result, 'postal_code' ); // 97401
}

function _bn_geocoding_get_country( $result ) {
	return _bn_geocoding_property( $result, 'country' ); // United States
}

function _bn_geocoding_property( $result, $target ) {
	if ( empty($result['address_components']) ) return false;
	
	foreach( $result['address_components'] as $key => $data ) {
		if ( isset($data['types'] ) ) foreach( $data['types'] as $datatype ) {
			if ( $datatype == $target ) {
				if ( isset($data['long_name']) ) return $data['long_name'];
				else if ( isset($data['short_name']) ) return $data['short_name'];
				else return false;
			}
		}
	}
	
	return false;
}

/**
 * Takes a number and formats it as a dollar value.
 *
 * @param $number
 * @param int $decimals
 *
 * @return string
 */
function bn_format_price( $number, $decimals = 0 ) {
	return '$' . number_format($number, $decimals);
}

/**
 * Creates a clickable link from a URL, and makes the visible portion of the URL look like a simple website.
 * Eg, http://www.facebook.com/radgh will appear as facebook.com/radgh
 *
 * @param $url
 *
 * @return string
 */
function bn_format_external_link( $url ) {
	$host = parse_url( $url, PHP_URL_HOST );
	if ( $host ) {
		$label = str_replace( array( 'http://', 'https://', 'www.' ), '', $host );
		
		$path = parse_url( $url, PHP_URL_PATH );
		if ( $path ) $label .= $path;
	}else{
		$label = str_replace( array( 'http://', 'https://', 'www.' ), '', $url );
	}
	
	// Shorten the link
	// realtor.ca/Residential/Single-Family/18509340/10---379-DARLING-Street-Brantford-Ontario-N3S7G4
	// realtor.ca/Resid...o-N3S7G4
	if ( strlen( $label ) > 46 ) {
		$label = substr( $label, 0, 32 ) . '&hellip;' . substr($label, -8);
	}
	
	
	return '<a href="'. esc_attr($url) .'" target="_blank" rel="external nofollow">'. esc_html($label) .'</a>';
}

/**
 * Creates a static map optionally with a link to Google Maps surrounding it.
 *
 * @param $address
 * @param $lat
 * @param $lng
 * @param bool $create_link
 *
 * @return string
 */
function bn_generate_map( $address, $lat, $lng, $create_link = true ) {
	$google_static_map_key = bn_get_google_static_maps_api_key();
	
	$maps_link = 'https://maps.google.com/?q=' . urlencode($address);
	
	$map_w = '400';
	$map_h = '240';
	$zoom = '14';
	$latlng_coords = $lat . ',' . $lng;
	$alt = 'Map location of '. $address;
	
	$output  = '<p>';
	
	if ( $create_link ) $output .= '<a href="'. esc_attr($maps_link) .'" target="_blank" rel="external" title="Open in Google Maps">';
	
	$output .= '<img '.
					'src="https://maps.googleapis.com/maps/api/staticmap'.
							'?center='. esc_attr($latlng_coords) .
							'&scale=2&zoom='. esc_attr($zoom) .
							'&size='. esc_attr($map_w) .'x'. esc_attr($map_h) .
							'&maptype=roadmap'.
							'&markers='. esc_attr($latlng_coords) .
							'&key='. esc_attr($google_static_map_key) . '" '.
					'alt="'. esc_attr($alt) .'"'.
					'style="max-width: 100%; height: auto;"' .
					'>';
	
	if ( $create_link ) $output .= '</a>';
	
	$output .= '</p>';
	
	return $output;
}



/**
 * Returns the address from the ACF location.
 *
 * @param $post_id
 * @param string $meta_key
 * @param bool $omit_country
 *
 * @return bool
 */
function bn_get_location_address( $post_id, $meta_key = 'location', $omit_country = true ) {
	$location = get_field( $meta_key, $post_id );
	
	if ( $omit_country ) $location = str_replace( ', Canada', '', $location );
	
	return !empty($location['address']) ? $location['address'] : false;
}

/**
 * Returns the street address for a property.
 * If the verify_state_province does not match the state/province of the listing, the whole address will be shown.
 *
 * @param $post_id
 * @param string $meta_key
 * @param string $verify_state_province
 *
 * @return bool
 */
function bn_get_location_street_address( $post_id, $meta_key = 'location', $verify_state_province = 'Brantford' ) {
	$location = get_post_meta( $post_id, $meta_key . '_geocoded', true );
	
	// Fall back to show whole address:
	if ( empty($location['address']) )
		return bn_get_location_address( $post_id );
	
	// If different city, show whole address
	if ( empty($location['city']) || strtolower($location['city']) != strtolower($verify_state_province) )
		return bn_get_location_address( $post_id );
	
	return $location['address'];
}

/**
 * Returns an array of lat/lng coordinates from the ACF location.
 *
 * @param $post_id
 * @param string $meta_key
 *
 * @return array|bool
 */
function bn_get_location_latlng( $post_id, $meta_key = 'location' ) {
	$location = get_field( $meta_key, $post_id );
	$lat = !empty($location['lat']) ? $location['lat'] : false;
	$lng = !empty($location['lng']) ? $location['lng'] : false;
	
	if ( $lat !== false && $lng !== false )
		return array( $lat, $lng );
	else
		return false;
}