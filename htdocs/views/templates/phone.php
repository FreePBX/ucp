<div class="phone-box" data-id="<?php echo $id?>" style="display:none">
	<div class="title-bar" data-id="<?php echo $id?>">
		<div class="type">
			<i class="fa fa-phone"></i>
		</div>
		<div class="name">
			<div class="title">Call</div>
			<div class="message-container">
				<span class="message"><?php echo $message?></span>
			</div>
		</div>
		<div class="actions">
			<i class="fa fa-times cancelExpand"></i><i class="fa fa-arrow-up"></i>
		</div>
	</div>
	<div class="window">
		<div class="input-container">
			<div class="input-group">
				<input type="text" class="form-control dialpad">
				<span class="input-group-btn">
					<button class="btn btn-default clear-input" type="button"><i class="fa fa-times"></i></button>
				</span>
			</div>
		</div>
		<table class="keypad">
			<tr>
				<td class="btn btn-default upper-left" data-num="1">
					<div class="num">1</div>
					<div class="letters">&nbsp;</div>
				</td>
				<td class="btn btn-default" data-num="2">
					<div class="num">2</div>
					<div class="letters">ABC</div>
				</td>
				<td class="btn btn-default upper-right" data-num="3">
					<div class="num">3</div>
					<div class="letters">DEF</div>
				</td>
			</tr>
			<tr>
				<td class="btn btn-default" data-num="4">
					<div class="num">4</div>
					<div class="letters">GHI</div>
				</td>
				<td class="btn btn-default" data-num="5">
					<div class="num">5</div>
					<div class="letters">JKL</div>
				</td>
				<td class="btn btn-default" data-num="6">
					<div class="num">6</div>
					<div class="letters">MNO</div>
				</td>
			</tr>
			<tr>
				<td class="btn btn-default" data-num="7">
					<div class="num">7</div>
					<div class="letters">PQRS</div>
				</td>
				<td class="btn btn-default" data-num="8">
					<div class="num">8</div>
					<div class="letters">TUV</div>
				</td>
				<td class="btn btn-default" data-num="9">
					<div class="num">9</div>
					<div class="letters">WXYZ</div>
				</td>
			</tr>
			<tr>
				<td class="btn btn-default lower-left" data-num="*">
					<div class="num">*</div>
					<div class="letters">&nbsp;</div>
				</td>
				<td class="btn btn-default" data-num="0">
					<div class="num">0</div>
					<div class="letters">+</div>
				</td>
				<td class="btn btn-default lower-right" data-num="#">
					<div class="num">#</div>
					<div class="letters">&nbsp;</div>
				</td>
			</tr>
		</table>
		<table class="actions">
			<tr>
				<td class="left">
					<button data-type="<?php echo $state?>" class="btn btn-primary action">Call</button>
				</td>
				<td class="right">
					<button class="btn btn-success secondaction">Hold</button>
				</td>
			</tr>
		</table>
	</div>
</div>
