<?php
/**
 * Setup::AdminSubMenu
 */
declare(strict_types=1);

namespace WpRank\EasyAlt;

defined( 'ABSPATH' ) || die();

/**
 * Admin
 */
class AdminSubMenu
{

    /**
     * The slug for the main menu
     *
     * @var string
     */
    public static $slug = 'easy-alt-edit';

    /**
     * The slug for the main menu
     *
     * @var string
     */
    public static $home = 'wp-rank';

    /**
     * The default tab
     *
     * @var string
     */
    private static $default_tab;

    /**
     * The main admin page URL
     *
     * @var string
     */
    public static $admin_page;

    /**
     * The navigation tabs
     *
     * @var array
     */
    public static $tabs = array();

    /**
     * The class name for the Licence client
     *
     * @var string
     */
    private static $licence_client = 'WC_AM_Client_2_7K2';

    /**
     * Constructor
     */
    public function __construct()
    {
        add_action( 'admin_init', array( $this, 'setup_settings' ) );
    }

    /**
     * Attach hook for later processing
     */
    public function run()
    {
        add_action( 'admin_menu', array( $this, 'init' ) );
    }


    /**
     * Initialize the admin menu
     */
    public function init()
    {
        // Security check — access restricted to admin users
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Create the main WP Rank menu if it does not exist yet
        AdminMainMenu::create_main_menu();

        $this->setup_tabs();

        // Create submenu
        add_submenu_page(
            self::$home,
            _x( 'Easy ALT Edit', 'Admin — Page title', 'eae' ),
            _x( 'Easy ALT Edit', 'Admin — Menu name', 'eae' ),
            'manage_options',
            self::$home . '-' . self::$slug,
            array( $this, 'settings_page_content' ),
            -1
        );

        // Must be called after menu creation
        self::$admin_page = menu_page_url( self::$home . '-' . self::$slug, false );

        // Remove menu entry created by WooCommerce API Manager PHP Client Library
        if ( class_exists( self::$licence_client ) ) {
            $this->remove_activation_menu();
        }
    }

    /**
     * Setup settings
     *
     * @access public
     */
    public function setup_settings()
    {
        register_setting(
            'eae_options',
            'eae_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize' ),
            )
        );
    }

    /**
     * Sanitization callback.
     */
    public function sanitize( $options )
    {
        $options['force_alts'] = ! empty( $options['force_alts'] );

        return $options;
    }

    /**
     * Settings page content
     */
    public function settings_page_content()
    {
        $active = $_GET['tab'] ?? self::$default_tab;
?>

        <div class="wrap wprank-admin">
            <h1>Easy ALT Edit [<?php esc_html_e( EAE_VERSION ); ?>]</h1>
            <nav class="nav-tab-wrapper">
                <?php $this->display_tab_nav( $active ); ?>
            </nav>
            <?php call_user_func( [ __CLASS__, self::$tabs[ $active ]['callback'] ] ); ?>
        </div>

<?php
    }

    /**
     * Affiche le contenu de l’onglet Settings
     *
     * @todo vérifier s’il y a quelquechose à y mettre
     */
    private function display_tab_settings()
    {
        $options = get_option( 'eae_options' );
        if ( isset( $options['force_alts'] ) ) {
            $checked = (bool)get_option( 'eae_options' )['force_alts'];
        } else {
            $checked = false;
        }
?>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php settings_fields( 'eae_options' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">
                            <?php esc_html_e( 'Force the use of alternative text from an image', 'eae' ); ?>
                        </th>
                        <td>
                            <input type="checkbox" name="eae_options[force_alts]" value="1" <?php checked( $checked, 1 ); ?>>
                            <?php esc_html_e( 'If this option is checked, the alternative text entered from the "Media" tab will take priority over the alternative text entered from an article, a page ...', 'eae' ); ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
<?php
    }

    /**
     * Affiche le contenu de l’onglet Documentation
     */
    private function display_tab_documentation()
    {
        $href = 'https://www.wprank.net/guides/doc-extension-easy-alt-edit-pour-wordpress/';

        require AdminMainMenu::$views_dir . 'admin-tab-documentation.inc';
    }

    /**
     * Affiche le contenu de l’onglet activation
     *
     * Pour le test : 6214b05ac2c6c0ff4712dde8e00626b815e309d0
     */
    private function display_tab_activation()
    {
        // The WC_AM_Client constructor needs the plugin path as an argument
        $plugin = EAE_PLUGIN_PATH . 'easy-alt-edit.php';

        // Instantiante WC_AM_Client
        $class_name = '\\' . self::$licence_client;
        $client = new $class_name( $plugin, '', EAE_VERSION, 'plugin', WPRANK_API_URL, 'Easy ALT Edit' );

        echo '<form action="options.php" method="post">';
        settings_fields( $client->data_key );
        do_settings_sections( $client->wc_am_activation_tab_key );
        submit_button( esc_html__( 'Save Changes', 'eae' ) );
        echo '</form>';
    }

    /**
     * Affiche le contenu de l’onglet désactivation
     */
    private function display_tab_deactivation()
    {
        // The WC_AM_Client constructor needs the plugin path as an argument
        $plugin = EAE_PLUGIN_PATH . 'easy-alt-edit.php';

        // Instantiante WC_AM_Client
        $class_name = '\\' . self::$licence_client;
        $client = new $class_name( $plugin, '', EAE_VERSION, 'plugin', WPRANK_API_URL, 'Easy ALT Edit' );

        echo '<form action="options.php" method="post">';
        settings_fields( $client->wc_am_deactivate_checkbox_key );
        do_settings_sections( $client->wc_am_deactivation_tab_key );
        submit_button( esc_html__( 'Save Changes', 'eae' ) );
        echo '</form>';
    }

    /**
     * Define tabs
     */
    private function setup_tabs()
    {
        self::$tabs['settings']      = [ 'name' => _x( 'Settings', 'Admin — tab name', 'eae'),             'callback' => 'display_tab_settings' ];
        self::$tabs['documentation'] = [ 'name' => _x( 'Documentation', 'Admin — tab name', 'eae'),        'callback' => 'display_tab_documentation' ];
        self::$tabs['activation']    = [ 'name' => _x( 'API Key Activation', 'Admin — tab name', 'eae'),   'callback' => 'display_tab_activation' ];
        self::$tabs['deactivation']  = [ 'name' => _x( 'API Key Deactivation', 'Admin — tab name', 'eae'), 'callback' => 'display_tab_deactivation' ];

        self::$default_tab = array_keys( self::$tabs )[0];
    }

    /**
     * Affiche la navigation par onglets
     */
    private function display_tab_nav( string $active )
    {
        $url    = self::$admin_page;
        $active = $_GET['tab'] ?? self::$default_tab;
        foreach ( self::$tabs as $code => $tab ) {

            // Ne pas afficher les onglets sans nom
            if ( false !== $tab['name'] ) {
                $class_active = ( $code == $active ) ? ' nav-tab-active' : '';
                printf( '<a href="%s&amp;tab=%s" class="nav-tab%s">%s</a>', $url, $code, $class_active, $tab['name'] );
            }
        }
    }

    /**
     * Remove the Activation submenu from Settings
     */
    private function remove_activation_menu()
    {
        remove_submenu_page( 'options-general.php', 'wc_am_client_easy_alt_edit_dashboard' );
    }
}
