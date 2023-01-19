<?php
/**
 * Easy ALT Edit - Plugin
 *
 * @package   EasyAltEdit
 * @link      https://www.wprank.net/
 */

defined( 'ABSPATH' ) || die( 'Cheating?' );

if ( ! class_exists( 'EAE_Plugin' ) ) {
	/**
	 * Core plugin
	 */
	class EAE_Plugin {

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_filter( 'manage_media_columns', array( $this, 'eae_media_columns' ) );
			add_filter( 'manage_media_custom_column', array( $this, 'eae_media_custom_column_alt' ) );

			add_action( 'admin_footer', array( $this, 'eae_ajax_request' ) );
			add_action( 'wp_ajax_eae', array( $this, 'eae_ajax_process' ) );

			if ( $this->force_alts() ) {
				add_filter( 'the_content', array( $this, 'eae_filter_the_content' ), 1 );
			}
		}

		/**
		 * Alt Check Force alts option.
		 *
		 * @since 1.0.0
		 */
		public function force_alts() {
			return (bool) get_option( 'eae_options' )['force_alts'];
		}

		/**
		 * Alt column.
		 *
		 * @param string[] $columns An array of columns displayed in the Media list table.
		 *
		 * @since 1.0.0
		 */
		public function eae_media_columns( $columns ) {
			$columns['eae-column'] = __( 'Alt Text', 'eae' );

			$columns = array(
				'cb'         => $columns['cb'],
				'title'      => $columns['cb'],
				'eae-column' => $columns['eae-column'],
				'date'       => $columns['date'],
				'author'     => $columns['author'],
				'parent'     => $columns['parent'],
				'comments'   => $columns['comments'],
			);

			return $columns;
		}

		/**
		 * Alt text field.
		 *
		 * @param string $column_name Name of the custom column.
		 *
		 * @since 1.0.0
		 */
		public function eae_media_custom_column_alt( $column_name ) {
			if ( 'eae-column' === $column_name ) {
				global $post;

				if ( false !== strpos( $post->post_mime_type, 'image' ) ) {

					$safe_post_ID = (int) $post->ID;

					$alt   = get_post_meta( $safe_post_ID, '_wp_attachment_image_alt', true );
					$nonce = wp_create_nonce( 'eae-update' );
					?>
					<input
						type="hidden"
						name="eae-id"
						value="<?php esc_attr_e( $safe_post_ID ); ?>"
					/>
					<input
						type="hidden"
						name="eae-nonce"
						value="<?php esc_attr_e( $nonce ); ?>"
					/>
					<input
						type="text"
						class="eae-field"
						name="<?php esc_attr_e( 'eae-' . $safe_post_ID ); ?>"
						value="<?php esc_attr_e( $alt ); ?>"
					/>
					<button type="button" class="button eae-button" data-media-title="<?php esc_attr_e( $post->post_title ); ?>">
						<?php esc_html_e( 'Use image title', 'eae' ); ?>
					</button>
					<?php
				}
			}
		}

		/**
		 * AJAX request.
		 *
		 * @since 1.0.0
		 */
		public function eae_ajax_request() {
			?>
			<script>
			jQuery(function($){
				function eae_update(field) {
					var eae_nonce = field.prev().val()
					var eae_id = field.prev().prev().val()
					var eae_value = field.val()

					$.ajax({
						type: 'POST',
						data: {
							action: 'eae',
							eae_nonce,
							eae_id,
							eae_value
						},
						beforeSend: function( xhr ) {
							field.attr('disabled', true)
						},
						url: ajaxurl,
						success: function(data){
							field.attr('disabled', false)
						}
					});
				}

				$('.eae-button').on('click', function() {
					var title = $(this).attr('data-media-title');
					var field = $(this).prev();

					field.val(title);
					eae_update(field);
				})

				$('.eae-field').on('keydown', function(event) {
					if(event.keyCode === 13) {
						$(this).blur();
						return false;
					}
				}).on('blur', function(){
					eae_update($(this));
					return false;
				});
			});
			</script>
			<?php
		}

		/**
		 * AJAX process.
		 *
		 * @since 1.0.0
		 */
		public function eae_ajax_process() {
			check_ajax_referer( 'eae-update', 'eae_nonce' );

			if ( ! isset( $_POST['eae_nonce'] ) && ! wp_verify_nonce( $_POST['eae_nonce'], 'eae-update' ) ) {
				echo 'No.';
				die();
			}

			if ( ! isset( $_POST['eae_id'] ) || ! isset( $_POST['eae_value'] ) ) {
				echo 'No again.';
				die();
			}

			$safe_id    = intval( $_POST['eae_id'] );
			$safe_value = sanitize_text_field( $_POST['eae_value'] );

			if ( update_post_meta( $safe_id, '_wp_attachment_image_alt', $safe_value ) ) {
				echo 'Saved';
			}

			die();
		}

		/**
		 * Filter the content.
		 *
		 * @param string $content page content.
		 *
		 * @since 1.0.0
		 */
		public function eae_filter_the_content( $content ) {
			$regex = '#<img[^>]* src=(?:\"|\')(?<src>([^"]*))(?:\"|\')[^>]*>#mU';
			preg_match_all( $regex, $content, $matches );

			$matches_tag = $matches[0];
			$matches_src = $matches['src'];

			foreach ( $matches_src as $key => $src ) {
				$match_tag = $matches_tag[ $key ];
				$post_id   = attachment_url_to_postid( $src );

				if ( ! $post_id ) {
					$dir  = wp_upload_dir();
					$path = $src;

					if ( 0 === strpos( $path, $dir['baseurl'] . '/' ) ) {
						$path = substr( $path, strlen( $dir['baseurl'] . '/' ) );
					}

					if ( preg_match( '/^(.*)(\-\d*x\d*)(\.\w{1,})/i', $path, $matches ) ) {
						$src     = $dir['baseurl'] . '/' . $matches[1] . $matches[3];
						$post_id = attachment_url_to_postid( $src );
					}
				}

				$alt = get_post_meta( $post_id, '_wp_attachment_image_alt', true );

				if ( ! empty( $alt ) ) {
					$image_alt   = 'alt="' . esc_attr( $alt ) . '"';
					$replace_tag = preg_replace( '/alt="([^"]*)"/', $image_alt, $match_tag );

					if ( $match_tag !== $replace_tag ) {
						$content = str_replace( $match_tag, $replace_tag, $content );
					}
				}
			}

			return $content;
		}
	}
}

new EAE_Plugin();
