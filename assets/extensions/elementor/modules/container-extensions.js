/**
 * Container Extensions Module
 * Handles sticky columns, stretch containers, padding to container, and equal height
 * For Elementor containers
 */

export default class ContainerExtensionsModule {
	constructor({ debounceResize }) {
		this.debounceResize = debounceResize;
		this.initialized = false;
	}

	/**
	 * Initialize all container extensions
	 */
	init() {
		// This module only works with Elementor
		if (typeof elementorFrontend === 'undefined') {
			return;
		}

		this.initialized = true;
		console.info('Container Extensions initialized');
	}

	/**
	 * Initialize sticky column
	 * @param {HTMLElement} element
	 */
	stickyContainerInit(element) {
		if (!element.classList.contains('has-sticky-column')) {
			return;
		}

		// Find parent with class .e-parent
		const parent = element.closest('.e-parent');
		if (parent) {
			parent.classList.add('sticky-parent');
		}

		// Wrap direct children with .elementor-element class
		const children = Array.from(element.children).filter(child =>
			child.classList.contains('elementor-element')
		);

		if (children.length > 0) {
			const wrapper = document.createElement('div');
			wrapper.className = 'sticky-column';

			// Insert wrapper before first child
			element.insertBefore(wrapper, children[0]);

			// Move children into wrapper
			children.forEach(child => {
				wrapper.appendChild(child);
			});
		}
	}

	/**
	 * Get current Elementor device mode
	 * @returns {string}
	 */
	getCurrentDevice() {
		if (typeof elementorFrontend !== 'undefined' && elementorFrontend.getCurrentDeviceMode) {
			return elementorFrontend.getCurrentDeviceMode();
		}
		return 'desktop';
	}

	/**
	 * Parse JSON data attribute safely
	 * @param {string} data
	 * @returns {Array}
	 */
	parseDataAttribute(data) {
		try {
			return JSON.parse(data) || [];
		} catch (e) {
			return [];
		}
	}

	/**
	 * Initialize padding to container
	 * @param {HTMLElement} element
	 */
	paddingToContainerInit(element) {
		const hasPaddingLeft = element.classList.contains('has-padding-block-to-left');
		const hasPaddingRight = element.classList.contains('has-padding-block-to-right');

		if (!hasPaddingLeft && !hasPaddingRight) {
			return;
		}

		const resizeHandler = () => {
			const dataResetOnDevices = element.getAttribute('data-reset-padding-to-container-on-devices') || '[]';
			const resetOnDevice = this.parseDataAttribute(dataResetOnDevices);
			const currentDevice = this.getCurrentDevice();

			// Find fake container for offset calculation
			const fakeContainer = document.querySelector('.vlt-fake-container');
			const offset = fakeContainer
				? fakeContainer.getBoundingClientRect().left + parseFloat(getComputedStyle(fakeContainer).paddingLeft)
				: 0;

			// Reset on specific devices
			if (resetOnDevice.includes(currentDevice)) {
				element.style.paddingLeft = '';
				element.style.paddingRight = '';
				return;
			}

			// Apply padding
			if (hasPaddingLeft) {
				element.style.paddingLeft = offset + 'px';
			}
			if (hasPaddingRight) {
				element.style.paddingRight = offset + 'px';
			}
		};

		resizeHandler();
		this.debounceResize(resizeHandler);
	}

	/**
	 * Initialize equal height
	 * @param {HTMLElement} element
	 */
	equalHeightContainerInit(element) {

		if (!element.classList.contains('has-equal-height-block-yes')) {
			return;
		}
		const resizeHandler = () => {
			const dataResetOnDevices = element.getAttribute('data-reset-equal-height-on-devices') || '[]';
			const resetOnDevice = this.parseDataAttribute(dataResetOnDevices);
			const currentDevice = this.getCurrentDevice();

			const items = element.querySelectorAll('.elementor-widget-container > div');

			if (items.length === 0) {
				return;
			}

			if (resetOnDevice.includes(currentDevice)) {
				items.forEach(item => {
					item.style.height = '';
					item.style.minHeight = '';
				});
				return;
			}

			items.forEach(item => {
				item.style.height = '';
				item.style.minHeight = '';
			});

			let maxHeight = 0;
			items.forEach(item => {
				const h = item.offsetHeight;
				if (h > maxHeight) maxHeight = h;
			});

			items.forEach(item => {
				item.style.height = maxHeight + 'px';
			});
		};

		resizeHandler();
		this.debounceResize(resizeHandler);
	}

