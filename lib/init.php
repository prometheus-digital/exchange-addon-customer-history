<?php
/**
 * Plugin Includes
 *
 * @package iThemes Exchange Customer History
 * @subpackage Utility
 * @author rzen Media, LLC
 * @license http://www.gnu.org/licenses/gpl.txt GPL2
 * @link https://rzen.net
 */

/**
 * Include all other plugin files
 *
 * @since  1.0.0
 */
require_once( dirname(__FILE__) . '/utility.php' );
require_once( dirname(__FILE__) . '/track-history.php' );
require_once( dirname(__FILE__) . '/show-history.php' );

/**
 * Exchange will build your add-on's settings page for you and link to it from our add-on
 * screen. You are free to link from it elsewhere as well if you'd like... or to not use our API
 * at all. This file has all the functions related to registering the page, printing the form, and saving
 * the options. This includes the wizard settings. Additionally, we use the Exchange storage API to
 * save / retreive options. Add-ons are not required to do this.
*/
include( dirname(__FILE__) . '/addon-settings.php' );
