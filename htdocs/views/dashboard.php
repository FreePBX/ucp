<nav class="pushmenu pushmenu-left">
	<h3>Welcome Andrew</h3>
	<ul>
		<?php foreach($menu as $module) {?>
		<li data-mod="<?php echo $module['rawname']?>" class="<?php echo ($module['rawname'] == $active_module) ? 'active' : ''?>">
			<?php if(empty($module['menu'])) {?>
	    		<a data-pjax data-mod="<?php echo $module['rawname']?>" href="?display=dashboard&amp;mod=<?php echo $module['rawname']?>"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span class="badge"><?php echo $module['badge']?></span><?php } ?></a>
			<?php } else {?>
	    		<a onClick="toggleSubMenu('<?php echo $module['rawname']?>')"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span class="badge"><?php echo $module['badge']?></span><?php } ?></a>
				<ul data-mod="<?php echo $module['rawname']?>" id="submenu-<?php echo $module['rawname']?>" class="dropdown-pushmenu">
					<?php foreach($module['menu'] as $smenu) {?>
						<li>
							<a data-mod="<?php echo $module['rawname']?>" data-pjax href="?display=dashboard&amp;mod=<?php echo $module['rawname']?>&amp;sub=<?php echo $smenu['rawname']?>"><?php echo $smenu['name']?> <?php if(isset($smenu['badge'])) {?><span class="badge"><?php echo $smenu['badge']?></span><?php } ?></a>
						</li>
					<?php } ?>
				</ul>
			<?php } ?>
		</li>
		<?php } ?>
	</ul>
</nav>
<div id="dashboard" class="pushmenu-push dashboard-container center-box">
	<!-- This navigation pane hides when the viewport is smaller than 768 -->
	<!-- Mobile Navigation Header -->
	<div id="nav-bar-background">
		<div id="bc-mobile-icon" onClick="toggleMenu()"><i class="fa fa-bars"></i></div>
		<ol id="top-dashboard-nav" class="breadcrumb">
		  <li><a data-mod="home" data-pjax href="?display=dashboard&amp;mod=home"><?php echo _('Home')?></a></li>
		  <?php if($active_module != 'home') {?>
			  <li class="bc-<?php echo $menu[$active_module]['rawname']?> active"><?php echo $menu[$active_module]['rawname']?></li>
			  <?php if(!empty($_REQUEST['sub'])) {?>
				  <li class="bc-<?php echo $_REQUEST['sub']?> active"><?php echo $_REQUEST['sub']?></li>
			<?php } ?>
		  <?php } ?>
		</ol>
		<div id="top-dashboard-nav-logout"><img src="assets/images/settings.png"> <a data-pjax-logout href="?logout=1"><?php echo _('Logout')?></a></div>
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
					<div id="loader-screen-content"><strong>Excuse us while we try to retrieve your content..</strong></div>
				</div>
				  <!-- The content below is loaded dynamically through PJAX After Dashboard had loaded -->
				  <div id="dashboard-content">
					  <?php echo $dashboard_content?>
				 </div>
			  </div>
		</div>
	</div>
	<div id="footer">
		<div id="footer-bar"></div>
		<div id="footer-content">
			<div id="footer-image">
				<a href="http://www.schmoozecom.com/">
					<img height="65" src="assets/images/schmooze-phone-icon.png">
				</a>
			</div>
			<div id="footer-message">
				User Control Panel is released as <a href="http://www.gnu.org/licenses/agpl-3.0.html" target="_blank">AGPLV3</a> or newer.<br/>
				Copyright 2013-<?php echo $year?> Schmooze Com Inc.<br/>
				<a href="http://www.schmoozecom.com/">http://www.schmoozecom.com/</a><br/>
				<span class="small-text">The removal of this copyright notice is stricly prohibited</span>
			</div>
			<div id="html5-badge">
				<a href="http://www.w3.org/html/logo/">
					<img src="assets/images/HTML5_Badge.png" height="65" alt="HTML5 Powered with Connectivity / Realtime, CSS3 / Styling, Device Access, Multimedia, Performance &amp; Integration, and Offline &amp; Storage" title="HTML5 Powered with Connectivity / Realtime, CSS3 / Styling, Device Access, Multimedia, Performance &amp; Integration, and Offline &amp; Storage">
				</a>
			</div>
		</div>
	</div>
</div>
