<div id="ucp-settings">
	<h3><?php echo _('User Control Panel Settings')?></h3>
	<div class="alert alert-info text-center" id="settings-message"><?php echo _("All fields update when unfocused (selecting another field) except password")?></div>
	<ul class="nav nav-tabs pb-0">
		<li><a class="active nav-link" href="#accountsettings" data-toggle="tab"><?php echo _("Account Settings")?></a></li>
		<li><a class="nav-link" href="#userinfo" data-toggle="tab"><?php echo _("User Details")?></a></li>
		<li><a class="nav-link" href="#ucpsettings" data-toggle="tab"><?php echo _("Interface Settings")?></a></li>
		<?php foreach($extra as $module => $data) { ?>
			<?php foreach($data as $e) { ?>
				<li><a href="#settings-<?php echo $e['module'] ?? ''; ?>-<?php echo $e['rawname'] ?? ''; ?>" data-toggle="tab"><?php echo $e['name'] ?? '';?></a></li>
			<?php }?>
		<?php }?>
	</ul>
	<div class="tab-content">
		<div class="tab-pane fade in active" id="accountsettings">
			<?php if($changeusername) {?>
				<div class="form-group">
					<label for="username" class="help"><?php echo _('Username')?> <i class="fa fa-question-circle"></i></label>
					<input name="username" type="username" class="form-control" id="username" value="<?php echo $username?>" data-prevusername="<?php echo $username?>" autocapitalize="off" autocorrect="off" autocomplete="off">
					<span class="help-block help-hidden" data-for="username"><?php echo _('The username used to login to User Control Panel and other services')?></span>
				</div>
			<?php }?>
			<?php if($changepassword) {?>
				<fieldset class="password-set">
					<legend><?php echo _("Password")?></legend>
					<div class="form-group">
						<label for="pwd" class="help"><?php echo _('Password')?> <i class="fa fa-question-circle"></i></label>
						<input name="pwd" type="password" class="form-control" id="pwd" value="******" autocapitalize="off" autocorrect="off" autocomplete="off">
						<span class="help-block help-hidden" data-for="pwd"><?php echo _('The password used to login to User Control Panel and other services')?></span>
					</div>
					<div class="form-group">
						<label for="pwd-confirm" class="help"><?php echo _('Confirm Password')?> <i class="fa fa-question-circle"></i></label>
						<input name="pwd-confirm" type="password" class="form-control" id="pwd-confirm" value="******" autocapitalize="off" autocorrect="off" autocomplete="off">
						<span class="help-block help-hidden" data-for="pwd-confirm"><?php echo _('The password used to login to User Control Panel and other services')?></span>
					</div>
					<button class="btn btn-default" id="update-pwd"><?php echo _("Update Password")?></button>
				</fieldset>
			<?php }?>
		</div>
		<div class="tab-pane fade" id="userinfo">
			<div class="form-group">
				<label for="displayname" class="help"><?php echo _('Display Name')?> <i class="fa fa-question-circle"></i></label>
				<input name="displayname" type="text" class="form-control" id="displayname" value="<?php echo $user['displayname']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="displayname"><?php echo _('How you would like your name displayed throughout UCP and Contact Manager')?></span>
			</div>
			<div class="form-group">
				<label for="email" class="help"><?php echo _('Email')?> <i class="fa fa-question-circle"></i></label>
				<input name="email" type="text" class="form-control" id="email" value="<?php echo $user['email']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="email"><?php echo _('Your Email Address')?></span>
			</div>
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
				<label for="title" class="help"><?php echo _('Title')?> <i class="fa fa-question-circle"></i></label>
				<input name="title" type="text" class="form-control" id="title" value="<?php echo $user['title']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="title"><?php echo _('Your Title')?></span>
			</div>
			<div class="form-group">
				<label for="company" class="help"><?php echo _('Company')?> <i class="fa fa-question-circle"></i></label>
				<input name="company" type="text" class="form-control" id="company" value="<?php echo $user['company']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="company"><?php echo _('Your Company')?></span>
			</div>
			<div class="form-group">
				<label for="cell" class="help"><?php echo _('Cell Phone')?> <i class="fa fa-question-circle"></i></label>
				<input name="cell" type="text" class="form-control" id="cell" value="<?php echo $user['cell']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="cell"><?php echo _('Your Cell Phone Number')?></span>
			</div>
			<div class="form-group">
				<label for="work" class="help"><?php echo _('Work Phone')?> <i class="fa fa-question-circle"></i></label>
				<input name="work" type="text" class="form-control" id="work" value="<?php echo $user['work']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="work"><?php echo _('Your Work Number')?></span>
			</div>
			<div class="form-group">
				<label for="home" class="help"><?php echo _('Home Phone')?> <i class="fa fa-question-circle"></i></label>
				<input name="home" type="text" class="form-control" id="home" value="<?php echo $user['home']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="home"><?php echo _('Your Home Number')?></span>
			</div>
			<div class="form-group">
				<label for="fax" class="help"><?php echo _('Fax')?> <i class="fa fa-question-circle"></i></label>
				<input name="fax" type="text" class="form-control" id="fax" value="<?php echo $user['fax']?>" autocapitalize="off" autocorrect="off" autocomplete="off">
				<span class="help-block help-hidden" data-for="fax"><?php echo _('Your Fax Number')?></span>
			</div>
			<div class="row" id="Contactmanager-image">
				<div class="col-md-4">
					<div id="contactmanager_dropzone" class="image">
						<div class="message"><?php echo _("Drop a new image here");?></div>
						<img class="<?php echo (!empty($contactmanager['data']) && !empty($contactmanager['data']['image'])) ? '' : 'hidden'?>" src="<?php echo (!empty($contactmanager['data']) && !empty($contactmanager['data']['image'])) ? '?quietmode=1&module=Contactmanager&command=limage&type=internal&entryid='.$contactmanager['data']['id'].'&time='.time() : ''?>">
					</div>
					<button id="contactmanager_del-image" data-entryid="<?php echo !empty($contactmanager['data']) ? $contactmanager['data']['id'] : ''?>" class="btn btn-danger btn-sm <?php echo (!empty($contactmanager['data']) && !empty($contactmanager['data']['image'])) ? '' : 'hidden'?>"><?php echo _("Delete Image")?></button>
				</div>
				<div class="col-md-8">
					<input type="hidden" name="contactmanager_image" id="contactmanager_image">
					<span class="btn btn-default btn-file">
						<?php echo _("Browse")?>
						<input id="contactmanager_imageupload" type="file" class="form-control" name="files[]" data-url="ajax.php?module=Contactmanager&amp;command=uploadimage" class="form-control" multiple>
					</span>
					<span class="filename"></span>
					<div id="contactmanager_upload-progress" class="progress">
						<div class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
					</div>
					<div class="radioset">
						<input name="contactmanager_gravatar" data-toggle="toggle" id="contactmanager_gravatar" data-entryid="<?php echo !empty($contactmanager['data']) ? $contactmanager['data']['id'] : ''?>" type="checkbox" value="on" <?php echo (!empty($contactmanager['data']) && !empty($contactmanager['data']['image'])) && !empty($contactmanager['data']['image']['gravatar']) ? 'checked' : ''?>>
						<label for="contactmanager_gravatar"><?php echo _("Use Gravatar")?></label>
					</div>
				</div>
			</div>
		</div>
		<div class="tab-pane fade" id="ucpsettings">
			<div class="form-group">
				<label for="lang" class="help"><?php echo _('Language')?> <i class="fa fa-question-circle"></i></label><br/>
				<div class="input-group">
					<?php echo FreePBX::View()->languageDrawSelect('lang',$user['language'],_('Default')); ?>
					<span class="input-group-btn ml-auto">
						<a href="#" class="btn btn-default" id="browserlang"><?php echo _("Use Browser Language")?></a>
					</span>
					<span class="input-group-btn">
						<a href="#" class="btn btn-default" id="systemlang"><?php echo _("Use PBX Language")?></a>
					</span>
				</div>
				<span class="help-block help-hidden" data-for="lang"><?php echo _('Your Language')?></span>
			</div>
			<div class="form-group">
				<label for="timezone" class="help"><?php echo _('Timezone')?> <i class="fa fa-question-circle"></i></label><br/>
				<div class="input-group">
					<?php echo FreePBX::View()->timezoneDrawSelect('timezone',$user['timezone'],_('Default')); ?>
					<span class="input-group-btn ml-auto">
						<a href="#" class="btn btn-default" id="browsertz"><?php echo _("Use Browser Timezone")?></a>
					</span>
					<span class="input-group-btn">
						<a href="#" class="btn btn-default" id="systemtz"><?php echo _("Use PBX Timezone")?></a>
					</span>
				</div>
				<span class="help-block help-hidden" data-for="timezone"><?php echo _('Your Timezone')?></span>
			</div>
			<div class="form-group desktopnotifications-group hidden">
				<label for="desktopnotifications-h" class="help"><?php echo _('Allow Desktop Notifications')?> <i class="fa fa-question-circle"></i></label><br/>
				<input type="checkbox" name="desktopnotifications" data-toggle="toggle" id="desktopnotifications">
				<span class="help-block help-hidden" data-for="desktopnotifications-h"><?php echo _('Allow browser desktop notifications from UCP modules.')?></span>
			</div>
			<div class="form-group tour-group">
				<label for="tour-h" class="help"><?php echo _('Restart Tour')?> <i class="fa fa-question-circle"></i></label><br/>
				<input type="checkbox" name="tour" data-toggle="toggle" id="tour">
				<span class="help-block help-hidden" data-for="tour-h"><?php echo _('When set to yes the tour will restart when this window closes')?></span>
			</div>
			<div class="form-group">
				<label for="datetimeformat" class="help"><?php echo _('Date and Time Format')?> <i class="fa fa-question-circle"></i></label><br/>
				<div class="input-group">
					<input type="text" class="form-control" placeholder="<?php echo $placeholders['datetimeformat']?>" id="datetimeformat" name="datetimeformat" value="<?php echo $user['datetimeformat']?>">
					<span class="input-group-addon" id="datetimeformat-now"></span>
				</div>
				<span class="help-block help-hidden" data-for="datetimeformat"><?php echo sprintf(_('The format dates and times should display in. The default of "llll" is locale aware. If left blank this will use the group/system format. For more formats please see: %s'),'http://momentjs.com/docs/#/displaying/format/')?></span>
			</div>
			<div class="form-group">
				<label for="dateformat" class="help"><?php echo _('Date Format')?> <i class="fa fa-question-circle"></i></label><br/>
				<div class="input-group">
					<input type="text" class="form-control" placeholder="<?php echo $placeholders['dateformat']?>" id="dateformat" name="dateformat" value="<?php echo $user['dateformat']?>">
					<span class="input-group-addon" id="dateformat-now"></span>
				</div>
				<span class="help-block help-hidden" data-for="dateformat"><?php echo sprintf(_('The format dates should display in. The default of "l" is locale aware. If left blank this will use the group/system format. For more formats please see: %s'),'http://momentjs.com/docs/#/displaying/format/')?></span>
			</div>
			<div class="form-group">
				<label for="timeformat" class="help"><?php echo _('Time Format')?> <i class="fa fa-question-circle"></i></label><br/>
				<div class="input-group">
					<input type="text" class="form-control" placeholder="<?php echo $placeholders['timeformat']?>" id="timeformat" name="timeformat" value="<?php echo $user['timeformat']?>">
					<span class="input-group-addon" id="timeformat-now"></span>
				</div>
				<span class="help-block help-hidden" data-for="timeformat"><?php echo sprintf(_('The format times should display in. The default of "LT" is locale aware. If left blank this will use the group/system format. For more formats please see: %s'),'http://momentjs.com/docs/#/displaying/format/')?></span>
			</div>
		</div>
		<?php foreach($extra as $module => $data) { ?>
			<?php foreach($data as $e) { ?>
				<div class="tab-pane fade" id="settings-<?php echo $e['module'] ?? ''; ?>-<?php echo $e['rawname'] ?? ''; ?>">
					<?php echo $e['html'] ?? ''; ?>
				</div>
			<?php }?>
		<?php }?>
	</div>
</div>
