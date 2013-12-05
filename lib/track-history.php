<?php

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
		add_action( 'it_exchange_after_payment_details', array( $this, 'output_user_history' ) );
		add_filter( 'send_admin_emails_body', array( $this, 'admin_email' ), 10, 2 );

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
	 * Render user history list.
	 *
	 * @since 1.0.0
	 *
	 * @param integer $transaction_id Transaction post ID.
	 */
	public function output_user_history( $transaction_id = 0 ) {

		// Attempt to retrieve post ID from query arg
		if ( ! $transaction_id )
			$transaction_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

		// If no post ID is available, bail here
		if ( ! $transaction_id )
			return false;

		// Initialize output
		$output = '';
		$output .= '<div class="products-header spacing-wrapper clearfix"></div>';
		$output .= '<div class="spacing-wrapper clearfix">';

		// Create a header for our list
		$output .= sprintf( '<h2>%s</h2>', __( 'Customer Browsing History', 'LION' ) );
		$output .= sprintf( '<p>%s</p>', __( 'Below is every page the customer visited, in order, prior to completing this transaction.', 'LION' ) );

		// Grab user history
		$user_history = get_post_meta( $transaction_id, '_user_history', true );

		// Output the user's history (if collected)
		if ( ! empty( $user_history ) && is_array( $user_history ) ) {

			// Strip off the referring URL
			$referrer = array_shift( $user_history );

			// Output the referrer
			$output .= '<p>';
			$output .= sprintf( __( '<strong>Referrer:</strong> %s', 'LION' ), preg_replace( '/(http.+)/', '<a href="$1" target="_blank">$1</a>', $referrer['url'] ) );
			$output .= '</p>';

			// If referrer was a search engine, output the query string the user used
			$search_history = $this->get_users_search_query( $referrer['url'] );
			if ( $search_history )
				$output .= '<p>' . sprintf( __( 'Original search query: %s', 'LION' ), '<strong><mark>' . $search_history . '</mark></strong>' ) . '</p>';

			// Output ordered list of browsing history
			$output .= '<table cellpadding="0" cellspacing="0" border="0" style="width:100%; border:1px solid #eee;">';
			$output .= '<tr>';
			$output .= '<th colspan="2" style="background:#333; color:#fff; padding:10px; text-align:left;">' . __( 'URL', 'LION' ) . '</th>';
			$output .= '<th style="background:#333; color:#fff; padding:10px; text-align:left;">' . __( 'Timestamp', 'LION' ) . '</th>';
			$output .= '<th style="background:#333; color:#fff; padding:10px; text-align:right;">' . __( 'Time on Page', 'LION' ) . '<span class="tip" title="' . __( 'Time elapsed between this page and the next.', 'LION' ) . '">i</span></th>';
			$output .= '<th style="background:#333; color:#fff; padding:10px; text-align:right;">' . __( 'Total', 'LION' ) . '<span class="tip" title="' . __( 'Time elapsed since first arriving on site.', 'LION' ) . '">i</span></th>';
			$output .= '</tr>';
			$count = 1;
			foreach ( $user_history as $key => $history ) {

				// Don't output the very last item.
				// This is always the internal /transaction/ page.
				if ( end( $user_history ) == $history )
					continue;

				$output .= '<tr>';
				$output .= '<td style="text-align:right; padding:10px 5px;">' . number_format( $count++, 0 ) . '.</td>';
				$output .= '<td style="padding:10px;"><a href="' . esc_url( $history['url'] ) . '" target="_blank">' . esc_url( $history['url'] ) . '</a></td>';
				$output .= '<td style="padding:10px; text-align:left;">' . date( 'Y/m/d \&\n\d\a\s\h\; h:i:sa', ( $history['time'] + get_option( 'gmt_offset' ) * 3600 ) ) . '</td>';
				$next = isset( $user_history[ $key + 1 ] ) ? $user_history[ $key + 1 ] : end( $user_history );
				$output .= '<td style="padding:10px; text-align:right;">' . $this->calculate_elapsed_time( $history['time'], $next['time'] ) . '</td>';
				$output .= '<td style="padding:10px; text-align:right;">' . $this->calculate_elapsed_time( $referrer['time'], $next['time'] ) . '</td>';
				$output .= '</tr>';
			}
			$output .= '</table>';

			// Output total elapsed time
			$output .= '<p>';
			$final_entry = end( $user_history );
			$output .= sprintf( __( '<strong>Total Time Elapsed:</strong> %s', 'LION' ), $this->calculate_elapsed_time( $referrer['time'], $final_entry['time'] ) );
			$output .= '</p>';

		// Otherwise, inform that no history was collected
		} else {
			$output .= '<p><em>' . __( 'No page history collected.', 'LION' ) . '</em></p>';
		}

		// Close out the container
		$output .= '</div>';

		// Echo our output
		echo $output;
	} /* output_user_history() */

	/**
	 * Add user history to admin purchase notification emails.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $output      Email body.
	 * @param  object $transaction Transaction post object.
	 * @return string              Updated email body.
	 */
	public function admin_email( $output, $transaction ) {

		// Append user history to the email body
		$output .= $this->output_user_history( $transaction->ID );

		// Return the output
		return $output;

	} /* admin_email() */

	/**
	 * Calculate time elapsed between two timestamps.
	 *
	 * @since  1.0.0
	 *
	 * @param  integer $original_time Older timestamp.
	 * @param  integer $new_time      Newer timestamp.
	 * @return string                 Time elapsed as 0d 00h 00m 00s.
	 */
	private function calculate_elapsed_time( $original_time = 0, $new_time = 0 ) {

		// If no new time present, use current time
		$new_time = $new_time ? $new_time : time();

		// Calculate elapsed time
		$elapsed_time = absint( absint( $new_time ) - absint( $original_time ) );

		// Output progressive amounts of detail
		if ( MINUTE_IN_SECONDS >= $elapsed_time ) {
			return sprintf(
				__( '%1$02ds', 'LION' ),
				$elapsed_time % 60
			);
		} elseif ( HOUR_IN_SECONDS >= $elapsed_time ) {
			return sprintf(
				__( '%1$02dm %2$02ds', 'LION' ),
				$elapsed_time / MINUTE_IN_SECONDS % 60,
				$elapsed_time % 60
			);
		} elseif ( DAY_IN_SECONDS >= $elapsed_time ) {
			return sprintf(
				__( '%1$02dh %2$02dm %3$02ds', 'LION' ),
				$elapsed_time / HOUR_IN_SECONDS % 60,
				$elapsed_time / MINUTE_IN_SECONDS % 60,
				$elapsed_time % 60
			);
		} else {
			return sprintf(
				__( '%1$d:%2$02d:%3$02d:%4$02d', 'LION' ),
				$elapsed_time / DAY_IN_SECONDS % 60,
				$elapsed_time / HOUR_IN_SECONDS % 60,
				$elapsed_time / MINUTE_IN_SECONDS % 60,
				$elapsed_time % 60
			);
		}
	} /* calculate_elapsed_time() */

	/**
	 * Parse search engine referrals for original search query.
	 *
	 * @link   http://www.electrictoolbox.com/php-keywords-search-engine-referer-url-2/
	 *
	 * @since  1.0.0
	 *
	 * @param  string      $url URL to parse.
	 * @return string|bool      Original search query if available, otherwise false.
	 */
	private function get_users_search_query( $url = false ) {

		// If no URL is specified, bail here
		if( ! $url )
			return false;

		// Parse URL and look for standard query strings
		$parts_url = parse_url( $url );
		$query = isset( $parts_url['query'] )
			? $parts_url['query']
			: ( isset( $parts_url['fragment'] )
				? $parts_url['fragment']
				: ''
			);

		// If no query was found, bail here
		if( ! $query )
			return false;

		// Parse the query and return the user's search string
		parse_str( $query, $parts_query );
		return isset( $parts_query['q'] ) ? $parts_query['q'] : ( isset( $parts_query['p'] ) ? $parts_query['p'] : '' );

	} /* get_users_search_query() */

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
