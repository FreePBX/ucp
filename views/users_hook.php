<?php
//Generate allowed Settings List.
$settingsExtHtml = '<div class="extensions-list">';
foreach($fpbxusers as $fpbxuser) {
	$checked = $fpbxuser['selected'] ? 'checked' : '';
	$settingsExtHtml .= '<input type="checkbox" name="ucp|settings[]" value="' . $fpbxuser['ext'] .'" id="ucp|settings'. $fpbxuser['ext'].'" '.$checked.'>';
	$settingsExtHtml .= '<label for="ucp|settings'. $fpbxuser['ext'].'"> ' . $fpbxuser['data']['name'] .'&lt;'.$fpbxuser['ext'] .'&gt;' . '</label>';
}
$settingsExtHtml .= '</div>';

//Generate?
$hook = 0;
foreach($mHtml as $m) {
$hookdescribe = $m['description'];
$hookcontent = $m['content'];
$hookhtml .= <<<HERE
<!--From hook $hook-->
$hookcontent
<!--END HOOK $hook-->
HERE;
$hook++;
}
//Sessions
$sessionhtml = '<table class="table table-condensed">';
$sessionhtml .= '<thead>';
$sessionhtml .= '<tr>';
$sessionhtml .= '<th>Session IP</th><th>Actions</th>';
$sessionhtml .= '</tr>';
$sessionhtml .= '</th>';
$sessionhtml .= '<tbody>';
foreach($sessions as $session) {
$sessionhtml .= '<tr>';
$sessionhtml .= '<td>'.$session['address'].'</td>';
$sessionhtml .= '<td><a href="?display=userman&amp;action=showuser&amp;user=' . $user['id'] . '&amp;deletesession=' . $session['session'] . '"><i class="fa fa-trash-o"></i></a></td>';
$sessionhtml .= '<tr>';
}
$sessionhtml .= '</tbody>';
$sessionhtml .= '</table>';

?>
<!--ucp/users_hook.php-->
<style>
.indent-div {
	margin-left: 15px;
}
</style>
<div class="section-title" data-for="ucphook"><h3><i class="fa fa-minus"></i><?php echo _("UCP") ?></h3></div>
<div class="section" data-id="ucphook">
<!--UCP LOGIN-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="ucp|login"><?php echo _('Allow Login')?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="ucp|login"></i>
					</div>
					<div class="col-md-9 radioset">
						<input type="radio" id="ucp1" name="ucp|login" value="true" <?php echo ($allowLogin) ? 'checked' : ''?>>
						<label for="ucp1"><?php echo _("Yes")?></label>
						<input type="radio" id="ucp2" name="ucp|login" value="false" <?php echo (!$allowLogin) ? 'checked' : ''?>>
						<label for="ucp2"><?php echo _("No")?></label>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="ucp|login-help" class="help-block fpbx-help-block"><?php echo _("May this user log in to UCP")?></span>
		</div>
	</div>
</div>					
<!--END UCP LOGIN-->
<!--UCP ALLOWED SETTINGS-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="ucp|settings"><?php echo _("Allowed Settings")?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="ucp|settings"></i>
					</div>
					<div class="col-md-9">
						<?php echo $settingsExtHtml ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="ucp|settings-help" class="help-block fpbx-help-block"><?php echo _("These are the assigned and active extensions which will show up for this user to control and edit in UCP")?></span>
		</div>
	</div>
</div>				
<!--END UCP ALLOWED SETTINGS-->
<!--Hooks to UCP -->
<?php echo $hookhtml ?>
<!--End Hooks to UCP-->
<!--UCP Sessions-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="sessions"><?php echo _("Active Sessions")?></label>
					</div>
					<div class="col-md-9">
						<?php echo $sessionhtml ?>
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
<!--END UCP ALLOWED SETTINGS-->
</div>
<!-- END ucp/users_hook.php-->
