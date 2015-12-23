var HomeC = UCPMC.extend({
	init: function() {
		this.packery = false;
		this.doit = null;
	},
	poll: function(data) {
		//console.log(data)
	},
	display: function(event) {
		$(window).on("resize.Home", this.resize);
		this.resize();
	},
	hide: function(event) {
		$(window).off("resize.Home");
		//$(".masonry-container").packery("destroy");
		this.packery = false;
	},
	contactClickOptions: function(type) {
		if (type != "number" || !UCP.Modules.Home.staticsettings.enableOriginate) {
			return false;
		}
		return [ { text: _("Originate Call"), function: "contactClickInitiate", type: "phone" } ];
	},
	contactClickInitiate: function(did) {
		var Webrtc = this,
				sfrom = "",
				temp = "",
				name = did,
				selected = "";
		if (UCP.validMethod("Contactmanager", "lookup")) {
			if (typeof UCP.Modules.Contactmanager.lookup(did).displayname !== "undefined") {
				name = UCP.Modules.Contactmanager.lookup(did).displayname;
			} else {
				temp = String(did).length == 11 ? String(did).substring(1) : did;
				if (typeof UCP.Modules.Contactmanager.lookup(temp).displayname !== "undefined") {
					name = UCP.Modules.Contactmanager.lookup(temp).displayname;
				}
			}
		}
		$.each(UCP.Modules.Home.staticsettings.extensions, function(i, v) {
			sfrom = sfrom + "<option>" + v + "</option>";
		});

		selected = "<option value=\"" + did + "\" selected>" + name + "</option>";
			UCP.showDialog(_("Originate Call"),
			"<label for=\"originateFrom\">From:</label> <select id=\"originateFrom\" class=\"form-control\">" + sfrom + "</select><label for=\"originateTo\">To:</label><select class=\"form-control Tokenize Fill\" id=\"originateTo\" multiple>" + selected + "</select><button class=\"btn btn-default\" id=\"originateCall\" style=\"margin-left: 72px;\">" + _("Originate") + "</button>",
			200,
			250,
			function() {
				$("#originateTo").tokenize({ maxElements: 1, datas: "index.php?quietmode=1&module=webrtc&command=contacts" });
				$("#originateCall").click(function() {
					setTimeout(function() {
						UCP.Modules.Home.originate();
					}, 50);
				});
				$("#originateTo").keypress(function(event) {
					if (event.keyCode == 13) {
						setTimeout(function() {
							UCP.Modules.Home.originate();
						}, 50);
					}
				});
			}
		);
	},
	refresh: function(module, id) {
		$("#"  +  module  +  "-title-"  +  id + " i.fa-refresh").addClass("fa-spin");
		$.post( "?quietmode=1&module=" + module + "&command=homeRefresh&id=" + id, {}, function( data ) {
			$("#" + module + "-title-" + id + " i.fa-refresh").removeClass("fa-spin");
			$("#" + module + "-content-" + id).html(data.content);
		});
	},
	originate: function() {
		if ($("#originateTo").val() !== null && $("#originateTo").val()[0] === "") {
			alert(_("Nothing Entered"));
			return;
		}
		$.post( "index.php?quietmode=1&module=home&command=originate",
						{ from: $("#originateFrom").val(),
						to: $("#originateTo").val()[0] },
						function( data ) {
							if (data.status) {
								UCP.closeDialog();
							}
						}
		);
	},
	resize: function() {
		var wasPackeryEnabled = this.packery;
		this.packery = $(window).width() >= 768;
		if (this.packery !== wasPackeryEnabled) {
			if (this.packery) {
				clearTimeout(this.doit);
				this.doit = setTimeout(function() {
					$(".widget").css("width", "33.33%");
					$(".widget").css("margin-bottom", "");
					$(".masonry-container").packery({
						columnWidth: 40,
						gutter: 10,
						itemSelector: ".widget"
					});
				}, 100);
			} else {
				this.packery = false;
				$(".masonry-container").packery("destroy");
				$(".widget").css("width", "100%");
				$(".widget").css("margin-bottom", "10px");
			}
		} else if (!this.packery) {
			$(".widget").css("width", "100%");
			$(".widget").css("margin-bottom", "10px");
		}
	}
});

$(document).bind("logIn", function( event ) {
	$("#settings-menu a.originate").on("click", function() {
		var sfrom = "";
		$.each(UCP.Modules.Home.staticsettings.extensions, function(i, v) {
			sfrom = sfrom + "<option>" + v + "</option>";
		});

		UCP.showDialog(_("Originate Call"),
			"<label for=\"originateFrom\">From:</label> <select id=\"originateFrom\" class=\"form-control\">" + sfrom + "</select><label for=\"originateTo\">To:</label><select class=\"form-control Tokenize Fill\" id=\"originateTo\" multiple></select><button class=\"btn btn-default\" id=\"originateCall\" style=\"margin-left: 72px;\">" + _("Originate") + "</button>",
			200,
			250,
			function() {
				$("#originateTo").tokenize({ maxElements: 1, datas: "index.php?quietmode=1&module=home&command=contacts" });
				$("#originateCall").click(function() {
					setTimeout(function() {
						UCP.Modules.Home.originate();
					}, 50);
				});
				$("#originateTo").keypress(function(event) {
					if (event.keyCode == 13) {
						setTimeout(function() {
							UCP.Modules.Home.originate();
						}, 50);
					}
				});
			}
		);
	});
});
