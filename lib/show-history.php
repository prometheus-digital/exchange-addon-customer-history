<?php
/**
 * History Output
 *
 * @package iThemes Exchange Customer History
 * @subpackage Output
 * @author rzen Media, LLC
 * @license http://www.gnu.org/licenses/gpl.txt GPL2
 * @link https://rzen.net
 */

/**
 * Render browsing history.
 *
 * @since  1.0.0
 *
 * @param  integer $transaction_id Transaction post ID.
 * @return string                  HTML Markup for browsing history table.
 */
function it_exchange_customer_history_get_browsing_history( $transaction_id = 0 ) {

	// Attempt to retrieve post ID from query arg
	if ( ! $transaction_id )
		$transaction_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

	// If no post ID is available, bail here
	if ( ! $transaction_id )
		return false;

	// Initialize output
	wp_enqueue_style( 'exchange-customer-history-admin' );
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
		$search_history = it_exchange_customer_history_get_search_query( $referrer['url'] );
		if ( $search_history )
			$output .= '<p>' . sprintf( __( 'Original search query: %s', 'LION' ), '<strong><mark>' . $search_history . '</mark></strong>' ) . '</p>';

		// Output ordered list of browsing history
		$output .= '<table class="history-table" cellpadding="0" cellspacing="0" border="0">';
		$output .= '<tr>';
		$output .= '<th>' . __( 'URL', 'LION' ) . '</th>';
		$output .= '<th>' . __( 'Timestamp', 'LION' ) . '</th>';
		$output .= '<th class="align-right">' . __( 'Time on Page', 'LION' ) . '<span class="tip" title="' . __( 'Time elapsed between this page and the next.', 'LION' ) . '">i</span></th>';
		$output .= '<th class="align-right">' . __( 'Total', 'LION' ) . '<span class="tip" title="' . __( 'Time elapsed since first arriving on site.', 'LION' ) . '">i</span></th>';
		$output .= '</tr>';
		$count = 1;
		foreach ( $user_history as $key => $history ) {

			// Don't output the very last item.
			// This is always the internal /transaction/ page.
			if ( end( $user_history ) == $history )
				continue;

			$output .= '<tr>';
			$output .= '<td class="url">' . $count++ . '. <a href="' . esc_url( $history['url'] ) . '" target="_blank">' . esc_url( $history['url'] ) . '</a></td>';
			$output .= '<td>' . date( 'Y/m/d \&\n\d\a\s\h\; h:i:sa', ( $history['time'] + get_option( 'gmt_offset' ) * 3600 ) ) . '</td>';
			$next = isset( $user_history[ $key + 1 ] ) ? $user_history[ $key + 1 ] : end( $user_history );
			$output .= '<td class="align-right">' . it_exchange_customer_history_calculate_elapsed_time( $history['time'], $next['time'] ) . '</td>';
			$output .= '<td class="align-right">' . it_exchange_customer_history_calculate_elapsed_time( $referrer['time'], $next['time'] ) . '</td>';
			$output .= '</tr>';
		}
		$output .= '</table>';

		// Output total elapsed time
		$output .= '<p>';
		$final_entry = end( $user_history );
		$output .= sprintf( __( '<strong>Total Time Elapsed:</strong> %s', 'LION' ), it_exchange_customer_history_calculate_elapsed_time( $referrer['time'], $final_entry['time'] ) );
		$output .= '</p>';

	// Otherwise, inform that no history was collected
	} else {
		$output .= '<p><em>' . __( 'No page history collected.', 'LION' ) . '</em></p>';
	}

	// Close out the container
	$output .= '</div>';

	// Echo our output
	return $output;
} /* it_exchange_customer_history_get_browsing_history() */

/**
 * Render purchase history.
 *
 * @since  1.0.0
 *
 * @param  integer $transaction_id Transaction post ID.
 * @return string                  HTML Markup for purchase history table.
 */
