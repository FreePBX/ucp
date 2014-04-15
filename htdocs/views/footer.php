		</div>
		<?php foreach($scripts as $script) {?>
			<script type="text/javascript" src="<?php echo $script ?>"></script>
		<?php } ?>
		<script>var modules = <?php echo $modules?></script>
		<div id="shade"></div>
	</body>
</html>
