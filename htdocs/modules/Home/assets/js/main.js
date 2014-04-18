var Home = new function() {
	this.refresh = function(module) {
		$('#'+module+'-widget-title i').addClass('fa-spin');
	};
};

$(function() {
	$('.masonry-container').packery({
		columnWidth: 40,
		itemSelector: '.widget'
	});
});
