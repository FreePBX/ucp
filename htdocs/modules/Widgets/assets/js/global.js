var WidgetsC = Class.extend({
	init: function() {
		this.activeDashboard = null;
		this.widgetMenuOpen = false;
	},
	ready: function() {
		this.setupAddDashboard();
		this.loadDashboard();
		this.initMenuDragabble();
		this.initCategoriesWidgets();
		this.initAddWidgetsButtons();
		this.initRemoveItemButtons();
		this.initLeftNavBarMenus();
		this.deactivateFullLoading();
		$(".custom-widget").each(function() {
			var widget_rawname = $(this).data("widget_rawname");
			var widget_id = $(this).data("widget_id");
			UCP.callModuleByMethod(widget_rawname,"displaySmallWidget",widget_id);
		});
	},
	pjaxEnd: function(event) {
		this.loadDashboard();
		this.deactivateFullLoading();
	},
	pjaxStart: function(event) {
		this.activateFullLoading();
	},
	loadDashboard: function() {
		var $this = this;
		if(!$(".gridster > ul").length) {
			return;
		}

		$('#add_dashboard').on('shown.bs.modal', function () {
			$('#dashboard_name').focus();
		});

		$('#add_dashboard').on('hidden.bs.modal', function () {
			$('#dashboard_name').val("");
		});

		$(document).on("click", ".edit-widget", function(){

			var container_object = $(this).parents(".flip-container");
			var rawname = $(this).data("rawname");
			var widget_id = $(this).data("widget_type_id");

			if(!container_object.hasClass("flip")){

				$(".settings-shown-blocker").show();

				container_object.addClass("flip");
				container_object.addClass("settings-shown");

				var settings_container = container_object.find(".widget-settings-content");
				$this.getSettingsContent(settings_container, widget_id, rawname);
			}
		});

		$(document).on("click", ".close-settings", function(){

			var container_object = $(this).parents(".flip-container");
			var rawname = $(this).data("rawname");
			var widget_id = $(this).data("widget_type_id");

			if(container_object.hasClass("flip")){

				$(".settings-shown-blocker").hide();
				container_object.removeClass("settings-shown");
				container_object.removeClass("flip");

				var widget_content_container = container_object.find(".widget-content");
				$this.getWidgetContent(widget_content_container, widget_id, rawname);
			}
		});

		//If got a click outside... we hide everything without saving
		$(document).on("click", ".settings-shown-blocker", function(){

			var container_object = $(".flip-container.gs-w.flip.settings-shown");

			if(container_object.hasClass("flip")){

				$(".settings-shown-blocker").hide();
				container_object.removeClass("settings-shown");
				container_object.removeClass("flip");
			}
		});

		$(".gridster > ul").gridster({
			serialize_params: function($w, wgd){
				return {
					id: $w.attr('data-id'),
					widget_module_name: $w.attr('data-widget_module_name'),
					name: $w.attr('data-name'),
					rawname: $w.attr('data-rawname'),
					widget_type_id: $w.attr('data-widget_type_id'),
					has_settings: $w.attr('data-has_settings'),
					col: wgd.col,
					row: wgd.row,
					size_x: wgd.size_x,
					size_y: wgd.size_y
				};
			},
			widget_margins: [5, 5],
			widget_base_dimensions: ['auto', 75],
			min_cols: 10,
			min_rows: 15,
			max_cols: 10,
			extra_rows: 5,
			shift_widgets_up: false,
			shift_larger_widgets_down: false,
			collision: {
				wait_for_mouseup: true
			},
			resize: {
				enabled: true,
				stop: function(){
					$this.saveLayoutContent();
				}
			},
			draggable: {
				stop: function(){
					$this.saveLayoutContent();
				}
			}
		});

		var gridster = $(".gridster > ul").gridster().data('gridster');

		gridster.$widgets.each(function(){
			if(!$(this).hasClass("add-widget-widget")){
				var widget_id = $(this).attr('data-widget_type_id');
				var widget_rawname = $(this).attr('data-rawname');
				var widget_content_container = $(this).find(".widget-content");
				$this.getWidgetContent(widget_content_container, widget_id, widget_rawname);
			}
		});

		//Are we looking a dashboard?
		var dashboard_id = $(".gridster").data("dashboard_id");
		this.activeDashboard = dashboard_id;

		$(".dashboard-menu").removeClass("active");

		$(".dashboard-menu[data-id='"+this.activeDashboard+"']").addClass("active");
		UCP.callModulesByMethod("showDashboard",this.activeDashboard);
	},
	saveLayoutContent: function() {
		this.activateFullLoading();
		var $this = this;
		var gridster_object = $(".gridster > ul").gridster().data('gridster');
		var gridData = gridster_object.serialize();
		var gridDataSerialized = JSON.stringify(gridData);

		$.post( "?quietmode=1&module=Dashboards&command=savedashlayout",
			{
				id: $this.activeDashboard,
				data: gridDataSerialized
			},
			function( data ) {
				if(data.status){
					console.log("saved grid");
				}else {
					$this.showAlert("Something went wrong saving the information (grid)", "danger");
				}
				$this.deactivateFullLoading();
			}
		);
	},
	saveSidebarContent: function() {
		var $this = this;
		this.activateFullLoading();

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
					$this.showAlert("Something went wrong saving the information (sidebar)", "danger");
				}
				$this.deactivateFullLoading();
			}
		);
	},
	activateFullLoading: function(){
		$(".main-block").removeClass("hidden");
	},
	deactivateFullLoading: function(){
		$(".main-block").addClass("hidden");
	},
	activateWidgetLoading: function(widget_object){

		var loading_html = '<div class="widget-loading-box">' +
			'					<span class="fa-stack fa">' +
			'						<i class="fa fa-cloud fa-stack-2x text-internal-blue"></i>' +
			'						<i class="fa fa-cog fa-spin fa-stack-1x secundary-color"></i>' +
			'					</span>' +
			'				</div>';

		widget_object.html(loading_html);

	},
	showAlert: function(message, type, callback_func){

		var type_class = "";
		if(type == 'success'){
			type_class = "alert-success";
		}else if(type == 'info'){
			type_class = "alert-info";
		}else if(type == 'warning'){
			type_class = "alert-warning";
		}else if(type == 'danger'){
			type_class = "alert-danger";
		}

		$("#alert_message").removeClass("alert-success alert-info alert-warning alert-danger");

		$("#alert_message").addClass(type_class);

		$("#alert_message").html(message);

		if(typeof callback_func == "function") {
			$(document).on("click", "#close_alert_button", function () {
				$("#alert_modal").modal("hide");
				callback_func();
			});
		}else {
			$(document).on("click", "#close_alert_button", function () {
				$("#alert_modal").modal("hide");
			});
		}

		$("#alert_modal").modal("show");
	},
	showConfirm: function(html, type, callback_func) {

		var type_class = "";
		if(type == 'success'){
			type_class = "alert-success";
		}else if(type == 'info'){
			type_class = "alert-info";
		}else if(type == 'warning'){
			type_class = "alert-warning";
		}else if(type == 'danger'){
			type_class = "alert-danger";
		}

		$("#confirm_content").removeClass("alert-success alert-info alert-warning alert-danger");

		$("#confirm_content").addClass(type_class);
		$("#confirm_content").html(html);

		$('#confirm_modal').one('shown.bs.modal', function () {
			$(document).one("click", "#modal_confirm_button", function(){
				if(typeof callback_func == "function"){
					callback_func();
				}
			});
		});

		$('#confirm_modal').modal('show');
	},
	widget_layout: function(widget_id, widget_module_name, widget_name, widget_type_id, widget_rawname, widget_has_settings, widget_content){

		var settings_html = '';
		if(widget_has_settings == "1"){
			settings_html = '<div class="widget-option edit-widget" data-rawname="'+widget_rawname+'" data-widget_type_id="'+widget_type_id+'">' +
								'<i class="fa fa-cog" aria-hidden="true"></i>' +
							'</div>';
		}

		var html = '' +
					'<li data-widget_module_name="'+widget_module_name+'" data-id="'+widget_id+'" data-name="'+widget_name+'" data-rawname="'+widget_rawname+'" data-widget_type_id="'+widget_type_id+'" data-has_settings="'+widget_has_settings+'" class="flip-container">' +
						'<div class="flipper">' +
							'<div class="front">' +
								'<div class="widget-title">' +
									'<div class="widget-module-name truncate-text">' + widget_module_name + '</div>' +
									'<div class="widget-module-subname truncate-text">('+widget_name+')</div>' +
									'<div class="widget-options">' +
										'<div class="widget-option remove-widget" data-widget_id="'+widget_id+'" data-widget_type_id="'+widget_type_id+'" data-widget_rawname="'+widget_rawname+'">' +
											'<i class="fa fa-times" aria-hidden="true"></i>' +
										'</div>' +
										settings_html +
									'</div>' +
								'</div>' +
								'<div class="widget-content">'+widget_content+'</div>' +
							'</div>' +
							'<div class="back">' +
								'<div class="widget-title settings-title">' +
									'<div class="widget-module-name truncate-text">Settings</div>' +
									'<div class="widget-module-subname truncate-text">(' + widget_module_name + ' '+widget_name+')</div>' +
									'<div class="widget-options">' +
										'<div class="widget-option close-settings" data-rawname="'+widget_rawname+'" data-widget_type_id="'+widget_type_id+'">' +
											'<i class="fa fa-times" aria-hidden="true"></i>' +
										'</div>' +
									'</div>' +
								'</div>' +
								'<div class="widget-settings-content">' +
								'</div>' +
							'</div>' +
						'</div>' +
					'</li>';

		return html;
	},
	smallWidgetLayout: function(widget_id, widget_module_name, widget_name, widget_type_id, widget_rawname, widget_icon, widget_content){
		var html = '' +
			'<li class="custom-widget" data-widget_id="'+widget_id+'">' +
				'<a href="#" data-module_name="'+widget_module_name+'" data-id="'+widget_id+'" data-name="'+widget_name+'" data-rawname="'+widget_rawname+'" data-type_id="'+widget_type_id+'" data-icon="' + widget_icon + '"><i class="' + widget_icon + '" aria-hidden="true"></i></a>' +
			'</li>';

		return html;
	},
	smallWidgetMenuLayout: function(widget_id, widget_rawname){
		var html = '' +
			'<div class="widget-extra-menu" id="menu_'+widget_rawname+'" data-module="'+widget_rawname+'">' +
				'<a href="#" class="closebtn" onclick="UCP.Modules.Widgets.closeExtraWidgetMenu()"><i class="fa fa-times-circle-o" aria-hidden="true"></i></a>' +
					'<div class="small-widget-content">' +
					'</div>' +
					'<button type="button" class="btn btn-xs btn-danger remove-small-widget" data-widget_id="'+widget_id+'" data-widget_rawname="'+widget_rawname+'">Remove Widget</button>' +
			'</div>';

		return html;
	},
	initMenuDragabble: function(){
		/*$(".menu-order").draggable({ axis: "x" });

		 $(".menu-space").droppable({
		 accept: ".menu-order",
		 activeClass: "droppable-menu-empty",
		 hoverClass: "droppable-menu-hover",
		 drop: function( event, ui ) {

		 console.log("bagre");
		 }
		 });*/
	},
	openExtraWidgetMenu: function() {
		this.widgetMenuOpen = true;
		$(".side-menu-widgets-container").css({ width: "250px", left: "55px"});
	},
	closeExtraWidgetMenu: function() {
		this.widgetMenuOpen = false;
		$(".side-menu-widgets-container").css({ width: "0", left: "45px"});
	},
	initLeftNavBarMenus: function(){
		var $this = this;
		$(document).on("click", ".custom-widget", function(event){
			if($this.widgetMenuOpen) {
				return; //the widget is already open. Dont reload the content
			}

			event.preventDefault();
			event.stopPropagation();

			var clicked_module = $(this).find("a").data("rawname");
			var clicked_id = $(this).find("a").data("id");

			if(!$("#menu_"+clicked_module).is(":visible")){

				if($(".widget-extra-menu").is(":visible")){
					$(".widget-extra-menu:visible").fadeOut("slow", function(){
						$("#menu_"+clicked_module).fadeIn("slow");
					});
				}else {
					$("#menu_"+clicked_module).fadeIn("slow");
				}
			}

			$this.openExtraWidgetMenu();

			var content_object = $("#menu_"+clicked_module).find(".small-widget-content");

			$this.activateWidgetLoading(content_object);

			$.post( "?quietmode=1&module=Dashboards&command=getwidgetcontent",
				{
					id: clicked_id,
					rawname: clicked_module
				},
				function( data ) {

					if(typeof data.html !== "undefined"){

						content_object.html(data.html);
						UCP.callModuleByMethod(clicked_module,"displaySmallWidgetSettings",clicked_id);

					}else {
						$this.showAlert("There was an error getting the widget information, try again later", "danger");
					}

				}, "json");
		});
	},
	initRemoveItemButtons: function(){
		var $this = this;
		$(document).on("click", ".remove-widget", function(event){
			event.preventDefault();
			event.stopPropagation();

			var widget_id = $(this).data("widget_id");
			var widget_rawname = $(this).data("widget_rawname");
			var widget_type_id = $(this).data("widget_type_id");

			$this.showConfirm("Are you sure you want to delete this widget?", "warning", function() {
				var gridster_object = $(".gridster > ul").gridster().data('gridster');
				UCP.callModuleByMethod(widget_rawname,"deleteWidget",widget_type_id,$this.activeDashboard);
				gridster_object.remove_widget($(".gs-w[data-id='" + widget_id + "']"), function() {
					$this.saveLayoutContent();
				});
			});

		});

		$(document).on("click", ".remove-small-widget", function(event){

			var widget_to_remove = $(this).data("widget_id");
			var widget_rawname = $(this).data("widget_rawname");

			console.log(widget_rawname);

			var sidebar_object_to_remove = $("#side_bar_content li.custom-widget[data-widget_id='" + widget_to_remove + "']");

			UCP.callModuleByMethod(widget_rawname,"deleteSmallWidget",widget_to_remove);

			sidebar_object_to_remove.remove();

			$this.closeExtraWidgetMenu();

			$this.saveSidebarContent();
		});

		$(document).on("click", ".remove-dashboard", function(event){

			event.preventDefault();
			event.stopPropagation();

			var dashboard_id = $(this).data("dashboard_id");

			$this.showConfirm("Are you sure you want to delete this dashboard?", "warning", function() {

				$this.activateFullLoading();

				$.post( "?quietmode=1&module=Dashboards&command=remove",
					{
						id: dashboard_id
					},
					function( data ) {
						if (data.status) {
							$(".dashboard-menu[data-id='" + dashboard_id + "']").remove();

							if($(".dashboard-menu").length > 0) {
								if(dashboard_id == $this.activeDashboard){
									$(".dashboard-menu").first().find("a").click();
								}
							}else {
								$(".gridster.ready").empty();
							}

						}else {
							$this.showAlert("Something went wrong removing the dashboard", "danger");
						}
						$this.deactivateFullLoading();
					}
				);
			});

		});
	},
	initAddWidgetsButtons: function(){
		var $this = this;
		$(".add-widget-button").click(function(){

			var current_dashboard_id = $this.activeDashboard;
			var widget_id = $(this).data('widget_id');
			var widget_module_name = $(this).data('widget_module_name');
			var widget_rawname = $(this).data('rawname');
			var widget_name = $(this).data('widget_name');
			var widget_has_settings = $(this).data('has_settings');

			var new_widget_id = current_dashboard_id + "-" + widget_id;

			var default_size_x = $(this).data('size_x');
			var default_size_y = $(this).data('size_y');

			//Checking if the widget is already on the dashboard
			var object_on_dashboard = $("li[data-id='"+new_widget_id+"']");

			if(object_on_dashboard.length <= 0){

				$this.activateFullLoading();

				$.post( "?quietmode=1&module=Dashboards&command=getwidgetcontent",
					{
						id: widget_id,
						rawname: widget_rawname
					},
					function( data ) {

						console.log(data);

						$("#add_widget").modal("hide");

						if(typeof data.html !== "undefined"){
							//So first we go the HTML content to add it to the widget
							var widget_html = data.html;
							var full_widget_html = $this.widget_layout(new_widget_id, widget_module_name, widget_name, widget_id, widget_rawname, widget_has_settings, widget_html);

							var gridster_object = $(".gridster > ul").gridster().data('gridster');
							//We are adding the widget always on the position 1,1
							gridster_object.add_widget(full_widget_html, default_size_x, default_size_y, 1, 1);
							UCP.callModuleByMethod(widget_rawname,"displayWidget",widget_id,$this.activeDashboard);
							$this.saveLayoutContent();
						}else {
							$this.showAlert("There was an error getting the widget information, try again later", "danger");
						}

						$this.deactivateFullLoading();

					}, "json");
			}else {
				$this.showAlert("You already have this widget on this dashboard", "info");
			}
		});

		$(".add-small-widget-button").click(function(){

			var widget_id = $(this).data('id');
			var widget_module_name = $(this).data('module_name');
			var widget_rawname = $(this).data('rawname');
			var widget_name = $(this).data('name');

			var widget_icon = $(this).data('icon');

			//Checking if the widget is already on the bar
			var object_on_bar = $("#side_bar_content li.custom-widget[data-widget_id='"+widget_id+"']");

			if(object_on_bar.length <= 0){

				$this.activateFullLoading();

				$.post( "?quietmode=1&module=Dashboards&command=getwidgetcontent",
					{
						id: widget_id,
						rawname: widget_rawname
					},
					function( data ) {

						$("#add_widget").modal("hide");

						if(typeof data.html !== "undefined"){

							//So first we go the HTML content to add it to the widget
							var widget_html = data.html;

							var full_widget_html = $this.smallWidgetLayout(widget_id, widget_module_name, widget_name, widget_id, widget_rawname, widget_icon, widget_html);

							var menu_widget_html = $this.smallWidgetMenuLayout(widget_id, widget_rawname);

							$("#side_bar_content .last-widget").before(full_widget_html);

							$(".side-menu-widgets-container").append(menu_widget_html);

							UCP.callModuleByMethod(widget_rawname,"displaySmallWidget",widget_id);

							$this.saveSidebarContent();
						}else {
							$this.showAlert("There was an error getting the widget information, try again later", "danger");
						}

						$this.deactivateFullLoading();

					}, "json");
			}else {
				$this.showAlert("You already have this widget on the side bar", "info");
			}
		});
	},
	initCategoriesWidgets: function(){
		$("div.bhoechie-tab-menu>div.list-group>a").click(function(e) {
			e.preventDefault();
			$(this).siblings('a.active').removeClass("active");
			$(this).addClass("active");
			var index = $(this).index();
			$("div.bhoechie-tab>div.bhoechie-tab-content").removeClass("active");
			$("div.bhoechie-tab>div.bhoechie-tab-content").eq(index).addClass("active");
		});
	},
	getWidgetContent: function(widget_content_object, widget_id, widget_rawname){
		var $this = this;
		this.activateWidgetLoading(widget_content_object);

		$.post( "?quietmode=1&module=Dashboards&command=getwidgetcontent",
			{
				id: widget_id,
				rawname: widget_rawname
			},
			function( data ) {

				var widget_html = data.html;

				if(typeof data.html === "undefined"){
					widget_html = '<div class="alert alert-danger">Something went wrong getting the content of the widget</div>';
				}

				widget_content_object.html(widget_html);
				UCP.callModuleByMethod(widget_rawname,"displayWidget",widget_id,$this.activeDashboard);

			}, "json");
	},
	getSettingsContent: function(widget_content_object, widget_id, widget_rawname){
		var $this = this;
		this.activateWidgetLoading(widget_content_object);

		$.post( "?quietmode=1&module=Dashboards&command=getwidgetsettingscontent",
			{
				id: widget_id,
				rawname: widget_rawname
			},
			function( data ) {

				var widget_html = data.html;

				if(typeof data.html === "undefined"){
					widget_html = '<div class="alert alert-danger">Something went wrong getting the settings from the widget</div>';
				}

				widget_content_object.html(widget_html);
				UCP.callModuleByMethod(widget_rawname,"displayWidgetSettings",widget_id,$this.activeDashboard);

			}, "json");
	},
	setupAddDashboard: function() {
		var $this = this;
		$("#create_dashboard").click(function() {
			if ($("#dashboard_name").length > 0) {
				if ($("#dashboard_name").val().trim() === "") {
					show_alert("You must put a dashboard name" , "danger", function(){ $("#add_dashboard").modal("show") });
					$("#add_dashboard").modal("hide");
				} else {
					var queryString = $("#add_dashboard_form").attr("action") + "&" + $("#add_dashboard_form").formSerialize();

					$this.activateFullLoading();

					$.post( "index.php?", queryString, function( data ) {
						if (!data.status) {
							$("#error-msg").html(data.message).fadeIn("fast");
						} else {
							var new_dashboard_html = '<li class="menu-order dashboard-menu" data-id="'+data.id+'"><a data-pjax href="?dashboard='+data.id+'">'+$("#dashboard_name").val()+' <div class="remove-dashboard" data-dashboard_id="'+data.id+'"><i class="fa fa-times" aria-hidden="true"></i></div></a></li>';
							$("#all_dashboards").append(new_dashboard_html);

							$("#add_dashboard").modal("hide");
						}
						$this.deactivateFullLoading();
					}, "json");
				}
			}
		});
	}

});
