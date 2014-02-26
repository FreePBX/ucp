<h3><?php echo sprintf(_("Settings for %s"),$user['username'])?></h3>
<?php if(!empty($message)) {?>
	<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
<?php } ?>
<form autocomplete="off" name="editM" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return editM_onsubmit();">
    <table>
        <tr>
            <td colspan="2"><h5><?php echo _("Module Settings")?></h5><hr></td>
        </tr>
		<tr>
        	<td><a href="#" class="info"><?php echo _("Allowed Voicemail")?>:<span><?php echo _("These are the assigned and active extensions which will show up for this user to control and edit in UCP")?></span></a></td>
    		<td>
				<div class="extensions-list">
				<?php foreach($fpbxusers as $fpbxuser) {?>
					<label><input type="checkbox" name="vmassigned[]" value="<?php echo $fpbxuser['ext']?>" <?php echo $fpbxuser['selected'] ? 'checked' : '' ?>> <?php echo $fpbxuser['data']['name']?> &lt;<?php echo $fpbxuser['ext']?>&gt;</label><br />
				<?php } ?>
				</div>
    		</td>
		<tr>
			<td colspan="2"><input type="submit" name="submit" value="Submit"></td>
		</tr>
        <tr>
            <td colspan="2"><h5><?php echo _("Active Sessions")?></h5><hr></td>
			<?php foreach($sessions as $session) {?>
	        <tr>
	            <td><?php echo $session['address']?></td>
	            <td><a href="?display=ucpadmin&amp;category=users&amp;action=showuser&amp;user=<?php echo $user['id']?>&amp;deletesession=<?php echo $session['session']?>"><img src="images/trash.png"></a></td>
	        </tr>
			<?php } ?>
        </tr>
	</table>
</form>