<?php

	/*
	|| #################################################################### ||
	|| #                             ArrowChat                            # ||
	|| # ---------------------------------------------------------------- # ||
	|| #    Copyright �2010-2012 ArrowSuites LLC. All Rights Reserved.    # ||
	|| # This file may not be redistributed in whole or significant part. # ||
	|| # ---------------- ARROWCHAT IS NOT FREE SOFTWARE ---------------- # ||
	|| #   http://www.arrowchat.com | http://www.arrowchat.com/license/   # ||
	|| #################################################################### ||
	*/
	
	header("Expires: Mon, 26 Jul 1990 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");

	// ########################## INCLUDE BACK-END ###########################
	require_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'bootstrap.php');
	require_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . AC_FOLDER_INCLUDES . DIRECTORY_SEPARATOR . 'init.php');
	require_once (dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'functions_send.php');

	// ########################### GET POST DATA #############################
	$chatroomid = get_var('chatroomid');
	$message 	= get_var('message');
	$s_message	= sanitize($message);
	
	// Get the username of the user sending the message
	if (check_if_guest($userid))
	{
		$username = strip_tags(create_guest_username($userid, $guest_name));
	}
	else
	{
		$username = strip_tags(get_username($userid));
	}

	// ######################### START POST MESSAGE ##########################
	if (!is_null($message)) 
	{
		// Check if message length is too long
		if (strlen($message) > $chatroom_message_length)
		{
			if (preg_match('@file[{]([0-9]{13})[}][{](.*)[}]@', $message, $match) OR preg_match('@video[{](.*)[}]@', $message, $match) OR preg_match('@image[{]([0-9]{13})[}][{](.*)[}]@', $message, $match) OR preg_match('@giphy[{](.*)[}][{](.*)[}]@', $message, $match))
			{
			}
			else
			{
				close_session();
				exit(0);
			}
		}
		
		if (logged_in($userid)) 
		{
			$chatroom_admin = 0;
			$chatroom_mod = 0;
			
			// Start Message Limit
			$result = $db->execute("
				SELECT limit_message_num, limit_seconds_num, name
				FROM arrowchat_chatroom_rooms
				WHERE id = '" . $db->escape_string($chatroomid) . "'
			");
			
			$row = $db->fetch_array($result);
			$limit_message_num = $row['limit_message_num'] - 1;
			$limit_seconds_num = $row['limit_seconds_num'];
			$chatroom_name = $row['name'];
			
			$result = $db->execute("
				SELECT sent
				FROM arrowchat_chatroom_messages
				WHERE user_id = '" . $db->escape_string($userid) . "'
					AND chatroom_id = '" . $db->escape_string($chatroomid) . "'
				ORDER BY sent desc
				LIMIT " . $db->escape_string($limit_message_num) . ", 1
			");
			
			$first_message = 0;
			$messages_are_limited = false;
			
			if ($row = $db->fetch_array($result))
			{
				$first_message = $row['sent'];
			}
			
			if (time() - $first_message <= $limit_seconds_num)
			{
				$messages_are_limited = true;
				$time_to_talk = $limit_seconds_num - (time() - $first_message);
			}
			// End Message Limit
			
			// Start Banned Check
			$result = $db->execute("
				SELECT ban_length, ban_time 
				FROM arrowchat_chatroom_banlist 
				WHERE (user_id = '" . $db->escape_string($userid) . "'
						AND chatroom_id = '" . $db->escape_string($chatroomid) . "')
					OR (ip_address = '" . $db->escape_string($user_ip) . "'
						AND chatroom_id = '" . $db->escape_string($chatroomid) . "')
				ORDER BY ban_time DESC
				LIMIT 1
			");
			
			if ($result AND $db->count_select() > 0) 
			{
				$row = $db->fetch_array($result);
				
				if ((empty($row['ban_length']) OR ((($row['ban_length'] * 60) + $row['ban_time']) > time())) AND $is_admin != 1) 
				{
					if (empty($row['ban_length']))
					{
						$error[] = array('t' => '1', 'm' => $language[55] . $chatroom_name);
					}
					else
					{
						$error[] = array('t' => '1', 'm' => $language[56] . $chatroom_name . $language[219] . $row['ban_length'] . $language[220]);
					}
					
					$response['error'] = $error;
					header('Content-type: application/json; charset=utf-8');
					echo json_encode($response);
					close_session();
					exit;
				}
			}
			// End Banned Check
			
			$result = $db->execute("
				SELECT is_admin, is_mod, silence_length, silence_time
				FROM arrowchat_chatroom_users
				WHERE user_id = '" . $db->escape_string($userid) . "'
					AND chatroom_id = '" . $db->escape_string($chatroomid) . "'
			");
			
			if ($row = $db->fetch_array($result))
			{
				if ($row['is_admin'] == 1 OR $is_admin == 1) 
				{
					$chatroom_admin = 1;
					$messages_are_limited = false;
				}
					
				if ($row['is_mod'] == 1 OR $is_mod == 1)
				{
					$chatroom_mod = 1;
					$messages_are_limited = false;
				}
			}
			
			// *** Start Group Permissions Check ***
			$disable_sending_group_msg = 0;
			
			if (check_array_for_match($group_id, $group_disable_sending_rooms_sep))
			$disable_sending_group_msg = 1;
			
			// Reverse group settings if enable mode is on
			if ($group_enable_mode == 1)
			{
				$disable_sending_group_msg = 1;
				
				if (check_array_for_match($group_id, $group_disable_sending_rooms_sep))
					$disable_sending_group_msg = 0;
			}
			// *** End Group Permissions Check ***
			
			if (empty($disable_sending_group_msg))
			{
				if (!$messages_are_limited)
				{
					if (empty($row['silence_time']) OR $row['silence_time'] + $row['silence_length'] < time())
					{
						$db->execute("
							INSERT INTO arrowchat_chatroom_messages (
								chatroom_id,
								user_id,
								username,
								message,
								global_message,
								is_mod,
								is_admin,
								sent
							) 
							VALUES (
								'" . $db->escape_string($chatroomid) . "', 
								'" . $db->escape_string($userid) . "', 
								'" . $db->escape_string($username) . "',
								'" . $db->escape_string($s_message) . "',
								'0',
								'" . $db->escape_string($chatroom_mod) . "',
								'" . $db->escape_string($chatroom_admin) . "',
								'" . time() . "'
							)
						");
						
						$last_id = $db->last_insert_id();
						
						// Update message history totals
						$result = $db->execute("
							SELECT sent
							FROM arrowchat_chatroom_messages
							ORDER BY id DESC
							LIMIT 1, 1
						");
						
						$date = time();
						$insert_date = date('Ymd', $date);
						
						if ($row = $db->fetch_array($result))
						{
							$last_date = date('Ymd', $row['sent']);
							
							if ($last_date != $insert_date && !empty($last_date))
							{
								$date1 = strtotime( $last_date );
								$date2 = strtotime( $insert_date );
								
								$days = count_days($date1, $date2);
								for ($i = 0; $i < $days; $i++) {
									$db->execute("
										INSERT INTO arrowchat_graph_log (
											date,
											chat_room_messages
										) 
										VALUES (
											'" . date('Ymd', $date1+(86400*$i)) . "',
											'0'
										) 
										ON DUPLICATE KEY 
											UPDATE chat_room_messages = chat_room_messages
									");	
								}
							}
							
							$db->execute("
								INSERT INTO arrowchat_graph_log (
									date,
									chat_room_messages
								) 
								VALUES (
									'" . $db->escape_string($insert_date) . "',
									'1'
								) 
								ON DUPLICATE KEY 
									UPDATE chat_room_messages = (chat_room_messages + 1)
							");
						}
						else
						{
							$db->execute("
								INSERT INTO arrowchat_graph_log (
									date,
									chat_room_messages
								) 
								VALUES (
									'" . $db->escape_string($insert_date) . "',
									'1'
								) 
							");
						}
						
						if ($push_on == 1)
						{
							push_publish($push_encrypt . '_chatroom' . $chatroomid, array('chatroommessage' => array("id" => $last_id, "name" => $username, "message" => $s_message, "userid" => $userid, "sent" => time(), "global" => '0', "mod" => $chatroom_mod, "admin" => $chatroom_admin, "chatroomid" => $chatroomid)));
						}

						echo $last_id;
					}
					else
					{
						$silence_time = $row['silence_time'] + $row['silence_length'] - time();
						$silence_message = $language[164] . $silence_time . $language[165];
						
						$error[] = array('t' => '1', 'm' => $silence_message);
						$response['error'] = $error;
						header('Content-type: application/json; charset=utf-8');
						echo json_encode($response);
					}
				} else {
					$flood_message = $language[169] . $time_to_talk . $language[170];
					
					$error[] = array('t' => '1', 'm' => $flood_message);
					$response['error'] = $error;
					header('Content-type: application/json; charset=utf-8');
					echo json_encode($response);
					close_session();
					exit;
				}
			}
			else
			{
				$error[] = array('t' => '1', 'm' => $language[209]);
				$response['error'] = $error;
				header('Content-type: application/json; charset=utf-8');
				echo json_encode($response);
				close_session();
				exit;
			}
			
			close_session();
			exit(0);
		}
	}

?>