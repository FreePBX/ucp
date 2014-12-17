<div id="ucp-settings">
	<h3><?php echo _('User Control Panel Settings')?></h3>
	<div class="vmsettings">
		<div id="message" class="alert" style="display:none;"></div>
		<form role="form">
			<div class="form-group">
				<label for="fname" class="help"><?php echo _('First Name')?> <i class="fa fa-question-circle"></i></label>
				<input name="fname" type="text" class="form-control" id="fname" value="<?php echo $user['fname']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="fname"><?php echo _('Your First Name')?></span>
			</div>
			<div class="form-group">
				<label for="lname" class="help"><?php echo _('Last Name')?> <i class="fa fa-question-circle"></i></label>
				<input name="lname" type="text" class="form-control" id="lname" value="<?php echo $user['lname']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="lname"><?php echo _('Your Last Name')?></span>
			</div>
			<div class="form-group">
				<label for="email" class="help"><?php echo _('Email')?> <i class="fa fa-question-circle"></i></label>
				<input name="email" type="text" class="form-control" id="email" value="<?php echo $user['email']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="email"><?php echo _('Your Email Address')?></span>
			</div>
			<div class="form-group">
				<label for="pwd" class="help"><?php echo _('UCP Password')?> <i class="fa fa-question-circle"></i></label>
				<input name="pwd" type="password" class="form-control" id="pwd" value="******" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="pwd"><?php echo _('The password used to login to User Control Panel and other services')?></span>
			</div>
			<div class="form-group desktopnotifications-group" style="display:none;">
				<label for="desktopnotifications-h" class="help"><?php echo _('Allow Desktop Notifications')?> <i class="fa fa-question-circle"></i></label>
				<div class="onoffswitch">
					<input type="checkbox" name="desktopnotifications" class="onoffswitch-checkbox" id="desktopnotifications">
					<label class="onoffswitch-label" for="desktopnotifications">
						<div class="onoffswitch-inner"></div>
						<div class="onoffswitch-switch"></div>
					</label>
				</div>
				<span class="help-block help-hidden" data-for="desktopnotifications-h"><?php echo _('Allow browser desktop notifications from UCP modules.')?></span>
			</div>
		</form>
	</div>
</div>
