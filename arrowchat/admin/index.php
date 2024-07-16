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
	require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "includes/admin_init.php");

	$security_token = get_var('token');
	
	if (!empty($security_token)) 
	{
		if (hash_equals($_SESSION['token'], $security_token)) 
		{
			// Clear Chat History
			if ($do == "delete_history") 
			{
				// Deletes messsages 3 hours old
				$result = $db->execute("
					DELETE FROM arrowchat 
					WHERE (arrowchat.read = 1 
							AND ('" . time() . "' - arrowchat.sent) > 10800)
						OR (arrowchat.read = 0
							AND ('" . time() . "' - arrowchat.sent) > 604800)
				");
				
				// Deletes notifications 5 days old
				$result = $db->execute("
					DELETE FROM arrowchat_notifications 
					WHERE arrowchat_notifications.user_read = 1 
						AND ('" . time() . "' - arrowchat_notifications.alert_time) > 432000
				");
			}
			
			// Announcement Hide Processor
			if (var_check('announcement_hide')) 
			{
				$result = $db->execute("
					UPDATE arrowchat_config 
					SET config_value = CASE 
						WHEN config_name = 'announcement' THEN '" . $db->escape_string(get_var('announcement')) . "'
					END WHERE config_name IN ('announcement')
				");
				
				if ($result) 
				{
					update_config_file();
					include(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . AC_FOLDER_CACHE . DIRECTORY_SEPARATOR . 'data_admin_options.php');
					
					$result = $db->execute("
						UPDATE arrowchat_status 
						SET announcement='1'
					");
					
					if ($result) 
					{
						$msg = "The chat announcement is now hidden.";
						
						if ($push_on == 1)
						{
							push_publish($push_encrypt . '_arrowchat', array('announcement' => array("data" => "", "read" => "1")));
						}
					} 
					else
					{
						$error = "There was a database error.  Please try again.";
					}
				} 
				else
				{
					$error = "There was a database error.  Please try again.";
				}
			}
			
			// Announcement Show Processor
			if (var_check('announcement_show')) 
			{
				$result = $db->execute("
					UPDATE arrowchat_config 
					SET config_value = CASE 
						WHEN config_name = 'announcement' THEN '" . $db->escape_string(get_var('announcement')) . "'
					END WHERE config_name IN ('announcement')
				");
				
				if ($result) 
				{
					update_config_file();
					include(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . AC_FOLDER_CACHE . DIRECTORY_SEPARATOR . 'data_admin_options.php');
					
					$result = $db->execute("
						UPDATE arrowchat_status 
						SET announcement='0'
					");
					
					if ($result) 
					{
						$msg = "The chat announcement is now being shown.";
						
						if ($push_on == 1)
						{
							push_publish($push_encrypt . '_arrowchat', array('announcement' => array("data" => $_POST['announcement'], "read" => "0")));
						}
					} 
					else
					{
						$error = "There was a database error.  Please try again.";
					}
				} 
				else
				{
					$error = "There was a database error.  Please try again.";
				}
			}
			
			// Announcement Save Processor
			if (var_check('announcement_save')) 
			{
				$result = $db->execute("
					UPDATE arrowchat_config 
					SET config_value = CASE 
						WHEN config_name = 'announcement' THEN '" . $db->escape_string(get_var('announcement')) . "'
					END WHERE config_name IN ('announcement')
				");
				
				if ($result) 
				{
					update_config_file();
					include(dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . AC_FOLDER_CACHE . DIRECTORY_SEPARATOR . 'data_admin_options.php');
					
					$msg = "The chat announcement has been updated.";
				} 
				else
				{
					$error = "There was a database error.  Please try again.";
				}
			}
		}
		else
		{
			die("No valid token");
		}
	}
	
	// Get information for the page
	$num_messages = $db->count_all("
		arrowchat
	");
	
	if (!empty(DB_USERTABLE)) {
		$num_users = $db->count_all("
			" . TABLE_PREFIX . DB_USERTABLE . "
		");
	}
	else
	{
		$num_users = 1;
	}
	
	$install_time = $db->fetch_row("
		SELECT config_value 
		FROM arrowchat_config 
		WHERE config_name = 'install_time'
	");
	
	// Calculate overview information
	$days_since = (time() - $install_time->config_value) / 86400;
	$messages_day = $num_messages / $days_since;
	$users_day = $num_users / $days_since;
	
	require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . "layout/pages_header.php");
	require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . "layout/pages_index.php");
	require_once (dirname(__FILE__) . DIRECTORY_SEPARATOR . "layout/pages_footer.php");
	
?>