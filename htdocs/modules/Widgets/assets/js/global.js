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
			widget_base_dimensions: [140, 140],
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
					col: wgd.col,
					row: wgd.row,
					size_x: wgd.size_x,
					size_y: wgd.size_y
				}
			}

		});
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