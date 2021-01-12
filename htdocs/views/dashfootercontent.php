<?php
	$version = get_framework_version();
	$version = $version ? $version : getversion();
	$benchmark_starttime = microtime_float();
	$benchmark_time = number_format(microtime_float() - $benchmark_starttime, 4);
?>
<div class="col-md-4">
	<a target="_blank" href="<?php echo $amp_conf['BRAND_IMAGE_FREEPBX_LINK_FOOT'] ?>"><img id="footer_logo1" src="./assets/images/freepbx_small.png?load_version=v15.0.6.26" alt="<?php echo $amp_conf['BRAND_FREEPBX_ALT_FOOT'] ?>"></a>
</div>
<div class="col-md-4" id="footer_text">
	<a href="http://www.freepbx.org" target="_blank">FreePBX</a> is a registered trademark of<br><a href="http://www.freepbx.org/copyright.html" target="_blank"> Sangoma Technologies Inc.</a><br>
	FreePBX <?php echo $version?> is licensed under the <a href="http://www.gnu.org/copyleft/gpl.html" target="_blank"> GPL</a><br><a href="http://www.freepbx.org/copyright.html" target="_blank">Copyright  2007-2021</a>
	<br><span id="benchmark_time">Page loaded in <?php echo $benchmark_time ?>s</span>
</div>
<div class="col-md-4">
	<a target="_blank" href="<?php echo $amp_conf['BRAND_IMAGE_SPONSOR_LINK_FOOT'] ?>"><img id="footer_logo" src="./assets/images/sangoma-horizontal_thumb.png" alt="<?php echo $amp_conf['BRAND_SPONSOR_ALT_FOOT'] ?>"></a>
</div>
