var SettingsC = UCPMC.extend({
	init: function() {
		this.language = language;
		this.timezone = timezone;
		this.datetimeformat = datetimeformat;
		this.timeformat = timeformat;
		this.dateformat = dateformat;
	},
	poll: function(data) {
		//console.log(data)
	},
	showMessage: function(message, type, timeout, html = false) {
		type = typeof type !== "undefined" ? type : "info";
		timeout = typeof timeout !== "undefined" ? timeout : 2000;
		if(html){
			$("#settings-message").removeClass().addClass("alert alert-"+type+" text-left").html(message);
		}
		else{
			$("#settings-message").removeClass().addClass("alert alert-"+type+" text-center").text(message);
		}
		
		setTimeout(function() {
			$("#settings-message").addClass("hidden");
		}, timeout);
	},
	updateTimeDisplay: function() {
		if(language === "") {
			language = this.language;
			Cookies.set("lang", language, { path: window.location.pathname.replace(/\/?$/,'') });
		}
		if(timezone === "") {
			timezone = this.timezone;
		}
		moment.locale(language);

		var userdtf = $("#datetimeformat").val();
		userdtf = (userdtf !== "") ? userdtf : datetimeformat;
		$("#datetimeformat-now").text(moment().tz(timezone).format(userdtf));

		var usertf = $("#timeformat").val();
		usertf = (usertf !== "") ? usertf : timeformat;
		$("#timeformat-now").text(moment().tz(timezone).format(usertf));

		var userdf = $("#dateformat").val();
		userdf = (userdf !== "") ? userdf : dateformat;
		$("#dateformat-now").text(moment().tz(timezone).format(userdf));
	},
	displaySimpleWidgetSettings: function(widget_id) {
		var $this = this;
		setInterval(function() {
			$this.updateTimeDisplay();
		},1000);
		$("#datetimeformat, #timeformat, #dateformat").keydown(function() {
			$this.updateTimeDisplay();
		});
		$("#browserlang").on("click", function(e){
			e.preventDefault();
			var bl =  browserLocale();
			bl = bl.replace("-","_");
			if(typeof bl === 'undefined'){
				UCP.showAlert(_("The Browser Language could not be determined"),"warning");
			}else{
				$("#lang").multiselect('select', bl);
				$("#lang").multiselect('refresh');
				$("#lang").trigger("onchange",[$("#lang option:selected"), $("#lang option:selected").is(":checked")]);
			}
		});
		$("#systemlang").on("click", function(e){
			e.preventDefault();
			var sl = UIDEFAULTLANG;
			if(typeof sl === 'undefined'){
				UCP.showAlert(_("The PBX Language is not set"),"warning");
			}else{
				$("#lang").multiselect('select', sl);
				$("#lang").multiselect('refresh');
				$("#lang").trigger("onchange",[$("#lang option:selected"), $("#lang option:selected").is(":checked")]);
			}
		});
		$("#browsertz").on("click", function(e){
			e.preventDefault();
			var btz =  moment.tz.guess();
			if(typeof btz === 'undefined'){
				UCP.showAlert(_("The Browser Timezone could not be determined"),"warning");
			}else{
				$("#timezone").multiselect('select', btz);
				$("#timezone").multiselect('refresh');
				$("#timezone").trigger("onchange",[$("#timezone option:selected"), $("#timezone option:selected").is(":checked")]);
			}
		});
		$("#systemtz").on("click", function(e){
			e.preventDefault();
			var stz = PHPTIMEZONE;
			if(typeof stz === 'undefined'){
				UCP.showAlert(_("The PBX Timezone is not set"),"warning");
			}else{
				$("#timezone").multiselect('select', stz);
				$("#timezone").multiselect('refresh');
				$("#timezone").trigger("onchange",[$("#timezone option:selected"), $("#timezone option:selected").is(":checked")]);
			}
		});
		$("#timezone").on("onchange", function(el, option, checked) {
			$.post( "ajax.php?module=Settings&command=settings", { key: "timezone", value: option.val() }, function( data ) {
				if(data.status) {
					timezone = option.val();
					$this.updateTimeDisplay();
					$this.showMessage(_("Success!"),"success");
					UCP.showConfirm(_("UCP needs to reload, ok?"), 'warning', function() {
						window.location.reload();
					});
				} else {
					$this.showMessage(data.message,"danger");
				}
			});
		});
		$("#lang").on("onchange", function(el, option, checked) {
			$.post( "ajax.php?module=Settings&command=settings", { key: "language", value: option.val() }, function( data ) {
				if(data.status) {
					language = option.val();
					$this.showMessage(_("Success!"),"success");
					$this.updateTimeDisplay();
					Cookies.set("lang", option.val(), { path: window.location.pathname.replace(/\/?$/,'') });
					UCP.showConfirm(_("UCP needs to reload, ok?"), 'warning', function() {
						window.location.reload();
					});
				} else {
					$this.showMessage(data.message,"danger");
				}
			});

		});
		if (Notify.isSupported()) {
			$("#ucp-settings .desktopnotifications-group").removeClass("hidden");
			$("#ucp-settings input[name=\"desktopnotifications\"]").prop("checked", UCP.notify);
			$("#ucp-settings input[name=\"desktopnotifications\"]").change(function() {
				if (!UCP.notify && $(this).is(":checked")) {
					Notify.requestPermission(function() {
						UCP.notificationsAllowed();
						$("#ucp-settings input[name=\"desktopnotifications\"]").prop("checked", true);
					}, function() {
						UCP.showAlert(_("Enabling notifications was denied"),"danger");
						UCP.notificationsDenied();
						$("#ucp-settings input[name=\"desktopnotifications\"]").prop("checked", false);
					});
				} else {
					UCP.notify = false;
				}
			});
		}

		var restartTour = false;
		$("#ucp-settings input[name=\"tour\"]").prop("checked", false);
		$("#ucp-settings input[name=\"tour\"]").change(function() {
			if($(this).is(":checked")) {
				restartTour = true;
			} else {
				restartTour = false;
			}
			$.post( UCP.ajaxUrl + "?module=ucptour&command=tour", { state: (restartTour ? 1 : 0) }, function( data ) {

			});
		});

		$("#widget_settings").one('hidden.bs.modal', function() {
			if(restartTour) {
				UCP.Modules.Ucptour.tour.restart();
			}
		});

		$("#update-pwd").click(function(e) {
			e.preventDefault();
			e.stopPropagation();
			var password = $("#pwd").val(), confirm = $("#pwd-confirm").val();
			if (password !== "" && password != "******" && confirm !== "") {
				if (confirm != password) {
					$this.showMessage(_("Password Confirmation Didn't Match!"),"danger");
				} else {
					$.post( "ajax.php?module=Settings&command=settings", { key: "password", value: confirm }, function( data ) {
						if (data.status) {
							$this.showMessage(_("Saved!"),"success");
							UCP.showConfirm(_("UCP needs to reload, ok?"), 'warning', function() {
								window.location.reload();
							});
						} else {
							$this.showMessage(data.message,"warning", 3000,  true);

						}
					});
				}
			} else {
				$this.showMessage(_("Password has not changed!"));
			}
		});

		$("#username").blur(function() {
			new_user = $(this).val();
			if($(this).val() != $(this).data("prevusername")) {				
				UCP.showConfirm(_("Are you sure you wish to change your username? UCP will reload after"), 'warning', function() {
					$.post( "ajax.php?module=Settings&command=settings", { key: "username", value: new_user}, function( data ) {
						if(data.status) {
							$this.showMessage(_("Username has been changed, reloading"),"success");
							window.location.reload();
						} else {
							$this.showMessage(data.message,"danger");
						}
					});
				});
			}
		});
		$("#userinfo input[type!=checkbox][type!=radio][name!=dateformat][name!=timeformat][name!=datetimeformat]").blur(function() {
			var getValueOtherInput = {};
			var filterInput = ["displayname", "fname", "lname","title","company"];
			$("#userinfo input").each(function() {
				var name = $(this).prop("name");
				if (filterInput.includes(name)) {
					var value = $(this).val();
					getValueOtherInput[name] = value;
				}
			});
			$.post( "ajax.php?module=Settings&command=settings", { key: $(this).prop("name"), value: $(this).val(), OtherInputValues:getValueOtherInput }, function( data ) {
				if (data.status) {
					$this.showMessage(_("Saved!"),"success");
				} else {
					$this.showMessage(data.message,"danger");
				}
				$(this).off("blur");
			});
		});
		$("#dateformat, #timeformat, #datetimeformat").blur(function() {
			var name = $(this).prop("name"),
					value = $(this).val();
			$.post( "ajax.php?module=Settings&command=settings", { key: name, value: value }, function( data ) {
				if (data.status) {
					if(value === "" && typeof $this[name] === "string") {
						window[name] = $this[name];
					} else {
						window[name] = value;
					}
					$this.showMessage(_("Saved!"),"success");
				} else {
					$this.showMessage(data.message,"danger");
				}
				$(this).off("blur");
			});
		});
		if($("#Contactmanager-image").length) {
			/**
			 * Drag/Drop/Upload Files
			 */
			$('#contactmanager_dropzone').on('drop dragover', function (e) {
				e.preventDefault();
			});
			$('#contactmanager_dropzone').on('dragleave drop', function (e) {
				$(this).removeClass("activate");
			});
			$('#contactmanager_dropzone').on('dragover', function (e) {
				$(this).addClass("activate");
			});
			var supportedRegExp = "png|jpg|jpeg";
			$( document ).ready(function() {
				$('#contactmanager_imageupload').fileupload({
					dataType: 'json',
					dropZone: $("#contactmanager_dropzone"),
					add: function (e, data) {
						//TODO: Need to check all supported formats
						var sup = "\.("+supportedRegExp+")$",
								patt = new RegExp(sup),
								submit = true;
						$.each(data.files, function(k, v) {
							if(!patt.test(v.name.toLowerCase())) {
								submit = false;
								alert(_("Unsupported file type"));
								return false;
							}
						});
						if(submit) {
							$("#contactmanager_upload-progress .progress-bar").addClass("progress-bar-striped active");
							data.submit();
						}
					},
					drop: function () {
						$("#contactmanager_upload-progress .progress-bar").css("width", "0%");
					},
					dragover: function (e, data) {
					},
					change: function (e, data) {
					},
					done: function (e, data) {
						$("#contactmanager_upload-progress .progress-bar").removeClass("progress-bar-striped active");
						$("#contactmanager_upload-progress .progress-bar").css("width", "0%");

						if(data.result.status) {
							$("#contactmanager_dropzone img").attr("src",data.result.url);
							$("#contactmanager_image").val(data.result.filename);
							$("#contactmanager_dropzone img").removeClass("hidden");
							$("#contactmanager_del-image").removeClass("hidden");
							$("#contactmanager_gravatar").prop('checked', false);
						} else {
							alert(data.result.message);
						}
					},
					progressall: function (e, data) {
						var progress = parseInt(data.loaded / data.total * 100, 10);
						$("#contactmanager_upload-progress .progress-bar").css("width", progress+"%");
					},
					fail: function (e, data) {
					},
					always: function (e, data) {
					}
				});

				$("#contactmanager_del-image").click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					var id = $("input[name=user]").val(),
							grouptype = 'userman';
					$.post( "ajax.php?&module=Contactmanager&command=delimage", {id: id, img: $("#contactmanager_image").val()}, function( data ) {
						if(data.status) {
							$("#contactmanager_image").val("");
							$("#contactmanager_dropzone img").addClass("hidden");
							$("#contactmanager_dropzone img").attr("src","");
							$("#contactmanager_del-image").addClass("hidden");
							$("#contactmanager_gravatar").prop('checked', false);
						}
					});
				});

				$("#contactmanager_gravatar").change(function() {
					if($(this).is(":checked")) {
						var id = $("input[name=user]").val(),
								grouptype = 'userman';
						if($("#email").val() === "") {
							alert(_("No email defined"));
							$("#contactmanager_gravatar").prop('checked', false);
							return;
						}
						var t = $("label[for=contactmanager_gravatar]").text();
						$("label[for=contactmanager_gravatar]").text(_("Loading..."));
						$.post( "ajax.php?module=Contactmanager&command=getgravatar", {id: id, grouptype: grouptype, email: $("#email").val()}, function( data ) {
							$("label[for=contactmanager_gravatar]").text(t);
							if(data.status) {
								$("#contactmanager_dropzone img").data("oldsrc",$("#dropzone img").attr("src"));
								$("#contactmanager_dropzone img").attr("src",data.url);
								$("#contactmanager_image").data("old",$("#image").val());
								$("#contactmanager_image").val(data.filename);
								$("#contactmanager_dropzone img").removeClass("hidden");
								$("#contactmanager_del-image").removeClass("hidden");
							} else {
								alert(data.message);
								$("#contactmanager_gravatar").prop('checked', false);
							}
						});
					} else {
						var oldsrc = $("#contactmanager_dropzone img").data("oldsrc");
						if(typeof oldsrc !== "undefined" && oldsrc !== "") {
							$("#contactmanager_dropzone img").attr("src",oldsrc);
							$("#contactmanager_image").val($("#image").data("old"));
						} else {
							$("#contactmanager_image").val("");
							$("#contactmanager_dropzone img").addClass("hidden");
							$("#contactmanager_dropzone img").attr("src","");
							$("#contactmanager_del-image").addClass("hidden");
						}
					}
				});
			});
		}
	}
});
