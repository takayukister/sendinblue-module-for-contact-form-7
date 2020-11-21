<?php

include_once path_join(
	CF7SENDINBLUE_PLUGIN_MODULES_DIR,
	'sendinblue/service.php'
);

include_once path_join(
	CF7SENDINBLUE_PLUGIN_MODULES_DIR,
	'sendinblue/contact-form-properties.php'
);


add_action( 'wpcf7_init', 'wpcf7_sendinblue_register_service', 1, 0 );

function wpcf7_sendinblue_register_service() {
	$integration = WPCF7_Integration::get_instance();

	$integration->add_service( 'sendinblue',
		WPCF7_Sendinblue::get_instance()
	);
}


add_action( 'wpcf7_submit', 'wpcf7_sendinblue_submit', 10, 2 );

function wpcf7_sendinblue_submit( $contact_form, $result ) {
	if ( $contact_form->in_demo_mode() ) {
		return;
	}

	$service = WPCF7_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	if ( empty( $result['posted_data_hash'] ) ) {
		return;
	}

	$prop = wp_parse_args(
		$contact_form->prop( 'sendinblue' ),
		array(
			'enable_contact_list' => true,
			'contact_lists' => array(),
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	if ( ! $prop['enable_contact_list'] ) {
		return;
	}

	$email = wpcf7_sendinblue_retrieve_attribute( 'EMAIL' );

	$attributes = array();

	foreach ( (array) $service->get_contact_attributes() as $attr ) {
		if ( empty( $attr['category'] ) or 'normal' != $attr['category'] ) {
			continue;
		}

		if ( ! empty( $attr['name'] ) ) {
			$attributes += array(
				$attr['name'] => wpcf7_sendinblue_retrieve_attribute( $attr['name'] ),
			);
		}
	}

	if ( empty( $email ) and empty( $attributes['SMS'] ) ) {
		return;
	}

	$contact_id = $service->create_contact( array(
		'email' => $email,
		'attributes' => array_filter( $attributes ),
		'listIds' => (array) $prop['contact_lists'],
	) );

	if ( ! $contact_id ) {
		return;
	}

	if ( ! $prop['enable_transactional_email'] or ! $prop['email_template'] ) {
		return;
	}

	$service->send_email( array(
		'templateId' => absint( $prop['email_template'] ),
		'to' => array(
			array(
				'name' => sprintf(
					'%1$s %2$s',
					$attributes['FIRSTNAME'],
					$attributes['LASTNAME']
				),
				'email' => $email,
			),
		),
	) );
}


function wpcf7_sendinblue_retrieve_attribute( $name, $context = 'contact' ) {
	$name = strtoupper( trim( $name ) );

	if ( empty( $name ) ) {
		return false;
	}

	$submission = WPCF7_Submission::get_instance();

	$field_name = sprintf(
		'your-%s',
		preg_replace( '/[^0-9a-z]+/', '-', strtolower( $name ) )
	);

	$attribute = $submission->get_posted_data( $field_name );
	$attribute = implode( ' ', (array) $attribute );
	$attribute = trim( $attribute );

	if ( '' === $attribute and 'contact' == $context ) {
		$your_name = $submission->get_posted_data( 'your-name' );
		$your_name = implode( ' ', (array) $your_name );
		$your_name = explode( ' ', $your_name );

		if ( 'LASTNAME' == $name ) {
			$attribute = implode(
				' ',
				array_slice( $your_name, 1 )
			);
		} elseif ( 'FIRSTNAME' == $name ) {
			$attribute = implode(
				' ',
				array_slice( $your_name, 0, 1 )
			);
		}
	}

	if ( 'contact' == $context and 'SMS' == $name ) {
		$plus = '+' == substr( $attribute, 0, 1 ) ? '+' : '';
		$attribute = preg_replace( '/[^0-9]/', '', $attribute );

		if ( 6 < strlen( $attribute ) and strlen( $attribute ) < 18 ) {
			$attribute = $plus . $attribute;
		} else { // Invalid phone number
			$attribute = '';
		}
	}

	$attribute = apply_filters(
		'wpcf7_sendinblue_retrieve_attribute',
		$attribute, $name, $context
	);

	return (string) $attribute;
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