function it_exchange_customer_history_get_purchase_history( $transaction_id = 0 ) {

	// Attempt to retrieve post ID from query arg
	if ( ! $transaction_id )
		$transaction_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

	// If no post ID is available, bail here
	if ( ! $transaction_id )
		return false;

	// Setup important variables
	$user_id        = it_exchange_get_transaction_customer_id( $transaction_id );
	$transactions   = array_reverse( it_exchange_get_customer_transactions( $user_id ) );
	$lifetime_total = 0;
	$count          = 1;

	// Initialize output
	wp_enqueue_style( 'exchange-customer-history-admin' );
	$output = '';
	$output .= '<div class="products-header spacing-wrapper clearfix"></div>';
	$output .= '<div class="spacing-wrapper clearfix">';

	// Include a header
	$output .= sprintf( '<h2>%s</h2>', __( 'Customer Purchase History', 'LION' ) );
	$output .= sprintf( '<p>%s</p>', __( 'Below is every purchase this customer has made in your shop.', 'LION' ) );

	// Output purhcase history table
	$output .= '<table class="history-table" cellpadding="0" cellspacing="0" border="0">';
	$output .= '<tr>';
	$output .= '<th>' . __( 'Order Number', 'LION' ) . '</th>';
	$output .= '<th>' . __( 'Order Date', 'LION' ) . '</th>';
	$output .= '<th class="alignt-right">' . __( 'Order Total', 'LION' ) . '</th>';
	$output .= '</tr>';
	if ( ! empty( $transactions ) ) {
		foreach ($transactions as $key => $purchase ) {
			$alt = $key % 2 ? ' class="alt"' : '';
			$current = $purchase->ID == $transaction_id ? ' class="current"' : $alt;
			$output .= '<tr' . $current . '>';
			$output .= '<td>' . $count++ . '. <a href="' . admin_url( "post.php?post={$purchase->ID}&action=edit" ) . '">' . it_exchange_get_transaction_order_number( $purchase ) . '</a></td>';
			$output .= '<td>' . it_exchange_get_transaction_date( $purchase ) . '</td>';
			$output .= '<td class="align-right">' . it_exchange_get_transaction_total( $purchase ) . '</td>';
			$output .= '</tr>';
			$lifetime_total += absint( it_exchange_get_transaction_total( $purchase, false ) );
		}
	}
	$output .= '</table>';

	// Output total lifetime value
	$output .= '<p>';
	$output .= sprintf( __( '<strong>Actual Lifetime Customer Value:</strong> %s', 'LION' ), '<span class="customer-total">' . it_exchange_format_price( $lifetime_total ) . '</span>' );
	$output .= '</p>';

	// Close out the container
	$output .= '</div>';

	return $output;
} /* it_exchange_customer_history_get_purchase_history() */

/**
 * Output browsing history on payment details page.
 *
 * @since  1.0.0
 */
function it_exchange_customer_history_output_browsing_history() {
	echo it_exchange_customer_history_get_browsing_history();
}
add_action( 'it_exchange_after_payment_details', 'it_exchange_customer_history_output_browsing_history' );

/**
 * Output purchase history on payment details page.
 *
 * @since  1.0.0
 */
function it_exchange_customer_history_output_purchase_history() {
	echo it_exchange_customer_history_get_purchase_history();
}
add_action( 'it_exchange_after_payment_details', 'it_exchange_customer_history_output_purchase_history' );

/**
 * Output browsing and purchase history in admin notification emails.
 *
 * @since  1.0.0
 *
 * @param  string $output      Email body.
 * @param  object $transaction Transaction post object.
 * @return string              Updated email body.
 */
function it_exchange_customer_history_admin_email( $output, $transaction ) {

	// Append user history to the email body
	$output .= it_exchange_customer_history_get_browsing_history( $transaction->ID );
	$output .= it_exchange_customer_history_get_purchase_history( $transaction->ID );

	// Return the output
	return $output;

} /* it_exchange_customer_history_admin_email() */
add_filter( 'send_admin_emails_body', 'it_exchange_customer_history_admin_email', 10, 2 );
