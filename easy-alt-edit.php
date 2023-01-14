<?php
/**
 * Plugin Name: Easy Alt Edit
 * Version: 1.1.0
 * Description: This extension allows you to easily and quickly manage alternate titles of your images directly from the media list.
 * Plugin URI: https://www.wprank.net
 * Text Domain: eae
 * Domain Path: /langs
 * Author: CreaNico / WP Rank
 * Author URI: https://www.creanico.fr
 *
 * @version 1.1.0
 * @package Easy Alt Text Edit
 */

defined( 'ABSPATH' ) || die( 'Cheating?' );

define( 'EAE_VERSION', '1.1.0' );
define( 'EAE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPRANK_API_URL', 'https://www.wprank.net/' );

if ( ! class_exists( 'WC_AM_Client_2_7K2' ) ) {
	require_once EAE_PLUGIN_PATH . 'wc-am-client.php';
}

if ( class_exists( 'WC_AM_Client_2_7K2' ) ) {
	$wcam_lib = new WC_AM_Client_2_7K2( __FILE__, '', EAE_VERSION, 'plugin', WPRANK_API_URL, 'Easy Alt Edit' );
}

if ( ! function_exists( 'eae_load_textdomain' ) ) {
	/**
	 * Translation.
	 *
	 * @since 1.0.0
	 */
	function eae_load_textdomain() {
		load_plugin_textdomain( 'eae', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
	}
}
add_action( 'plugins_loaded', 'eae_load_textdomain' );

require_once EAE_PLUGIN_PATH . 'admin/class-eae-plugin.php';
require_once EAE_PLUGIN_PATH . 'admin/AdminMainMenu.php';
require_once EAE_PLUGIN_PATH . 'admin/AdminSubMenu.php';
$admin_menu = new WpRank\EasyAlt\AdminSubMenu();
$admin_menu->run();