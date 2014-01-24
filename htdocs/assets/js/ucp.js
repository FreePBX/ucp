$(function() {
	/* This screws with the checkbox selector on iphones for some reason */
    //FastClick.attach(document.body);
	if(Modernizr.touch) {
		//user has some sort of touch based device
	}
	

	
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
	$(document).pjax('[data-pjax] a, a[data-pjax]', '#content-container');
	
	$(document).on('submit', '#frm-login', function(event) {
		$.pjax.submit(event, "#content-container")
	})
	
	//After load event restylize the page
	$(document).on('pjax:end', function() {
		stylize();
	})
});

//Applies any javascript related stylizers
function stylize() {
	$('.radioset').buttonset();
	$( "button, input[type='button'], input[type='submit']" ).button();
}

function toggleMenu() {
    //$(this).toggleClass('active');
    $('.pushmenu-push').toggleClass('pushmenu-push-toright');
    $('.pushmenu-left').toggleClass('pushmenu-open');
}