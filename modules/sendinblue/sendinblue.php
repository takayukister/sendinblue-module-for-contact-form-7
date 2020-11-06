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
 * @param int $right Length of right-hand unmasked text. Default 0.
 * @param int $left Length of left-hand unmasked text. Default 0.
 *
 * @todo Merge into wpcf7_mask_password().
 */
function wpcf7_mask_password_improved( $text, $right = 0, $left = 0 ) {
	$length = strlen( $text );

	$right = absint( $right );
	$left = absint( $left );

	if ( $length < $right + $left ) {
		$right = $left = 0;
	}

	if ( $length <= 48 ) {
		$masked = str_repeat( '*', $length - ( $right + $left ) );
	} elseif ( $right + $left < 48 ) {
		$masked = str_repeat( '*', 48 - ( $right + $left ) );
	} else {
		$masked = '****';
	}

	$left_unmasked = $left ? substr( $text, 0, $left ) : '';
	$right_unmasked = $right ? substr( $text, -1 * $right ) : '';

	$text = $left_unmasked . $masked . $right_unmasked;

	return $text;
}
