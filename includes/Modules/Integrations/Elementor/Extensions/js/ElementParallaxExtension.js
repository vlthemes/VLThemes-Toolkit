(function ($) {
	'use strict';

	if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
		console.warn('GSAP or ScrollTrigger not loaded — Element Parallax disabled');
		return;
	}

	class ElementParallaxExtension {
		constructor() {
			this.triggers = [];
			this.initialized = false;
			this.resizeCallbacks = [];
			this.resizeTimer = null;
			this.init();
		}

		init() {
			$(window).on('load resize orientationchange', () => this.triggerResize());
			$(document).ready(() => {
				this.initParallax();
				this.setupRefreshHandlers();
				this.initialized = true;
				console.info('Element Parallax Extension initialized');
			});
		}

		triggerResize() {
			clearTimeout(this.resizeTimer);
			this.resizeTimer = setTimeout(() => {
				this.resizeCallbacks.forEach(cb => cb());
			}, 250);
		}

		debounceResize(cb) {
			if (typeof cb === 'function' && !this.resizeCallbacks.includes(cb)) {
				this.resizeCallbacks.push(cb);
			}
		}

		isMobile() {
			return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
		}

		parseParallax(el) {
			const parts = (el.getAttribute('data-element-parallax') || '').trim().split(/\s+/);
			return {
				y: parts[0] ? parseFloat(parts[0]) : 0,
				x: parts[1] ? parseFloat(parts[1]) : 0
			};
		}

		parseOpacity(el) {
			const parts = (el.getAttribute('data-element-opacity') || '').trim().split(/\s+/);
			const start = parts[0] ? parseFloat(parts[0]) : null;
			return { start, end: parts[1] ? parseFloat(parts[1]) : start };
		}

		parseScale(el) {
			const parts = (el.getAttribute('data-element-scale') || '').trim().split(/\s+/);
			const start = parts[0] ? parseFloat(parts[0]) : null;
			return { start, end: parts[1] ? parseFloat(parts[1]) : start };
		}

		parseThreshold(el) {
			const parts = (el.getAttribute('data-element-threshold') || '').trim().split(/\s+/);
			return {
				y: parts[0] ? parseFloat(parts[0]) : null,
				x: parts[1] ? parseFloat(parts[1]) : null
			};
		}

		getTargetElement(el) {
			const inner = el.querySelector(':scope > .elementor-widget-container');
			return inner || el;
		}

		initParallax(scope = document) {
			if (this.isMobile()) {
				console.info('Element Parallax disabled on mobile');
				return;
			}

			scope.querySelectorAll('.vlt-element-parallax').forEach(el => {
				if (el._parallaxInitialized) return;

				const { y, x } = this.parseParallax(el);
				const { start: opacityStart, end: opacityEnd } = this.parseOpacity(el);
				const { start: scaleStart, end: scaleEnd } = this.parseScale(el);
				const { y: thresholdY, x: thresholdX } = this.parseThreshold(el);

				if (!y && !x && opacityStart === null && scaleStart === null) return;

				const target = this.getTargetElement(el);

				gsap.set(target, {
					x: "+=0",
					y: "+=0",
					opacity: opacityStart ?? 1,
					scale: scaleStart ?? 1,
					force3D: true,
					transition: 'none'
				});

				const parentSelector = el.getAttribute('data-element-parallax-parent');
				const parent = parentSelector ? el.closest(parentSelector) || document.querySelector(parentSelector) : null;

				if (parent) {
					const start = el.getAttribute('data-element-start') || 'top top';
					const end = el.getAttribute('data-element-end') || 'bottom top';

					const tween = gsap.fromTo(target, {
						x: "+=0",
						y: "+=0",
						opacity: opacityStart ?? 1,
						scale: scaleStart ?? 1
					}, {
						x: x,
						y: y,
						opacity: opacityEnd ?? undefined,
						scale: scaleEnd ?? undefined,
						immediateRender: false,
						scrollTrigger: {
							trigger: parent,
							start,
							end,
							scrub: true,
							invalidateOnRefresh: true
						}
					});

					this.triggers.push(tween.scrollTrigger);

					if (!el._resizeBound) {
						el._resizeBound = true;
						this.debounceResize(() => tween.scrollTrigger?.refresh());
					}
				} else {
					const update = () => {
						const rect = el.getBoundingClientRect();
						const scrollY = window.pageYOffset || document.documentElement.scrollTop;
						const height = window.innerHeight;
						const center = (scrollY + height / 2 - (rect.top + scrollY) - rect.height / 2) / (height / 2);
						const abs = Math.abs(center);

						let ty = y ? (center < 0 ? -1 : 1) * gsap.utils.interpolate(0, y, abs) : 0;
						let tx = x ? (center < 0 ? -1 : 1) * gsap.utils.interpolate(0, x, abs) : 0;

						if (thresholdY !== null && Math.abs(ty) > thresholdY) ty = 0;
						if (thresholdX !== null && Math.abs(tx) > thresholdX) tx = 0;

						const props = {
							x: tx,
							y: ty,
							force3D: true
						};

						if (opacityStart !== null && opacityEnd !== null) {
							props.opacity = gsap.utils.interpolate(opacityStart, opacityEnd, abs);
						}
						if (scaleStart !== null && scaleEnd !== null) {
							props.scale = gsap.utils.interpolate(scaleStart, scaleEnd, abs);
						}

						gsap.set(target, props);
					};

					update();

					const st = ScrollTrigger.create({
						start: 'top bottom',
						end: 'bottom top',
						scrub: true,
						onUpdate: update,
						onRefresh: update
					});

					this.triggers.push(st);
					this.debounceResize(update);
				}

				el._parallaxInitialized = true;
				target.style.willChange = 'transform, opacity';
			});
		}

		setupRefreshHandlers() {
			$(window).on('elementor/frontend/init', () => {
				if (window.elementorFrontend?.hooks) {
					elementorFrontend.hooks.addAction('frontend/element_ready/global', scope => {
						const el = scope instanceof HTMLElement ? scope : scope[0];
						this.initParallax(el);
						setTimeout(() => ScrollTrigger.refresh(), 150);
					});
				}
			});
		}
	}

	new ElementParallaxExtension();

})(jQuery);