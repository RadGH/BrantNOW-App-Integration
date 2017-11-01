<?php

if ( !defined('ABSPATH') ) die('This file should not be accessed directly.');

function bn_add_user_roles_and_capabilities() {
	// Add the "Real Estate Agent" role (bn_agent) if it doesn't exist
	// Agents can add open house listings themselves, or edit listings that are assigned to them by a broker.
	if ( get_role( 'bn_agent' ) === null ) {
		add_role( 'bn_agent', 'Real Estate Agent', array( 'read' => 'true' ) );
	}
	
	// Add the "Real Estate Broker" role (bn_broker) if it doesn't exist
	// Brokers can add open house listings to themselves, and any agents who belong to the same agency.
	if ( get_role( 'bn_broker' ) === null ) {
		add_role( 'bn_broker', 'Real Estate Broker', array( 'read' => 'true' ) );
	}
	
	$roles_and_caps = array(
		'administrator' => array(
			'upload_files' => true,
			
			"edit_open_house"              => true,
			"read_open_house"              => true,
			"delete_open_house"            => true,
			
			"edit_open_houses"             => true,
			"edit_others_open_houses"      => true,
			"publish_open_houses"          => true,
			"read_private_open_houses"     => true,
			
			"delete_open_houses"           => true,
			"delete_private_open_houses"   => true,
			"delete_published_open_houses" => true,
			"delete_others_open_houses"    => true,
			"edit_private_open_houses"     => true,
			"edit_published_open_houses"   => true,
			
			"edit_agency"              => true,
			"read_agency"              => true,
			"delete_agency"            => true,
			
			"edit_agencies"             => true,
			"edit_others_agencies"      => true,
			"publish_agencies"          => true,
			"read_private_agencies"     => true,
			
			"delete_agencies"           => true,
			"delete_private_agencies"   => true,
			"delete_published_agencies" => true,
			"delete_others_agencies"    => true,
			"edit_private_agencies"     => true,
			"edit_published_agencies"   => true,
		),
		
		'editor' => array(
			'upload_files' => true,
			
			"edit_open_house"              => true,
			"read_open_house"              => true,
			"delete_open_house"            => true,
			
			"edit_open_houses"             => true,
			"edit_others_open_houses"      => true,
			"publish_open_houses"          => true,
			"read_private_open_houses"     => true,
			
			"delete_open_houses"           => true,
			"delete_private_open_houses"   => true,
			"delete_published_open_houses" => true,
			"delete_others_open_houses"    => true,
			"edit_private_open_houses"     => true,
			"edit_published_open_houses"   => true,
			
			"edit_agency"              => true,
			"read_agency"              => true,
			"delete_agency"            => true,
			
			"edit_agencies"             => true,
			"edit_others_agencies"      => true,
			"publish_agencies"          => true,
			"read_private_agencies"     => true,
			
			"delete_agencies"           => true,
			"delete_private_agencies"   => true,
			"delete_published_agencies" => true,
			"delete_others_agencies"    => true,
			"edit_private_agencies"     => true,
			"edit_published_agencies"   => true,
		),
		
		'bn_broker' => array(
			'upload_files' => true,
			
			"edit_open_house"              => true,
			"read_open_house"              => true,
			"delete_open_house"            => true,
			
			"edit_open_houses"             => true,
			"edit_others_open_houses"      => true,
			"publish_open_houses"          => true,
			"read_private_open_houses"     => false,
			
			"delete_open_houses"           => true,
			"delete_private_open_houses"   => false,
			"delete_published_open_houses" => true,
			"delete_others_open_houses"    => true,
			"edit_private_open_houses"     => false,
			"edit_published_open_houses"   => true,
			
			"edit_agency"              => false,
			"read_agency"              => false,
			"delete_agency"            => false,
			
			"edit_agencies"             => false,
			"edit_others_agencies"      => false,
			"publish_agencies"          => false,
			"read_private_agencies"     => false,
			
			"delete_agencies"           => false,
			"delete_private_agencies"   => false,
			"delete_published_agencies" => false,
			"delete_others_agencies"    => false,
			"edit_private_agencies"     => false,
			"edit_published_agencies"   => false,
		),
		
		'bn_agent' => array(
			'upload_files' => true,
			
			"edit_open_house"              => true,
			"read_open_house"              => true,
			"delete_open_house"            => true,
			
			"edit_open_houses"             => true,
			"edit_others_open_houses"      => false,
			"publish_open_houses"          => true,
			"read_private_open_houses"     => false,
			
			"delete_open_houses"           => true,
			"delete_private_open_houses"   => false,
			"delete_published_open_houses" => true,
			"delete_others_open_houses"    => false,
			"edit_private_open_houses"     => true,
			"edit_published_open_houses"   => true,
			
			"edit_agency"              => false,
			"read_agency"              => false,
			"delete_agency"            => false,
			
			"edit_agencies"             => false,
			"edit_others_agencies"      => false,
			"publish_agencies"          => false,
			"read_private_agencies"     => false,
			
			"delete_agencies"           => false,
			"delete_private_agencies"   => false,
			"delete_published_agencies" => false,
			"delete_others_agencies"    => false,
			"edit_private_agencies"     => false,
			"edit_published_agencies"   => false,
		),
	);
	
	foreach( $roles_and_caps as $role_name => $c ) {
		$role = get_role( $role_name );
		if ( $role === null ) continue;
		
		foreach( $c as $cap_name => $cap_enabled ) {
			if ( $cap_enabled ) {
				$role->add_cap( $cap_name );
			}else{
				$role->remove_cap( $cap_name );
			}
		}
	}
}


function bn_user_phone_and_title_fields( $user ) { ?>
	<script>jQuery(function() {
		var $profile_form = jQuery('#your-profile');
		var $membership_rows = jQuery('#mepr_phone, #mepr_title').closest('tr');
		var $agency_table = $profile_form.find('tr.acf-field-59c1fa27f0384').closest('table');
		
		// Move our title and phone fields after the URL field. Also move the user's agency, if it exists
		jQuery('#url').closest('tr').after( $agency_table.find('tr') ).after( $membership_rows );
		
		// Remove the URL and Biography fields
		$profile_form.find('tr.user-description-wrap').remove();
		$profile_form.find('tr.user-url-wrap').remove();
		
		// Remove the <h2> that reads "About Yourself" and "User Agency"
		$profile_form.find('h2').each(function() {
			var text = jQuery(this).text();
			if ( text === 'About Yourself' || text === 'User Agency' ) {
				jQuery(this).remove();
			}
		});
		
		$agency_table.remove();
	});</script>
	<?php
}
add_action( 'show_user_profile', 'bn_user_phone_and_title_fields' );
add_action( 'edit_user_profile', 'bn_user_phone_and_title_fields' );