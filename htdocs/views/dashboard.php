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
<div id="dashboard" class="pushmenu-push dashboard-container center-box">
	<!-- This navigation pane hides when the viewport is smaller than 768 -->
	<!-- Mobile Navigation Header -->
	<div id="nav-bar-background">
		<div id="global-message-container">
			<div id="global-message"></div>
		</div>
		<div id="bc-mobile-icon"><i class="fa fa-bars"></i></div>
		<ol id="top-dashboard-nav" class="breadcrumb">
		  <li><a data-mod="home" data-pjax href="?display=dashboard&amp;mod=home"><?php echo _('Home')?></a></li>
		  <?php if($active_module != 'home' && !empty($menu[$active_module])) {?>
			  <li class="bc-<?php echo $menu[$active_module]['rawname']?> active"><?php echo $menu[$active_module]['rawname']?></li>
			  <?php if(!empty($_REQUEST['sub'])) {?>
				  <li class="bc-<?php echo $_REQUEST['sub']?> active"><?php echo $_REQUEST['sub']?></li>
			<?php } ?>
		  <?php } ?>
		</ol>
		<div id="top-dashboard-nav-right">
			<div id="presence-box2">
				<div class="p-btn">
					<i class="fa fa-circle"></i>
				</div>
				<div class="p-container">
					<div class="p-msg"></div>
				</div>
			</div>
			<div id="settings-btn">
				<i class="fa fa-cog"></i>
			</div>
		</div>
	</div>
	<ol id="settings-menu">
		<li>
			<a data-pjax href="?display=dashboard&amp;mod=ucpsettings"><?php echo _('Settings')?></a>
		</li>
		<li>
			<a data-pjax-logout href="?logout=1"><?php echo _('Logout')?></a>
		</li>
	</ol>
	<ol id="presence-menu2">
		<?php if(isset($presence)) {?>
			<li><a class="change-status">Change Status</a></li>
			<li class="statuses">
				<?php echo $presence['menu'] ?>
			</li>
		<?php } ?>
		<li class="options">
			<ol>
				<li class="actions">
					<?php foreach($presence['actions'] as $m => $a) {?>
						<i class="fa <?php echo $a?>" data-module="<?php echo $m?>"></i>
					<?php } ?>
				</li>
				<li>
					<span style="padding-left:5px;">Recent Contacts</span>
					<div class="clist">
						<ol>
							<?php foreach($rcontacts as $c) {?>
								<li><i class="fa fa-male"></i><?php echo $c['name']?></li>
							<?php } ?>
						</ol>
					</div>
				</li>
			</ol>
		</li>
	</ol>
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
			<div id="footer-image">
				<a href="http://www.schmoozecom.com/">
					<img height="65" src="assets/images/schmooze-phone-icon.png">
				</a>
			</div>
			<div id="footer-message">
				<?php echo sprintf(_('User Control Panel is released as %s or newer'),'<a href="http://www.gnu.org/licenses/agpl-3.0.html" target="_blank">AGPLV3</a>')?>.<br/>
				<?php echo sprintf(_('Copyright 2013-%s Schmooze Com Inc'),$year)?>.<br/>
				<a href="http://www.schmoozecom.com/">http://www.schmoozecom.com/</a><br/>
				<span class="small-text"><?php echo _('The removal of this copyright notice is stricly prohibited')?></span>
			</div>
		</div>
	</div>
</div>
