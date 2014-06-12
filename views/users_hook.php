<style>
.indent-div {
	margin-left: 15px;
}
</style>
<script>
$('.extension-checkbox').change(function(event){
	var ext = $(this).data('extension');
	var name = $(this).data('name');
	if($(this).is(':checked')) {
		$('#settings-ext-list').append('<div class="settings-extensions" data-extension="'+ext+'"><label><input type="checkbox" name="ucp|settings[]" value="'+ext+'" checked> '+name+' &lt;'+ext+'&gt;</label><br /></div>');
	} else {
		$('.settings-extensions[data-extension="'+ext+'"]').remove();
	}
});
</script>
<table>
	<tr class="guielToggle" data-toggle_class="UCP">
		<td colspan="2" ><h4><span class="guielToggleBut">-  </span><?php echo _("UCP")?></h4><hr></td>
	</tr>
	<tr>
		<td colspan="2">
			<div class="indent-div">
				<table>
					<tr class="UCP">
						<td><?php echo _('Allow Login')?></td>
						<td>
							<span class="radioset">
								<input type="radio" id="ucp1" name="ucp|login" value="true" <?php echo ($allowLogin) ? 'checked' : ''?>><label for="ucp1">Yes</label>
								<input type="radio" id="ucp2" name="ucp|login" value="false" <?php echo (!$allowLogin) ? 'checked' : ''?>><label for="ucp2">No</label>
							</span>
						</td>
					</tr>
					<tr class="UCP">
						<td colspan="2"><h5><?php echo _("Module Settings")?></h5><hr></td>
					</tr>
					<tr class="UCP">
						<td>
							<a href="#" class="info"><?php echo _("Allowed Settings")?>:<span><?php echo _("These are the assigned and active extensions which will show up for this user to control and edit in UCP")?></span></a>
						</td>
						<td>
							<div id="settings-ext-list" class="extensions-list">
							<?php foreach($fpbxusers as $fpbxuser) {?>
								<div class="settings-extensions" data-extension="<?php echo $fpbxuser['ext']?>">
									<label>
										<input type="checkbox" name="ucp|settings[]" value="<?php echo $fpbxuser['ext']?>" <?php echo $fpbxuser['selected'] ? 'checked' : '' ?>> <?php echo $fpbxuser['data']['name']?> &lt;<?php echo $fpbxuser['ext']?>&gt;
									</label>
									<br />
								</div>
							<?php } ?>
							</div>
						</td>
					</tr>
					<?php foreach($mHtml as $m) {?>
						<tr class="UCP">
							<td>
								<?php echo $m['description'];?>
							</td>
							<td>
								<?php echo $m['content'];?>
							</td>
						</tr>
					<?php } ?>
					<tr class="UCP">
						<td colspan="2"><h5><?php echo _("Active Sessions")?></h5><hr></td>
					</tr>
					<?php foreach($sessions as $session) {?>
						<tr class="UCP">
							<td><?php echo $session['address']?></td>
							<td><a href="?display=userman&amp;action=showuser&amp;user=<?php echo $user['id']?>&amp;deletesession=<?php echo $session['session']?>"><img src="images/trash.png"></a></td>
						</tr>
					<?php } ?>
				</table>
			</div>
		</td>
	</tr>
</table>
