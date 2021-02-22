<?php
/**
 * Easy Alt Edit - Options page
 *
 * @package   EasyAltEdit
 * @link      https://www.wprank.net/
 */

defined( 'ABSPATH' ) || die( 'Cheating?' );

if ( ! class_exists( 'EAE_Options' ) ) {
	/**
	 * Admin Options page
	 */
	class EAE_Options {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			if ( is_admin() ) {
				add_action( 'admin_init', array( $this, 'register_settings' ) );
				add_action( 'admin_menu', array( $this, 'options_page' ) );
			}
		}

		/**
		 * Returns all options.
		 *
		 * @since 1.0.0
		 */
		public static function get_options() {
			return get_option( 'eae_options' );
		}

		/**
		 * Returns single option.
		 *
		 * @param string $id Option ID.
		 * @since 1.0.0
		 */
		public static function get_option( $id ) {
			$options = self::get_options();

			if ( isset( $options[ $id ] ) ) {
				return $options[ $id ];
			}
		}

		/**
		 * Register settings.
		 *
		 * @since 1.0.0
		 */
		public function register_settings() {
			register_setting(
				'eae_options',
				'eae_options',
				array( $this, 'sanitize' )
			);
		}

		/**
		 * Add options page.
		 *
		 * @since 1.0.0
		 */
		public function options_page() {
			add_options_page(
				esc_html__( 'Easy Alt Edit', 'eae' ),
				esc_html__( 'Easy Alt Edit', 'eae' ),
				'manage_options',
				'easy-alt-edit',
				array( $this, 'render' )
			);
		}

		/**
		 * Render options page.
		 *
		 * @since 1.0.0
		 */
		public function render() {
			?>
			<div class="wrap">
				<h1>
					<?php esc_html_e( get_admin_page_title() ); ?>
				</h1>
				<form method="post" action="options.php">
					<?php settings_fields( 'eae_options' ); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row">
								<?php esc_html_e( 'force the use of alternative text from an image', 'eae' ); ?>
							</th>
							<td>
								<?php $value = self::get_option( 'force_alts' ); ?>
								<input type="checkbox" name="eae_options[force_alts]" value="1" <?php checked( $value, 1 ); ?>>
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
		 * Sanitization callback.
		 *
		 * @param array $options Options.
		 * @since 1.0.0
		 */
		public function sanitize( $options ) {
			if ( ! is_array( $options ) || empty( $options ) || ( false === $options ) ) {
				return array();
			}

			if ( isset( $options['force_alts'] ) && ( 1 == $options['force_alts'] ) ) {
				$options['force_alts'] = 1;
			} else {
				unset( $options['checkbox_example'] );
			}

			return $options;
		}
	}
}

new EAE_Options();
