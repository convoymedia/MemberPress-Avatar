<?php
	/*
	Plugin Name: MemberPress Avatar
	Plugin URI: http://convoymedia.com
	Description: Extend MemberPress Profile to include a user avatar and give the user the ability to uplaod an image to it
	Version: 0.0.1
	Author: Convoy Media
	Author URI: http://convoymedia.com
	*/

	function convoy_profile_image($message) {
		echo $message;
		
		global $current_user;
		get_currentuserinfo();

		$current_user_id = $current_user->ID;
		$profile_image = get_field( 'profile_image','user_'.$current_user_id );

		$current_display_name = $current_user->data->display_name;
		$current_user_email = $current_user->data->user_email;

		?>
		<form name="basic_profile" id="basic_profile" action="<?php echo site_url(); ?>/wp-admin/admin-post.php" method="post" enctype="multipart/form-data">
			<label>Profile Image</label>
			<?php 
				$profile_pic = get_user_meta($current_user_id, 'memberpress_avatar', true);
				$image = wp_get_attachment_image_src( $profile_pic, 'thumbnail' );
			?>
			<img src="<?php echo $image[0]; ?>" />
			<?php wp_nonce_field('memberpress_avatar'); ?>
			<input type="hidden" name="action" value="convoy_upload_profile_image" />
			<input type="file" id="userProfileImage" name="userProfileImage" multiple="false" />
			<input id="submit" type="submit" name="submit" value="Save Profile Image" />
		</form>
		<?php
	}
	add_filter('mepr-account-welcome-message', 'convoy_profile_image');



	function convoy_profile_image_post() {
		if (is_user_logged_in() && wp_verify_nonce($_POST['_wpnonce'], 'memberpress_avatar')) {
			global $current_user;
			get_currentuserinfo();

			$current_user_id = $current_user->ID;
			if (!function_exists('wp_generate_attachment_metadata')){
				require_once(ABSPATH . "wp-admin" . '/includes/image.php');
				require_once(ABSPATH . "wp-admin" . '/includes/file.php');
				require_once(ABSPATH . "wp-admin" . '/includes/media.php');
			}
			if($_FILES)
			{
				foreach ($_FILES as $file => $array)
				{
					if($_FILES[$file]['error'] !== UPLOAD_ERR_OK){return "upload error : " . $_FILES[$file]['error'];}//If upload error
					$attach_id = media_handle_upload($file,$new_post);
					update_user_meta($current_user_id, 'memberpress_avatar', $attach_id);
				}
			}
		}
		wp_redirect('http://autest.digitalethos.net/profile/');
		die();
	}
	add_action( 'admin_post_convoy_upload_profile_image', 'convoy_profile_image_post' );
	add_action( 'admin_post_nopriv_convoy_upload_profile_image', 'convoy_profile_image_post' );

	function convoy_add_admin_scripts(){
		wp_enqueue_media();
		wp_enqueue_script('convoy-uploader', get_stylesheet_directory_uri().'/js/uploader.js', array('jquery'), false, true );
	}
	add_action('admin_enqueue_scripts', 'convoy_add_admin_scripts');

	function convoy_extra_profile_fields( $user ) {
		$profile_pic = ($user!=='add-new-user') ? get_user_meta($user->ID, 'memberpress_avatar', true): false;

		if( !empty($profile_pic) ){
			$image = wp_get_attachment_image_src( $profile_pic, 'thumbnail' );

		} ?>

		<table class="form-table fh-profile-upload-options">
			<tr>
				<th>
					<label for="image"><?php _e('Main Profile Image', 'convoy') ?></label>
				</th>

				<td>
					<input type="button" data-id="convoy_image_id" data-src="convoy-img" class="button convoy-image" name="convoy_image" id="convoy-image" value="Upload" />
					<input type="hidden" class="button" name="convoy_image_id" id="convoy_image_id" value="<?php echo !empty($profile_pic) ? $profile_pic : ''; ?>" />
					<img id="convoy-img" src="<?php echo !empty($profile_pic) ? $image[0] : ''; ?>" style="<?php echo  empty($profile_pic) ? 'display:none;' :'' ?> max-width: 100px; max-height: 100px;" />
				</td>
			</tr>
		</table><?php

	}
	add_action( 'show_user_profile', 'convoy_extra_profile_fields' );
	add_action( 'edit_user_profile', 'convoy_extra_profile_fields' );
	add_action( 'user_new_form', 'convoy_extra_profile_fields' );

	function convoy_profile_update($user_id){
		if( current_user_can('edit_users') ){
			$profile_pic = empty($_POST['convoy_image_id']) ? '' : $_POST['convoy_image_id'];
			update_user_meta($user_id, 'memberpress_avatar', $profile_pic);
		}

	}
	add_action('profile_update', 'convoy_profile_update');
	add_action('user_register', 'convoy_profile_update');

	function my_profile_upload_js() { ?>
		<script type="text/javascript">
			jQuery( document ).ready( function() {

				/* WP Media Uploader */
				var _convoy_media = true;
				var _orig_send_attachment = wp.media.editor.send.attachment;

				jQuery( '.convoy-image' ).click( function() {

					var button = jQuery( this ),
							textbox_id = jQuery( this ).attr( 'data-id' ),
							image_id = jQuery( this ).attr( 'data-src' ),
							_convoy_media = true;

					wp.media.editor.send.attachment = function( props, attachment ) {

						if ( _convoy_media && ( attachment.type === 'image' ) ) {
							if ( image_id.indexOf( "," ) !== -1 ) {
								image_id = image_id.split( "," );
								$image_ids = '';
								jQuery.each( image_id, function( key, value ) {
									if ( $image_ids )
										$image_ids = $image_ids + ',#' + value;
									else
										$image_ids = '#' + value;
								} );

								var current_element = jQuery( $image_ids );
							} else {
								var current_element = jQuery( '#' + image_id );
							}

							jQuery( '#' + textbox_id ).val( attachment.id );
											console.log(textbox_id)
							current_element.attr( 'src', attachment.url ).show();
						} else {
							alert( 'Please select a valid image file' );
							return false;
						}
					}

					wp.media.editor.open( button );
					return false;
				} );

			} );
		</script>
	<?php }
	add_action('admin_head','my_profile_upload_js');
?>