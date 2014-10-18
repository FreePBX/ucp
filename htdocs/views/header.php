<!DOCTYPE html>
<html>
	<head>
		<title>User Control Panel</title>
		<meta http-equiv="x-pjax-version" content="<?php echo $version?>">
		<!--[if lt IE 9]>
			<script src="assets/js/html5shiv.js"></script>
		<![endif]-->

		<link href="assets/css/compiled/main/<?php echo $bootstrapcssless?>" rel="stylesheet" type="text/css">
		<link href="assets/css/compiled/main/<?php echo $facssless?>" rel="stylesheet" type="text/css">
		<link href="assets/css/compiled/main/<?php echo $sfcssless?>" rel="stylesheet" type="text/css">

		<link href="assets/css/bootstrap-select.min.css" rel="stylesheet" type="text/css">
		<link href="assets/css/emojione.min.css" rel="stylesheet" type="text/css">
		<link href="assets/css/jquery.tokenize.css" rel="stylesheet" type="text/css">
		<link href="assets/css/compiled/main/<?php echo $ucpcssless?>" rel="stylesheet" type="text/css">

		<link href="assets/css/compiled/modules/<?php echo $ucpmoduleless?>" rel="stylesheet" type="text/css">

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
		<script type="text/javascript" src="assets/js/jquery-1.11.1.min.js"></script>
	</head>
	<body>
	<div id="loading-container">
		<div class="message-container">
			<div class="message"><?php echo _("Loading")?></div>
		</div>
	</div>
	<div id="content-container">
