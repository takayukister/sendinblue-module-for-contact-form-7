<?php

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

class WPCF7_Sendinblue extends WPCF7_Service {

	private static $instance;
	private $api_key;

	public static function get_instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {
		$option = WPCF7::get_option( 'sendinblue' );

		if ( isset( $option['api_key'] ) ) {
			$this->api_key = $option['api_key'];
		}
	}

	public function get_title() {
		return __( 'Sendinblue', 'contact-form-7' );
	}

	public function is_active() {
		return (bool) $this->get_api_key();
	}

	public function get_api_key() {
		return $this->api_key;
	}

	public function get_categories() {
		return array( 'email_marketing' );
	}

	public function icon() {
	}

	public function link() {
		echo wpcf7_link(
			'https://www.sendinblue.com/?tap_a=30591-fb13f0&tap_s=1031580-b1bb1d',
			'sendinblue.com'
		);
	}

	public function create_contact( $properties ) {
		$endpoint = 'https://api.sendinblue.com/v3/contacts';

		$request = array(
			'headers' => array(
				'Accept' => 'application/json',
				'Content-Type' => 'application/json; charset=utf-8',
				'API-Key' => $this->get_api_key(),
			),
			'body' => json_encode( $properties ),
		);

		$response = wp_remote_post( $endpoint, $request );

		if ( 400 <= (int) wp_remote_retrieve_response_code( $response ) ) {
			if ( WP_DEBUG ) {
				$this->log( $endpoint, $request, $response );
			}

			return false;
		}

		return true;
	}

	protected function log( $url, $request, $response ) {
		wpcf7_log_remote_request( $url, $request, $response );
	}

	protected function menu_page_url( $args = '' ) {
		$args = wp_parse_args( $args, array() );

		$url = menu_page_url( 'wpcf7-integration', false );
		$url = add_query_arg( array( 'service' => 'sendinblue' ), $url );

		if ( ! empty( $args ) ) {
			$url = add_query_arg( $args, $url );
		}

		return $url;
	}

	protected function save_data() {
		WPCF7::update_option( 'sendinblue', array(
			'api_key' => $this->api_key,
		) );
	}

	protected function reset_data() {
		$this->api_key = null;
		$this->save_data();
	}

	public function load( $action = '' ) {
		if ( 'setup' == $action and 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'wpcf7-sendinblue-setup' );

			if ( ! empty( $_POST['reset'] ) ) {
				$this->reset_data();
				$redirect_to = $this->menu_page_url( 'action=setup' );
			} else {
				$api_key = isset( $_POST['api_key'] ) ? trim( $_POST['api_key'] ) : '';

				if ( $api_key ) {
					$this->api_key = $api_key;
					$this->save_data();

					$redirect_to = $this->menu_page_url( array(
						'message' => 'success',
					) );
				} else {
					$redirect_to = $this->menu_page_url( array(
						'action' => 'setup',
						'message' => 'invalid',
					) );
				}
			}

			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	public function admin_notice( $message = '' ) {
		if ( 'invalid' == $message ) {
			echo sprintf(
				'<div class="error notice notice-error is-dismissible"><p><strong>%1$s</strong>: %2$s</p></div>',
				esc_html( __( "Error", 'contact-form-7' ) ),
				esc_html( __( "Invalid key values.", 'contact-form-7' ) ) );
		}

		if ( 'success' == $message ) {
			echo sprintf( '<div class="updated notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html( __( 'Settings saved.', 'contact-form-7' ) ) );
		}
	}

	public function display( $action = '' ) {
		echo '<p>' . sprintf(
			esc_html( __( 'Email, SMS, Facebook, Chat, CRM, and more, all-in-one platform to help you grow your business through building stronger customer relationships. For details, see %s.', 'contact-form-7' ) ),
			wpcf7_link(
				__( 'https://contactform7.com/recaptcha/', 'contact-form-7' ),
				__( 'reCAPTCHA (v3)', 'contact-form-7' )
			)
		) . '</p>';

		if ( $this->is_active() ) {
			echo sprintf(
				'<p class="dashicons-before dashicons-yes">%s</p>',
				esc_html( __( "Sendinblue is active on this site.", 'contact-form-7' ) )
			);
		}

		if ( 'setup' == $action ) {
			$this->display_setup();
		} else {
			echo sprintf(
				'<p><a href="%1$s" class="button">%2$s</a></p>',
				esc_url( $this->menu_page_url( 'action=setup' ) ),
				esc_html( __( 'Setup integration', 'contact-form-7' ) )
			);
		}
	}

	private function display_setup() {
		$api_key = $this->get_api_key();

?>
<form method="post" action="<?php echo esc_url( $this->menu_page_url( 'action=setup' ) ); ?>">
<?php wp_nonce_field( 'wpcf7-sendinblue-setup' ); ?>
<table class="form-table">
<tbody>
<tr>
	<th scope="row"><label for="publishable"><?php echo esc_html( __( 'API key', 'contact-form-7' ) ); ?></label></th>
	<td><?php
		if ( $this->is_active() ) {
			echo esc_html( wpcf7_mask_password( $api_key ) );
			echo sprintf(
				'<input type="hidden" value="%s" id="api_key" name="api_key" />',
				esc_attr( $api_key )
			);
		} else {
			echo sprintf(
				'<input type="text" aria-required="true" value="%s" id="api_key" name="api_key" class="regular-text code" />',
				esc_attr( $api_key )
			);
		}
	?></td>
</tr>
</tbody>
</table>
<?php
		if ( $this->is_active() ) {
			submit_button(
				_x( 'Remove key', 'API keys', 'contact-form-7' ),
				'small', 'reset'
			);
		} else {
			submit_button( __( 'Save changes', 'contact-form-7' ) );
		}
?>
</form>
<?php
	}
}
