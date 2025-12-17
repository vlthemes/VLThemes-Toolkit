(function ($) {
	'use strict';

	class CustomCssExtension {
		constructor() {
			this.initialized = false;
			this.init();
		}

		init() {
			$(window).on('elementor:init', () => {
				if (typeof ElementorProConfig === 'undefined') {
					this.initCustomCss();
				}
			});
		}

		initCustomCss() {
			if (typeof elementor === 'undefined') return;

			elementor.hooks.addFilter('editor/style/styleText', (css, context) => this.addElementCss(css, context));

			elementor.settings.page.model.on('change:custom_css', this.addPageCss.bind(this));
			elementor.on('preview:loaded', this.addPageCss.bind(this));

			this.initialized = true;
			console.info('Custom CSS Extension initialized');
		}

		addPageCss() {
			if (typeof elementor === 'undefined') return;

			const css = elementor.settings.page.model.get('custom_css');
			if (!css) return;

			const selector = `.elementor-page-${elementor.config.document.id}`;
			const processed = css.replace(/selector/g, selector);

			const $style = elementor.$previewContents.find('#elementor-custom-css-page');
			if ($style.length) $style.remove();

			elementor.$previewContents.find('head').append(
				`<style id="elementor-custom-css-page">${processed}</style>`
			);
		}

		addElementCss(css, context) {
			if (!context?.model) return css;

			const custom = context.model.get('settings')?.get('custom_css');
			if (!custom) return css;

			const selector = context.model.get('elType') === 'document'
				? elementor.config.document.settings.cssWrapperSelector
				: `.elementor-element.elementor-element-${context.model.get('id')}`;

			return css + '\n' + custom.replace(/selector/g, selector);
		}
	}

	new CustomCssExtension();

})(jQuery);
