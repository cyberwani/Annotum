<?php


/*
	- Actor 								:: Event 						:: Event data
	--------------------------------------------------------------------------------------------------------------------------------
	- user who can edit a post 				:: edit/revision created 		:: revision ID
	- user that can transition post state 	:: post transitioned states		:: old state, new state (corresponds to global state array)
	- reviewer or editor+ 					:: Reviewer comment created 	:: comment ID being replied to (if applicable)
	- reviewer 								:: Review submitted				:: review (corresponds to a global array used for translation purposes)
	- author, co-author, editor+ 			:: Internal comment created		:: comment ID being replied to (if applicable)
	- author, editor+						:: Added co-author				:: co-author ID
	- author, editor+						:: Removed co-author			:: co-author ID
	- author, editor+ 						:: Author changed				:: author ID
	- editor+								:: Added reviewer				:: reviewer ID
	- editor+								:: Removed reviewer				:: reviewer ID
 */

/**
 * Saves an event in post meta to be used when outputting an audit log.
 * 
 * @param int $post_id ID of the post that the event occurred for
 * @param int $actor_id ID of the user that created the event
 * @param int $event_id ID of the event corresponding to an events array. See $event_array in function annowf_audit_log
 * @param array $data Any data associated with the event.
 * @return bool True if the event was saved, false otherwise
 */ 
function annowf_save_audit_item($post_id, $actor_id, $event_id, $data = array()) {
	$num_items = get_post_meta($post_id, '_anno_audit_count', true);
	if (empty($num_items)) {
		$num_items = 0;
	}
	
	$audit_item = array(
		'actor' => $actor_id,
		'event' => $event_id,
		'time' => time(),
		'data' => $data,
	);

	if (update_post_meta($post_id, '_anno_audit_item_'.($num_items + 1), $audit_item)) {
		update_post_meta($post_id, '_anno_audit_count', $num_items + 1);
		return true;
	}
	return false;
}

/**
 * Displays an audit log of events for a given post.
 * 
 * @param int $post_id The ID of the post to display the log for.
 * @return void
 */ 
function annowf_audit_log($post) {
	$post_id = $post->ID;
	$html = '';
	$num_items = get_post_meta($post_id, '_anno_audit_count', true);
	$items = array();
	for ($i = 1; $i <= $num_items; $i++) { 
		$items[] = get_post_meta($post_id, '_anno_audit_item_'.$i, true);
	}
	
	if (empty($items)) {
		return $html;
	}

	// Indexes start at 1 to prevent empty() check failures
	$event_array = array(
		0 => '',
		1 => _x('Created a revision', 'article audit event', 'anno'),
		2 => _x('Transitioned post state from %s to %s', 'article audit event', 'anno'),
		3 => _x('Added a reviewer comment', 'article audit event', 'anno'),
		4 => _x('Added a review of %s', 'article audit event', 'anno'),
		5 => _x('Added an internal comment', 'article audit event', 'anno'),
		6 => _x('Added %s as a co-author', 'article audit event', 'anno'),
		7 => _x('Removed %s as a co-author', 'article audit event', 'anno'),
		8 => _x('Added %s as a reviewer', 'article audit event', 'anno'),
		9 => _x('Removed %s as a reviewer', 'article audit event', 'anno'),
	);

	foreach ($items as $item) {
		$html = '';
		$actor = get_userdata(absint($item['actor']));
		if (!empty($actor)) {
			$edit_url = anno_edit_user_url($actor->ID);
			$html .= '<strong><a href="'.$edit_url.'">'.$actor->user_login.'</a></strong>';
		}
		
		if (!empty($item['event'])) {
			$event = absint($item['event']);
			$event_html = '';
			switch ($event) {
				case 1:
					if (!empty($item['data']) && is_array($item['data'])) {
						if (current_user_can('edit_post', absint($item['data'][0])) && $rev_url = get_edit_post_link(absint($item['data'][0]))) {
							$event_html .= '<a href="'.$rev_url.'">'.$event_array[$event].'</a>';
						}
					}
					break;
				case 3:
				case 5:
					if (!empty($item['data']) && is_array($item['data'])) {
						$comment = get_comment(absint($item['data'][0]));
						if (!empty($comment)) {
							//TODO possibly process current URL and just add #comment-$comment->comment_ID when appropriate
							$comment_url = get_edit_post_link($comment->comment_post_ID).'#comment-'.$comment->comment_ID;
							$event_html .= '<a href="'.$comment_url.'">'.$event_array[$event].'</a>';
						}
					}
					break;
				case 2:
					if (!empty($item['data']) && is_array($item['data'])) {
						global $annowf_states;
						$event_html .= sprintf($event_array[$event], $annowf_states[$item['data'][0]], $annowf_states[$item['data'][1]]);
					}
					break;
				case 4:
					if (!empty($item['data']) && is_array($item['data'])) {
						global $anno_review_options;
						$event_html .= sprintf($event_array[$event], $anno_review_options[$item['data'][0]]);
					}
					break;
				case 6:
				case 7:
				case 8:
				case 9:
					if (!empty($item['data']) && is_array($item['data'])) {
						$user = get_userdata(absint($item['data'][0]));
						$user_markup = '<strong><a href="'.$edit_url.'">'.$actor->user_login.'</a></strong>';
						$event_html .= sprintf($event_array[$event], $user_markup);
					}
					break;
				default:
					break;
			}
			
			if (empty($event_html)) {
				$event_html = $event_array[$event];
			}

			$html .= $event_html;
		}

		
		if (!empty($item['time']) && is_numeric($item['time'])) {
			$html .= date(_x('j F, Y @ H:i', 'audit log date format', 'anno'), absint($item['time']));
		}
		echo '<div>'.$html.'</div>';
	}
		
}

/**
 * Returns an appropriate link for editing a given user.
 * 
 * @param int $user_id The id of the user to get the url for
 * @return string edit user url
 */ 
function anno_edit_user_url($user_id) {
	if ( get_current_user_id() == $user_id ) {
		$edit_url = 'profile.php';
	}
	else {
		$edit_url = esc_url( add_query_arg( 'wp_http_referer', urlencode( stripslashes( $_SERVER['REQUEST_URI'] ) ), "user-edit.php?user_id=$user_id" ) );
	}
	return $edit_url;
	
}


?>