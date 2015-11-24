<!DOCTYPE html>
<html>
	<head>
		<title><?php echo _('User Control Panel')?></title>
		<meta http-equiv="x-pjax-version" content="<?php echo $version?>">
		<?php if($shiv) {?>
		<!--[if lt IE 9]>
			<script src="assets/js/html5shiv.js"></script>
		<![endif]-->
		<?php } ?>

		<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
		<link href="assets/css/font-awesome.min.css" rel="stylesheet" type="text/css">
		<link href="assets/css/compiled/main/<?php echo $sfcssless?>" rel="stylesheet" type="text/css">

		<link href="assets/css/bootstrap-select.min.css" rel="stylesheet" type="text/css">
		<link href="assets/css/emojione.min.css" rel="stylesheet" type="text/css">
		<link href="assets/css/jquery.tokenize.css" rel="stylesheet" type="text/css">
		<link href="assets/css/compiled/main/<?php echo $ucpcssless?>" rel="stylesheet" type="text/css">

		<link href="assets/css/compiled/modules/<?php echo $ucpmoduleless?>" rel="stylesheet" type="text/css">

		<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,maximum-scale=1">
		<!-- Apple Specific -->
		<meta name="apple-mobile-web-app-capable" content="yes" />

		<link rel="apple-touch-icon" href="<?php echo $iconsdir?>/60x60.png"> <!-- 60 x 60 -->
		<link rel="apple-touch-icon" sizes="76x76" href="<?php echo $iconsdir?>/76x76.png">
		<link rel="apple-touch-icon" sizes="120x120" href="<?php echo $iconsdir?>/120x120.png">
		<link rel="apple-touch-icon" sizes="152x152" href="<?php echo $iconsdir?>/152x152.png">

		<link rel="apple-touch-startup-image" href="<?php echo $iconsdir?>/320x480.png">

		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<!-- End Apple Specific -->

		<link href="<?php echo $iconsdir?>/192x192.png" rel="icon" sizes="192x192" />
		<link href="<?php echo $iconsdir?>/128x128.png" rel="icon" sizes="128x128" />
		<!-- apple does not use this but android does -->
		<link rel="apple-touch-icon-precomposed" sizes="128x128" href="<?php echo $iconsdir?>/128x128.png">
		<link rel="apple-touch-icon" sizes="128x128" href="<?php echo $iconsdir?>/128x128.png">

		<meta name="mobile-web-app-capable" content="yes">
		<?php if($shiv) {?>
		<!--[if gte IE 9]>
		  <style type="text/css">
		    .gradient {
		       filter: none;
		    }
		  </style>
		<![endif]-->
		<?php } ?>
		<script type="text/javascript" src="assets/js/jquery-1.11.3.min.js"></script>
		<!-- Display hack for localization on checkbox switches -->
		<style>
			.onoffswitch-inner:before {
					content: "<?php echo _("ON")?>";
			}
			.onoffswitch-inner:after {
					content: "<?php echo _("OFF")?>";
			}
		</style>
	</head>
	<body>
	<div id="loading-container">
		<div class="message-container">
			<div class="message"><?php echo _("Loading")?></div>
		</div>
	</div>
	<div id="content-container">
