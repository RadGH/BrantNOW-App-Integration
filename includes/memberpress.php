<?php

if ( !defined('ABSPATH') ) die('This file should not be accessed directly.');

function wcd_add_user_form_to_memberpress_profile_fields() {
	$page_id = get_field( 'cantors_edit_profile_page', 'options' );
	if ( !$page_id ) return;
	
	echo'<a href="', get_permalink($page_id), '" class="button wcd-profile-button">', "Edit WCN Profile &raquo;", '</a>';
}
add_action( 'mepr-account-home-fields', 'wcd_add_user_form_to_memberpress_profile_fields' );