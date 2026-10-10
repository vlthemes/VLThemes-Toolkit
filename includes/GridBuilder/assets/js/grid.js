/* ========================================
 * Grid Builder: AJAX filters and pagination (includes/GridBuilder/Ajax.php)
 *
 * The grid, its filter and its pagination are separate elements placed by their own shortcodes.
 * A control ([data-vlt-grid-for="{layout}"][data-vlt-grid-instance="{n}"]) drives the n-th grid of that
 * layout in document order. Every grid keeps its own state, so several grids on a page never mix.
 * Only the newest request of a grid is applied: older ones are aborted and their late answers ignored.
 *
 * Events — fired on the grid element and bubbling, so listen on document to catch every grid:
 *
 *     vlt-grid:init         the grid is ready (also for grids added later with vltGridBuilder.init())
 *     vlt-grid:before-load  a filter / page / load-more request starts; cancelable (event.preventDefault() stops it)
 *     vlt-grid:updated      new items are in the DOM — re-run sliders, lightboxes, lazy loaders… on detail.items
 *     vlt-grid:filtered     after "updated", when a filter changed
 *     vlt-grid:paginated    after "updated", for page numbers and load more
 *     vlt-grid:error        the request failed
 *
 * detail: { grid, layout, layoutSettings, page, maxPages, term, action ('filter' | 'page' | 'more'), append, items }
 * (items / append on updated, filtered, paginated; before-load has the requested term and nextPage). Example:
 *
 *     document.addEventListener( 'vlt-grid:updated', ( event ) => myLightbox( event.detail.items ) );
 *     jQuery( document ).on( 'vlt-grid:updated', ( event ) => myLightbox( event.originalEvent.detail.items ) );
 * ======================================== */
