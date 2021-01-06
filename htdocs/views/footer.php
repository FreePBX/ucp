<?php
	$version = get_framework_version();
	$version = $version ? $version : getversion();
	$benchmark_starttime = microtime_float();
	$benchmark_time = number_format(microtime_float() - $benchmark_starttime, 4);
?>
		<footer id="footer">
			<hr>
			<div id="footer-content" class="row">
				<div class="col-md-4">
					<a target="_blank" href="<?php echo $amp_conf['BRAND_IMAGE_FREEPBX_LINK_FOOT'] ?>"><img id="footer_logo1" src="./assets/images/freepbx_small.png?load_version=v15.0.6.26" alt="<?php echo $amp_conf['BRAND_FREEPBX_ALT_FOOT'] ?>"></a>
				</div>
				<div class="col-md-4" id="footer_text">
					<a href="http://www.freepbx.org" target="_blank">FreePBX</a> is a registered trademark of<br><a href="http://www.freepbx.org/copyright.html" target="_blank"> Sangoma Technologies Inc.</a><br>
					FreePBX <?php echo $version?> is licensed under the <a href="http://www.gnu.org/copyleft/gpl.html" target="_blank"> GPL</a><br><a href="http://www.freepbx.org/copyright.html" target="_blank">Copyright© 2007-2021</a>
					<br><span id="benchmark_time">Page loaded in <?php echo $benchmark_time ?>s</span>
				</div>
				<div class="col-md-4">
					<a target="_blank" href="<?php echo $amp_conf['BRAND_IMAGE_SPONSOR_LINK_FOOT'] ?>"><img id="footer_logo" src="./assets/images/sangoma-horizontal_thumb.png" alt="<?php echo $amp_conf['BRAND_SPONSOR_ALT_FOOT'] ?>"></a>
				</div>
			</div>		
		</footer>
		<script type="text/javascript" src="assets/js/jquery.toast.min.js"></script>
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
