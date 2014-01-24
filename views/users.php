<h3><?php echo _("Add New User")?></h3>
<form autocomplete="off" name="editM" action="<?php $_SERVER['PHP_SELF'] ?>" method="post" onsubmit="return editM_onsubmit();">
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
			<td colspan="2"><input type="submit" value="Submit"></td>
		</tr>
	</table>
</form>