(function () {
	'use strict';

	if (typeof jarallax === 'undefined') {
		console.warn('Jarallax not loaded.');
		return;
	}

	function refreshAllJarallax() {
		const elements = document.querySelectorAll('.jarallax');

		if (elements.length === 0) {
			return;
		}

		jQuery('.jarallax').jarallax('destroy').jarallax({
			speed: .9
		});
	};

	document.addEventListener('DOMContentLoaded', refreshAllJarallax);

	window.addEventListener('vlt:jarallax:refresh', () => {
		refreshAllJarallax();
	});

	class JarallaxHandler extends elementorModules.frontend.handlers.Base {

			getDefaultElements() {
				return {
					$element: this.$element
				};
			}

			onInit() {
				super.onInit();
				this.applySettings();
			}

			applySettings() {
				const settings = this.getElementSettings();
				const el = this.$element[0];

				this.syncDataAttributes(el, settings);
			}

			syncDataAttributes(el, settings) {
				const speed = settings.vlt_jarallax_speed?.size;
				if (speed !== undefined && speed !== '') {
					el.dataset.speed = speed;
				} else {
					delete el.dataset.speed;
				}

				if (settings.vlt_jarallax_type) {
					el.dataset.type = settings.vlt_jarallax_type;
				} else {
					delete el.dataset.type;
				}

				if (settings.vlt_jarallax_video_url) {
					el.dataset.jarallaxVideo = settings.vlt_jarallax_video_url;
				} else {
					delete el.dataset.jarallaxVideo;
				}
			}

			onElementChange(propertyName) {
				if (propertyName.startsWith('vlt_jarallax')) {
					clearTimeout(this.changeTimeout);
					this.changeTimeout = setTimeout(() => {
						this.applySettings();
						window.dispatchEvent(new Event('vlt:jarallax:refresh'));
					}, 300);
				}
			}
	}

	window.addEventListener('elementor/frontend/init', () => {
		elementorFrontend.hooks.addAction('frontend/element_ready/container', ($scope) => {
			elementorFrontend.elementsHandler.addHandler(JarallaxHandler, {
				$element: $scope
			});
			refreshAllJarallax();

		});
	});

})();
