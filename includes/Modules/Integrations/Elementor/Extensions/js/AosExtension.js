(function ($) {
	'use strict';

	class AosExtension {
		constructor() {
			this.initialized = false;
			this.resizeCallbacks = [];
			this.resizeTimer = null;
			this.init();
		}

		init() {
			$(window).on('resize orientationchange load', () => this.triggerResize());
			$(() => {
				this.setupHandlers();
				this.initAOS();
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

		isMobile() {
			return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
		}

		initAOS() {
			if (typeof AOS === 'undefined') {
				console.warn('AOS not loaded');
				return;
			}

			AOS.init({
				disable: () => this.isMobile(),
				offset: 200,
				duration: 1000,
				easing: 'ease',
				once: true,
				startEvent: 'vlt:site:loaded'
			});

			this.initialized = true;
			console.info('AOS Extension initialized');
		}

		refresh() {
			if (this.initialized) {
				AOS.refresh();
			}
		}

		refreshHard() {
			if (this.initialized) {
				AOS.refreshHard();
			}
		}

		setupHandlers() {
			this.debounceResize(() => this.refresh());

			$(document).on('endLoadingNewItems.vpf', () => this.refreshHard());

			$(window).on('elementor/frontend/init', () => {
				if (window.elementorFrontend?.hooks) {
					elementorFrontend.hooks.addAction('frontend/element_ready/global', () => this.refresh());
				}
			});
		}

		destroy() {
			// Clear resize timer and callbacks
			clearTimeout(this.resizeTimer);
			this.resizeCallbacks = [];

			// Remove event listeners
			$(window).off('resize orientationchange load');
			$(document).off('endLoadingNewItems.vpf');
			$(window).off('elementor/frontend/init');

			// Destroy AOS instance if available
			if (typeof AOS !== 'undefined' && this.initialized) {
				// Remove all AOS attributes from elements
				document.querySelectorAll('[data-aos]').forEach(el => {
					el.classList.remove('aos-init', 'aos-animate');
					el.removeAttribute('data-aos');
				});
			}

			this.initialized = false;
			console.info('AOS Extension destroyed');
		}
	}

	// Create instance and expose globally
	window.aosExtension = new AosExtension();

})(jQuery);
