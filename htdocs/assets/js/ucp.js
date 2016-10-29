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

		textdomain("ucp");
	},
	ready: function(loggedIn) {
		$(window).resize(function() {UCP.windowResize();});
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
			$(document).trigger("logIn", [null, null]);
		} else {
			UCP.setupLogin();
		}

		this.binds();

		this.callModulesByMethod("ready",$.url().param());
	},
	windowResize: function() {

	},
	callModuleByMethod: function() {
		var args = [],
				mdata = [];

		Array.prototype.push.apply( args, arguments );
		module = args.shift().modularize();
		method = args.shift();
		if(UCP.validMethod(module, method)) {
			if (typeof window[module] == "object" && typeof window[module][method] == "function") {
				return window[module][method].apply( window[module] , args );
			} else if (UCP.validMethod(module, method)) {
				return UCP.Modules[module][method].apply( UCP.Modules[module] , args );
			}
		} else {
			return null;
		}
	},
	callModulesByMethod: function() {
		var args = [],
				mdata = [];

		Array.prototype.push.apply( args, arguments );
		method = args.shift();
		$.each(modules, function( index, module ) {
			if (typeof window[module] == "object" && typeof window[module][method] == "function") {
				mdata[module] = window[module][method].apply( window[module] , args );
			} else if (UCP.validMethod(module, method)) {
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
		if ($.support.pjax) {
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
						/*
						UCP.token = data.token;
						$.pjax.submit(event, "#content-container");
						$(document).one("pjax:end", function() {
							UCP.setupDashboard();
							$(document).trigger("logIn", [username, password]);
						});
						*/
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
		//Start PJAX Stuff
		if ($.support.pjax) {
			$.post( "index.php", { "quietmode": 1, "command": "staticsettings" }, function( data ) {
				if (data.status) {
					$.each(data.settings, function(i, v) {
						if (typeof window[i] !== "undefined") {
							window[i].staticsettings = v;
						} else if (typeof Ucp.Modules[i] !== "undefined") {
							Ucp.Modules[i].staticsettings = v;
						}
					});
					$(document).trigger("staticSettingsFinished");
				}
			});

			//Navigation Clicks
			$(document).on("click", "[data-pjax] a, a[data-pjax]", function(event) {
				var container = $("#dashboard-content"),
						clicker = $(this).data("mod");
				$.pjax.click(event, { container: container });
			});
		} else {
			alert(_("UCP is not supported in your browser"));
		}
		$("a.logout").click(function(event) {
			event.preventDefault();
			event.stopPropagation();
			$(document).trigger("logOut");
			location.href = "?logout=1";
		});

		$(document).on("pjax:end", function() {UCP.pjaxEnd();});
		$(document).on("pjax:start", function() {UCP.pjaxStart();});
		$(document).on("pjax:timeout", function(event) {UCP.pjaxTimeout(event);});
		$(document).on("pjax:error", function(event) {UCP.pjaxError(event);});

		//This allows browsers to request user notifications from said user.
		$(document).click(function() {
			if (UCP.loggedIn && Notify.needsPermission() && UCP.notify === null) {
				Notify.requestPermission(UCP.notificationsAllowed(), UCP.notificationsDenied() );
			}
		});

		//TODO: Do something with this eventually, hidden/display tabs
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
			state = this[UCP.hidden] ? "hidden" : "visible";
		}
		//TODO: call windowState of modules
		if (typeof window[UCP.activeModule] == "object" &&
			typeof window[UCP.activeModule].windowState == "function") {
			window[UCP.activeModule].windowState(state);
		}
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
						if (typeof window[module] == "object" && typeof window[module].poll == "function") {
							window[module].poll(data, $.url().param());
						} else if (UCP.validMethod(module, "poll")) {
							UCP.Modules[module].poll(data, $.url().param());
						}
					});
				}
				UCP.polling = false;
			}, error: function(jqXHR, textStatus, errorThrown) {
				//We probably should logout on every event here... but
				if (jqXHR.status === 403) {
					$(document).trigger("logOut");
					location.href = "?logout=1";
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
	closeDialog: function(callback) {
		//TODO: Use Carlos-Bootstrap Dialog
		$(".dialog").fadeOut("fast", function(event) {
			$(this).remove();
			if (typeof callback === "function") {
				callback();
			}
		});
	},
	showDialog: function(title, content, height, width, callback) {
		//TODO: Use Carlos-Bootstrap Dialog
		var w = (typeof width !== "undefined") ? width : "250px",
				h = (typeof height !== "undefined") ? height : "250px",
				html = "<div class=\"dialog\" style=\"height:" + h + "px;width:" + w + "px;margin-top:-" + (h / 2) + "px;margin-left:-" + (w / 2) + "px;\"><div class=\"title\">" + title + "<i class=\"fa fa-times\" onclick=\"UCP.closeDialog()\"></i></div><div class=\"content\">" + content + "</div></div>";
		if ($(".dialog").length) {
			$(".dialog").fadeOut("fast", function(event) {
				$(this).remove();
				$(html).appendTo("#dashboard-content").hide().fadeIn("fast", function(event) {
					if (typeof callback === "function") {
						callback();
					}
				});
			});
		} else {
			$(html).appendTo("#dashboard-content").hide().fadeIn("fast", function(event) {
				if (typeof callback === "function") {
					callback();
				}
			});
		}
	},
	addPhone: function(module, id, s, msg, contacts, callback) {
		var message = (typeof msg !== "undefined") ? msg : "",
				state = (typeof s !== "undefined") ? s : "call";
		if ($( ".phone-box[data-id=\"" + id + "\"]" ).length > 0) {
			return;
		}
		$.ajax({ url: "index.php", data: { quietmode: 1, command: "template", type: "phone", template: { id: id, state: state, message: message, module: module, contacts: contacts } }, success: function(data) {
			$( "#messages-container" ).append( data.contents );
			if (typeof callback === "function") {
				callback(id, state, message);
			}
			$( ".phone-box[data-id=\"" + id + "\"]" ).fadeIn("fast", function() {
				$(document).trigger( "phoneWindowAdded" );
				if (!$( "#messages-container .phone-box[data-id=\"" + id + "\"]" ).hasClass("expand")) {
					$( "#messages-container .phone-box[data-id=\"" + id + "\"]" ).one("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend", function() {
						$("#messages-container .phone-box[data-id=\"" + id + "\"] input").focus();
					});
					$( "#messages-container .phone-box[data-id=\"" + id + "\"]" ).addClass("expand");
					$( "#messages-container .phone-box[data-id=\"" + id + "\"]" ).find(".fa-arrow-up").addClass("fa-arrow-down").removeClass("fa-arrow-up");
				}
			});
			$( "#messages-container .phone-box[data-id=\"" + id + "\"] .title-bar" ).on("click", function(event) {
				if (!$(event.target).hasClass("cancelExpand")) {
					var container = $("#messages-container .phone-box");
					if (!container.hasClass("expand")) {
						container.find(".fa-arrow-up").addClass("fa-arrow-down").removeClass("fa-arrow-up");
					} else {
						container.find(".fa-arrow-down").addClass("fa-arrow-up").removeClass("fa-arrow-down");
					}
					container.one("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend", function() {
						if (container.hasClass("expand")) {
							$("#messages-container .phone-box[data-id=\"" + id + "\"] input").focus();
						}
					});
					container.toggleClass("expand");
				} else {
					UCP.removePhone(id);
				}
			});
		}, dataType: "json", type: "POST" });
	},
	removePhone: function(id) {
		$( "#messages-container .phone-box[data-id=\"" + id + "\"]" ).off("click");
		$( "#messages-container .phone-box[data-id=\"" + id + "\"]" ).fadeOut("fast", function() {
			$(this).remove();
			$(document).trigger( "phoneWindowRemoved");
		});
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
	pjaxEnd: function(event) {
		this.binds();
		this.callModulesByMethod("pjaxEnd",event);
		NProgress.done();
	},
	pjaxStart: function(event) {
		NProgress.start();
		this.callModulesByMethod("pjaxStart",event);
	},
	pjaxTimeout: function(event) {
		//query higher up event here
		event.preventDefault();
		return false;
	},
	pjaxError: function(event) {
		//query higher up event here
		console.log("error");
		console.log(event);
		event.preventDefault();
		NProgress.done();
		$("#nav-btn-settings .icon i").removeClass("out");
		return false;
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
	binds: function() {
		$('table[data-toggle="table"]').bootstrapTable();
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

/** Language, global functions so they act like php procedurals **/
UCP.i18n = new Jed(languages);
