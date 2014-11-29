var SettingsC = UCPMC.extend({
	init: function() {
		this.packery = false;
		this.doit = null;
		this.modules = [];
	},
	poll: function(data) {
		//console.log(data)
	},
	display: function(event) {
		$(window).on("resize.Settings", Settings.resize);
		this.resize();
		$.each(modules, function( index, module ) {
			if (typeof window[module] == "object" && typeof window[module].settingsDisplay == "function") {
				window[module].settingsDisplay();
			} else if (UCP.validMethod(module, "settingsDisplay")) {
				UCP.Modules[module].settingsDisplay();
			}
		});
	},
	hide: function(event) {
		$(window).off("resize.Settings");
		//$(".masonry-container").packery("destroy");
		Settings.packery = false;
		$.each(modules, function( index, module ) {
			if (typeof window[module] == "object" && typeof window[module].settingsHide == "function") {
				window[module].settingsDisplay();
			} else if (UCP.validMethod(module, "settingsHide")) {
				UCP.Modules[module].settingsHide();
			}
		});
	},
	resize: function() {
		var wasPackeryEnabled = Settings.packery;
		Settings.packery = $(window).width() >= 768;
		if (Settings.packery !== wasPackeryEnabled) {
			if (Settings.packery) {
				clearTimeout(this.doit);
				this.doit = setTimeout(function() {
					$(".section").css("width", "300px");
					$(".section").css("margin-bottom", "");
					$(".masonry-container").packery({
						columnWidth: 40,
						itemSelector: ".section"
					});
				}, 100);
			} else {
				Settings.packery = false;
				$(".masonry-container").packery("destroy");
				$(".section").css("width", "100%");
				$(".section").css("margin-bottom", "10px");
			}
		} else if (!Settings.packery) {
			$(".section").css("width", "100%");
			$(".section").css("margin-bottom", "10px");
		}
	}
}), Settings = new SettingsC();
