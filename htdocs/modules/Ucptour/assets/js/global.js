var UcptourC = UCPMC.extend({
	init: function() {
		this.tour = null;
	},
	poll: function(data) {
		//console.log(data)
	},

});

$(document).bind("logIn", function( event ) {
	UCP.Modules.Ucptour.tour = new Tour({
		debug: false,
		storage: false,
		keyboard: false,
		onEnd: function (tour) {
			$.post( UCP.ajaxUrl + "?module=ucptour&command=tour", { state: 0 }, function( data ) {

			});
		},
		steps: [
			{
				orphan: true,
				title: sprintf(_("Welcome to %s!"),UCP.Modules.Ucptour.staticsettings.brand),
				content: _("Congratulations!")+"<br><br> "+_("You just successfully logged in for the first time!")+" <br>"+_("This tour will take you on a brief walkthrough of the new User Control Panel in a few simple steps.")+"<br><br>"+_("You can always exit the tour if you'd like, and you can restart the tour at anytime by clicking your User Settings and then 'Restart Tour'")+"<br><br><u>"+_("To continue just click Next")+"</u>",
				backdrop: true,
			}, {
				backdrop: true,
				backdropContainer: "#nav-bar-background",
				element: "#add_new_dashboard",
				placement: "left",
				title: _("Adding a dashboard"),
				content: _("The User Control Panel is now separated by 'Dashboards'. You can add a new dashboard by clicking this symbol")+"<br><br>"+_("Click this symbol to continue"),
				next: -1,
				reflex: true,
				onShow: function(tour) {
					$(".navbar.navbar-inverse.navbar-fixed-left").css("z-index","1029");
				},
				onShown: function(tour) {
					var step = tour.getCurrentStep();
					$("#add_dashboard").one("shown.bs.modal", function() {
						tour.goTo(step + 1);
					});
				}
			}, {
				backdrop: true,
				backdropContainer: "#add_dashboard .modal-dialog",
				element: "#dashboard_name",
				placement: "bottom",
				title: _("Name your dashboard"),
				content: _("Enter a name for your dashboard in this input box"),
				onShown: function(tour) {
					var step = tour.getCurrentStep();
					$("#dashboard_name").keyup(function(e) {
						if (e.keyCode == '13') {
							$(document).one("addDashboard",function(e, id) {
								$(".dashboard-menu[data-id="+id+"]").addClass("tour-step");
								$(".dashboard-menu[data-id="+id+"] a").click();
								tour.goTo(step + 2);
							});
						}
					});
				}
			}, {
				backdrop: true,
				backdropContainer: "#add_dashboard .modal-dialog",
				element: "#create_dashboard",
				placement: "bottom",
				title: _("Save your dashboard"),
				content: _("When you are finished simply hit 'Create Dashboard' to create your dashboard"),
				reflex: true,
				next: -1,
				onShown: function(tour) {
					var step = tour.getCurrentStep();
					if($("#dashboard_name").val() === "") {
						tour.goTo(step - 1);
					}
				},
				onNext: function(tour) {
					var step = tour.getCurrentStep();
					$(document).one("addDashboard",function(e, id) {
						$(".dashboard-menu[data-id="+id+"]").addClass("tour-step");
						$(".dashboard-menu[data-id="+id+"] a").click();
						tour.goTo(step + 1);
					});
					return (new jQuery.Deferred()).promise();
				}
			}, {
				backdrop: true,
				backdropContainer: "#nav-bar-background",
				element: ".dashboard-menu.tour-step",
				placement: "bottom",
				title: _("Dashboards"),
				content: _("Your dashboard has been added here"),
				previous: -1
			}, {
				backdrop: true,
				backdropContainer: ".main-content-object",
				element: "#dashboard-content",
				placement: "bottom",
				title: _("Dashboard Widgets"),
				content: _("Dashboard widgets will be displayed here"),
				previous: -1,
				onShown: function(tour) {
					$("#dashboard-content").css("height","calc(100vh - 66px)");
				},
				onNext: function(tour) {
					$("#dashboard-content").css("height","");
				}
			}, {
				backdrop: true,
				backdropContainer: "#nav-bar-background",
				element: ".dashboard-menu.tour-step .edit-dashboard",
				placement: "bottom",
				title: _("Editing a Dashboard"),
				content: _("The dashboard's name can be changed by clicking the pencil")
			}, {
				backdrop: true,
				backdropContainer: "#nav-bar-background",
				element: ".dashboard-menu.tour-step .remove-dashboard",
				placement: "left",
				title: _("Delete a Dashboard"),
				content: sprintf(_("A dashboard can be deleted by clicking the '%s'"),'X')
			}, {
				backdrop: true,
				backdropContainer: "#nav-bar-background",
				element: ".dashboard-menu.tour-step",
				placement: "bottom",
				title: _("Ordering dashboards"),
				content: _("Multiple dashboard can be re-ordered by hovering with your mouse until the move cursor is shown. Then clicking and dragging the dashboard in the order you want"),
				onHidden: function(tour) {
					$(".navbar.navbar-inverse.navbar-fixed-left").css("z-index","");
				}
			}, {
				backdrop: true,
				backdropContainer: "#side_bar_content",
				element: "#side_bar_content .add-widget",
				placement: "right",
				title: _("Adding Widgets"),
				content: sprintf(_("Widgets can be added by clicking the '%s' symbol"),'(+)')+"<br><br>"+_("Click this symbol to continue"),
				reflex: true,
				next: -1,
				onShown: function(tour) {
					var step = tour.getCurrentStep();
					$("#add_widget").one("shown.bs.modal", function() {
						tour.goTo(step + 1);
					});
					$(".tour-step-background").css("background-color","white");
				}
			}, {
				backdrop: true,
				backdropContainer: "#add_widget .modal-body",
				element: "#add_widget .modal-body .nav-tabs",
				placement: "left",
				title: _("Selecting Widgets"),
				content: _("There are two different types of widgets. Dashboard Widgets and Side Bar widgets. Let's start with dashboard widgets"),
				previous: -1,
				onShown: function(tour) {
					$("a[href=#red]").click();
				}
			}, {
				backdrop: true,
				backdropContainer: "#add_widget .tab-pane.active .bhoechie-tab-container",
				element: "#add_widget .tab-pane.active .list-group-item.active",
				placement: "right",
				title: _("Selecting Dashboard Widgets"),
				content: _("Dashbord Widgets are sorted into categories on the left. These widgets will appear directly on your dashboard. You can click on any category to get a listing of the widgets available"),
				previous: -1
			}, {
				backdrop: true,
				backdropContainer: "#add_widget .tab-pane.active .bhoechie-tab-container",
				element: "#add_widget .tab-pane.active .bhoechie-tab-content.active .ibox-content-widget:first",
				placement: "bottom",
				title: _("Selecting Widgets"),
				content: _("Widgets are listed on the right. The titles and descriptions will be show for each widget"),
				onShown: function(tour) {
					$("#add_widget .modal-body").scrollTop(0);
					var myStep = tour.getCurrentStep();
					$("#add_widget .tab-pane.active .bhoechie-tab-content.active .ibox-content-widget:first .add-widget-button").one("click",function() {
						tour.goTo(myStep + 2);
					});
				}
			}, {
				backdrop: true,
				backdropContainer: "#add_widget .tab-pane.active .bhoechie-tab-content.active .ibox-content-widget:first",
				element: "#add_widget .tab-pane.active .bhoechie-tab-content.active .ibox-content-widget:first .add-widget-button",
				placement: "right",
				title: _("Adding Widgets"),
				content: sprintf(_("Clicking the '%s' symbol will add this widget to the currently active dashboard."),'(+)')+"<br><br>"+_("Click this symbol to continue"),
				next: -1,
				onShown: function(tour) {
					var step = tour.getCurrentStep();
					$("#add_widget .tab-pane.active .bhoechie-tab-content.active .ibox-content-widget:first .add-widget-button").off("click");
					$(document).one("post-body.widgets", function(e, widget_id) {
						$(".grid-stack-item[data-id="+widget_id+"]").addClass("tour-step");
						tour.goTo(step + 1);
					});
				}
			}, {
				element: ".grid-stack-item.tour-step",
				placement: "right",
				title: _("Dashboard Widget"),
				content: _("Widgets are placed automatically on the dashboard after they have been added")
			}, {
				element: ".grid-stack-item.tour-step .widget-title",
				placement: "bottom",
				title: _("Widget Placement"),
				content: _("Widgets can be moved around by clicking and dragging on the title bar"),
				onNext: function(tour) {
					$(".grid-stack-item.tour-step .ui-icon-gripsmall-diagonal-se").show();
				}
			}, {
				element: ".grid-stack-item.tour-step .ui-icon-gripsmall-diagonal-se",
				placement: "right",
				title: _("Widget Size"),
				content: _("Widgets can be resized by placing your mouse near the corner of the widget. Click and drag to resize the widget.")+"<br><br>"+_("Note: some widgets have size restrictions!"),
				onNext: function(tour) {
					$(".grid-stack-item.tour-step .ui-icon-gripsmall-diagonal-se").hide();
				}
			}, {
				element: ".grid-stack-item.tour-step .widget-title .lock-widget",
				placement: "right",
				title: _("Widget Locking"),
				content: _("Widgets can be locked into place to prevent their movement")
			}, {
				element: ".grid-stack-item.tour-step .widget-title .edit-widget",
				placement: "right",
				title: _("Widget Settings"),
				content: _("Widgets settings can be changed by clicking this icon")
			}, {
				element: ".grid-stack-item.tour-step .widget-title .remove-widget",
				placement: "right",
				title: _("Widget Removal"),
				content: sprintf(_("Widgets can also be removed by clicking the '%s' symbol"),'X')
			}, {
				element: ".dashboard-menu.active .lock-dashboard",
				placement: "bottom",
				title: _("Dashboard Locking"),
				content: sprintf(_("All widgets in a dashboard can also be locked globally by clicking the '%s' symbol on the dashboard tab"),'X')
			}, {
				element: ".navbar.navbar-inverse.navbar-fixed-left",
				placement: "right",
				title: _("Side Bar Widgets"),
				content: _("This is where side bar widgets live. Side bar widgets do not change when you change dashboards. They are global throughout UCP")
			}, {
				backdrop: true,
				backdropContainer: "#side_bar_content",
				element: "#side_bar_content .add-widget",
				placement: "right",
				title: _("Adding Side Bar Widgets"),
				content: sprintf(_("Side bar Widgets can also be added by clicking the '%s' symbol. These appear under the '%s' symbol in this side bar"),'(+)','(+)')+"<br><br>"+_("Click this symbol to continue"),
				reflex: true,
				next: -1,
				onShown: function(tour) {
					var step = tour.getCurrentStep();
					$("#add_widget").one("shown.bs.modal", function() {
						tour.goTo(step + 1);
					});
					$(".tour-step-background").css("background-color","white");
				}
			}, {
				backdrop: true,
				backdropContainer: "#add_widget .modal-body",
				element: "#add_widget .modal-body .nav-tabs",
				placement: "right",
				title: _("Selecting Side Bar Widgets"),
				content: _("Side Bar widgets are grouped in a single category called 'Side Bar Widgets'"),
				onShown: function(tour) {
					$("#add_widget .modal-body").scrollTop(0);
					$("a[href=#small]").click();
				}
			}, {
				backdrop: true,
				backdropContainer: "#add_widget .tab-pane.active .bhoechie-tab-container",
				element: "#add_widget .tab-pane.active .list-group-item.active",
				placement: "bottom",
				title: _("Selecting Small Widgets"),
				content: _("Small Widgets are listed on the right. The titles and descriptions will be show for each widget"),
			}, {
				backdrop: true,
				backdropContainer: "#add_widget .tab-pane.active .bhoechie-tab-container",
				element: "#add_widget .tab-pane.active .bhoechie-tab-content.active .ibox-content-widget:first",
				placement: "bottom",
				title: _("Selecting Widgets"),
				content: _("Widgets are listed on the right. The titles and descriptions will be show for each widget"),
				onShown: function(tour) {
					$("#add_widget .modal-body").scrollTop(0);
					var myStep = tour.getCurrentStep();
					$("#add_widget .tab-pane.active .bhoechie-tab-content.active .ibox-content-widget:first .add-small-widget-button").one("click",function() {
						tour.goTo(myStep + 2);
					});
				}
			}, {
				backdrop: true,
				orphan: true,
				backdropContainer: "#add_widget .tab-pane.active .bhoechie-tab-content.active .ibox-content-widget:first",
				element: ".add-small-widget-button",
				placement: "right",
				title: _("Adding Small Widgets"),
				content: sprintf(_("Clicking the '%s' symbol will add this small widget to the display. It will be visible on all dashboards"),'(+)')+"<br><br>"+_("Click this symbol to continue"),
				next: -1,
				onShown: function(tour) {
					var step = tour.getCurrentStep();
					$(document).one("post-body.addsimplewidget", function(e, widget_id) {
						$(".custom-widget[data-widget_id="+widget_id+"]").addClass("tour-step");
						tour.goTo(step + 1);
					});
				}
			}, {
				element: "#side_bar_content .custom-widget.tour-step",
				placement: "right",
				title: _("Small Widget Display"),
				content: _("Once a small widget has been added it will show up in the left sidebar")+"<br><br>"+_("Click the widget's icon to continue"),
				next: -1,
				onShown: function(tour) {
					var step = tour.getCurrentStep();
					$(document).one("post-body.simplewidget", function(e, widget_id, widget_type_id) {
						tour.goTo(step + 1);
					});
				}
			}, {
				element: ".widget-extra-menu:visible .small-widget-content",
				placement: "right",
				title: _("Small Widget Display"),
				content: _("The widget's content is displayed here")
			}, {
				element: ".widget-extra-menu:visible .remove-small-widget",
				placement: "top",
				title: _("Small Widget Display"),
				content: _("To remove this widget from the side bar click 'Remove Widget'")
			}, {
				element: ".widget-extra-menu:visible .close-simple-widget-menu",
				placement: "bottom",
				title: _("Small Widget Display"),
				content: sprintf(_("To just close/hide the widget's content click the '%s' symbol"),'(X)')+"<br><br>"+_("Click this symbol to continue"),
				next: -1,
				onShown: function(tour) {
					var step = tour.getCurrentStep();
					$(document).one("post-body.closesimplewidget", function(e, widget_id, widget_type_id) {
						tour.goTo(step + 1);
					});
				}
			}, {
				element: "#side_bar_content .settings-widget",
				placement: "right",
				title: _("User Settings"),
				content: _("Your specific settings are defined when clicking the 'gear' icon in the side bar")
			}, {
				element: "#side_bar_content .logout-widget",
				placement: "right",
				title: _("Logout"),
				content: _("Your can logout of UCP by clicking this logout button")
			}, {
				orphan: true,
				title: _("End of tour"),
				content: sprintf(_("You have finished the tour of User Control Panel for %s 14. You can restart this tour at any time in your User Settings"),UCP.Modules.Ucptour.staticsettings.brand)
			}
		]
	});
	if(UCP.Modules.Ucptour.staticsettings.show) {
		// Initialize the tour
		UCP.Modules.Ucptour.tour.init();

		// Start the tour
		UCP.Modules.Ucptour.tour.start();
	}
});
