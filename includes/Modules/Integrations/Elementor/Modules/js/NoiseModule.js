(function ($) {
	'use strict';

	class VLTNoiseHandler extends elementorModules.frontend.handlers.Base {

		onInit() {
			super.onInit();

			this.$overlay = null;

			if (this.getElementSettings('vlt_noise_enable') === 'yes') {
				this.addOverlay();
			}
		}

		addOverlay() {
			const el = this.$element[0];
			if (!el || this.$overlay) {
				return;
			}

			const overlay = document.createElement('div');
			overlay.className = 'vlt-noise-overlay';

			el.prepend(overlay);
			this.$overlay = overlay;
		}

		removeOverlay() {
			if (this.$overlay) {
				this.$overlay.remove();
				this.$overlay = null;
			}
		}

		onElementChange(propertyName) {
			if (propertyName !== 'vlt_noise_enable') {
				return;
			}

			if (this.getElementSettings('vlt_noise_enable') === 'yes') {
				this.addOverlay();
			} else {
				this.removeOverlay();
			}
		}

		onDestroy() {
			this.removeOverlay();

			super.onDestroy();
		}
	}

	// Register handler
	$(window).on('elementor/frontend/init', () => {
		elementorFrontend.hooks.addAction('frontend/element_ready/container', ($element) => {
			elementorFrontend.elementsHandler.addHandler(VLTNoiseHandler, { $element });
		});
	});

})(jQuery);
