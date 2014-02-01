<?php foreach($messages as $folder => $data) {?>
	<?php echo $folder?> (<?php echo count($data['messages'])?>)</br>
	<ul>
	<?php foreach($data['messages'] as $message) {?>
		<li>Message From <?php echo $message['callerid']?></li>
	<?php } ?>
	</ul>
<?php } ?>