var UCPC = Class.extend({
	init: function(){
		this.loggedIn = false;
		this.pollID = null;
		this.notify = null;
		this.activeModule = 'Home';
		this.transitioning = false;
		this.lastScrollTop = 0;
		this.hidden = 'hidden';
	},
	ready: function() {
		//Detect if the client allows touch access.
		if(Modernizr.touch) {
			$$('#dashboard').swipeLeft(function() {
				if($('.pushmenu-left').hasClass('pushmenu-open')) {
					toggleMenu();
				}
			});
			$$('#dashboard').swipeRight(function() {
				if(!$('.pushmenu-left').hasClass('pushmenu-open')) {
					toggleMenu();
				}
			});
		}
		//Bind to the Resizer
		$(window).resize(function() {UCP.windowResize();});
		$(document).bind('logIn', function( event ) {UCP.logIn(event);});
		$(document).bind('logOut', function( event ) {UCP.logOut(event);});
		$(window).bind('online', function( event ) {UCP.online(event);});
		$(window).bind('offline', function( event ) {UCP.offline(event);});

		//Start PJAX Stuff
		if ($.support.pjax) {
			//logout bind
			$(document).pjax('a[data-pjax-logout]', '#content-container');

			//Navigation Clicks
			$(document).on('click', '[data-pjax] a, a[data-pjax]', function(event) {
				var container = $('#dashboard-content');
				var clicker = $(this).data('mod');
				var breadcrumbs = '<li><a data-mod="home" data-pjax href="?display=dashboard&amp;mod=home">Home</a></li>';
				$.pjax.click(event, {container: container});

				var mod = $.url().param('mod');
				var sub = $.url().param('sub');

				if(mod != 'home') {
					breadcrumbs = breadcrumbs+'<li class="active">'+mod+'</li>';
				}
				if(typeof sub !== 'undefined') {
					breadcrumbs = breadcrumbs+'<li class="active">'+sub+'</li>';
				}

				$('#top-dashboard-nav').html(breadcrumbs);

				$( ".pushmenu li").each(function( index ) {
					if($(this).data('mod') == clicker) {
						$(this).addClass('active');
					} else {
						$(this).removeClass('active');
					}
				});
				$( ".nav li" ).each(function( index ) {
					if($(this).data('mod') == clicker) {
						$(this).addClass('active');
					} else {
						$(this).removeClass('active');
					}
				});
			});

			$(document).on('submit', '#frm-login', function(event) {
				var queryString = $(this).formSerialize();
				queryString = queryString + '&quietmode=1&module=User&command=login';
				$.post( "index.php", queryString, function( data ) {
					if(!data.status) {
						$('#error-msg').html(data.message).fadeIn("fast");
						$('#login-window').height('300');
					} else {
						$.pjax.submit(event, "#content-container");
						$(document).one('pjax:end', function() {
							$(document).trigger('logIn');
						});
					}
				}, "json");
				return false;
			});
		}

		$('a[data-pjax-logout]').click(function(event) {
			$(document).trigger('logOut');
		});

		$(document).on('pjax:end', function() {UCP.pjaxEnd();});
		$(document).on('pjax:start', function() {UCP.pjaxStart();});
		$(document).on('pjax:timeout', function(event) {UCP.pjaxTimeout(event);});
		$(document).on('pjax:error', function(event) {UCP.pjaxError(event);});

		//if we are already logged in (the login window is missing) in then throw the loggedIn trigger
		if(!$('#login-window').length) {
			$(document).trigger('logIn');
		}

		//Show/Hide Settings Drop Down
		$('#top-dashboard-nav-right').click(function() {
			$('#settings-menu').toggle();
		});

		//Hide Settings Menu when clicking outside of it
		$('html').click(function(event) {
			if(($(event.target).parents().index($('#top-dashboard-nav-right')) == -1) && $(event.target).parents().index($('#settings-menu')) == -1) {
				if($('#settings-menu').is(":visible")) {
					$('#settings-menu').hide();
				}
			}
		});

		//Show/Hide Side Bar
		$('#bc-mobile-icon').click(function() {
			UCP.toggleMenu();
		});

		//mobile submenu display
		$('.mobileSubMenu').click(function() {
			var menu = $(this).data('mod');
			$('#submenu-'+menu).slideToggle();
		});

		$('#dashboard-content').bind('scroll', function() {
			if(UCP.transitioning || $( window ).width() > 767) {
				return true;
			}
			st = $('#dashboard-content').scrollTop();
			if (st > 40) {
				$(document).trigger('hideFooter');
				UCP.windowResize(true);
			} else {
				$(document).trigger('mobileScrollUp');
				$(document).trigger('showFooter');
				UCP.windowResize();
			}
			UCP.transitioning = true;
			setTimeout(function(){UCP.transitioning = false;},90);

			UCP.lastScrollTop = st;
		});

		UCP.windowResize();

		//This allows browsers to request user notifications from said user.
		$(document).click(function() {
			if(UCP.loggedIn && Notify.needsPermission() && UCP.notify === null) {
				Notify.requestPermission(UCP.notificationsAllowed(),UCP.notificationsDenied());
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
		} else if ('onfocusin' in document) {
			$(document).on("onfocusin onfocusout",UCP.onchange);
		// All others:
		} else {
			$(window).on("onpageshow onpagehide onfocus onblur",UCP.onchange);
		}
	},
	onchange: function (evt) {
		var v = 'visible', h = 'hidden',
			evtMap = {
				focus:v, focusin:v, pageshow:v, blur:h, focusout:h, pagehide:h
			};

		evt = evt || window.event;
		var state = '';
		if (evt.type in evtMap) {
			state = evtMap[evt.type];
		} else {
			state = this[UCP.hidden] ? "hidden" : "visible";
		}
		if (typeof window[UCP.activeModule] == 'object' && typeof window[UCP.activeModule].windowState == 'function') {
			window[UCP.activeModule].windowState(state);
		}
	},
	poller: function() {
		this.pollID = setInterval(function(){
			$.ajax({ url: "index.php?quietmode=1&command=poll", data: {data: $.url().param()}, success: function(data){
				if(data.status) {
					$.each(data.modData, function( module, data ) {
						if (typeof window[module] == 'object' && typeof window[module].poll == 'function') {
							window[module].poll(data, $.url().param());
						}
					});
				}
			}, dataType: "json"});
		}, 5000);
	},
	windowResize: function(hiddenFooter) {
		if($( window ).width() > 767 && $('.pushmenu-left').hasClass('pushmenu-open')) {
			UCP.toggleMenu();
		}

		hiddenFooter = (typeof hiddenFooter !== undefined) ? hiddenFooter : false;
		//run the resize hack against dashboard content
		if($('#dashboard-content').length) {
			if(!hiddenFooter) {
				$('#dashboard-content').height($('#dashboard').height() - 135);
			} else {
				$('#dashboard-content').height($('#dashboard').height() - 59);
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
		$('.dialog').fadeOut('fast', function(event) {
			$(this).remove();
		});
	},
	showDialog: function(title,content,height,width) {
		var w = (typeof width !== undefined) ? width : '250px';
		var h = (typeof height !== undefined) ? height : '150px';
		var html = '<div class="dialog" style="height:'+h+'px;width:'+w+'px;margin-top:-'+h/2+'px;margin-left:-'+w/2+'px;"><div class="title">'+title+'<i class="fa fa-times" onclick="UCP.closeDialog()"></i></div><div class="content">'+content+'</div></div>';
		if($('.dialog').length) {
			$('.dialog').fadeOut('fast', function(event) {
				$(this).remove();
				$(html).appendTo("#dashboard-content").hide().fadeIn('fast');
			});
		} else {
			$(html).appendTo("#dashboard-content").hide().fadeIn('fast');
		}
	},
	toggleMenu: function() {
		$('.pushmenu-push').toggleClass('pushmenu-push-toright');
		$('.pushmenu-left').toggleClass('pushmenu-open');
		//dropdown-pushmenu
		$( ".pushmenu .dropdown-pushmenu" ).each(function( index ) {
			if($(this).is(":visible")) {
				$(this).slideToggle();
			}
		});
	},
	toTitleCase: function(str) {
		return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
	},
	pjaxEnd: function(event) {
		this.windowResize();
		$('#loader-screen').fadeOut('fast');
		console.log('Execute: '+this.activeModule+'.hide()');
		if (typeof window[this.activeModule] == 'object' && typeof window[this.activeModule].hide == 'function') {
			window[this.activeModule].hide(event);
		}
		this.activeModule = $.url().param('mod');
		this.activeModule = (this.activeModule !== undefined) ? UCP.toTitleCase(this.activeModule) : 'Home';
		console.log('Execute: '+this.activeModule+'.display()');
		if (typeof window[this.activeModule] == 'object' && typeof window[this.activeModule].display == 'function') {
			window[this.activeModule].display(event);
		}
		this.binds();
	},
	pjaxStart: function(event) {

	},
	pjaxTimeout: function(event) {
		//query higher up event here
		$('#loader-screen').fadeIn('fast');
		event.preventDefault();
		return false;
	},
	pjaxError: function(event) {
		//query higher up event here
		console.log('error');
		event.preventDefault();
		return false;
	},
	online: function(event) {
		if(this.loggedIn && this.pollID === null) {
			this.poller();
		}
	},
	offline: function(event) {
		if(this.pollID !== null) {
			clearInterval(this.pollID);
			this.pollID = null;
		}
	},
	logIn: function(event) {
		this.activeModule = $.url().param('mod');
		this.activeModule = (this.activeModule !== undefined) ? UCP.toTitleCase(this.activeModule) : 'Home';
		this.loggedIn = true;
		this.poller();
		if(!Notify.needsPermission() && this.notify === null) {
			this.notify = true;
		}
		console.log('Execute: '+this.activeModule+'.display()');
		if (typeof window[this.activeModule] == 'object' && typeof window[this.activeModule].display == 'function') {
			window[this.activeModule].display(event);
		}
		this.binds();
	},
	logOut: function(event) {
		this.loggedIn = false;
		clearInterval(this.pollID);
		this.pollID = null;
		if (typeof window[this.activeModule] == 'object' && typeof window[this.activeModule].hide == 'function') {
			window[this.activeModule].hide(event);
		}
	},
	binds: function() {
		$('.form-group label.help').click(function() {
			var f = $(this).prop('for');
			if(!$('.help-hidden[data-for="'+f+'"]').is(':visible')) {
				//hide all others
				$('.help-hidden').fadeOut('slow');
				//display our reference
				$('.help-hidden[data-for="'+f+'"]').fadeIn('slow');
			} else {
				$('.help-hidden[data-for="'+f+'"]').fadeOut('slow');
			}
		});
	}
});
var UCP = new UCPC();
$(function() {
	UCP.ready();
});
