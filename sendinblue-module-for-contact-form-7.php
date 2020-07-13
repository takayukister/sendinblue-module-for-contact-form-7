<?php
/*
Plugin Name: Sendinblue module for Contact Form 7
Description: Just another Sendinblue module for Contact Form 7.
Author: Takayuki Miyoshi
Text Domain: sendinblue-module-for-contact-form-7
Domain Path: /languages/
Version: 0.7-dev
*/

define( 'CF7SENDINBLUE_PLUGIN', __FILE__ );

define( 'CF7SENDINBLUE_PLUGIN_BASENAME',
	plugin_basename( CF7SENDINBLUE_PLUGIN )
);

define( 'CF7SENDINBLUE_PLUGIN_NAME',
	trim( dirname( CF7SENDINBLUE_PLUGIN_BASENAME ), '/' )
);

define( 'CF7SENDINBLUE_PLUGIN_DIR',
	untrailingslashit( dirname( CF7SENDINBLUE_PLUGIN ) )
);

define( 'CF7SENDINBLUE_PLUGIN_MODULES_DIR',
	CF7SENDINBLUE_PLUGIN_DIR . '/modules'
);

add_action( 'plugins_loaded', function() {
	if ( ! class_exists( 'WPCF7_Service' ) ) {
		return;
	}

	$dir = CF7SENDINBLUE_PLUGIN_MODULES_DIR;

	if ( empty( $dir ) or ! is_dir( $dir ) ) {
		return false;
	}

	$file = path_join( $dir, 'sendinblue/sendinblue.php' );

	if ( file_exists( $file ) ) {
		include_once $file;
	}
}, 20, 0 );
