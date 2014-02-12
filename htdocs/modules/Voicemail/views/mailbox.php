<div class="mailbox">
	<div class="row">
		<div class="col-md-2">
			<div class="folder-list">
			<?php foreach($folders as $folder) {?>
				<div class="folder <?php echo ($folder['folder'] == $active_folder) ? 'active' : ''?>" data-folder="<?php echo $folder['folder']?>"><a vm-pjax href="?display=dashboard&amp;mod=voicemail&amp;sub=<?php echo $ext?>&amp;folder=<?php echo $folder['folder']?>" class="folder-inner"><?php echo $folder['name']?> <span class="badge"><?php echo isset($folder['count']) ? $folder['count'] : 0?></span></a></div>
			<?php }?>
			</div>
		</div>
		<div class="col-md-10">
			<div class="table-responsive">
				<table class="table table-hover table-bordered message-table message-list">
					<thead>
					<tr class="message-header">
						<th class="visible-xs">Date</th>
						<th class="hidden-xs">Date</th>
						<th>Time</th>
						<th>CID</th>
						<th class="hidden-xs">Mailbox</th>
						<th class="hidden-xs">Length</th>
						<th>Play</th>
						<th class="visible-md visible-lg">Download</th>
					</tr>
					</thead>
				<?php if(!empty($messages)) {?>
					<?php foreach($messages as $message){?>
						<tr class="vm-message" data-msg="<?php echo $message['msg_id']?>" draggable="true">
							<td class="visible-xs"><?php echo date('m-d',$message['origtime'])?></td>
							<td class="hidden-xs"><?php echo date('Y-m-d',$message['origtime'])?></td>
							<td><?php echo date('h:m:sa',$message['origtime'])?></td>
							<td><?php echo $message['callerid']?></td>
							<td class="hidden-xs"><?php echo $message['origmailbox']?></td>
							<td class="hidden-xs"><?php echo $message['duration']?> sec</td>
							<td>|></td>
							<td class="visible-md visible-lg"><img src="modules/Voicemail/assets/images/browser_download.png" style="cursor:pointer;"></td>
						</tr>
					<?php }?>
				<?php } else { ?>
					<tr class="vm-message">
						<td colspan="7">No Messages</td>
					</tr>
				<?php } ?>
				</table>
			</div>
		</div>
	</div>
</div>