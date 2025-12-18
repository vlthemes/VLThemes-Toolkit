(function () {
	'use strict';

	if (typeof Rellax === 'undefined') {
		console.warn('Rellax.js not loaded — Parallax Extension disabled');
		return;
	}

	class ParallaxHandler extends elementorModules.frontend.handlers.Base {
		getDefaultSettings() {
			return {
				selectors: {
					element: '.rellax'
				}
			};
		}

		getDefaultElements() {
			return {
				$element: this.$element
			};
		}

		bindEvents() {
			elementorFrontend.addListenerOnce(this.getUniqueHandlerID() + 'resize', 'resize', this.onResize.bind(this));
		}

		unbindEvents() {
			elementorFrontend.removeListeners(this.getUniqueHandlerID() + 'resize', 'resize', this.onResize.bind(this));
		}

		onInit() {
			super.onInit();

			const settings = this.getElementSettings();

			// Check if parallax is enabled
			if (settings.vlt_rellax_enable !== 'yes') {
				return;
			}

			this.initRellax();
		}

		initRellax() {
			// Destroy existing instance
			if (this.rellaxInstance) {
				this.rellaxInstance.destroy();
				this.rellaxInstance = null;
			}

			const el = this.$element[0];
			if (!el) {
				return;
			}

			// Get settings from Elementor
			const settings = this.getElementSettings();

			// Add rellax class if not present (in editor, render_attributes doesn't run)
			if (!el.classList.contains('rellax')) {
				el.classList.add('rellax');
			}

			// Sync settings to data attributes (important for editor mode)
			this.syncDataAttributes(el, settings);

			// Remove transitions that conflict with Rellax
			el.style.transition = 'none';

			try {
				// Initialize Rellax for this element
				this.rellaxInstance = new Rellax(el, {
					center: false,
					wrapper: null,
					round: true,
					vertical: true,
					horizontal: false
				});
			} catch (error) {
				console.error('Rellax initialization error:', error);
			}
		}

		syncDataAttributes(el, settings) {
			// Sync speed - always set, use default if not specified
			const speed = settings.vlt_rellax_speed?.size !== undefined ? settings.vlt_rellax_speed.size : -3;
			el.setAttribute('data-rellax-speed', speed);

			// Sync percentage
			if (settings.vlt_rellax_percentage?.size !== undefined && settings.vlt_rellax_percentage?.size !== '') {
				el.setAttribute('data-rellax-percentage', settings.vlt_rellax_percentage.size);
			} else {
				el.removeAttribute('data-rellax-percentage');
			}

			// Sync zindex
			if (settings.vlt_rellax_zindex !== undefined && settings.vlt_rellax_zindex !== '') {
				el.setAttribute('data-rellax-zindex', settings.vlt_rellax_zindex);
			} else {
				el.removeAttribute('data-rellax-zindex');
			}

			// Sync min
			if (settings.vlt_rellax_min !== undefined && settings.vlt_rellax_min !== '') {
				el.setAttribute('data-rellax-min', settings.vlt_rellax_min);
			} else {
				el.removeAttribute('data-rellax-min');
			}

			// Sync max
			if (settings.vlt_rellax_max !== undefined && settings.vlt_rellax_max !== '') {
				el.setAttribute('data-rellax-max', settings.vlt_rellax_max);
			} else {
				el.removeAttribute('data-rellax-max');
			}
		}

		onResize() {
			if (this.rellaxInstance) {
				this.rellaxInstance.refresh();
			}
		}

		onElementChange(propertyName) {
			// Refresh when parallax settings change
			if (propertyName.indexOf('vlt_rellax') === 0) {
				// Debounce to avoid excessive reinitialization
				clearTimeout(this.changeTimeout);
				this.changeTimeout = setTimeout(() => {
					this.initRellax();
				}, 300);
			}
		}

		onDestroy() {
			// Clear debounce timeout
			clearTimeout(this.changeTimeout);

			if (this.rellaxInstance) {
				this.rellaxInstance.destroy();
				this.rellaxInstance = null;
			}
		}
	}

	window.addEventListener('elementor/frontend/init', () => {
		// Register handler for containers
		elementorFrontend.hooks.addAction('frontend/element_ready/container', function ($scope) {
			elementorFrontend.elementsHandler.addHandler(ParallaxHandler, {
				$element: $scope
			});
		});

		// Register handler for common widgets
		elementorFrontend.hooks.addAction('frontend/element_ready/widget', function ($scope) {
			elementorFrontend.elementsHandler.addHandler(ParallaxHandler, {
				$element: $scope
			});
		});
	});

})();
