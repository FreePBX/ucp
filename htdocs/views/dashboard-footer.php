					</div>
				</div>
			</div>
			<div id="messages-container">
			</div>
		</div>
	</div>

	<!-- Add dashboard Modal -->
	<div class="modal fade" id="add_dashboard" tabindex="-1" role="dialog" aria-labelledby="add_dashboard_label">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="add_dashboard_label">Add Dashboard</h4>
				</div>
				<div class="modal-body">
					<form id="add_dashboard_form" method="POST" action="quietmode=1&module=Dashboards&command=add">
						<div class="form-group">
							<label for="dashboard_name">Dashboard Name</label>
							<input type="text" class="form-control" id="dashboard_name" name="name" pattern=".{1,50}" autocomplete="off" required>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
					<button type="submit" id="create_dashboard" class="btn btn-primary">Create Dashboard</button>
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
					<h4 class="modal-title" id="add_widget_label">Add Widget</h4>
				</div>
				<div class="modal-body">
					<div class="col-lg-12 col-md-12 bhoechie-tab-container">
						<div class="col-lg-3 col-md-3 col-sm-3 col-xs-3 bhoechie-tab-menu">
							<?php if(!empty($all_widgets) && $all_widgets["status"]) { ?>

								<div class="list-group">
									<?php $first = true; foreach($all_widgets["widget"] as $widget_category_info){ ?>
										<a href="#" class="list-group-item text-center <?php echo ($first) ? "active" : ""; ?>">
											<h4 class="<?php echo $widget_category_info["icon"]; ?>"></h4><br/><?php echo $widget_category_info["display"]; ?>
										</a>
									<?php $first = false; } ?>
									<a href="#" class="list-group-item text-center">
										<h4 class="fa fa-list"></h4><br/>Side Bar Widgets
									</a>
								</div>
							<?php } ?>
						</div>
						<div class="col-lg-9 col-md-9 col-sm-9 col-xs-9 bhoechie-tab">

							<?php if(!empty($all_widgets) && $all_widgets["status"]) { ?>
								<?php $first = true; foreach($all_widgets["widget"] as $widget_category_info){ ?>
									<!-- flight section -->
									<div class="bhoechie-tab-content <?php echo ($first) ? "active" : ""; ?>">

										<?php if(!empty($widget_category_info["list"])) { ?>
											<?php foreach($widget_category_info["list"] as $widget_id => $widget_list){ ?>
												<div class="ibox-content-widget">
													<div class="row">
														<div class="widget-title col-md-11">
															<h4><?php echo $widget_list["display"]; ?>
																<br>
																<small class="m-r">Widget description</small>
															</h4>
														</div>
														<div class="widget-add-container top-offset text-center">
															<button type="button" class="btn btn-sm btn-primary btn-outline add-widget-button" data-widget_module_name="<?php echo $widget_category_info["display"]; ?>" data-widget_name="<?php echo $widget_list["display"]; ?>" data-widget_id="<?php echo $widget_id; ?>" data-rawname="<?php echo $widget_category_info["rawname"]; ?>" data-size_x="<?php echo $widget_list["defaultsize"]["width"]; ?>" data-size_y="<?php echo $widget_list["defaultsize"]["height"]; ?>"><i class="fa fa-plus-circle" aria-hidden="true"></i></button>
														</div>
													</div>
												</div>
											<?php } ?>
										<?php } ?>
									</div>
									<?php $first = false; } ?>
									<div class="bhoechie-tab-content <?php echo ($first) ? "active" : ""; ?>">

										<div class="ibox-content-widget">
											<div class="row">
												<div class="widget-title col-md-11">
													<h4>Small Widget 1
														<br>
														<small class="m-r">Small Widget description</small>
													</h4>
												</div>
												<div class="widget-add-container top-offset text-center">
													<button type="button" class="btn btn-sm btn-primary btn-outline add-widget-button" data-widget_module_name="smallwidget" data-widget_name="smallwidgetname" data-widget_id="smallwidgetid" data-rawname="smallwidgetrawname"><i class="fa fa-plus-circle" aria-hidden="true"></i></button>
												</div>
											</div>
										</div>
										<div class="ibox-content-widget">
											<div class="row">
												<div class="widget-title col-md-11">
													<h4>Small Widget 2
														<br>
														<small class="m-r">Small Widget description</small>
													</h4>
												</div>
												<div class="widget-add-container top-offset text-center">
													<button type="button" class="btn btn-sm btn-primary btn-outline add-widget-button" data-widget_module_name="smallwidget" data-widget_name="smallwidgetname" data-widget_id="smallwidgetid" data-rawname="smallwidgetrawname"><i class="fa fa-plus-circle" aria-hidden="true"></i></button>
												</div>
											</div>
										</div>
									</div>
							<?php } ?>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
				</div>
			</div>
		</div>
	</div>

	<!-- MODAL, ALERTS -->
	<div id="alert_modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="alert_label">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title" id="alert_label">Alert</h4>
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
							<button class="btn btn-primary btn-block" id="close_alert_button" type="button">Close</button>
						</div>
					</div>
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
					<h4 class="modal-title" id="confirm_message_title">Confirm action</h4>
				</div>
				<div class="modal-body">
					<div class="alert" role="alert" id="confirm_content">

					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
					<button type="button" class="btn btn-primary" data-dismiss="modal" id="modal_confirm_button">Accept</button>
				</div>
			</div>
		</div>
	</div>