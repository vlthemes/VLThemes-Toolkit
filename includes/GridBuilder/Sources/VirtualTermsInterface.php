<?php

namespace VLT\Toolkit\GridBuilder\Sources;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A source whose taxonomy is not a registered WordPress taxonomy
 *
 * Its terms live in the layout config (e.g. per-image labels of MediaSource), so the filter takes them
 * from the source instead of get_terms(). Term IDs only have to be stable for one config.
 */
interface VirtualTermsInterface {
	/**
	 * Terms of a virtual taxonomy, in display order
	 *
	 * @param array  $query    Sanitised "query" config section
	 * @param string $taxonomy Taxonomy
	 *
	 * @return \WP_Term[]|null Null when $taxonomy is a real WordPress taxonomy
	 */
	public function get_virtual_terms( array $query, $taxonomy );
}
