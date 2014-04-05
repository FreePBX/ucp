<!DOCTYPE html>
<html>
	<head>
		<title>User Control Panel</title>
		<meta http-equiv="x-pjax-version" content="<?php echo $version?>">
		<!--[if lt IE 9]>
			<script src="assets/js/html5shiv.js"></script>
		<![endif]-->

		<link href="assets/css/compiled/<?php echo $bootstrapcssless?>" rel="stylesheet" type="text/css">
		<link href="assets/css/compiled/<?php echo $facssless?>" rel="stylesheet" type="text/css">

		<link href="assets/framework/css/jquery-ui.css" rel="stylesheet" type="text/css">

		<script type="text/javascript" src="assets/framework/js/jquery-<?php echo $amp_conf['JQUERY_VER'] ?>.min.js"></script>


		<?php if($amp_conf['JQMIGRATE']) { ?>
			<script type="text/javascript" src="assets/framework/js/jquery-migrate-1.2.1.js"></script>
		<?php } ?>

		<link href="assets/css/bootstrap-select.min.css" rel="stylesheet" type="text/css">
		<link href="assets/css/compiled/<?php echo $ucpcssless?>" rel="stylesheet" type="text/css">
		<script type="text/javascript" src="assets/js/fileinput.js"></script>
		<script>
			$(document).bind("mobileinit", function() {
				$.mobile.ignoreContentEnabled = true;
			});
		</script>
		<script type="text/javascript" src="assets/js/recorder.js"></script>
		<script type="text/javascript" src="assets/framework/js/bootstrap-3.0.2.custom.min.js"></script>
		<script type="text/javascript" src="assets/framework/js/jquery-ui-1.10.3.custom.min.js"></script>
		<script type="text/javascript" src="assets/framework/js/jquery.cookie.js<?php echo $version_tag ?>"></script>
		<script type="text/javascript" src="assets/js/jquery.iframe-transport.js"></script>
		<script type="text/javascript" src="assets/js/jquery.fileupload.js"></script>
		<script type="text/javascript" src="assets/js/jquery.form.min.js"></script>
		<script type="text/javascript" src="assets/js/jquery.jplayer.min.js"></script>
		<script type="text/javascript" src="assets/js/quo.js"></script>
		<script type="text/javascript" src="assets/js/purl.js"></script>
		<script type="text/javascript" src="assets/js/modernizr.js"></script>
		<script type="text/javascript" src="assets/js/fastclick.js"></script>
		<script type="text/javascript" src="assets/js/jquery.pjax.js"></script>
		<script type="text/javascript" src="assets/js/notify.js"></script>
		<script type="text/javascript" src="assets/js/bootstrap-select.min.js"></script>
		<script type="text/javascript" src="assets/js/ucp.js"></script>
		<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,maximum-scale=1">
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<link rel="apple-touch-icon" href="assets/images/badge.png" />
		<link rel="apple-touch-icon-precomposed" href="assets/images/badge.png" />
		<!--[if gte IE 9]>
		  <style type="text/css">
		    .gradient {
		       filter: none;
		    }
		  </style>
		<![endif]-->
	</head>
	<body>
	<div id="binder"></div>
	<div id="content-container">
