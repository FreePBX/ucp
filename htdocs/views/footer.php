		</div>
		<script>
			$(document).bind("mobileinit", function() {
				$.mobile.ignoreContentEnabled = true;
			});
			var languages = { locale_data : <?php echo $language ?> };
		</script>
		<script type="text/javascript" src="assets/js/compiled/main/<?php echo $gScripts?>"></script>
		<script type="text/javascript" src="assets/js/compiled/modules/<?php echo $scripts?>"></script>
		<script>
			var modules = <?php echo $modules?>;
			var desktop = <?php echo $desktop ? "true" : "false"?>;
			emojione.imagePathPNG = 'assets/images/emoji/png/';
			emojione.imagePathSVG = 'assets/images/emoji/svg/';
		</script>
		<div id="shade"></div>
	</body>
</html>
