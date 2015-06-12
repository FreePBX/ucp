<div class="panel panel-info">
	<div class="panel-heading">
		<div class="panel-title">
			<a href="#" data-toggle="collapse" data-target="#moreinfo-ucp"><i class="glyphicon glyphicon-info-sign"></i></a>&nbsp;&nbsp;&nbsp;<?php echo _("What is UCP")?>
		</div>
	</div>
	<!--At some point we can probably kill this... Maybe make is a 1 time panel that may be dismissed-->
	<div class="panel-body collapse" id="moreinfo-ucp">
		<p><?php echo _('UCP, otherwise known as the User Control Panel, is a user interface for FreePBX.')?></p>
	</div>
</div>
<div class="nav-container ucp-navs">
	<div class="scroller scroller-left"><i class="glyphicon glyphicon-chevron-left"></i></div>
	<div class="scroller scroller-right"><i class="glyphicon glyphicon-chevron-right"></i></div>
	<div class="wrapper">
		<ul class="nav nav-tabs list" role="tablist">
			<li role="presentation" data-name="ucp-general" class="change-tab active">
				<a href="#ucp-general" aria-controls="ucp-general" role="tab" data-toggle="tab">
					<?php echo _("General")?>
				</a>
			</li>
			<li role="presentation" data-name="ucp-miscellaneous" class="change-tab">
				<a href="#ucp-miscellaneous" aria-controls="ucp-miscellaneous" role="tab" data-toggle="tab">
					<?php echo _("Miscellaneous")?>
				</a>
			</li>
			<?php foreach($mHtml as $mod) {?>
				<li role="presentation" data-name="ucp-<?php echo $mod['rawname']?>" class="change-tab">
					<a href="#ucp-<?php echo $mod['rawname']?>" aria-controls="ucp-<?php echo $mod['rawname']?>" role="tab" data-toggle="tab">
						<?php echo $mod['title']?>
					</a>
				</li>
			<?php } ?>
		</ul>
	</div>
</div>
<div class="tab-content display">
	<div role="tabpanel" id="ucp-general" class="tab-pane active">
		<!--UCP LOGIN-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="ucp_login"><?php echo _('Allow Login')?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="ucp_login"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" id="ucp1" name="ucp_login" value="true" <?php echo ($allowLogin) ? 'checked' : ''?>>
								<label for="ucp1"><?php echo _("Yes")?></label>
								<input type="radio" id="ucp2" name="ucp_login" value="false" <?php echo (!is_null($allowLogin) && !$allowLogin) ? 'checked' : ''?>>
								<label for="ucp2"><?php echo _("No")?></label>
								<?php if($mode == "user") {?>
									<input type="radio" id="ucp3" name="ucp_login" value='inherit' <?php echo is_null($allowLogin) ? 'checked' : ''?>>
									<label for="ucp3"><?php echo _('Inherit')?></label>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="ucp_login-help" class="help-block fpbx-help-block"><?php echo _("May this user log in to UCP")?></span>
				</div>
			</div>
		</div>
		<!--END UCP LOGIN-->
		<!--UCP Sessions-->
		<?php if($mode == "user") { ?>
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="sessions"><?php echo _("Active Sessions")?></label>
							</div>
							<div class="col-md-9">
								<table class="table table-condensed">
									<thead>
										<tr>
											<th>Session IP</th>
											<th>Actions</th>
										</tr>
									</thead>
									<tbody>
										<?php foreach($sessions as $session) { ?>
											<tr>
												<td><?php echo $session['address']?></td>
												<td><a href="?display=userman&amp;action=showuser&amp;user=<?php echo $user['id'] ?>&amp;deletesession=<?php echo $session['session'] ?>"><i class="fa fa-trash-o"></i></a></td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
				</div>
			</div>
		</div>
		<?php } ?>
		<!--END UCP ALLOWED SETTINGS-->
	</div>
	<div role="tabpanel" id="ucp-miscellaneous" class="tab-pane">
		<!--UCP ALLOWED SETTINGS-->
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="ucp_settings"><?php echo _("Allowed Extension Settings")?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="ucp_settings"></i>
							</div>
							<div class="col-md-9">
								<select data-placeholder="Extensions" id="ucp_settings" class="form-control chosenmultiselect" name="ucp_settings[]" multiple="multiple">
									<?php foreach($ausers as $key => $value) {?>
										<option value="<?php echo $key?>" <?php echo in_array($key,$sassigned) ? 'selected' : '' ?>><?php echo $value?></option>
									<?php } ?>
								</select>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="ucp_settings-help" class="help-block fpbx-help-block"><?php echo _("These are the assigned and active extensions which will be allowed to edit extension settings such as Call Waiting, Call Forwarding, Find Me/Follow Me and others")?></span>
				</div>
			</div>
		</div>
		<div class="element-container">
			<div class="row">
				<div class="col-md-12">
					<div class="row">
						<div class="form-group">
							<div class="col-md-3">
								<label class="control-label" for="ucp_originate"><?php echo _("Enable Originating Calls") ?></label>
								<i class="fa fa-question-circle fpbx-help-icon" data-for="ucp_originate"></i>
							</div>
							<div class="col-md-9 radioset">
								<input type="radio" name="ucp_originate" id="ucp_originate_yes" value="yes" <?php echo ($originate) ? 'checked' : ''?>>
								<label for="ucp_originate_yes"><?php echo _("Yes")?></label>
								<input type="radio" name="ucp_originate" id="ucp_originate_no" value="no" <?php echo (!is_null($originate) && !$originate) ? 'checked' : ''?>>
								<label for="ucp_originate_no"><?php echo _("No")?></label>
								<?php if($mode == "user") {?>
									<input type="radio" id="ucp_originate_inherit" name="ucp_originate" value='inherit' <?php echo is_null($originate) ? 'checked' : ''?>>
									<label for="ucp_originate_inherit"><?php echo _('Inherit')?></label>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-md-12">
					<span id="ucp_originate-help" class="help-block fpbx-help-block"><?php echo _("Allow this user to originate calls from within UCP. This is not the same as WebRTC")?></span>
				</div>
			</div>
		</div>
		<!--END UCP ALLOWED SETTINGS-->
	</div>
	<?php foreach($mHtml as $mod) {?>
		<div role="tabpanel" id="ucp-<?php echo $mod['rawname']?>" class="tab-pane">
			<?php echo $mod['content']?>
		</div>
	<?php } ?>
</div>
<style>
.ucp-navs .scroller-left {
	left: 26px;
}
.ucp-navs .scroller-right {
	right: 23px;
}
</style>
