<?php
/**
 * Plugin Name: iThemes Exchange - Customer History Add-on
 * Plugin URI: http://ithemes.com/exchange/
 * Description: Track and store customer browsing history with their completed paments. There are no settings for this add-on.
 * Version: 1.0.0
 * Author: Brian Richards
 * Author URI: http://rzen.net
 * Text Domain: LION
 * Domain Path: /languages
 * iThemes Package: exchange-addon-customer-history
 *
 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
*/

/**
 * Main plugin instantiation class.
 *
 * @since 1.0.0
 */
class Exchange_Customer_History_Init {

	/**
	 * Fire up the engines.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Connect our pieces where they belong
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'plugins_loaded', array( $this, 'i18n' ) );
		add_action( 'it_exchange_register_addons', array( $this, 'register_addon' ) );

	} /* __construct() */

	function i18n() {
		load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Register this add-on within Exchange.
	 *
	 * @since  1.0.0
	 */
	function register_addon() {
		$options = array(
			'name'              => __( 'Customer History', 'LION' ),
			'description'       => sprintf( __( 'Track and store customer browsing history with their %s. There are no settings for this add-on.', 'LION' ), '<a href="' . admin_url( 'edit.php?post_type=it_exchange_tran' ) . '">' . __( 'completed paments', 'LION' ) . '</a>' ),
			'author'            => 'Brian Richards',
			'author_url'        => 'http://rzen.net/',
			'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/customer-history-icon.png' ),
			'file'              => dirname( __FILE__ ) . '/lib/track-history.php',
			'category'          => 'admin',
			'supports'          => null,
			'settings-callback' => null,
		);
		it_exchange_register_addon( 'customer_history', $options );
	} /* register_addon() */

	/**
	 * Check if all requirements are met.
	 *
	 * @since  1.0.0
	 *
	 * @return bool True if requirements are met, otherwise false.
	 */
	private function meets_requirements() {

		if ( class_exists('IT_Exchange') && version_compare( $GLOBALS['it_exchange']['version'], '1.7.1', '>=') )
			return true;
		else
			return false;

	} /* meets_requirements() */

	/**
	 * Output error message and disable plugin if requirements are not met.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'iThemes Exchange Customer History requires iThemes Exchange 1.7.1 or greater and has been <a href="%s">deactivated</a>. Please install, activate or update iThemes Exchange and then reactivate this plugin.', 'LION' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( plugin_basename( __FILE__ ) );
		}

	} /* maybe_disable_plugin() */
}
$Exchange_Customer_History_Init = new Exchange_Customer_History_Init;

function ithemes_repository_exchange_addon_customer_history_updater_register( $updater ) { 
    $updater->register( 'exchange-addon-customer-history', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_repository_exchange_addon_customer_history_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );