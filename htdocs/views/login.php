<div id="login-window">
	<form id="frm-login" method="POST" action="?display=dashboard">
		<h2 id="login-header" class="text-center">User Control Panel</h2>
		<?php if(!empty($error_warning)) {?>
			<div class="alert alert-warning"><?php echo $error_warning?></div>
		<?php } ?>
		<?php if(!empty($error_danger)) {?>
			<div class="alert alert-danger"><?php echo $error_danger?></div>
		<?php } ?>
		<div class="input-group input-margin">
			<span class="input-group-addon"><i class="fa fa-user fa-fw"></i></span>
			<input type="text" class="form-control" placeholder="Username">
		</div>
		<div class="input-group input-margin">
			<span class="input-group-addon"><i class="fa fa-key fa-fw"></i></span>
			<input type="password" class="form-control" placeholder="Password">
		</div>
		<br/>
		<div class="row">
			<div class="col-sm-12">
				<table id="login-extras">
					<tr>
						<td id="rm-text">Remember Me</td>
						<td id="rm-checkbox">
							<div class="onoffswitch">
							    <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="myonoffswitch">
							    <label class="onoffswitch-label" for="myonoffswitch">
							        <div class="onoffswitch-inner"></div>
							        <div class="onoffswitch-switch"></div>
							    </label>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="3" id="td-btn-login"><button type="submit" id="btn-login">Login</button></td>
					</tr>
				</table>
			</div>
		</div>
	</form>
</div>