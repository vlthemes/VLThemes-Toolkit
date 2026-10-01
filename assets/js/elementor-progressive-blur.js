(function ($) {
	'use strict';

	class VLTProgressiveBlurHandler extends elementorModules.frontend.handlers.Base {

		onInit() {
			super.onInit();
			this.render();
		}

		onElementChange(propertyName) {
			if (propertyName.indexOf('progressive_blur') === 0 || propertyName === 'enable_progressive_blur') {
				this.render();
			}
		}

		getOverlay() {
			return this.$element[0].querySelector(':scope > .vlt-progressive-blur-overlay');
		}

		render() {
			const $el = this.$element[0];
			const enabled = this.getElementSettings('enable_progressive_blur');

			$el.classList.remove(
				'vlt-progressive-blur',
				'vlt-progressive-blur-top',
				'vlt-progressive-blur-bottom'
			);

			if (enabled !== 'yes') {
				const overlay = this.getOverlay();
				if (overlay) {
					overlay.remove();
				}
				return;
			}

			const position = this.getElementSettings('progressive_blur_position') || 'bottom';

			$el.classList.add('vlt-progressive-blur', 'vlt-progressive-blur-' + position);

			// Appended last so it always paints above sibling widgets —
			// backdrop-filter only blurs what is visually behind it in the stack.
			// All visual styling (size, blur, mask, z-index) comes from Elementor's
			// own generated CSS via the controls' `selectors`, not from this script.
			if (!this.getOverlay()) {
				const overlay = document.createElement('div');
				overlay.className = 'vlt-progressive-blur-overlay';
				$el.appendChild(overlay);
			}
		}

		onDestroy() {
			const overlay = this.getOverlay();
			if (overlay) {
				overlay.remove();
			}
			super.onDestroy();
		}
	}

	$(window).on('elementor/frontend/init', () => {
		const initHandler = ($element) => {
			elementorFrontend.elementsHandler.addHandler(VLTProgressiveBlurHandler, { $element });
		};

		elementorFrontend.hooks.addAction('frontend/element_ready/container', initHandler);
	});

})(jQuery);