(function () {
	'use strict';

	const settings = window.vltGridBuilder || {};
	const states = new WeakMap();

	function parse(html) {
		const template = document.createElement('template');
		template.innerHTML = html;
		return Array.from(template.content.children);
	}

	function init(root) {
		if (states.has(root)) {
			return states.get(root);
		}

		let config;

		try {
			config = JSON.parse(root.dataset.vltGrid || '{}');
		} catch {
			return null;
		}

		const state = {
			root: root,
			layout: String(config.layout),
			layoutSettings: config.layoutSettings || {},
			page: config.page || 1,
			maxPages: config.maxPages || 1,
			seed: config.seed || 0,
			term: 0,
			request: 0,
			controller: null,
		};

		states.set(root, state);
		emit(state, 'init', {});
		return state;
	}

	/**
	 * Fire a grid event (see the header); returns false when a listener cancelled it
	 */
	function emit(state, name, extra, cancelable) {
		return state.root.dispatchEvent(new CustomEvent('vlt-grid:' + name, {
			bubbles: true,
			cancelable: !!cancelable,
			detail: Object.assign({
				grid: state.root,
				layout: state.layout,
				layoutSettings: state.layoutSettings,
				page: state.page,
				maxPages: state.maxPages,
				term: state.term,
			}, extra),
		}));
	}

	/**
	 * Grid a control belongs to
	 */
	function gridOf(control) {
		const grids = document.querySelectorAll('[data-vlt-grid][data-vlt-grid-layout="' + window.CSS.escape(control.dataset.vltGridFor) + '"]');
		const grid = grids[(parseInt(control.dataset.vltGridInstance, 10) || 1) - 1];

		return grid ? init(grid) : null;
	}

	/**
	 * Controls (filters, paginations) of a grid
	 */
	function controlsOf(state, selector) {
		return Array.from(document.querySelectorAll(selector + '[data-vlt-grid-for="' + window.CSS.escape(state.layout) + '"]')).filter(function (control) {
			return gridOf(control) === state;
		});
	}

	function onClick(event) {
		const control = event.target.closest('[data-vlt-grid-for]');
		const state = control && gridOf(control);

		if (!state) {
			return;
		}

		const filter = event.target.closest('[data-vlt-grid-term]');
		const page = event.target.closest('[data-vlt-grid-page]');
		const more = event.target.closest('[data-vlt-grid-more]');

		// Theme markup may use links: the script handles them, the browser doesn't navigate
		if (filter || page || more) {
			event.preventDefault();
		}

		if (filter) {
			setTerm(state, parseInt(filter.dataset.vltGridTerm, 10) || 0);
		} else if (page) {
			load(state, { action: 'page', page: parseInt(page.dataset.vltGridPage, 10) || 1, append: false, scroll: true });
		} else if (more && state.page < state.maxPages) {
			load(state, { action: 'more', page: state.page + 1, append: true, button: more });
		}
	}

	function onChange(event) {
		const select = event.target.closest('[data-vlt-grid-filter]');
		const control = select && select.closest('[data-vlt-grid-for]');
		const state = control && gridOf(control);

		if (state) {
			setTerm(state, parseInt(select.value, 10) || 0);
		}
	}

	/**
	 * Change the filter: every filter of the grid shows it, and the grid goes back to page 1
	 */
	function setTerm(state, term) {
		if (!emit(state, 'before-load', { action: 'filter', term: term, nextPage: 1 }, true)) {
			return;
		}

		state.term = term;

		controlsOf(state, '.vlt-gb__filters').forEach(function (filters) {
			filters.querySelectorAll('[data-vlt-grid-term]').forEach(function (button) {
				const active = (parseInt(button.dataset.vltGridTerm, 10) || 0) === term;
				button.classList.toggle('is-active', active);
				button.setAttribute('aria-pressed', active ? 'true' : 'false');
			});

			filters.querySelectorAll('[data-vlt-grid-filter]').forEach(function (select) {
				select.value = String(term);
			});
		});

		load(state, { action: 'filter', page: 1, append: false, checked: true });
	}

	function setLoading(state, loading) {
		[state.root].concat(controlsOf(state, '.vlt-gb__pagination')).forEach(function (element) {
			element.classList.toggle('is-loading', loading);

			if (loading) {
				element.setAttribute('aria-busy', 'true');
			} else {
				element.removeAttribute('aria-busy');
			}
		});
	}

	function load(state, options) {
		// Filters ask before changing their buttons; pages ask here
		if (!options.checked && !emit(state, 'before-load', { action: options.action, nextPage: options.page }, true)) {
			return;
		}

		if (state.controller) {
			state.controller.abort();
		}

		const controller = new AbortController();
		const request = ++state.request;
		const button = options.button;

		state.controller = controller;
		setLoading(state, true);

		if (button) {
			button.disabled = true;
			button.classList.add('is-loading');

			// Loading text from data-vlt-grid-loading; the label comes back if the request fails
			if (button.dataset.vltGridLoading) {
				button.dataset.vltGridLabel = button.textContent;
				button.textContent = button.dataset.vltGridLoading;
			}
		}

		const body = new FormData();
		body.append('action', settings.action);
		body.append('layout', state.layout);
		body.append('page', options.page);
		body.append('term', state.term);
		body.append('seed', state.seed);

		// Editor preview: its unsaved settings (accepted only for editors of the layout, with the nonce)
		if (settings.preview) {
			body.append('preview', '1');
			body.append('config', settings.preview.config);
			body.append('nonce', settings.preview.nonce);
		}

		fetch(settings.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin', signal: controller.signal })
			.then(function (response) {
				return response.json();
			})
			.then(function (response) {
				if (request !== state.request) {
					return;
				}

				if (!response || !response.success) {
					throw new Error('vlt-grid: bad response');
				}

				render(state, response.data, options);
			})
			.catch(function (error) {
				if ('AbortError' === error.name || request !== state.request) {
					return;
				}

				showError(state.root);
				emit(state, 'error', { action: options.action });

				if (button) {
					button.disabled = false;
					button.classList.remove('is-loading');

					if (button.dataset.vltGridLabel) {
						button.textContent = button.dataset.vltGridLabel;
					}
				}
			})
			.finally(function () {
				if (request === state.request) {
					state.controller = null;
					setLoading(state, false);
				}
			});
	}

	function render(state, data, options) {
		const root = state.root;
		const items = root.querySelector('.vlt-gb__items');
		let nodes = parse(data.items);

		if (options.append) {
			// Past the end the server answers with the "no items" message — nothing to add then
			nodes = nodes.filter(function (node) {
				return !node.classList.contains('vlt-gb__empty');
			});
			items.append.apply(items, nodes);
		} else {
			items.replaceChildren.apply(items, nodes);
		}

		controlsOf(state, '.vlt-gb__pagination').forEach(function (pagination) {
			pagination.innerHTML = data.pagination;
		});

		const error = root.querySelector('.vlt-gb__error');

		if (error) {
			error.remove();
		}

		state.page = data.page;
		state.maxPages = data.maxPages;

		if (options.scroll) {
			// Page numbers replace the items: bring the grid back into view and move focus to it
			if (root.getBoundingClientRect().top < 0) {
				const smooth = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
				root.scrollIntoView({ behavior: smooth ? 'smooth' : 'auto', block: 'start' });
			}

			items.setAttribute('tabindex', '-1');
			items.focus({ preventScroll: true });
		}

		const detail = { action: options.action, append: !!options.append, items: nodes };

		emit(state, 'updated', detail);
		emit(state, 'filter' === options.action ? 'filtered' : 'paginated', detail);
	}

	function showError(root) {
		if (root.querySelector('.vlt-gb__error')) {
			return;
		}

		const error = document.createElement('p');
		error.className = 'vlt-gb__error';
		error.setAttribute('role', 'alert');
		error.textContent = (settings.i18n && settings.i18n.error) || 'Error';
		root.append(error);
	}

	// Controls can be anywhere on the page: one delegated listener for all of them, bound once
	if (!settings.bound) {
		settings.bound = true;
		document.addEventListener('click', onClick);
		document.addEventListener('change', onChange);
	}

	/**
	 * Initialise grids in a container — also for grids inserted later (vltGridBuilder.init(element))
	 */
	settings.init = function (context) {
		(context || document).querySelectorAll('[data-vlt-grid]').forEach(init);
	};

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', function () {
			settings.init();
		});
	} else {
		settings.init();
	}
})();
