<?php

add_filter(
	'wpcf7_contact_form_properties',
	'wpcf7_sendinblue_register_property',
	10, 2
);

function wpcf7_sendinblue_register_property( $properties, $contact_form ) {
	$service = WPCF7_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return $properties;
	}

	$properties += array(
		'sendinblue' => array(),
	);

	return $properties;
}


add_action(
	'wpcf7_save_contact_form',
	'wpcf7_sendinblue_save_contact_form',
	10, 3
);

function wpcf7_sendinblue_save_contact_form( $contact_form, $args, $context ) {
	$service = WPCF7_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return;
	}

	$prop = isset( $_POST['wpcf7-sendinblue'] )
		? (array) $_POST['wpcf7-sendinblue']
		: array();

	$prop = wp_parse_args(
		$prop,
		array(
			'enable_contact_list' => true,
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	$prop['email_template'] = absint( $prop['email_template'] );

	$contact_form->set_properties( array(
		'sendinblue' => $prop,
	) );
}


add_filter(
	'wpcf7_editor_panels',
	'wpcf7_sendinblue_editor_panels',
	10, 1
);

function wpcf7_sendinblue_editor_panels( $panels ) {
	$service = WPCF7_Sendinblue::get_instance();

	if ( ! $service->is_active() ) {
		return $panels;
	}

	$contact_form = WPCF7_ContactForm::get_current();

	$prop = wp_parse_args(
		$contact_form->prop( 'sendinblue' ),
		array(
			'enable_contact_list' => true,
			'enable_transactional_email' => false,
			'email_template' => 0,
		)
	);

	$editor_panel = function () use ( $prop, $service ) {

		// Todo: Correct desctiption and link
		$description = sprintf(
			esc_html(
				__( "You can edit the form template here. For details, see %s.", 'contact-form-7' )
			),
			wpcf7_link(
				__( 'https://contactform7.com/editing-form-template/', 'contact-form-7' ),
				__( 'Editing form template', 'contact-form-7' )
			)
		);

		$lists = $service->get_lists();
		$templates = $service->get_templates();

		// Todo: Move the following script and style to Contact Form 7 core.
?>
<script>
( function( $ ) {
	$( function() {
		$( '#wpcf7-sendinblue-enable-contact-list, #wpcf7-sendinblue-enable-transactional-email' ).on( 'change', function() {
			if ( $( this ).is( ':checked' ) ) {
				$( this ).closest( 'tr' ).removeClass( 'inactive' );
			} else {
				$( this ).closest( 'tr' ).addClass( 'inactive' );
			}
		} );
	} );
} )( jQuery );
</script>
<style>
#sendinblue-panel table tr.inactive ~ tr {
	display: none;
}
</style>
<h2><?php echo esc_html( __( 'Sendinblue', 'contact-form-7' ) ); ?></h2>

<fieldset>
	<legend><?php echo $description; ?></legend>

	<table class="form-table" role="presentation">
		<tbody>
			<tr class="<?php echo $prop['enable_contact_list'] ? '' : 'inactive'; ?>">
				<th scope="row">
		<?php

		echo esc_html( __( 'Contact list', 'contact-form-7' ) );

		?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
		<?php

		echo esc_html( __( 'Contact list', 'contact-form-7' ) );

		?>
						</legend>
						<label for="wpcf7-sendinblue-enable-contact-list">
							<input type="checkbox" name="wpcf7-sendinblue[enable_contact_list]" id="wpcf7-sendinblue-enable-contact-list" value="1" <?php checked( $prop['enable_contact_list'] ); ?> />
		<?php

		echo esc_html(
			__( "Add form submitters to your contact list", 'contact-form-7' )
		);

		?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
		<?php

		foreach ( $lists as $list ) {
			echo sprintf(
				'<label><input type="checkbox" value="%1$s" /> %2$s</label>',
				absint( $list['id'] ),
				esc_html( $list['name'] )
			);
		}

		?>
				</td>
			</tr>
			<tr class="<?php echo $prop['enable_transactional_email'] ? '' : 'inactive'; ?>">
				<th scope="row">
		<?php

		echo esc_html( __( 'Transactional email', 'contact-form-7' ) );

		?>
				</th>
				<td>
					<fieldset>
						<legend class="screen-reader-text">
		<?php

		echo esc_html( __( 'Transactional email', 'contact-form-7' ) );

		?>
						</legend>
						<label for="wpcf7-sendinblue-enable-transactional-email">
							<input type="checkbox" name="wpcf7-sendinblue[enable_transactional_email]" id="wpcf7-sendinblue-enable-transactional-email" value="1" <?php checked( $prop['enable_transactional_email'] ); ?> />
		<?php

		echo esc_html(
			__( "Send a transactional email to the contact", 'contact-form-7' )
		);

		?>
						</label>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="wpcf7-sendinblue-email-template">
		<?php

		echo esc_html( __( 'Email template', 'contact-form-7' ) );

		?>
					</label>
				</th>
				<td>
		<?php

		if ( $templates ) {
			echo '<select name="wpcf7-sendinblue[email_template]" id="wpcf7-sendinblue-email-template">';

			foreach ( $templates as $template ) {
				$atts = wpcf7_format_atts( array(
					'value' => $template['id'],
					'selected' => $prop['email_template'] === $template['id']
						? 'selected' : '',
				) );

				echo sprintf(
					'<option %1$s>%2$s</option>',
					$atts,
					esc_html( $template['name'] )
				);
			}

			echo '</select>';
		} else {
			echo sprintf(
				/* translators: %s: link labeled 'Sendinblue dashboard' */
				esc_html( __( 'You have no active email template yet. Go to the %s and create your first template.', 'contact-form-7' ) ),
				sprintf(
					'<a %1$s>%2$s <span class="screen-reader-text">%3$s</span><span aria-hidden="true" class="dashicons dashicons-external" style="text-decoration: none"></span></a>',
					wpcf7_format_atts( array(
						'href' => 'https://app-smtp.sendinblue.com/templates',
						'target' => '_blank',
						'rel' => 'external noreferrer noopener',
					) ),
					esc_html( __( 'Sendinblue dashboard', 'contact-form-7' ) ),
					esc_html( __( '(opens in a new tab)', 'contact-form-7' ) )
				)
			);
		}

		?>
				</td>
			</tr>
		</tbody>
	</table>
</fieldset>
<?php
	};

	$panels += array(
		'sendinblue-panel' => array(
			'title' => __( 'Sendinblue', 'contact-form-7' ),
			'callback' => $editor_panel,
		),
	);

	return $panels;
}
