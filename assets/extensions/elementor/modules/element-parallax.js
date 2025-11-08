/**
 * Element Parallax Module
 * Handles element-based parallax effects using GSAP and ScrollTrigger
 *
 * @global gsap - GSAP animation library
 * @global ScrollTrigger - GSAP ScrollTrigger plugin
 */

/* global gsap, ScrollTrigger */

export default class ElementParallaxModule {
	constructor({ isMobileDevice, debounceResize }) {
		this.isMobileDevice = isMobileDevice;
		this.debounceResize = debounceResize;
		this.initialized = false;
		this.triggers = [];
	}

	/**
	 * Initialize Element Parallax
	 */
	init() {
		// Check if GSAP and ScrollTrigger are loaded
		if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
			console.warn('GSAP or ScrollTrigger not loaded');
			return;
		}

		// Skip on mobile devices (performance optimization)
		if (this.isMobileDevice()) {
			console.info('Element Parallax disabled on mobile device');
			return;
		}

		// Initialize parallax on all elements
		this.initParallax();

		this.initialized = true;
		console.info('Element Parallax initialized');
	}

	/**
	 * Parse parallax values from data attribute
	 * @param {HTMLElement} el
	 * @returns {{y: number, x: number}}
	 */
	parseParallax(el) {
		const raw = (el.getAttribute('data-element-parallax') || '').trim();
		const parts = raw.split(/\s+/);
		return {
			y: parts[0] ? parseFloat(parts[0]) : 0,
			x: parts[1] ? parseFloat(parts[1]) : 0
		};
	}

	/**
	 * Parse opacity values from data attribute
	 * @param {HTMLElement} el
	 * @returns {{start: number|null, end: number|null}}
	 */
	parseOpacity(el) {
		const raw = (el.getAttribute('data-element-opacity') || '').trim();
		const parts = raw.split(/\s+/);
		const start = parts[0] ? parseFloat(parts[0]) : null;
		return { start, end: parts[1] ? parseFloat(parts[1]) : start };
	}

	/**
	 * Parse scale values from data attribute
	 * @param {HTMLElement} el
	 * @returns {{start: number|null, end: number|null}}
	 */
	parseScale(el) {
		const raw = (el.getAttribute('data-element-scale') || '').trim();
		const parts = raw.split(/\s+/);
		const start = parts[0] ? parseFloat(parts[0]) : null;
		return { start, end: parts[1] ? parseFloat(parts[1]) : start };
	}

	/**
	 * Parse threshold values from data attribute
	 * @param {HTMLElement} el
	 * @returns {{y: number|null, x: number|null}}
	 */
	parseThreshold(el) {
		const raw = (el.getAttribute('data-element-threshold') || '').trim();
		const parts = raw.split(/\s+/);
		return {
			y: parts[0] ? parseFloat(parts[0]) : null,
			x: parts[1] ? parseFloat(parts[1]) : null
		};
	}

	/**
	 * Get window scroll and height info
	 * @returns {{scrollY: number, height: number}}
	 */
	getWindowInfo() {
		return {
			scrollY: window.pageYOffset || document.documentElement.scrollTop || 0,
			height: window.innerHeight || document.documentElement.clientHeight || 0
		};
	}

	/**
	 * Update element transform based on scroll position
	 */
	updateElementTransform(el, y, x, opacityStart, opacityEnd, scaleStart, scaleEnd, thresholdY, thresholdX) {
		const { scrollY, height } = this.getWindowInfo();
		const data = el._parallaxData || {};
		const rect = el.getBoundingClientRect();

		el._parallaxData = {
			width: rect.width,
			height: rect.height,
			y: rect.top + scrollY,
			x: rect.left
		};

		const centerOffset = (scrollY + height / 2 - data.y - data.height / 2) / (height / 2);
		const centerPercent = centerOffset;
		const absPercent = Math.abs(centerPercent);

		let translateY = y ? (centerPercent < 0 ? -1 : 1) * gsap.utils.interpolate(0, y, absPercent) : 0;
		let translateX = x ? (centerPercent < 0 ? -1 : 1) * gsap.utils.interpolate(0, x, absPercent) : 0;

		if (thresholdY != null && Math.abs(translateY) > thresholdY) translateY = 0;
		if (thresholdX != null && Math.abs(translateX) > thresholdX) translateX = 0;

		const props = { x: translateX, y: translateY, force3D: true };

		if (opacityStart != null && opacityEnd != null) {
			props.opacity = gsap.utils.interpolate(opacityStart, opacityEnd, absPercent);
		}

		if (scaleStart != null && scaleEnd != null) {
			props.scale = gsap.utils.interpolate(scaleStart, scaleEnd, absPercent);
		}

		gsap.set(el, props);
	}

	/**
	 * Initialize parallax on elements
	 * @param {HTMLElement|null} scope
	 * @returns {Array}
	 */
	initParallax(scope = null) {
		const root = scope || document;
		const triggers = [];

		const elements = root.querySelectorAll('.vlt-element-parallax');

		elements.forEach(el => {
			if (el._parallaxInitialized) return;

			const { y, x } = this.parseParallax(el);
			const { start: opacityStart, end: opacityEnd } = this.parseOpacity(el);
			const { start: scaleStart, end: scaleEnd } = this.parseScale(el);
			const { y: thresholdY, x: thresholdX } = this.parseThreshold(el);

			if (!y && !x && opacityStart === null && scaleStart === null) return;

			gsap.set(el, {
				x: 0,
				y: 0,
				opacity: opacityStart ?? 1,
				scale: scaleStart ?? 1,
				transition: 'none',
				force3D: true
			});

			const parentSelector = el.getAttribute('data-element-parallax-parent');
			const parent = parentSelector ?
				el.closest(parentSelector) || document.querySelector(parentSelector) :
				null;

			if (parent) {
				// Parent-based parallax
				const start = el.getAttribute('data-element-start') || 'top top';
				const end = el.getAttribute('data-element-end') || 'bottom top';
				const toProps = {
					...(y && { y }),
					...(x && { x }),
					...(opacityEnd !== null && { opacity: opacityEnd }),
					...(scaleEnd !== null && { scale: scaleEnd })
				};

				const tween = gsap.fromTo(el, {
					x: 0,
					y: 0,
					opacity: opacityStart ?? 1,
					scale: scaleStart ?? 1
				}, {
					...toProps,
					immediateRender: false,
					scrollTrigger: {
						trigger: parent,
						start,
						end,
						scrub: true,
						invalidateOnRefresh: true
					}
				});

				triggers.push(tween.scrollTrigger);

				if (!el._resizeBound) {
					el._resizeBound = true;
					this.debounceResize(() => tween.scrollTrigger?.refresh());
				}
			} else {
				// Viewport-based parallax
				const updateElementData = () => {
					const rect = el.getBoundingClientRect();
					el._parallaxData = {
						width: rect.width,
						height: rect.height,
						y: rect.top + this.getWindowInfo().scrollY,
						x: rect.left
					};
				};

				updateElementData();
				const update = () => this.updateElementTransform(
					el, y, x, opacityStart, opacityEnd, scaleStart, scaleEnd, thresholdY, thresholdX
				);
				update();

				const scrollTrigger = ScrollTrigger.create({
					start: 'top bottom',
					end: 'bottom top',
					scrub: true,
					invalidateOnRefresh: true,
					onRefresh: () => {
						updateElementData();
						update();
					},
					onUpdate: update
				});

				triggers.push(scrollTrigger);

				if (!el._resizeBound) {
					el._resizeBound = true;
					this.debounceResize(() => {
						updateElementData();
						scrollTrigger?.refresh();
						update();
					});
				}

				el._parallaxUpdate = update;
				el._parallaxScrollTrigger = scrollTrigger;
			}

			el._parallaxInitialized = true;
			el.style.willChange = 'transform, opacity';
		});

		this.triggers.push(...triggers);
		return triggers;
	}


	/**
	 * Refresh all ScrollTriggers
	 */
	refresh() {
		if (!this.initialized) return;

		this.triggers.forEach(trigger => {
			if (trigger?.refresh) {
				trigger.refresh();
			}
		});
	}

	/**
	 * Setup refresh handlers for dynamic content
	 */
	setupRefreshHandlers() {
		// Wait for Elementor frontend to be fully initialized
		window.addEventListener('elementor/frontend/init', () => {
			if (!window.elementorFrontend?.hooks) return;

			// Hook into every widget/section/column that becomes ready
			elementorFrontend.hooks.addAction('frontend/element_ready/global', (scope) => {

				const element = scope instanceof HTMLElement ? scope : scope[0];

				// Initialize parallax (your existing method)
				const triggers = this.initParallax(element);

				// Refresh after a short delay
				setTimeout(() => {
					triggers.forEach(st => st?.refresh());
				}, 150);

			});
		});
	}

	/**
	 * Destroy all parallax instances
	 */
	destroy() {
		this.triggers.forEach(trigger => {
			if (trigger?.kill) {
				trigger.kill();
			}
		});

		this.triggers = [];
		this.initialized = false;
	}
}
