<table>
    <tr class="guielToggle" data-toggle_class="UCP">
        <td colspan="2" ><h4><span class="guielToggleBut">-  </span><?php echo _("UCP")?></h4><hr></td>
    </tr>
    <tr class="UCP">
        <td colspan="2"><h5><?php echo _("UCP Module Settings")?></h5><hr></td>
    </tr>
	<tr class="UCP">
    	<td><a href="#" class="info"><?php echo _("Voicemail Access")?>:<span><?php echo _("These are the assigned and active extensions which will show up for this user to control and edit in UCP")?></span></a></td>
		<td>
			<div class="extensions-list">
			<?php foreach($fpbxusers as $fpbxuser) {?>
				<label><input type="checkbox" name="ucp|voicemail[]" value="<?php echo $fpbxuser['ext']?>" <?php echo $fpbxuser['selected'] ? 'checked' : '' ?>> <?php echo $fpbxuser['data']['name']?> &lt;<?php echo $fpbxuser['ext']?>&gt;</label><br />
			<?php } ?>
			</div>
		</td>
    <tr class="UCP">
        <td colspan="2"><h5><?php echo _("Active Sessions")?></h5><hr></td>
		<?php foreach($sessions as $session) {?>
        <tr>
            <td><?php echo $session['address']?></td>
            <td><a href="?display=userman&amp;action=showuser&amp;user=<?php echo $user['id']?>&amp;deletesession=<?php echo $session['session']?>"><img src="images/trash.png"></a></td>
        </tr>
		<?php } ?>
    </tr>
</table>