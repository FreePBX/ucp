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
		var resave = false;
		$(".custom-widget").each(function() {
			var widget_rawname = $(this).data("widget_rawname");
			var widget_id = $(this).data("id");
			UCP.callModuleByMethod(widget_rawname,"addSimpleWidget",widget_id);
			$(document).trigger("post-body.addsimplewidget",[ widget_id, $this.activeDashboard ]);
			if(typeof $(this).find("a").data("regenuuid") !== "undefined" && $(this).find("a").data("regenuuid")) {
				resave = true;
			}
			count++;
			if(total == count) {
				if(resave) {
					$this.saveSidebarContent();
				}
			}
		});
		window.onpopstate = function(event) {
			if(typeof event.state !== "undefined" && event.state !== null && typeof event.state.activeDashboard !== "undefined") {
				var el = $("#all_dashboards .dashboard-menu[data-id="+event.state.activeDashboard+"] a");
				//set popstate event to true so we dont destroy history
				el.data("popstate",true);
				el.click();
			}
		};
		var title = $("#all_dashboards .dashboard-menu.active a").text();
		//set tab title
		if(title !== "") {
			$("title").text(_("User Control Panel") + " - " + title);
		}
	},
	loadDashboard: function() {
		var $this = this;

		$("#dashboard-content .dashboard-error.no-dash").click(function() {
			$("#add_new_dashboard").click();
		});

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
			var settings_container = $('#widget_settings .modal-body'),
					parent = $(this).parents(".grid-stack-item"),
					rawname = parent.data("rawname"),
					widget_type_id = parent.data("widget_type_id"),
					widget_id = parent.data("id"),
					title = parent.data("widget_module_name"),
					name = parent.data("name");

			$('#widget_settings').attr("data-rawname",rawname);
			$('#widget_settings').data('rawname',rawname);

			$('#widget_settings').attr("data-id",widget_id);
			$('#widget_settings').data('id',widget_id);

			$('#widget_settings').attr("data-widget_type_id",widget_type_id);
			$('#widget_settings').data('widget_type_id',widget_type_id);

			$this.activateSettingsLoading();
			$("#widget_settings .modal-title").html('<i class="fa fa-cog" aria-hidden="true"></i> '+title+" "+_("Settings")+" ("+name+")");
			$('#widget_settings').modal('show');
			$('#widget_settings').one('shown.bs.modal', function() {
				$this.getSettingsContent(settings_container, widget_id, widget_type_id, rawname, function() {
					$("#widget_settings .modal-body .fa-question-circle").click(function(e) {
						e.preventDefault();
						e.stopPropagation();
						var f = $(this).parents("label").attr("for");
						$(".help-block").addClass('help-hidden');
						$('.help-block[data-for="'+f+'"]').removeClass('help-hidden');
					});
					$(document).trigger("post-body.widgetsettings",[ widget_id, $this.activeDashboard ]);
				});
			});
		});

		$(window).resize(function() {
			var gridstack = $(".grid-stack").data('gridstack');
			if(typeof gridstack === "undefined") {
				return;
			}
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

		if(!$(".grid-stack").length) {
			this.activeDashboard = null;
		} else {
			var dashboard_id = $(".grid-stack").data("dashboard_id");
			//Are we looking a dashboard?
			this.activeDashboard = dashboard_id;

			$this.setupGridStack();
			$this.bindGridChanges();

			var gridstack = $(".grid-stack").data('gridstack');
			var total = gridstack.grid.nodes.length;
			var count = 0;
			var resave = false;
			$.each(gridstack.grid.nodes, function(i,v){
				var el = v.el;
				if(!el.hasClass("add-widget-widget")){
					var widget_id = $(el).data('id');
					var widget_type_id = $(el).data('widget_type_id');
					var widget_rawname = $(el).data('rawname');
					if(typeof $(el).data("regenuuid") !== "undefined" && $(el).data("regenuuid")) {
						resave = true;
					}
					$this.getWidgetContent(widget_id, widget_type_id, widget_rawname, function() {
						count++;
						if(count == total) {
							$(document).trigger("post-body.widgets",[ null, $this.activeDashboard ]);
							if(resave) {
								$this.saveLayoutContent();
							}
						}
					});
				}
			});

			$(".dashboard-menu").removeClass("active");

			$(".dashboard-menu[data-id='"+this.activeDashboard+"']").addClass("active");
			UCP.callModulesByMethod("showDashboard",this.activeDashboard);
		}
	},
	/**
	 * Save Dashboard Layout State
	 * @method saveLayoutContent
	 */
	saveLayoutContent: function() {
		this.activateFullLoading();

		var $this = this,
				grid = $('.grid-stack').data('gridstack');

		//TODO: lodash :-|
		var gridDataSerialized = lodash.map($('.grid-stack .grid-stack-item:visible').not(".grid-stack-placeholder"), function (el) {
			el = $(el);
			var node = el.data('_gridstack_node'),
					locked = el.find(".lock-widget i").hasClass("fa-lock");

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
				locked: locked,
				uuid: el.data('uuid')
			};
		});

		dashboards[$this.activeDashboard] = gridDataSerialized;

		$.post( UCP.ajaxUrl,
			{
				module: "Dashboards",
				command: "savedashlayout",
				id: $this.activeDashboard,
				data: JSON.stringify(gridDataSerialized)
			},
			function( data ) {
				if(data.status){
					console.log("saved grid");
				}else {
					UCP.showAlert(_("Something went wrong saving the information (grid)"), "danger");
				}
		}).always(function() {
			$this.deactivateFullLoading();
		}).fail(function(jqXHR, textStatus, errorThrown) {
			UCP.showAlert(textStatus,'warning');
		});
	},
	saveSidebarContent: function(callback) {
		this.activateFullLoading();

		var $this = this,
				sidebar_objects = $("#side_bar_content li.custom-widget a"),
				all_content = [];

		sidebar_objects.each(function(){

			var widget_id = $(this).data('id'),
					widget_type_id = $(this).data('widget_type_id'),
					widget_module_name = $(this).data('module_name'),
					widget_rawname = $(this).data('rawname'),
					widget_name = $(this).data('name'),
					widget_icon = $(this).data('icon'),
					small_widget = {
						id:widget_id,
						widget_type_id: widget_type_id,
						module_name: widget_module_name,
						rawname: widget_rawname,
						name: widget_name,
						icon: widget_icon
					};

			all_content.push(small_widget);

		});

		var gridDataSerialized = JSON.stringify(all_content);

		$.post( UCP.ajaxUrl ,
			{
				module: "Dashboards",
				command: "savesimplelayout",
				data: gridDataSerialized
			},
			function( data ) {
				if(data.status){
					console.log("sidebar saved");
				}else {
					UCP.showAlert(_("Something went wrong saving the information (sidebar)"), "danger");
				}
				if(typeof callback === "function") {
					callback();
				}
		}).always(function() {
			$this.deactivateFullLoading();
		}).fail(function(jqXHR, textStatus, errorThrown) {
			UCP.showAlert(textStatus,'warning');
		});
	},
	/**
	 * Show the full screen loading
	 * @method activateFullLoading
	 */
	activateFullLoading: function(){
		$(".main-block").removeClass("hidden");
		NProgress.start();
	},
	/**
	 * Hide the full screen loading
	 * @method deactivateFullLoading
	 */
	deactivateFullLoading: function(){
		$(".main-block").addClass("hidden");
		NProgress.done();
	},
	/**
	 * Show the widget loading screen
	 * @method activateWidgetLoading
	 * @param  {object}              widget_object jQuery object of the widget content
	 * @return {string}                            Returns the html if no object provided
	 */
	activateWidgetLoading: function(widget_object){

		var loading_html = '<div class="widget-loading-box">' +
			'					<span class="fa-stack fa">' +
			'						<i class="fa fa-cloud fa-stack-2x text-internal-blue"></i>' +
			'						<i class="fa fa-cog fa-spin fa-stack-1x secundary-color"></i>' +
			'					</span>' +
			'				</div>';

		if(typeof widget_object !== "undefined") {
			widget_object.html(loading_html);
		} else {
			return loading_html;
		}
	},
	/**
	 * Show the settings loading screen
	 * @method activateSettingsLoading
	 */
	activateSettingsLoading: function() {
		var loading_html = '<div class="settings-loading-box">' +
			'					<span class="fa-stack fa">' +
			'						<i class="fa fa-cloud fa-stack-2x text-internal-blue"></i>' +
			'						<i class="fa fa-cog fa-spin fa-stack-1x secundary-color"></i>' +
			'					</span>' +
			'				</div>';
		$("#widget_settings .modal-body").html(loading_html);
	},
	/**
	 * Generate Widget Layout
	 * @method widget_layout
	 * @param  {string}      widget_id           The widget ID
	 * @param  {string}      widget_module_name  The widget module name
	 * @param  {string}      widget_name         The widget name
	 * @param  {string}      widget_type_id      The widget sub ID
	 * @param  {string}      widget_rawname      The widget rawname
	 * @param  {Boolean}     widget_has_settings If the widget has settings or not
	 * @param  {string}      widget_content      The widget content
	 * @param  {Boolean}      resizable           is resizable
	 * @param  {Boolean}      locked              is locked
	 * @return {string}                          The finalized html
	 */
	widget_layout: function(widget_id, widget_module_name, widget_name, widget_type_id, widget_rawname, widget_has_settings, widget_content, resizable, locked){
		var cased = widget_rawname.modularize(),
				icon = allWidgets[cased].icon,
				lockIcon = locked ? 'fa-lock' : 'fa-unlock-alt',
				settings_html = '';

		//TODO: boolean is checking by string reference??
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
									'<div class="widget-module-name truncate-text"><i class="fa-fw '+icon+'"></i>' + widget_name + '</div>' +
									'<div class="widget-module-subname truncate-text">('+widget_module_name+')</div>' +
									'<div class="widget-options">' +
										'<div class="widget-option remove-widget" data-widget_id="'+widget_id+'" data-widget_type_id="'+widget_type_id+'" data-widget_rawname="'+widget_rawname+'">' +
											'<i class="fa fa-times" aria-hidden="true"></i>' +
										'</div>' +
										settings_html +
										'<div class="widget-option lock-widget" data-widget_id="'+widget_id+'" data-widget_type_id="'+widget_type_id+'" data-widget_rawname="'+widget_rawname+'">' +
											'<i class="fa '+lockIcon+'" aria-hidden="true"></i>' +
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
	/**
	 * Generate Side Bar Icon Layout
	 * @method smallWidgetLayout
	 * @param  {string}          widget_id          The widget ID
	 * @param  {string}          widget_rawname     The widget rawname
	 * @param  {string}          widget_name        The widget name
	 * @param  {string}          widget_type_id     The widget sub id
	 * @param  {string}          widget_icon        The widget icon class
	 * @return {string}                             The finalized HTML
	 */
	smallWidgetLayout: function(widget_id, widget_rawname, widget_name, widget_type_id, widget_icon){
		var html = '' +
			'<li class="custom-widget" data-widget_id="'+widget_id+'" data-widget_rawname="'+widget_rawname+'" data-widget_type_id="'+widget_type_id+'">' +
				'<a href="#" data-id="'+widget_id+'" data-name="'+widget_name+'" data-rawname="'+widget_rawname+'" data-widget_type_id="'+widget_type_id+'" data-icon="' + widget_icon + '"><i class="' + widget_icon + '" aria-hidden="true"></i></a>' +
			'</li>';

		return html;
	},
	/**
	 * Small Widget Menu Layout
	 * @method smallWidgetMenuLayout
	 * @param  {string}              widget_id      The Widget ID
	 * @param  {string}              widget_rawname The widget rawname
	 * @param  {string}              widget_name    The widget name
	 * @param  {string}              widget_type_id The widget name
	 * @param  {string}              widget_icon    The widget icon class
	 * @param  {string}              widget_sub     The widget sub name
	 * @param  {Boolean}             hasSettings    If the settings COG should be generated
	 * @return {string}                             The finalized HTML
	 */
	smallWidgetMenuLayout: function(widget_id, widget_rawname, widget_name, widget_type_id, widget_icon, widget_sub, hasSettings){
		var settings_html = '';
		if(hasSettings) {
			settings_html = '<i class="fa fa-cog show-simple-widget-settings" aria-hidden="true"></i>';
		}

		var html = '' +
			'<div class="widget-extra-menu" id="menu_'+widget_id+'" data-id="'+widget_id+'" data-widget_type_id="'+widget_type_id+'" data-module="'+widget_rawname+'" data-name="'+widget_name+'" data-widget_name="'+widget_type_id+'" data-icon="'+widget_icon+'">' +
				'<div class="menu-actions">' +
					'<i class="fa fa-times-circle-o close-simple-widget-menu" aria-hidden="true"></i>' +
					settings_html +
				'</div>' +
				'<h5 class="small-widget-title"><i class="fa '+widget_icon+'"></i> <span>'+widget_sub+'</span> <small>('+widget_name+')</small></h5>' +
				'<div class="small-widget-content">' +
				'</div>' +
				'<button type="button" class="btn btn-xs btn-danger remove-small-widget" data-widget_id="'+widget_id+'" data-widget_rawname="'+widget_rawname+'">'+_('Remove Widget')+'</button>' +
			'</div>';

		return html;
	},
	/**
	 * Show dashboard error
	 * @method showDashboardError
	 * @param  {string}           message The message to show
	 */
	showDashboardError: function(message) {
		//TODO: should we destroy the gird if it exists?
		$("#dashboard-content .module-page-widgets").html('<div class="dashboard-error"><div class="message"><i class="fa fa-exclamation-circle" aria-hidden="true"></i><br/>'+message+'</div></div>');
	},
	/**
	 * Initalize Menu Dragging
	 * @method initMenuDragabble
	 */
	initMenuDragabble: function(){
		var $this = this,
				el = document.getElementById('side_bar_content');

		var sortable = Sortable.create(el, {
			draggable: ".custom-widget",
			filter: "i",
			onUpdate: function (evt) {
				sortable.option("disabled",true);
				$this.saveSidebarContent(function() {
					sortable.option("disabled",false);
				});
			}
		});
	},
	/**
	 * Initalize Dashboard Tab Dragging
	 * @method initDashboardDragabble
	 */
	initDashboardDragabble: function() {
		var $this = this,
				el = document.getElementById('all_dashboards');

		var sortable = Sortable.create(el, {
			draggable: ".dashboard-menu",
			filter: "i",
			onUpdate: function (evt) {
				sortable.option("disabled",true);
				$this.saveDashboardOrder(function() {
					sortable.option("disabled",false);
				});
			}
		});
	},
	/**
	 * Save Dashboard Tab order
	 * @method saveDashboardOrder
	 * @param  {Function}         callback Callback function when finished saving
	 */
	saveDashboardOrder: function(callback) {
		var dashboardOrder = [],
				$this = this;
		$this.activateFullLoading();
		$("#all_dashboards li").each(function() {
			dashboardOrder.push($(this).data("id"));
		});
		$.post( UCP.ajaxUrl,
			{
				module: "Dashboards",
				command: "reorder",
				order: dashboardOrder
			},
			function( data ) {
				if(typeof callback === "function") {
					callback();
				}
		}).always(function() {
			$this.deactivateFullLoading();
		}).fail(function(jqXHR, textStatus, errorThrown) {
			UCP.showAlert(textStatus,'warning');
		});
	},
	/**
	 * Open(Show) the extra widget menu
	 * @method openExtraWidgetMenu
	 * @param  {Function}          callback callback function when the menu is finished opening
	 */
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
		$(".side-menu-widgets-container").addClass("open");
	},
	/**
	 * Close the side bar menu
	 * @method closeExtraWidgetMenu
	 * @param  {Function}           callback Callback when the menu is finished closing
	 */
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
			$(document).trigger("post-body.closesimplewidget");
			if(typeof callback === "function") {
				callback();
			}
		});
		$(".side-menu-widgets-container").removeClass("open");
	},
	/**
	 * Initialize Side Bar Widgets
	 * @method initLeftNavBarMenus
	 */
	initLeftNavBarMenus: function(){
		var $this = this;

		$(document).on("click", ".close-simple-widget-menu", function() {
			$this.closeExtraWidgetMenu();
		});

		/**
		 * Click to show the simple widget settings
		 */
		$(document).on("click", ".show-simple-widget-settings", function() {
			var parent = $(this).parents(".widget-extra-menu"),
					rawname = parent.data("module"),
					widget_type_id = parent.data("widget_type_id"),
					widget_id = parent.data("id"),
					settings_container = $('#widget_settings .modal-body'),
					title = parent.data("name"),
					name = parent.data("widget_name");

			$('#widget_settings').attr("data-rawname",rawname);
			$('#widget_settings').data('rawname',rawname);

			$('#widget_settings').attr("data-id",widget_id);
			$('#widget_settings').data('id',widget_id);

			$('#widget_settings').attr("data-widget_type_id",widget_type_id);
			$('#widget_settings').data('widget_type_id',widget_type_id);

			$this.activateSettingsLoading();
			$("#widget_settings .modal-title").html('<i class="fa fa-cog" aria-hidden="true"></i> '+title+" "+_("Settings")+" ("+name+")");
			$('#widget_settings').modal('show');
			$('#widget_settings').one('shown.bs.modal', function() {
				$this.getSimpleSettingsContent(settings_container, widget_id, widget_type_id, rawname, function() {
					$("#widget_settings .modal-body .fa-question-circle").click(function(e) {
						e.preventDefault();
						e.stopPropagation();
						var f = $(this).parents("label").attr("for");
						$(".help-block").addClass('help-hidden');
						$('.help-block[data-for="'+f+'"]').removeClass('help-hidden');
					});
					$(document).trigger("post-body.simplewidgetsettings",[ widget_id ]);
				});
			});
		});

		/**
		 * Click the settings cog on a widget
		 */
		$(document).on("click", ".settings-widget", function(event){
			event.preventDefault();
			event.stopPropagation();

			var widget_type_id = 'user',
					widget_id = 'user',
					rawname = 'settings',
					settings_container = $('#widget_settings .modal-body');
			$this.activateSettingsLoading();
			$("#widget_settings .modal-title").html('<i class="fa fa-cog" aria-hidden="true"></i> '+_("User Settings"));
			$('#widget_settings').modal('show');
			$('#widget_settings').one('shown.bs.modal', function() {
				$this.getSimpleSettingsContent(settings_container, widget_id, widget_type_id, rawname, function() {
					$("#widget_settings .modal-body .fa-question-circle").click(function(e) {
						e.preventDefault();
						e.stopPropagation();
						var f = $(this).parents("label").attr("for");
						$(".help-block").addClass('help-hidden');
						$('.help-block[data-for="'+f+'"]').removeClass('help-hidden');
					});
					$(document).trigger("post-body.simplewidgetsettings",[ widget_id ]);
				});
			});
		});

		/**
		 * Click sidebar widgets (Simple widgets)
		 */
		$(document).on("click", ".custom-widget i", function(event){
			event.preventDefault();
			event.stopPropagation();

			var widget = $(this).parents(".custom-widget");

			//We are already looking at it so close it and move on
			if(widget.hasClass("active")) {
				$this.closeExtraWidgetMenu();
				return;
			}

			var clicked_module = widget.find("a").data("rawname"),
					clicked_id = widget.find("a").data("widget_type_id"),
					widget_id = widget.find("a").data("id"),
					content_object = $("#menu_"+widget_id).find(".small-widget-content");

			$("#side_bar_content li.active").removeClass("active");
			widget.addClass("active");

			$(".widget-extra-menu:visible").addClass("hidden");

			$this.activateWidgetLoading(content_object);
			$("#menu_"+widget_id).removeClass("hidden");
			$this.openExtraWidgetMenu();

			$.post( UCP.ajaxUrl,
				{
					module: "Dashboards",
					command: "getsimplewidgetcontent",
					id: clicked_id,
					rawname: clicked_module,
					uuid: uuid
				},
				function( data ) {
					if(typeof data.html !== "undefined"){
						content_object.html(data.html);

						UCP.callModuleByMethod(clicked_module,"displaySimpleWidget",widget_id);
						$(document).trigger("post-body.simplewidget",[ widget_id ]);
					}else {
						UCP.showAlert(_("There was an error getting the widget information, try again later"), "danger");
					}
				}).fail(function(jqXHR, textStatus, errorThrown) {
					UCP.showAlert(textStatus,'warning');
				});
		});
	},
	/**
	 * Initialize the item lock buttons
	 * @method initLockItemButtons
	 * @return {[type]}            [description]
	 */
	initLockItemButtons: function(){
		var $this = this;

		/**
		 * Lock a single widget on a dashboard
		 */
		$(document).on("click", ".lock-widget", function(event){
			event.preventDefault();
			event.stopPropagation();

			if(window.innerWidth <= 768) {
				UCP.showAlert(_("Widgets can not be locked on this device"),"warning");
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

			//set locking on widgets
			grid.movable($(".grid-stack-item[data-id="+id+"]"), locked);
			grid.locked($(".grid-stack-item[data-id="+id+"]"), !locked);

			//save layout
			$this.saveLayoutContent();
		});

		/**
		 * Lock all widgets on a dashboard
		 * TODO: this only works with the current dashboard for now
		 */
		$(document).on("click", ".lock-dashboard", function(event){
			event.preventDefault();
			event.stopPropagation();

			if($(this).hasClass("fa-unlock-alt")) {
				$(this).removeClass("fa-unlock-alt").addClass("fa-lock");
				$(".widget-options .fa-unlock-alt").click();
			} else {
				$(this).removeClass("fa-lock").addClass("fa-unlock-alt");
				$(".widget-options .fa-lock").click();
			}
		});
	},
	/**
	 * Initalize the document remove buttons
	 * @method initRemoveItemButtons
	 */
	initRemoveItemButtons: function(){
		var $this = this;

		/**
		 * Remove widget button
		 */
		$(document).on("click", ".remove-widget", function(event){
			//stop browser
			event.preventDefault();
			event.stopPropagation();

			var widget_id = $(this).data("widget_id");
			var widget_rawname = $(this).data("widget_rawname");
			var widget_type_id = $(this).data("widget_type_id");

			UCP.showConfirm(_("Are you sure you want to delete this widget?"), "warning", function() {
				var grid = $('.grid-stack').data('gridstack');
				//remove widget
				grid.removeWidget($(".grid-stack-item[data-id='" + widget_id + "']"));
				//save layout
				$this.saveLayoutContent();
				//call module method
				UCP.callModuleByMethod(widget_rawname,"deleteWidget",widget_type_id,$this.activeDashboard);
				//TODO: does this need a document trigger?
			});
		});

		/**
		 * Remove small widget code
		 */
		$(document).on("click", ".remove-small-widget", function(event){
			//stop browser
			event.preventDefault();
			event.stopPropagation();

			var widget_to_remove = $(this).data("widget_id"),
					widget_rawname = $(this).data("widget_rawname"),
					sidebar_object_to_remove = $("#side_bar_content li.custom-widget[data-widget_id='" + widget_to_remove + "']"),
					sidebar_menu_to_remove = $(".side-menu-widgets-container .widget-extra-menu[data-id='menu_" + widget_rawname + "_"+widget_to_remove+"']");

			UCP.callModuleByMethod(widget_rawname,"deleteSimpleWidget",widget_to_remove);

			sidebar_object_to_remove.remove();

			//close the menu
			$this.closeExtraWidgetMenu(function() {
				sidebar_menu_to_remove.remove();
			});

			//save the page
			$this.saveSidebarContent();
		});

		/**
		 * Edit Dashboard Button
		 */
		$(document).on("click", ".edit-dashboard", function(event){
			//stop the browser
			event.preventDefault();
			event.stopPropagation();

			var parent = $(this).parents('.dashboard-menu'),
					dashboard_id = parent.data("id"),
					title = parent.find("a");

			//se the input to what we have now
			$('#edit_dashboard_name').val(title.text());

			//trigger when the modal is shown (once)
			$('#edit_dashboard').one('shown.bs.modal', function () {
				//unbind because we were bound previously
				$("#edit_dashboard").off("keydown");
				$("#edit_dashboard").on('keydown', function(event) {
					switch(event.keyCode) {
						case 13: //detect enter
							$("#edit_dashboard_btn").click();
						break;
					}
				});
				//click event
				$("#edit_dashboard_btn").one("click",function() {
					//get the new name
					var name = $('#edit_dashboard_name').val();
					//show loading window so nothing changes
					$this.activateFullLoading();
					//send it off and save!
					$.post( UCP.ajaxUrl,
						{
							module: "Dashboards",
							command: "rename",
							id: dashboard_id,
							name: name
						},
						function( data ) {
							if(data.status) {
								title.text(name);
								$("#edit_dashboard").modal('hide');
							} else {
								UCP.showAlert(_("Something went wrong removing the dashboard"), "danger");
							}
					}).always(function() {
						$this.deactivateFullLoading();
					}).fail(function(jqXHR, textStatus, errorThrown) {
						UCP.showAlert(textStatus,'warning');
					});
				});
				//focus on the name
				$('#dashboard_name').focus();
			});
			//show the modal
			$("#edit_dashboard").modal('show');
		});

		/**
		 * Remve Dashboard
		 */
		$(document).on("click", ".remove-dashboard", function(event){
			//stop browser from doing what it wants
			event.preventDefault();
			event.stopPropagation();

			var dashboard_id = $(this).parents('.dashboard-actions').data("dashboard_id");

			//Check confirm
			UCP.showConfirm(_("Are you sure you want to delete this dashboard?"), "warning", function() {

				//show loading window so nothing changes
				$this.activateFullLoading();

				$.post( UCP.ajaxUrl ,
					{
						module: "Dashboards",
						command: "remove",
						id: dashboard_id
					},
					function( data ) {
						if (data.status) {
							$(".dashboard-menu[data-id='" + dashboard_id + "']").remove();

							if(dashboard_id == $this.activeDashboard) {
								if($(".dashboard-menu").length > 0) {
									$(".dashboard-menu").first().find("a").click();
								} else {
									$this.showDashboardError(_("You have no dashboards. Click here to add one"));
									$("#dashboard-content .dashboard-error").css("cursor","pointer");
									$("#dashboard-content .dashboard-error").click(function() {
										$("#add_new_dashboard").click();
									});
								}
							}

						} else {
							UCP.showAlert(_("Something went wrong removing the dashboard"), "danger");
						}
				}).always(function() {
					$this.deactivateFullLoading();
				}).fail(function(jqXHR, textStatus, errorThrown) {
					UCP.showAlert(textStatus,'warning');
				});
			});

		});
	},
	/**
	 * Initialize Widget Add Buttons
	 * TODO: needs cleanup
	 * @method initAddWidgetsButtons
	 */
	initAddWidgetsButtons: function(){
		$("#add_widget").on("show.bs.modal",function() {
			$this.closeExtraWidgetMenu();
			$(".navbar-nav .add-widget").addClass("active");
		});
		//tab select scroll position memory
		$('#add_widget .nav-tabs a[data-toggle=tab]').on('shown.bs.tab', function (e) {
			$("#add_widget .bhoechie-tab-menu .list-group-item").each(function() {
				$(this).data("position",$(this).position().top);
			});
			var container = $("#add_widget .tab-content");
			$(e.relatedTarget).data("scroll",container.scrollTop());

			var scroll = $(e.target).data("scroll");
			if(typeof scroll !== "undefined") {
				container.scrollTop(scroll);
			} else {
				container.scrollTop(0);
			}
		});
		$("#add_widget").on("shown.bs.modal",function() {
			$("#add_widget .bhoechie-tab-menu .list-group-item").each(function() {
				$(this).data("position",$(this).position().top);
			});
			$("#add_widget .tab-content").off("scroll");
			$("#add_widget .tab-content").scroll(function() {
				var top = $(this).scrollTop();
				var bottom = $(this).scrollTop() + $(this).height();
				if(($(this).find(".tab-pane.active .bhoechie-tab-menu").height() - (top - 30)) > $(this).height()) {
					$(this).find(".tab-pane.active .bhoechie-tab").css("top",top);
				}

				var active  = $(this).find(".tab-pane.active .list-group-item.active");
				active.removeClass("top-locked bottom-locked");
				if(top > (active.data("position") + 10)) {
					active.addClass("top-locked");
				} else if(bottom < (active.data("position") + active.height())) {
					active.addClass("bottom-locked");
				}
			});
		});
		$("#add_widget").on("hidden.bs.modal",function() {
			$(".navbar-nav .add-widget").removeClass("active top-locked bottom-locked");
		});
		var $this = this;
		$(document).on("click",".add-widget-button", function(){
			if($this.activeDashboard === null) {
				UCP.showAlert(_("There is no active dashboard to add widgets to"), "danger");
				return;
			}
			var current_dashboard_id = $this.activeDashboard,
					widget_id = $(this).data('widget_id'),
					widget_module_name = $(this).data('widget_module_name'),
					widget_rawname = $(this).data('rawname'),
					widget_name = $(this).data('widget_name'),
					new_widget_id = uuid.v4(),
					icon = allWidgets[widget_rawname.modularize()].icon,
					widget_info = allWidgets[widget_rawname.modularize()].list[widget_id],
					widget_has_settings = false,
					default_size_x = 2,
					default_size_y = 2,
					min_size_x = null,
					min_size_y = null,
					max_size_x = null,
					max_size_y = null,
					resizable = true,
					dynamic = false;

			if(typeof widget_info.defaultsize !== "undefined") {
				default_size_x = widget_info.defaultsize.width;
				default_size_y = widget_info.defaultsize.height;
			}

			if(typeof widget_info.maxsize !== "undefined") {
				max_size_x = widget_info.maxsize.width;
				max_size_y = widget_info.maxsize.height;
			}

			if(typeof widget_info.minsize !== "undefined") {
				min_size_x = widget_info.minsize.width;
				min_size_y = widget_info.minsize.height;
			}

			if(typeof widget_info.hasSettings !== "undefined") {
				widget_has_settings = widget_info.hasSettings;
			}

			if(typeof widget_info.resizable !== "undefined") {
				resizable = widget_info.resizable;
			}

			if(typeof widget_info.dynamic !== "undefined") {
				dynamic = widget_info.dynamic;
			}

			//Checking if the widget is already on the dashboard
			var object_on_dashboard = ($(".grid-stack-item[data-rawname='"+widget_rawname+"'][data-widget_type_id='"+widget_id+"']").length > 0);

			if(dynamic || !object_on_dashboard) {

				$this.activateFullLoading();

				$.post( UCP.ajaxUrl ,
					{
						module: "Dashboards",
						command: "getwidgetcontent",
						id: widget_id,
						rawname: widget_rawname,
						uuid: new_widget_id
					},
					function( data ) {

						$("#add_widget").modal("hide");

						if(typeof data.html !== "undefined"){
							//So first we go the HTML content to add it to the widget
							var widget_html = data.html;
							var full_widget_html = $this.widget_layout(new_widget_id, widget_module_name, widget_name, widget_id, widget_rawname, widget_has_settings, widget_html, resizable, false);
							var grid = $('.grid-stack').data('gridstack');
							//We are adding the widget always on the position 1,1
							grid.addWidget($(full_widget_html), 1, 1, default_size_x, default_size_y, true, min_size_x, max_size_x, min_size_y, max_size_y);
							grid.resizable($("div[data-id='"+new_widget_id+"']"), resizable);
							UCP.callModuleByMethod(widget_rawname,"displayWidget",new_widget_id,$this.activeDashboard);
							$(document).trigger("post-body.widgets",[ new_widget_id, $this.activeDashboard ]);
						}else {
							UCP.showAlert(_("There was an error getting the widget information, try again later"), "danger");
						}
					}).always(function() {
						$this.deactivateFullLoading();
					}).fail(function(jqXHR, textStatus, errorThrown) {
						UCP.showAlert(textStatus,'warning');
					});
			} else {
				UCP.showAlert(_("You already have this widget on this dashboard"), "info");
			}
		});

		/**
		 * Add Small Widget Button Bind
		 */
		$(".add-small-widget-button").click(function(){

			var widget_id = $(this).data('id'),
					widget_rawname = $(this).data('rawname'),
					widget_name = $(this).data('name'),
					widget_sub = $(this).data('widget_type_id'),
					new_widget_id = uuid.v4(),
					widget_info = allSimpleWidgets[widget_rawname.modularize()].list[widget_id],
					widget_icon = allSimpleWidgets[widget_rawname.modularize()].icon,
					hasSettings = false,
					dynamic = false;

			if(typeof widget_info.hasSettings !== "undefined") {
				hasSettings = widget_info.hasSettings;
			}

			if(typeof widget_info.dynamic !== "undefined") {
				dynamic = widget_info.dynamic;
			}

			//Checking if the widget is already on the dashboard

			var object_on_dashboard = ($("#side_bar_content li.custom-widget[data-widget_rawname='"+widget_rawname+"'][data-widget_type_id='"+widget_id+"']").length > 0);

			//Checking if the widget is already on the bar
			if(dynamic || !object_on_dashboard){

				$this.activateFullLoading();

				$.post( UCP.ajaxUrl,
					{
						module: "Dashboards",
						command: "getsimplewidgetcontent",
						id: widget_id,
						rawname: widget_rawname,
						uuid: new_widget_id
					},
					function( data ) {
						$("#add_widget").modal("hide");

						if(typeof data.html !== "undefined"){
							//get small widget layout
							var full_widget_html = $this.smallWidgetLayout(new_widget_id, widget_rawname, widget_name, widget_id, widget_icon);
							//get small widget menu layout
							var menu_widget_html = $this.smallWidgetMenuLayout(new_widget_id, widget_rawname, widget_name, widget_id, widget_icon, widget_sub, hasSettings);

							//add icon to sidebar
							if($("#side_bar_content .custom-widget").length) {
								//we already have an element on the sidebar so add to the end
								$("#side_bar_content .custom-widget").last().after(full_widget_html);
							} else {
								//add widget after the add button because we dont have anything there
								$("#side_bar_content .add-widget").after(full_widget_html);
							}

							//now add the menu (hidden) to the widgets container for expansion later
							$(".side-menu-widgets-container").append(menu_widget_html);

							//execute module method
							UCP.callModuleByMethod(widget_rawname,"addSimpleWidget",new_widget_id);

							//execute trigger
							$(document).trigger("post-body.addsimplewidget",[ new_widget_id, $this.activeDashboard ]);

							//save side bar
							$this.saveSidebarContent();
						}else {
							UCP.showAlert(_("There was an error getting the widget information, try again later"), "danger");
						}
					}).always(function() {
						$this.deactivateFullLoading();
					}).fail(function(jqXHR, textStatus, errorThrown) {
						UCP.showAlert(textStatus,'warning');
					});
			}else {
				UCP.showAlert(_("You already have this widget on the side bar"), "info");
			}
		});
	},
	/**
	 * Initiate Category Binds
	 * @method initCategoriesWidgets
	 */
	initCategoriesWidgets: function(){
		$("#add_widget .bhoechie-tab-container").each(function() {
			var parent = $(this);
			$(this).find(".list-group-item").click(function(e) {
				e.preventDefault();
				$(this).siblings('a.active').removeClass("active top-locked bottom-locked");
				$(this).addClass("active");
				var id = $(this).data("id");
				parent.find(".bhoechie-tab-content").removeClass("active top-locked bottom-locked");
				parent.find(".bhoechie-tab-content[data-id='"+id+"']").addClass("active");
			});
		});
	},
	/**
	 * Get Widget content
	 * TODO: This is duplicated in certain places!!!
	 * @method getWidgetContent
	 * @param  {string}           widget_id             The widget ID
	 * @param  {string}           widget_type_id        The widget type ID
	 * @param  {string}           widget_rawname        The widget rawname
	 * @param  {Function}         callback              Callback Function when done (success + complete)
	 */
	getWidgetContent: function(widget_id, widget_type_id, widget_rawname, callback){
		var $this = this,
				widget_content_object = $(".grid-stack-item[data-id='"+widget_id+"'] .widget-content");
		this.activateWidgetLoading(widget_content_object);

		$.post( UCP.ajaxUrl,
			{
				module: "Dashboards",
				command: "getwidgetcontent",
				id: widget_type_id,
				rawname: widget_rawname,
				uuid: widget_id
			},
			function( data ) {

				var widget_html = data.html;

				if(typeof data.html === "undefined"){
					widget_html = '<div class="alert alert-danger">'+_('Something went wrong getting the content of the widget')+'</div>';
				}

				widget_content_object.html(widget_html);
				UCP.callModuleByMethod(widget_rawname,"displayWidget",widget_id,$this.activeDashboard);
				setTimeout(function() {
					UCP.callModuleByMethod(widget_rawname,"resize",widget_id,$this.activeDashboard);
				},100);

			}).done(function() {
				if(typeof callback === "function") {
					callback();
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				UCP.showAlert(textStatus,'warning');
			});
	},
	/**
	 * Get Simple Widget Settings Content
	 * @method getSimpleSettingsContent
	 * @param  {object}           widget_content_object jQuery object of the settings container
	 * @param  {string}           widget_id             The widget ID
	 * @param  {string}           widget_type_id        The widget type ID
	 * @param  {string}           widget_rawname        The widget rawname
	 * @param  {Function}         callback              Callback Function when done (success + complete)
	 */
	getSimpleSettingsContent: function(widget_content_object, widget_id, widget_type_id, widget_rawname, callback){
		var $this = this;

		$.post( UCP.ajaxUrl,
			{
				module: "Dashboards",
				command: "getsimplewidgetsettingscontent",
				id: widget_type_id,
				rawname: widget_rawname,
				uuid: widget_id
			},
			function( data ) {

				var widget_html = data.html;

				if(typeof data.html === "undefined"){
					widget_html = '<div class="alert alert-danger">'+_('Something went wrong getting the settings from the widget')+'</div>';
				}

				widget_content_object.html(widget_html);
				UCP.callModuleByMethod(widget_rawname,"displaySimpleWidgetSettings",widget_id);
			}).done(function() {
				if(typeof callback === "function") {
					callback();
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				UCP.showAlert(textStatus,'warning');
			});
	},
	/**
	 * Get Module Settings Content
	 * @method getSettingsContent
	 * @param  {object}           widget_content_object jQuery object of the settings container
	 * @param  {string}           widget_id             The widget ID
	 * @param  {string}           widget_type_id        The widget type ID
	 * @param  {string}           widget_rawname        The widget rawname
	 * @param  {Function}         callback              Callback Function when done (success + complete)
	 */
	getSettingsContent: function(widget_content_object, widget_id, widget_type_id, widget_rawname, callback){
		var $this = this;

		$.post( UCP.ajaxUrl,
			{
				module: "Dashboards",
				command: "getwidgetsettingscontent",
				id: widget_type_id,
				rawname: widget_rawname,
				uuid: widget_id
			},
			function( data ) {

				var widget_html = data.html;

				if(typeof data.html === "undefined"){
					widget_html = '<div class="alert alert-danger">'+_('Something went wrong getting the settings from the widget')+'</div>';
				}

				widget_content_object.html(widget_html);
				UCP.callModuleByMethod(widget_rawname,"displayWidgetSettings",widget_id,$this.activeDashboard);
			}).done(function() {
				if(typeof callback === "function") {
					callback();
				}
			}).fail(function(jqXHR, textStatus, errorThrown) {
				UCP.showAlert(textStatus,'warning');
			});
	},
	/**
	 * Setup grid stack!
	 * @method setupGridStack
	 * @return {object}       The gridstack object!
	 */
	setupGridStack: function() {
		var gridstack = $(".grid-stack").data('gridstack');
		if(typeof gridstack === "undefined") {
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
			gridstack = $(".grid-stack").data('gridstack');
		}
		return gridstack;
	},
	/**
	 * Bind Grid Stack changes
	 * @method bindGridChanges
	 */
	bindGridChanges: function() {
		var $this = this;
		$('.grid-stack').on('resizestop', function(event, ui) {
			//Never on mobile, Always on Desktop
			if(window.innerWidth > 768) {
				UCP.callModulesByMethod("resize",ui.element.data("id"),$this.activeDashboard);
			}
		});

		$('.grid-stack').on('removed', function(event, items) {
			//Never on Desktop, Always on mobile
			if(window.innerWidth <= 768) {
				//save layout
				$this.saveLayoutContent();
			}
		});

		$('.grid-stack').on('added', function(event, items) {
			//Never on Desktop, Always on mobile
			if(window.innerWidth <= 768) {
				//save layout
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
				//save layout
				$this.saveLayoutContent();
			}
		});
		//some gitchy crap going on here, we have to relock the widget
		$('.grid-stack').on('dragstop', function(event, ui) {
			var grid = $(".grid-stack").data('gridstack');
			$('.grid-stack .grid-stack-item:visible').not(".grid-stack-placeholder").each(function(){
				var el = $(this);
						locked = el.find(".lock-widget i").hasClass("fa-lock");
				grid.movable(el, !locked);
				grid.locked(el, locked);
				grid.resizable(el, !locked);
			});
		});
		//some gitchy crap going on here, we have to relock the widget
		$('.grid-stack').on('resizestop', function(event, ui) {
			var grid = $(".grid-stack").data('gridstack');
			$('.grid-stack .grid-stack-item:visible').not(".grid-stack-placeholder").each(function(){
				var el = $(this);
						locked = el.find(".lock-widget i").hasClass("fa-lock");
				grid.movable(el, !locked);
				grid.locked(el, locked);
				grid.resizable(el, !locked);
			});
		});
	},
	/**
	 * Setup Add Dashboard Button Binds
	 * @method setupAddDashboard
	 */
	setupAddDashboard: function() {
		var $this = this;
		$("#create_dashboard").click(function() {
			//make sure there is something in the name
			if ($("#dashboard_name").val().trim() === "") {
				//if empty then return back and focus on name
				UCP.showAlert(_("You must set a dashboard name!"),'warning', function() {
					$("#dashboard_name").focus();
				});
			} else {
				//show loading screen while we save this dashboard
				$this.activateFullLoading();

				$.post( UCP.ajaxUrl, {module: "Dashboards", command: "add", name: $("#dashboard_name").val()}, function( data ) {
					if (!data.status) {
						UCP.showAlert(data.message,'warning');
					} else {
						var select = $("#all_dashboards li").length;
						var new_dashboard_html = '<li class="menu-order dashboard-menu" data-id="'+data.id+'"><a data-dashboard>'+$("#dashboard_name").val()+'</a> <div class="dashboard-actions" data-dashboard_id="'+data.id+'"><i class="fa fa-unlock-alt lock-dashboard" aria-hidden="true"></i><i class="fa fa-pencil edit-dashboard" aria-hidden="true"></i><i class="fa fa-times remove-dashboard" aria-hidden="true"></i></div></li>';
						$("#all_dashboards").append(new_dashboard_html);

						dashboards[data.id] = null;

						$(document).trigger("addDashboard",[data.id]);

						if(!select) {
							$("#all_dashboards li a").click();
						}

						//hide modal we are done
						$("#add_dashboard").modal("hide");
					}
				}).fail(function(jqXHR, textStatus, errorThrown) {
					UCP.showAlert(textStatus,'warning');
				}).always(function() {
					$this.deactivateFullLoading();
				});
			}
		});

		//dashboard tab click
		$(document).on("click",".dashboard-menu a[data-dashboard]", function(e) {
			//stop default browser actions
			e.preventDefault();
			e.stopPropagation();

			var gridstack = $(".grid-stack").data('gridstack'),
					id = $(this).parents(".dashboard-menu").data("id"),
					popstate = $(this).data("popstate");

			popstate = (typeof popstate !== "undefined") ? popstate : false;
			//we are on this dashboard. So do nothing
			if($this.activeDashboard == id) {
				return;
			}

			//remove active from any dashboard tab
			$(".dashboard-menu").removeClass("active");
			//remove the click block from all
			$(".dashboards a[data-dashboard]").removeClass("pjax-block");
			//add click block to this one
			$(this).addClass("pjax-block");
			//activate our tab
			$(".dashboard-menu[data-id='"+id+"']").addClass("active");
			//push browser history (pjax like) only if we aren't in a popstate event
			if(!popstate) {
				history.pushState({ activeDashboard: id }, $(this).text(), "?dashboard="+id);
			} else {
				$(this).data("popstate",false);
			}
			//set tab title
			$("title").text(_("User Control Panel") + " - " + $(this).text());
			//set our active dashboard
			$this.activeDashboard = id;

			if(typeof gridstack !== "undefined") {
				//destroy the grid (which also deletes the elements!)
				gridstack.destroy(true);
			}

			//add back grid container
			$("#module-page-widgets").html('<div class="grid-stack" data-dashboard_id="'+id+'">');

			//setup grid
			gridstack = $this.setupGridStack();

			//load widgets
			$this.activateFullLoading();
			var resave = false;
			async.each(dashboards[id], function(widget, callback) {
				//uppercase the module rawname
				var cased = widget.rawname.modularize();
				if(typeof allWidgets[cased] === "undefined") {
					callback();
					return;
				}
				//get loading html
				var widget_html = $this.activateWidgetLoading();
				//TODO: fix this
				widget.resizable = true;
				if(!widget.id.match(/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i)) {
					widget.id = uuid.v4();
					resave = true;
				}
				//get widget content
				var full_widget_html = $this.widget_layout(widget.id, widget.widget_module_name, widget.name, widget.widget_type_id, widget.rawname, widget.has_settings, widget_html, widget.resizable, widget.locked);
				//get max/min size of this widget
				var min_size_x = (typeof allWidgets[cased].list[widget.widget_type_id].minsize !== "undefined" && typeof allWidgets[cased].list[widget.widget_type_id].minsize.width !== "undefined") ? allWidgets[cased].list[widget.widget_type_id].minsize.width : null;
				var min_size_y = (typeof allWidgets[cased].list[widget.widget_type_id].minsize !== "undefined" && typeof allWidgets[cased].list[widget.widget_type_id].minsize.height !== "undefined") ? allWidgets[cased].list[widget.widget_type_id].minsize.height : null;
				var max_size_x = (typeof allWidgets[cased].list[widget.widget_type_id].maxsize !== "undefined" && typeof allWidgets[cased].list[widget.widget_type_id].maxsize.width !== "undefined") ? allWidgets[cased].list[widget.widget_type_id].maxsize.width : null;
				var max_size_y = (typeof allWidgets[cased].list[widget.widget_type_id].maxsize !== "undefined" && typeof allWidgets[cased].list[widget.widget_type_id].maxsize.height !== "undefined") ? allWidgets[cased].list[widget.widget_type_id].maxsize.height : null;
				//is this widget resizable?
				var resizable = (typeof allWidgets[cased].list[widget.widget_type_id].resizable !== "undefined") ? allWidgets[cased].list[widget.widget_type_id].resizable : true;

				//now add the widget
				gridstack.addWidget($(full_widget_html), widget.size_x, widget.size_y, widget.col, widget.row, false, min_size_x, max_size_x, min_size_y, max_size_y);

				//set resizable
				setTimeout(function() {
					gridstack.resizable($(".grid-stack-item[data-id="+widget.id+"]"), !widget.locked);
				});

				//set locked/or not
				gridstack.movable($(".grid-stack-item[data-id="+widget.id+"]"), !widget.locked);
				gridstack.locked($(".grid-stack-item[data-id="+widget.id+"]"), widget.locked);

				//get widget content
				$.post( UCP.ajaxUrl,
					{
						module: "Dashboards",
						command: "getwidgetcontent",
						id: widget.widget_type_id,
						rawname: widget.rawname,
						uuid: widget.id
					},
					function( data ) {
						//set the content from what we got
						$(".grid-stack .grid-stack-item[data-id="+widget.id+"] .widget-content").html(data.html);
						//execute module method
						UCP.callModuleByMethod(widget.rawname,"displayWidget",widget.id,$this.activeDashboard);
						//execute resize module method
						setTimeout(function() {
							UCP.callModuleByMethod(widget.rawname,"resize",widget.id,$this.activeDashboard);
						},100);

						//trigger event
						$(document).trigger("post-body.widgets",[ widget.id, $this.activeDashboard ]);
					}
				).done(function() {
					callback(); //trigger callback to async
				}).fail(function(jqXHR, textStatus, errorThrown) {
					callback(textStatus); //trigger error to async
				});
			}, function(err) {
				if(err) {
					//show error because there was an error
					UCP.showAlert(err,'danger');
				} else {
					//hide loading window
					$this.deactivateFullLoading();
					//bind grid events
					$this.bindGridChanges();
					//execute module methods
					UCP.callModulesByMethod("showDashboard",$this.activeDashboard);
					//trigger all widgets loaded event
					$(document).trigger("post-body.widgets",[ null, $this.activeDashboard ]);
					if(resave) {
						$this.saveLayoutContent();
					}
				}
			});
		});
	}
});
