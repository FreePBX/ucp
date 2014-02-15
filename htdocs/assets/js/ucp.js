var transitioning = false;
$(function() {
	/* This screws with the checkbox selector on iphones for some reason */
    //FastClick.attach(document.body);
	if(Modernizr.touch) {
		//user has some sort of touch based device
		$$('#dashboard').swipeLeft(function() {
			if($('.pushmenu-left').hasClass('pushmenu-open')) {
				toggleMenu()
			}
		});
		$$('#dashboard').swipeRight(function() {
			if(!$('.pushmenu-left').hasClass('pushmenu-open')) {
				toggleMenu()
			}
		});
	}
	
	//Hide Menu when the screen resizes
	$( window ).resize(function() {
		if($( window ).width() > 767 && $('.pushmenu-left').hasClass('pushmenu-open')) {
			toggleMenu();
		}		
		resizeContent();
	});
	
	//help tags
	$("a.info").each(function(){
		$(this).after('<span class="help">?<span>' + $(this).find('span').html() + '</span></span>');
		$(this).find('span').remove();
		$(this).replaceWith($(this).html())
	})

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
	
	stylize();
	if ($.support.pjax) {
	    $(document).on('click', '[data-pjax] a, a[data-pjax]', function(event) {
			var container = $('#dashboard-content')
			var clicker = $(this).data('mod');
			var breadcrumbs = '<li><a data-mod="home" data-pjax href="?display=dashboard&amp;mod=home">Home</a></li>';
			$.pjax.click(event, {container: container})
			
			var mod = $.url().param('mod');
			var sub = $.url().param('sub');
			
			if(mod != 'home') {
					breadcrumbs = breadcrumbs+'<li class="active">'+mod+'</li>'
			}
			if(typeof sub !== 'undefined') {
				breadcrumbs = breadcrumbs+'<li class="active">'+sub+'</li>'
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
	    })
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
				$.pjax.submit(event, "#content-container")
			}
		}, "json");
		return false;
	})
	
	//After load event restylize the page
	$(document).on('pjax:end', function() {
		stylize();
		resizeContent();
	})
	
	$(document).on('pjax:timeout', function(event) {
		//query higher up event here
		console.log('timeout')
		event.preventDefault()
		return false
	})
	$(document).on('pjax:error', function(event) {
		//query higher up event here
		console.log('error')
		event.preventDefault()
		return false
	})
	
	$(".pushmenu").bind("transitionend webkitTransitionEnd oTransitionEnd MSTransitionEnd", function(){ 
		//transitioning = false;
		//alert('completed');
	});
	
	resizeContent();
});

function resizeContent() {
	//run the resize hack against dashboard content
	if($('#dashboard-content').length) {
		$('#dashboard-content').height($('#dashboard').height() - 60);
	}
}

//Applies any javascript related stylizers
function stylize() {
	$('.radioset').buttonset();
	$( "button, input[type='button'], input[type='submit']" ).button();
}

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
	$('#submenu-'+menu).slideToggle()
}