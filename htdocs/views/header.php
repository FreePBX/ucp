<!DOCTYPE html>
<html>
	<head>
		<title>User Control Panel</title>
		<!--[if lt IE 9]>
			<script src="assets/js/html5shiv.js"></script>
		<![endif]-->
		<?php if ($amp_conf['USE_GOOGLE_CDN_JS']) { ?>
			<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/<?php echo $amp_conf['BOOTSTRAP_VER']?>/css/bootstrap.min.css">
		<?php } else { ?>
			<link href="global/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
		<?php } ?>
		
		<link href="global/assets/css/bootstrap-fixes.css" rel="stylesheet" type="text/css" />
		
		<link rel="stylesheet" href="global/assets/css/font-awesome.min.css">

		<link href="global/<?php echo $framework_css.$version_tag.$css_ver?>" rel="stylesheet" type="text/css">

		<?php if ($amp_conf['DISABLE_CSS_AUTOGEN'] == true) { ?>
			<link href="global/<?php echo $amp_conf['JQUERY_CSS'] . $version_tag?>" rel="stylesheet" type="text/css">
		<?php } ?>
		
		<?php if ($use_popover_css) { ?>
			<link href="global/<?php echo $popover_css.$version_tag ?>" rel="stylesheet" type="text/css">
		<?php } ?>

		<?php if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('he_IL'))) { ?>
			<link href="global/assets/css/mainstyle-rtl.css" rel="stylesheet" type="text/css" />
		<?php } ?>

		<?php if ($amp_conf['BRAND_CSS_CUSTOM']) { ?>
			<link href="global/<?php echo $amp_conf['BRAND_CSS_CUSTOM'] . $version_tag ?>" rel="stylesheet" type="text/css">
		<?php } ?>

		<link href="assets/css/ucp.css<?php echo $version_tag ?>" rel="stylesheet" type="text/css">

		<?php if ($amp_conf['USE_GOOGLE_CDN_JS']) { ?>
			<script src="//ajax.googleapis.com/ajax/libs/jquery/<?php echo $amp_conf['JQUERY_VER'] ?>/jquery.min.js"></script>
			<script>window.jQuery || document.write('<script src="assets/js/jquery-<?php echo $amp_conf['JQUERY_VER'] ?>.min.js"></script>')</script>
		<?php } else { ?>
			<script type="text/javascript" src="global/assets/js/jquery-<?php echo $amp_conf['JQUERY_VER'] ?>.min.js"></script>
		<?php } ?>

		
		<?php if($amp_conf['JQMIGRATE']) { ?>
			<script type="text/javascript" src="global/assets/js/jquery-migrate-1.2.1.js"></script>
		<?php } ?>
	</head>
	<body>