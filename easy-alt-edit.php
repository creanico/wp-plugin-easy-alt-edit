<?php
/**
 * Plugin Name: Easy ALT Edit
 * Version: 1.1.3
 * Description: This extension allows you to easily and quickly manage alternate titles of your images directly from the media list.
 * Plugin URI: https://www.wprank.net
 * Text Domain: eae
 * Domain Path: /langs
 * Author: CreaNico / WP Rank
 * Author URI: https://www.creanico.fr
 *
 * @version 1.1.3
 */

defined( 'ABSPATH' ) || die( 'Cheating?' );

define( 'EAE_VERSION', '1.1.3' );
define( 'EAE_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
if ( ! defined( 'WPRANK_API_URL' ) ) {
	define( 'WPRANK_API_URL', 'https://www.wprank.net/' );
}

if ( ! class_exists( 'WC_AM_Client_2_7K2' ) ) {
	require_once EAE_PLUGIN_PATH . 'wc-am-client.php';
}

if ( class_exists( 'WC_AM_Client_2_7K2' ) ) {
	$wcam_lib = new WC_AM_Client_2_7K2( __FILE__, '', EAE_VERSION, 'plugin', WPRANK_API_URL, 'Easy ALT Edit' );
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

// Nécessaire pour que la fermeture de la notice dure x jours, sur la session ou indéfiniment
require __DIR__ . '/deps/persist-admin-notices-dismissal/persist-admin-notices-dismissal.php';

add_action( 'admin_init', array( 'PAnD', 'init' ) );
add_action( 'admin_notices', 'abw_expiration_prochaine_eae' );

function abw_expiration_prochaine_eae() {
	$dismissible = 'notice-expiration-session'; // nom-duree (nom-1 : 1 jour, nom-forever : indéfiniment, nom-session : session)
	if ( ! PAnD::is_admin_notice_active( $dismissible ) ) {
		return;
	}
	// Récupération de la date d'expiration sur wprank ou en transient
	$access_expires = get_transient( 'access_expires_eae' );
	if( empty($access_expires) ): // Pas de transient, on récupère les infos sur wprank par WC API Manager
		global $wcam_lib;
		$plugin_status_info = array();
  		$plugin_status_info = $wcam_lib->license_key_status(); // Seule problématique : si le client à plusieurs commande de la même variation, on ne peut déterminer quelle est la date à retenir pour l'activation du site s'il utilise la clé de licence utilisateur et non la clé de licence produit/commande.
		$access_expires = abw_recuperation_date_expiration($plugin_status_info);
		set_transient( 'access_expires_eae', $access_expires, 3600*24 ); // On stock en transient pour 24 heures
	endif;
	if( !empty($access_expires) ) $strtotime_access_expires = strtotime($access_expires);
	if( !empty($access_expires) && checkdate( date('m', $strtotime_access_expires), date('d', $strtotime_access_expires), date('Y', $strtotime_access_expires) ) && $strtotime_access_expires>time() && $strtotime_access_expires<(time()+15*24*3600) ): // Définir ici le nombre de jour pour déclencher la fenêtre d'information
		$class = 'notice notice-warning is-dismissible';
		$titre = __( "Your Easy ALT Edit license expires in", 'eae' )." ";
		$message = __( "After expiration, you will no longer have access to updates and support.", 'eae' );
		$bouton = sprintf( __( "Extend your license with 40%% off before the %s!", 'eae' ), date('d/m/Y', $strtotime_access_expires) );
		$script = '<script>var countDownDate = new Date("'.$access_expires.'").getTime(); var x = setInterval(function() { var maintenant = new Date(); var now = maintenant.getTime(); var distance = countDownDate - now +(maintenant.getTimezoneOffset()*60*1000); var days = Math.floor(distance / (1000 * 60 * 60 * 24)); var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)); var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60)); var seconds = Math.floor((distance % (1000 * 60)) / 1000); document.getElementById("abw_countdown_monetico").innerHTML = days + " jour"+(days>1?"s":"")+ " " + (hours>0?("0" + hours).slice(-2):"0") + " heure"+(hours>1?"s":"")+ " " + (minutes>0?("0" + minutes).slice(-2):"0") + " minute"+(minutes>1?"s":"")+ " et " + ("0" + seconds).slice(-2) + " seconde"+(seconds>1?"s":""); if (distance < 0) {  clearInterval(x); document.getElementById("abw_countdown_monetico").innerHTML = "EXPIRÉE"; } }, 1000);</script>';

		printf( '<div data-dismissible="%1$s" class="%2$s"><p><strong style="font-size:16px">%3$s<span id="abw_countdown_monetico"></span></strong></p><p>%4$s</p><p><a class="button button-primary" href="https://www.wprank.net/boutique/renouveler-vos-licences/?utm_source=site_client&utm_medium=notice" target="_blank">%5$s</a></p>%6$s</div>', esc_attr( $dismissible ), esc_attr( $class ), esc_html( $titre ), esc_html( $message ), esc_html( $bouton ), $script );
	endif;
}
if(!function_exists('abw_recuperation_date_expiration')):
	function abw_recuperation_date_expiration( $tab ) {

		// Si un client à acheté plusieurs licences d'une même variation sur des dommandes différentes, on ne peut pas déterminer la bonne date à utiliser si il utilise sa clé de licence globale.
		// Si il utilise sa clé de licence produit/commande, pas de problème, une seule date va remonter
		// Si il y a plus d'une date qui remonte, on ne veux pas afficher la notice, on renvoi une date vide
		if ( isset( $tab['data'] ) ) {
			$nb_achats = count( $tab['data']['api_key_expirations']['non_wc_subs_resources'] );
			if ( $nb_achats == 1 ) {
				$time = $tab['data']['api_key_expirations']['non_wc_subs_resources'][0]['friendly_api_key_expiration_date']; // ex. 24 octobre 2023 13h11

				// Conversion de la date textuelle française en date standardisée
				$parsedTime = \IntlDateFormatter::create( 'fr', IntlDateFormatter::FULL, IntlDateFormatter::FULL, null, null, "d MMMM y kk'h'mm" )->parse( $time );
				if ( $parsedTime ) {
					$dateTime = \DateTime::createFromFormat( 'U', $parsedTime );
					$formattedDate = $dateTime->format( 'Y-m-d H:i' );

					return $formattedDate;
				}
			}
		}

		return '';
	}
endif;