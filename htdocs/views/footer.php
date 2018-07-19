
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
		<?php foreach($gScripts as $file) { ?>
			<script type="text/javascript" src="assets/js/<?php echo $file.$version_tag?>"></script>
		<?php } ?>
		<script src="assets/js/bootstrap-table-locale/bootstrap-table-en-US.js<?php echo $version_tag?>"></script>
		<script src="assets/js/ajax-bootstrap-select-locale/ajax-bootstrap-select.en-US.js<?php echo $version_tag?>"></script>
		<?php if($lang != "en_US") {
			$html = '';
			switch($lang) {
				case "es_ES":
					$html .= '<script src="assets/js/bootstrap-table-locale/bootstrap-table-es-SP.js'.$version_tag.'"></script>';
					$html .= "<script>$.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['es-SP']);</script>";
				break;
				default:
					$html .= '<script src="assets/js/bootstrap-table-locale/bootstrap-table-'.str_replace("_","-",$lang).'.js'.$version_tag.'"></script>';
					$html .= "<script>$.extend($.fn.bootstrapTable.defaults, $.fn.bootstrapTable.locales['".str_replace("_","-",$lang)."']);</script>";
				break;
			}
			echo $html;
		}?>
		<?php foreach($scripts as $file) { ?>
			<script type="text/javascript" src="<?php echo $file.$version_tag?>"></script>
		<?php } ?>
		<?php if(!empty($user)) {?>
			<script>
				var modules = <?php echo $modules?>;
				var desktop = <?php echo $desktop ? "true" : "false"?>;
				var ucpserver = <?php echo $ucpserver ?>;
				var timezone = '<?php echo $timezone ?>';
				var language = '<?php echo FreePBX::View()->getLocale()?>';
				moment.locale(language);
				var UIDEFAULTLANG = '<?php echo FreePBX::Config()->get('UIDEFAULTLANG')?>';
				var PHPTIMEZONE = '<?php echo FreePBX::Config()->get('PHPTIMEZONE')?>';
				var timeformat = '<?php echo $timeformat ?>';
				var dateformat = '<?php echo $dateformat ?>';
				var datetimeformat = '<?php echo $datetimeformat ?>';
				var moduleSettings = <?php echo json_encode($moduleSettings)?>;
				var dashboards = <?php echo !empty($dashboards_info) ? json_encode($dashboards_info) : '{}'?>;
				var allWidgets = <?php echo json_encode($all_widgets['widget'])?>;
				var allSimpleWidgets = <?php echo json_encode($all_simple_widgets['widget'])?>;
				emojione.imagePathSVG = 'assets/images/emoji/svg/';
				emojione.imageType = 'svg';
			</script>
		<?php } ?>
		<div id="shade"></div>
	</body>
</html>
