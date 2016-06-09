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

		textdomain("ucp");
	},
	ready: function() {
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
	},
	ajaxStart: function() {
		$("#nav-btn-settings i").addClass("fa-spin");
	},
	ajaxStop: function() {
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
						UCP.token = data.token;
						$.pjax.submit(event, "#content-container");
						$(document).one("pjax:end", function() {
							UCP.setupDashboard();
							$(document).trigger("logIn", [username, password]);
						});
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
		$("#loading-container").fadeOut("fast");
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

				$( ".pushmenu li").each(function( index ) {
					if ($(this).data("mod") == clicker) {
						$(this).addClass("active");
					} else {
						$(this).removeClass("active");
					}
				});
				$( ".nav li" ).each(function( index ) {
					if ($(this).data("mod") == clicker) {
						$(this).addClass("active");
					} else {
						$(this).removeClass("active");
					}
				});
				if ($(".pushmenu-left").hasClass("pushmenu-open")) {
					$(".pushmenu-push").removeClass("pushmenu-push-toright");
					$(".pushmenu-left").removeClass("pushmenu-open");
				}
			});
		} else {
			//no pjax support
			//TODO: Im not sure what happens if we hit this?
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

		//Show/Hide Side Bar
		$("#bc-mobile-icon").click(function() {
			UCP.toggleMenu();
		});

		//mobile submenu display
		$(".mobileSubMenu").click(function() {
			var menu = $(this).data("mod");
			$("#submenu-" + menu).slideToggle();
		});

		$("#footer").bind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function() { UCP.transitioning = false; });
		$("#footer").bind("transitionstart webkitTransitionStart oTransitionStart MSTransitionStart", function() { UCP.transitioning = true; });

		$("#dashboard-content").bind("scroll", function() {
			if (UCP.transitioning || $( window ).width() > 767) {
				return true;
			}
			st = $("#dashboard-content").scrollTop();
			if (st > 40) {
				UCP.footerHidden = true;
				$("#footer").addClass("shrink");
				$(document).trigger("hideFooter");
				UCP.windowResize();
			} else {
				UCP.footerHidden = false;
				$("#footer").removeClass("shrink");
				$(document).trigger("mobileScrollUp");
				$(document).trigger("showFooter");
				UCP.windowResize();
			}

			UCP.lastScrollTop = st;
		});

		UCP.windowResize();

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

		//Detect if the client allows touch access.
		if (Modernizr.touch) {
			/*
			$$("#dashboard").swipeLeft(function() {
				if ($(".pushmenu-left").hasClass("pushmenu-open")) {
					toggleMenu();
				}
			});
			$$("#dashboard").swipeRight(function() {
				if (!$(".pushmenu-left").hasClass("pushmenu-open")) {
					toggleMenu();
				}
			});
			*/
		}
		this.calibrateMenus();
		$("#loading-container").fadeOut("fast");
	},
	calibrateMenus: function() {
		//If we are currently calibrating or a menu is being displayed
		//then dont calibrate
		if(this.calibrating || $(".nav-btn-menu.active").length > 0) {
			return;
		}
		this.calibrating = true;
		//Menu adjustments
		//$("#presence-box2").css("right", $(".nav-btns").width() + "px");
		//$("#presence-menu2").css("right", $(".nav-btns").width() + "px");

		totalNavs = $(".module-container").filter(":visible").length;
		navWidth = $(".module-container").filter(":visible").last().outerWidth();

		count = totalNavs;
		$(".module-container").filter(":visible").each(function() {
			var module = $(this).data("module"),
			menuObj = $("#" + module + "-menu"),
			btnObj = $("#nav-btn-" + module),
			hidden = menuObj.outerHeight() + 30;
			count--;
			if (menuObj.length > 0) {
				menuObj.css("right", (navWidth * count) + "px");
				//reposition placement of menu
				//hidding the full length of it
				menuObj.data("hidden", hidden);
				menuObj.css("top", "-" + hidden + "px");
				//now "show" it (really it's hidden so show it to the dom)
				menuObj.show();

				//Show/Hide Settings Drop Down
				$("#nav-btn-" + module).off("click");
				$("#nav-btn-" + module).click(function() {
					menuObj.toggleClass("active");
					$("#nav-btn-" + module).toggleClass("active");
					if (menuObj.css("top") == "36px") {
						menuObj.css("top", "-" + menuObj.data("hidden") + "px");
					} else {
						menuObj.css("top", "36px");
					}
					//hide menu when clicked outside
					$("html").on("click." + module, function(event) {
						if ($(event.target).parents().index($("#nav-btn-" + module)) == -1) {
							if ((menuObj.hasClass("active") &&
									(menuObj.data("keep-on-click") != "false")) ||
									(menuObj.hasClass("active") &&
									(menuObj.data("keep-on-click") == "false") &&
									($(event.target).parents().index($("#" + module + "-menu")) == -1))) {
								menuObj.removeClass("active");
								$("#nav-btn-" + module).removeClass("active");
								menuObj.css("top", "-" + menuObj.data("hidden") + "px");
								$("html").off("click." + module);
							}
						}
					});
				});
			}
		});
		this.calibrating = false;
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
		//Interval is in a callback to shortpoll to make sure we are "online"
		UCP.displayGlobalMessage(_("Connecting...."), "rgba(128, 128, 128, 0.5)", true);
		UCP.shortpoll(function() {
			UCP.pollID = setInterval(function() {
				UCP.shortpoll();
			},5000);
			$.each(modules, function( index, module ) {
				if (typeof window[module] == "object" && typeof window[module].connect == "function") {
					window[module].connect(username, password);
				} else if (UCP.validMethod(module, "connect")) {
					UCP.Modules[module].connect(username, password);
				}
			});
			UCP.removeGlobalMessage();
			UCP.websocketConnect();
		});
	},
	disconnect: function() {
		clearInterval(this.pollID);
		this.pollID = null;
		this.polling = false;
		$("#nav-btn-settings i").removeClass("fa-spin");
		$.each(modules, function( index, module ) {
			if (typeof window[module] == "object" && typeof window[module].disconnect == "function") {
				window[module].disconnect();
			} else if (UCP.validMethod(module, "disconnect")) {
				UCP.Modules[module].disconnect();
			}
		});
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
			$.each(modules, function( index, module ) {
				if (typeof window[module] == "object" && typeof window[module].prepoll == "function") {
					mdata[module] = window[module].prepoll($.url().param());
				} else if (UCP.validMethod(module, "prepoll")) {
					mdata[module] = UCP.Modules[module].prepoll($.url().param());
				}
			});
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
	windowResize: function() {
		if ($( window ).width() > 767 && $(".pushmenu-left").hasClass("pushmenu-open")) {
			UCP.toggleMenu();
		}
		if($(window).width() < 992) {
			$('table[data-toggle=table]').each(function() {
				if(!$(this).bootstrapTable('getOptions').cardView) {
					//$(this).bootstrapTable('toggleView');
				}
			});
			resizeMode = 'mobile';
		} else {
			$('table[data-toggle=table]').each(function() {
				if($(this).bootstrapTable('getOptions').cardView) {
					//$(this).bootstrapTable('toggleView');
				}
			});
			resizeMode = 'desktop';
		}

		//run the resize hack against dashboard content
		if ($("#dashboard-content").length) {
			if (!UCP.footerHidden) {
				$("#dashboard-content").height($("#dashboard").height() - 135);
				$("#fs-navside").height($("#dashboard").height() - 135);
				//presence-box2
			} else {
				$("#dashboard-content").height($("#dashboard").height() - 59);
				$("#fs-navside").height($("#dashboard").height() - 59);
			}
		}
	},
	notificationsAllowed: function() {
		this.notify = true;
	},
	notificationsDenied: function() {
		this.notify = false;
	},
	closeDialog: function(callback) {
		$(".dialog").fadeOut("fast", function(event) {
			$(this).remove();
			if (typeof callback === "function") {
				callback();
			}
		});
	},
	showDialog: function(title, content, height, width, callback) {
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
	toggleMenu: function() {
		$(".pushmenu-push").toggleClass("pushmenu-push-toright");
		$(".pushmenu-left").toggleClass("pushmenu-open");
		//dropdown-pushmenu
		$( ".pushmenu .dropdown-pushmenu" ).each(function( index ) {
			if ($(this).is(":visible")) {
				$(this).slideToggle();
			}
		});
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
		var display = $.url().param("display"),
				breadcrumbs = "<li class=\"home\"><a data-mod=\"home\" data-pjax href=\"?display=dashboard&amp;mod=home\">" + _("Home") + "</a></li>",
				sub = $.url().param("sub");

		this.windowResize();
		NProgress.done();
		$("#nav-btn-settings .icon i").removeClass("out");
		if (typeof window[this.activeModule] == "object" &&
			typeof window[this.activeModule].hide == "function") {
			window[this.activeModule].hide(event);
		} else if (this.validMethod(this.activeModule, "hide")) {
			this.Modules[this.activeModule].hide(event);
		}

		if (typeof display === "undefined" || display == "dashboard") {
			this.activeModule = $.url().param("mod");
			this.activeModule = (this.activeModule !== undefined) ? UCP.toTitleCase(this.activeModule) : "Home";
			textdomain(this.activeModule.toLowerCase());
			if (typeof window[this.activeModule] == "object" &&
				typeof window[this.activeModule].display == "function") {
				window[this.activeModule].display(event);
			} else if (this.validMethod(this.activeModule, "display")) {
				this.Modules[this.activeModule].display(event);
			}
		} else if (display == "settings") {
			this.settingsBinds();
		}

		if (typeof UCP.Modules[this.activeModule] !== "undefined" && typeof UCP.Modules[this.activeModule].getInfo !== "undefined") {
			var tmp = UCP.Modules[this.activeModule].getInfo();
			name = tmp.name;
		} else {
			name = this.activeModule;
		}

		if (typeof display === "undefined" || display == "dashboard") {
			if (this.activeModule != "Home") {
				breadcrumbs = breadcrumbs + "<li class=\"module bc-" + this.activeModule.toLowerCase() + " active\">" + name + "</li>";
			}
			if (typeof sub !== "undefined") {
				breadcrumbs = breadcrumbs + "<li class=\"subsection bc-" + sub + " active\">" + sub + "</li>";
			}
		} else if (display == "settings") {
			breadcrumbs = breadcrumbs + "<li class=\"module active\">" + _("Settings") + "</li>";
		}

		$("#top-dashboard-nav").html(breadcrumbs);

		this.binds();
	},
	pjaxStart: function(event) {
		NProgress.start();
		$("#nav-btn-settings .icon i").addClass("out");
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
		this.activeModule = $.url().param("mod");
		this.activeModule = (this.activeModule !== undefined) ? UCP.toTitleCase(this.activeModule) : "Home";
		textdomain(this.activeModule.toLowerCase());
		this.loggedIn = true;
		this.connect(username, password);
		if (!Notify.needsPermission() && this.notify === null) {
			this.notify = true;
		}
		var display = $.url().param("display");
		if (typeof display === "undefined" || display == "dashboard") {
			if (typeof window[this.activeModule] == "object" &&
				typeof window[this.activeModule].display == "function") {
				window[this.activeModule].display(event);
			} else if (this.validMethod(this.activeModule, "display")) {
				this.Modules[this.activeModule].display(event);
			}
		} else if (display == "settings") {
			this.settingsBinds();
		}
		this.binds();
	},
	logOut: function(event) {
		if (typeof window[this.activeModule] == "object" &&
			typeof window[this.activeModule].hide == "function") {
			window[this.activeModule].hide(event);
		} else if (this.validMethod(this.activeModule, "hide")) {
			this.Modules[this.activeModule].hide(event);
		}
		localforage.clear();
		this.loggedIn = false;
		this.disconnect();
	},
	settingsBinds: function() {
		if (Notify.isSupported()) {
			$("#ucp-settings input[name=\"desktopnotifications\"]").prop("checked", UCP.notify);
			$("#ucp-settings input[name=\"desktopnotifications\"]").off();
			$("#ucp-settings input[name=\"desktopnotifications\"]").change(function() {
				if (!UCP.notify && $(this).is(":checked")) {
					Notify.requestPermission(function() {
						UCP.notificationsAllowed();
						$("#ucp-settings input[name=\"desktopnotifications\"]").prop("checked", true);
						$("#message").addClass("alert-success");
						$("#message").text("Saved!");
						$("#message").fadeIn( "slow", function() {
							setTimeout(function() { $("#message").fadeOut("slow"); }, 2000);
						});
					}, function() {
						UCP.notificationsDenied();
						$("#ucp-settings input[name=\"desktopnotifications\"]").prop("checked", false);
					});
				} else {
					UCP.notify = false;
					$("#message").addClass("alert-success");
					$("#message").text("Saved!");
					$("#message").fadeIn( "slow", function() {
						setTimeout(function() { $("#message").fadeOut("slow"); }, 2000);
					});
				}
			});
			$("#ucp-settings .desktopnotifications-group").show();
		}

		if (typeof $.cookie("lang") !== "undefined") {
			$("#ucp-settings select[name=\"lang\"]").val($.cookie("lang"));
		}
		$("#ucp-settings select[name=\"lang\"]").change(function() {
			$.cookie("lang", $(this).val());
			if (confirm(_("UCP needs to reload, ok?"))) {
				window.location.reload();
			}
		});

		$("#ucp-settings input[type!=\"checkbox\"]").off();
		$("#ucp-settings input[name=username]").keyup(function() {
			var parent = $(this).parents(".form-group"), green = "rgba(60, 118, 61, 0.11)", red = 'rgba(169, 68, 66, 0.11)', $this = this;
			parent.removeClass("has-success has-error");
			$(this).css("background-color","");
			//check username input
			if($(this).val() != $(this).data("prevusername")) {
				$.post( "?quietmode=1&command=ucpsettings", { key: "usernamecheck", value: $(this).val() }, function( data ) {
					if(data.status) {
						parent.addClass("has-success");
						$($this).css("background-color",green);
					} else {
						parent.addClass("has-error");
						$($this).css("background-color",red);
					}
				});
			}
		});
		$("#update-pwd").click(function(e) {
			e.preventDefault();
			e.stopPropagation();
			var password = $("#pwd").val(), confirm = $("#pwd-confirm").val();
			if (password !== "" && password != "******" && confirm !== "") {
				if (confirm != password) {
					$("#message").addClass("alert-danger");
					$("#message").text(_("Password Confirmation Didn't Match!"));
					$("#message").fadeIn( "fast" );
				} else {
					$.post( "?quietmode=1&command=ucpsettings", { key: "password", value: confirm }, function( data ) {
						if (data.status) {
							$("#message").addClass("alert-success");
							$("#message").text(_("Saved!"));
							$("#message").fadeIn( "slow", function() {
								setTimeout(function() { $("#message").fadeOut("slow"); }, 2000);
							});
						} else {
							$("#message").addClass("alert-danger");
							$("#message").text(data.message);
						}
					});
				}
			} else {
				$("#message").addClass("alert-danger");
				$("#message").text(_("Password has not changed!"));
				$("#message").fadeIn( "fast" );
			}
		});
		$("#ucp-settings input[type!=\"checkbox\"]").change(function() {
			var password = $(this).val();
			if ($(this).prop("type") == "password") {
				return;
			}
			$(this).blur(function() {
				$(this).off("blur");
				if($(this).prop("name") == "username") {
					if($(this).val() != $(this).data("prevusername")) {
						//do ajax
						if($(this).parents(".form-group").hasClass("has-success")) {
							if(confirm(_("Are you sure you wish to change your username?"))) {
								$.post( "?quietmode=1&command=ucpsettings", { key: "username", value: $(this).val() }, function( data ) {
									if(data.status) {
										alert(_("Username has been changed, reloading"));
										location.reload();
									} else {
										alert(data.message);
									}
								});
							}
						} else {

						}
					}
				} else {
					$.post( "?quietmode=1&command=ucpsettings", { key: $(this).prop("name"), value: $(this).val() }, function( data ) {
						if (data.status) {
							$("#message").addClass("alert-success");
							$("#message").text(_("Saved!"));
							$("#message").fadeIn( "slow", function() {
								setTimeout(function() { $("#message").fadeOut("slow"); }, 2000);
							});
						} else {
							$("#message").addClass("alert-danger");
							$("#message").text(data.message);
						}
						$(this).off("blur");
					});
				}
			});
		});
	},
	binds: function() {
		$(".form-group label.help").click(function() {
			var f = $(this).prop("for");
			if (!$(".help-hidden[data-for=\"" + f + "\"]").is(":visible")) {
				//hide all others
				$(".help-hidden").fadeOut("slow", function() {
					if (("#module-page-settings .masonry-container").length && Settings.packery) {
						$("#module-page-settings .masonry-container").packery();
					}
				});
				//display our reference
				$(".help-hidden[data-for=\"" + f + "\"]").fadeIn("slow");
				if (("#module-page-settings .masonry-container").length && Settings.packery) {
					$("#module-page-settings .masonry-container").packery();
				}
			} else {
				$(".help-hidden[data-for=\"" + f + "\"]").fadeOut("slow", function() {
					if (("#module-page-settings .masonry-container").length && Settings.packery) {
						$("#module-page-settings .masonry-container").packery();
					}
				});
			}
		});
		$('table[data-toggle="table"]').bootstrapTable();
	},
	dateFormatter: function(unixtimestamp) {
		unixtimestamp = parseInt(unixtimestamp);
		return moment.unix(unixtimestamp).tz(timezone).format('MM/DD/YYYY h:mm:ssa');
	},
	updateNavBadge: function(button, num) {
		var badge = $("#nav-btn-" + button + " .badge");
		if (num > 0) {
			badge.text(num);
			badge.fadeIn("fast");
		} else {
			badge.fadeOut("fast", function() {
				badge.text(0);
			});
		}
	}
}), UCP = new UCPC();
$(function() {
	UCP.ready();
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
