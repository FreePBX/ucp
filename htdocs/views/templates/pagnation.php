<ul class="pagination pagination-sm">
	<?php if($activePage > 1) { ?>
		<li>
			<a vm-pjax href="<?php echo $link?>&amp;page=1"><?php echo _('First')?></a>
		</li>
	<?php } ?>
	<li <?php echo ($startPage == 1) ? 'class="disabled"' : ''?>>
		<a <?php echo ($startPage != 1) ? 'vm-pjax href="'.$link.'&amp;page='.($startPage - 1).'"' : '' ?>>&laquo;</a>
	</li>
	<?php for($i=$startPage;$i<=$endPage;$i++) {?>
		<li <?php echo ($activePage == $i) ? 'class="active"' : ''?>>
			<a vm-pjax href="<?php echo $link?>&amp;page=<?php echo $i?>"><?php echo $i?> <?php echo ($activePage == $i) ? '<span class="sr-only">(current)</span>' : ''?></a>
		</li>
	<?php } ?>
	<li <?php echo ($endPage == $totalPages) ? 'class="disabled"' : ''?>>
		<a <?php echo ($endPage != $totalPages) ? 'vm-pjax href="'.$link.'&amp;page='.($endPage + 1).'"' : '#' ?>>&raquo;</a>
	</li>
	<?php if($activePage != $totalPages) { ?>
		<li>
			<a vm-pjax href="<?php echo $link?>&amp;page=<?php echo $totalPages?>"><?php echo _('Last')?></a>
		</li>
	<?php } ?>
</ul>
