					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Add dashboard Modal -->
	<div class="modal fade" id="add_dashboard" tabindex="-1" role="dialog" aria-labelledby="add_dashboard_label">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="add_dashboard_label"><i class="fa fa-plus-circle" aria-hidden="true"></i> <?php echo _("Add Dashboard")?></h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label for="dashboard_name"><?php echo _("Dashboard Name")?></label>
						<input type="text" class="form-control" id="dashboard_name" name="name" pattern=".{1,50}" autocomplete="off" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo _("Cancel")?></button>
					<button type="submit" id="create_dashboard" class="btn btn-primary"><?php echo _("Create Dashboard")?></button>
				</div>
			</div>
		</div>
	</div>
	<!-- Edit dashboard Modal -->
	<div class="modal fade" id="edit_dashboard" tabindex="-1" role="dialog" aria-labelledby="edit_dashboard_label">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="edit_dashboard_label"><i class="fa fa-pencil" aria-hidden="true"></i> <?php echo _("Edit Dashboard")?></h4>
				</div>
				<div class="modal-body">
					<div class="form-group">
						<label for="dashboard_name"><?php echo _("Dashboard Name")?></label>
						<input type="text" class="form-control" id="edit_dashboard_name" name="name" pattern=".{1,50}" autocomplete="off" required>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo _("Cancel")?></button>
					<button type="submit" id="edit_dashboard_btn" class="btn btn-primary"><?php echo _("Edit Dashboard")?></button>
				</div>
			</div>
		</div>
	</div>
	<!-- Add Widget Modal -->
	<div class="modal fade" id="add_widget" tabindex="-1" role="dialog" aria-labelledby="add_widget_label">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="add_widget_label"><i class="fa fa-plus-circle" aria-hidden="true"></i> <?php echo _("Add Widget")?></h4>
				</div>
				<div class="modal-body">
					<ul id="tabs" class="nav nav-tabs" data-tabs="tabs">
						<li class="active"><a href="#red" data-toggle="tab"><?php echo _("Dashboard Widgets")?></a></li>
						<li class=""><a href="#small" data-toggle="tab"><?php echo _("Side Bar Widgets")?></a></li>
					</ul>
					<div class="tab-content">
						<div class="tab-pane active" id="red">
							<div class="row">
								<div class="col-lg-12 col-md-12 bhoechie-tab-container">
									<div class="col-lg-3 col-md-3 col-sm-3 col-xs-3 bhoechie-tab-menu">
										<?php if(!empty($all_widgets) && $all_widgets["status"]) { ?>
											<div class="list-group">
												<?php $first = true; foreach($all_widgets["widget"] as $widget_category_info){ ?>
													<a href="#" class="list-group-item text-center <?php echo ($first) ? "active" : ""; ?>" data-id="dashboard-<?php echo $widget_category_info['rawname']?>">
														<h4 class="<?php echo $widget_category_info["icon"]; ?>"></h4><br/><?php echo $widget_category_info["display"]; ?>
													</a>
												<?php $first = false; } ?>
											</div>
										<?php } ?>
									</div>
									<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 bhoechie-tab">

										<?php if(!empty($all_widgets) && $all_widgets["status"]) { ?>
											<?php $first = true; foreach($all_widgets["widget"] as $widget_category_info){ ?>
												<!-- flight section -->
												<div class="bhoechie-tab-content <?php echo ($first) ? "active" : ""; ?>" data-id="dashboard-<?php echo $widget_category_info['rawname']?>">

													<?php if(!empty($widget_category_info["list"])) { ?>
														<?php foreach($widget_category_info["list"] as $widget_id => $widget_list){ ?>
															<div class="ibox-content-widget">
																<div class="row">
																	<div class="widget-title col-md-11">
																		<h4><i class="<?php echo isset($widget_list["icon"]) ? $widget_list["icon"] : $widget_category_info['icon'] ?>"></i> <?php echo $widget_list["display"]; ?>
																			<br>
																			<small class="m-r"><?php echo isset($widget_list["description"])?$widget_list["description"]:''?></small>
																		</h4>
																	</div>
																	<div class="widget-add-container top-offset text-center">
																		<button type="button" class="btn btn-sm btn-primary btn-outline add-widget-button" data-widget_module_name="<?php echo $widget_category_info["display"]; ?>" data-widget_name="<?php echo $widget_list["display"]; ?>" data-widget_id="<?php echo $widget_id; ?>" data-rawname="<?php echo $widget_category_info["rawname"]; ?>"><i class="fa fa-plus-circle" aria-hidden="true"></i></button>
																	</div>
																</div>
															</div>
														<?php } ?>
													<?php } ?>
												</div>
												<?php $first = false; } ?>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
						<div class="tab-pane" id="small">
							<div class="row">
								<div class="col-lg-12 col-md-12 bhoechie-tab-container">
									<div class="col-lg-3 col-md-3 col-sm-3 col-xs-3 bhoechie-tab-menu">
										<div class="list-group">
											<?php $first = true; foreach($all_simple_widgets["widget"] as $widget_category_info){ ?>
												<a href="#" class="list-group-item text-center <?php echo ($first) ? "active" : ""; ?>" data-id="small-<?php echo $widget_category_info['rawname']?>">
													<h4 class="<?php echo $widget_category_info["icon"]; ?>"></h4><br/><?php echo $widget_category_info["display"]; ?>
												</a>
											<?php $first = false; } ?>
										</div>
									</div>
									<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 bhoechie-tab">
										<?php if(!empty($all_simple_widgets) && $all_simple_widgets["status"]) { ?>
											<?php $first = true; foreach($all_simple_widgets["widget"] as $widget_category_info){?>
												<div class="bhoechie-tab-content <?php echo ($first) ? "active" : ""; ?>" data-id="small-<?php echo $widget_category_info['rawname']?>">
												<?php if(!empty($widget_category_info["list"])) { ?>
													<?php foreach($widget_category_info["list"] as $widget_id => $widget_list){ ?>
														<div class="ibox-content-widget">
															<div class="row">
																<div class="widget-title col-md-11">
																	<h4><i class="<?php echo $widget_category_info['icon'] ?>"></i> <?php echo $widget_list["display"]; ?>
																		<br>
																		<small class="m-r"><?php echo isset($widget_list["description"])?$widget_list["description"]:''?></small>
																	</h4>
																</div>
																<div class="widget-add-container top-offset text-center">
																	<button type="button" class="btn btn-sm btn-primary btn-outline add-small-widget-button" <?php echo !empty($widget_list['hasSettings']) ? 'data-widget_settings="true"' : ''?> data-module_name="<?php echo $widget_category_info["display"]; ?>" data-name="<?php echo $widget_category_info["display"]; ?>" data-widget_type_id="<?php echo $widget_list['display']?>" data-id="<?php echo $widget_id; ?>" data-rawname="<?php echo $widget_category_info["rawname"]; ?>" data-icon="<?php echo $widget_category_info["icon"]; ?>"><i class="fa fa-plus-circle" aria-hidden="true"></i></button>
																</div>
															</div>
														</div>
													<?php } ?>
												<?php } ?>
												</div>
											<?php $first = false; } ?>
										<?php } ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo _('Cancel')?></button>
				</div>
			</div>
		</div>
	</div>
	<!-- widget settings -->
	<div class="modal fade" id="widget_settings" tabindex="-1" role="dialog" aria-labelledby="widget_settings_label">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><i class="fa fa-times" aria-hidden="true"></i></button>
					<h4 class="modal-title" id="widget_settings_label"><i class="fa fa-cog" aria-hidden="true"></i> <?php echo _("Widget Settings")?></h4>
				</div>
				<div class="modal-body widget-settings-content"></div>
			</div>
		</div>
	</div>

	<!-- MODAL, ALERTS -->
	<!-- Global empty Modal -->
	<div class="modal fade" id="globalModal" role="dialog" aria-hidden="true">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
					<h4 class="modal-title"></h4>
				</div>
				<div class="modal-body"></div>
				<div class="modal-footer">
				</div>
			</div>
		</div>
	</div>

	<!-- Confirm Modal -->
	<div class="modal fade" id="confirm_modal" tabindex="-1" role="dialog" aria-labelledby="confirm_message_title">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="confirm_message_title"><i class="fa fa-question-circle" aria-hidden="true"></i> <?php echo _("Confirm Action")?></h4>
				</div>
				<div class="modal-body">
					<div class="alert" role="alert" id="confirm_content">

					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal"><?php echo _("Cancel")?></button>
					<button type="button" class="btn btn-primary" data-dismiss="modal" id="modal_confirm_button"><?php echo _("Accept")?></button>
				</div>
			</div>
		</div>
	</div>

	<!-- Alert Modal -->
	<div id="alert_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="alert_label">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="alert_label"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i> <?php echo _("Alert")?></h4>
				</div>
				<div class="modal-body">
					<div class="row">
						<div class="col-md-12">
							<div class="alert" role="alert" id="alert_message"></div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<div class="row">
						<div class="col-sm-4 col-sm-offset-4">
							<button class="btn btn-primary btn-block" id="close_alert_button" type="button"><?php echo _("Close")?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="messages-container">
	</div>
