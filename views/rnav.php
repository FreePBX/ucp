<div class='rnav'>
	<ul>
		<li class="rnav-heading">User List</li>
		<li><hr></li>
		<?php foreach($users as $user) {?>
			<li><a href='config.php?display=ucpadmin&amp;category=users&amp;action=showuser&amp;user=<?php echo $user['id']?>'><?php echo $user['username']?></a></li>
		<?php }?>
	</ul>
</div>