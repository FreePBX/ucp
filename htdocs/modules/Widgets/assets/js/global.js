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
				show_alert("Something went wrong saving the information (grid)", "danger");
			}
			deactivate_full_loading();
		}
	);
}

function save_sidebar_content(){
	activate_full_loading();

	var sidebar_objects = $("#side_bar_content li.custom-widget a");

	var all_content = [];

	sidebar_objects.each(function(){

		var widget_id = $(this).data('id');
		var widget_module_name = $(this).data('module_name');
		var widget_rawname = $(this).data('rawname');
		var widget_name = $(this).data('name');
		var widget_icon = $(this).data('icon');

		var small_widget = {id:widget_id,
							module_name: widget_module_name,
							rawname: widget_rawname,
							name: widget_name,
							icon: widget_icon};

		all_content.push(small_widget);
	});

	var gridDataSerialized = JSON.stringify(all_content);

	$.post( "?quietmode=1&module=Dashboards&command=savesimplelayout",
		{
			data: gridDataSerialized
		},
		function( data ) {
			if(data.status){
				console.log("sidebar saved");
			}else {
				show_alert("Something went wrong saving the information (sidebar)", "danger");
			}
			deactivate_full_loading();
		}
	);
}

var WidgetsO = Class.extend({
	init: function() {
		$(".gridster ul").gridster({
			serialize_params: function($w, wgd){
				return {
					id: $w.attr('data-id'),
					widget_module_name: $w.attr('data-widget_module_name'),
					name: $w.attr('data-name'),
					rawname: $w.attr('data-rawname'),
					widget_type_id: $w.attr('data-widget_type_id'),
					col: wgd.col,
					row: wgd.row,
					size_x: wgd.size_x,
					size_y: wgd.size_y
				};
			},
			widget_margins: [10, 10],
			widget_base_dimensions: ['auto', 145],
			min_cols: 10,
			min_rows: 5,
			max_cols: 7,
			shift_widgets_up: false,
			shift_larger_widgets_down: false,
			collision: {
				wait_for_mouseup: true
			},
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
			}
		});

		var gridster = $(".gridster ul").gridster().data('gridster');

		gridster.$widgets.each(function(){
			if(!$(this).hasClass("add-widget-widget")){
				var widget_id = $(this).attr('data-widget_type_id');
				var widget_rawname = $(this).attr('data-rawname');
				var widget_content_container = $(this).find(".widget-content");
				get_widget_content(widget_content_container, widget_id, widget_rawname);
			}
		});

		//
	}

}), dashboard_widgets = new WidgetsO();

$(function(){ //DOM Ready
	if($(".gridster ul").length) {
		dashboard_widgets.init();
	}
});
