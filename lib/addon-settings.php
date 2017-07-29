<?php
/**
 * ExchangeWP Customer History Add-on
 * @package exchange-addon-customer-history
 * @since 1.0.0
*/

/**
 * Call back for settings page
 *
 * This is set in options array when registering the add-on and called from it_exchange_enable_addon()
 *
 * @since 1.0.0
 * @return void
*/
function it_exchange_customer_history_settings_callback() {
	$IT_Exchange_Customer_History_Add_On = new IT_Exchange_Customer_History_Add_On();
	$IT_Exchange_Customer_History_Add_On->print_settings_page();
}

class IT_Exchange_Customer_History_Add_On {

	/**
	 * @var boolean $_is_admin true or false
	 * @since 1.0.0
	*/
	var $_is_admin;

	/**
	 * @var string $_current_page Current $_GET['page'] value
	 * @since 1.0.0
	*/
	var $_current_page;

	/**
	 * @var string $_current_add_on Current $_GET['add-on-settings'] value
	 * @since 1.0.0
	*/
	var $_current_add_on;

	/**
	 * @var string $statcanadian_message will be displayed if not empty
	 * @since 1.0.0
	*/
	var $statcanadian_message;

	/**
	 * @var string $error_message will be displayed if not empty
	 * @since 1.0.0
	*/
	var $error_message;

	/**
 	 * Class constructor
	 *
	 * Sets up the class.
	 * @since 1.0.0
	 * @return void
	*/
	function __construct() {
		$this->_is_admin       = is_admin();
		$this->_current_page   = empty( $_GET['page'] ) ? false : $_GET['page'];
		$this->_current_add_on = empty( $_GET['add-on-settings'] ) ? false : $_GET['add-on-settings'];

		if ( ! empty( $_POST ) && $this->_is_admin && 'it-exchange-addons' == $this->_current_page && 'customer-history' == $this->_current_add_on ) {
			add_action( 'it_exchange_save_add_on_settings_customer_history', array( $this, 'save_settings' ) );
			do_action( 'it_exchange_save_add_on_settings_customer_history' );
		}
	}

