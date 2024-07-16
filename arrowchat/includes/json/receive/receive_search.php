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

	// ########################## INCLUDE BACK-END ###########################
	require_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'bootstrap.php');
	require_once (dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . AC_FOLDER_INCLUDES . DIRECTORY_SEPARATOR . 'init.php');

	// ########################### INITILIZATION #############################
	$response = array();
	$searched_users = array();
	$merged_results = array();
	$rows1 = array();
	$rows2 = array();
	$search_name = get_var('search_name');
	$time = time();
	$search_results = false;
	$friends_list_enabled = false;
	$old_online_timeout = $online_timeout;

	// ###################### START SEARCH RECEIVE ######################
	if (logged_in($userid) AND strlen($search_name) >= 2) 
	{
		if ($disable_buddy_list == 1 OR check_if_guest($userid) OR NO_FREIND_SYSTEM == 1 OR ($is_admin == 1 AND $admin_chat_all == 1) OR ($is_mod == 1 AND $admin_chat_all == 1))
		{
			// Get search results for all users both online and offline
			
			$result = $db->execute("			
				SELECT DISTINCT " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . " name, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " userid, arrowchat_status.status status, arrowchat_status.session_time session_time, arrowchat_status.is_admin is_admin
				FROM " . TABLE_PREFIX . DB_USERTABLE . " 
				INNER JOIN arrowchat_status 
					ON " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " = arrowchat_status.userid 
				WHERE LOWER(" . DB_USERTABLE_NAME . ") 
					LIKE '%" . $db->escape_string(strtolower($search_name)) . "%'
				ORDER BY
				  CASE
					WHEN LOWER(name) LIKE '" . $db->escape_string(strtolower($search_name)) . "%' THEN 1
					WHEN LOWER(name) LIKE '%" . $db->escape_string(strtolower($search_name)) . "' THEN 2
					ELSE 3
				  END,
				  session_time DESC
				LIMIT 25
			");
			
			if ($result AND $db->count_select() > 0) 
			{
				while ($rows1[] = $db->fetch_array($result)){}
				
				$search_results = true;
			}
			
			$result = $db->execute("
				SELECT userid, guest_name name, session_time, status
				FROM arrowchat_status
				WHERE LOWER(guest_name) 
					LIKE '%" . $db->escape_string(strtolower($search_name)) . "%'
				ORDER BY
				  CASE
					WHEN LOWER(name) LIKE '" . $db->escape_string(strtolower($search_name)) . "%' THEN 1
					WHEN LOWER(name) LIKE '%" . $db->escape_string(strtolower($search_name)) . "' THEN 2
					ELSE 3
				  END,
				  session_time DESC
				LIMIT 25
			");
			
			if ($result AND $db->count_select() > 0)
			{
				while ($rows2[] = $db->fetch_array($result)){}
				
				$search_results = true;
			}
			
			$merged_results = array_merge($rows1, $rows2);
		} 
		else 
		{
			// Get search results for users that are on the friend's list only
			
			$friends_list_enabled = true;
			$online_timeout = time();
			$sql = get_friend_list($userid, 0);
			
			$sql = str_replace("WHERE ", "WHERE LOWER(" . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . ") LIKE '%" . $db->escape_string(strtolower($search_name)) . "%' AND ", $sql);
			$sql = str_replace("ORDER BY ", "ORDER BY CASE WHEN LOWER(username) LIKE '" . $db->escape_string(strtolower($search_name)) . "%' THEN 1 WHEN LOWER(username) LIKE '%" . $db->escape_string(strtolower($search_name)) . "' THEN 2 ELSE 3 END, ", $sql);
			$sql = $sql . " LIMIT 25";

			$result = $db->execute($sql);
			
			if ($result AND $db->count_select() > 0) 
			{
				while ($rows1[] = $db->fetch_array($result)){}
				
				$search_results = true;
			}
			
			$merged_results = array_merge($rows1, $rows2);
		}
		
		if ($search_results) 
		{
			// Process SQL search results
			
			$online_timeout = $old_online_timeout;
			
			if (!empty($block_chats))
			{
				$block_chats_unserialized = unserialize($block_chats);
				
				if (!is_array($block_chats_unserialized))
				{
					$block_chats_unserialized = array();
				}
			}
			else
			{
				$block_chats_unserialized = array();
			}
			
			foreach ($merged_results as $row) 
			{
				if (!empty($row))
				{
					if (!empty($row['lastactivity']))
					{
						$row['session_time'] = $row['lastactivity'];
					}
					
					if (!empty($row['username']))
					{
						$row['name'] = $row['username'];
					}
					
					if ($row['userid'] != $userid) 
					{
						if ((($time-$row['session_time']) < $online_timeout) AND $row['status'] != 'invisible' AND $row['status'] != 'offline') 
						{
							if ($row['status'] != 'busy' AND $row['status'] != 'away') 
							{
								$row['status'] = 'available';
							}
						} 
						else 
						{
							if ($row['status'] == 'invisible') 
							{
								$row['status'] = 'offline';
							} 
							else
							{
								$row['status'] = 'offline';
							}
						}
						
						if (empty($row['is_admin']))
						{
							$row['is_admin'] = 0;
						}
						
						$show_user = false;
						
						if ($friends_list_enabled)
						{
							$show_user = true;
						}
						else if (check_if_guest($userid))
						{
							if ($guests_chat_with == 1)
							{
								if (check_if_guest($row['userid']))
									$show_user = true;
							}
							else if ($guests_chat_with == 2)
							{
								$show_user = true;
							}
							else if ($guests_chat_with == 3)
							{
								if (!check_if_guest($row['userid']))
									$show_user = true;
							}
							else if ($guests_chat_with == 4)
							{
								if ($row['is_admin'] == 1)
									$show_user = true;
							}
							else
							{
								$show_user = true;
							}
						}
						else
						{
							if ($users_chat_with == 1)
							{
								if (check_if_guest($row['userid']))
									$show_user = true;
							}
							else if ($users_chat_with == 2)
							{
								$show_user = true;
							}
							else if ($users_chat_with == 3)
							{
								if (!check_if_guest($row['userid']))
									$show_user = true;
							}
							else if ($guests_chat_with == 4)
							{
								if ($row['is_admin'] == 1)
									$show_user = true;
							}
							else
							{
								$show_user = true;
							}
						}
						
						if ($hide_admins_buddylist == 1 AND $row['is_admin'] == 1)
						{
							$show_user = false;
						}
						
						if (($is_admin == 1 AND $admin_chat_all == 1) OR ($is_mod == 1 AND $admin_chat_all == 1))
						{
							$show_user = true;
						}
						
						if (check_if_guest($row['userid']))
						{
							$avatar = $base_url . AC_FOLDER_ADMIN . "/images/img-no-avatar.png";
						}
						else
						{
							if (DB_USERTABLE_AVATAR == DB_USERTABLE_USERID)
								$avatar = get_avatar($row['userid'], $row['userid']);
							else {
								$sql_user = get_user_details($row['userid']);
								$user_result = $db->execute($sql_user);
			
								if ($user_result AND $db->count_select() > 0) 
								{
									$user_row = $db->fetch_array($user_result);
									$avatar = get_avatar($user_row['avatar'], $row['userid']);
								}
								else
								{
									$avatar = get_avatar('', $row['userid']);
								}
							}
						}
						
						if (!in_array($row['userid'], $block_chats_unserialized))
						{
							if (!empty($row['name']) AND $show_user)
							{
								$searched_users[] = array('id' => $row['userid'], 'name' => $row['name'], 'avatar' => $avatar, 'status' => $row['status']);
							}
						}
					}
				}
			}

			if (!empty($searched_users)) 
			{
				$response['search'] = $searched_users;
			}
		}
	}

	header('Content-type: application/json; charset=utf-8');
	echo json_encode($response);
	close_session();
	exit;

?>