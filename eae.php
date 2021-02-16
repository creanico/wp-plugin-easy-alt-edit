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

if ( ! function_exists( 'eae_load_textdomain' ) ) {
	/**
	 * Translation.
	 */
	function eae_load_textdomain() {
		load_plugin_textdomain( 'eae', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
	}
}

add_action( 'plugins_loaded', 'eae_load_textdomain' );


if ( ! function_exists( 'eae_media_columns' ) ) {
	/**
	 * Alt column.
	 *
	 * @param string[] $columns An array of columns displayed in the Media list table.
	 */
	function eae_media_columns( $columns ) {
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
}

add_filter( 'manage_media_columns', 'eae_media_columns' );


if ( ! function_exists( 'eae_media_custom_column_alt' ) ) {
	/**
	 * Alt text field.
	 *
	 * @param string $column_name Name of the custom column.
	 */
	function eae_media_custom_column_alt( $column_name ) {
		if ( 'eae-column' === $column_name ) {
			global $post;

			if ( false !== strpos( $post->post_mime_type, 'image' ) ) {
				$alt   = get_post_meta( $post->ID, '_wp_attachment_image_alt', true );
				$nonce = wp_create_nonce( 'eae-update' );
				?>
				<input
					type="hidden"
					name="eae-id"
					value="<?php echo esc_attr( $post->ID ); ?>"
				/>
				<input
					type="hidden"
					name="eae-nonce"
					value="<?php echo esc_attr( $nonce ); ?>"
				/>
				<input
					type="text"
					class="eae-field"
					name="<?php echo esc_attr( 'eae-' . $post->ID ); ?>"
					value="<?php echo esc_attr( $alt ); ?>"
				/>
				<button type="button" class="button eae-button" data-media-title="<?php echo esc_attr( $post->post_title ); ?>">
					<?php esc_html_e( 'Use image title', 'eae' ); ?>
				</button>
				<?php
			}
		}
	}
}

add_filter( 'manage_media_custom_column', 'eae_media_custom_column_alt' );


if ( ! function_exists( 'eae_ajax_request' ) ) {
	/**
	 * AJAX request.
	 */
	function eae_ajax_request() {
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
}

add_action( 'admin_footer', 'eae_ajax_request' );


if ( ! function_exists( 'eae_ajax_process' ) ) {
	/**
	 * AJAX process.
	 */
	function eae_ajax_process() {
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

		if ( update_post_meta( $safe_id, '_wp_attachment_image_alt', wp_unslash( $safe_value ) ) ) {
			echo 'Saved';
		}

		die();
	}
}

add_action( 'wp_ajax_eae', 'eae_ajax_process' );
