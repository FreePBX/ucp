<div class="message-box" data-id="<?php echo $id?>" style="display:none">
	<div class="title-bar" data-id="<?php echo $id?>">
		<div class="type">
			<i class="fa fa-comment"></i>
		</div>
		<div class="name"><?php echo $title?></div>
		<div class="actions">
			<i class="fa fa-times cancelExpand"></i>
		</div>
	</div>
	<div class="window">
		<div class="chat">
			<div class="history">
				<?php foreach($history as $h) { ?>
					<strong><?php echo $h['from']?>:</strong> <?php echo $h['message']?><br/>
				<?php } ?>
				<?php if(!empty($history)) {?>
					<span class="date">Sent at <?php echo date('g:i A \\o\\n l', $h['date'])?></span>
				<?php } ?>
			</div>
		</div>
		<div class="response">
			<textarea></textarea>
		</div>
	</div>
</div>
