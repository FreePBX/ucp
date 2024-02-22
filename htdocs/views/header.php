<!DOCTYPE html>
<html>

<head>
	<title>
		<?php echo _('User Control Panel') ?>
	</title>
	<meta http-equiv="x-pjax-version" content="<?php echo $version ?>">
	<?php if ($shiv) { ?>
		<!--[if lt IE 9]>
			<script src="assets/js/html5shiv.js"></script>
		<![endif]-->
	<?php } ?>
	<link href="assets/css/jquery.toast.min.css" rel="stylesheet" type="text/css">
	<?php foreach ($ucpcss as $file) { ?>
		<link href="assets/css/<?php echo $file . $version_tag ?>" rel="stylesheet" type="text/css">
	<?php } ?>
	<link href="assets/css/compiled/modules/<?php echo $ucpmoduleless . $version_tag ?>" rel="stylesheet"
		type="text/css">

	<link rel="icon" type="image/png" href="<?php echo $iconsdir ?>/16x16.png">

	<meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1,maximum-scale=1">
	<!-- Apple Specific -->
	<meta name="apple-mobile-web-app-capable" content="yes" />

	<link rel="apple-touch-icon" href="<?php echo $iconsdir ?>/60x60.png"> <!-- 60 x 60 -->
	<link rel="apple-touch-icon" sizes="76x76" href="<?php echo $iconsdir ?>/76x76.png">
	<link rel="apple-touch-icon" sizes="120x120" href="<?php echo $iconsdir ?>/120x120.png">
	<link rel="apple-touch-icon" sizes="152x152" href="<?php echo $iconsdir ?>/152x152.png">

	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<!-- End Apple Specific -->

	<link href="<?php echo $iconsdir ?>/192x192.png" rel="icon" sizes="192x192" />
	<link href="<?php echo $iconsdir ?>/128x128.png" rel="icon" sizes="128x128" />
	<!-- apple does not use this but android does -->
	<link rel="apple-touch-icon-precomposed" sizes="128x128" href="<?php echo $iconsdir ?>/128x128.png">
	<link rel="apple-touch-icon" sizes="128x128" href="<?php echo $iconsdir ?>/128x128.png">

	<meta name="mobile-web-app-capable" content="yes">
	<?php if ($shiv) { ?>
		<!--[if gte IE 9]>
		  <style type="text/css">
			.gradient {
			   filter: none;
			}
		  </style>
		<![endif]-->
	<?php } ?>
	<script type="text/javascript" src="assets/js/jquery-3.6.0.min.js<?php echo $version_tag ?>"></script>
	<!-- Display hack for localization on checkbox switches -->
	<style>
		.onoffswitch-inner:before {
			content: "<?php echo _("ON") ?>";
		}

		.onoffswitch-inner:after {
			content: "<?php echo _("OFF") ?>";
		}
	</style>
</head>

<body>
	<div class="main-block">
		<span class="fa-stack fa-5x">
			<i class="fa fa-cloud fa-stack-2x text-internal-blue"></i>
			<i class="fa fa-cog fa-spin fa-stack-1x secundary-color"></i>
		</span>
	</div>
	<div class="settings-shown-blocker">
	</div>

	<!-- small device nav menu -->
	<nav class="pushmenu pushmenu-left">
		<?php if ($user) { ?>
			<h3>
				<?php echo sprintf(_('Welcome %s'), (!empty($user['fname']) ? $user['fname'] : $user['username'])) ?>
			</h3>
		<?php } ?>
		<ul>
			<?php foreach ($menu as $module) { ?>
				<li data-mod="<?php $active_module ??= '';
				echo $module['rawname'] ?>" class="<?php echo ($module['rawname'] == $active_module) ? 'active' : '' ?>">
					<?php if (empty($module['menu'])) { ?>
						<a data-pjax data-mod="<?php echo $module['rawname'] ?>"
							href="?display=dashboard&amp;mod=<?php echo $module['rawname'] ?>"><?php echo $module['name'] ?? ''; ?>
							<?php if (isset($module['badge'])) { ?><span class="badge">
								<?php echo $module['badge'] ?>
							</span>
						<?php } ?>
						</a>
					<?php }
					else { ?>
						<a class="mobileSubMenu" data-mod="<?php echo $module['rawname'] ?>"><?php echo $module['name'] ?? ''; ?> 		<?php if (isset($module['badge'])) { ?><span class="badge">
									<?php echo $module['badge'] ?>
								</span>
							<?php } ?>
						</a>
						<ul data-mod="<?php echo $module['rawname'] ?>" id="submenu-<?php echo $module['rawname'] ?>"
							class="dropdown-pushmenu">
							<?php foreach ($module['menu'] as $smenu) { ?>
								<li>
									<a data-mod="<?php echo $module['rawname'] ?>" data-pjax
										href="?display=dashboard&amp;mod=<?php echo $module['rawname'] ?>&amp;sub=<?php echo $smenu['rawname'] ?>"><?php echo (strlen((string) $smenu['name']) > 20) ? substr((string) $smenu['name'], 0, 20) . '...' : $smenu['name'] ?> 			<?php if (isset($smenu['badge'])) { ?><span class="badge">
												<?php echo $smenu['badge'] ?>
											</span>
										<?php } ?>
									</a>
								</li>
							<?php } ?>
						</ul>
					<?php } ?>
				</li>
			<?php } ?>
		</ul>
	</nav>