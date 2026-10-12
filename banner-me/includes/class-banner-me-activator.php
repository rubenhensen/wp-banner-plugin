<?php

class Banner_Me_Activator {

	public static function activate() {
		if ( get_option( 'banner_me_banners' ) === false ) {
			add_option( 'banner_me_banners', array() );
		}
	}
}
