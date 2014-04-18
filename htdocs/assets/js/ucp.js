var transitioning = false;
$(function() {
	/* This screws with the checkbox selector on iphones for some reason */
    //FastClick.attach(document.body);
	if(Modernizr.touch) {
		//user has some sort of touch based device
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

	//Hide Menu when the screen resizes
	$( window ).resize(function() {
		if($( window ).width() > 767 && $('.pushmenu-left').hasClass('pushmenu-open')) {
			toggleMenu();
		}
		UCP.resizeContent();
	});

	//help tags
	$("a.info").each(function(){
		$(this).after('<span class="help">?<span>' + $(this).find('span').html() + '</span></span>');
		$(this).find('span').remove();
		$(this).replaceWith($(this).html());
	});

	$(".help").on('mouseenter', function(){
			side = fpbx.conf.text_dir == 'lrt' ? 'left' : 'right';
			var pos = $(this).offset();
			var offset = (200 - pos.side)+"px";
			//left = left > 0 ? left : 0;
			$(this).find("span")
					.css(side, offset)
					.stop(true, true)
					.delay(500)
					.animate({opacity: "show"}, 750);
		}).on('mouseleave', function(){
			$(this).find("span")
					.stop(true, true)
					.animate({opacity: "hide"}, "fast");
	});

	if ($.support.pjax) {
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

			if($( window ).width() < 767 && $('.pushmenu-left').hasClass('pushmenu-open')) {
				toggleMenu();
			}
		});
	}

	$(document).pjax('a[data-pjax-logout]', '#content-container');

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
					$(document).trigger('loggedIn');
				});
			}
		}, "json");
		return false;
	});

	$('a[data-pjax-logout]').click(function(event) {
		$(document).trigger('logOut');
	});

	//After load event restylize the page
	$(document).on('pjax:end', function() {
		UCP.resizeContent();
		$('#loader-screen').fadeOut('fast');
	});

	$(document).on('pjax:start', function() {
		//$('#loader-screen').fadeIn('fast');
	});

	$(document).on('pjax:timeout', function(event) {
		//query higher up event here
		$('#loader-screen').fadeIn('fast');
		event.preventDefault();
		return false;
	});
	$(document).on('pjax:error', function(event) {
		//query higher up event here
		console.log('error');
		event.preventDefault();
		return false;
	});

	$(".pushmenu").bind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function(){
		//transitioning = false;
		//alert('completed');
	});

	if(!$('#login-window').length) {
		$(document).trigger('loggedIn');
	}

	UCP.resizeContent();
});
$(document).bind('loggedIn', function( event ) {
	UCP.loggedIn = true;
	UCP.poll();
	if(!Notify.needsPermission() && UCP.notify === null) {
		UCP.notify = true;
	}
});

$(document).bind('logOut', function( event ) {
	UCP.loggedIn = false;
	clearInterval(UCP.pollID);
	UCP.pollID = null;
});

$(window).bind('online', function( event ) {
	if(UCP.loggedIn && UCP.pollID === null) {
		UCP.poll();
	}
});

$(window).bind('offline', function( event ) {
	if(UCP.pollID !== null) {
		clearInterval(UCP.pollID);
		UCP.pollID = null;
	}
});

function toggleMenu() {
	if(!transitioning) {
		//transitioning = true;
		//$(this).toggleClass('active');
		$('.pushmenu-push').toggleClass('pushmenu-push-toright');
		$('.pushmenu-left').toggleClass('pushmenu-open');
		//dropdown-pushmenu
		$( ".pushmenu .dropdown-pushmenu" ).each(function( index ) {
			if($(this).is(":visible")) {
				$(this).slideToggle();
			}
		});
	}
}

function toggleSubMenu(menu) {
	$('#submenu-'+menu).slideToggle();
}

//This allows browsers to request user notifications from said user.
$(document).click(function() {
	if(UCP.loggedIn && Notify.needsPermission() && UCP.notify === null) {
		Notify.requestPermission(UCP.pg(),UCP.pd());
	}
});

var UCP = new function() {
	this.loggedIn = false;
	this.pollID = null;
	this.notify = null;
	this.poll = function() {
		this.pollID = setInterval(function(){
			$.ajax({ url: "index.php?quietmode=1&command=poll", success: function(data){
				if(data.status) {
					$.each(data.modData, function( module, data ) {
						if (typeof window[module+'_poll'] == 'function') {
							window[module+'_poll'](data);
						}
					});
				}
			}, dataType: "json"});
		}, 5000);
	};
	this.resizeContent = function() {
		//run the resize hack against dashboard content
		if($('#dashboard-content').length) {
			$('#dashboard-content').height($('#dashboard').height() - 135);
		}
	};
	this.pg = function() {
		this.notify = true;
	};
	this.pd = function() {
		this.notify = false;
	};
	this.closeDialog = function() {
		$('.dialog').fadeOut('fast', function(event) {
			$(this).remove();
		});
	};
	this.showDialog = function(title,content,height,width) {
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
	};
};
