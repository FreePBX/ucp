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
		});
	},
	pjaxEnd: function(event) {
		this.loadDashboard();
		this.deactivateFullLoading();
	},
	pjaxStart: function(event) {
		this.activateFullLoading();
	},
	resize: function() {
		var gridstack = $(".grid-stack").data('gridstack');
		if(typeof gridstack === "undefined") {
			return;
		}
		$(window).resize(function() {
			setTimeout(function() {
				if(window.innerWidth <= 768) {
					gridstack.resizable($(".grid-stack-item").not('[data-gs-no-resize]'),false);
					gridstack.enableMove(false);
				} else {
					gridstack.resizable($(".grid-stack-item").not('[data-gs-no-resize]'),true);
					gridstack.enableMove(true);
				}
			},100);
		});
	},
	loadDashboard: function() {
		var $this = this;
		if(!$(".grid-stack").length) {
			return;
		}

		$('#add_dashboard').on('shown.bs.modal', function () {
			$('#dashboard_name').focus();
		});

		$('#add_dashboard').on('hidden.bs.modal', function () {
			$('#dashboard_name').val("");
		});

		$(document).on("click", ".edit-widget", function(){
			var rawname = $(this).data("rawname");
			var widget_type_id = $(this).data("widget_type_id");
			var widget_id = $(this).parents(".grid-stack-item").data("id");

			$('#widget_settings').one('hidden.bs.modal', function (e) {
				$(".settings-shown-blocker").hide();
			});
			$('#widget_settings').attr("data-rawname",rawname);
			$('#widget_settings').data('rawname',rawname);
			var settings_container = $('#widget_settings .modal-body');
			var parent = $(this).parents(".grid-stack-item");
			var title = parent.data("widget_module_name");
			var name = parent.data("name");
			$this.activateSettingsLoading();
			$(".settings-shown-blocker").show();
			$("#widget_settings .modal-title").html('<i class="fa fa-cog" aria-hidden="true"></i> '+title+" "+_("Settings")+" ("+name+")");
			$('#widget_settings').modal('show');
			$this.getSettingsContent(settings_container, widget_type_id, rawname, function() {
				$("#widget_settings .modal-body .fa-question-circle").click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					var f = $(this).parents("label").attr("for");
					$(".help-block").addClass('help-hidden');
					$('.help-block[data-for="'+f+'"]').removeClass('help-hidden');
				});
				UCP.callModuleByMethod(rawname,"displayWidgetSettings",widget_id,$this.activeDashboard);
				$(document).trigger("post-body.widgetsettings",[ widget_id, widget_type_id, $this.activeDashboard ]);
			});
		});

		$('.grid-stack').gridstack({
			cellHeight: 35,
			verticalMargin: 10,
			animate: true,
			float: true,
			draggable: {
				handle: '.widget-title',
				scroll: false,
				appendTo: 'body'
			}
		});

		$('.grid-stack').on('resizestop', function(event, ui) {
			//Never on Desktop, Always on mobile
			UCP.callModulesByMethod("resize",$this.activeDashboard);
		});

		$('.grid-stack').on('removed', function(event, items) {
			//Never on Desktop, Always on mobile
			if(window.innerWidth <= 768) {
				$this.saveLayoutContent();
			}
		});

		$('.grid-stack').on('added', function(event, items) {
			//Never on Desktop, Always on mobile
			if(window.innerWidth <= 768) {
				$this.saveLayoutContent();
			}
		});

		$('.grid-stack').on('change', function(event, items) {
			//This triggers on any bubbling change so if items
			//is undefined then return
			if(typeof items === "undefined") {
				return;
			}
			//Always on Desktop, Never on mobile
			if(window.innerWidth > 768) {
				$this.saveLayoutContent();
			}

		});

		$this.resize();

		var gridstack = $(".grid-stack").data('gridstack');
		var total = gridstack.grid.nodes.length;
		var count = 0;
		$.each(gridstack.grid.nodes, function(i,v){
			var el = v.el;
			if(!el.hasClass("add-widget-widget")){
				var widget_id = $(el).data('id');
				var widget_type_id = $(el).data('widget_type_id');
				var widget_rawname = $(el).data('rawname');
				$this.getWidgetContent(widget_id, widget_type_id, widget_rawname, function() {
					count++;
					if(count == total) {
						$(document).trigger("post-body.widgets",[ $this.activeDashboard ]);
					}
				});
			}
		});

		//Are we looking a dashboard?
		var dashboard_id = $(".grid-stack").data("dashboard_id");
		this.activeDashboard = dashboard_id;

		$(".dashboard-menu").removeClass("active");

		$(".dashboard-menu[data-id='"+this.activeDashboard+"']").addClass("active");
		UCP.callModulesByMethod("showDashboard",this.activeDashboard);
	},
	saveLayoutContent: function() {
		this.activateFullLoading();
		var $this = this;

		var gridDataSerialized = lodash.map($('.grid-stack .grid-stack-item:visible').not(".grid-stack-placeholder"), function (el) {
			el = $(el);
			var node = el.data('_gridstack_node');
			return {
				id: el.data('id'),
				widget_module_name: el.data('widget_module_name'),
				name: el.data('name'),
				rawname: el.data('rawname'),
				widget_type_id: el.data('widget_type_id'),
				has_settings: el.data('has_settings'),
				size_x: node.x,
				size_y: node.y,
				col: node.width,
				row: node.height
			};
		});

		$.post( "?quietmode=1&module=Dashboards&command=savedashlayout",
			{
				id: $this.activeDashboard,
				data: JSON.stringify(gridDataSerialized)
			},
			function( data ) {
				if(data.status){
					console.log("saved grid");
				}else {
					$this.showAlert(_("Something went wrong saving the information (grid)"), "danger");
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
					$this.showAlert(_("Something went wrong saving the information (sidebar)"), "danger");
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
	activateSettingsLoading: function() {
		var loading_html = '<div class="settings-loading-box">' +
			'					<span class="fa-stack fa">' +
			'						<i class="fa fa-cloud fa-stack-2x text-internal-blue"></i>' +
			'						<i class="fa fa-cog fa-spin fa-stack-1x secundary-color"></i>' +
			'					</span>' +
			'				</div>';
		$("#widget_settings .modal-body").html(loading_html);
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
					'<div data-widget_module_name="'+widget_module_name+'" data-id="'+widget_id+'" data-name="'+widget_name+'" data-rawname="'+widget_rawname+'" data-widget_type_id="'+widget_type_id+'" data-has_settings="'+widget_has_settings+'" class="flip-container">' +
						'<div class="grid-stack-item-content flipper">' +
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
								'<div class="widget-content container">'+widget_content+'</div>' +
							'</div>' +
							'<div class="back">' +
								'<div class="widget-title settings-title">' +
									'<div class="widget-module-name truncate-text">'+_('Settings')+'</div>' +
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
					'</div>';

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
					'<button type="button" class="btn btn-xs btn-danger remove-small-widget" data-widget_id="'+widget_id+'" data-widget_rawname="'+widget_rawname+'">'+_('Remove Widget')+'</button>' +
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
			event.preventDefault();
			event.stopPropagation();

			//the widget is already open. close it
			if($this.widgetMenuOpen) {
				$this.closeExtraWidgetMenu();
				return;
			}

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

			$.post( "?quietmode=1&module=Dashboards&command=getsimplewidgetcontent",
				{
					id: clicked_id,
					rawname: clicked_module
				},
				function( data ) {

					if(typeof data.html !== "undefined"){

						content_object.html(data.html);
						UCP.callModuleByMethod(clicked_module,"displaySimpleWidget",clicked_id);
						$(document).trigger("post-body.simplewidget",[ $this.activeDashboard ]);
					}else {
						$this.showAlert(_("There was an error getting the widget information, try again later"), "danger");
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

			$this.showConfirm(_("Are you sure you want to delete this widget?"), "warning", function() {
				//TODO
				var grid = $('.grid-stack').data('gridstack');
				//We are adding the widget always on the position 1,1
				grid.removeWidget($(".grid-stack-item[data-id='" + widget_id + "']"));
				UCP.callModuleByMethod(widget_rawname,"deleteWidget",widget_type_id,$this.activeDashboard);
			});

		});

		$(document).on("click", ".remove-small-widget", function(event){

			var widget_to_remove = $(this).data("widget_id");
			var widget_rawname = $(this).data("widget_rawname");

			console.log(widget_rawname);

			var sidebar_object_to_remove = $("#side_bar_content li.custom-widget[data-widget_id='" + widget_to_remove + "']");

			UCP.callModuleByMethod(widget_rawname,"deleteSimpleWidget",widget_to_remove);

			sidebar_object_to_remove.remove();

			$this.closeExtraWidgetMenu();

			$this.saveSidebarContent();
		});

		$(document).on("click", ".remove-dashboard", function(event){

			event.preventDefault();
			event.stopPropagation();

			var dashboard_id = $(this).data("dashboard_id");

			$this.showConfirm(_("Are you sure you want to delete this dashboard?"), "warning", function() {

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
								var grid = $('.grid-stack').data('gridstack');
								grid.destroy();
								$('.grid-stack').empty();
							}

						}else {
							$this.showAlert(_("Something went wrong removing the dashboard"), "danger");
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

			var new_widget_id = current_dashboard_id + "-" + widget_rawname + "-" + widget_id;

			var default_size_x = $(this).data('size_x');
			var default_size_y = $(this).data('size_y');
			var min_size_x = $(this).data('min_x');
			var min_size_y = $(this).data('min_y');
			var max_size_x = $(this).data('max_x');
			var max_size_y = $(this).data('max_y');
			var icon = $(this).data('icon');
			var no_resize = $(this).data('no_resize');
			no_resize = (typeof no_resize !== "undefined") ? no_resize : false;
			var resizable = !no_resize;

			//Checking if the widget is already on the dashboard
			var object_on_dashboard = $("div[data-id='"+new_widget_id+"']");

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
							//TODO
							var grid = $('.grid-stack').data('gridstack');
							//We are adding the widget always on the position 1,1
							grid.addWidget($(full_widget_html), 1, 1, default_size_x, default_size_y, true, min_size_x, max_size_x, min_size_y, max_size_y);
							grid.resizable($("div[data-id='"+new_widget_id+"']"), resizable);
							UCP.callModuleByMethod(widget_rawname,"displayWidget",widget_id,$this.activeDashboard);
							$(document).trigger("post-body.widgets",[ $this.activeDashboard ]);
						}else {
							$this.showAlert(_("There was an error getting the widget information, try again later"), "danger");
						}

						$this.deactivateFullLoading();

					}, "json");
			}else {
				$this.showAlert(_("You already have this widget on this dashboard"), "info");
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

				$.post( "?quietmode=1&module=Dashboards&command=getsimplewidgetcontent",
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

							$(document).trigger("post-body.simplewidget",[ $this.activeDashboard ]);

							UCP.callModuleByMethod(widget_rawname,"displaySimpleWidget",widget_id);

							$this.saveSidebarContent();
						}else {
							$this.showAlert(_("There was an error getting the widget information, try again later"), "danger");
						}

						$this.deactivateFullLoading();

					}, "json");
			}else {
				$this.showAlert(_("You already have this widget on the side bar"), "info");
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
	getWidgetContent: function(widget_id, widget_type_id, widget_rawname, callback){
		var $this = this,
				widget_content_object = $(".grid-stack-item[data-id='"+widget_id+"'] .widget-content");
		this.activateWidgetLoading(widget_content_object);

		$.post( "?quietmode=1&module=Dashboards&command=getwidgetcontent",
			{
				id: widget_type_id,
				rawname: widget_rawname
			},
			function( data ) {

				var widget_html = data.html;

				if(typeof data.html === "undefined"){
					widget_html = '<div class="alert alert-danger">'+_('Something went wrong getting the content of the widget')+'</div>';
				}

				widget_content_object.html(widget_html);
				UCP.callModuleByMethod(widget_rawname,"displayWidget",widget_id,$this.activeDashboard);
				UCP.callModuleByMethod(widget_rawname,"resize",$this.activeDashboard);

				if(typeof callback === "function") {
					callback(data);
				}
			}, "json");
	},
	getSettingsContent: function(widget_content_object, widget_id, widget_rawname, callback){
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
					widget_html = '<div class="alert alert-danger">'+_('Something went wrong getting the settings from the widget')+'</div>';
				}

				widget_content_object.html(widget_html);
				if(typeof callback === "function") {
					callback();
				}

			}, "json");
	},
	setupAddDashboard: function() {
		var $this = this;
		$("#create_dashboard").click(function() {
			if ($("#dashboard_name").length > 0) {
				if ($("#dashboard_name").val().trim() === "") {
					show_alert(_("You must have a dashboard name") , "danger", function(){ $("#add_dashboard").modal("show"); });
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
