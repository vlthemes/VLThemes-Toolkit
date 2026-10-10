<?php

namespace VLT\Toolkit\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Editor preview as a real front-end page (the Visual Portfolio approach)
 *
 * The editor POSTs the unsaved settings to home_url( '?vlt_grid_preview=1' ) into its iframe. The page has no
 * theme header / footer, but wp_head() and wp_footer() load everything the theme and plugins enqueue — fonts,
 * colours, scripts (lightboxes, sliders…) — so the grid looks and behaves as on the site.
 *
 * - Only editors of the layout, with the editor nonce. Recognised on init (Visual Portfolio does the same), so the
 *   admin bar, 404 handling, canonical / Yoast redirects, WooCommerce geolocation, Rocket Loader and page / object
 *   / minify / CDN caches are out of the way before WordPress sets the page up.
 * - Since WordPress 7.1 the block editor is cross-origin isolated (Document-Isolation-Policy, see
 *   wp_set_up_cross_origin_isolation()): a document in another agent cluster can't be reached even from the same
 *   origin. The editor says whether it is isolated, and the page then joins it with the same header — only then,
 *   or the mismatch would happen the other way round on editors that aren't isolated.
 * - The grid sits in <div class="vlt-gb-preview">; a theme adds its content classes with the
 *   vlt_toolkit_grid_preview_classes filter, or styles body.vlt-gb-preview-body for the preview only.
 * - The page reports its height and state (ok / empty / no-skin) to the editor with postMessage, and keeps
 *   links from navigating the frame away.
 */
class Preview {
	const QUERY_VAR = 'vlt_grid_preview';

	/**
	 * Layout being previewed (0 = not a preview request), set on init
	 *
	 * @var int
	 */
	private static $post_id = 0;

	/**
	 * Init
	 */
	public static function init() {
		// Recognised on init, before WordPress sets up the admin bar (on "wp") and decides about a 404
		add_action( 'init', [ __CLASS__, 'detect' ], 1 );
		add_action( 'template_redirect', [ __CLASS__, 'maybe_render' ], 0 );
	}

	/**
	 * Preview page URL (the editor POSTs to it): the home URL with a trailing slash (some servers need it),
	 * the current scheme, and a time stamp past any cache
	 *
	 * @return string
	 */
	public static function url() {
		return set_url_scheme( add_query_arg( [
			self::QUERY_VAR => '1',
			't'             => time(),
		], trailingslashit( home_url( '/' ) ) ) );
	}

	/**
	 * A valid preview request: switch off what would get in the way, before the page is set up
	 */
	public static function detect() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- the nonce is checked right below
		if ( is_admin() || !isset( $_GET[ self::QUERY_VAR ] ) ) {
			return;
		}
		// phpcs:enable

		$post_id = absint( $_POST['post_id'] ?? 0 );

