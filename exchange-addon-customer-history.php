<?php
/**
 * Plugin Name: ExchangeWP - Customer History Add-on
 * Plugin URI: https://exchangewp.com/downloads/customer-history/
 * Description: Track and store customer browsing history with their completed payments. There are no settings for this add-on.
 * Version: 1.0.9
 * Author: ExchangeWP
 * Author URI: https://exchangewp.com
 * License: GPL2
 * Text Domain: LION
 * Domain Path: /lang
 * ExchangeWP Package: exchange-addon-customer-history
 *
 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
*/

/*
Copyright 2013 rzen Media, LLC (email : brian@rzen.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
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

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url( __FILE__ );

		// Connect our pieces where they belong
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'plugins_loaded', array( $this, 'i18n' ) );
		add_action( 'it_exchange_register_addons', array( $this, 'register_addon' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_and_styles' ) );

	} /* __construct() */

	/**
	 * Load localization.
	 *
	 * @since  1.0.0
	 */
	function i18n() {
		load_plugin_textdomain( 'LION', false, $this->directory_path . '/lang/' );
	} /* i18n() */

	/**
	 * Register this add-on within Exchange.
	 *
	 * @since  1.0.0
	 */
	function register_addon() {
		$options = array(
			'name'              => __( 'Customer History', 'LION' ),
			'description'       => sprintf( __( 'Track and store customer browsing history with their %s.', 'LION' ), '<a href="' . admin_url( 'edit.php?post_type=it_exchange_tran' ) . '">' . __( 'completed payments', 'LION' ) . '</a>' ),
			'author'            => 'ExchangeWP',
			'author_url'        => 'https://exchangewp.com/downloads/customer-history/',
			'icon'              => $this->directory_url . '/lib/images/customer-history-icon.png',
			'file'              => $this->directory_path . '/lib/init.php',
			'category'          => 'admin',
			'supports'          => null,
		);
		it_exchange_register_addon( 'customer_history', $options );
	} /* register_addon() */

	/**
	 * Register scritps and styles for this plugin.
	 *
	 * @since  1.0.0
	 */
	function register_scripts_and_styles() {
		wp_register_style( 'exchange-customer-history-admin', $this->directory_url . '/lib/admin.css' );
	} /* register_scripts_and_styles() */

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
			echo '<p>' . sprintf( __( 'ExchangeWP Customer History requires iThemes Exchange 1.7.1 or greater and has been <a href="%s">deactivated</a>. Please install, activate or update iThemes Exchange and then reactivate this plugin.', 'LION' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}

	} /* maybe_disable_plugin() */
}
$Exchange_Customer_History_Init = new Exchange_Customer_History_Init;

/**
 * ExchangeWP updater.
 *
 * @since  1.0.0
 *
 * @param  object $updater iThemes Updater object.
 */
 function exchange_customer_history_plugin_updater() {

 	$license_check = get_transient( 'exchangewp_license_check' );

 	if ($license_check->license == 'valid' ) {
 		$license_key = it_exchange_get_option( 'exchangewp_licenses' );
 		$license = $license_key['exchange_license'];

 		$edd_updater = new EDD_SL_Plugin_Updater( 'https://exchangewp.com', __FILE__, array(
 				'version' 		=> '1.0.9', 				// current version number
 				'license' 		=> $license, 				// license key (used get_option above to retrieve from DB)
 				'item_id' 		=> 382,					 	  // name of this plugin
 				'author' 	  	=> 'ExchangeWP',    // author of this plugin
 				'url'       	=> home_url(),
 				'wp_override' => true,
 				'beta'		  	=> false
 			)
 		);
 	}

 }

 add_action( 'admin_init', 'exchange_customer_history_plugin_updater', 0 );
