<?php
/**
 * Plugin Name: Easy Alt Edit
 * Version: 1.0.0
 * Description: This extension allows you to easily and quickly manage alternate titles of your images directly from the media list.
 * Plugin URI: https://www.wprank.net
 * Text Domain: eae
 * Domain Path: /langs
 * Author: CreaNico / WP Rank
 * Author URI: https://www.creanico.fr
 *
 * @version 1.0.0
 * @package Easy Alt Text Edit
 */

defined( 'ABSPATH' ) || die( 'Cheating?' );

define( 'EAE_URL', plugin_dir_url( __FILE__ ) );
define( 'EAE_DIR', plugin_dir_path( __FILE__ ) );

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

require_once EAE_DIR . 'classes/class-eae-options.php';
require_once EAE_DIR . 'classes/class-eae-plugin.php';
