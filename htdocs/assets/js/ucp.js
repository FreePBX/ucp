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
		this.domain = "ucp";
		this.i18n = null;
		this.messageBuffer = {};
		this.token = null;
		this.lastIO = null;
		this.Modules = {};
	},
	ready: function() {
		$(window).resize(function() {UCP.windowResize();});
		$(document).bind("logIn", function( event ) {UCP.logIn(event);});
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
		var btn = $("#btn-login");
		if ($.support.pjax) {
			$(document).on("submit", "#frm-login", function(event) {
				var queryString = $(this).formSerialize();

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
							$(document).trigger("logIn");
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
						clicker = $(this).data("mod"),
						breadcrumbs = "<li class=\"home\"><a data-mod=\"home\" data-pjax href=\"?display=dashboard&amp;mod=home\">Home</a></li>",
						mod = "home",
						sub = "",
						display = "";
				$.pjax.click(event, { container: container });

				mod = $.url().param("mod");
				sub = $.url().param("sub");
				display = $.url().param("display");
				if (typeof display === "undefined" || display == "dashboard") {
					if (mod != "home") {
						breadcrumbs = breadcrumbs + "<li class=\"module bc-" + mod + " active\">" + mod + "</li>";
					}
					if (typeof sub !== "undefined") {
						breadcrumbs = breadcrumbs + "<li class=\"subsection bc-" + sub + " active\">" + sub + "</li>";
					}
				} else if (display == "settings") {
					breadcrumbs = breadcrumbs + "<li class=\"module active\">Settings</li>";
				}

				$("#top-dashboard-nav").html(breadcrumbs);

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

		$("#presence-box2").click(function() {
			$(this).toggleClass("active");
			$("#presence-menu2").toggleClass("active");
			$(this).find("i").toggleClass("active");
		});

		$("#presence-menu2 .change-status").click(function() {
			$("#presence-menu2 .options").toggleClass("shrink");
			$("#presence-menu2 .statuses").toggleClass("grow");
			if ($("#presence-menu2 .statuses").hasClass("grow")) {
				$("#presence-menu2 .change-status").text("Select Actions");
			} else {
				$("#presence-menu2 .change-status").text("Change Status");
			}
		});

		//Hide Settings Menu when clicking outside of it
		$("html").click(function(event) {
			if (($(event.target).parents().index($("#presence-menu2")) == -1) &&
				$(event.target).parents().index($("#presence-box2")) == -1) {
				if ($("#presence-menu2").hasClass("active")) {
					$("#presence-menu2").removeClass("active");
					$("#presence-box2").removeClass("active");
					$("#presence-box2 i").removeClass("active");
				}
			}
		});

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
		}

		//Menu adjustments
		//$("#presence-box2").css("right", $(".nav-btns").width() + "px");
		//$("#presence-menu2").css("right", $(".nav-btns").width() + "px");
		totalNavs = $(".module-container").length;
		navWidth = $(".module-container").last().outerWidth();

		count = totalNavs;
		$(".module-container").each(function() {
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
				$("#nav-btn-" + module).click(function() {
					menuObj.toggleClass("active");
					$("#nav-btn-" + module).toggleClass("active");
					if (menuObj.css("top") == "36px") {
						menuObj.css("top", "-" + menuObj.data("hidden") + "px");
					} else {
						menuObj.css("top", "36px");
					}
				});

				//hide menu when clicked outside
				$("html").click(function(event) {
					if ($(event.target).parents().index($("#nav-btn-" + module)) == -1) {
						if (menuObj.hasClass("active")) {
							menuObj.removeClass("active");
							$("#nav-btn-" + module).removeClass("active");
							menuObj.css("top", "-" + menuObj.data("hidden") + "px");
						}
					}
				});
			}
		});
		$("#loading-container").fadeOut("fast");
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
		if (!this.loggedIn) {
			return false;
		}

		//If we don't have a valid token then try to get one
		if (UCP.token === null) {
			$.post( "index.php", { "quietmode": 1, "command": "token", "module": "User" }, function( data ) {
				if (data.status && data.token !== null) {
					UCP.token = data.token;
					UCP.wsconnect(namespace, callback);
				} else {
					callback(false);
				}
			});
		} else {
			var host = $.url().attr("host"),
					port = 8001,
					socket = null;
			try {
				socket = io("ws://" + host + ":" + port + "/" + namespace, {
					reconnection: true,
					query: "token=" + UCP.token
				});
			}catch (err) {
				callback(false);
			}
			socket.on("connect", function() {
				UCP.lastIO = socket.io;
			});
			socket.on("connect_error", function(reason) {
				//console.error('Unable to connect Socket.IO', reason);
			});
			callback(socket);
		}
	},
	connect: function() {
		//Interval is in a callback to shortpoll to make sure we are "online"
		UCP.displayGlobalMessage(_("Connecting...."), "rgba(128, 128, 128, 0.5)", true);
		UCP.shortpoll(function() {
			UCP.pollID = setInterval(function() {
				UCP.shortpoll();
			}, 5000);
			$.each(modules, function( index, module ) {
				if (typeof window[module] == "object" && typeof window[module].connect == "function") {
					window[module].connect();
				} else if (UCP.validMethod(module, "connect")) {
					UCP.Modules[module].connect();
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
				if (typeof Ucp.Modules[module] === "undefined" && typeof window[className]) {
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
	closeDialog: function() {
		$(".dialog").fadeOut("fast", function(event) {
			$(this).remove();
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
	addPhone: function(module, id, s, msg, callback) {
		var message = (typeof msg !== "undefined") ? msg : "",
				state = (typeof s !== "undefined") ? s : "call";
		if ($( ".phone-box[data-id=\"" + id + "\"]" ).length > 0) {
			return;
		}
		$.ajax({ url: "index.php", data: { quietmode: 1, command: "template", type: "phone", template: { id: id, state: state, message: message, module: module } }, success: function(data) {
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
	addChat: function(module, id, icon, from, to, sender, msgid, message, callback) {
		if (!$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).length && (typeof this.messageBuffer[id] === "undefined")) {
			//add placeholder
			if (typeof msgid !== "undefined") {
				this.messageBuffer[id] = [];
				this.messageBuffer[id].push({
					sender: sender,
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
								UCP.addChatMessage(id, v.sender, v.msgid, v.message);
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
				$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").animate({ scrollTop: $("#messages-container .message-box[data-id=\"" + id + "\"] .chat")[0].scrollHeight }, "slow");
			}, dataType: "json", type: "POST" });
		} else {
			if (typeof msgid !== "undefined") {
				UCP.addChatMessage(id, sender, msgid, message);
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
	addChatMessage: function(id, sender, msgid, message, colorNew) {
		message = emojione.toImage(htmlEncode(message));

		if ($( "#messages-container .message-box[data-id=\"" + id + "\"]" ).length) {
			if (!$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).hasClass("expand")) {
				$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).addClass("expand");
				$( "#messages-container .message-box[data-id=\"" + id + "\"] .fa-arrow-up" ).addClass("fa-arrow-down").removeClass("fa-arrow-up");
			}
			$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).data("last-msg-id", msgid);

			if (typeof colorNew === "undefined" || colorNew) {
				$( "#messages-container .title-bar[data-id=\"" + id + "\"]" ).css("background-color", "#428bca");
			} else {
				sender = "Me";
			}
			$( "#messages-container .message-box[data-id=\"" + id + "\"] .chat" ).append("<div class='message' data-id='" + msgid + "'><strong>" + sender + ":</strong> " + message + "</div>");
			if (UCP.chatTimeout[id] !== undefined && UCP.chatTimeout[id] !== null) {
				clearTimeout(UCP.chatTimeout[id]);
			}

			var d = new Date();
			UCP.chatTimeout[id] = setTimeout(function() {
				$( "#messages-container .message-box[data-id=\"" + id + "\"] .chat" ).append("<div class=\"status\" data-type=\"date\">Sent at " + d.format("g:i A \\o\\n l") + "</div>");
				$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").animate({ scrollTop: $("#messages-container .message-box[data-id=\"" + id + "\"] .chat")[0].scrollHeight }, "fast");
			}, 60000);

			$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").animate({ scrollTop: $("#messages-container .message-box[data-id=\"" + id + "\"] .chat")[0].scrollHeight }, "slow");
		} else if (typeof this.messageBuffer[id] !== "undefined") {
			this.messageBuffer[id].push({
				sender: sender,
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
		this.windowResize();
		$("#loader-screen").fadeOut("fast");
		if (typeof window[this.activeModule] == "object" &&
			typeof window[this.activeModule].hide == "function") {
			window[this.activeModule].hide(event);
		} else if (this.validMethod(this.activeModule, "hide")) {
			this.Modules[this.activeModule].hide(event);
		}
		var display = $.url().param("display");
		if (typeof display === "undefined" || display == "dashboard") {
			this.activeModule = $.url().param("mod");
			this.activeModule = (this.activeModule !== undefined) ? UCP.toTitleCase(this.activeModule) : "Home";
			this.domain = this.activeModule.toLowerCase();
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
	pjaxStart: function(event) {

	},
	pjaxTimeout: function(event) {
		//query higher up event here
		$("#loader-screen").fadeIn("fast");
		event.preventDefault();
		return false;
	},
	pjaxError: function(event) {
		//query higher up event here
		console.log("error");
		console.log(event);
		event.preventDefault();
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
	logIn: function(event) {
		this.activeModule = $.url().param("mod");
		this.activeModule = (this.activeModule !== undefined) ? UCP.toTitleCase(this.activeModule) : "Home";
		this.domain = this.activeModule.toLowerCase();
		this.loggedIn = true;
		this.connect();
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
		this.loggedIn = false;
		this.disconnect();
	},
	settingsBinds: function() {
		if (Notify.isSupported()) {
			$("#ucp-settings input[name=\"desktopnotifications\"]").prop("checked", UCP.notify);
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

		$("#ucp-settings input[type!=\"checkbox\"]").change(function() {
			var password = $(this).val();
			$(this).blur(function() {
				if ($(this).prop("type") == "password") {
					UCP.showDialog("Confirm Password", "Please Reconfirm Your Password<input type='password' id='ucppass'></input><button id='passsub'>Submit</button>");
					$("#passsub").click(function() {
						if ($("#ucppass").val() !== "") {
							var np = $("#ucppass").val();
							if (np != password) {
								$("#message").addClass("alert-danger");
								$("#message").text(_("Password Confirmation Didn't Match!"));
								$("#message").fadeIn( "fast" );
							} else {
								UCP.closeDialog();
								$.post( "?quietmode=1&command=ucpsettings", { key: "password", value: $("#ucppass").val() }, function( data ) {
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
						}
					});
					return 0;
				}
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

/** Language, global functions so they act like php **/
UCP.i18n = new Jed(languages);
function _(string) {
	try {
		return UCP.i18n.dgettext( UCP.domain, string );
	} catch (err) {
		return string;
	}
}

function sprintf() {
	try {
		return UCP.i18n.sprintf.apply(this, arguments);
	} catch (err) {
		return string;
	}
}
