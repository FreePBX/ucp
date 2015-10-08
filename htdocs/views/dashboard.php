<!-- small device nav menu -->
<nav class="pushmenu pushmenu-left">
	<h3><?php echo sprintf(_('Welcome %s'),(!empty($user['fname']) ? $user['fname'] : $user['username']))?></h3>
	<ul>
		<?php foreach($menu as $module) {?>
		<li data-mod="<?php echo $module['rawname']?>" class="<?php echo ($module['rawname'] == $active_module) ? 'active' : ''?>">
			<?php if(empty($module['menu'])) {?>
	    		<a data-pjax data-mod="<?php echo $module['rawname']?>" href="?display=dashboard&amp;mod=<?php echo $module['rawname']?>"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span class="badge"><?php echo $module['badge']?></span><?php } ?></a>
			<?php } else {?>
	    		<a class="mobileSubMenu" data-mod="<?php echo $module['rawname']?>"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span class="badge"><?php echo $module['badge']?></span><?php } ?></a>
				<ul data-mod="<?php echo $module['rawname']?>" id="submenu-<?php echo $module['rawname']?>" class="dropdown-pushmenu">
					<?php foreach($module['menu'] as $smenu) {?>
						<li>
							<a data-mod="<?php echo $module['rawname']?>" data-pjax href="?display=dashboard&amp;mod=<?php echo $module['rawname']?>&amp;sub=<?php echo $smenu['rawname']?>"><?php echo (strlen($smenu['name']) > 20) ? substr($smenu['name'],0,20).'...' : $smenu['name']?> <?php if(isset($smenu['badge'])) {?><span class="badge"><?php echo $smenu['badge']?></span><?php } ?></a>
						</li>
					<?php } ?>
				</ul>
			<?php } ?>
		</li>
		<?php } ?>
	</ul>
</nav>
<!-- end small device nav menu -->
<div id="dashboard" class="pushmenu-push dashboard-container center-box">
	<!-- This navigation pane hides when the viewport is smaller than 768 -->
	<div id="nav-bar-background">
		<div id="global-message-container">
			<div id="global-message"></div>
		</div>
		<div id="bc-mobile-icon"><i class="fa fa-bars"></i></div>
		<ol id="top-dashboard-nav" class="breadcrumb">
			<li class="home"><a data-mod="home" data-pjax href="?display=dashboard&amp;mod=home"><?php echo _('Home')?></a></li>
			<?php if($active_module != 'home' && !empty($menu[$active_module])) {?>
				<li class="module bc-<?php echo $menu[$active_module]['rawname']?> active"><?php echo $menu[$active_module]['name']?></li>
				<?php if(!empty($_REQUEST['sub'])) {?>
					<li class="subsection bc-<?php echo $_REQUEST['sub']?> active"><?php echo $_REQUEST['sub']?></li>
				<?php } ?>
			<?php } elseif($active_module == 'ucpsettings') { ?>
				<li class="bc-ucpsettings active"><?php echo _('Settings')?></li>
		  <?php } ?>
		</ol>
		<div id="top-dashboard-nav-right">
			<div class="nav-btns">
				<?php foreach($navItems as $button) {?>
					<div id="nav-btn-<?php echo $button['rawname']?>" class="module-container <?php echo (!empty($button['hide']) ? 'hidden' : '')?>" data-module="<?php echo $button['rawname']?>">
						<div class="icon">
							<i class="<?php echo preg_match("/^fa-/",$button['icon']) ? "fa ". $button['icon'] : $button['icon']?>"></i>
							<?php echo !empty($button['badge']) ? '<span class="badge">'.$button['badge'].'</span>' : '<span class="badge" style="display:none">0</span>'?>
						</div>
						<?php echo isset($button['extra']) ? $button['extra'] : ""?>
					</div>
				<?php } ?>
			</div>
		</div>
	</div>
	<div class="nav-menus">
		<?php foreach($navItems as $module => $item) {
			if (!empty($item['menu']['html'])) {?>
				<ol id="<?php echo $item['rawname']?>-menu" class="nav-btn-menu" data-module="<?php echo $item['rawname']?>">
					<?php echo $item['menu']['html'] ?>
				</ol>
		<?php } } ?>
	</div>
	<div class="clear"></div>
	<div id="container-fixed-left" class="container-fluid">
		<div class="row">
			<!-- This navigation pane hides when the viewport is smaller than 768 -->
			<div id="fs-navside" class="col-sm-2">
				<ul class="nav nav-pills nav-stacked">
					<?php foreach($menu as $module) {?>
						<li data-mod="<?php echo $module['rawname']?>" class="menu-<?php echo $module['rawname']?> <?php echo ($module['rawname'] == $active_module) ? 'active' : ''?>">
							<?php if(empty($module['menu'])) {?>
								<a data-mod="<?php echo $module['rawname']?>" data-pjax href="?display=dashboard&amp;mod=<?php echo $module['rawname']?>"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span id="<?php echo $module['rawname']?>-badge" class="badge"><?php echo $module['badge']?></span><?php } ?></a>
							<?php } elseif(!empty($module['menu']) && count($module['menu']) == 1) {?>
								<a data-mod="<?php echo $module['rawname']?>" data-pjax href="?display=dashboard&amp;mod=<?php echo $module['rawname']?>&amp;sub=<?php echo $module['menu'][0]['rawname']?>"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span id="<?php echo $module['rawname']?>-badge" class="badge"><?php echo $module['badge']?></span><?php } ?></a>
							<?php } else {?>
								<a class="dropdown-toggle <?php echo ($module['rawname'] == $active_module) ? 'active' : ''?>" data-toggle="dropdown" href="#"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span id="<?php echo $module['rawname']?>-badge" class="badge"><?php echo $module['badge']?></span><?php } ?> <span class="caret"></span></a>
								<ul class="dropdown-menu">
									<?php foreach($module['menu'] as $smenu) {?>
										<li>
			   								<a data-mod="<?php echo $module['rawname']?>" data-pjax href="?display=dashboard&amp;mod=<?php echo $module['rawname']?>&amp;sub=<?php echo $smenu['rawname']?>"><?php echo $smenu['name']?> <?php if(isset($smenu['badge'])) {?><span id="<?php echo $module['rawname']?>-<?php echo $smenu['rawname']?>-badge" class="badge"><?php echo $smenu['badge']?></span><?php } ?></a>
										</li>
									<?php } ?>
								</ul>
							<?php } ?>
						</li>
					<?php } ?>
				</ul>
			</div>
			  <div class="col-sm-10">
				<div id="loader-screen">
					<div id="loader-screen-content"><strong><?php echo _('Excuse us while we try to retrieve your content')?>..</strong></div>
				</div>
				  <!-- The content below is loaded dynamically through PJAX After Dashboard had loaded -->
				  <div id="dashboard-content">
					  <?php echo $dashboard_content?>
				 </div>
			  </div>
		</div>
	</div>
	<div id="messages-container">
	</div>
	<div id="footer">
		<div id="footer-bar"></div>
		<div id="footer-content">
			<?php echo $dashboard_footer_content?>
	</div>
</div>
