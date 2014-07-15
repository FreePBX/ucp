<div class="message-box" data-module="<?php echo $module?>" data-last-msg-id="<?php echo !empty($history['lastMessage']['id']) ? $history['lastMessage']['id'] : '0'?>" data-id="<?php echo $id?>" data-from="<?php echo $from?>" data-to="<?php echo $to?>" style="display:none">
	<div class="title-bar" data-id="<?php echo $id?>">
		<div class="type">
			<i class="fa fa-comment"></i>
		</div>
		<div class="name"><?php echo $title?></div>
		<div class="actions">
			<i class="fa fa-times cancelExpand"></i><i class="fa fa-arrow-up"></i>
		</div>
	</div>
	<div class="window">
		<div class="chat">
			<div class="history">
				<?php if(!empty($history['messages'])) { ?>
					<?php foreach($history['messages'] as $h) { ?>
						<div class="message" data-id="<?php echo $h['id']?>">
							<strong><?php echo $h['from']?>:</strong> <?php echo $h['message']?>
						</div>
					<?php } ?>
					<?php if(!empty($history)) {?>
						<span class="date">Sent at <?php echo date('g:i A \\o\\n l', $h['date'])?></span>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
		<div class="response">
			<textarea></textarea>
		</div>
	</div>
</div>
