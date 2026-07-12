<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Upload MIME Types Module
 *
 * Extends allowed upload MIME types for WordPress
 * Adds support for SVG and other file formats
 */
class UploadMimes extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'upload_mimes';

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
		// Extend allowed MIME types
		add_filter( 'upload_mimes', [ $this, 'extend_mime_types' ] );
	}

	/**
	 * Extend allowed MIME types
	 *
	 * @param array $mimes existing MIME types
	 *
	 * @return array modified MIME types
	 */
	public function extend_mime_types( $mimes ) {
		// Add SVG support
		$mimes['svg'] = 'image/svg+xml';

		// Add JSON support
		$mimes['json'] = 'application/json';

		// Add WebP support
		$mimes['webp'] = 'image/webp';

		// Add font file support
		$mimes['otf']   = 'font/otf';
		$mimes['ttf']   = 'font/ttf';
		$mimes['woff']  = 'font/woff';
		$mimes['woff2'] = 'font/woff2';

		return apply_filters( 'vlt_toolkit_upload_mimes', $mimes );
	}
}