	/**
	 * Initialize stretch container
	 * @param {HTMLElement} element
	 */
	stretchContainerInit(element) {
		const hasStretchLeft = element.classList.contains('has-stretch-block-to-left');
		const hasStretchRight = element.classList.contains('has-stretch-block-to-right');
		const hasStretchContainer = element.classList.contains('has-stretch-block-to-container');

		if (!hasStretchLeft && !hasStretchRight && !hasStretchContainer) {
			return;
		}

		const resizeHandler = () => {
			const winW = window.innerWidth;
			const dataResetOnDevices = element.getAttribute('data-reset-on-devices') || '[]';
			const resetOnDevice = this.parseDataAttribute(dataResetOnDevices);
			const currentDevice = this.getCurrentDevice();

			// Get element rect
			const blockRect = element.getBoundingClientRect();
			const offsetLeft = blockRect.left;
			const offsetRight = winW - blockRect.right;
			const elWidth = blockRect.width;

			// Get inner wrapper (direct children)
			const inner = element.querySelector(':scope > *');

			if (!inner) {
				return;
			}

			// Reset on specific devices
			if (resetOnDevice.includes(currentDevice)) {
				inner.style.marginLeft = '';
				inner.style.marginRight = '';
				inner.style.maxWidth = '';
				inner.style.width = '';
				return;
			}

			// Apply stretch
			if (hasStretchLeft) {
				inner.style.marginLeft = -Math.round(offsetLeft) + 'px';
				inner.style.maxWidth = 'unset';
				inner.style.width = Math.round(elWidth + offsetLeft) + 'px';
			} else if (hasStretchRight) {
				inner.style.marginRight = -Math.round(offsetRight) + 'px';
				inner.style.maxWidth = 'unset';
				inner.style.width = Math.round(elWidth + offsetRight) + 'px';
			} else if (hasStretchContainer) {
				const container = document.querySelector('.vlt-fake-container');

				if (container) {
					const containerRect = container.getBoundingClientRect();
					const containerStyle = getComputedStyle(container);
					const paddingLeft = parseFloat(containerStyle.paddingLeft) || 0;
					const paddingRight = parseFloat(containerStyle.paddingRight) || 0;

					const offsetLeftContainer = blockRect.left - containerRect.left - paddingLeft;
					const offsetRightContainer = containerRect.right - blockRect.right - paddingRight;

					inner.style.marginLeft = -Math.round(offsetLeftContainer) + 'px';
					inner.style.marginRight = -Math.round(offsetRightContainer) + 'px';
					inner.style.width = Math.round(blockRect.width + offsetLeftContainer + offsetRightContainer) + 'px';
					inner.style.maxWidth = 'unset';
				}
			}
		};

		// Trigger custom event for element parallax
		document.dispatchEvent(new CustomEvent('vlt.element-parallax'));

		resizeHandler();
		this.debounceResize(resizeHandler);
	}

	/**
	 * Setup Elementor hooks
	 */
	setupRefreshHandlers() {

		// Wait for Elementor frontend to be fully initialized
		window.addEventListener('elementor/frontend/init', () => {
			if (!window.elementorFrontend?.hooks) return;

			// Hook into every widget/section/column that becomes ready
			elementorFrontend.hooks.addAction('frontend/element_ready/global', (scope) => {

				const element = scope instanceof HTMLElement ? scope : scope[0];

				if (element) {
					this.stretchContainerInit(element);
					this.paddingToContainerInit(element);
					this.stickyContainerInit(element);
					this.equalHeightContainerInit(element);
				}

			});
		});

	}

}
