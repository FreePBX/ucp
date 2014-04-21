var SettingsC = UCPC.extend({
	init: function(){
		this.packery = false;
		this.doit = null;
	},
	poll: function(data){
		//console.log(data)
	},
	display: function(event) {
		$(window).on("resize.Settings", Settings.resize);
		this.resize();
	},
	hide: function(event) {
		$(window).off("resize.Settings");
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
					$('.section').css('width','300px');
					$('.section').css('margin-bottom','');
					$('.masonry-container').packery({
						columnWidth: 40,
						itemSelector: '.section'
					});
				}, 100);
			} else {
				this.packery = false;
				$('.masonry-container').packery('destroy');
				$('.section').css('width','100%');
				$('.section').css('margin-bottom','10px');
			}
		} else if(!this.packery) {
			$('.section').css('width','100%');
			$('.section').css('margin-bottom','10px');
		}
	}
});
var Settings = new SettingsC();
