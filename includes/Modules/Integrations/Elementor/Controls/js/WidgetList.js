/* ========================================
 * Widget List Control - Populate widgets in editor
 * ======================================== */
(function() {
	'use strict';

	// Wait for Elementor to be ready
	jQuery(window).on('elementor:init', function() {

		// Register custom control view
		const WidgetListControlView = elementor.modules.controls.Select2.extend({

			onBeforeRender: function() {
				// Check if we're in a section or container
				if (!this.container || (this.container.type !== 'section' && this.container.type !== 'container')) {
					return;
				}

				const widgetsConfig = elementor.widgetsCache || elementor.config.widgets;
				const widgets = {};

				// Get the current container element
				const $element = this.container.view.$el;

				// Find all widgets in this container
				$element.find('.elementor-widget').each(function() {
					const $widget = jQuery(this);
					let widgetType = $widget.data('widget_type');

					if (widgetType) {
						// Remove the instance suffix (e.g., "heading.default" -> "heading")
						widgetType = widgetType.replace(/\..+$/, '');

						const config = widgetsConfig[widgetType];
						if (config && config.title) {
							// Use widget type as key to avoid duplicates
							widgets[widgetType] = config.title;
						}
					}
				});

				// Set the options for the dropdown
				this.model.set('options', widgets);
			}
		});

		// Register the control
		elementor.addControlView('vlt-widget-list', WidgetListControlView);
	});

})();
