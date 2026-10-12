<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'banner_me_banners' );
delete_option( 'banner_me_db_version' );

// Clean up old v1 options if they still exist
delete_option( 'banner_text' );
delete_option( 'banner_visible' );
delete_option( 'banner_start_date' );
delete_option( 'banner_end_date' );
