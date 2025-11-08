import AOSModule from './modules/aos.js';
import JarallaxModule from './modules/jarallax.js';
import ElementParallaxModule from './modules/element-parallax.js';
import ContainerExtensionsModule from './modules/container-extensions.js';

(function () {
	'use strict';

	// ===================================
	// UTILITY FUNCTIONS
	// ===================================

	// Resize handlers registry
	const resizeHandlers = [];
	let resizeTimeout;

	/**
	 * Handle resize events with debouncing
	 * @param {Event} event
	 */
	function handleResize(event) {
		if (resizeHandlers.length) {
			clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(() => {
				resizeHandlers.forEach(handler => {
					if (typeof handler === 'function') {
						handler(event);
					}
				});
			}, 250);
		}
	}

	// Register resize handlers
	window.addEventListener('load', handleResize);
	window.addEventListener('resize', handleResize);
	window.addEventListener('orientationchange', handleResize);

	/**
	 * Check if device is mobile
	 * @returns {boolean}
	 */
	function isMobileDevice() {
		return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
	}

	/**
	 * Debounced resize handler with callback registry
	 * If callback is provided, registers it for resize events
	 * If no callback, triggers resize event manually
	 *
	 * @param {Function|undefined} callback - Function to call on resize
	 */
	function debounceResize(callback) {
		if (typeof callback === 'function') {
			// Register callback if not already registered
			if (!resizeHandlers.includes(callback)) {
				resizeHandlers.push(callback);
			}
		} else {
			// Trigger resize event manually
			if (typeof Event === 'function') {
				window.dispatchEvent(new Event('resize'));
			} else if (document.createEvent) {
				const evt = document.createEvent('UIEvents');
				evt.initUIEvent('resize', true, false, window, 0);
				window.dispatchEvent(evt);
			}
		}
	}

	// ===================================
	// MODULES
	// ===================================

	// Initialize modules
	const aosModule = new AOSModule({ isMobileDevice, debounceResize });
	const jarallaxModule = new JarallaxModule({ isMobileDevice, debounceResize });
	const elementParallaxModule = new ElementParallaxModule({ isMobileDevice, debounceResize });
	const containerExtensionsModule = new ContainerExtensionsModule({ debounceResize });

	// ===================================
	// INITIALIZATION
	// ===================================

	/**
	 * Site loaded event
	 * Fires when all content is loaded and ready
	 */
	function onSiteLoaded() {
		// Initialize AOS
		aosModule.init();
		aosModule.setupRefreshHandlers();

		// Initialize Jarallax
		jarallaxModule.init();
		jarallaxModule.setupRefreshHandlers();

		// Initialize Element Parallax
		elementParallaxModule.init();
		elementParallaxModule.setupRefreshHandlers();

		// Initialize Container Extensions
		containerExtensionsModule.init();
		containerExtensionsModule.setupRefreshHandlers();

		document.dispatchEvent(new CustomEvent('vlt.site-loaded'));

		console.info('VLT Framework initialized');
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', onSiteLoaded);
	} else {
		// DOM already loaded
		onSiteLoaded();
	}

})();