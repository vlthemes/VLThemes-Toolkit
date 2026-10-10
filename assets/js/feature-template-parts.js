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

	/* ========================================
	 * Template Settings meta box: display/exclude rules repeater
	 * ======================================== */
	const typeSelect = document.getElementById('vlt_tp_template_type');
	const rulesWrap = document.querySelector('.vlt-tp-rules-wrap');

	function toggleRulesWrap() {
		if (!typeSelect || !rulesWrap) {
			return;
		}
		const hiddenFor = (rulesWrap.dataset.rulesHiddenFor || '').split(',');
		rulesWrap.style.display = hiddenFor.includes(typeSelect.value) ? 'none' : '';
	}

	if (typeSelect) {
		typeSelect.addEventListener('change', toggleRulesWrap);
		toggleRulesWrap();
	}

	function toggleSpecifics(row) {
		const select = row.querySelector('.vlt-tp-rule-select');
		const cell = row.querySelector('.vlt-tp-specifics-cell');
		if (select && cell) {
			cell.style.display = 'specifics' === select.value ? '' : 'none';
		}
	}

	document.querySelectorAll('.vlt-tp-repeater').forEach(function (repeater) {
		repeater.querySelectorAll('.vlt-tp-rule-row').forEach(toggleSpecifics);
	});

	document.body.addEventListener('change', function (event) {
		if (event.target.classList.contains('vlt-tp-rule-select')) {
			toggleSpecifics(event.target.closest('.vlt-tp-rule-row'));
		}
	});

	document.body.addEventListener('click', function (event) {
		// Add a new rule row
		const addButton = event.target.closest('.vlt-tp-add-rule');
		if (addButton) {
			const field = addButton.closest('.vlt-tp-field');
			const repeater = field.querySelector('.vlt-tp-repeater');
			const template = field.querySelector('.vlt-tp-repeater-template');
			const nextIndex = repeater.querySelectorAll('.vlt-tp-rule-row').length;

			const html = template.innerHTML.replace(/__INDEX__/g, String(nextIndex));
			const wrapper = document.createElement('div');
			wrapper.innerHTML = html;

			const newRow = wrapper.querySelector('.vlt-tp-rule-row');
			if (newRow) {
				repeater.appendChild(newRow);
				toggleSpecifics(newRow);
			}
			return;
		}

		// Remove a rule row
		const removeButton = event.target.closest('.vlt-tp-remove-rule');
		if (removeButton) {
			const row = removeButton.closest('.vlt-tp-rule-row');
			const repeater = row ? row.closest('.vlt-tp-repeater') : null;
			if (row) {
				row.remove();
			}
			if (repeater) {
				reindexRows(repeater);
			}
		}
	});

	function reindexRows(repeater) {
		repeater.querySelectorAll('.vlt-tp-rule-row').forEach(function (row, index) {
			row.querySelectorAll('[name]').forEach(function (field) {
				field.name = field.name.replace(/\[\d+\]/, '[' + index + ']');
			});
			const picker = row.querySelector('.vlt-tp-picker');
			if (picker) {
				picker.dataset.fieldName = picker.dataset.fieldName.replace(/\[\d+\]/, '[' + index + ']');
			}
		});
	}

	/* ========================================
	 * Template Settings meta box: "Specific Target" AJAX picker
	 * ======================================== */
	let searchTimeout = null;

	function closeAllResultLists(exceptPicker) {
		document.querySelectorAll('.vlt-tp-picker-results').forEach(function (list) {
			if (list.closest('.vlt-tp-picker') !== exceptPicker) {
				list.hidden = true;
				list.innerHTML = '';
			}
		});
	}

	function addPickerChip(picker, id, label) {
		const chips = picker.querySelector('.vlt-tp-picker-chips');
		if (chips.querySelector('.vlt-tp-picker-chip[data-id="' + id + '"]')) {
			return;
		}

		const chip = document.createElement('span');
		chip.className = 'vlt-tp-picker-chip';
		chip.dataset.id = String(id);
		chip.textContent = label + ' ';

		const input = document.createElement('input');
		input.type = 'hidden';
		input.name = picker.dataset.fieldName;
		input.value = String(id);
		chip.appendChild(input);

		const remove = document.createElement('button');
		remove.type = 'button';
		remove.className = 'vlt-tp-picker-chip-remove';
		remove.setAttribute('aria-label', 'Remove');
		remove.textContent = '×';
		chip.appendChild(remove);

		chips.appendChild(chip);
	}

	document.body.addEventListener('input', function (event) {
		if (!event.target.classList.contains('vlt-tp-picker-search')) {
			return;
		}

		const input = event.target;
		const picker = input.closest('.vlt-tp-picker');
		const list = picker.querySelector('.vlt-tp-picker-results');
		const query = input.value.trim();

		clearTimeout(searchTimeout);

		if (query.length < 2) {
			list.hidden = true;
			list.innerHTML = '';
			return;
		}

		searchTimeout = setTimeout(function () {
			if (typeof tp_admin_data === 'undefined') {
				return;
			}

			const url = tp_admin_data.ajax_url +
				'?action=vlt_tp_search_specifics' +
				'&nonce=' + encodeURIComponent(tp_admin_data.search_nonce) +
				'&search=' + encodeURIComponent(query);

			fetch(url, { credentials: 'same-origin' })
				.then(function (response) { return response.json(); })
				.then(function (response) {
					list.innerHTML = '';

					if (!response || !response.success || !response.data || !response.data.length) {
						list.hidden = true;
						return;
					}

					response.data.forEach(function (item) {
						const li = document.createElement('li');
						li.className = 'vlt-tp-picker-result';
						li.dataset.id = String(item.id);
						li.dataset.label = item.label;
						li.textContent = item.label;
						list.appendChild(li);
					});

					list.hidden = false;
				})
				.catch(function () {
					list.hidden = true;
				});
		}, 300);
	});

	document.body.addEventListener('click', function (event) {
		const result = event.target.closest('.vlt-tp-picker-result');
		if (result) {
			const picker = result.closest('.vlt-tp-picker');
			addPickerChip(picker, result.dataset.id, result.dataset.label);

			const list = picker.querySelector('.vlt-tp-picker-results');
			const search = picker.querySelector('.vlt-tp-picker-search');
			list.hidden = true;
			list.innerHTML = '';
			search.value = '';
			return;
		}

		const chipRemove = event.target.closest('.vlt-tp-picker-chip-remove');
		if (chipRemove) {
			chipRemove.closest('.vlt-tp-picker-chip').remove();
			return;
		}

		const picker = event.target.closest('.vlt-tp-picker');
		closeAllResultLists(picker);
	});
});
