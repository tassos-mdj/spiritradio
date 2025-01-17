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
	
	// UNCOMMENT THE SESSION START IF YOU ARE USING SESSIONS TO GET THE USER ID
	// session_start();

	/**
	 * This function returns the user ID of the logged in user on your site.  Technical support will not
	 * help you with this for stand-alone installations.  You must purchase the professional installation
	 * if you are having trouble.
	 *
	 * Suggestion: Check out the other integration files in the functions/integrations directory for
	 * many examples of how this can be done.  The easiest way is to get the user ID through a cookie.
	 *
	 * @return the user ID of the logged in user or NULL if not logged in
	 */
	function get_user_id() 
	{
		global $db;
		
		$userid = NULL;
		
		foreach ($_COOKIE as $key => $val)
		{
			if (preg_match("/ips4_member_id/", $key))
			{
				$cookie_name = $key;
			}
			
			if (preg_match("/ips4_pass_hash/", $key))
			{
				$cookie_pass = $key;
			}
			
			if (preg_match("/ips4_IPSSessionFront/", $key))
			{
				$session_id = $key;
			}
			
			if (preg_match("/ips4_login_key/", $key))
			{
				$login_key = $key;
			}
		}

		if (isset($_COOKIE[$cookie_name]) AND !empty($_COOKIE[$cookie_name])) 
		{
			$result = $db->execute("
				SELECT " . DB_USERTABLE_USERID . " userid
				FROM " . TABLE_PREFIX . DB_USERTABLE . "
				WHERE member_id = '" . $db->escape_string($_COOKIE[$cookie_name]) . "'
					AND member_login_key = '" . $db->escape_string($_COOKIE[$cookie_pass]) . "'
			");

			if ($row = $db->fetch_array($result))
			{
				if (!empty($row['userid']))
					$userid = $row['userid'];
			}
		}
		
		if (empty($userid) AND (isset($_COOKIE[$session_id]) AND !empty($_COOKIE[$session_id])))
		{
			$result = $db->execute("
				SELECT member_id
				FROM " . TABLE_PREFIX . "core_sessions
				WHERE id = '" . $db->escape_string($_COOKIE[$session_id]) . "'
			");

			if ($row = $db->fetch_array($result))
			{
				if (!empty($row['member_id']))
					$userid = $row['member_id'];
			}
		}
		
		if (empty($userid) AND (isset($_COOKIE[$login_key]) AND !empty($_COOKIE[$login_key])))
		{
			$result = $db->execute("
				SELECT member_id
				FROM " . TABLE_PREFIX . "core_members_known_devices
				WHERE login_key = '" . $db->escape_string($_COOKIE[$login_key]) . "'
			");

			if ($row = $db->fetch_array($result))
			{
				if (!empty($row['member_id']))
					$userid = $row['member_id'];
			}
		}

		return $userid;
	}

	/**
	 * This function returns the SQL statement for the buddylist of the user.  You should retrieve
	 * all ONLINE friends that the user is friends with.  Do not retrieve offline users.  You can use
	 * global $online_timeout to get the online timeout.
	 * ex: AND (arrowchat_status.session_time + 60 + " . $online_timeout . ") > " . time() . " 
	 *
	 * @param userid the user ID of the person receiving the buddylist
	 * @param the time of the buddylist request
	 * @return the SQL statement to retrieve the user's friend list
	 */
	function get_friend_list($userid, $time)
	{
		global $db;
		global $online_timeout;
		
		$sql = ("
			SELECT DISTINCT " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " userid, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . " username, arrowchat_status.session_time lastactivity, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_AVATAR . " avatar, CONCAT(" . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . ",'-'," . TABLE_PREFIX . DB_USERTABLE . ".members_seo_name) link, arrowchat_status.is_admin, arrowchat_status.status 
			FROM " . TABLE_PREFIX . DB_FRIENDSTABLE . " 
			JOIN " . TABLE_PREFIX . DB_USERTABLE . " 
				ON  " . TABLE_PREFIX . DB_FRIENDSTABLE . "." . DB_FRIENDSTABLE_FRIENDID . " = " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " 
			LEFT JOIN arrowchat_status 
				ON " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " = arrowchat_status.userid 
			WHERE " . TABLE_PREFIX . DB_FRIENDSTABLE . "." . DB_FRIENDSTABLE_USERID . " = '" . $db->escape_string($userid) . "' 
				AND arrowchat_status.session_time > (" . time() . " - " . $online_timeout . " - 60)
			ORDER BY " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . " ASC
		");
		
		return $sql; 
	}

	/**
	 * This function returns the SQL statement for all online users.  You should retrieve
	 * all ONLINE users regardless of friend status.  Do not retrieve offline users.  You can use
	 * global $online_timeout to get the online timeout.
	 * ex: AND (arrowchat_status.session_time + 60 + " . $online_timeout . ") > " . time() . " 
	 *
	 * @param userid the user ID of the person receiving the buddylist
	 * @param the time of the buddylist request
	 * @return the SQL statement to retrieve all online users
	 */
	function get_online_list($userid, $time) 
	{
		global $db;
		global $online_timeout;
		
		$sql = ("
			SELECT DISTINCT " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " userid, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . " username, arrowchat_status.session_time lastactivity, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_AVATAR . " avatar, CONCAT(" . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . ",'-'," . TABLE_PREFIX . DB_USERTABLE . ".members_seo_name) link, arrowchat_status.is_admin, arrowchat_status.status 
			FROM " . TABLE_PREFIX . DB_USERTABLE . " 
			JOIN arrowchat_status 
				ON " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " = arrowchat_status.userid 
			WHERE arrowchat_status.session_time > (" . time() . " - " . $online_timeout . " - 60)
				AND " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " != '" . $db->escape_string($userid) . "' 
			ORDER BY " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . " ASC
		");
		
		return $sql; 
	}

	/**
	 * This function returns the SQL statement to get the user details of a specific user.  You should
	 * get the user's ID, username, last activity time in unix, link to their profile, avatar, and status.
	 *
	 * @param userid the user ID to get the details of
	 * @return the SQL statement to retrieve the user's defaults
	 */
	function get_user_details($userid) 
	{
		global $db;
		
		$sql = ("
			SELECT " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " userid, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . " username, arrowchat_status.session_time lastactivity, CONCAT(" . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . ",'-'," . TABLE_PREFIX . DB_USERTABLE . ".members_seo_name) link,  " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_AVATAR . " avatar, arrowchat_status.is_admin, arrowchat_status.status 
			FROM " . TABLE_PREFIX . DB_USERTABLE . " 
			LEFT JOIN arrowchat_status 
				ON " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " = arrowchat_status.userid 
			WHERE " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " = '" . $db->escape_string($userid) . "'
		");
		
		return $sql;
	}

	/**
	 * This function returns the profile link of the specified user ID.
	 *
	 * @param userid the user ID to get the profile link of
	 * @return the link of the user ID's profile
	 */
	function get_link($link, $user_id) 
	{
		global $base_url;
		
		return $base_url . '../index.php?/profile/' . $link;
	}

	/**
	 * This function returns the URL of the avatar of the specified user ID.
	 *
	 * @param userid the user ID of the user
	 * @param image if the image includes more than just a user ID, this param is passed
	 * in from the avatar row in the buddylist and get user details functions.
	 * @return the link of the user ID's avatar
	 */
	function get_avatar($image, $user_id) 
	{
		global $base_url;
		
		if (preg_match('/http:/', $image) OR preg_match('/https:/', $image))
		{
			return $image;
		}
		else if (!empty($image)) 
		{
			return $base_url . '../uploads/' . $image;
		} 
		else 
		{
			return $base_url . AC_FOLDER_ADMIN . "/images/img-no-avatar.png";
		}
	}
	
	/**
	 * This function returns the group ID of the user into an array.
	 *
	 * @param userid the user ID of the user
	 * @return an array of groups the user is in or NULL if no groups
	 */ 
	function get_group_id($userid)
	{
		global $db;
		
		$group_ids = array();
      
		$result = $db->execute("
			SELECT member_group_id, mgroup_others
			FROM " . TABLE_PREFIX . DB_USERTABLE . "
			WHERE " . DB_USERTABLE_USERID . " = '" . $db->escape_string($userid) . "'
		");
      
		if ($result AND $db->count_select() > 0) 
		{	 
			if ($row = $db->fetch_array($result))
			{
				$group_ids[] = $row['member_group_id'];
				
				$tmp = explode(",", $row['mgroup_others']);
				
				foreach ($tmp as $val)
				{
					if (!empty($val))
						$group_ids[] = $val;
				}
			}
			 
			return $group_ids;
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * This function returns an array of all the groups and their names so that
	 * the ArrowChat admin panel can manage them.
	 *
	 * @return nested arrays of the group IDs and names. The nested array must follow:
			   array(group id, group name)
	 */
	function get_all_groups()
	{
		global $db;
		
		$groups = array();
		
		$result = $db->execute("
			SELECT g_id
			FROM " . TABLE_PREFIX . "core_groups
		");
		
		if ($result AND $db->count_select() > 0) 
		{	 
			while ($row = $db->fetch_array($result))
			{
				$result2 = $db->execute("
					SELECT word_custom
					FROM " . TABLE_PREFIX . "core_sys_lang_words
					WHERE word_key = 'core_group_" . $db->escape_string($row['g_id']) . "'
				");
				
				if ($result2 AND $db->count_select() > 0) 
				{
					$row2 = $db->fetch_array($result2);
					$group_name = $row2['word_custom'];
				}
				else
				{
					$group_name = "N/A";
				}
				
				$groups[] = array($row['g_id'], $group_name);
			}
			 
			return $groups;
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * This function returns the name of the logged in user.  You should not need to
	 * change this function.
	 *
	 * @param userid the user ID of the user
	 * @return the name of the user
	 */
	function get_username($userid) 
	{ 
		global $db;
		global $language;
		global $show_full_username;
		
		$users_name = $language[83];

		$result = $db->execute("
			SELECT " . DB_USERTABLE_NAME . " name 
			FROM " . TABLE_PREFIX . DB_USERTABLE . " 
			WHERE " . DB_USERTABLE_USERID . " = '" . $db->escape_string($userid) . "'
		");  

		if ($result AND $db->count_select() > 0)  
		{
			$row = $db->fetch_array($result); 
			$users_name = $row['name']; 
		}

		$pieces = explode(" ", $users_name);
		
		if ($show_full_username == 1)
		{
			return $users_name;
		}
		else
		{
			return $pieces[0]; 
		}
	} 

?>