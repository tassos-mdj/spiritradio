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
		
		$result = $db->execute("
			SELECT option_value 
			FROM " . TABLE_PREFIX . "options 
			WHERE option_name = 'siteurl'
		");
		
		if ($row = $db->fetch_array($result))
		{
			$site_url = $row['option_value'];
		}
		
		$cookiehash = md5($site_url);
		$cookiehash2 = md5("http://" . $_SERVER['SERVER_NAME']);
		$cookiehash3 = md5("http://" . $_SERVER['SERVER_NAME'] . "/");
		$cookiehash4 = md5("http://www." . $_SERVER['SERVER_NAME']);
		$cookiehash5 = md5("http://www." . $_SERVER['SERVER_NAME'] . "/");
		$cookiehash6 = md5("https://" . $_SERVER['SERVER_NAME']);
		$cookiehash7 = md5("https://" . $_SERVER['SERVER_NAME'] . "/");
		$cookiehash8 = md5("https://www." . $_SERVER['SERVER_NAME']);
		$cookiehash9 = md5("https://www." . $_SERVER['SERVER_NAME'] . "/");

		if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash]) OR !empty($_COOKIE['wordpress_logged_in_' . $cookiehash2]) OR !empty($_COOKIE['wordpress_logged_in_' . $cookiehash3]) OR !empty($_COOKIE['wordpress_logged_in_' . $cookiehash4]) OR !empty($_COOKIE['wordpress_logged_in_' . $cookiehash5]) OR !empty($_COOKIE['wordpress_logged_in_' . $cookiehash6]) OR !empty($_COOKIE['wordpress_logged_in_' . $cookiehash7]) OR !empty($_COOKIE['wordpress_logged_in_' . $cookiehash8]) OR !empty($_COOKIE['wordpress_logged_in_' . $cookiehash9]))
		{
			if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash]))
				$username = strstrb($_COOKIE['wordpress_logged_in_' . $cookiehash], '|');
				
			if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash2]))
				$username = strstrb($_COOKIE['wordpress_logged_in_' . $cookiehash2], '|');
				
			if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash3]))
				$username = strstrb($_COOKIE['wordpress_logged_in_' . $cookiehash3], '|');
				
			if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash4]))
				$username = strstrb($_COOKIE['wordpress_logged_in_' . $cookiehash4], '|');
				
			if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash5]))
				$username = strstrb($_COOKIE['wordpress_logged_in_' . $cookiehash5], '|');
				
			if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash6]))
				$username = strstrb($_COOKIE['wordpress_logged_in_' . $cookiehash6], '|');
				
			if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash7]))
				$username = strstrb($_COOKIE['wordpress_logged_in_' . $cookiehash7], '|');
				
			if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash8]))
				$username = strstrb($_COOKIE['wordpress_logged_in_' . $cookiehash8], '|');
				
			if (!empty($_COOKIE['wordpress_logged_in_' . $cookiehash9]))
				$username = strstrb($_COOKIE['wordpress_logged_in_' . $cookiehash9], '|');
			
			$result = $db->execute("
				SELECT " . DB_USERTABLE_USERID . " 
				FROM " . TABLE_PREFIX . DB_USERTABLE . " 
				WHERE user_login = '" . $db->escape_string($username) . "'
			");

			if ($row = $db->fetch_array($result))
			{
				$userid = $row[DB_USERTABLE_USERID];
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
			SELECT DISTINCT " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " userid, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . " username, arrowchat_status.session_time lastactivity, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_AVATAR . " avatar, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " link, arrowchat_status.is_admin, arrowchat_status.status 
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
			SELECT DISTINCT " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " userid, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . " username, arrowchat_status.session_time lastactivity, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_AVATAR . " avatar, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " link, arrowchat_status.is_admin, arrowchat_status.status 
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
			SELECT " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " userid, " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_NAME . " username, arrowchat_status.session_time lastactivity,  " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_USERID . " link,  " . TABLE_PREFIX . DB_USERTABLE . "." . DB_USERTABLE_AVATAR . " avatar, arrowchat_status.is_admin, arrowchat_status.status 
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
		// WordPress does not have user profiles by default
		return '#';
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
		return "https://www.gravatar.com/avatar/" . md5($image) . "?d=identicon";
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
			SELECT meta_value
			FROM " . TABLE_PREFIX . "usermeta
			WHERE user_id = '" . $db->escape_string($userid) . "'
				AND meta_key = '" . TABLE_PREFIX . "capabilities'
		");
      
		if ($result AND $db->count_select() > 0) 
		{	 
			if ($row = $db->fetch_array($result))
			{
				$groups_array = unserialize($row['meta_value']);
				
				foreach($groups_array as $key => $value)
				{
					$group_ids[] = $key;
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
			SELECT option_value
			FROM " . TABLE_PREFIX . "options
			WHERE option_name = '" . TABLE_PREFIX . "user_roles'
		");
		
		if ($result AND $db->count_select() > 0) 
		{	 
			if ($row = $db->fetch_array($result))
			{
				$groups_array = unserialize($row['option_value']);
				
				foreach($groups_array as $key => $value)
				{
					$groups[] = array($key, $value['name']);
				}
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