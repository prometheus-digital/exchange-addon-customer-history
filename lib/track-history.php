<?php
/**
 * User tracking functionality.
 *
 * @package iThemes Exchange Customer History
 * @subpackage Tracking
 * @author rzen Media, LLC
 * @license http://www.gnu.org/licenses/gpl.txt GPL2
 * @link https://rzen.net
 */

/**
 * Main tracking class.
 *
 * @since 1.0.0
 */
class Exchange_Track_Customer_History {

	/**
	 * Load all hooks in their proper place.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'it_exchange_register_addons', array( $this, 'register_addon' ) );
		add_action( 'template_redirect', array( $this, 'update_user_history' ) );
		add_action( 'it_exchange_generate_transaction_object', array( $this, 'update_transaction_object') );
		add_filter( 'it_exchange_add_transaction', array( $this, 'save_user_history' ), 10, 7 );

		// Uncomment the following action to enable devmode
		// add_action( 'get_header', array( $this, 'devmode' ) );

	} /* __construct() */

	/**
	 * Returning user history from session.
	 *
	 * @since 1.0.0
	 */
	private function get_user_history() {

		// Grab user history from the current session
		$session_history = it_exchange_get_session_data( 'user_history' );

		// If user has an established history, return that
		if ( isset( $session_history ) && ! empty( $session_history ) ) {
			return (array) $session_history;

		// Otherwise, return an array with the original referrer
		} else {
			$referrer = isset( $_SERVER['HTTP_REFERER'] )
				? $_SERVER['HTTP_REFERER']
				: __( 'Direct Traffic', 'LION' );

			return array( array( 'url' => $referrer, 'time' => time() ) );
		}

	} /* get_user_history() */

	/**
	 * Initialize tracking of user's browsing history
	 *
	 * @since 1.0.0
	 */
	public function update_user_history() {

		// Grab user history from the current session
		$history = $this->get_user_history();

		// Add the current page to the user's history
		$protocol = ( isset( $_SERVER["HTTPS"] ) && $_SERVER["HTTPS"] == "on") ? "https://" : "http://";
		$page_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$history[] = array( 'url' => $page_url, 'time' => time() );

		// Push the updated history to the current session
		it_exchange_update_session_data( 'user_history', $history );

	} /* update_user_history() */

	/**
	 * Add user history to the transaction object during checkout.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $transaction_object Transaction data object.
	 * @return object                     Updated transaction object.
	 */
	public function update_transaction_object( $transaction_object ) {
		$transaction_object->user_history = $this->get_user_history();
		return $transaction_object;
	} /* update_transaction_object () */

	/**
	 * Store user history in transaction meta.
	 *
	 * @since  1.0.0
	 *
	 * @param  integer $transaction_id Transaction post ID.
	 * @param  string  $method         Transaction method name.
	 * @param  integer $method_id      Transaction method ID.
	 * @param  string  $status         Transaction status.
	 * @param  integer $customer_id    User ID.
	 * @param  object  $cart_object    Cart object.
	 * @param  array   $args           Additional transaction arguments.
	 */
	public function save_user_history( $transaction_id, $method, $method_id, $status, $customer_id, $cart_object, $args ) {

		// If there is no transaction ID, bail here
		if ( ! $transaction_id )
			return;

		// Grab the user history from the transaction object
		$user_history = $cart_object->user_history;

		// If user history was captured, sanitize and store the URLs
		if ( is_array( $user_history ) && ! empty( $user_history ) ) {

			// Setup a clean, safe array for the database
			$sanitized_urls = array();

			// Sanitize the referrer a bit differently,
			// than the rest because it may not be a URL.
			$referrer = array_shift( $user_history );
			$sanitized_urls[] = array(
				'url'  => sanitize_text_field( $referrer['url'] ),
				'time' => absint( $referrer['time'] ),
			);

			// Sanitize each additional URL
			foreach ( $user_history as $history ) {
				$sanitized_urls[] = array(
					'url'  => esc_url_raw( $history['url'] ),
					'time' => absint( $history['time'] ),
				);
			}

			// Store sanitized history as post meta
			update_post_meta( $transaction_id, '_user_history', $sanitized_urls );

		}

	} /* save_user_history() */

	/**
	 * Handle developer debug data.
	 *
	 * Usage: Hook to get_header, append "?devmode=true" to any front-end URL.
	 * To view tracked history, add "&output=history".
	 * To view session object, add "&output=session".
	 * To reset tracked history, add "&reset=history".
	 *
	 * @since 1.0.0
	 */
	public function devmode() {

		// Only proceed if URL querystring cotnains "devmode=true"
		if ( isset($_GET['devmode']) && 'true' == $_GET['devmode'] ) {

			// Output user history if URL querystring contains 'output=history'
			if ( isset($_GET['output']) && 'history' == $_GET['output'] )
				print_r( $this->get_user_history() );

			// Output user $_SESSION contents if URL querystring contains 'output=session'
			if ( isset($_GET['output']) && 'session' == $_GET['output'] )
				print_r( it_exchange_get_session() );

			// Clear user_history and dump us back at the homepage if URL querystring contains 'history=reset'
			if ( isset($_GET['history']) && 'reset' == $_GET['history'] ) {
				it_exchange_clear_session_data( 'user_history' );
				wp_redirect( site_url() );
				exit;
			}

		}

	} /* devmode() */

}
$Exchange_Track_Customer_History = new Exchange_Track_Customer_History;
