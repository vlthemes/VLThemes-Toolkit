(function ($) {
	'use strict';

	const $window = $(window);

	class VLTEqualHeightHandler extends elementorModules.frontend.handlers.Base {

		onInit() {
			super.onInit();

			this.cachedElements = [];
			this.changeTimeout = null;
			this.resizeTimeout = null;
			this.currentDevice = elementorFrontend.getCurrentDeviceMode();

			// Initialize equal height
			if (this.isEqualHeightEnabled()) {
				this.run();
			}

			// Bind resize event
			this.bindResizeEvent();
		}

		bindResizeEvent() {
			$window.on('resize.vlt-equal-height' + this.getID(), () => {
				clearTimeout(this.resizeTimeout);
				this.resizeTimeout = setTimeout(() => {
					this.handleResize();
				}, 250);
			});
		}

		handleResize() {
			const newDevice = elementorFrontend.getCurrentDeviceMode();

			// Check if device changed
			if (newDevice !== this.currentDevice) {
				this.currentDevice = newDevice;
			}

			// Re-run equal height
			if (this.isEqualHeightEnabled()) {
				this.unbindMatchHeight(true);
				this.run();
			}
		}

		isEqualHeightEnabled() {
			return this.getElementSettings('vlt_equal_height_widgets') === 'yes' && $.fn.matchHeight;
		}

		isDisabledOnDevice() {
			const currentDevice = elementorFrontend.getCurrentDeviceMode();
			const resetDevices = this.getElementSettings('vlt_equal_height_reset_on_devices') || [];

			return resetDevices.includes(currentDevice);
		}

		getSelectedWidgets() {
			return this.getElementSettings('vlt_equal_height_widget_selector') || [];
		}

		getTargetElements() {
			const selectedWidgets = this.getSelectedWidgets();
			const _this = this;

			return selectedWidgets.map(function(widget) {
				// Find all widget containers for this widget type using findElement
				const selector = '.elementor-widget-' + widget + ' > .elementor-widget-container > *';
				let the_container = _this.findElement(selector);

				// If not found, try without findElement (for nested containers)
				if (!the_container.length) {
					the_container = _this.$element.find(selector);
				}

				// Fallback for optimized markup
				if (elementorFrontendConfig.experimentalFeatures &&
					elementorFrontendConfig.experimentalFeatures.e_optimized_markup &&
					!the_container.length) {
					return _this.findElement('.elementor-widget-' + widget + '> *').length ?
						_this.findElement('.elementor-widget-' + widget + '> *') :
						_this.$element.find('.elementor-widget-' + widget + '> *');
				}

				return the_container;
			});
		}

		onElementChange(prop) {
			// Check if it's an equal height related property
			if (prop.indexOf('vlt_equal_height') !== 0) {
				return;
			}

			// Clear previous timeout (debounce)
			if (this.changeTimeout) {
				clearTimeout(this.changeTimeout);
			}

			// Debounce: wait for user to finish changing
			this.changeTimeout = setTimeout(() => {
				this.unbindMatchHeight(true);
				this.run();
			}, 300);
		}

		unbindMatchHeight(isCachedOnly) {
			if (isCachedOnly) {
				this.cachedElements.forEach(function($el) {
					$el.matchHeight({
						remove: true
					});
				});
				this.cachedElements = [];
			} else {
				this.getTargetElements().forEach(function($el) {
					if ($el && $el.length) {
						$el.matchHeight({
							remove: true
						});
					}
				});
			}
		}

		run() {
			const _this = this;

			// Clear cached elements at the start
			this.cachedElements = [];

			// Check if disabled on device
			if (this.isDisabledOnDevice()) {
				this.unbindMatchHeight();
				return;
			}

			// Get selected widgets
			const selectedWidgets = this.getSelectedWidgets();

			// If no widgets selected, remove matchHeight
			if (!selectedWidgets || selectedWidgets.length === 0) {
				this.unbindMatchHeight();
				return;
			}

			// Get target elements
			const elements = this.getTargetElements();

			// If no elements found, remove matchHeight
			if (!elements || elements.length === 0) {
				this.unbindMatchHeight();
				return;
			}

			// Get height mode setting
			const heightMode = this.getElementSettings('vlt_equal_height_mode') || 'separate';

			if (heightMode === 'combined') {
				// Combine all elements into one jQuery collection
				let allElements = $();
				elements.forEach(function($el) {
					if ($el && $el.length) {
						allElements = allElements.add($el);
					}
				});

				// Apply matchHeight to all elements together
				if (allElements.length) {
					allElements.matchHeight({
						byRow: false
					});
					_this.cachedElements.push(allElements);
				}
			} else {
				// Apply matchHeight to each widget type group separately
				elements.forEach(function($el) {
					if ($el && $el.length) {
						$el.matchHeight({
							byRow: false
						});
						_this.cachedElements.push($el);
					}
				});
			}
		}

		onDestroy() {
			// Clear timeouts
			if (this.changeTimeout) {
				clearTimeout(this.changeTimeout);
			}
			if (this.resizeTimeout) {
				clearTimeout(this.resizeTimeout);
			}
			// Remove matchHeight from all elements
			this.unbindMatchHeight();
			// Unbind resize event
			$window.off('resize.vlt-equal-height' + this.getID());
			super.onDestroy();
		}
	}

	// Register handlers
	$(window).on('elementor/frontend/init', () => {
		const initHandler = ($element) => {
			elementorFrontend.elementsHandler.addHandler(VLTEqualHeightHandler, { $element });
		};

		elementorFrontend.hooks.addAction('frontend/element_ready/container', initHandler);
		elementorFrontend.hooks.addAction('frontend/element_ready/section', initHandler);
	});

})(jQuery);
