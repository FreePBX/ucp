/**
 * This is the User Control Panel Object.
 *
 * License can be found in the license file inside the module directory
 * Copyright 2006-2014 Schmooze Com Inc.
 */

var UCPC = Class.extend({
	init: function() {
		this.loggedIn = false;
		this.pollID = null;
		this.polling = false;
		this.notify = null;
		this.activeModule = "Home";
		this.transitioning = false;
		this.lastScrollTop = 0;
		this.hidden = "hidden";
		this.ws = null;
		this.footerHidden = false;
		this.chatTimeout = {};
		this.i18n = null;
		this.messageBuffer = {};
		this.token = null;
		this.lastIO = null;
		this.Modules = {};
		this.calibrating = false;
		this.UCPSettings = {packery: true};
		this.ajaxUrl = '';
		this.urlParams = {};

		textdomain("ucp");
	},
	ready: function(loggedIn) {
		this.parseUrl();
		$(document).bind("logIn", function( event, username, password ) {UCP.logIn(event, username, password);});
		$(document).bind("logOut", function( event ) {UCP.logOut(event);});
		$(window).bind("online", function( event ) {UCP.online(event);});
		$(window).bind("offline", function( event ) {UCP.offline(event);});
		$(document).ajaxError(UCP.ajaxError);
		$(document).ajaxStart(UCP.ajaxStart);
		$(document).ajaxStop(UCP.ajaxStop);
		//if we are already logged in (the login window is missing)
		//in then throw the loggedIn trigger
		if (!$("#login-window").length) {
			UCP.setupDashboard();
			$(document).trigger("logIn");
		} else {
			UCP.setupLogin();
		}

		var setupBootstrapToggle = function(el) {
			var on = _('Enable'),
				off = _('Disable');

			on = typeof $(el).data("on") !== "undefined" ? $(el).data("on") : on;
			off = typeof $(el).data("off") !== "undefined" ? $(el).data("off") : off;
			$(el).bootstrapToggle({
				on: on,
				off: off
			});
		};
		var setupBootstrapTable = function(el) {
			$(el).bootstrapTable();
		};

		$(document).on("post-body.simplewidget", function() {
			$('.small-widget-content input[type=checkbox][data-toggle="toggle"]:visible').each(function() {
				setupBootstrapToggle(this);
			});
			$('.small-widget-content table[data-toggle="table"]:visible').each(function() {
				setupBootstrapTable(this);
			});
		});
		$(document).on("post-body.widgets",function(){
			$('.grid-stack input[type=checkbox][data-toggle="toggle"]:visible').each(function() {
				setupBootstrapToggle(this);
			});
			$('.grid-stack table[data-toggle="table"]:visible').each(function() {
				setupBootstrapTable(this);
			});
		});
		$(document).on("post-body.widgetsettings post-body.simplewidgetsettings",function(){
			var load = function() {
				$('.widget-settings-content input[type=checkbox][data-toggle="toggle"]:visible').each(function() {
					setupBootstrapToggle(this);
				});
				$('.widget-settings-content table[data-toggle="table"]:visible').each(function() {
					setupBootstrapTable(this);
				});
			};
			load();
			var loaded = [];
			//tab navigation
			$('.widget-settings-content a[data-toggle="tab"]').on("shown.bs.tab", function(e) {
				var href = $(e.target).attr("href");
				if(loaded.indexOf(href) === -1) {
					loaded.push(href);
					load();
				}
			});
		});

		this.callModulesByMethod("ready",$.url().param());
	},
	parseUrl: function() {
		var self = this;
		var path = window.location.pathname.toString().split('/');
		path[path.length - 1] = 'ajax.php';
		if (typeof window.location.origin == 'undefined') {
			// Oh look, IE. Hur Dur, I'm a bwowsah.
			window.location.origin = window.location.protocol+'//'+window.location.host;
			if (window.location.port.length !== 0) {
				window.location.origin = window.location.origin+':'+window.location.port;
			}
		}
		this.ajaxUrl = window.location.origin + path.join('/');
		if (window.location.search.length) {
			var params = window.location.search.split(/\?|&/);
			for (var i = 0, len = params.length; i < len; i++) {
				if (res = params[i].match(/(.+)=(.+)/)) {
					self.urlParams[res[1]] = res[2];
				}
			}
		}
	},
	callModuleByMethod: function() {
		var args = [],
				mdata = [];

		Array.prototype.push.apply( args, arguments );
		module = args.shift().modularize();
		method = args.shift();
		if(UCP.validMethod(module, method)) {
			return UCP.Modules[module][method].apply( UCP.Modules[module] , args );
		} else {
			return null;
		}
	},
	callModulesByMethod: function() {
		var args = [],
				mdata = [];

		if(typeof modules === "undefined") {
			return mdata;
		}

		Array.prototype.push.apply( args, arguments );
		method = args.shift();
		$.each(modules, function( index, module ) {
			if (UCP.validMethod(module, method)) {
				mdata[module] = UCP.Modules[module][method].apply( UCP.Modules[module] , args );
			}
		});
		return mdata;
	},
	ajaxStart: function() {
		//TODO: this doesnt exit anymore
		$("#nav-btn-settings i").addClass("fa-spin");
	},
	ajaxStop: function() {
		//TODO: this doesnt exit anymore
		$("#nav-btn-settings i").removeClass("fa-spin");
	},
	ajaxError: function(event, jqxhr, settings, exception) {
		if (exception !== "abort" && !$("#global-message-container").is(":visible")) {
			UCP.disconnect();
		}
	},
	setupLogin: function() {
		var btn = $("#btn-login"), fbtn = $("#btn-forgot");
		$(".action-switch span").click(function() {
			var hide = $(this).data("hide"), show = $(this).data("show");
			$("." + show).show();
			$("." + hide).hide();
			$("input[name!=username][name!=token][name!=ftoken]").val("");
		});
		$("#btn-forgot").click(function() {
			var otext = fbtn.text();
			if ($("input[name=email]").length > 0) {
				fbtn.prop("disabled", true);
				fbtn.text(_("Processing..."));
				if ($("input[name=username]").val().trim() === "" && $("input[name=email]").val().trim() === "") {
					alert(_("Please enter either a username or email address"));
					fbtn.prop("disabled", false);
					fbtn.text(otext);
				} else {
					var queryString = $("#frm-login").formSerialize();
					queryString = queryString + "&quietmode=1&module=User&command=forgot";
					$.post( "index.php", queryString, function( data ) {
						if (!data.status) {
							$("#error-msg").html(data.message).fadeIn("fast");
						} else {
							alert(_("Your reset link is in the mail!"));
						}
						fbtn.prop("disabled", false);
						fbtn.text(otext);
					});
				}
			}
			if ($("input[name=npass1]").length > 0) {
				var token = $("input[name=ftoken]").val(),
						pass1 = $("input[name=npass1]").val().trim(),
						pass2 = $("input[name=npass2]").val().trim();
				if (pass1 != pass2) {
					alert(_("New password and old password do not match"));
					return false;
				} else if (pass1 === "" || pass2 === "") {
					alert(_("Password fields can't be blank!"));
					return false;
				} else {
					var queryString = $("#frm-login").formSerialize();
					queryString = queryString + "&quietmode=1&module=User&command=reset";
					$.post( "index.php", queryString, function( data ) {
						if (!data.status) {
							$("#error-msg").html(data.message).fadeIn("fast");
						} else {
							alert(_("Password has been changed!"));
							$("#switch-login").click();
						}
					});

				}
			}
		});
		if ($("html").hasClass("history")) {
			$(document).on("submit", "#frm-login", function(event) {
				var queryString = $(this).formSerialize(),
						username = $("input[name=username]").val(),
						password = $("input[name=password]").val();

				btn.prop("disabled", true);
				btn.text(_("Processing..."));
				queryString = queryString + "&quietmode=1&module=User&command=login";
				$.post( "index.php", queryString, function( data ) {
					if (!data.status) {
						$("#error-msg").html(data.message).fadeIn("fast");
						$("#login-window").height("300");
						btn.prop("disabled", false);
						btn.text(_("Login"));
					} else {
						location.reload();
					}
				}, "json");
				return false;
			});
			btn.prop("disabled", false);
			btn.text(_("Login"));
		} else {
			//TODO: I guess we don't allow login...
			//Seriously though this probably means most of
			//the other functionality of UCP isn't supported as well.
			btn.text(_("Your Browser is unsupported at this time."));
			$(".jsalert").show();
			$(".jsalert").text(_("Your browser is unsupported at this time. Please upgrade or talk to your system administrator"));
			$("#login-window").height("300");
		}
		$(".main-block").addClass("hidden");
	},
	setupDashboard: function() {
		var totalNavs = 0, navWidth = 33, Ucp = this;
		//inite class autoloader
		UCP.autoload();

		if (!$("html").hasClass("history")) {
			UCP.showAlert(_("UCP is not supported in your browser"));
		}
		$("li.logout-widget").click(function(event) {
			$(document).trigger("logOut");
		});

		//This allows browsers to request user notifications from said user.
		$(document).click(function() {
			if (UCP.loggedIn && Notify.needsPermission() && UCP.notify === null) {
				Notify.requestPermission(UCP.notificationsAllowed(), UCP.notificationsDenied() );
			}
		});

		UCP.hidden = "hidden";

		// Standards:
		if (UCP.hidden in document) {
			$(document).on("visibilitychange", UCP.onchange);
		} else if ((UCP.hidden = "mozHidden") in document) {
			$(document).on("mozvisibilitychange", UCP.onchange);
		} else if ((UCP.hidden = "webkitHidden") in document) {
			$(document).on("webkitvisibilitychange", UCP.onchange);
		} else if ((UCP.hidden = "msHidden") in document) {
			$(document).on("msvisibilitychange", UCP.onchange);
		// IE 9 and lower:
		} else if ("onfocusin" in document) {
			$(document).on("onfocusin onfocusout", UCP.onchange);
		// All others:
		} else {
			$(window).on("onpageshow onpagehide onfocus onblur", UCP.onchange);
		}

	},
	onchange: function(evt) {
		var v = "visible", h = "hidden",
			evtMap = {
				focus: v, focusin: v, pageshow: v, blur: h, focusout: h, pagehide: h
			},
			state = "";

		evt = evt || window.event;
		if (evt.type in evtMap) {
			state = evtMap[evt.type];
		} else {
			state = UCP.hidden ? "hidden" : "visible";
		}
		UCP.callModulesByMethod("windowState",state);
	},
	wsconnect: function(namespace, callback) {
		//console.log(namespace);
		if (!this.loggedIn) {
			return false;
		}

		if(window.location.protocol != "https:" && !ucpserver.enabled) {
			return false;
		}

		if(window.location.protocol == "https:" && !ucpserver.enabledS) {
			return false;
		}

		//If we don't have a valid token then try to get one
		if (UCP.token === null) {
			$.post( "index.php", { "quietmode": 1, "command": "token", "module": "User" }, function( data ) {
				if (data.status && data.token !== null) {
					UCP.token = data.token;
					UCP.wsconnect(namespace, callback);
				} else {
					UCP.displayGlobalMessage(sprintf(_("Unable to get a token to use UCP Node Server because: '%s'"),data.message), "red", true);
					callback(false);
				}
			});
		} else {
			var host = ucpserver.host,
					port = ucpserver.port,
					portS = ucpserver.portS,
					socket = null;
			try {
				var connectString = (window.location.protocol == "https:") ? "wss://" + host + ":" + portS + "/" + namespace : "ws://" + host + ":" + port + "/" + namespace;
				socket = io(connectString, {
					reconnection: true,
					query: "token=" + UCP.token
				});
			}catch (err) {
				UCP.displayGlobalMessage(err, "red", true);
				callback(false);
			}
			var timeout = setTimeout(function(){
				window.s = socket;
				UCP.displayGlobalMessage(_("Unable to authenticate with the UCP Node Server"), "red", true);
				callback(false);
			}, 3000);
			socket.on("connect", function() {
				clearTimeout(timeout);
				UCP.lastIO = socket.io;
				UCP.removeGlobalMessage();
				callback(socket);
			});
			socket.on("connect_error", function(reason) {
				UCP.displayGlobalMessage(sprintf(_("Unable to connect to the UCP Node Server because: '%s'"),reason), "red", true);
				callback(false);
			});
		}
	},
	connect: function(username, password) {
		var $this = this;
		//Interval is in a callback to shortpoll to make sure we are "online"
		UCP.displayGlobalMessage(_("Connecting...."), "rgba(128, 128, 128, 0.5)", true);
		UCP.shortpoll(function() {
			UCP.pollID = setInterval(function() {
				UCP.shortpoll();
			},5000);
			$this.callModulesByMethod("connect",username,password);
			UCP.removeGlobalMessage();
			UCP.websocketConnect();
		});
	},
	disconnect: function() {
		clearInterval(this.pollID);
		this.pollID = null;
		this.polling = false;
		$("#nav-btn-settings i").removeClass("fa-spin");
		this.callModulesByMethod("disconnect");
		UCP.displayGlobalMessage(_("You are currently working in offline mode."), "rgba(128, 128, 128, 0.5)", true);
		UCP.websocketDisconnect();
	},
	websocketConnect: function() {
		if (UCP.lastIO !== null) {
			UCP.lastIO.reconnect();
		}
	},
	websocketDisconnect: function() {
		if (UCP.lastIO !== null) {
			UCP.lastIO.disconnect();
		}
	},
	autoload: function() {
		var Ucp = this;
		$.each(modules, function( index, module ) {
			var className = module + "C", UCPclass = null;
			if (typeof window[module] === "undefined") {
				if (typeof Ucp.Modules[module] === "undefined" && typeof window[className] === "function") {
					UCPclass = window[className];
					console.log("Auto Loading " + className);
					Ucp.Modules[module] = new UCPclass(Ucp);
					if(typeof moduleSettings[module] !== "undefined") {
						Ucp.Modules[module].staticsettings = moduleSettings[module];
					}
				}
			}
		});
	},
	shortpoll: function(callback) {
		if (!UCP.polling) {
			UCP.polling = true;
			var mdata = {};
			mdata = this.callModulesByMethod("prepoll",$.url().param());
			$.ajax({ url: "index.php", data: { quietmode: 1, command: "poll", data: $.url().param(), mdata: mdata }, success: function(data) {
				if (data.status) {
					if (typeof callback === "function") {
						callback();
					}
					$.each(data.modData, function( module, data ) {
						if (UCP.validMethod(module, "poll")) {
							UCP.Modules[module].poll(data, $.url().param());
						}
					});
				}
				UCP.polling = false;
			}, error: function(jqXHR, textStatus, errorThrown) {
				//We probably should logout on every event here... but
				if (jqXHR.status === 403) {
					$("li.logout-widget").click();
				}
			}, dataType: "json", type: "POST" });
		}
	},
	notificationsAllowed: function() {
		this.notify = true;
	},
	notificationsDenied: function() {
		this.notify = false;
	},
	hideDialog: function(callback) {
		this.closeDialog(callback);
	},
	closeDialog: function(callback) {
		$("#globalModal").one('hidden.bs.modal', function (e) {
			if (typeof callback === "function") {
				callback();
			}
		});
		$("#globalModal").modal('hide');
	},
	showAlert: function(message, type, callback_func){
		var type_class = "";
		switch(type) {
			case 'success':
				type_class = "alert-success";
			break;
			case 'warning':
				type_class = "alert-warning";
			break;
			case 'danger':
				type_class = "alert-danger";
			break;
			case 'info':
			default:
				type_class = "alert-info";
			break;
		}

		$("#alert_message").removeClass("alert-success alert-info alert-warning alert-danger");

		$("#alert_message").addClass(type_class);

		$("#alert_message").html(message);

		if(typeof callback_func == "function") {
			$(document).on("click", "#close_alert_button", function () {
				$("#alert_modal").modal("hide");
				callback_func();
			});
		}else {
			$(document).on("click", "#close_alert_button", function () {
				$("#alert_modal").modal("hide");
			});
		}

		$("#alert_modal").modal("show");
	},
	showConfirm: function(html, type, callback_func) {
		var type_class = "";
		switch(type) {
			case 'success':
				type_class = "alert-success";
			break;
			case 'warning':
				type_class = "alert-warning";
			break;
			case 'danger':
				type_class = "alert-danger";
			break;
			case 'info':
			default:
				type_class = "alert-info";
			break;
		}

		$("#confirm_content").removeClass("alert-success alert-info alert-warning alert-danger");

		$("#confirm_content").addClass(type_class);
		$("#confirm_content").html(html);

		$('#confirm_modal').one('shown.bs.modal', function () {
			$("#modal_confirm_button").one("click", function(){
				if(typeof callback_func == "function"){
					callback_func();
				}
			});
		});

		$('#confirm_modal').one('hidden.bs.modal', function () {
			$("#modal_confirm_button").off("click");
		});

		$('#confirm_modal').modal('show');
	},
	showDialog: function(title, content, footer, callback) {
		var show = function() {
			$('#globalModal .modal-title').html(title);
			$('#globalModal .modal-body').html(content);
			$('#globalModal .modal-footer').html(footer);
			$("#globalModal").one('shown.bs.modal', function (e) {
				if (typeof callback === "function") {
					callback();
				}
			});
			$("#globalModal").modal('show');
		};
		if($("#globalModal").is(":visible")) {
			$("#globalModal").modal('hide');
			$("#globalModal").one('hidden.bs.modal', function (e) {
				show();
			});
		} else {
			show();
		}

	},
	addChat: function(module, id, icon, from, to, cnam, msgid, message, callback, htmlV, direction) {
		var html = (typeof htmlV !== "undefined") ? htmlV : false;
		if (!$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).length && (typeof this.messageBuffer[id] === "undefined")) {
			//add placeholder
			if (typeof msgid !== "undefined") {
				this.messageBuffer[id] = [];
				this.messageBuffer[id].push({
					sender: cnam,
					msgid: msgid,
					message: message
				});
			}
			var newWindow = (typeof msgid === "undefined");
			$.ajax({ url: "index.php", data: { quietmode: 1, command: "template", type: "chat", newWindow: newWindow, template: { module: module, icon: icon, id: id, to: to, from: from } }, success: function(data) {
				$( "#messages-container" ).append( data.contents );
				$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).fadeIn("fast", function() {
					if (typeof msgid !== "undefined") {
						if (typeof UCP.messageBuffer[id] !== "undefined") {
							$.each(UCP.messageBuffer[id], function(i, v) {
								UCP.addChatMessage(id, v.sender, v.msgid, v.message, false, html, direction);
							});
							delete UCP.messageBuffer[id];
						}
					} else {
						if (!$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).hasClass("expand")) {
							$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).one("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend", function() {
								$(this).find("textarea").focus();
							});
							$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).addClass("expand");
							$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).find(".fa-arrow-up").addClass("fa-arrow-down").removeClass("fa-arrow-up");
						}
					}
					if (typeof callback === "function") {
						callback();
					}
					$(document).trigger( "chatWindowAdded", [ id, module,  $( "#messages-container .message-box[data-id=\"" + id + "\"]" ) ] );
				});

				$( "#messages-container .message-box .title-bar[data-id=\"" + id + "\"]" ).on("click", function(event) {
					if (!$(event.target).hasClass("cancelExpand")) {
						var container = $("#messages-container .message-box[data-id=\"" + id + "\"]");
						if (!container.hasClass("expand")) {
							container.find(".fa-arrow-up").addClass("fa-arrow-down").removeClass("fa-arrow-up");
						} else {
							container.find(".fa-arrow-down").addClass("fa-arrow-up").removeClass("fa-arrow-down");
						}
						container.one("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend", function() {
							if (container.hasClass("expand")) {
								$(this).find("textarea").focus();
							}
						});
						container.toggleClass("expand");
					} else {
						UCP.removeChat($(this).data("id"));
					}
				});
				$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").imagesLoaded( function() {
					$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").animate({ scrollTop: $("#messages-container .message-box[data-id=\"" + id + "\"] .chat")[0].scrollHeight }, "slow");
				});
			}, dataType: "json", type: "POST" });
		} else {
			if (typeof msgid !== "undefined") {
				UCP.addChatMessage(id, cnam, msgid, message, false, html, direction);
			}
			return null;
		}
	},
	removeChat: function(id) {
		if (typeof UCP.chatTimeout[id] !== "undefined") {
			clearTimeout(UCP.chatTimeout[id]);
		}
		$( "#messages-container .title-bar[data-id=\"" + id + "\"]" ).off("click");
		$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).fadeOut("fast", function() {
			$(this).remove();
			$(document).trigger( "chatWindowRemoved", [ id ] );
		});
	},
	addChatMessage: function(id, cnam, msgid, message, newmsg, htmlV, direction) {
		var emailre = /([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})/ig,
				urlre = /((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)/ig,
				html = (typeof htmlV !== "undefined") ? htmlV : false;
		if(!html) {
			message = emojione.toImage(htmlEncode(message));
			message = message.replace(urlre,"<a href='$1' target='_blank'>$1</a>");
			message = message.replace(emailre,"<a href='mailto:$1@$2.$3' target='_blank'>$1@$2.$3</a>");
		}

		if ($( "#messages-container .message-box[data-id=\"" + id + "\"]" ).length) {
			if (!$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).hasClass("expand")) {
				$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).addClass("expand");
				$( "#messages-container .message-box[data-id=\"" + id + "\"] .fa-arrow-up" ).addClass("fa-arrow-down").removeClass("fa-arrow-up");
			}
			$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).data("last-msg-id", msgid);

			if (typeof newmsg === "undefined" || newmsg) {
				$( "#messages-container .title-bar[data-id=\"" + id + "\"]" ).css("background-color", "#428bca");
			}
			$( "#messages-container .message-box[data-id=\"" + id + "\"] .chat" ).append("<div class='message "+direction+"' data-id='" + msgid + "'>" + message + "</div>");
			if (UCP.chatTimeout[id] !== undefined && UCP.chatTimeout[id] !== null) {
				clearTimeout(UCP.chatTimeout[id]);
			}

			var d = moment().tz(timezone).calendar();
			UCP.chatTimeout[id] = setTimeout(function() {
				$( "#messages-container .message-box[data-id=\"" + id + "\"] .chat" ).append("<div class=\"status\" data-type=\"date\">Sent at " + d + "</div>");
				$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").imagesLoaded( function() {
					$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").animate({ scrollTop: $("#messages-container .message-box[data-id=\"" + id + "\"] .chat")[0].scrollHeight }, "slow");
				});
			}, 60000);

			$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").imagesLoaded( function() {
				$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").animate({ scrollTop: $("#messages-container .message-box[data-id=\"" + id + "\"] .chat")[0].scrollHeight }, "slow");
			});
		} else if (typeof this.messageBuffer[id] !== "undefined") {
			this.messageBuffer[id].push({
				sender: cnam,
				msgid: msgid,
				message: message
			});
		}
	},
	displayGlobalMessage: function(message, color, sticky) {
		color = (typeof color !== "undefined") ? color : "#f76a6a;";
		sticky = (typeof sticky !== "undefined") ? sticky : false;
		$("#global-message").text(message);
		$("#global-message-container").css("background-color", color);
		$("#global-message-container").fadeIn("slow", function() {
			if (!sticky) {
				setTimeout(function() {
					$("#global-message-container").fadeOut("slow");
				}, 3000);
			}
		});
	},
	removeGlobalMessage: function() {
		$("#global-message-container").fadeOut("slow");
	},
	toTitleCase: function(str) {
		return str.replace(/\w\S*/g, function(txt) { return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase(); });
	},
	validMethod: function(module, method) {
		if (typeof this.Modules[module] == "object" &&
			typeof this.Modules[module][method] == "function") {
			return true;
		} else {
			return false;
		}
	},
	online: function(event) {
		if (this.loggedIn && this.pollID === null) {
			this.connect();
		}
	},
	offline: function(event) {
		this.disconnect();
	},
	logIn: function(event, username, password) {
		//TODO: need to figure out text domains!
		textdomain(this.activeModule.toLowerCase());
		this.loggedIn = true;
		this.connect(username, password);
		if (!Notify.needsPermission() && this.notify === null) {
			this.notify = true;
		}
	},
	logOut: function(event) {
		this.loggedIn = false;
		this.disconnect();
	},
	dateTimeFormatter: function(unixtimestamp) {
		unixtimestamp = parseInt(unixtimestamp);
		return moment.unix(unixtimestamp).tz(timezone).format(datetimeformat);
	},
	timeFormatter: function(unixtimestamp) {
		unixtimestamp = parseInt(unixtimestamp);
		return moment.unix(unixtimestamp).tz(timezone).format(timeformat);
	},
	dateFormatter: function(unixtimestamp) {
		unixtimestamp = parseInt(unixtimestamp);
		return moment.unix(unixtimestamp).tz(timezone).format(dateformat);
	},
	humanDiff: function(unixtimestamp) {
		unixtimestamp = parseInt(unixtimestamp);
		return moment.duration(moment.unix(unixtimestamp).diff(moment(new Date()))).humanize(true);
	}
}), UCP = new UCPC();
$(function() {
	UCP.ready(UCP.loggedIn);
});

String.prototype.modularize = function() {
		return this.toLowerCase().charAt(0).toUpperCase() + this.slice(1);
};

jQuery.fn.highlight = function(str, className) {
	var regex = new RegExp("\\b" + str + "\\b", "gi");

	return this.each(function() {
		this.innerHTML = this.innerHTML.replace(regex, function(matched) {return "<span class=\"" + className + "\">" + matched + "</span>";});
	});
};

function htmlEncode( html ) {
	return document.createElement( "a" ).appendChild(
		document.createTextNode( html ) ).parentNode.innerHTML;
}

function htmlDecode( html ) {
	var a = document.createElement( "a" ); a.innerHTML = html;
	return a.textContent;
}

$('#globalModal').on('hide.bs.modal',function(){
	$('#globalModalLabel').html("");
	$('#globalModalBody').html("");
	$('#globalModalFooter').html("");
});


/** Language, global functions so they act like php procedurals **/
UCP.i18n = new Jed(languages);
