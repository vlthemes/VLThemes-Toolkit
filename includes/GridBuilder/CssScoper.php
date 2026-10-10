<?php

namespace VLT\Toolkit\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom CSS of a layout, limited to that layout
 *
 * Isolation rules — every selector is prefixed with the layout's scope class (.vlt-gb--{ID}):
 * - ".title"                  → ".vlt-gb--12 .title"          (inside the grid)
 * - "selector"                → ".vlt-gb--12"                 (the grid container itself)
 * - "selector .title", "selector:hover" → ".vlt-gb--12 .title", ".vlt-gb--12:hover"
 * - "html", "body", ":root" at the start → the scope class       (no reaching outside)
 * - Selector lists are prefixed one by one; @media / @supports / @container / @layer blocks are scoped inside;
 *   @keyframes, @font-face and other at-rules are kept as written; @import, @charset and @namespace are dropped.
 *
 * Every instance of the same layout shares the scope class, so the CSS is printed once per page and applies to all of them.
 * Saving needs the edit_css capability (as WordPress' Additional CSS), see PostTypes::can_edit_css().
 */
class CssScoper {
	/**
	 * Max stored length
	 */
	const MAX_LENGTH = 50000;

	/**
	 * Meta sanitize callback — CSS is not text: sanitize_text_field() would break it, so only markup is removed
	 *
	 * @param mixed $css CSS
	 *
	 * @return string
	 */
	public static function sanitize( $css ) {
		$css = wp_strip_all_tags( (string) $css );

		return mb_substr( str_replace( '</', '', $css ), 0, self::MAX_LENGTH );
	}

	/**
	 * Scope CSS to a selector
	 *
	 * @param string $css   CSS
	 * @param string $scope Scope selector
	 *
	 * @return string
	 */
	public static function scope( $css, $scope ) {
		return str_replace( '</', '', self::scope_block( self::sanitize( $css ), $scope ) );
	}

	/**
	 * Scope a list of rules
	 *
	 * @param string $css   CSS
	 * @param string $scope Scope
	 *
	 * @return string
	 */
	private static function scope_block( $css, $scope ) {
		$out    = '';
		$i      = 0;
		$length = strlen( $css );

		while ( $i < $length ) {
			[ $prelude, $at, $char ] = self::read_until( $css, $i );
			$prelude                 = trim( self::strip_comments( $prelude ) );

			if ( null === $char ) {
				break;
			}

			if ( ';' === $char ) {
				$i = $at + 1;

				if ( '' !== $prelude && !preg_match( '/^@(import|charset|namespace)\b/i', $prelude ) ) {
					$out .= $prelude . ';';
				}

				continue;
			}

			[ $body, $i ] = self::read_block( $css, $at );

			if ( '' === $prelude ) {
				continue;
			}

			if ( '@' !== $prelude[0] ) {
				$out .= self::scope_selectors( $prelude, $scope ) . '{' . $body . '}';
			} elseif ( preg_match( '/^@(media|supports|container|layer|scope)\b/i', $prelude ) ) {
				$out .= $prelude . '{' . self::scope_block( $body, $scope ) . '}';
			} else {
				$out .= $prelude . '{' . $body . '}';
			}
		}

		return $out;
	}

	/**
	 * Read up to the next top-level "{" or ";" (skipping comments, strings and parentheses)
	 *
	 * @param string $css   CSS
	 * @param int    $start Start offset
	 *
	 * @return array [ text, offset of the stop char, stop char or null at the end ]
	 */
	private static function read_until( $css, $start ) {
		$length = strlen( $css );
		$depth  = 0;

		for ( $i = $start; $i < $length; $i++ ) {
			$c = $css[ $i ];

			if ( '/' === $c && '*' === ( $css[ $i + 1 ] ?? '' ) ) {
				$i = self::skip_comment( $css, $i );
			} elseif ( '"' === $c || "'" === $c ) {
				$i = self::skip_string( $css, $i );
			} elseif ( '(' === $c ) {
				$depth++;
			} elseif ( ')' === $c ) {
				$depth = max( 0, $depth - 1 );
			} elseif ( 0 === $depth && ( '{' === $c || ';' === $c ) ) {
				return [ substr( $css, $start, $i - $start ), $i, $c ];
			} elseif ( 0 === $depth && '}' === $c ) {
				// Stray closing brace: drop what came before it
				$start = $i + 1;
			}
		}

		return [ substr( $css, $start ), $length, null ];
	}

	/**
	 * Read a block body
	 *
	 * @param string $css  CSS
	 * @param int    $open Offset of "{"
	 *
	 * @return array [ body, offset after the closing "}" ]
	 */
	private static function read_block( $css, $open ) {
		$length = strlen( $css );
		$depth  = 1;

		for ( $i = $open + 1; $i < $length; $i++ ) {
			$c = $css[ $i ];

			if ( '/' === $c && '*' === ( $css[ $i + 1 ] ?? '' ) ) {
				$i = self::skip_comment( $css, $i );
			} elseif ( '"' === $c || "'" === $c ) {
				$i = self::skip_string( $css, $i );
			} elseif ( '{' === $c ) {
				$depth++;
			} elseif ( '}' === $c && 0 === --$depth ) {
				return [ substr( $css, $open + 1, $i - $open - 1 ), $i + 1 ];
			}
		}

		// Unclosed block: take the rest
		return [ substr( $css, $open + 1 ), $length ];
	}

	/**
	 * Prefix every selector of a list
	 *
	 * @param string $selectors Selector list
	 * @param string $scope     Scope
	 *
	 * @return string
	 */
	private static function scope_selectors( $selectors, $scope ) {
		$parts  = [];
		$depth  = 0;
		$start  = 0;
		$length = strlen( $selectors );

		// Split on top-level commas only: ":is(a, b)" and "[title='a,b']" stay whole
		for ( $i = 0; $i < $length; $i++ ) {
			$c = $selectors[ $i ];

			if ( '"' === $c || "'" === $c ) {
				$i = self::skip_string( $selectors, $i );
			} elseif ( '(' === $c || '[' === $c ) {
				$depth++;
			} elseif ( ')' === $c || ']' === $c ) {
				$depth = max( 0, $depth - 1 );
			} elseif ( ',' === $c && 0 === $depth ) {
				$parts[] = substr( $selectors, $start, $i - $start );
				$start   = $i + 1;
			}
		}

		$parts[] = substr( $selectors, $start );
		$scoped  = [];

		foreach ( $parts as $selector ) {
			$selector = trim( $selector );

			if ( '' === $selector ) {
				continue;
			}

			if ( preg_match( '/^(?:selector|html|body|:root)(?![\w-])/i', $selector, $match ) ) {
				$scoped[] = $scope . substr( $selector, strlen( $match[0] ) );
			} else {
				$scoped[] = $scope . ' ' . $selector;
			}
		}

		return implode( ',', $scoped );
	}

	/**
	 * Skip a comment
	 *
	 * @param string $css CSS
	 * @param int    $i   Offset of "/*"
	 *
	 * @return int Offset of the comment's last char
	 */
	private static function skip_comment( $css, $i ) {
		$end = strpos( $css, '*/', $i + 2 );

		return false === $end ? strlen( $css ) : $end + 1;
	}

	/**
	 * Skip a quoted string
	 *
	 * @param string $css CSS
	 * @param int    $i   Offset of the opening quote
	 *
	 * @return int Offset of the closing quote
	 */
	private static function skip_string( $css, $i ) {
		$quote  = $css[ $i ];
		$length = strlen( $css );

		for ( $j = $i + 1; $j < $length; $j++ ) {
			if ( '\\' === $css[ $j ] ) {
				$j++;
			} elseif ( $quote === $css[ $j ] ) {
				return $j;
			}
		}

		return $length;
	}

	/**
	 * Remove comments
	 *
	 * @param string $css CSS
	 *
	 * @return string
	 */
	private static function strip_comments( $css ) {
		return (string) preg_replace( '#/\*.*?\*/#s', '', $css );
	}
}
