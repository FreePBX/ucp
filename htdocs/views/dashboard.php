<nav class="pushmenu pushmenu-left">
	<h3>Welcome Andrew</h3>
	<?php foreach($menu as $module) {?>
    	<a data-pjax data-mod="<?php echo $module['rawname']?>" class="<?php echo ($module['rawname'] == $active_module) ? 'active' : ''?>" href="?display=dashboard&amp;mod=<?php echo $module['rawname']?>"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span class="badge"><?php echo $module['badge']?></span><?php } ?></a>
	<?php } ?>
</nav>
<div id="dashboard" class="pushmenu-push dashboard-container center-box">
	<!-- This navigation pane hides when the viewport is smaller than 768 -->
	<!-- Mobile Navigation Header -->
	<div id="nav-bar-background">
		<div id="bc-mobile-icon" onClick="toggleMenu()"><i class="fa fa-bars"></i></div>
		<ol id="top-dashboard-nav" class="breadcrumb">
		  <li><a data-mod="home" data-pjax class="<?php echo ($active_module == 'home') ? 'active' : ''?>" href="?display=dashboard&amp;mod=home">Home</a></li>
		  <?php if($active_module != 'home') {?>
			  <li class="bc-<?php echo $menu[$active_module]['rawname']?>"><a data-mod="<?php echo $menu[$active_module]['rawname']?>" data-pjax class="active" href="?display=dashboard&amp;mod=<?php echo $menu[$active_module]['rawname']?>"><?php echo $menu[$active_module]['rawname']?></a></li>
		  <?php } ?>
		</ol>
		<div id="top-dashboard-nav-logout"><a data-pjax-logout href="?logout=1">Logout</a></div>
	</div>
	<div class="clear"></div>
	<div id="container-fixed-left" class="container">
		<div class="row">
			<!-- This navigation pane hides when the viewport is smaller than 768 -->
			<div id="fs-navside" class="col-sm-2">
				<ul class="nav nav-pills nav-stacked">
					<?php foreach($menu as $module) {?>
						<li data-mod="<?php echo $module['rawname']?>" class="menu-<?php echo $module['rawname']?> <?php echo ($module['rawname'] == $active_module) ? 'active' : ''?>">
							<?php if(empty($module['menu'])) {?>
   								<a data-mod="<?php echo $module['rawname']?>" data-pjax href="?display=dashboard&amp;mod=<?php echo $module['rawname']?>"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span class="badge"><?php echo $module['badge']?></span><?php } ?></a> 
							<?php } else {?>
								<a data-mod="<?php echo $module['rawname']?>" class="dropdown-toggle <?php echo ($module['rawname'] == $active_module) ? 'active' : ''?>" data-toggle="dropdown" href="#"><?php echo $module['name']?> <?php if(isset($module['badge'])) {?><span class="badge"><?php echo $module['badge']?></span><?php } ?> <span class="caret"></span></a>
								<ul class="dropdown-menu">
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
			</div>
			  <div class="col-sm-10">
				  <!-- The content below is loaded dynamically through PJAX After Dashboard had loaded -->
				  <div id="dashboard-content">
					  <?php echo $dashboard_content?>
				 </div>
			  </div>
		</div>
	</div>
</div>