<?php

namespace VLT\Toolkit\Modules\Helpers;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Media Helper Module
 *
 * Provides utility methods for working with media (videos, etc.)
 */
class MediaHelper extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'media_helper';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Register module
	 */
	public function register() {
		// No hooks needed - this is a utility module
	}

	/**
	 * Parse video ID from URL
	 *
	 * Extracts video vendor (youtube, vimeo) and video ID from URL
	 *
	 * @param string $url Video URL
	 *
	 * @return array Array with vendor and video ID [vendor, id]
	 */
	public static function parse_video_id( $url ) {
		if ( empty( $url ) || !is_string( $url ) ) {
			return [ 'custom', '' ];
		}

		$vendors = [
			[
				'vendor'       => 'youtube',
				'pattern'      => '/(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be|youtube-nocookie\.com)\/(?:embed\/|v\/|watch\?v=|watch\?list=(.*)&v=|watch\?(.*[^&]&)v=)?((\w|-){11})(&list=(\w+)&?)?/',
				'patternIndex' => 6,
			],
			[
				'vendor'       => 'vimeo',
				'pattern'      => '/https?:\/\/(?:www\.|player\.)?vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)/',
				'patternIndex' => 3,
			],
		];

		foreach ( $vendors as $vendor ) {
			$video_id = false;

			if ( preg_match( $vendor['pattern'], $url, $matches ) && isset( $matches[ $vendor['patternIndex'] ] ) ) {
				$video_id = $matches[ $vendor['patternIndex'] ];
			}

			if ( $video_id ) {
				$data = [ $vendor['vendor'], $video_id ];

				return apply_filters( 'vlt_toolkit_video_id', $data, $url );
			}
		}

		return apply_filters( 'vlt_toolkit_video_id', [ 'custom', esc_url_raw( $url ) ], $url );
	}
}
