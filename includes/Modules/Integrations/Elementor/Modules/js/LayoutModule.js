(function ($) {
	'use strict';

	class VLTLayoutHandler extends elementorModules.frontend.handlers.Base {

		getCurrentDevice() {
			return elementorFrontend.getCurrentDeviceMode();
		}

		onInit() {
			super.onInit();

			this.resizeTimeout = null;
			this.currentDevice = this.getCurrentDevice();

			// Initialize all features
			this.initStretch();
			this.initPaddingToContainer();
			this.initScrollReveal();

			// Bind resize event
			this.bindResizeEvent();
		}

		bindResizeEvent() {
			$(window).on('resize.vlt-layout' + this.getID(), () => {
				clearTimeout(this.resizeTimeout);
				this.resizeTimeout = setTimeout(() => {
					this.handleResize();
				}, 250);
			});
		}

		handleResize() {
			const newDevice = this.getCurrentDevice();

			// Check if device changed
			if (newDevice !== this.currentDevice) {
				this.currentDevice = newDevice;
			}

			// Re-run handlers that depend on resize
			this.initStretch();
			this.initPaddingToContainer();
			this.initScrollReveal();
		}

		onElementChange(propertyName) {
			// Check if it's a layout related property
			if (propertyName.indexOf('vlt_stretch') === 0) {
				this.initStretch();
			}

			if (propertyName.indexOf('vlt_padding_to_container') === 0) {
				this.initPaddingToContainer();
			}

			if (propertyName.indexOf('vlt_scroll_reveal') === 0) {
				this.initScrollReveal();
			}
		}

		// === STRETCH ===
		initStretch() {
			const stretchEnabled = this.getElementSettings('vlt_stretch_enabled');
			if (stretchEnabled !== 'yes') {
				// Reset stretch when disabled
				this.$element.css({
					marginLeft: '',
					marginRight: '',
					width: '',
					maxWidth: ''
				});
				return;
			}

			const resetDevices = this.getElementSettings('vlt_stretch_reset_on_devices') || [];
			if (resetDevices.includes(this.currentDevice)) {
				this.$element.css({
					marginLeft: '',
					marginRight: '',
					width: '',
					maxWidth: ''
				});
				return;
			}

			const stretchSide = this.getElementSettings('vlt_stretch_side') || 'to-left';
			const rect = this.$element[0].getBoundingClientRect();
			const winW = window.innerWidth;
			const left = rect.left;
			const right = winW - rect.right;

			if (stretchSide === 'to-left') {
				this.$element.css({
					marginLeft: -Math.round(left) + 'px',
					width: Math.round(rect.width + left) + 'px',
					maxWidth: 'unset'
				});
			} else if (stretchSide === 'to-right') {
				this.$element.css({
					marginRight: -Math.round(right) + 'px',
					width: Math.round(rect.width + right) + 'px',
					maxWidth: 'unset'
				});
			} else if (stretchSide === 'to-container') {
				let container = $('.container').first();
				if (!container.length) {
					container = $('<div class="container"></div>').appendTo('body');
				}

				const cRect = container[0].getBoundingClientRect();
				const cStyle = window.getComputedStyle(container[0]);
				const pl = parseFloat(cStyle.paddingLeft) || 0;
				const pr = parseFloat(cStyle.paddingRight) || 0;
				const offsetL = rect.left - cRect.left - pl;
				const offsetR = cRect.right - rect.right - pr;

				this.$element.css({
					marginLeft: -Math.round(offsetL) + 'px',
					marginRight: -Math.round(offsetR) + 'px',
					width: Math.round(rect.width + offsetL + offsetR) + 'px',
					maxWidth: 'unset'
				});
			}
		}

		// === PADDING TO CONTAINER ===
		initPaddingToContainer() {
			const paddingEnabled = this.getElementSettings('vlt_padding_to_container');
			if (paddingEnabled !== 'yes') {
				// Reset padding when disabled
				this.$element.css({
					paddingLeft: '',
					paddingRight: ''
				});
				return;
			}

			const resetDevices = this.getElementSettings('vlt_padding_to_container_reset_on_devices') || [];
			if (resetDevices.includes(this.currentDevice)) {
				this.$element.css({
					paddingLeft: '',
					paddingRight: ''
				});
				return;
			}

			let container = $('.container').first();
			if (!container.length) {
				container = $('<div class="container"></div>').appendTo('body');
			}

			const offset = container[0].getBoundingClientRect().left +
				(parseFloat(window.getComputedStyle(container[0]).paddingLeft) || 0);

			const paddingSide = this.getElementSettings('vlt_padding_to_container_side') || 'to-left';

			if (paddingSide === 'to-left') {
				this.$element.css({
					paddingLeft: offset + 'px',
					paddingRight: ''
				});
			}
			if (paddingSide === 'to-right') {
				this.$element.css({
					paddingRight: offset + 'px',
					paddingLeft: ''
				});
			}
		}

		// === SCROLL REVEAL ===
		initScrollReveal() {
			if (this._scrollRevealTween) {
				this._scrollRevealTween.scrollTrigger && this._scrollRevealTween.scrollTrigger.kill();
				this._scrollRevealTween.kill();
				this._scrollRevealTween = null;
				gsap.set(this.$element[0], { clearProps: 'transform' });
			}

			const enabled = this.getElementSettings('vlt_scroll_reveal_enabled');
			if (enabled !== 'yes') {
				return;
			}

			if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
				console.warn('VLT Scroll Reveal: gsap or ScrollTrigger not found.');
				return;
			}

			gsap.registerPlugin(ScrollTrigger);

			const offsetY      = this.getElementSettings('vlt_scroll_reveal_y')       ?? -50;
			const scale        = this.getElementSettings('vlt_scroll_reveal_scale')   ?? 0.85;
			const triggerClass = this.getElementSettings('vlt_scroll_reveal_trigger') || '';
			const start        = this.getElementSettings('vlt_scroll_reveal_start')   || 'top 85%';
			const end          = this.getElementSettings('vlt_scroll_reveal_end')     || 'top 40%';

			const triggerEl = triggerClass ? document.querySelector(triggerClass) : this.$element[0];

			if (!triggerEl) {
				console.warn('VLT Scroll Reveal: trigger "' + triggerClass + '" not found.');
				return;
			}

			gsap.set(this.$element[0], {
				y: offsetY,
				scale: scale,
			});

			this._scrollRevealTween = gsap.to(this.$element[0], {
				y: 0,
				scale: 1,
				ease: 'none',
				scrollTrigger: {
					trigger: triggerEl,
					start: start,
					end: end,
					scrub: true,
				},
			});
		}

		onDestroy() {
			if (this.resizeTimeout) {
				clearTimeout(this.resizeTimeout);
			}
			$(window).off('resize.vlt-layout' + this.getID());

			if (this._scrollRevealTween) {
				this._scrollRevealTween.scrollTrigger && this._scrollRevealTween.scrollTrigger.kill();
				this._scrollRevealTween.kill();
				this._scrollRevealTween = null;
			}

			super.onDestroy();
		}
	}

	// Register handlers
	$(window).on('elementor/frontend/init', () => {
		const initHandler = ($element) => {
			elementorFrontend.elementsHandler.addHandler(VLTLayoutHandler, { $element });
		};

		elementorFrontend.hooks.addAction('frontend/element_ready/container', initHandler);
		elementorFrontend.hooks.addAction('frontend/element_ready/widget', initHandler);
	});

})(jQuery);
