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

/**
 * Translations
 */
function eae_load_textdomain() {
	load_plugin_textdomain( 'eae', false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
}
add_action( 'plugins_loaded', 'eae_load_textdomain' );

/**
 * Alt text column
 */
function eae_media_columns( $columns ) {
	$columns['eae-column'] = __( 'Alt Text', 'eae' );

	return $columns;
}
add_filter( 'manage_media_columns', 'eae_media_columns' );

/**
 * Columns order
 */
function eae_media_columns_order( $columns ) {
	$columns = array(
		'cb'          => $columns['cb'],
		'title'       => $columns['cb'],
		'eae-column' => $columns['eae-column'],
		'date'        => $columns['date'],
		'author'      => $columns['author'],
		'parent'      => $columns['parent'],
		'comments'    => $columns['comments'],
	);

	return $columns;
}
add_filter( 'manage_media_columns', 'eae_media_columns_order' );

/**
 * Alt text field
 */
function eae_media_custom_column_alt( $column_name ) {
	if ( 'eae-column' === $column_name ) {
		global $post;

		if ( false !== strpos( $post->post_mime_type, 'image' ) ) {
			echo '<input
                type="text"
                class="eae-field"
                name="eae-alt-' . $post->ID . '"
                data-media-id="' . $post->ID . '"
                value="' . wp_strip_all_tags( __( get_post_meta( $post->ID, '_wp_attachment_image_alt', true ) ) ) . '"
            />';

			echo '<button type="button" class="button eae-button" data-media-title="' . $post->post_title . '">' . __( 'Use image title', 'eae' ) . '</button>';
		}
	}
}
add_filter( 'manage_media_custom_column', 'eae_media_custom_column_alt' );

/**
 * jQuery event
 */
function eae_jquery_event() {
	echo "<script>jQuery(function($){
        function eae_update(field) {
            var field_value = field.val()

			$.ajax({
				type: 'POST',
				data: {
					action: 'eaesave',
					value: field_value,
					media: field.attr('data-media-id'),
					eae_update : '" . wp_create_nonce( 'eaeupdate' ) . "'
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
            var eae_title = $(this).attr('data-media-title');
            var eae_field = $(this).prev();

            eae_field.val(eae_title);
            eae_update(eae_field);
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
	});</script>";
}
add_action( 'admin_footer', 'eae_jquery_event' );

/**
 * Process AJAX request
 */
function eae_process_update() {
	global $_POST;

	check_ajax_referer( 'eaeupdate', 'eae_update' );

	if ( !isset($_POST['eae_update']) && !wp_verify_nonce( $_POST['eae_update'], 'eaeupdate' ) ) {
		echo 'No.';
		die();
	}

	if ( !isset($_POST['media'] ) || !isset($_POST['value']) ) {
		echo 'No again.';
		die();
	}

	$safe_id = intval( $_POST['media']);
	$safe_value = sanitize_text_field($_POST['value']);

	if ( update_post_meta( $safe_id, '_wp_attachment_image_alt', wp_unslash( $safe_value ) ) ) {
		echo 'Saved';
	}

	die();
}
add_action( 'wp_ajax_eaesave', 'eae_process_update' );
