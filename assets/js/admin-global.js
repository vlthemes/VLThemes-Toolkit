document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	/* ========================================
	 * Elementor Pro Links (Affiliate Redirect)
	 * ======================================== */
	const affiliateUrl = 'https://be.elementor.com/visit/?bta=65732&brand=elementor';
	const linkColor = '#d54e21';

	// 1. Main Elementor Pro menu link
	const link = document.querySelector('a[href="admin.php?page=go_elementor_pro"]');
	if (link) {
		link.href = affiliateUrl;
		link.target = '_blank';
		link.style.color = linkColor;
	}

	// 2. Go Pro button
	const link2 = document.querySelector('a.elementor-plugins-gopro');
	if (link2) {
		link2.href = affiliateUrl;
		link2.target = '_blank';
		link2.style.color = linkColor;
	}

	// 3. Overview Go Pro
	const link3 = document.querySelector('li.e-overview__go-pro a');
	if (link3) {
		link3.href = affiliateUrl;
		link3.target = '_blank';
	}

	// 4. Top-level menu Go Pro
	const link4Parent = document.querySelector('.toplevel_page_elementor > ul > li:last-child > a');
	const link4 = link4Parent && link4Parent.querySelector('.dashicons-star-filled') ? link4Parent : null;
	if (link4) {
		link4.href = affiliateUrl;
		link4.target = '_blank';
	}

	// 5. Maybe other links
	var links = document.querySelectorAll('a[href*="elementor.com/pro"], a[href*="go.elementor.com"]');
	links.forEach(function (link) {
		link.href = affiliateUrl;
	});

});