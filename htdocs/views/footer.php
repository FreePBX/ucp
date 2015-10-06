		</div>
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
			emojione.imagePathPNG = 'assets/images/emoji/png/';
			emojione.imagePathSVG = 'assets/images/emoji/svg/';
		</script>
		<div id="shade"></div>
	</body>
</html>
