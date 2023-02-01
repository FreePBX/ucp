<div class="message-box" data-module="<?php echo $module?>" data-last-msg-id="<?php echo !empty($history['lastMessage']['id']) ? $history['lastMessage']['id'] : '0'?>" data-id="<?php echo $id?>" data-from="<?php echo $from?>" data-to="<?php echo is_array($to)? $to[0] : $to; ?>" style="display:none">
	<div class="title-bar" data-id="<?php echo $id?>">
		<div class="type">
			<i class="<?php echo $icon?>"></i>
		</div>
		<div class="name">
			<?php echo $title?>
		</div>
		<div class="actions">
			<i class="fa fa-times cancelExpand"></i><i class="fa fa-arrow-up"></i>
		</div>
	</div>
	<div class="window">
		<div class="chat">
			<div class="history">
				<?php if(!empty($history['messages'])) { ?>
					<?php foreach($history['messages'] as $h) { ?>
						<div class="message <?php echo $h['direction']?>" data-id="<?php echo $h['id']?>" title="<?php echo $this->UCP->View->getDateTime($h['date']) ?>">
							<?php echo $h['message']?>
						</div>
					<?php } ?>
					<?php if(!empty($history)) {?>
						<div class="status" data-type="date"><?php sprintf(_('Sent at %s'),$this->UCP->View->getDateTime($h['date']));?></div>
					<?php } ?>
				<?php } ?>
			</div>
		</div>
		<div class="response-status"></div>
		<div class="response">
			<textarea class="form-control"></textarea>
		</div>
	</div>
</div>
