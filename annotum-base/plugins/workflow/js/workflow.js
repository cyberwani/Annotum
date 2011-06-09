jQuery(document).ready( function($) {
	// Type-ahead
	$('.user-input').suggest( 'admin-ajax.php?action=anno-user-search', { delay: 200, minchars: 2, multiple: false} );
	
	$('input[type="submit"], a.submitdelete, #submitpost').click(function() {
		$(this).siblings('.ajax-loading').css('visibility', 'visible');
	});

	function anno_add_co_author() {
		var user = $('input[type="text"]#co-author-input').val();
		if (user == '') {
			return false;
		}
		var data = {action: 'anno-add-co-author', user: user, post_id: ANNO_POST_ID};
		data['_ajax_nonce-manage-co_author'] = $('#_ajax_nonce-manage-co_author').val();
		$.post(ajaxurl, data, function(d) {
			if (d.message == 'success') {
				$('ul#co-author-list').prepend(d.html);
				// Append co-author to author dropdown
				$('#post_author_override').append(d.author);
				// Clear error box
				$('#co-author-add-error').html('').hide();
				//Clear input box
				$('input[type="text"]#co-author-input').val('');
			}
			else {
				$('#co-author-add-error').html(d.html).show();
			}
		}, 'json');
	}
	
	$('input[type="text"]#co-author-input').keydown(function(e) {
		if (e.keyCode && e.keyCode == 13) {
			anno_add_co_author();
			return false;
		}
	});
	$('input[type="button"]#co-author-add').click(anno_add_co_author);
	
	function anno_add_reviewer() {
		var user = $('input[type="text"]#reviewer-input').val();
		if (user == '') {
			return false;
		}

		var data = {action: 'anno-add-reviewer', user: user, post_id: ANNO_POST_ID};
		data['_ajax_nonce-manage-reviewer'] = $('#_ajax_nonce-manage-reviewer').val();

		$.post(ajaxurl, data, function(d) {
			if (d.message == 'success') {
				$('ul#reviewer-list').prepend(d.html);
				$('#reviewer-add-error').html('').hide();
				$('input[type="text"]#reviewer-input').val('');
			}
			else {
				$('#reviewer-add-error').html(d.html).show();
			}
		}, 'json');
	}
	
	$('input[type="text"]#reviewer-input').keydown(function(e) {
		if (e.keyCode && e.keyCode == 13) {
			anno_add_reviewer();
			return false;
		}
	});
	$('input[type="button"]#reviewer-add').click(anno_add_reviewer);
	
	
	
	$('.anno-user-remove').live('click', function() {
		var click = $(this);
		var type = $(this).closest('ul').attr('data-type');
		var user_id = $(this).closest('li').attr('id').replace('user-', '');
		var data = {action: 'anno-remove-' + type, user_id: user_id, post_id: ANNO_POST_ID};
		data['_ajax_nonce-manage-' + type] = $('#_ajax_nonce-manage-' + type).val();
		
		$.post(ajaxurl, data, function(d) {
			if (d.message == 'success') {
				click.closest('li').fadeOut();
				// Remove from author dropdown menu
				if (type == 'co_author') {
					$('#post_author_override option[value=' + user_id + ']').remove();
				}
			}
		}, 'json');
		return false;
	});
	
});

