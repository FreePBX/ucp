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
			$(document).trigger("logIn");
			UCP.setupDashboard();
		} else {
			UCP.setupLogin();
		}
	},
	ajaxStart: function() {
		$("#settings-btn i").addClass("fa-spin");
	},
	ajaxStop: function() {
		$("#settings-btn i").removeClass("fa-spin");
	},
	ajaxError: function(event, jqxhr, settings, exception) {
		UCP.displayGlobalMessage("Opps something went wrong. Try again a little later");
	},
	setupLogin: function() {
		if ($.support.pjax) {
			$(document).on("submit", "#frm-login", function(event) {
				var queryString = $(this).formSerialize();
				queryString = queryString + "&quietmode=1&module=User&command=login";
				$.post( "index.php", queryString, function( data ) {
					if (!data.status) {
						$("#error-msg").html(data.message).fadeIn("fast");
						$("#login-window").height("300");
					} else {
						$.pjax.submit(event, "#content-container");
						$(document).one("pjax:end", function() {
							$(document).trigger("logIn");
							UCP.setupDashboard();
						});
					}
				}, "json");
				return false;
			});
		} else {
			//no pjax support...
		}
	},
	setupDashboard: function() {
		//Start PJAX Stuff
		if ($.support.pjax) {
			$.post( "index.php", { "quietmode": 1, "command": "staticsettings" }, function( data ) {
				if (data.status) {
					$.each(data.settings, function(i, v) {
						window[i].staticsettings = v;
					});
					$(document).trigger("staticSettingsFinished");
				}
			});
			//logout bind
			$(document).pjax("a[data-pjax-logout]", "#content-container");

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
		}
		$("a[data-pjax-logout]").click(function(event) {
			$(document).trigger("logOut");
		});

		$(document).on("pjax:end", function() {UCP.pjaxEnd();});
		$(document).on("pjax:start", function() {UCP.pjaxStart();});
		$(document).on("pjax:timeout", function(event) {UCP.pjaxTimeout(event);});
		$(document).on("pjax:error", function(event) {UCP.pjaxError(event);});

		//Show/Hide Settings Drop Down
		$("#settings-btn").click(function() {
			$("#settings-menu").toggleClass("active");
			$("#settings-btn i").toggleClass("active");
		});

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
			if (($(event.target).parents().index($("#settings-btn")) == -1) &&
				$(event.target).parents().index($("#settings-menu")) == -1) {
				if ($("#settings-menu").hasClass("active")) {
					$("#settings-menu").removeClass("active");
					$("#settings-btn i").removeClass("active");
				}
			}
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
	startComm: function() {
		this.ws = new WebSocket("ws://" + $.url().attr("host") + ":8081");
		this.ws.onerror = function(event) {
			//
		};
		this.ws.onmessage = function(event) {
			var data = JSON.parse(event.data);
			//get messages here
		};
		this.ws.onopen = function(event) {
			//attemp to connect here
		};
		this.ws.onclose = function(event) {
			//terminate the connection do stuff after here
			console.warn("Unable to make websockets connection, falling back to polling");
			UCP.shortpoll();
			UCP.pollID = setInterval(function() {
				UCP.shortpoll();
			}, 5000);
		};
	},
	longpoll: function() {
		//not used because longpoll is irritating with apache and php
		$.ajax( { url: "index.php?quietmode=1&command=poll", data: { data: $.url().param() }, success: function(data) {
			if (data.status) {
				$.each(data.modData, function( module, data ) {
					if (typeof window[module] == "object" && typeof window[module].poll == "function") {
						window[module].poll(data, $.url().param());
					}
				});
			}
			UCP.longpoll();
		}, dataType: "json", type: "POST" });
	},
	shortpoll: function() {
		if (!UCP.polling) {
			UCP.polling = true;
			var mdata = {};
			$.each(modules, function( index, module ) {
				if (typeof window[module] == "object" && typeof window[module].prepoll == "function") {
					mdata[module] = window[module].prepoll($.url().param());
				}
			});
			$.ajax({ url: "index.php?quietmode=1&command=poll", data: { data: $.url().param(), mdata: mdata }, success: function(data) {
				if (data.status) {
					$.each(data.modData, function( module, data ) {
						if (typeof window[module] == "object" && typeof window[module].poll == "function") {
							window[module].poll(data, $.url().param());
						}
					});
				}
				UCP.polling = false;
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
			} else {
				$("#dashboard-content").height($("#dashboard").height() - 59);
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
	showDialog: function(title, content, height, width) {
		var w = (typeof width !== "undefined") ? width : "250px",
				h = (typeof height !== "undefined") ? height : "250px",
				html = "<div class=\"dialog\" style=\"height:" + h + "px;width:" + w + "px;margin-top:-" + (h / 2) + "px;margin-left:-" + (w / 2) + "px;\"><div class=\"title\">" + title + "<i class=\"fa fa-times\" onclick=\"UCP.closeDialog()\"></i></div><div class=\"content\">" + content + "</div></div>";
		if ($(".dialog").length) {
			$(".dialog").fadeOut("fast", function(event) {
				$(this).remove();
				$(html).appendTo("#dashboard-content").hide().fadeIn("fast");
			});
		} else {
			$(html).appendTo("#dashboard-content").hide().fadeIn("fast");
		}
	},
	addPhone: function(module, id, s, msg, callback) {
		var message = (typeof msg !== "undefined") ? msg : "",
				state = (typeof s !== "undefined") ? s : "call";
		if ($( ".phone-box[data-id=\"" + id + "\"]" ).length > 0) {
			return;
		}
		$.ajax({ url: "index.php?quietmode=1&command=template&type=phone", data: { template: { id: id, state: state, message: message, module: module } }, success: function(data) {
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
	addChat: function(module, id, title, from, to, sender, msgid, message) {
		if (!$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).length) {
			var newWindow = (typeof msgid === "undefined");
			$.ajax({ url: "index.php?quietmode=1&command=template&type=chat", data: { newWindow: newWindow, template: { module: module, id: id, title: title, to: to, from: from } }, success: function(data) {
				$( "#messages-container" ).append( data.contents );
				$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).fadeIn("fast", function() {
					if (typeof msgid !== "undefined") {
						UCP.addChatMessage(id, sender, msgid, message);
					} else {
						if (!$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).hasClass("expand")) {
							$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).one("webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend", function() {
								$(this).find("textarea").focus();
							});
							$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).addClass("expand");
							$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).find(".fa-arrow-up").addClass("fa-arrow-down").removeClass("fa-arrow-up");
						}
					}
				});
				$(document).trigger( "chatWindowAdded", [ id, module,  $( "#messages-container .message-box[data-id=\"" + id + "\"]" ) ] );
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
		$( "#messages-container .title-bar[data-id=\"" + id + "\"]" ).off("click");
		$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).fadeOut("fast", function() {
			$(this).remove();
			$(document).trigger( "chatWindowRemoved", [ id ] );
		});
	},
	addChatMessage: function(id, sender, msgid, message, colorNew) {
		if (!$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).hasClass("expand")) {
			$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).addClass("expand");
			$( "#messages-container .message-box[data-id=\"" + id + "\"] .fa-arrow-up" ).addClass("fa-arrow-down").removeClass("fa-arrow-up");
		}
		$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).data("last-msg-id",msgid);

		if(typeof colorNew === "undefined" || colorNew) {
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
			$( "#messages-container .message-box[data-id=\"" + id + "\"] .chat" ).append("<span class=\"date\">Sent at " + d.format("g:i A \\o\\n l") + "</span><br/>");
		}, 60000);

		$("#messages-container .message-box[data-id=\"" + id + "\"] .chat").animate({ scrollTop: $("#messages-container .message-box[data-id=\"" + id + "\"] .chat")[0].scrollHeight }, "slow");
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
		}
		var display = $.url().param("display");
		if (typeof display === "undefined" || display == "dashboard") {
			this.activeModule = $.url().param("mod");
			this.activeModule = (this.activeModule !== undefined) ? UCP.toTitleCase(this.activeModule) : "Home";
			if (typeof window[this.activeModule] == "object" &&
				typeof window[this.activeModule].display == "function") {
				window[this.activeModule].display(event);
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
	online: function(event) {
		if (this.loggedIn && this.pollID === null) {
			this.startComm();
		}
	},
	offline: function(event) {

	},
	logIn: function(event) {
		this.activeModule = $.url().param("mod");
		this.activeModule = (this.activeModule !== undefined) ? UCP.toTitleCase(this.activeModule) : "Home";
		this.loggedIn = true;
		this.startComm();
		if (!Notify.needsPermission() && this.notify === null) {
			this.notify = true;
		}
		var display = $.url().param("display");
		if (typeof display === "undefined" || display == "dashboard") {
			if (typeof window[this.activeModule] == "object" &&
				typeof window[this.activeModule].display == "function") {
				window[this.activeModule].display(event);
			}
		} else if (display == "settings") {
			this.settingsBinds();
		}
		this.binds();
	},
	logOut: function(event) {
		this.loggedIn = false;
		clearInterval(this.pollID);
		this.pollID = null;
		if (typeof window[this.activeModule] == "object" &&
			typeof window[this.activeModule].hide == "function") {
			window[this.activeModule].hide(event);
		}
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
						if($("#ucppass").val() !== "") {
							var np = $("#ucppass").val();
							if (np != password) {
								$("#message").addClass("alert-danger");
								$("#message").text("Password Confirmation Didn't Match!");
								$("#message").fadeIn( "fast" );
							} else {
								UCP.closeDialog();
								$.post( "?quietmode=1&command=ucpsettings", { key: "password", value: $("#ucppass").val() }, function( data ) {
									if (data.status) {
										$("#message").addClass("alert-success");
										$("#message").text("Saved!");
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
						$("#message").text("Saved!");
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
