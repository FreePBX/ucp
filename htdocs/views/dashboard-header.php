<nav id="nav-bar-background" class="navbar navbar-default navbar-fixed-top">
	<div id="global-message-container">
		<div id="global-message"></div>
	</div>

	<div id="add_new_dashboard" class="add-dashboard" data-toggle="modal" data-target="#add_dashboard">
		<i class="fa fa-2x fa-plus-circle" aria-hidden="true"></i>
	</div>

	<ul class="nav nav-tabs dashboards" role="tablist" id="all_dashboards">
		<?php if(!empty($user_dashboards)) { ?>
			<?php foreach($user_dashboards as $dashboard_info) { ?>
				<li class="<?php echo ($dashboard_info["id"] == $active_dashboard) ? 'active' : ''?> menu-order dashboard-menu" data-id="<?php echo $dashboard_info["id"]?>">
					<a data-pjax href="?dashboard=<?php echo $dashboard_info["id"]?>"><?php echo $dashboard_info["name"]?></a> <div class="remove-dashboard" data-dashboard_id="<?php echo $dashboard_info["id"]?>"><i class="fa fa-times" aria-hidden="true"></i></div>
				</li>
			<?php } ?>
		<?php } ?>
	</ul>
</nav>

<!-- left navbar -->
<nav class="navbar navbar-inverse navbar-fixed-left">
	<ul class="nav navbar-nav" id="side_bar_content">
		<li class="add-widget first-widget" data-toggle="modal" data-target="#add_widget"><a href="#"><i class="fa fa-plus-circle" aria-hidden="true"></i></a></li>
		<?php if(!empty($user_small_widgets)) { ?>
			<?php foreach($user_small_widgets as $small_widget) { ?>
				<li class="custom-widget" data-widget_id="<?php echo $small_widget->id; ?>">
					<a href="#" data-module_name="<?php echo $small_widget->module_name; ?>" data-id="<?php echo $small_widget->id; ?>" data-name="<?php echo $small_widget->name; ?>" data-rawname="<?php echo $small_widget->rawname; ?>" data-type_id="<?php echo $small_widget->type_id; ?>" data-icon="<?php echo $small_widget->icon; ?>"><i class="<?php echo $small_widget->icon; ?>" aria-hidden="true"></i></a>
				</li>
			<?php } ?>
		<?php } ?>
		<li class="last-widget"><a href="?logout"><i class="fa fa-sign-out fa-rotate-180" aria-hidden="true"></i></a></li>
	</ul>
</nav>

<div class="side-menu-widgets-container">
	<?php if(!empty($user_small_widgets)) { ?>
		<?php foreach($user_small_widgets as $small_widget) { ?>
			<div class="widget-extra-menu" id="menu_<?php echo $small_widget->rawname; ?>" data-module="<?php echo $small_widget->rawname; ?>">
				<a href="#" class="closebtn" onclick="close_extra_widget_menu()"><i class="fa fa-times-circle-o" aria-hidden="true"></i></a>
				<div class="small-widget-content">
				</div>
				<button type="button" class="btn btn-xs btn-danger remove-small-widget" data-widget_id="<?php echo $small_widget->id; ?>">Remove Widget</button>
			</div>
		<?php } ?>
	<?php } ?>
</div>

<!-- fake right bar -->
<div class="right-bar">
</div>

<div class="container-fluid main-content-object">
	<div class="row">
		<div class="col-md-12 col-sm-12 col-lg-12 main-content-column">
			<div id="dashboard-content">