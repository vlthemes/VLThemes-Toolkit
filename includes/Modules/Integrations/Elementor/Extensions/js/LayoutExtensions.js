(function ($) {
	'use strict';

	class LayoutExtensions {
		constructor() {
			this.initialized = false;
			this.resizeCallbacks = [];
			this.resizeTimer = null;
			this.init();
		}

		init() {
			$(window).on('load resize orientationchange', () => this.triggerResize());
			$(document).ready(() => {
				this.setupHandlers();
				this.initialized = true;
				console.info('Layout Extensions initialized');
			});
		}

		triggerResize() {
			clearTimeout(this.resizeTimer);
			this.resizeTimer = setTimeout(() => {
				this.resizeCallbacks.forEach(cb => cb());
			}, 250);
		}

		debounceResize(cb) {
			if (typeof cb === 'function' && !this.resizeCallbacks.includes(cb)) {
				this.resizeCallbacks.push(cb);
			}
		}

		getCurrentDevice() {
			return (typeof elementorFrontend !== 'undefined' && elementorFrontend.getCurrentDeviceMode)
				? elementorFrontend.getCurrentDeviceMode()
				: 'desktop';
		}

		parseData(json) {
			try {
				return JSON.parse(json || '[]');
			} catch (e) {
				return [];
			}
		}

		stickyContainerInit(el) {
			if (!el.classList.contains('has-sticky-column')) return;

			const parent = el.closest('.e-parent');
			if (parent) parent.classList.add('sticky-parent');

			const children = [...el.children].filter(c => c.classList.contains('elementor-element'));
			if (children.length) {
				const wrapper = document.createElement('div');
				wrapper.className = 'sticky-column';
				el.insertBefore(wrapper, children[0]);
				children.forEach(child => wrapper.appendChild(child));
			}
		}

		paddingToContainerInit(el) {
			if (!el.classList.contains('has-padding-block-to-left') && !el.classList.contains('has-padding-block-to-right')) return;

			const handler = () => {
				const reset = this.parseData(el.getAttribute('data-reset-padding-to-container-on-devices'));
				if (reset.includes(this.getCurrentDevice())) {
					el.style.paddingLeft = el.style.paddingRight = '';
					return;
				}

				let fake = document.querySelector('.container');
				if (!fake) {
					fake = document.createElement('div');
					fake.className = 'container';
					document.body.appendChild(fake);
				}

				const offset = fake.getBoundingClientRect().left + parseFloat(getComputedStyle(fake).paddingLeft) || 0;
				if (el.classList.contains('has-padding-block-to-left')) el.style.paddingLeft = offset + 'px';
				if (el.classList.contains('has-padding-block-to-right')) el.style.paddingRight = offset + 'px';
			};

			handler();
			this.debounceResize(handler);
		}

		equalHeightContainerInit(el) {
			if (!el.classList.contains('has-equal-height-block-yes')) return;

			const handler = () => {
				const reset = this.parseData(el.getAttribute('data-reset-equal-height-on-devices'));
				if (reset.includes(this.getCurrentDevice())) {
					el.querySelectorAll('.elementor-widget-container > div').forEach(item => item.style.height = '');
					return;
				}

				const items = el.querySelectorAll('.elementor-widget-container > div');
				items.forEach(item => item.style.height = '');

				let max = 0;
				items.forEach(item => {
					if (item.offsetHeight > max) max = item.offsetHeight;
				});

				items.forEach(item => item.style.height = max + 'px');
			};

			handler();
			this.debounceResize(handler);
		}

		stretchContainerInit(el) {
			if (!el.classList.contains('has-stretch-block-to-left') &&
				!el.classList.contains('has-stretch-block-to-right') &&
				!el.classList.contains('has-stretch-block-to-container')) return;

			const handler = () => {
				const reset = this.parseData(el.getAttribute('data-reset-on-devices'));
				if (reset.includes(this.getCurrentDevice())) {
					const inner = el.querySelector(':scope > *');
					if (inner) {
						inner.style.marginLeft = inner.style.marginRight = inner.style.width = inner.style.maxWidth = '';
					}
					return;
				}

				const inner = el.querySelector(':scope > *');
				if (!inner) return;

				const rect = el.getBoundingClientRect();
				const winW = window.innerWidth;
				const left = rect.left;
				const right = winW - rect.right;

				if (el.classList.contains('has-stretch-block-to-left')) {
					inner.style.marginLeft = -Math.round(left) + 'px';
					inner.style.width = Math.round(rect.width + left) + 'px';
					inner.style.maxWidth = 'unset';
				} else if (el.classList.contains('has-stretch-block-to-right')) {
					inner.style.marginRight = -Math.round(right) + 'px';
					inner.style.width = Math.round(rect.width + right) + 'px';
					inner.style.maxWidth = 'unset';
				} else if (el.classList.contains('has-stretch-block-to-container')) {
					const container = document.querySelector('.vlt-fake-container') || document.querySelector('.container');
					if (container) {
						const cRect = container.getBoundingClientRect();
						const cStyle = getComputedStyle(container);
						const pl = parseFloat(cStyle.paddingLeft) || 0;
						const pr = parseFloat(cStyle.paddingRight) || 0;
						const offsetL = rect.left - cRect.left - pl;
						const offsetR = cRect.right - rect.right - pr;
						inner.style.marginLeft = -Math.round(offsetL) + 'px';
						inner.style.marginRight = -Math.round(offsetR) + 'px';
						inner.style.width = Math.round(rect.width + offsetL + offsetR) + 'px';
						inner.style.maxWidth = 'unset';
					}
				}

				// Fix visual portfolio if inside
				if ($.fn.vpf && $('.vp-portfolio').length) {
					setTimeout(function () {
						$('.vp-portfolio').vpf();
					}, 150);
				}

			};

			handler();
			this.debounceResize(handler);
		}

		setupHandlers() {
			// For frontend and editor preview
			$(window).on('elementor/frontend/init', () => {
				if (window.elementorFrontend?.hooks) {
					elementorFrontend.hooks.addAction('frontend/element_ready/global', scope => {
						const el = scope instanceof HTMLElement ? scope : scope[0];
						if (el) {
							this.stretchContainerInit(el);
							this.paddingToContainerInit(el);
							this.stickyContainerInit(el);
							this.equalHeightContainerInit(el);
						}
					});
				}
			});

			// For Elementor editor
			if (window.elementor) {
				elementor.on('preview:loaded', () => {
					if (window.elementorFrontend?.hooks) {
						elementorFrontend.hooks.addAction('frontend/element_ready/global', scope => {
							const el = scope instanceof HTMLElement ? scope : scope[0];
							if (el) {
								this.stretchContainerInit(el);
								this.paddingToContainerInit(el);
								this.stickyContainerInit(el);
								this.equalHeightContainerInit(el);
							}
						});
					}
				});
			}
		}
	}

	new LayoutExtensions();

})(jQuery);