		if (
			'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' )
			|| !wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ?? '' ) ), Ajax::ADMIN_NONCE )
			|| !$post_id
			|| PostTypes::LAYOUT !== get_post_type( $post_id )
			|| !current_user_can( 'edit_post', $post_id )
		) {
			return;
		}

		self::$post_id = $post_id;

		add_filter( 'show_admin_bar', '__return_false' );
		add_filter( 'pre_handle_404', '__return_true' );
		add_filter( 'wp_robots', 'wp_robots_no_robots' );

		// No redirect to a "canonical" URL (also Yoast's "remove unneeded query variables")
		remove_action( 'template_redirect', 'redirect_canonical' );

		add_action( 'template_redirect', function () {
			if ( class_exists( '\WPSEO_Frontend' ) ) {
				remove_action( 'template_redirect', [ \WPSEO_Frontend::get_instance(), 'clean_permalink' ], 1 );
			}
		}, -1 );

		// WooCommerce geolocation reloads the page — the frame would reload with it
		add_action( 'wp_enqueue_scripts', function () {
			wp_dequeue_script( 'wc-geolocation' );
		}, 11 );

		// Cloudflare Rocket Loader would defer the scripts (inline settings included) and break their order
		add_filter( 'script_loader_tag', fn( $tag ) => str_replace( '<script', '<script data-cfasync="false"', $tag ) );
		add_filter( 'wp_inline_script_attributes', fn( $attributes ) => [ 'data-cfasync' => 'false' ] + $attributes );

		// Page cache / minify / CDN plugins' conventions
		foreach ( [ 'DONOTCACHEPAGE', 'DONOTCACHEDB', 'DONOTMINIFY', 'DONOTCDN', 'DONOTCACHEOBJECT' ] as $constant ) {
			if ( !defined( $constant ) ) {
				define( $constant, true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
			}
		}
	}

	/**
	 * Render the preview instead of the requested page (403 for an invalid preview request)
	 */
	public static function maybe_render() {
		// phpcs:disable WordPress.Security.NonceVerification -- checked in detect()
		if ( !isset( $_GET[ self::QUERY_VAR ] ) || is_admin() ) {
			return;
		}

		if ( !self::$post_id ) {
			wp_die( esc_html__( 'You are not allowed to preview this grid layout.', 'toolkit' ), '', 403 );
		}

		$post_id              = self::$post_id;
		$config               = Config::sanitize( wp_unslash( $_POST['config'] ?? '' ) );
		$config['id']         = $post_id;
		$config['custom_css'] = current_user_can( 'edit_css' )
			? CssScoper::sanitize( wp_unslash( $_POST['css'] ?? '' ) )
			: (string) get_post_meta( $post_id, PostTypes::META_CSS, true );

		nocache_headers();
		status_header( 200 );

		if ( !empty( $_POST['isolated'] ) && !headers_sent() ) {
			header( 'Document-Isolation-Policy: isolate-and-credentialless' );
		}
		// phpcs:enable

		// The grid first: everything it enqueues (skin, layout, inserts' Elementor / block styles) lands in <head>
		$html  = Renderer::render( $config, true );
		$state = Renderer::$preview_state;

		// Filter / pagination requests inside the frame carry the unsaved settings (Ajax::load checks them)
		wp_add_inline_script( 'vlt-grid', 'window.vltGridBuilder = window.vltGridBuilder || {}; window.vltGridBuilder.preview = ' . wp_json_encode( [
			'config' => wp_json_encode( $config ),
			'nonce'  => wp_create_nonce( Ajax::ADMIN_NONCE ),
		] ) . ';', 'before' );

		$classes = implode( ' ', array_map( 'sanitize_html_class', (array) apply_filters( 'vlt_toolkit_grid_preview_classes', [ 'vlt-gb-preview' ], $config ) ) );

		self::template( $html, $state, $classes );
		exit;
	}

	/**
	 * The page
	 *
	 * @param string $html    Grid markup
	 * @param string $state   Renderer::$preview_state
	 * @param string $classes Wrapper classes
	 */
	private static function template( $html, $state, $classes ) {
		?><!DOCTYPE html>
<html <?php language_attributes(); ?> style="margin-top:0 !important">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style id="vlt-gb-preview-css">
		html, body.vlt-gb-preview-body { min-height: 0 !important; height: auto !important; margin: 0 !important; }
		body.vlt-gb-preview-body { padding: 0 !important; }
		<?php // Placeholders drawn when the theme has no skin (Renderer::preview_items): the layout's shape in grey ?>
		.vlt-gb__placeholder { height: 100%; min-height: 160px; box-sizing: border-box; background: #f4f4f5; border: 1px solid #e4e4e7; border-radius: 8px; }
		.vlt-gb--layout-grid .vlt-gb__placeholder { min-height: 0; aspect-ratio: var(--vlt-grid-ratio, 4/3); }
		.vlt-gb--layout-tiles .vlt-gb__placeholder { min-height: 0; }
		.vlt-gb--layout-masonry .vlt-gb__item:nth-child(3n+1) .vlt-gb__placeholder { height: 240px; }
		.vlt-gb--layout-masonry .vlt-gb__item:nth-child(3n+2) .vlt-gb__placeholder { height: 170px; }
		.vlt-gb--layout-masonry .vlt-gb__item:nth-child(3n) .vlt-gb__placeholder { height: 300px; }
	</style>
</head>
<body <?php body_class( 'vlt-gb-preview-body' ); ?> data-vlt-grid-state="<?php echo esc_attr( $state ); ?>">
	<div class="<?php echo esc_attr( $classes ); ?>">
		<?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered by Renderer ?>
	</div>
	<?php wp_footer(); ?>
	<script>
		( function () {
			var state = document.body.getAttribute( 'data-vlt-grid-state' );
			var last = -1;

			// Height and state for the editor (it checks the message comes from its own frame); on load always,
			// since the editor (re)binds its listener when the frame loads
			function report( force ) {
				var height = Math.ceil( document.body.getBoundingClientRect().height );

				if ( ( force === true || height !== last ) && window.parent !== window ) {
					last = height;
					window.parent.postMessage( { vltGridPreview: true, height: height, state: state }, '*' );
				}
			}

			report();
			new ResizeObserver( report ).observe( document.body );
			window.addEventListener( 'load', function () {
				report( true );
			} );

			// The frame is as tall as its content, so the wheel belongs to the editor. Smooth-scroll scripts of the
			// theme (Lenis, Locomotive…) would take it and cancel it: catch it first (capture on window, before them)
			// and scroll the editor instead — the canvas vertically, the preview stage sideways (a device wider than
			// the canvas). Ctrl + wheel (zoom) is left to the browser.
			window.addEventListener( 'wheel', function ( event ) {
				event.stopImmediatePropagation();

				if ( window.parent === window || event.ctrlKey ) {
					return;
				}

				var unit = 1 === event.deltaMode ? 16 : 1;
				var stage = null;

				try {
					stage = window.frameElement && window.frameElement.closest( '.vlt-grid-editor__stage' );
				} catch ( error ) {
					// Another origin: the canvas only
				}

				event.preventDefault();
				window.parent.scrollBy( { top: event.deltaY * unit } );

				if ( stage && event.deltaX ) {
					stage.scrollLeft += event.deltaX * unit;
				}
			}, { capture: true, passive: false } );

			// A preview, not a browser: links don't navigate the frame (grid controls are handled by grid.js)
			document.addEventListener( 'click', function ( event ) {
				var link = event.target.closest( 'a[href]' );

				if ( link && !event.defaultPrevented ) {
					event.preventDefault();
				}
			} );

			document.addEventListener( 'submit', function ( event ) {
				event.preventDefault();
			} );
		} )();
	</script>
</body>
</html>
		<?php
	}
}
