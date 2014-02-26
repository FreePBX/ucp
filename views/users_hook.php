<style>
.indent-div {
	margin-left: 15px;
}
</style>
<table>
    <tr class="guielToggle" data-toggle_class="UCP">
        <td colspan="2" ><h4><span class="guielToggleBut">-  </span><?php echo _("UCP")?></h4><hr></td>
    </tr>
	<tr>
		<td colspan="2">
			<div class="indent-div">
				<table>
				    <tr class="UCP">
				        <td colspan="2"><h5><?php echo _("Module Settings")?></h5><hr></td>
				    </tr>
					<tr class="UCP">
				    	<td colspan="2">
				    		<?php echo $mHtml;?>
				    	</td>
					</t>
				    <tr class="UCP">
				        <td colspan="2"><h5><?php echo _("Active Sessions")?></h5><hr></td>
				    </tr>
					<?php foreach($sessions as $session) {?>
				    <tr class="UCP">
				        <td><?php echo $session['address']?></td>
				        <td><a href="?display=userman&amp;action=showuser&amp;user=<?php echo $user['id']?>&amp;deletesession=<?php echo $session['session']?>"><img src="images/trash.png"></a></td>
				    </tr>
					<?php } ?>
				</table>
			</div>
		</td>
	</tr>
</table>