$('.extension-checkbox').change(function(event){
	var ext = $(this).data('extension');
	var name = $(this).data('name');
	if($(this).is(':checked')) {
		$('#settings-ext-list').append('<div class="settings-extensions" data-extension="'+ext+'"><label><input type="checkbox" name="ucp|settings[]" value="'+ext+'" checked> '+name+' &lt;'+ext+'&gt;</label><br /></div>');
	} else {
		$('.settings-extensions[data-extension="'+ext+'"]').remove();
	}
});
