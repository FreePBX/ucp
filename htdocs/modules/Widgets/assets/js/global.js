function save_layout_content(){

	activate_full_loading();

	var gridster_object = $(".gridster ul").gridster().data('gridster');
	var gridData = gridster_object.serialize();
	var gridDataSerialized = JSON.stringify(gridData);

	$.post( "?quietmode=1&module=Dashboards&command=savedashlayout",
		{
			id: UCP.activeDashboard,
			data: gridDataSerialized
		},
		function( data ) {
			if(data.status){
				console.log("saved grid");
			}else {
				show_alert("Something went wrong saving the information", "danger");
			}
			deactivate_full_loading();
		}
	);
}

var WidgetsO = UCPMC.extend({
	init: function() {
		$(".gridster ul").gridster({
			widget_margins: [10, 10],
			widget_base_dimensions: [150, 150],
			min_cols: 10,
			min_rows: 5,
			resize: {
				enabled: true,
				stop: function(){
					save_layout_content();
				}
			},
			draggable: {
				stop: function(){
					save_layout_content();
				}
			},
			serialize_params: function($w, wgd){
				return {
					id: $w.attr('data-id'),
					name: $w.attr('data-name'),
					rawname: $w.attr('data-rawname'),
					widget_type_id: $w.attr('data-widget_type_id'),
					col: wgd.col,
					row: wgd.row,
					size_x: wgd.size_x,
					size_y: wgd.size_y
				}
			}
		});

		var gridster = $(".gridster ul").gridster().data('gridster');

		console.log(gridster.$widgets);

		gridster.$widgets.each(function(){
			var widget_id = $(this).attr('data-widget_type_id');
			var widget_rawname = $(this).attr('data-rawname');
			var widget_content_container = $(this).find(".widget-content");
			get_widget_content(widget_content_container, widget_id, widget_rawname);
		});

		//
	},
	poll: function(data) {

	},
	display: function(event) {

	},
	hide: function(event) {

	},
	refresh: function(module, id) {

	},
	originate: function() {
	},
	resize: function() {
	}

}), dashboard_widgets = new WidgetsO();

$(function(){ //DOM Ready
	dashboard_widgets.init();
});