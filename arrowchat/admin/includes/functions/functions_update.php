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
	 * Does the entire processing of the admin config file for the cache
	 *
	*/
	function update_config_file() 
	{
		global $db;
		
		// Update admin change time for cache system
		$db->execute("
			UPDATE arrowchat_config
			SET config_value = '" . $db->escape_string(time()) . "'
			WHERE config_name = 'admin_update_time'
		");
		
		$file = '<?php ';
	
		$result = $db->execute("
			SELECT * 
			FROM arrowchat_config
		");
		
		if ($result) 
		{
			while ($row = $db->fetch_array($result)) 
			{
				$file .= '$' . $row['config_name'] . '="' . $db->escape_string($row['config_value']) . '";';
			}
		}
		
		$result = $db->execute("
			SELECT * 
			FROM arrowchat_smilies
		");
		
		$file .= ' $smileys = array(';
		
		if ($result) 
		{
			while ($row = $db->fetch_array($result)) 
			{
				$pattern = str_replace("\\", "\\\\\\\\", $row['code']);
				$pattern = str_replace(";", "\\;", $pattern);
				$pattern = str_replace("'", "\\'", $pattern);
				$pattern = str_replace('"', '\\"', $pattern);
				
				$file .= '"'.$pattern.'" => "'.$row['name'].'",';
			}
		}
		
		$file .= ');';
		
		$result = $db->execute("
			SELECT * 
			FROM arrowchat_banlist
		");
		
		$file .= ' $banlist = array(';
		
		if ($result) 
		{
			while ($row = $db->fetch_array($result)) 
			{
				if (!empty($row['ban_userid']))
				{
					$file .= '"' . $row['ban_userid'] . '",';
				}
				else
				{
					$file .= '"' . $row['ban_ip'] . '",';
				}
			}
		}
		
		$file .= ');';
		$file .= '$trayicon = array();$plugins = array();$apps = array();$themes = array();';
		
		$result = $db->execute("
			SELECT * 
			FROM arrowchat_themes 
			WHERE active = '1'
		");
		
		if ($result) 
		{
			while ($row = $db->fetch_array($result)) 
			{
				$file .= '$themes[]=array("' . $row['id'] . '","' . $row['name'] . '","' . $row['folder'] . '");';
			}
		}
		
		$file .= ' ?>';
		
		$myFile = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . AC_FOLDER_CACHE . DIRECTORY_SEPARATOR . "data_admin_options.php";
		
		$fh = fopen($myFile, 'w') or die("We could not open the cache/data_admin_options.php file for writing.  Please create a blank file if it does not exist called data_admin_options.php in the cache folder and CHMOD to 777.  After, try whatever you were doing again.");
		fwrite($fh, $file) or die("We could not open the cache/data_admin_options.php file for writing.  Please create a blank file if it does not exist called data_admin_options.php in the cache folder and CHMOD to 777.  After, try whatever you were doing again.");
		fclose($fh);
	}
	
?>