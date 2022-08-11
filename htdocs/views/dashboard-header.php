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
					<a data-dashboard class="<?php echo ($dashboard_info["id"] == $active_dashboard) ? 'pjax-block' : ''?>"><?php echo $dashboard_info["name"]?></a>
					<div class="dashboard-actions" data-dashboard_id="<?php echo $dashboard_info["id"]?>">
						<i class="fa fa-unlock-alt lock-dashboard" aria-hidden="true"></i>
						<i class="fa fa-pencil edit-dashboard" aria-hidden="true"></i>
						<i class="fa fa-times remove-dashboard" aria-hidden="true"></i>
					</div>
				</li>
			<?php } ?>
		<?php } ?>
	</ul>
	<div class="ucp__logo">
		<?php global $amp_conf; ?>
		<a href="<?php echo $amp_conf['BRAND_IMAGE_FREEPBX_LINK_LEFT']; ?>" target="_blank" >
			<img src="<?php echo isset($baseUrl) ? $baseUrl : "" ; ?>/admin/<?= $amp_conf['BRAND_IMAGE_TANGO_LEFT'] ?>" alt="<?= $amp_conf['DASHBOARD_FREEPBX_BRAND'] ?>">
		</a>
	</div>
</nav>

<!-- left navbar -->
<nav class="navbar navbar-inverse navbar-fixed-left">
	<ul class="nav navbar-nav" id="side_bar_content">
		<li class="add-widget first-widget locked" data-toggle="modal" data-target="#add_widget"><a href="#"><i class="fa fa-plus-circle" aria-hidden="true"></i></a></li>
		<?php if(!empty($user_small_widgets)) { ?>
			<?php foreach($user_small_widgets as $small_widget) { ?>
				<?php
					$regenuuid = '';
					if(!preg_match('/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i',$small_widget['id'])) {
						$small_widget['widget_type_id'] = $small_widget['id'];
						$small_widget['id'] = (string)\Ramsey\Uuid\Uuid::uuid4();
						$regenuuid = 'data-regenuuid="true"';
					}
				?>
				<li class="custom-widget" data-widget_id="<?php echo $small_widget['id']; ?>" data-widget_rawname="<?php echo $small_widget['rawname']; ?>" data-widget_type_id="<?php echo $small_widget['widget_type_id']?>">
					<a href="#" title="<?php echo $small_widget['rawname']." ".$small_widget['widget_type_id']; ?>" data-module_name="<?php echo $small_widget['module_name']; ?>" <?php echo $regenuuid?> data-id="<?php echo $small_widget['id']; ?>" data-widget_type_id="<?php echo $small_widget['widget_type_id']?>" data-name="<?php echo $small_widget['name']; ?>" data-rawname="<?php echo $small_widget['rawname']; ?>" data-icon="<?php echo $small_widget['icon']; ?>">
						<i class="<?php echo $small_widget['icon']; ?>" aria-hidden="true"></i>
					</a>
				</li>
			<?php } ?>
		<?php } ?>
		<input type="hidden" id="userID" value="<?php echo $user['id'] ?>">
		<?php if($displaySaveTemplate) { ?>
			<input type="hidden" id="templateID" value="<?php echo $templateId ?>">
			<li class="save-widget">
				<a href="#" id= "saveTemplate" title="Save Template"><i class="fa fa-save" aria-hidden="true"></i></a>
			</li>
		<?php } else { ?>
			<li class="reset-widget">
				<a href="#" id= "resetTemplate" title="Reset Template"><i class="fa fa-undo" aria-hidden="true"></i></a>
			</li>
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
			<div class="widget-extra-menu" id="menu_<?php echo $small_widget['id'];?>" data-id="<?php echo $small_widget['id'];?>" data-widget_type_id="<?php echo $small_widget['widget_type_id'];?>" data-module="<?php echo $small_widget['rawname']; ?>" data-name="<?php echo $small_widget['name']?>" data-widget_name="<?php echo $small_widget['widget_name']; ?>" data-icon="<?php echo $small_widget['icon']; ?>">
				<div class="menu-actions">
					<i class="fa fa-times-circle-o close-simple-widget-menu" aria-hidden="true"></i><?php if($small_widget['hasSettings']) { ?><i class="fa fa-cog show-simple-widget-settings" aria-hidden="true"></i><?php } ?>
				</div>
				<h5 class="small-widget-title"><i class="fa <?php echo $small_widget['icon']?>"></i> <span><?php echo $small_widget['widget_name']?></span> <small>(<?php echo $small_widget['name']?>)</small></h5>
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
