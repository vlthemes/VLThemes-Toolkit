(function () {
	'use strict';

	if (typeof jarallax === 'undefined') {
		console.warn('Jarallax not loaded.');
		return;
	}

	class JarallaxHandler extends elementorModules.frontend.handlers.Base {
		getDefaultSettings() {
			return {
				classes: {
					element: 'elementor-motion-parallax jarallax'
				}
			};
		}

		activate() {
			const $element = this.$element;
			const settings = this.getElementSettings();

			// Add the jarallax class directly to the container
			$element.addClass(this.getSettings('classes.element'));

			// Apply all Jarallax data attributes directly to the container element
			this.syncDataAttributes($element[0], settings);

			// Initialize Jarallax directly on the container
			$element.jarallax();
		}

		syncDataAttributes(el, settings) {
			// Speed
			if (settings.vlt_jarallax_speed?.size !== undefined && settings.vlt_jarallax_speed.size !== '') {
				el.setAttribute('data-speed', settings.vlt_jarallax_speed.size);
			} else {
				el.removeAttribute('data-speed');
			}

			// Type (e.g., 'scroll', 'scale', etc.)
			if (settings.vlt_jarallax_type && settings.vlt_jarallax_type !== '') {
				el.setAttribute('data-type', settings.vlt_jarallax_type);
			} else {
				el.removeAttribute('data-type');
			}

			// Video URL
			if (settings.vlt_jarallax_video_url && settings.vlt_jarallax_video_url !== '') {
				el.setAttribute('data-jarallax-video', settings.vlt_jarallax_video_url);
			} else {
				el.removeAttribute('data-jarallax-video');
			}
		}

		deactivate() {
			const $element = this.$element;

			// Destroy Jarallax instance if it exists
			if ($element.hasClass('jarallax')) {
				jarallax($element[0], 'destroy');
			}

			// Remove class and data attributes
			$element.removeClass(this.getSettings('classes.element'));
			$element.removeAttr('data-speed data-type data-jarallax-video');
		}

		toggle() {
			if (this.getElementSettings('vlt_jarallax_enable')) {
				this.activate();
			} else {
				this.deactivate();
			}
		}

		onInit() {
			super.onInit();
			this.toggle();
		}

		// Optional: Re-apply on element settings change (e.g., in editor)
		onElementChange(propertyName) {
			if (propertyName.startsWith('vlt_jarallax_')) {
				this.deactivate();
				this.toggle();
			}
		}
	}

	jQuery(window).on('elementor/frontend/init', () => {
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/container',
			($element) => {
				elementorFrontend.elementsHandler.addHandler(JarallaxHandler, {
					$element: $element
				});
			}
		);
	});
})();