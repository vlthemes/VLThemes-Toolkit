document.addEventListener('DOMContentLoaded', function () {
	'use strict';

	/* ========================================
	 * Add "View All Templates" button
	 * ======================================== */
	const addNewButton = document.querySelector('.page-title-action');
	if (addNewButton && typeof tp_admin_data !== 'undefined') {
		// Create the new button.
		const customButton = document.createElement('a');
		customButton.href = tp_admin_data.tp_edit_url;
		customButton.textContent = tp_admin_data.tp_view_all_text;
		customButton.className = 'page-title-action';
		customButton.style.marginLeft = '10px';
		addNewButton.insertAdjacentElement('afterend', customButton);
	}
});
