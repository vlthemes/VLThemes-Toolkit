<?php

namespace VLT\Toolkit\Admin;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared look of the toolkit's admin list tables (Template Parts, Grid Layouts)
 *
 * Cells built from the same pieces, styled by assets/css/admin-list.css (dashboard tokens):
 * - summary(): type in bold + detail, a quiet line, badges; optional thumbnail on the left
 * - shortcode(): one-line code with a copy icon (WordPress' own ClipboardJS, as "Copy URL" in the media library)
 * - note(): quiet text, "—" when empty
 *
 * A screen opts in with ListTable::screen( 'edit-{post_type}' ) from its admin_enqueue_scripts hook.
 */
class ListTable {
	/**
	 * Enqueue the styles and the copy script on a list screen
	 *
	 * @param string $screen_id Screen ID (edit-{post_type})
	 */
	public static function screen( $screen_id ) {
		$screen = get_current_screen();

		if ( !$screen || $screen_id !== $screen->id ) {
			return;
		}

		$css = VLT_TOOLKIT_PATH . 'assets/css/admin-list.css';

		wp_enqueue_style( 'vlt-admin-list', VLT_TOOLKIT_URL . 'assets/css/admin-list.css', [], VLT_TOOLKIT_VERSION . '.' . filemtime( $css ) );
		wp_enqueue_script( 'clipboard' );
		wp_add_inline_script( 'clipboard', self::copy_script() );
	}

	/**
	 * Summary cell
	 *
	 * @param string $title  Type / kind, in bold
	 * @param string $detail Its detail (plain text), quieter
	 * @param array  $lines  Quiet lines (HTML, already escaped)
	 * @param array  $badges [ [ label, modifier ('' | 'is-warning'), icon SVG (optional, trusted markup) ], … ]
	 * @param string $thumb  Thumbnail HTML on the left (already escaped)
	 *
	 * @return string
	 */
	public static function summary( $title, $detail = '', array $lines = [], array $badges = [], $thumb = '' ) {
		$html = '<div class="vlt-list-summary">' . $thumb . '<div class="vlt-list-summary__body">';
		$html .= '<span class="vlt-list-summary__title"><strong>' . esc_html( $title ) . '</strong>' . ( '' !== $detail ? ' <span>' . esc_html( $detail ) . '</span>' : '' ) . '</span>';

		foreach ( array_filter( $lines ) as $line ) {
			$html .= '<span class="vlt-list-summary__meta">' . $line . '</span>';
		}

		if ( $badges ) {
			$html .= '<span class="vlt-list-summary__badges">';

			foreach ( $badges as $badge ) {
				[ $label, $modifier ] = $badge;
				$icon                 = $badge[2] ?? '';

				$html .= '<span class="vlt-list-badge' . ( $modifier ? ' ' . sanitize_html_class( $modifier ) : '' ) . '">' . $icon . esc_html( $label ) . '</span>';
			}

			$html .= '</span>';
		}

		return $html . '</div></div>';
	}

	/**
	 * Shortcode cell: one line, copied whole by the icon
	 *
	 * @param string $code Shortcode
	 *
	 * @return string
	 */
	public static function shortcode( $code ) {
		return sprintf(
			'<span class="vlt-list-code"><code>%1$s</code><button type="button" class="vlt-list-code__copy" data-clipboard-text="%2$s" aria-label="%3$s" title="%4$s">%5$s</button></span>',
			esc_html( $code ),
			esc_attr( $code ),
			esc_attr__( 'Copy shortcode', 'toolkit' ),
			esc_attr__( 'Copy', 'toolkit' ),
			'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/></svg>'
			. '<svg class="is-done" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
		);
	}

	/**
	 * Note cell
	 *
	 * @param string $note Note
	 *
	 * @return string
	 */
	public static function note( $note ) {
		return '' !== trim( (string) $note ) ? '<span class="vlt-list-note">' . wp_kses_post( nl2br( $note ) ) . '</span>' : '<span class="vlt-list-note is-empty">—</span>';
	}

	/**
	 * Copy buttons: ClipboardJS, a check mark for a moment, then the copy icon again; codes that fit lose the fade
	 *
	 * @return string
	 */
	private static function copy_script() {
		$copied = wp_json_encode( __( 'Copied', 'toolkit' ) );

		return 'document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".vlt-list-code code").forEach(function(e){e.parentNode.classList.toggle("is-fit",e.scrollWidth<=e.clientWidth);});var c=new ClipboardJS(".vlt-list-code__copy");c.on("success",function(e){var b=e.trigger,l=b.getAttribute("aria-label");e.clearSelection();b.classList.add("is-copied");b.setAttribute("aria-label",' . $copied . ');setTimeout(function(){b.classList.remove("is-copied");b.setAttribute("aria-label",l);},1500);});});';
	}
}
