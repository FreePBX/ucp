		</div>
		<script>
			$(document).bind("mobileinit", function() {
				$.mobile.ignoreContentEnabled = true;
			});
			var languages = { locale_data : <?php echo $language ?> };
		</script>
		<script type="text/javascript" src="assets/js/<?php echo $gScripts?>"></script>
		<script type="text/javascript" src="assets/js/compiled/<?php echo $scripts?>"></script>
		<script>var modules = <?php echo $modules?></script>
		<div id="shade"></div>
	</body>
</html>
