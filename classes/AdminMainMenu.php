<?php
/**
 * Setup::AdminMainMenu
 *
 * This class creates the main WP Rank menu if it has not been created before.
 *
 * @package EasyAltEdit
 */
declare(strict_types=1);

namespace WpRank\EasyAlt;

defined( 'ABSPATH' ) || die();

/**
 * Admin
 */
class AdminMainMenu {

    /**
     * The slug for the main menu
     *
     * @var string
     */
    public static $home = 'wp-rank';

    /**
     * The hook suffix for the main menu
     *
     * @var string
     */
    public static $hook_suffix = '';

    /**
     * The position for the menu (after Tools 75)
     *
     * @var int`
     */
    private static $menu_position = 77;

    /**
     * Constructor
     */
    private function __construct()
    {
    }

    /**
     * Creates main WP Rank menu
     *
     * Menu icon: @see https://developer.wordpress.org/resource/dashicons/
     */
    public static function create_main_menu() {

        if ( ! self::main_menu_exists() ) {

            self::$hook_suffix = add_menu_page(
                _x( 'WP Rank', 'Admin — Page title', 'eae' ),
                _x( 'WP Rank', 'Admin — Menu name', 'eae' ),
                'manage_options',
                self::$home,
                array( __CLASS__, 'display_main_page' ),
                'dashicons-performance',
                self::$menu_position
            );
        }
    }

    /**
     * Common main menu page
     */
    public static function display_main_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html_x( 'WP Rank', 'Admin — Page title', 'eae' ) . '<h1>';
        echo '<p>Ici mettre une présentation de la gamme de produits de WP Rank<p>';
        echo '<p>Faire une vue ?<p>';
        echo '</div>';
    }

    /**
     * Check if WP Rank main menu exists
     */
    private static function main_menu_exists(): bool {
        foreach( $GLOBALS['menu'] as $entry ) {
            if ( $entry[2] == self::$home ) {
                return true;
            }
        }

        return false;
    }
}
