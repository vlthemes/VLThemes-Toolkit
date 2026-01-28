(function ($) {
	'use strict';

	if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
		console.warn('GSAP or ScrollTrigger not loaded — Parallax Extension disabled');
		return;
	}

	gsap.registerPlugin(ScrollTrigger);

	class VLTParallaxHandler extends elementorModules.frontend.handlers.Base {

		onInit() {
			super.onInit();

			this.scrollTrigger = null;
			this.changeTimeout = null;

			// Check if parallax is enabled
			if (this.getElementSettings('vlt_parallax_enable') === 'yes') {
				this.initParallax();
			}
		}

		destroyParallax() {
			// Kill ScrollTrigger instance
			if (this.scrollTrigger) {
				this.scrollTrigger.kill();
				this.scrollTrigger = null;
			}

			// Reset element transform
			gsap.set(this.$element[0], { clearProps: 'transform' });
		}

		initParallax() {
			const parallaxEnabled = this.getElementSettings('vlt_parallax_enable');

			// Destroy existing instance
			this.destroyParallax();

			// If disabled, return
			if (parallaxEnabled !== 'yes') {
				return;
			}

			const el = this.$element[0];
			if (!el) {
				return;
			}

			// Get settings
			const settings = this.getElementSettings();
			const speed = settings.vlt_parallax_speed?.size !== undefined ? settings.vlt_parallax_speed.size : 2;
			const percentage = settings.vlt_parallax_percentage?.size !== undefined ? settings.vlt_parallax_percentage.size : 0.5;
			const zIndex = settings.vlt_parallax_zindex;
			const minOffset = settings.vlt_parallax_min;
			const maxOffset = settings.vlt_parallax_max;

			// Set z-index if specified
			if (zIndex !== undefined && zIndex !== '') {
				gsap.set(el, { zIndex: zIndex });
			}

			// Create parallax animation with ScrollTrigger (like Rellax)
			// Speed works as multiplier - negative = up, positive = down
			this.scrollTrigger = ScrollTrigger.create({
				trigger: el,
				start: 'top bottom',
				end: 'max',
				scrub: true,
				onUpdate: (self) => {
					// Calculate element center position relative to viewport
					const rect = el.getBoundingClientRect();
					const elementCenter = rect.top + rect.height / 2;
					const viewportCenter = window.innerHeight / 2;

					// Distance from center (negative = above center, positive = below center)
					const distanceFromCenter = elementCenter - viewportCenter;

					// Calculate offset based on percentage
					// When percentage = 0.5 and element is in center, offset should be 0
					const totalScroll = el.offsetHeight + window.innerHeight;
					const centerOffset = totalScroll * (0.5 - percentage);

					// Calculate final Y position
					let y = (distanceFromCenter + centerOffset) * speed * -0.1;

					// Apply min/max constraints if specified (by absolute value)
					if (minOffset !== undefined && minOffset !== '') {
						if (Math.abs(y) < Math.abs(minOffset)) {
							y = y < 0 ? -Math.abs(minOffset) : Math.abs(minOffset);
						}
					}
					if (maxOffset !== undefined && maxOffset !== '') {
						if (Math.abs(y) > Math.abs(maxOffset)) {
							y = y < 0 ? -Math.abs(maxOffset) : Math.abs(maxOffset);
						}
					}

					// Apply transform
					gsap.set(el, { y: y });
				}
			});
		}

		onElementChange(propertyName) {
			// Refresh when parallax settings change
			if (propertyName.indexOf('vlt_parallax') !== 0) {
				return;
			}

			// Clear previous timeout (debounce)
			if (this.changeTimeout) {
				clearTimeout(this.changeTimeout);
			}

			// Debounce: wait for user to finish changing
			this.changeTimeout = setTimeout(() => {
				this.initParallax();
			}, 300);
		}

		onDestroy() {
			// Clear timeout
			if (this.changeTimeout) {
				clearTimeout(this.changeTimeout);
			}

			// Destroy parallax
			this.destroyParallax();

			super.onDestroy();
		}
	}

	// Register handlers
	$(window).on('elementor/frontend/init', () => {
		const initHandler = ($element) => {
			elementorFrontend.elementsHandler.addHandler(VLTParallaxHandler, { $element });
		};

		elementorFrontend.hooks.addAction('frontend/element_ready/container', initHandler);
		elementorFrontend.hooks.addAction('frontend/element_ready/widget', initHandler);
	});

})(jQuery);
