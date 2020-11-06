<?php

include_once path_join(
	CF7SENDINBLUE_PLUGIN_MODULES_DIR,
	'sendinblue/service.php'
);


add_action( 'wpcf7_init', 'wpcf7_sendinblue_register_service', 10, 0 );

function wpcf7_sendinblue_register_service() {
	$integration = WPCF7_Integration::get_instance();

	$integration->add_service( 'sendinblue',
		WPCF7_Sendinblue::get_instance()
	);
}

add_action( 'wpcf7_submit', 'wpcf7_sendinblue_submit', 10, 2 );

function wpcf7_sendinblue_submit( $contact_form, $result ) {
	$service = WPCF7_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	if ( $contact_form->in_demo_mode() ) {
		return;
	}

	$do_submit = true;

	if ( empty( $result['status'] )
	or ! in_array( $result['status'], array( 'mail_sent' ) ) ) {
		$do_submit = false;
	}

	if ( ! $do_submit ) {
		return;
	}

	$submission = WPCF7_Submission::get_instance();

	$properties = array(
		'email' => $submission->get_posted_data( 'your-email' ),
		'attributes' => $submission->get_posted_data(),
	);

	$service->create_contact( $properties );
}


/**
 * Masks a password with a string of asterisks (*).
 *
 * This improves wpcf7_mask_password().
 *
 * @todo Merge into wpcf7_mask_password().
 */
function wpcf7_mask_password_improved( $text, $deprecated = null ) {
	$length = strlen( $text );

	if ( $length <= 4 ) {
		$text = str_repeat( '*', $length );
	} elseif ( $length <= 8 ) {
		$text = str_repeat( '*', $length - 2 )
			. substr( $text, -2 );
	} elseif ( $length <= 24 ) {
		$text = str_repeat( '*', $length - 4 )
			. substr( $text, -4 );
	} elseif ( $length <= 48 ) {
		$text = substr( $text, 0, 4 )
			. str_repeat( '*', $length - 8 )
			. substr( $text, -4 );
	} else {
		$text = substr( $text, 0, 4 )
			. str_repeat( '*', 40 )
			. substr( $text, -4 );
	}

	return $text;
}
