/**
 * VLT AOS Extension Handler
 *
 * Handles AOS (Animate On Scroll) initialization and refresh for Elementor elements
 * The data-aos attributes are rendered by PHP, this initializes AOS and refreshes it
 */

// Initialize AOS globally when DOM is ready
jQuery(document).ready(function () {
	if (typeof AOS !== 'undefined') {
		AOS.init({
			disable: 'mobile',
			offset: 200,
			duration: 1000,
			easing: 'ease',
			once: true,
			// startEvent: 'vlt:site:loaded' || 'DOMContentLoaded'
		});
	}
});

class VLTAosHandler extends elementorModules.frontend.handlers.Base {

	onInit() {
		super.onInit();

		// Apply AOS attributes dynamically for editor
		if (elementorFrontend.isEditMode()) {
			this.applyAosAttributes();
		}

		// Refresh AOS to detect new elements
		if (typeof AOS !== 'undefined') {
			AOS.refresh();
		}
	}

	/**
	 * Apply AOS attributes in editor (since PHP render doesn't work in editor)
	 */
	applyAosAttributes() {
		const settings = this.getElementSettings();

		// Check if animation is enabled
		if (!settings.vlt_aos_animation || settings.vlt_aos_animation === 'none') {
			// Remove attributes if none
			this.$element.removeAttr('data-aos data-aos-duration data-aos-delay data-aos-offset');
			return;
		}

		// Apply animation
		this.$element.attr('data-aos', settings.vlt_aos_animation);

		// Apply duration (convert seconds to milliseconds)
		if (settings.vlt_aos_duration?.size) {
			const durationMs = settings.vlt_aos_duration.size * 1000;
			this.$element.attr('data-aos-duration', durationMs);
		} else {
			this.$element.removeAttr('data-aos-duration');
		}

		// Apply delay (convert seconds to milliseconds)
		if (settings.vlt_aos_delay?.size) {
			const delayMs = settings.vlt_aos_delay.size * 1000;
			this.$element.attr('data-aos-delay', delayMs);
		} else {
			this.$element.removeAttr('data-aos-delay');
		}

		// Apply offset
		if (settings.vlt_aos_offset !== undefined && settings.vlt_aos_offset !== '') {
			this.$element.attr('data-aos-offset', settings.vlt_aos_offset);
		} else {
			this.$element.removeAttr('data-aos-offset');
		}
	}

	onElementChange(propertyName) {
		if (propertyName.indexOf('vlt_aos') === 0) {
			// Reapply attributes in editor
			if (elementorFrontend.isEditMode()) {
				this.applyAosAttributes();
			}

			// Refresh AOS when settings change
			if (typeof AOS !== 'undefined') {
				AOS.refresh();
			}
		}
	}
}

// Register handlers when Elementor frontend is ready
jQuery(window).on('elementor/frontend/init', () => {
	// Handle containers
	elementorFrontend.hooks.addAction('frontend/element_ready/container', ($element) => {
		elementorFrontend.elementsHandler.addHandler(VLTAosHandler, { $element });
	});

	// Handle widgets
	elementorFrontend.hooks.addAction('frontend/element_ready/widget', ($element) => {
		elementorFrontend.elementsHandler.addHandler(VLTAosHandler, { $element });
	});
});