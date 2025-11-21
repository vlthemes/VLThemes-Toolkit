<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamic Content Module
 *
 * Parses dynamic content variables in text
 */
class DynamicContent extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'dynamic_content';

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
		add_filter( 'the_content', [ __CLASS__, 'parse' ], 999 );
		add_filter( 'the_excerpt', [ __CLASS__, 'parse' ], 999 );
		add_filter( 'widget_text', [ __CLASS__, 'parse' ], 999 );
	}

	/**
	 * Parse dynamic content variables in text
	 *
	 * Replaces dynamic variables with their actual values:
	 * - {{YEAR}} - Current year (e.g., 2025)
	 * - {{SITE_TITLE}} - Site title from WordPress settings
	 * - {{SITE_URL}} - Home URL of the site
	 * - {{SITE_NAME}} - Site name (blogname)
	 * - {{ADMIN_EMAIL}} - Administrator email
	 * - {{PAGE_TITLE}} - Current page title
	 * - {{SITE_TAGLINE}} - Site tagline/description
	 * - {{PAGE_ID}} - Current page ID
	 * - {{THEME_NAME}} - Active theme name
	 *
	 * @param string $text Text containing dynamic variables
	 *
	 * @return string Parsed text with replaced variables
	 */
	public static function parse( $text ) {
		if ( empty( $text ) || !is_string( $text ) ) {
			return $text;
		}

		$theme        = wp_get_theme();
		$replacements = [
			'{{YEAR}}'         => date( 'Y' ),
			'{{SITE_TITLE}}'   => get_bloginfo( 'name' ),
			'{{SITE_URL}}'     => home_url( '/' ),
			'{{SITE_NAME}}'    => get_bloginfo( 'name' ),
			'{{ADMIN_EMAIL}}'  => get_bloginfo( 'admin_email' ),
			'{{PAGE_TITLE}}'   => get_the_title(),
			'{{SITE_TAGLINE}}' => get_bloginfo( 'description' ),
			'{{PAGE_ID}}'      => get_the_ID(),
			'{{THEME_NAME}}'   => $theme->get( 'Name' ),
		];

		$replacements = apply_filters( 'vlt_toolkit_dynamic_content_vars', $replacements );

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
	}
}
