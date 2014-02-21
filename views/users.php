<h3><?php echo _("Add New User")?></h3>
<?php if(!empty($message)) {?>
	<div class="alert alert-<?php echo $message['type']?>"><?php echo $message['message']?></div>
<?php } ?>
<form autocomplete="off" name="editM" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return editM_onsubmit();">
	<input type="hidden" name="prevUsername" value="<?php echo !empty($user['username']) ? $user['username'] : ''; ?>">
    <table>
        <tr>
            <td colspan="2"><h5><?php echo _("User Settings")?></h5><hr></td>
        </tr>
        <tr>
            <td><a href="#" class="info"><?php echo _("UCP Username")?>:<span><?php echo _("This is the UCP login")?></span></a></td>
            <td><input type="text" name="username" maxlength="100" value="<?php echo !empty($user['username']) ? $user['username'] : ''; ?>"></td>
        </tr>
        <tr>
            <td><a href="#" class="info"><?php echo _("UCP Password")?>:<span><?php echo _("This is the UCP Password")?></span></a></td>
        	<td><input type="password" name="password" maxlength="150" value="<?php echo !empty($user['password']) ? '******' : ''; ?>"></td>
		</tr>
        <tr>
            <td colspan="2"><h5><?php echo _("Module Settings")?></h5><hr></td>
        </tr>
		<tr>
        	<td><a href="#" class="info"><?php echo _("Assigned Voicemail Extensions")?>:<span><?php echo _("These are the assigned and active extensions which will show up for this user to control and edit in UCP")?></span></a></td>
    		<td>
				<div class="extensions-list">
				<?php foreach($fpbxusers as $fpbxuser) {?>
					<label><input type="checkbox" name="assigned[]" value="<?php echo $fpbxuser['data'][0]?>" <?php echo $fpbxuser['selected'] ? 'checked' : '' ?>> <?php echo $fpbxuser['data'][1]?> &lt;<?php echo $fpbxuser['data'][0]?>&gt;</label><br />
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
	            <td><a href="?display=ucpadmin&amp;category=users&amp;action=showuser&amp;user=1&amp;deletesession=<?php echo $session['session']?>"><img src="images/trash.png"></a></td>
	        </tr>
			<?php } ?>
        </tr>
	</table>
</form>