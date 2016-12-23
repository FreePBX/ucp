var WidgetsC = Class.extend({
	init: function() {
		this.activeDashboard = null;
		this.widgetMenuOpen = false;
	},
	ready: function() {
		this.setupAddDashboard();
		this.loadDashboard();
		this.initMenuDragabble();
		this.initDashboardDragabble();
		this.initCategoriesWidgets();
		this.initAddWidgetsButtons();
		this.initRemoveItemButtons();
		this.initLockItemButtons();
		this.initLeftNavBarMenus();
		this.deactivateFullLoading();
		var $this = this;
		var total = $(".custom-widget").length;
		var count = 0;
		$(".custom-widget").each(function() {
			var widget_rawname = $(this).data("widget_rawname");
			var widget_id = $(this).data("widget_id");
			UCP.callModuleByMethod(widget_rawname,"addSimpleWidget",widget_id);
			count++;
			if(total == count) {
				//$(document).trigger("post-body.addsimplewidget",[ widget_id, $this.activeDashboard ]);
			}
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
		if(!$(".grid-stack").length) {
			return;
		}

		$('#add_dashboard').on('shown.bs.modal', function () {
			$('#dashboard_name').focus();
			$("#add_dashboard").off("keydown");
			$("#add_dashboard").on('keydown', function(event) {
				switch(event.keyCode) {
					case 13:
						$("#create_dashboard").click();
					break;
				}
			});
		});

		$('#add_dashboard').on('hidden.bs.modal', function () {
			$('#dashboard_name').val("");
		});

		$('#edit_dashboard').on('shown.bs.modal', function () {
			$('#edit_dashboard_name').focus();
		});

		$('#edit_dashboard').on('hidden.bs.modal', function () {
			$('#edit_dashboard_name').val("");
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
			//Never on mobile, Always on Desktop
			UCP.callModulesByMethod("resize",ui.element.data("id"),$this.activeDashboard);
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

		var gridstack = $(".grid-stack").data('gridstack');

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
		var $this = this,
				grid = $('.grid-stack').data('gridstack');

		var gridDataSerialized = lodash.map($('.grid-stack .grid-stack-item:visible').not(".grid-stack-placeholder"), function (el) {
			el = $(el);
			var node = el.data('_gridstack_node'),
					locked = el.find(".lock-widget i").hasClass("fa-lock");
			grid.movable(el, !locked); //some gitchy crap going on here, we have to relock the widget
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
				row: node.height,
				locked: locked
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
	saveSidebarContent: function(callback) {
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
				if(typeof callback === "function") {
					callback();
				}
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
	widget_layout: function(widget_id, widget_module_name, widget_name, widget_type_id, widget_rawname, widget_has_settings, widget_content, resizable){

		var settings_html = '';
		if(widget_has_settings == "1"){
			settings_html = '<div class="widget-option edit-widget" data-rawname="'+widget_rawname+'" data-widget_type_id="'+widget_type_id+'">' +
								'<i class="fa fa-cog" aria-hidden="true"></i>' +
							'</div>';
		}
		var rs_html = '';
		if(!resizable) {
			rs_html = 'data-no-resize="true"';
		}

		var html = '' +
					'<div data-widget_module_name="'+widget_module_name+'" data-id="'+widget_id+'" data-name="'+widget_name+'" data-rawname="'+widget_rawname+'" data-widget_type_id="'+widget_type_id+'" data-has_settings="'+widget_has_settings+'" class="flip-container" '+rs_html+'>' +
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
										'<div class="widget-option lock-widget" data-widget_id="'+widget_id+'" data-widget_type_id="'+widget_type_id+'" data-widget_rawname="'+widget_rawname+'">' +
											'<i class="fa fa-unlock-alt" aria-hidden="true"></i>' +
										'</div>' +
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
			'<li class="custom-widget" data-widget_id="'+widget_id+'" data-widget_rawname="'+widget_rawname+'">' +
				'<a href="#" data-module_name="'+widget_module_name+'" data-id="'+widget_id+'" data-name="'+widget_name+'" data-rawname="'+widget_rawname+'" data-type_id="'+widget_type_id+'" data-icon="' + widget_icon + '"><i class="' + widget_icon + '" aria-hidden="true"></i></a>' +
			'</li>';

		return html;
	},
	smallWidgetMenuLayout: function(widget_id, widget_rawname, name, widget_name, widget_icon, hasSettings){
		var settings_html = '';
		if(hasSettings) {
			settings_html = '<i class="fa fa-cog show-simple-widget-settings" aria-hidden="true"></i>';
		}
		var html = '' +
			'<div class="widget-extra-menu" id="menu_'+widget_rawname+'_'+widget_id+'" data-id="menu_'+widget_rawname+'_'+widget_id+'" data-widget_type_id="'+widget_id+'" data-module="'+widget_rawname+'" data-name="'+name+'" data-widget_name="'+widget_name+'" data-icon="'+widget_icon+'">' +
				'<div class="menu-actions">' +
					'<i class="fa fa-times-circle-o close-simple-widget-menu" aria-hidden="true"></i>' +
					settings_html +
				'</div>' +
				'<h5 class="small-widget-title"><i class="fa"></i> <span></span> <small></small></h5>' +
				'<div class="small-widget-content">' +
				'</div>' +
				'<button type="button" class="btn btn-xs btn-danger remove-small-widget" data-widget_id="'+widget_id+'" data-widget_rawname="'+widget_rawname+'">'+_('Remove Widget')+'</button>' +
			'</div>';

		return html;
	},
	initMenuDragabble: function(){
		var $this = this;
		var el = document.getElementById('side_bar_content');
		var sortable = Sortable.create(el, {
			draggable: ".custom-widget",
			filter: "i",
			onUpdate: function (evt) {
				sortable.option("disabled",true);
				$this.saveSidebarContent(function() {
					sortable.option("disabled",false);
				});
			},
		});
	},
	initDashboardDragabble: function() {
		var $this = this;
		var el = document.getElementById('all_dashboards');
		var sortable = Sortable.create(el, {
			draggable: ".dashboard-menu",
			onUpdate: function (evt) {
				sortable.option("disabled",true);
				$this.saveDashboardOrder(function() {
					sortable.option("disabled",false);
				});
			},
		});
	},
	saveDashboardOrder: function(callback) {
		var dashboards = [],
				$this = this;
		$this.activateFullLoading();
		$("#all_dashboards li").each(function() {
			dashboards.push($(this).data("id"));
		});
		$.post( "?quietmode=1&module=Dashboards&command=reorder",
			{
				order: dashboards
			},
			function( data ) {
				$this.deactivateFullLoading();
				if(typeof callback === "function") {
					callback();
				}
			}, "json");
	},
	openExtraWidgetMenu: function(callback) {
		var previous = this.widgetMenuOpen;
		this.widgetMenuOpen = true;
		if(previous) {
			if(typeof callback === "function") {
				callback();
			}
			return;
		}
		$(".side-menu-widgets-container").one("transitionend",function() {
			if(typeof callback === "function") {
				callback();
			}
		});
		$(".side-menu-widgets-container").css({ width: "250px", left: "55px"});
	},
	closeExtraWidgetMenu: function(callback) {
		var previous = this.widgetMenuOpen;
		this.widgetMenuOpen = false;
		if(!previous) {
			$("#side_bar_content li.active").removeClass("active");
			if(typeof callback === "function") {
				callback();
			}
			return;
		}
		$(".side-menu-widgets-container").one("transitionend",function() {
			$(".widget-extra-menu:visible").addClass("hidden");
			$("#side_bar_content li.active").removeClass("active");
			if(typeof callback === "function") {
				callback();
			}
		});
		$(".side-menu-widgets-container").css({ width: "0", left: "45px"});
	},
	initLeftNavBarMenus: function(){
		var $this = this;

		$(document).on("click", ".close-simple-widget-menu", function() {
			$this.closeExtraWidgetMenu();
		});

		$(document).on("click", ".show-simple-widget-settings", function() {
			var parent = $(this).parents(".widget-extra-menu"),
					rawname = parent.data("module"),
					widget_type_id = parent.data("widget_type_id"),
					widget_id = parent.data("id");

			$('#widget_settings').one('hidden.bs.modal', function (e) {
				$(".settings-shown-blocker").hide();
			});

			$('#widget_settings').attr("data-rawname",rawname);
			$('#widget_settings').data('rawname',rawname);

			var settings_container = $('#widget_settings .modal-body');
			var title = parent.data("name");
			var name = parent.data("widget_name");

			$this.activateSettingsLoading();
			$(".settings-shown-blocker").show();
			$("#widget_settings .modal-title").html('<i class="fa fa-cog" aria-hidden="true"></i> '+title+" "+_("Settings")+" ("+name+")");
			$('#widget_settings').modal('show');
			$this.getSimpleSettingsContent(settings_container, widget_type_id, rawname, function() {
				$("#widget_settings .modal-body .fa-question-circle").click(function(e) {
					e.preventDefault();
					e.stopPropagation();
					var f = $(this).parents("label").attr("for");
					$(".help-block").addClass('help-hidden');
					$('.help-block[data-for="'+f+'"]').removeClass('help-hidden');
				});
				UCP.callModuleByMethod(rawname,"displaySimpleWidgetSettings",widget_id);
				$(document).trigger("post-body.simplewidgetsettings",[ widget_id, widget_type_id ]);
			});
		});

		$(document).on("click", ".custom-widget i", function(event){
			event.preventDefault();
			event.stopPropagation();

			var widget = $(this).parents(".custom-widget");

			//We are already looking at it so do nothing
			if(widget.hasClass("active")) {
				return;
			}

			var clicked_module = widget.find("a").data("rawname");
			var clicked_id = widget.find("a").data("id");
			var widget_id = clicked_module + "_" + clicked_id;

			$("#side_bar_content li.active").removeClass("active");
			widget.addClass("active");

			$(".widget-extra-menu:visible").addClass("hidden");
			var content_object = $("#menu_"+widget_id).find(".small-widget-content");
			$("#menu_"+widget_id).find(".small-widget-title i").removeClass().addClass($("#menu_"+widget_id).data("icon"));
			$("#menu_"+widget_id).find(".small-widget-title span").text($("#menu_"+widget_id).data("name"));
			if($("#menu_"+widget_id).data("name") != $("#menu_"+widget_id).data("widget_name")) {
				$("#menu_"+widget_id).find(".small-widget-title small").text("("+$("#menu_"+widget_id).data("widget_name")+")");
			} else {
				$("#menu_"+widget_id).find(".small-widget-title small").text("");
			}
			$this.activateWidgetLoading(content_object);
			$("#menu_"+widget_id).removeClass("hidden");
			$this.openExtraWidgetMenu();

			$.post( "?quietmode=1&module=Dashboards&command=getsimplewidgetcontent",
				{
					id: clicked_id,
					rawname: clicked_module
				},
				function( data ) {
					if(typeof data.html !== "undefined"){
						content_object.html(data.html);

						UCP.callModuleByMethod(clicked_module,"displaySimpleWidget",clicked_id);
						$(document).trigger("post-body.simplewidget",[ clicked_id, $this.activeDashboard ]);
					}else {
						$this.showAlert(_("There was an error getting the widget information, try again later"), "danger");
					}
				}, "json");
		});
	},
	initLockItemButtons: function(){
		var $this = this;
		$(document).on("click", ".lock-widget", function(event){
			event.preventDefault();
			event.stopPropagation();
			if(window.innerWidth <= 768) {
				alert(_("Widgets can not be locked"));
				return;
			}
			var locked = $(this).find("i").hasClass("fa-lock"),
				id = $(this).data("widget_id"),
				grid = $('.grid-stack').data('gridstack');
			if(locked) {
				$(this).find("i").removeClass().addClass("fa fa-unlock-alt");
			} else {
				$(this).find("i").removeClass().addClass("fa fa-lock");
			}
			if($(".grid-stack-item[data-id="+id+"]").data("no-resize") != "true") {
				grid.resizable($(".grid-stack-item[data-id="+id+"]"), locked);
			}

			grid.movable($(".grid-stack-item[data-id="+id+"]"), locked);
			grid.locked($(".grid-stack-item[data-id="+id+"]"), !locked);

			$this.saveLayoutContent();
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

		$(document).on("click", ".lock-dashboard", function(event){

			event.preventDefault();
			event.stopPropagation();

		});

		$(document).on("click", ".edit-dashboard", function(event){

			event.preventDefault();
			event.stopPropagation();

			var parent = $(this).parents('.dashboard-menu');
			var dashboard_id = parent.data("id");
			var title = parent.find("a");

			$('#edit_dashboard_name').val(title.text());

			$('#edit_dashboard').one('shown.bs.modal', function () {
				$("#edit_dashboard_btn").off("click");
				$("#edit_dashboard").off("keydown");
				$("#edit_dashboard").on('keydown', function(event) {
					switch(event.keyCode) {
						case 13:
							$("#edit_dashboard_btn").click();
						break;
					}
				});
				$("#edit_dashboard_btn").one("click",function() {
					var name = $('#edit_dashboard_name').val();
					title.text(name);
					$("#edit_dashboard").modal('hide');
				});
				$('#dashboard_name').focus();
			});

			$("#edit_dashboard").modal('show');
		});

		$(document).on("click", ".remove-dashboard", function(event){

			event.preventDefault();
			event.stopPropagation();

			var dashboard_id = $(this).parents('.dashboard-actions').data("dashboard_id");

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
		$("#add_widget").on("show.bs.modal",function() {
			$this.closeExtraWidgetMenu();
			$(".navbar-nav .add-widget").addClass("active");
		});
		$("#add_widget").on("hidden.bs.modal",function() {
			$(".navbar-nav .add-widget").removeClass("active");
		});
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
			default_size_x = (typeof default_size_x === "undefined" || Number(default_size_x) === 0) ? 2 : default_size_x;
			var default_size_y = $(this).data('size_y');
			default_size_y = (typeof default_size_y === "undefined" || Number(default_size_y) === 0) ? 2 : default_size_y;
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

						$("#add_widget").modal("hide");

						if(typeof data.html !== "undefined"){
							//So first we go the HTML content to add it to the widget
							var widget_html = data.html;
							var full_widget_html = $this.widget_layout(new_widget_id, widget_module_name, widget_name, widget_id, widget_rawname, widget_has_settings, widget_html, resizable);
							//TODO
							var grid = $('.grid-stack').data('gridstack');
							//We are adding the widget always on the position 1,1
							grid.addWidget($(full_widget_html), 1, 1, default_size_x, default_size_y, true, min_size_x, max_size_x, min_size_y, max_size_y);
							grid.resizable($("div[data-id='"+new_widget_id+"']"), resizable);
							UCP.callModuleByMethod(widget_rawname,"displayWidget",new_widget_id,$this.activeDashboard);
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
			var widget_type_id = $(this).data('widget_type_id');
			var hasSettings = $(this).data('widget_settings');
			hasSettings = (hasSettings == "true") ? true : false;

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

							var menu_widget_html = $this.smallWidgetMenuLayout(widget_id, widget_rawname, widget_name, widget_type_id, widget_icon, hasSettings);

							$("#side_bar_content .custom-widget").last().after(full_widget_html);

							$(".side-menu-widgets-container").append(menu_widget_html);

							//$(document).trigger("post-body.addsimplewidget",[ widget_id, $this.activeDashboard ]);

							UCP.callModuleByMethod(widget_rawname,"addSimpleWidget",widget_id);

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
				UCP.callModuleByMethod(widget_rawname,"resize",widget_id,$this.activeDashboard);

				if(typeof callback === "function") {
					callback(data);
				}
			}, "json");
	},
	getSimpleSettingsContent: function(widget_content_object, widget_id, widget_rawname, callback){
		var $this = this;

		$.post( "?quietmode=1&module=Dashboards&command=getsimplewidgetsettingscontent",
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
	getSettingsContent: function(widget_content_object, widget_id, widget_rawname, callback){
		var $this = this;

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
					alert(_("You must have a dashboard name"));
					$("#add_dashboard").modal("hide");
				} else {
					$this.activateFullLoading();

					$.post( "index.php?", {quietmode:1, module: "Dashboards", command: "add", name: $("#dashboard_name").val()}, function( data ) {
						if (!data.status) {
							$("#error-msg").html(data.message).fadeIn("fast");
						} else {
							var new_dashboard_html = '<li class="menu-order dashboard-menu" data-id="'+data.id+'"><a data-pjax href="?dashboard='+data.id+'">'+$("#dashboard_name").val()+'</a> <div class="dashboard-actions" data-dashboard_id="'+data.id+'"><i class="fa fa-unlock-alt lock-dashboard" aria-hidden="true"></i><i class="fa fa-pencil edit-dashboard" aria-hidden="true"></i><i class="fa fa-times remove-dashboard" aria-hidden="true"></i></div></li>';
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
