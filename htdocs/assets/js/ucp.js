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
		window.onerror = function (message, url, line) {
			if(!$("#alert_modal").is(":visible")) {
				UCP.showAlert(_("There was an error. See the console log for more details"),'danger');
			}
		};
	},
	ready: function(loggedIn) {
		this.parseUrl();
		$(document).bind("logIn", function( event) {UCP.logIn(event);});
		$(document).bind("logOut", function( event ) {UCP.logOut(event);});
		$(window).bind("online", function( event ) {UCP.online(event);});
		$(window).bind("offline", function( event ) {UCP.offline(event);});
		$(document).ajaxError(function( event, jqxhr, settings, thrownError ) {
			//you can set jqxhr.hideGlobal = true in your .fail(jqXHR, textStatus, errorThrown) { jqxhr.hideGlobal = true } function to not show this message
			if ((jqxhr.status === 500 || jqxhr.status === 403) && (typeof jqxhr.hideGlobal === "undefined" || jqxhr.hideGlobal === false)) {
				setTimeout(function() {
					if(!$("#alert_modal").is(":visible")) {
						UCP.showAlert(_("There was an error. See the console log for more details"),'danger');
					}
					try {
						var obj = JSON.parse(jqxhr.responseText);
						if(typeof obj.error.file !== "undefined") {
							console.error(thrownError + ": " + obj.error.message);
							console.error(obj.error.file + ": " + obj.error.line);
						} else if(typeof obj.error !== "undefined") {
							console.error(thrownError + ": " + obj.error);
						} else if(typeof obj.message !== "undefined") {
							console.error(thrownError + ": " + obj.message);
						}
					} catch(e) {
						console.error(thrownError + ": " + e);
					}
				},200);
			}
		});
		//if we are already logged in (the login window is missing)
		//in then throw the loggedIn trigger
		if (!$("#login-window").length) {
			UCP.setupDashboard();
			$(document).trigger("logIn");
		} else {
			UCP.setupLogin();
		}

		var setupBootstrapToggle = function(el) {
			if(typeof $(el).data("on") === "undefined") {
				$(el).data("on",_('Enable'));
			}
			if(typeof $(el).data("off") === "undefined") {
				$(el).data("off",_('Disable'));
			}
			$(el).bootstrapToggle();
		};
		var setupBootstrapTable = function(el) {
			$(el).bootstrapTable();
		};
		var setupBootstrapMultiselect = function(el) {
			$(el).multiselect({
				enableFiltering: true,
				enableCaseInsensitiveFiltering: true
			});
		};
		var setupBootstrapSelect = function(el) {
			if(typeof $(el).data("container") === "undefined") {
				$(el).data("container","body");
			}
			$(el).selectpicker();
		};

		$("#globalModal").on("shown.bs.modal",function() {
			$('#globalModal .modal-body select[data-toggle="select"]:visible').each(function() {
				setupBootstrapSelect(this);
			});
			$('#globalModal .modal-body select[data-toggle="multiselect"]:visible').each(function() {
				setupBootstrapMultiselect(this);
			});
			$('#globalModal .modal-body input[type=checkbox][data-toggle="toggle"]:visible').each(function() {
				setupBootstrapToggle(this);
			});
			$('#globalModal .modal-body table[data-toggle="table"]:visible').each(function() {
				setupBootstrapTable(this);
			});
		});

		$(document).on("post-body.simplewidget", function() {
			$('.small-widget-content select[data-toggle="select"]:visible').each(function() {
				setupBootstrapSelect(this);
			});
			$('.small-widget-content select[data-toggle="multiselect"]:visible').each(function() {
				setupBootstrapMultiselect(this);
			});
			$('.small-widget-content input[type=checkbox][data-toggle="toggle"]:visible').each(function() {
				setupBootstrapToggle(this);
			});
			$('.small-widget-content table[data-toggle="table"]:visible').each(function() {
				setupBootstrapTable(this);
			});
		});
		$(document).on("post-body.widgets",function(){
			$('.grid-stack select[data-toggle="select"]:visible').each(function() {
				setupBootstrapSelect(this);
			});
			$('.grid-stack select[data-toggle="multiselect"]:visible').each(function() {
				setupBootstrapMultiselect(this);
			});
			$('.grid-stack input[type=checkbox][data-toggle="toggle"]:visible').each(function() {
				setupBootstrapToggle(this);
			});
			$('.grid-stack table[data-toggle="table"]:visible').each(function() {
				setupBootstrapTable(this);
			});
		});
		$(document).on("post-body.widgetsettings post-body.simplewidgetsettings",function(){
			var load = function() {
				$('.widget-settings-content select[data-toggle="select"]:visible').each(function() {
					setupBootstrapSelect(this);
				});
				$('.widget-settings-content select[data-toggle="multiselect"]:visible').each(function() {
					setupBootstrapMultiselect(this);
				});
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
				var res = params[i].match(/(.+)=(.+)/);
				if (res) {
					self.urlParams[res[1]] = res[2];
				}
			}
		}
	},
	callModuleByMethod: function() {
		var args = Array.prototype.slice.call(arguments),
				mdata = [],
				module = args.shift().modularize(),
				method = args.shift();

		if(UCP.validMethod(module, method)) {
			return UCP.Modules[module][method].apply( UCP.Modules[module] , args );
		} else {
			return null;
		}
	},
	callModulesByMethod: function() {
		var args = Array.prototype.slice.call(arguments),
				mdata = {},
				method = args.shift();

		if(typeof modules === "undefined") {
			return mdata;
		}

		$.each(modules, function( index, module ) {
			if (UCP.validMethod(module, method)) {
				mdata[module] = UCP.Modules[module][method].apply( UCP.Modules[module] , args );
			}
		});
		return mdata;
	},
	setupLogin: function() {
		var $this = this;
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
					UCP.showAlert("<b>"+_("Please enter either a username or email address")+"</b>", 'warning', 'mid-center');
					fbtn.prop("disabled", false);
					fbtn.text(otext);
				} else {
					var queryString = $("#frm-login").formSerialize();
					queryString = queryString + "&quietmode=1&module=User&command=forgot";
					$.post( "index.php", queryString, function( data ) {
						message	= data.message == "" ? _("Submitted") : data.message;
						level 	= data.status ? "success" : "danger";
						UCP.showAlert("<h3>"+message+"</h3>", level, "mid-center", "center");
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
					UCP.showAlert(_("New password and old password do not match"),"warning");
					return false;
				} else if (pass1 === "" || pass2 === "") {
					UCP.showAlert(_("Password fields can't be blank!"),"warning");
					return false;
				} else {
					var queryString = $("#frm-login").formSerialize();
					queryString = queryString + "&quietmode=1&module=User&command=reset";
					$.post( "index.php", queryString, function( data ) {
						if (!data.status) {
							if(typeof data.message != "undefined" && typeof data.error == "undefined"){
								$("#error-msg").html(data.message).fadeIn("fast");
							}
							if(typeof data.error != "undefined"){
									err = "<ul>";
									$.each(data.error, function(i, v) {
											err += "<li>"+v+"</li>";
									});
									err += "</ul>";
									$("#modal-policies").modal('show');
									$(".modal-body").html(err);
							}
						} else {
							UCP.showAlert(_("Password has been changed!"));
							$("#switch-login").click();
						}
					});

				}
			}
		});
		$("#btn-login").click(async function (event) {
			btn.prop("disabled", true);
			btn.text(_("Processing..."));

			if (typeof checkPasswordReminder === "function") {
				let res = await checkPasswordReminder();
				await handleMFAFunc(res);
			} else {
				await handleMFAFunc(true);
			}

			return false
		});

		async function handleMFAFunc(val) {
			if (val) {
				if (typeof checkMFAEnabled === "function" && parseInt(window.isMFALicensed)) {
					let username = $("input[name=username]").val(),
						password = $("input[name=password]").val();
					password = encodeURIComponent(window.btoa(password));
					await checkMFAEnabled(username, password, false, '');
				} else {
					normalLogin();
				}
			} else {
				normalLogin();
			}
		}

		function normalLogin() {
			// Regular login
			var queryString = $("#frm-login").formSerialize(),
				username = $("input[name=username]").val(),
				password = $("input[name=password]").val();

			queryString = queryString + "&module=User&command=login";
			$.post(UCP.ajaxUrl, queryString, function (data) {
				if (!data.status) {
					$("#error-msg").html(data.message).fadeIn("fast");
					$("#login-window").height("300");
					btn.prop("disabled", false);
					btn.text(_("Login"));
				} else {
					sessionStorage.setItem('username', username);
					sessionStorage.setItem('password', password);
					location.reload();
				}
			});
		}
		if ($("html").hasClass("history")) {
			$(document).on("submit", "#frm-login", function(event) {
				var queryString = $(this).formSerialize(),
						username = $("input[name=username]").val(),
						password = $("input[name=password]").val();

				btn.prop("disabled", true);
				btn.text(_("Processing..."));
				queryString = queryString + "&module=User&command=login";
				$.post( UCP.ajaxUrl, queryString, function( data ) {
					if (!data.status) {
						$("#error-msg").html(data.message).fadeIn("fast");
						$("#login-window").height("300");
						btn.prop("disabled", false);
						btn.text(_("Login"));
					} else {
						sessionStorage.setItem('username', username);
						sessionStorage.setItem('password', password);
						location.reload();
					}
				});
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
			UCP.showAlert(_("UCP is not supported in your browser"), "warning");
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
			$(document).on("visibilitychange", UCP.visibilityChange);
		} else if ((UCP.hidden = "mozHidden") in document) {
			$(document).on("mozvisibilitychange", UCP.visibilityChange);
		} else if ((UCP.hidden = "webkitHidden") in document) {
			$(document).on("webkitvisibilitychange", UCP.visibilityChange);
		} else if ((UCP.hidden = "msHidden") in document) {
			$(document).on("msvisibilitychange", UCP.visibilityChange);
		// IE 9 and lower:
		} else if ("onfocusin" in document) {
			$(document).on("onfocusin onfocusout", UCP.visibilityChange);
		// All others:
		} else {
			$(window).on("onpageshow onpagehide onfocus onblur", UCP.visibilityChange);
		}

	},
	visibilityChange: function(evt) {
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
		UCP.hidden = state;
		UCP.callModulesByMethod("windowState",document.visibilityState);
	},
	wsconnect: function(namespace, callback) {
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
					UCP.displayGlobalMessage(sprintf(_("Unable to get a token to use UCP Node Server.<br>%s"),data.message), "red", true);
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
				$.ajax({
					url: "ajax.php", data: { module: "ucp", command: "fetchSettings" }, success: function (data) {
						ucpserver = data.ucpserver;
						if (!ucpserver.enabled) {
							clearTimeout(timeout);
							socket.disconnect();
							UCP.removeGlobalMessage();
						} else {
							UCP.displayGlobalMessage(sprintf(_("Unable to connect to the UCP Node Server.<br>%s"), reason), "red", true);
						}
						callback(false);
					}, error: function (xhr, status, error) {
						console.error("Error fetching settings:", error);
					}, dataType: "json", type: "POST"
				});
			});
		}
	},
	connect: function(username, password) {
		if(this.pollID === null) {
			var $this = this;
			//Interval is in a callback to shortpoll to make sure we are "online"
			UCP.shortpoll(function() {
				UCP.pollID = setInterval(function() {
					UCP.shortpoll();
				},5000);
				$this.callModulesByMethod("connect",username,password);
				UCP.websocketConnect();
			});
		}
	},
	disconnect: function() {
		clearInterval(this.pollID);
		this.pollID = null;
		this.polling = false;
		this.callModulesByMethod("disconnect");
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
			$.ajax(
				{
					url: "ajax.php",
					dataType: "json",
					type: "POST",
					data:
					{
						quietmode: 1,
						command: "poll",
						data: mdata
					}
				}
			).done(function(data) {
				if (data.status) {
					if (typeof callback === "function") {
						callback();
					}
					$.each(data.modData, function( module, data ) {
						if (UCP.validMethod(module, "poll") && $(".grid-stack-item[data-rawname='"+module.toLowerCase()+"']").length) {
							UCP.Modules[module].poll(data, $.url().param());
						}
					});
				}
				UCP.polling = false;
			}).fail(function(jqXHR, textStatus, errorThrown) {
				if (jqXHR.status === 401) {
					UCP.showAlert(_("The session has expired and you are being forcibly logged out"),'danger');
					$("#alert_modal .modal-footer").remove();
					$("#alert_modal .modal-header button").remove();
					UCP.disconnect();
					window.location = "?logout";
				}
			});
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
	/**
	 * Show Alert Toast
	 * @method showAlert
	 * @param  {string}  message       The Text or HTML to show.
	 * @param  {string}  type          Default: info. E.g: success, warning, danger, info.
	 * @param  {string}  position      default: top-right. E.g: bottom-left or bottom-right or bottom-center or top-left or top-right or top-center or mid-center or an object representing the left, right, top, bottom values to position the toast on page.
	 * @param  {string}  textAlign     default: left. E.g: left, right, center.
	 */
	showAlert: function(message, type = "info", position = "top-right", textAlign = 'left'){
		/**
		 * https://kamranahmed.info/toast
		 * https://github.com/kamranahmedse/jquery-toast-plugin
		 */
		$.toast().reset('all');
		var type_class = "";
		switch(type) {
			case 'success':
				type_class = "#50CB34";
				icon_type  = "success";
				t_color    = "white";
			break;
			case 'warning':
				type_class = "#E0DE00";
				icon_type  = "error";
				t_color    = "grey";
			break;
			case 'danger':
				type_class = "#FF4B4B";
				icon_type  = "warning";
				t_color    = "white";
			break;
			case 'info':
			default:
				type_class = "#333333";
				icon_type  = "info";
				t_color    = "white";
			break;
		}
		$.toast({ 
			text : "<b>"+message+"</b>", 
			showHideTransition : 'slide',  	// It can be plain, fade or slide
			bgColor : type_class,         	// Background color for toast
			loader : false,					// Show progress bar
			icon : icon_type,				// show icon
			textColor : t_color,           	// text color
			allowToastClose : false,       	// Show the close button or not
			hideAfter : 4000,              	// `false` to make it sticky or time in miliseconds to hide after
			stack : 5,                     	// `fakse` to show one stack at a time count showing the number of toasts that can be shown at once
			textAlign : textAlign,          // Alignment of text i.e. left, right, center
			position : position       		// bottom-left or bottom-right or bottom-center or top-left or top-right or top-center or mid-center or an object representing the left, right, top, bottom values to position the toast on page
		  })
	},
	/**
	 * Show Confirmation Modal Box
	 * @method showConfirm
	 * @param  {string}    html          The HTML to show
	 * @param  {string}    type          The alert info type
	 * @param  {function}    callback_func Callback function when the user presses accept
	 */
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
	/**
	 * Show a global dialog box
	 * @method showDialog
	 * @param  {string}   title    The HTML title of the modal
	 * @param  {string}   content  The HTML content of the modal
	 * @param  {string}   footer   The HTML footer of the modal
	 * @param  {Function} callback Callback function when the modal is displayed
	 */
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
	addChat: function(module, id, icon, from, to, title, msgid, message, callback, html, direction) {
		html = (typeof html !== "undefined") ? html : false;
		title = (typeof title !== "undefined") ? title : '<div class="from"><strong>F:</strong> '+from+'</div><br/><div class="to"><strong>T:</strong> '+to+'</div>';
		if (!$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).length && (typeof this.messageBuffer[id] === "undefined")) {
			//add placeholder
			if (typeof msgid !== "undefined") {
				this.messageBuffer[id] = [];
				this.messageBuffer[id].push({
					msgid: msgid,
					message: message
				});
			}
			var newWindow = (typeof msgid === "undefined");
			$.ajax({ url: "ajax.php", data: { module: "ucp", command: "template", type: "chat", newWindow: newWindow, template: { module: module, icon: icon, id: id, to: to, from: from, title: title } }, success: function(data) {
				$( "#messages-container" ).append( data.contents );
				$("#messages-container .message-box[data-id=\"" + id + "\"] .response textarea").emojioneArea({
					pickerPosition: "top",
					filtersPosition: "top",
					tonesStyle: "checkbox",
					inline: true,
					useInternalCDN: false,
					imageType: 'svg',
					textcomplete: {
						maxCount: 5,
						placement: 'top'
					}
				});
				$( "#messages-container .message-box[data-id=\"" + id + "\"]" ).fadeIn("fast", function() {
					if (typeof msgid !== "undefined") {
						if (typeof UCP.messageBuffer[id] !== "undefined") {
							$.each(UCP.messageBuffer[id], function(i, v) {
								UCP.addChatMessage(id, v.msgid, v.message, false, html, direction);
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
				UCP.addChatMessage(id, msgid, message, false, html, direction);
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
	addChatMessage: function(id, msgid, message, newmsg, htmlV, direction) {
		var emailre = /([a-z0-9_\.-]+)@([\da-z\.-]+)\.([a-z\.]{2,6})/ig,
				urlre = /((([A-Za-z]{3,9}:(?:\/\/)?)(?:[\-;:&=\+\$,\w]+@)?[A-Za-z0-9\.\-]+|(?:www\.|[\-;:&=\+\$,\w]+@)[A-Za-z0-9\.\-]+)((?:\/[\+~%\/\.\w\-_]*)?\??(?:[\-\+=&;%@\.\w_]*)#?(?:[\.\!\/\\\w]*))?)/ig,
				html = (typeof htmlV !== "undefined") ? htmlV : false;
		if(!html) {
			message = emojione.toImage(htmlEncode(message));
			message = message.replace(urlre,"<a href='$1' target='_blank'>$1</a>");
			message = message.replace(emailre,"<a href='mailto:$1@$2.$3' target='_blank'>$1@$2.$3</a>");
		}

		//message already exists
		if($( "#messages-container .message-box[data-id=\"" + id + "\"] .message[data-id='"+msgid+"']").length) {
			return;
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
				msgid: msgid,
				message: message
			});
		}
	},
	displayGlobalMessage: function(message, color, sticky) {
		UCP.showAlert(message,'danger');
	},
	removeGlobalMessage: function() {
		//nothing
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
		var $this = this;
		if (this.loggedIn) {
			$(document).on("post-body.widgets", function(event, widget_id, dashboard_id) {
				if(widget_id === null) {
					$this.connect();
				}
			});

		}
	},
	offline: function(event) {
		this.disconnect();
	},
	logIn: function(event) {
		var username = sessionStorage.getItem('username');
		var password = sessionStorage.getItem('password');
		sessionStorage.removeItem('username');
		sessionStorage.removeItem('password');
		//TODO: need to figure out text domains!
		textdomain(this.activeModule.toLowerCase());
		this.loggedIn = true;
		var $this = this;
		$(document).on("post-body.widgets", function(event, widget_id, dashboard_id) {
			if(widget_id === null) {
				$this.connect(username, password);
			}
		});
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
	},
	durationFormatter: function(value, amount) {
		amount = (typeof amount !== "undefined" && amount !== null) ? amount : "seconds"
		return moment.duration(parseInt(value), amount).format('D[ day] H[ hour(s)] m[ minute] s[ second]');
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

function dbug(data) {
	console.log(data);
}

$('#globalModal').on('hide.bs.modal',function(){
	$('#globalModalLabel').html("");
	$('#globalModalBody').html("");
	$('#globalModalFooter').html("");
});


/** Language, global functions so they act like php procedurals **/
UCP.i18n = new Jed(languages);

$("#saveTemplate").click(function() {
	id = $("#templateID").val();
	uid = $("#userID").val();
	$.post("ajax.php?module=User&command=saveTemplate", {tempid: id, uid: uid}, function( data ) {
		if (!data.status) {
			alert(data.message);
		} else {
			alert(data.message);
			location.reload();
		}
	});
	return false
});

$("#resetTemplate").click(function() {
	if (confirm("Are you sure you wish to reset the template ?")){
		var answer = prompt ("Reset option will reset all your existing UCP dashboard/widgets configuration to your assigned template's UCP configuration. This action is irrevocable. Are you sure you want to reset, please type \"reset\" to confirm ? ");
		if (answer != 'reset') {
			return false;
		}
		uid = $("#userID").val();
		$.post("ajax.php?module=User&command=resetTemplate", {uid: uid}, function( data ) {
			if (!data.status) {
				alert(data.message);
			} else {
				alert(data.message);
				var ucp;
				ucp =  $(location).attr('origin')+$(location).attr('pathname');
				window.open(ucp,'_self');
			}
		});
		return false
	}
});
