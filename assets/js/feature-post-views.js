/* ========================================
 * Post Views: count a view once per browser session (Features/PostViews.php)
 * ======================================== */
(function () {
	'use strict';

	if (typeof vltPostViews === 'undefined' || !vltPostViews.postId) {
		return;
	}

	const key = 'vlt-view:' + vltPostViews.postId;

	try {
		if (sessionStorage.getItem(key)) {
			return;
		}
		sessionStorage.setItem(key, '1');
	} catch (e) {}

	const body = new FormData();
	body.append('action', vltPostViews.action);
	body.append('post_id', vltPostViews.postId);

	fetch(vltPostViews.ajaxUrl, { method: 'POST', body, credentials: 'same-origin', keepalive: true }).catch(function () {});
})();
