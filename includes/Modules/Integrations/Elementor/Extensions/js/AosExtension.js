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

			$(window).on('vlt:site:loaded', () => this.initAOS());
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

			if (this.isMobile()) {
				console.info('AOS disabled on mobile');
				return;
			}

			AOS.init({
				offset: 150,
				duration: 1000,
				easing: 'ease',
				once: true,
				mirror: false,
				disable: () => window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches
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
	}

	new AosExtension();

})(jQuery);
