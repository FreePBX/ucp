$(function() {
	if (Modernizr.draganddrop) {
		// Browser supports HTML5 DnD.
		
		enable_drags();

		$('.mailbox .folder-list .folder').on('drop', function (event) {
			if (event.stopPropagation) {
				event.stopPropagation(); // Stops some browsers from redirecting.
			}
		    if (event.preventDefault) {
				event.preventDefault(); // Necessary. Allows us to drop.
		    }
			//do teh folder move stuff here
			var msg = event.originalEvent.dataTransfer.getData("msg");
			var folder = $(event.currentTarget).data('folder');
			var data = {"msg":msg,"folder":folder}
			voicemail_ajax('moveToFolder',data)
			if(true) {
				$(this).removeClass("hover");
				var dragSrc = $('.message-list .vm-message[data-msg="'+msg+'"]');
				dragSrc.remove();
				$(".vm-temp").remove();
				var badge = $(event.currentTarget).find('.badge');
				badge.text(Number(badge.text()) + 1);
				var badge = $('.mailbox .folder-list .folder.active').find('.badge');
				badge.text(Number(badge.text()) - 1);
			}
		});
		$('.mailbox .folder-list .folder').on('dragover', function (event) {
		    if (event.preventDefault) {
				event.preventDefault(); // Necessary. Allows us to drop.
		    }
			$(this).addClass("hover");
		});
		$('.mailbox .folder-list .folder').on('dragenter', function (event) {
			$(this).addClass("hover");
		});
		$('.mailbox .folder-list .folder').on('dragleave', function (event) {
			$(this).removeClass("hover");
		});
	} else {
		// Fallback to a library solution.
		console.log('no drag')
	}
	
	$(document).on('click', '[vm-pjax] a, a[vm-pjax]', function(event) {
		var container = $('#dashboard-content')
		$.pjax.click(event, {container: container})
		enable_drags();
	})
})

function voicemail_ajax(command,data) {
	$.post( "index.php?quietmode=1&module=voicemail&command="+command, data, function( data ) {
		if(data.status) {
			return true;
		} else {
			return false;
		}
	});
}

function enable_drags() {
	$('.mailbox .vm-message').on('drop', function (event) {
	});
	$('.mailbox .vm-message').on('dragstart', function (event) {
		$(this).fadeTo( "fast" , 0.5);
		event.originalEvent.dataTransfer.effectAllowed = 'move';
	    event.originalEvent.dataTransfer.setData('msg', $(this).data("msg"));
	});
	$('.mailbox .vm-message').on('dragend', function (event) {
		$(".vm-temp").remove();
	    $(this).fadeTo( "fast" , 0.99);
	});
	$('.mailbox .vm-message').on('dragenter', function (event) {
		$(".vm-temp").remove();
		$(this).before( '<tr class="vm-temp" data-msg="h"><td colspan="7">&nbsp;</td></tr>' );
		$('.vm-temp').on('dragover', function (event) {
		    if (event.preventDefault) {
				event.preventDefault(); // Necessary. Allows us to drop.
		    }
		});
		$('.vm-temp').on('drop', function (event) {
			if (event.stopPropagation) {
				event.stopPropagation(); // Stops some browsers from redirecting.
			}
			if(true) {
				var msg = event.originalEvent.dataTransfer.getData("msg");
				var dragSrc = $('.message-list .vm-message[data-msg="'+msg+'"]');
				$(this).replaceWith('<tr class="vm-message" data-msg="'+msg+'" draggable="true">'+dragSrc.html()+'</tr>');
				dragSrc.remove();
				enable_drags();
			}
		});
	})
}