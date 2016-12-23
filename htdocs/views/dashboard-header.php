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
					<a data-pjax href="?dashboard=<?php echo $dashboard_info["id"]?>"><?php echo $dashboard_info["name"]?></a>
					<div class="dashboard-actions" data-dashboard_id="<?php echo $dashboard_info["id"]?>">
						<i class="fa fa-unlock-alt lock-dashboard" aria-hidden="true"></i>
						<i class="fa fa-pencil edit-dashboard" aria-hidden="true"></i>
						<i class="fa fa-times remove-dashboard" aria-hidden="true"></i>
					</div>
				</li>
			<?php } ?>
		<?php } ?>
	</ul>
</nav>

<!-- left navbar -->
<nav class="navbar navbar-inverse navbar-fixed-left">
	<ul class="nav navbar-nav" id="side_bar_content">
		<li class="add-widget first-widget locked" data-toggle="modal" data-target="#add_widget"><a href="#"><i class="fa fa-plus-circle" aria-hidden="true"></i></a></li>
		<?php if(!empty($user_small_widgets)) { ?>
			<?php foreach($user_small_widgets as $small_widget) { ?>
				<li class="custom-widget" data-widget_id="<?php echo $small_widget['id']; ?>" data-widget_rawname="<?php echo $small_widget['rawname']; ?>">
					<a href="#" data-module_name="<?php echo $small_widget['module_name']; ?>" data-id="<?php echo $small_widget['id']; ?>" data-name="<?php echo $small_widget['name']; ?>" data-rawname="<?php echo $small_widget['rawname']; ?>" data-type_id="<?php echo $small_widget['type_id']; ?>" data-icon="<?php echo $small_widget['icon']; ?>">
						<i class="<?php echo $small_widget['icon']; ?>" aria-hidden="true"></i>
					</a>
				</li>
			<?php } ?>
		<?php } ?>
		<li class="settings-widget locked">
			<a href="#"><i class="fa fa-cog" aria-hidden="true"></i></a>
		</li>
		<li class="logout-widget locked">
			<a href="?logout"><i class="fa fa-sign-out fa-rotate-180" aria-hidden="true"></i></a>
		</li>
	</ul>
</nav>

<div class="side-menu-widgets-container">
	<?php if(!empty($user_small_widgets)) { ?>
		<?php foreach($user_small_widgets as $small_widget) { ?>
			<div class="widget-extra-menu" id="menu_<?php echo $small_widget['rawname'];?>_<?php echo $small_widget['id'];?>" data-id="menu_<?php echo $small_widget['rawname'];?>_<?php echo $small_widget['id'];?>" data-widget_type_id="<?php echo $small_widget['id'];?>" data-module="<?php echo $small_widget['rawname']; ?>" data-name="<?php echo $small_widget['name']?>" data-widget_name="<?php echo $small_widget['widget_name']; ?>" data-icon="<?php echo $small_widget['icon']; ?>">
				<div class="menu-actions">
					<i class="fa fa-times-circle-o close-simple-widget-menu" aria-hidden="true"></i>
					<?php if($small_widget['hasSettings']) { ?>
						<i class="fa fa-cog show-simple-widget-settings" aria-hidden="true"></i>
					<?php } ?>
				</div>
				<h5 class="small-widget-title"><i class="fa"></i> <span></span> <small></small></h5>
				<div class="small-widget-content">
				</div>
				<button type="button" class="btn btn-xs btn-danger remove-small-widget" data-widget_id="<?php echo $small_widget['id']; ?>" data-widget_rawname="<?php echo $small_widget['rawname']; ?>"><?php echo _("Remove Widget")?></button>
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
