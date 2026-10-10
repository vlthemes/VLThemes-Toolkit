<?php

namespace VLT\Toolkit\Modules\Integrations\ACF;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * User Avatar
 *
 * Uses an ACF image field on the user (Users → Profile) instead of Gravatar, when set.
 * The field name comes from the 'vlt_toolkit_user_avatar_field' filter (default: user_avatar).
 */
class UserAvatar {
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * Register hooks
	 */
	public function register() {
		add_filter( 'pre_get_avatar_data', [ $this, 'avatar_data' ], 10, 2 );
	}

	/**
	 * Resolve the user behind get_avatar()'s $id_or_email
	 *
	 * @param mixed $id_or_email User ID, email, WP_User, WP_Post or WP_Comment
	 *
	 * @return int User ID or 0
	 */
	private function get_user_id( $id_or_email ) {
		if ( $id_or_email instanceof \WP_User ) {
			return $id_or_email->ID;
		}

		if ( $id_or_email instanceof \WP_Post ) {
			return (int) $id_or_email->post_author;
		}

		if ( $id_or_email instanceof \WP_Comment ) {
			if ( $id_or_email->user_id ) {
				return (int) $id_or_email->user_id;
			}

			// Guest comment left with a registered user's email
			$id_or_email = $id_or_email->comment_author_email;
		}

		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}

		$user = is_string( $id_or_email ) && is_email( $id_or_email ) ? get_user_by( 'email', $id_or_email ) : false;

		return $user ? $user->ID : 0;
	}

	/**
	 * Swap the avatar URL for the user's ACF image
	 *
	 * @param array $args        Avatar data args
	 * @param mixed $id_or_email User identifier
	 *
	 * @return array
	 */
	public function avatar_data( $args, $id_or_email ) {
		// Settings → Discussion previews the default avatars with force_default
		if ( !empty( $args['force_default'] ) ) {
			return $args;
		}

		$user_id = $this->get_user_id( $id_or_email );

		if ( !$user_id ) {
			return $args;
		}

		$field    = apply_filters( 'vlt_toolkit_user_avatar_field', 'user_avatar' );
		$image_id = (int) get_field( $field, 'user_' . $user_id, false );

		if ( !$image_id ) {
			return $args;
		}

		// [ w, h ] picks the smallest square intermediate size that covers the request (get_avatar() also asks 2× for srcset)
		$size  = (int) ( $args['size'] ?? 96 );
		$image = wp_get_attachment_image_src( $image_id, apply_filters( 'vlt_toolkit_user_avatar_size', [ $size, $size ], $size, $user_id ) );

		if ( $image ) {
			$args['url']          = $image[0];
			$args['found_avatar'] = true;
		}

		return $args;
	}
}