	/**
 	 * Class deprecated constructor
	 *
	 * Sets up the class.
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Customer_History_Add_On() {
		self::__construct();
	}

	function print_settings_page() {
		$settings = it_exchange_get_option( 'addon_customer_history', true, false );

		$form_values  = empty( $this->error_message ) ? $settings : ITForm::get_post_data();
		$form_options = array(
			'id'      => apply_filters( 'it_exchange_add_on_customer_history', 'it-exchange-add-on-customer-history-settings' ),
			'enctype' => apply_filters( 'it_exchange_add_on_customer_history_settings_form_enctype', false ),
			'action'  => 'admin.php?page=it-exchange-addons&add-on-settings=customer_history',
		);
		$form         = new ITForm( $form_values, array( 'prefix' => 'it-exchange-add-on-customer-history' ) );

		if ( ! empty ( $this->status_message ) )
			ITUtility::show_status_message( $this->status_message );
		if ( ! empty( $this->error_message ) )
			ITUtility::show_error_message( $this->error_message );

		?>
		<div class="wrap">
			<?php screen_icon( 'it-exchange' ); ?>
			<h2><?php _e( 'Customer History Settings', 'LION' ); ?></h2>

			<?php do_action( 'it_exchange_customer_history_settings_page_top' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

			<?php $form->start_form( $form_options, 'it-exchange-customer-history-settings' ); ?>
				<?php do_action( 'it_exchange_customer_history_settings_form_top' ); ?>
				<h4>License Key</h4>
				<?php
				   $exchangewp_customer_history_options = get_option( 'it-storage-exchange_addon_customer_history' );
				   $license = $exchangewp_customer_history_options['customer_history_license'];
				   // var_dump($license);
				   $exstatus = trim( get_option( 'exchange_customer_history_license_status' ) );
				   // var_dump($exstatus);
				?>
				<p>
				 <label class="description" for="exchange_customer_history_license_key"><?php _e('Enter your license key'); ?></label>
				 <!-- <input id="customer_history_license" name="it-exchange-add-on-customer_history-customer_history_license" type="text" value="<?php #esc_attr_e( $license ); ?>" /> -->
				 <?php $form->add_text_box( 'customer_history_license' ); ?>
				 <span>
				   <?php if( $exstatus !== false && $exstatus == 'valid' ) { ?>
							<span style="color:green;"><?php _e('active'); ?></span>
							<?php wp_nonce_field( 'exchange_customer_history_nonce', 'exchange_customer_history_nonce' ); ?>
							<input type="submit" class="button-secondary" name="exchange_customer_history_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
						<?php } else {
							wp_nonce_field( 'exchange_customer_history_nonce', 'exchange_customer_history_nonce' ); ?>
							<input type="submit" class="button-secondary" name="exchange_customer_history_license_activate" value="<?php _e('Activate License'); ?>"/>
						<?php } ?>
				 </span>
				</p>


				<?php do_action( 'it_exchange_customer_history_settings_form_bottom' ); ?>
				<p class="submit">
					<?php $form->add_submit( 'submit', array( 'value' => __( 'Save Changes', 'LION' ), 'class' => 'button button-primary button-large' ) ); ?>
				</p>
			<?php $form->end_form(); ?>
			<?php do_action( 'it_exchange_customer_history_settings_page_bottom' ); ?>
			<?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
		</div>
		<?php
	}

	/**
	 * Save settings
	 *
	 * @since 1.0.0
	 * @return void
	*/
    function save_settings() {
        $defaults = it_exchange_get_option( 'addon_customer_history' );
        $new_values = wp_parse_args( ITForm::get_post_data(), $defaults );

        // Check nonce
    		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'it-exchange-customer-history-settings' ) ) {
    			$this->error_message = __( 'Error. Please try again', 'LION' );
    			return;
    		}

    		$errors = apply_filters( 'it_exchange_add_on_manual_transaction_validate_settings', $this->get_form_errors( $new_values ), $new_values );
    		if ( ! $errors && it_exchange_save_option( 'addon_customer_history', $new_values ) ) {
    			ITUtility::show_status_message( __( 'Settings saved.', 'LION' ) );
    		} else if ( $errors ) {
    			$errors = implode( '<br />', $errors );
    			$this->error_message = $errors;
    		} else {
    			$this->status_message = __( 'Settings not saved.', 'LION' );
    		}

				if( isset( $_POST['exchange_customer_history_license_activate'] ) ) {

					// run a quick security check
				 	if( ! check_admin_referer( 'exchange_customer_history_nonce', 'exchange_customer_history_nonce' ) )
						return; // get out if we didn't click the Activate button

					// retrieve the license from the database
					// $license = trim( get_option( 'exchange_customer_history_license_key' ) );
					$exchangewp_customer_history_options = get_option( 'it-storage-exchange_addon_customer_history' );
 				  $license = $exchangewp_customer_history_options['customer_history_license'];

					// data to send in our API request
					$api_params = array(
						'edd_action' => 'activate_license',
						'license'    => $license,
						'item_name'  => urlencode( 'customer-history' ), // the name of our product in EDD
						'url'        => home_url()
					);

					// Call the custom API.
					$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

					// make sure the response came back okay
					if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

						if ( is_wp_error( $response ) ) {
							$message = $response->get_error_message();
						} else {
							$message = __( 'An error occurred, please try again.' );
						}

					} else {

						$license_data = json_decode( wp_remote_retrieve_body( $response ) );

						if ( false === $license_data->success ) {

							switch( $license_data->error ) {

								case 'expired' :

									$message = sprintf(
										__( 'Your license key expired on %s.' ),
										date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
									);
									break;

								case 'revoked' :

									$message = __( 'Your license key has been disabled.' );
									break;

								case 'missing' :

									$message = __( 'Invalid license.' );
									break;

								case 'invalid' :
								case 'site_inactive' :

									$message = __( 'Your license is not active for this URL.' );
									break;

								case 'item_name_mismatch' :

									$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), 'customer_history' );
									break;

								case 'no_activations_left':

									$message = __( 'Your license key has reached its activation limit.' );
									break;

								default :

									$message = __( 'An error occurred, please try again.' );
									break;
							}

						}

					}

					// Check if anything passed on a message constituting a failure
					if ( ! empty( $message ) ) {
						$base_url = admin_url( 'admin.php?page=' . 'customer_history' );
						$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

						wp_redirect( $redirect );
						exit();
					}

					//$license_data->license will be either "valid" or "invalid"
					update_option( 'exchange_customer_history_license_status', $license_data->license );
					// wp_redirect( admin_url( 'admin.php?page=' . 'customer_history-license' ) );
					exit();
				}

			 // deactivate here
			 // listen for our activate button to be clicked
				if( isset( $_POST['exchange_customer_history_license_deactivate'] ) ) {

					// run a quick security check
				 	if( ! check_admin_referer( 'exchange_customer_history_nonce', 'exchange_customer_history_nonce' ) )
						return; // get out if we didn't click the Activate button

					// retrieve the license from the database
					// $license = trim( get_option( 'exchange_customer_history_license_key' ) );

					$exchangewp_customer_history_options = get_option( 'it-storage-exchange_addon_customer_history' );
 				  $license = $exchangewp_customer_history_options['customer_history_license'];


					// data to send in our API request
					$api_params = array(
						'edd_action' => 'deactivate_license',
						'license'    => $license,
						'item_name'  => urlencode( 'customer-history' ), // the name of our product in EDD
						'url'        => home_url()
					);
					// Call the custom API.
					$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

					// make sure the response came back okay
					if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

						if ( is_wp_error( $response ) ) {
							$message = $response->get_error_message();
						} else {
							$message = __( 'An error occurred, please try again.' );
						}

						// $base_url = admin_url( 'admin.php?page=' . 'customer_history-license' );
						// $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

						wp_redirect( 'admin.php?page=customer_history' );
						exit();
					}

					// decode the license data
					$license_data = json_decode( wp_remote_retrieve_body( $response ) );
					// $license_data->license will be either "deactivated" or "failed"
					if( $license_data->license == 'deactivated' ) {
						delete_option( 'exchange_customer_history_license_status' );
					}

					// wp_redirect( admin_url( 'admin.php?page=' . 'customer_history-license' ) );
					exit();

				}

			}

		/**
		* This is a means of catching errors from the activation method above and displaying it to the customer
		*
		* @since 1.2.2
		*/
		function exchange_customer_history_admin_notices() {
		  if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

		  	switch( $_GET['sl_activation'] ) {

		  		case 'false':
		  			$message = urldecode( $_GET['message'] );
		  			?>
		  			<div class="error">
		  				<p><?php echo $message; ?></p>
		  			</div>
		  			<?php
		  			break;

		  		case 'true':
		  		default:
		  			// Developers can put a custom success message here for when activation is successful if they way.
		  			break;

		  	}
		  }
		}

    /**
     * Validates for values
     *
     * Returns string of errors if anything is invalid
     *
     * @since 0.1.0
     * @return void
    */
    public function get_form_errors( $values ) {

        return $errors;
    }
}
