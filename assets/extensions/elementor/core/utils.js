/**
 * VLT Elementor Utilities
 *
 * Common utility functions used across all Elementor modules
 */

// Resize handlers registry
const resizeHandlers = [];
let resizeTimeout;

/**
 * Handle resize events with debouncing
 * @param {Event} event - Resize event
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

/**
 * Check if device is mobile
 * @returns {boolean} True if mobile device
 */
export function isMobileDevice() {
	return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

/**
 * Debounced resize handler with callback registry
 * If callback is provided, registers it for resize events
 * If no callback, triggers resize event manually
 *
 * @param {Function|undefined} callback - Function to call on resize
 */
export function debounceResize(callback) {
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

/**
 * Initialize resize event handlers
 * Should be called once when the application starts
 */
export function initResizeHandlers() {
	window.addEventListener('load', handleResize);
	window.addEventListener('resize', handleResize);
	window.addEventListener('orientationchange', handleResize);
}

/**
 * Get computed style value
 * @param {HTMLElement} element - DOM element
 * @param {string} property - CSS property name
 * @returns {string} Computed style value
 */
export function getComputedStyle(element, property) {
	return window.getComputedStyle(element).getPropertyValue(property);
}

/**
 * Parse data attribute to appropriate type
 * @param {string} value - String value to parse
 * @returns {*} Parsed value (number, boolean, or string)
 */
export function parseDataAttribute(value) {
	if (value === 'true') return true;
	if (value === 'false') return false;
	if (value === 'null') return null;
	if (value === 'undefined') return undefined;
	if (!isNaN(value) && value !== '') return parseFloat(value);
	return value;
}

/**
 * Throttle function execution
 * @param {Function} func - Function to throttle
 * @param {number} limit - Time limit in milliseconds
 * @returns {Function} Throttled function
 */
export function throttle(func, limit) {
	let inThrottle;
	return function(...args) {
		if (!inThrottle) {
			func.apply(this, args);
			inThrottle = true;
			setTimeout(() => inThrottle = false, limit);
		}
	};
}

/**
 * Debounce function execution
 * @param {Function} func - Function to debounce
 * @param {number} wait - Wait time in milliseconds
 * @returns {Function} Debounced function
 */
export function debounce(func, wait) {
	let timeout;
	return function(...args) {
		clearTimeout(timeout);
		timeout = setTimeout(() => func.apply(this, args), wait);
	};
}
