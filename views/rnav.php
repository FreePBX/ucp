<div class='rnav'>
	<ul>
		<li class="rnav-heading">Actions</li>
		<li><a href='config.php?display=ucpadmin&amp;category=users'>Users</a></li>
	</ul>
</div>
<?php if(isset($_REQUEST['category']) && $_REQUEST['category'] == 'users') {?>
<div class='rnav'>
	<ul>
		<li class="rnav-heading">User List</li>
		<li><a href='config.php?display=ucpadmin&amp;category=users&amp;action=adduser'>Add New User</a></li>
		<li><hr></li>
		<?php foreach($users as $user) {?>
			<li><a href='config.php?display=ucpadmin&amp;category=users&amp;action=showuser&amp;user=<?php echo $user['id']?>'><?php echo $user['username']?></a></li>
		<?php }?>
	</ul>
</div>
<?php } ?>