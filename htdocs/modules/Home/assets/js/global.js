var HomeC = UCPC.extend({
	init: function(){
		this.packery = false;
		this.doit = null;
	},
	poll: function(data){
		//console.log(data)
	},
	display: function(event) {
		$(window).on("resize.Home", Home.resize);
		this.resize();
	},
	hide: function(event) {
		$(window).off("resize.Home");
		//$('.masonry-container').packery('destroy');
		this.packery = false;
	},
	resize: function() {
		var wasPackeryEnabled = this.packery;
		this.packery = $(window).width() >= 768;
		if(this.packery !== wasPackeryEnabled) {
			if(this.packery) {
				clearTimeout(this.doit);
				this.doit = setTimeout(function() {
					$('.widget').css('width','33.33%');
					$('.widget').css('margin-bottom','');
					$('.masonry-container').packery({
						columnWidth: 40,
						itemSelector: '.widget'
					});
				}, 100);
			} else {
				this.packery = false;
				$('.masonry-container').packery('destroy');
				$('.widget').css('width','100%');
				$('.widget').css('margin-bottom','10px');
			}
		} else if(!this.packery) {
			$('.widget').css('width','100%');
			$('.widget').css('margin-bottom','10px');
		}
	}
});
var Home = new HomeC();
