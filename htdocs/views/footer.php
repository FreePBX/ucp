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
								<input type="text" class="form-control" id="dashboard_name" name="name" pattern=".{1,50}" required>
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

		<footer id="footer">
			<div id="footer-content" class="col-md-12">
				<?php echo $dashboard_footer_content?>
			</div
		</footer>
		<script>
			$(document).bind("mobileinit", function() {
				$.mobile.ignoreContentEnabled = true;
			});
			var languages = { locale_data : <?php echo $language ?> };
		</script>
		<script type="text/javascript" src="assets/js/compiled/main/<?php echo $gScripts?>"></script>
		<script src="assets/js/bootstrap-table-locale/bootstrap-table-en-US.js"></script>
		<?php if($lang != "en_US") {
			$html = '';
			switch($lang) {
				case "es_ES":
					$html .= '<script src="assets/js/bootstrap-table-locale/bootstrap-table-es-SP.js"></script>';
					$html .= "<script>$.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['es-SP']);</script>";
				break;
				default:
					$html .= '<script src="assets/js/bootstrap-table-locale/bootstrap-table-'.str_replace("_","-",$lang).'.js"></script>';
					$html .= "<script>$.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['".str_replace("_","-",$lang)."']);</script>";
				break;
			}
			echo $html;
		}?>
		<script type="text/javascript" src="assets/js/compiled/modules/<?php echo $scripts?>"></script>
		<script>
			var modules = <?php echo $modules?>;
			var desktop = <?php echo $desktop ? "true" : "false"?>;
			var ucpserver = <?php echo $ucpserver ?>;
			var timezone = '<?php echo $timezone ?>';
			var timeformat = '<?php echo $timeformat ?>';
			var dateformat = '<?php echo $dateformat ?>';
			var datetimeformat = '<?php echo $datetimeformat ?>';
			emojione.imagePathPNG = 'assets/images/emoji/png/';
			emojione.imagePathSVG = 'assets/images/emoji/svg/';
		</script>
		<div id="shade"></div>
	</body>
</html>
