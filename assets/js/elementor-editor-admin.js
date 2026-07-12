document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	/* ========================================
	 * Elementor Pro Links (Affiliate Redirect)
	 * ======================================== */
	const affiliateUrl = 'https://be.elementor.com/visit/?bta=65732&brand=elementor';

	function replaceLinks() {
		document.querySelectorAll('a[href*="elementor.com/pro"], a[href*="go.elementor.com"]').forEach(function(link) {
			link.href = affiliateUrl;
		});
	}
	replaceLinks();
	new MutationObserver(replaceLinks).observe(document.body, {
		childList: true,
		subtree: true
	});

});