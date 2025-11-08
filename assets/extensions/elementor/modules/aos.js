/**
 * AOS (Animate On Scroll) Module
 * Handles scroll-based animations using AOS library
 *
 * @global AOS - External library loaded via wp_enqueue_script
 */

/* global AOS */

export default class AOSModule {
	constructor({ isMobileDevice, debounceResize }) {
		this.isMobileDevice = isMobileDevice;
		this.debounceResize = debounceResize;
		this.initialized = false;
	}

	/**
	 * Initialize AOS animations
	 */
	init() {

		// Check if AOS library is loaded
		if (typeof AOS === 'undefined') {
			console.warn('AOS library not loaded');
			return;
		}

		// Skip AOS on mobile devices (performance optimization)
		if (this.isMobileDevice()) {
			console.info('AOS disabled on mobile device');
			return;
		}

		// Initialize AOS with configuration
		AOS.init({
			offset: 150,
			duration: 1000,
			easing: 'ease',
			once: true,
			mirror: false,
			// Disable animations if user prefers reduced motion
			disable: function () {
				return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			}
		});

		this.initialized = true;
		console.info('AOS initialized');
	}

	/**
	 * Refresh AOS calculations
	 */
	refresh() {
		if (this.initialized && typeof AOS !== 'undefined') {
			AOS.refresh();
		}
	}

	/**
	 * Hard refresh AOS (recalculate everything)
	 */
	refreshHard() {
		if (this.initialized && typeof AOS !== 'undefined') {
			AOS.refreshHard();
		}
	}

	/**
	 * Setup AOS refresh handlers for dynamic content
	 */
	setupRefreshHandlers() {
		// Debounced resize refresh
		this.debounceResize(() => {
			this.refresh();
		});

		// Refresh AOS when new items are loaded (Visual Portfolio Grid)
		document.addEventListener('endLoadingNewItems.vpf', () => {
			this.refreshHard();
		});

		// Wait for Elementor frontend to be fully initialized
		window.addEventListener('elementor/frontend/init', () => {
			if (!window.elementorFrontend?.hooks) return;

			// Hook into every widget/section/column that becomes ready
			elementorFrontend.hooks.addAction('frontend/element_ready/global', (scope) => {
				this.refresh();
			});
		});

	}
}
