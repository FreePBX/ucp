<div id="login-window">
	<form id="frm-login" method="POST" action="?display=dashboard">
		<input type="hidden" name="token" value="<?php echo $token?>">
		<h2 id="login-header" class="text-center"><?php echo _('User Control Panel')?></h2>
		<?php if(!empty($error_warning)) {?>
			<div class="alert alert-warning"><?php echo $error_warning?></div>
		<?php } ?>
		<?php if(!empty($error_danger)) {?>
			<div class="alert alert-danger"><?php echo $error_danger?></div>
		<?php } ?>
		<div id="error-msg" class="alert alert-danger" style="display:none"></div>
		<div class="input-group input-margin">
			<span class="input-group-addon"><i class="fa fa-user fa-fw"></i></span>
			<input type="text" name="username" class="form-control" placeholder="Username" autocapitalize="off" autocorrect="off">
		</div>
		<div class="input-group input-margin">
			<span class="input-group-addon"><i class="fa fa-key fa-fw"></i></span>
			<input type="password" name="password" class="form-control" placeholder="Password" autocapitalize="off" autocorrect="off">
		</div>
		<br/>
		<div class="row">
			<div class="col-sm-12">
				<table id="login-extras">
					<tr>
						<td id="rm-text"><?php echo _('Remember Me')?></td>
						<td id="rm-checkbox">
							<div class="onoffswitch">
							    <input type="checkbox" name="rememberme" class="onoffswitch-checkbox" id="rememberme">
							    <label class="onoffswitch-label" for="rememberme">
							        <div class="onoffswitch-inner"></div>
							        <div class="onoffswitch-switch"></div>
							    </label>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan="3" id="td-btn-login"><button type="submit" id="btn-login"><?php echo _('Login')?></button></td>
					</tr>
				</table>
			</div>
		</div>
	</form>
</div>