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
	refresh: function(module,id) {
		$('#'+module+'-title-'+id+' i.fa-refresh').addClass('fa-spin');
		$.post( "?quietmode=1&module="+module+"&command=homeRefresh&id="+id, {}, function( data ) {
			$('#'+module+'-title-'+id+' i.fa-refresh').removeClass('fa-spin');
			$('#'+module+'-content-'+id).html(data.content);
		});
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
