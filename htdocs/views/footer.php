		<script src="global/assets/js/bootstrap-3.0.2.custom.min.js"></script>
		<script type="text/javascript" src="global/assets/js/jquery-ui-1.10.3.custom.min.js"></script>
		<?php if ($amp_conf['USE_PACKAGED_JS'] && file_exists("global/assets/js/pbxlib.js")) {?>
			<script type="text/javascript" src="global/assets/js/pbxlib.js'<?php echo $version_tag . '.' . filectime("global/assets/js/pbxlib.js") ?>"></script>
		<?php } else {?>
			<script type="text/javascript" src="global/assets/js/menu.js<?php echo $version_tag ?>"></script>
			<script type="text/javascript" src="global/assets/js/jquery.hotkeys.js<?php echo $version_tag ?>"></script>
			<script type="text/javascript" src="global/assets/js/jquery.cookie.js<?php echo $version_tag ?>"></script>
			<script type="text/javascript" src="global/assets/js/script.legacy.js<?php echo $version_tag ?>"></script>
			<script type="text/javascript" src="global/assets/js/jquery.toggleval.3.0.js<?php echo $version_tag ?>"></script>
			<script type="text/javascript" src="global/assets/js/tabber-minimized.js<?php echo $version_tag ?>"></script>
		<?php } ?>
		
		<?php if ($amp_conf['BRAND_ALT_JS']) { ?>
			<script type="text/javascript" src="global/<?php echo $amp_conf['BRAND_ALT_JS'] . $version_tag ?>"></script>
		<?php } ?>
		<script type="text/javascript">var fpbx = {"conf":{"need_reload":false}}</script>
	</body>
</html>