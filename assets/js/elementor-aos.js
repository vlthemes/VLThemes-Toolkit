(function ($) {
	'use strict';

	// Initialize AOS globally when DOM is ready (only on frontend)
	$(document).ready(function () {
		// Don't initialize AOS in editor - we control animations manually there
		if (!elementorFrontend.isEditMode() && typeof AOS !== 'undefined') {
			AOS.init({
				disable: 'mobile',
				offset: 200,
				duration: 1000,
				once: true,
				easing: 'ease',
				startEvent: 'vlt:site:loaded'
			});
		}
	});

	class VLTAosHandler extends elementorModules.frontend.handlers.Base {

		getDefaultSettings() {
			return {
				classes: {
					animated: 'aos-animate',
					preview: 'vlt-aos-preview',
					init: 'aos-init'
				}
			};
		}

		// Get Settings for Current Device ===
		getAosSettings() {
			// Use built-in getCurrentDeviceSetting method from base class
			const animation = this.getCurrentDeviceSetting('vlt_aos_animation') || 'none';
			const settings = this.getElementSettings();

			return {
				animation: animation,
				duration: settings.vlt_aos_duration || 1,
				delay: settings.vlt_aos_delay || 0,
				easing: settings.vlt_aos_easing || 'ease',
				offset: settings.vlt_aos_offset !== undefined ? settings.vlt_aos_offset : 200
			};
		}

		// Init ===
		onInit() {
			super.onInit();

			this.isAnimating = false;
			this.changeTimeout = null;
			this.resizeTimeout = null;
			this.prevSettings = JSON.stringify(this.getAosSettings());
			this.currentDevice = elementorFrontend.getCurrentDeviceMode();

			this.applyAosAttributes();

			if (elementorFrontend.isEditMode()) {
				this.playPreviewAnimation();
			} else {
				// Listen for window resize to update attributes on breakpoint change
				this.bindResizeEvent();
				// Force AOS to recognize elements with their attributes
				this.refreshAos();
			}
		}

		// Bind Resize Event ===
		bindResizeEvent() {
			$(window).on('resize.vlt-aos' + this.getID(), () => {
				clearTimeout(this.resizeTimeout);
				this.resizeTimeout = setTimeout(() => {
					this.handleBreakpointChange();
				}, 250);
			});
		}

		// Handle Breakpoint Change
		handleBreakpointChange() {
			const newDevice = elementorFrontend.getCurrentDeviceMode();

			// Only update if device actually changed
			if (newDevice !== this.currentDevice) {
				this.currentDevice = newDevice;

				// Get the new animation settings
				const newSettings = this.getAosSettings();

				// Check if animation changed
				if (newSettings.animation === 'none') {
					// Remove all AOS attributes and classes
					this.removeAosAttributes();
				} else {
					// Remove AOS animation classes to reset state
					const classes = this.getSettings('classes');
					this.$element.removeClass([classes.animated, classes.init].join(' '));

					// Remove data-aos-id attribute to force AOS to reinitialize
					this.$element.removeAttr('data-aos-id');

					// Reapply attributes with new breakpoint settings
					this.applyAosAttributes();

					// Reinitialize AOS for this element (frontend only)
					if (!elementorFrontend.isEditMode() && typeof AOS !== 'undefined') {
						AOS.refreshHard();
					}
				}
			}
		}

		// Apply AOS Attributes
		applyAosAttributes() {
			const settings = this.getAosSettings();
			const $el = this.$element;
			const classes = this.getSettings('classes');

			if (!settings.animation || settings.animation === 'none') {
				this.removeAosAttributes();
				return;
			}

			// Animation type
			$el.attr('data-aos', settings.animation);

			// Duration in milliseconds
			const durationMs = (settings.duration || 1) * 1000;
			$el.attr('data-aos-duration', durationMs);

			// Delay in milliseconds
			if (settings.delay) {
				const delayMs = (settings.delay || 0) * 1000;
				$el.attr('data-aos-delay', delayMs);
			}

			// Easing
			if (settings.easing) {
				$el.attr('data-aos-easing', settings.easing);
			}

			// Offset
			if (settings.offset !== undefined) {
				$el.attr('data-aos-offset', settings.offset);
			}

			$el.addClass(classes.init);
		}

		// Remove AOS Attributes ===
		removeAosAttributes() {
			const $el = this.$element;
			const classes = this.getSettings('classes');

			// Remove data attributes
			$el.removeAttr('data-aos data-aos-duration data-aos-delay data-aos-easing data-aos-offset data-aos-id');

			// Remove classes
			$el.removeClass([classes.animated, classes.preview, classes.init].join(' '));
		}

		// Play Preview Animation in Editor
		playPreviewAnimation() {
			// Prevent double execution
			if (this.isAnimating) {
				return;
			}

			const settings = this.getAosSettings();
			const $el = this.$element;
			const classes = this.getSettings('classes');

			if (!settings.animation || settings.animation === 'none') {
				return;
			}

			this.isAnimating = true;

			// Remove animated class first (reset state)
			$el.removeClass(classes.animated);

			// Force reflow to restart CSS animation
			void $el[0].offsetWidth;

			// Add classes in next frame
			requestAnimationFrame(() => {
				$el.addClass(classes.preview);
				$el.addClass(classes.animated);

				// Reset flag after animation duration
				const duration = (settings.duration || 1) * 1000;
				setTimeout(() => {
					this.isAnimating = false;
				}, duration + 100);
			});
		}

		// Refresh AOS ===
		refreshAos() {
			// Skip in editor - we control animations manually there
			if (elementorFrontend.isEditMode()) {
				return;
			}

			if (typeof AOS !== 'undefined') {
				// setTimeout(() => {
					// Use refreshHard to rebuild elements array with new attributes
					AOS.refreshHard();
				// }, 100);
			}
		}

		// Handle Settings Change
		onElementChange(propertyName) {
			if (propertyName.indexOf('vlt_aos') !== 0) {
				return;
			}

			// In editor mode, only react to animation, duration, and easing changes
			if (elementorFrontend.isEditMode()) {
				// Check if changed property is one that affects preview animation
				const affectsPreview =
					propertyName.indexOf('vlt_aos_animation') === 0 ||
					propertyName === 'vlt_aos_duration' ||
					propertyName === 'vlt_aos_easing';

				if (!affectsPreview) {
					return;
				}
			}

			// Clear previous timeout (debounce)
			if (this.changeTimeout) {
				clearTimeout(this.changeTimeout);
			}

			// Debounce: wait for user to finish changing
			this.changeTimeout = setTimeout(() => {
				const currentSettings = JSON.stringify(this.getAosSettings());

				// Only update if settings actually changed
				if (this.prevSettings !== currentSettings) {
					this.prevSettings = currentSettings;

					if (elementorFrontend.isEditMode()) {
						// Reset animation flag to allow replay
						this.isAnimating = false;

						// Apply new attributes
						this.applyAosAttributes();

						// Play animation
						this.playPreviewAnimation();
					}
				}
			}, 300);
		}

		// Cleanup ===
		onDestroy() {
			if (this.changeTimeout) {
				clearTimeout(this.changeTimeout);
			}
			if (this.resizeTimeout) {
				clearTimeout(this.resizeTimeout);
			}
			// Unbind resize event
			$(window).off('resize.vlt-aos' + this.getID());

			this.removeAosAttributes();
			super.onDestroy();
		}
	}

	// Register handlers
	$(window).on('elementor/frontend/init', () => {
		const initHandler = ($element) => {
			elementorFrontend.elementsHandler.addHandler(VLTAosHandler, { $element });
		};

		elementorFrontend.hooks.addAction('frontend/element_ready/container', initHandler);
		elementorFrontend.hooks.addAction('frontend/element_ready/widget', initHandler);
	});

})(jQuery);
