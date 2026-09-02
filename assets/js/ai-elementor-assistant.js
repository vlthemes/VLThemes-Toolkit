(function ($) {

	"use strict";

	if (typeof vlt_toolkit_ai_assistant === "undefined") {
		return;
	}

	var STRINGS = vlt_toolkit_ai_assistant.strings;

	// Control types we can safely read/write via model.setSetting().
	var SUPPORTED_CONTROL_TYPES = {
		text: "text",
		textarea: "text",
		wysiwyg: "wysiwyg"
	};

	var VLTToolkitAIAssistant = {

		panel: null,
		launcher: null,
		currentModel: null,
		currentControlKey: null,
		currentFieldType: "text",
		generatedContent: "",

		revealLauncher: function () {
			if (this.launcher) {
				this.launcher.addClass("is-visible");

				if (window.console && window.console.debug) {
					console.debug("VLT AI Assistant: launcher button revealed.");
				}
			}
		},

		buildPanel: function () {
			if (this.panel) {
				return;
			}

			var self = this;

			var $panel = $(
				'<div id="vlt-toolkit-ai-assistant-panel" class="vlt-toolkit-ai-assistant-panel">' +
					'<div class="vlt-toolkit-ai-assistant-panel__header">' +
						"<span>" + STRINGS.title + "</span>" +
						'<button type="button" class="vlt-toolkit-ai-assistant-panel__close" aria-label="Close">&times;</button>' +
					"</div>" +
					'<div class="vlt-toolkit-ai-assistant-panel__body">' +
						'<p class="vlt-toolkit-ai-assistant-panel__target"></p>' +
						"<label>" + STRINGS.promptLabel + "</label>" +
						'<textarea class="vlt-toolkit-ai-assistant-panel__prompt" rows="3" placeholder="' + STRINGS.promptPlaceholder + '"></textarea>' +
						'<div class="vlt-toolkit-ai-assistant-panel__error" style="display:none;"></div>' +
						'<div class="vlt-toolkit-ai-assistant-panel__result" style="display:none;"></div>' +
						'<div class="vlt-toolkit-ai-assistant-panel__actions">' +
							'<button type="button" class="button button-primary vlt-toolkit-ai-assistant-panel__generate">' + STRINGS.generate + "</button>" +
							'<button type="button" class="button vlt-toolkit-ai-assistant-panel__insert" style="display:none;">' + STRINGS.insert + "</button>" +
						"</div>" +
					"</div>" +
				"</div>"
			);

			$("body").append($panel);
			this.panel = $panel;

			// Floating launcher button, always visible while editing.
			var launcherIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 592 568"><path fill="currentColor" d="M122.873 1.562c-.533.933-2.4 7.066-4 13.733-9.733 38.4-39.333 75.067-75.066 93.333-7.2 3.6-21.067 8.4-40 13.867-2 .667-3.467 2.133-3.734 3.867-.533 3.333 1.6 4.533 14.267 7.6 31.734 8 64 30.266 82.667 57.333 9.333 13.467 18.266 32.667 21.866 46.933 3.467 13.6 4.267 15.067 7.6 15.067 3.2 0 3.6-.667 6.667-12.933 13.733-53.734 53.467-93.334 107.467-107.067 18.266-4.667 16.8-8.667-5.467-14.667-48.4-13.066-88.4-53.733-101.2-103.2-1.867-7.2-3.733-13.6-4.267-14.266-1.066-1.734-5.6-1.467-6.8.4M379.94 111.162c-.933 1.066-3.066 8.133-4.933 15.6-11.067 46.4-30.934 82.4-63.2 114.533-19.734 19.733-35.467 31.2-60 43.867-19.334 9.866-34.4 15.333-54.934 20.133-13.866 3.2-16.266 4.4-17.6 8.8-1.6 5.467 2.8 8.4 17.867 11.733 86.8 19.6 155.733 87.334 176.667 173.467 4.4 18.533 5.733 21.733 8.8 22.667 3.467 1.2 7.6-.4 9.2-3.2.666-1.334 2.4-8 4-14.934 12.533-58 49.867-112.133 99.333-144.666 23.867-15.6 52.667-27.734 80.667-33.867 12.933-2.8 16-4.667 16-10 0-4.8-3.867-6.8-20-10.667-28.267-6.8-51.867-16.8-75.2-31.866-50.267-32.667-86.533-84.134-100-142.134-4.8-20.8-5.067-21.333-10.667-21.333-2.4 0-5.2.8-6 1.867M163.14 410.895c-.266.933-2.133 6.533-4 12.4-9.866 30-33.333 52.4-63.733 60.667-11.867 3.2-11.333 6 1.733 9.866 16.133 4.667 26 10.667 39.333 24.134 13.2 13.2 20 24.533 24.4 41.333 1.867 7.2 2.667 8.667 4.8 8.4 1.867-.267 3.067-2.533 4.667-9.067 2.933-11.333 11.867-28.266 19.333-36.8 11.6-13.2 31.334-25.466 46.934-28.933 6-1.333 7.866-2.4 7.866-4.267 0-1.733-1.866-2.8-7.333-4.133-10.8-2.8-28.267-11.733-37.333-19.333-13.867-11.467-26-30.8-29.733-47.2-1.2-5.467-2.534-8.134-4-8.4-1.334-.267-2.534.266-2.934 1.333"/></svg>';

			var $launcher = $(
				'<button type="button" id="vlt-toolkit-ai-assistant-launcher" class="vlt-toolkit-ai-assistant-launcher" title="' + STRINGS.title + '"></button>'
			);
			$launcher.html(launcherIcon);
			$("body").append($launcher);
			this.launcher = $launcher;

			$launcher.on("click", function () {
				self.togglePanel();
			});

			$panel.find(".vlt-toolkit-ai-assistant-panel__close").on("click", function () {
				self.hidePanel();
			});

			$panel.find(".vlt-toolkit-ai-assistant-panel__generate").on("click", function () {
				self.generate();
			});

			$panel.find(".vlt-toolkit-ai-assistant-panel__insert").on("click", function () {
				self.insert();
			});
		},

		bindElementorHooks: function () {
			var self = this;

			if (typeof elementor === "undefined" || !elementor.hooks) {
				console.warn("VLT AI Assistant: elementor.hooks is not available, editor integration disabled.");
				return;
			}

			elementor.hooks.addAction("panel/open_editor/widget", function (panel, model, view) {
				self.setActiveWidget(model, view);
			});

			elementor.hooks.addAction("panel/open_editor/global", function () {
				self.clearActiveWidget();
			});
		},

		setActiveWidget: function (model, view) {
			this.currentModel = model;
			this.currentView = view;

			if (window.console && window.console.debug) {
				console.debug(
					"VLT AI Assistant: widget selected",
					model.get ? model.get("widgetType") : model,
					"controls:", model.controls,
					"settings:", model.get ? model.get("settings") : null
				);
			}

			this.updateTargetLabel();
		},

		clearActiveWidget: function () {
			this.currentModel = null;
			this.currentView = null;
			this.currentControlKey = null;
			this.updateTargetLabel();
		},

		// Ignore settings keys that are never human-written copy (internal/meta/id/style fields).
		IGNORED_KEY_PATTERN: /^(_|id$|css_|link$|url$|hash$|template_id$)/i,

		// Find the first supported text-bearing control on the active widget's model.
		//
		// Elementor has two widget architectures: legacy widgets (control definitions
		// available on model.controls, values on model.get('settings')) and newer
		// "atomic" widgets (e.g. native Heading/Text), which may not expose usable
		// control-type metadata on model.controls at all. To work for both, this looks
		// at the control definitions when present (for an accurate type/label), and
		// otherwise falls back to scanning the raw settings values for plain strings.
		findWritableControl: function () {
			if (!this.currentModel || !this.currentModel.getSetting) {
				return null;
			}

			var controls = this.currentModel.controls || {};
			var settings = this.currentModel.get("settings");
			var key;

			// Pass 1: use control metadata when available, preferring a field that already has content.
			for (key in controls) {
				if (!controls.hasOwnProperty(key)) {
					continue;
				}

				var control = controls[key];

				if (control && SUPPORTED_CONTROL_TYPES.hasOwnProperty(control.type)) {
					var currentValue = settings ? settings.get(key) : "";

					if (currentValue && String(currentValue).trim().length > 0) {
						return { key: key, type: control.type, label: control.label || key };
					}
				}
			}

			for (key in controls) {
				if (!controls.hasOwnProperty(key)) {
					continue;
				}

				var control2 = controls[key];

				if (control2 && SUPPORTED_CONTROL_TYPES.hasOwnProperty(control2.type)) {
					return { key: key, type: control2.type, label: control2.label || key };
				}
			}

			// Pass 2: no usable control metadata (e.g. an atomic widget) — scan raw settings
			// values for a plausible plain-text field instead.
			var attrs = settings && settings.attributes ? settings.attributes : null;

			if (attrs) {
				var bestKey = null;
				var bestValue = "";

				for (key in attrs) {
					if (!attrs.hasOwnProperty(key) || this.IGNORED_KEY_PATTERN.test(key)) {
						continue;
					}

					var value = attrs[key];

					if (typeof value !== "string") {
						continue;
					}

					var looksLikeHtml = /<\/?[a-z][\s\S]*>/i.test(value);

					if (value.trim().length > bestValue.trim().length) {
						bestKey = key;
						bestValue = value;
					} else if (!bestKey && value === "") {
						bestKey = key;
					}

					if (looksLikeHtml) {
						return { key: key, type: "wysiwyg", label: key };
					}
				}

				if (bestKey) {
					return { key: bestKey, type: "text", label: bestKey };
				}
			}

			return null;
		},

		updateTargetLabel: function () {
			var $target = this.panel.find(".vlt-toolkit-ai-assistant-panel__target");
			var control = this.findWritableControl();

			if (!control) {
				this.currentControlKey = null;
				$target.text(STRINGS.noSelection);
				return;
			}

			this.currentControlKey = control.key;
			this.currentFieldType = SUPPORTED_CONTROL_TYPES[control.type];
			$target.text(STRINGS.targetLabel + " " + control.label);
		},

		togglePanel: function () {
			if (this.panel.hasClass("is-visible")) {
				this.hidePanel();
			} else {
				this.showPanel();
			}
		},

		showPanel: function () {
			this.updateTargetLabel();
			this.panel.addClass("is-visible");
		},

		hidePanel: function () {
			this.panel.removeClass("is-visible");
		},

		showError: function (message) {
			this.panel.find(".vlt-toolkit-ai-assistant-panel__error").text(message).show();
		},

		clearError: function () {
			this.panel.find(".vlt-toolkit-ai-assistant-panel__error").hide().text("");
		},

		generate: function () {
			var self = this;
			var $prompt = this.panel.find(".vlt-toolkit-ai-assistant-panel__prompt");
			var prompt = $.trim($prompt.val());

			this.clearError();

			if (!prompt) {
				this.showError(STRINGS.emptyPrompt);
				return;
			}

			if (!this.currentModel || !this.currentControlKey) {
				this.showError(STRINGS.noSelection);
				return;
			}

			var $generateBtn = this.panel.find(".vlt-toolkit-ai-assistant-panel__generate");
			var $result = this.panel.find(".vlt-toolkit-ai-assistant-panel__result");
			var $insertBtn = this.panel.find(".vlt-toolkit-ai-assistant-panel__insert");

			$generateBtn.prop("disabled", true).text(STRINGS.generating);
			$insertBtn.hide();
			$result.hide().text("");

			$.ajax({
				url: vlt_toolkit_ai_assistant.ajaxUrl,
				method: "POST",
				data: {
					action: "vlt_toolkit_ai_generate_content",
					nonce: vlt_toolkit_ai_assistant.nonce,
					prompt: prompt,
					fieldType: self.currentFieldType
				}
			}).done(function (response) {
				if (response && response.success && response.data && response.data.content) {
					self.generatedContent = response.data.content;

					if (self.currentFieldType === "wysiwyg") {
						// Render as HTML so the preview reads like the final result, not raw markup.
						$result.html(response.data.content).show();
					} else {
						$result.text(response.data.content).show();
					}

					$insertBtn.show();
				} else {
					var message = (response && response.data && response.data.message) || STRINGS.error;
					self.showError(message);
				}
			}).fail(function () {
				self.showError(STRINGS.error);
			}).always(function () {
				$generateBtn.prop("disabled", false).text(STRINGS.generate);
			});
		},

		insert: function () {
			if (!this.currentModel || !this.currentControlKey || !this.generatedContent) {
				return;
			}

			var container = this.currentView && this.currentView.getContainer ? this.currentView.getContainer() : null;

			if (container && typeof $e !== "undefined" && $e.run) {
				// $e.run updates the container's data, the canvas preview, and
				// undo history. It does NOT, by itself, update the open panel's
				// control input — that only re-renders in response to the
				// model's own "change:external:<key>" event, which is fired by
				// setSetting()/setExternalChange() specifically. A real user
				// edit goes through setSetting() only (see Elementor's own
				// setSettingsModel, which calls $e.run then triggers
				// "settings:change") and the panel updates because it's the
				// same object as container.settings — but since we're calling
				// in from outside the control view, both calls are needed here
				// to cover the panel and the canvas/history reliably.
				var settings = {};
				settings[this.currentControlKey] = this.generatedContent;

				$e.run("document/elements/settings", {
					container: container,
					settings: settings
				});
			}

			// Updates the panel control via container.settings' own
			// "change:external:<key>" event (setSetting -> setExternalChange).
			this.currentModel.setSetting(this.currentControlKey, this.generatedContent);

			this.hidePanel();
		}
	};

	// Wait for Elementor's own loading overlay (#elementor-loading /
	// #elementor-preview-loading) to actually finish disappearing before
	// showing the launcher, so it doesn't appear on top of the preloader.
	// Elementor fades it out itself; poll until it's gone rather than
	// guessing a fixed delay tied to Elementor's internal animation timing.
	function waitForPreloaderToClear(callback) {
		var $preloader = $("#elementor-loading, #elementor-preview-loading");

		if ($preloader.length === 0 || $preloader.is(":hidden")) {
			callback();
			return;
		}

		var checkInterval = setInterval(function () {
			if ($preloader.is(":hidden")) {
				clearInterval(checkInterval);
				callback();
			}
		}, 100);
	}

	// Elementor fires "elementor:loaded" (editor bootstrap done) BEFORE
	// "elementor:init" (initial document opened) — the reverse of what the
	// names suggest. "elementor:init" is the more reliable "ready" signal
	// here since the panel/hooks API is what we actually depend on, so
	// bind and build directly on it rather than chasing "elementor:loaded",
	// which may already have fired by the time this script's listeners are
	// registered. The launcher itself waits for the preloader on top of that.
	$(window).on("elementor:init", function () {
		VLTToolkitAIAssistant.bindElementorHooks();
		VLTToolkitAIAssistant.buildPanel();

		waitForPreloaderToClear(function () {
			VLTToolkitAIAssistant.revealLauncher();
		});
	});

})(jQuery);
