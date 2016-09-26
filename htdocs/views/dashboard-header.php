<nav id="nav-bar-background" class="navbar navbar-default navbar-fixed-top">
	<div id="global-message-container">
		<div id="global-message"></div>
	</div>

	<div id="add_new_dashboard" class="add-dashboard">
		<button class="btn btn-sm btn-primary btn-outline" type="button" data-toggle="modal" data-target="#add_dashboard">
			<i class="fa fa-plus" aria-hidden="true"></i>
		</button>
	</div>

	<ul class="nav nav-tabs dashboards" role="tablist" id="all_dashboards">
		<?php foreach($user_dashboards as $dashboard_info) { ?>
			<li class="<?php echo ($dashboard_info["id"] == $active_dashboard) ? 'active' : ''?> menu-order dashboard-menu" data-id="<?php echo $dashboard_info["id"]?>">
				<a data-pjax href="?dashboard=<?php echo $dashboard_info["id"]?>"><?php echo $dashboard_info["name"]?> <div class="remove-dashboard" data-dashboard_id="<?php echo $dashboard_info["id"]?>"><i class="fa fa-times" aria-hidden="true"></i></div></a>
			</li>
		<?php } ?>
	</ul>
</nav>

<!-- left navbar -->
<nav class="navbar navbar-inverse navbar-fixed-left">
	<ul class="nav navbar-nav">
		<li class="add-widget" data-toggle="modal" data-target="#add_widget"><a href="#"><i class="fa fa-plus-circle" aria-hidden="true"></i></a></li>
		<li><a href="#"><i class="fa fa-user" aria-hidden="true"></i></a></li>
		<li><a href="#"><i class="fa fa-cogs" aria-hidden="true"></i></a></li>
		<li><a href="?logout"><i class="fa fa-sign-out" aria-hidden="true"></i></a></li>
	</ul>
</nav>

<div class="container-fluid main-content-object">
	<div class="row">
		<div class="col-md-12 col-sm-12 col-lg-12 main-content-column">
			<div id="dashboard-content">