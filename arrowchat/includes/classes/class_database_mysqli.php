<?php

	/*
	|| #################################################################### ||
	|| #                             ArrowChat                            # ||
	|| # ---------------------------------------------------------------- # ||
	|| #    Copyright �2010-2020 ArrowSuites LLC. All Rights Reserved.    # ||
	|| # This file may not be redistributed in whole or significant part. # ||
	|| # ---------------- ARROWCHAT IS NOT FREE SOFTWARE ---------------- # ||
	|| #   http://www.arrowchat.com | http://www.arrowchat.com/license/   # ||
	|| #################################################################### ||
	*/

	//***************** MYSQL CLASS **********************
	class QuickDB
	{
		public $con 			= null;		// for db connection
		public $dbselect		= true;		// for db selection
		public $database_name	= null;		// to store database name
		private $result 		= null;		// for mysql result resource id
		private $row 			= null;		// for fetched row
		private $rows 			= null;		// for number of rows fetched
		private $affected 		= null;		// for number of rows affected
		private $insert_id 		= null;		// for last inserted id
		private $query 			= null;		// for the last run query
		private $show_errors 	= null;		// for knowing whether to display errors
		private $emsg 			= null;		// for mysql error description
		private $eno 			= null;		// for mysql error number
		
		
		// Intialize the class with connection to db
		public function __construct($host, $user, $password, $db, $persistent = false, $show_errors = false, $emoji_support = false)
		{
			if ($show_errors == true)
			{
				$this->show_errors = true;
			}

			$this->database_name = $db;
			$this->con = @mysqli_connect($host, $user, $password, $db);
			
			if ($this->con)
			{
				if ($emoji_support)
				{
					mysqli_query($this->con, "SET NAMES utf8mb4");
					mysqli_query($this->con, "SET CHARACTER SET utf8mb4");
					mysqli_query($this->con, "SET COLLATION_CONNECTION = 'utf8mb4_unicode_ci'");
				}
				else
				{
					mysqli_query($this->con, "SET NAMES utf8");
					mysqli_query($this->con, "SET CHARACTER SET utf8");
					mysqli_query($this->con, "SET COLLATION_CONNECTION = 'utf8_general_ci'");
				}
				
				return $this->con;
			}
			else
			{
				return false;
			}
		}
		
		// Close the connection to database
		public function __destruct()
		{
			$this->close();
		}

		// Close the connection to database
		public function close()
		{
			$result = @mysqli_close($this->con);
			
			return $result;
		}
	
		// stores mysql errors
		private function setError($msg, $no)
		{
			$this->emsg = $msg;
			$this->eno = $no;
			
			if ($this->show_errors == true)
			{
				print '	<div style="margin-top:5px;margin-bottom:5px;background:#f6f6f6; padding:5px; font-size:13px; font-family:verdana; border:1px solid #cccccc;">
						<span style="color:#ff0000;">MySQL Error Number</span> : ' . $no . '<br />
						<span style="color:#ff0000;">MySQL Error Message</span> : ' . $msg . '</div>';
			}
		}
		
	
		#################################################
		#				General Functions				#
		#################################################
		
		public function isSelect($command)
		{
			// Default trim()'s mask plus left parentheses
			$_TRIM_MASK_WITH_PAREN = "( \t\n\r\0\x0B";

			if ('SELECT' === strtoupper(substr(ltrim($command, $_TRIM_MASK_WITH_PAREN), 0, 6)))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	
		// Runs the SQL query (general execute query function)
		public function execute($command)
		{
			# Params:
			# 		$command = query command
			
			if (!$command)
			{
				exit("No Query Command Specified !!");
			}
			
			if (strpos($command, $this->database_name) !== false)
			{
				if (!preg_match('/^[a-zA-Z0-9_]+$/', $this->database_name)) 
				{
					$new_db = "`" . $this->database_name . "`";
					$command = str_replace($this->database_name, $new_db, $command); 
				}
			}
			
			$this->query = $command;
			
			// For Operational query
			if (!$this->isSelect($command))
			{
				$this->result = @mysqli_query($this->con, $command) or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));

				if (stripos($command, "insert ") !== false)
				{
					if ($this->result)
					{
						$this->insert_id = intval(mysqli_insert_id($this->con));
					}
				}

				if ($this->result)
				{
					$this->affected = intval(mysqli_affected_rows($this->con));
					// return the number of rows affected
					return $this->result;
				}
			}
			else
			{
				// For Selection query
				$this->result = @mysqli_query($this->con, $command) or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
				
				if ($this->result)
				{
					$this->rows = @intval(mysqli_num_rows($this->result));
					// return the query resource for later processing
					return $this->result;
				}
			}
		}	

		// Gets records from table
		public function select($table, $rows = "*", $condition = null, $order = null)
		{
			# Params:
			# 		$table = the name of the table
			#		$rows = rows to be selected
			# 		$condition = example: where id = 99
			#		$order = ordering field name

			if (!$table)
			{
				exit("No Table Specified !!");
			}
			
			$sql = "select $rows from $table";

			if($condition)
			{
				$sql .= ' where ' . $condition;
			}
			else if($order)
			{
				$sql .= ' order by ' . $order;
			}

			$this->query = $sql;
			$this->result = mysqli_query($this->con, $sql) or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));

			if ($this->result)
			{
				$this->rows = intval(mysqli_num_rows($this->result));
				// return the query resource for later processing
				return $this->result;
			}
		}	


		// Inserts records
		public function insert($table, $data)
		{
			# Params:
			# 		$table = the name of the table
			# 		$data = field/value pairs to be inserted
			
			if ($table)
			{
				if ($data)
				{
					$this->result = mysqli_query($this->con, "insert into $table set $data") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
					$this->query = "insert into $table set $data";

					if ($this->result)
					{
						$this->affected = intval(mysqli_affected_rows($this->con));
						$this->insert_id = intval(mysqli_insert_id($this->con));
						// return the number of rows affected
						return $this->affected;
					}
				}
				else
				{
					print "No Data Specified !!";
				}
			}
			else
			{
				print "No Table Specified !!";
			}
		}

		// Updates records
		public function update($table, $data, $condition)
		{
			# Params:
			# 		$table = the name of the table
			# 		$data = field/value pairs to be updated
			# 		$condition = example: where id = 99

			if ($table)
			{
				if ($data)
				{
					if ($condition)
					{
						$this->result = mysqli_query($this->con, "update $table set $data where $condition") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
						$this->query = "update $table set $data where $condition";

						if ($this->result)
						{
							$this->affected = intval(mysqli_affected_rows($this->con));
							// return the number of rows affected
							return $this->affected;
						}
					}
					else
					{
						print "No Condition Specified !!";
					}
				}
				else
				{
					print "No Data Specified !!";
				}
			}
			else
			{
				print "No Table Specified !!";
			}
		}

		// Deletes records
		public function delete($table, $condition)
		{
			# Params:
			# 		$table = the name of the table
			# 		$condition = example: where id = 99

			if ($table)
			{
				if ($condition)
				{
					$this->result = mysqli_query($this->con, "delete from $table where $condition") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
					$this->query = "delete from $table where $condition";

					if ($this->result)
					{
						$this->affected = intval(mysqli_affected_rows($this->con));
						// return the number of rows affected
						return $this->affected;
					}
				}
				else
				{
					print "No Condition Specified !!";
				}
			}
			else
			{
				print "No Table Specified !!";
			}
		}

		// returns table data in array
		public function load_array()
		{
			$arr = array();
			
			while ($row = mysqli_fetch_object($this->result))
			{
				$arr[] = $row;
			}

			return $arr;
		}


		// print a complete table from the specified table
		public function get_html($command, $display_field_headers = true, $table_attribs = 'border="0" cellpadding="3" cellspacing="2" style="padding-bottom:5px; border:1px solid #cccccc; font-size:13px; font-family:verdana;"')
		{
			if (!$command)
			{
				exit("No Query Command Specified !!");
			}

			$this->query = $command;
			$this->result = mysqli_query($this->con, $command) or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
			
			if ($this->result)
			{
				$this->rows = intval(mysqli_num_rows($this->result));
				
				$num_fields = mysqli_num_fields($this->result);

				print '<br /><br /><div>
						<table ' . $table_attribs . '>'
						. "\n" . '<tr>';

				if ($display_field_headers == true)
				{
					// printing table headers
					for($i = 0; $i < $num_fields; $i++)
					{
						$field = mysqli_fetch_field($this->result);
						print "<td bgcolor='#f6f6f6' style=' border:1px solid #cccccc;'><strong style='color:#666666;'>" . ucwords($field->name) . "</strong></td>\n";
					}
					print "</tr>\n";
				}
				
				// printing table rows
				while($row = mysqli_fetch_row($this->result))
				{
					print "<tr>";
				
					foreach($row as $td)
					{
						print "<td bgcolor='#f6f6f6'>$td</td>\n";
					}
				
					print "</tr>\n";
				}
				print "</table></div><br /><br />";
			}
		}
		
		
		public function last_insert_id()
		{
			if ($this->insert_id)
			{
				return $this->insert_id;
			}
		}
		
		// Counts all records from a table
		public function count_all($table)
		{
			if (!$table)
			{
				exit("No Table Specified !!");
			}
			
			$this->result = mysqli_query($this->con, "select count(*) as total from $table") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
			$this->query = "select count(*) as total from $table";

			if ($this->result)
			{
				$this->row = mysqli_fetch_array($this->result);
				return intval($this->row["total"]);
			}
		}
		
		// Counts records based on specified criteria
		public function count_rows($command)
		{
			# Params:
			# 		$command = query command

			if (!$command)
			{
				exit("No Query Command Specified !!");
			}
		
			$this->query = $command;
			$this->result = mysqli_query($this->con, $command) or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));

			if ($this->result)
			{
				return intval(mysqli_num_rows($this->result));
			}
		}

		// Updates a row if it exists or adds if it doesn't already exist.
		public function insert_update($table, $data, $condition)
		{
			# Params:
			# 		$table = the name of the table
			# 		$data = field/value pairs to be added/updated
			# 		$condition = example: where id = 99

			if ($table)
			{
				if ($data)
				{
					if ($condition)
					{
						if ($this->row_exists("select * from $table where $condition"))
						{
							$this->result = mysqli_query($this->con, "update $table set $data where $condition") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
							$this->query = "update $table set $data where $condition";

							if ($this->result)
							{
								$this->affected = intval(mysqli_affected_rows($this->con));
								// return the number of rows affected
								return $this->affected;
							}
						}
						else
						{
							$this->result = mysqli_query($this->con, "insert into $table set $data") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
							$this->query = "insert into $table set $data";

							if ($this->result)
							{
								$this->insert_id = intval(mysqli_insert_id($this->con));
								$this->affected = intval(mysqli_affected_rows($this->con));
								// return the number of rows affected
								return $this->affected;
							}
						}
					}
					else
					{
						print "No Condition Specified !!";
					}
				}
				else
				{
					print "No Data Specified !!";
				}
			}
			else
			{
				print "No Table Specified !!";
			}
		}


		// Runs the sql query with claus "limit x, x"
		public function select_limited($table, $start, $return_count, $condition = null, $order = null)
		{
			# Params:
			# 		$start = starting row for limit clause
			# 		$return_count = number of records to fetch
			# 		$condition = example: where id = 99
			# 		$order = ordering field name
			
			if ($table && $start >= 0 && $return_count)
			{
				if ($condition)
				{
					if ($order)
					{
						$this->result = mysqli_query($this->con, "select * from $table where $condition order by $order limit $start, $return_count") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
						$this->query = "select * from $table where $condition order by $order limit $start, $return_count";
					}
					else
					{
						$this->result = mysqli_query($this->con, "select * from $table where $condition limit $start, $return_count") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
						$this->query = "select * from $table where $condition limit $start, $return_count";
					}
				}
				else
				{
					if ($order)
					{
						$this->result = mysqli_query($this->con, "select * from $table order by $order limit $start, $return_count") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
						$this->query = "select * from $table order by $order limit $start, $return_count";
					}
					else
					{
						$this->result = mysqli_query($this->con, "select * from $table limit $start, $return_count") or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));
						$this->query = "select * from $table limit $start, $return_count";
					}
				}

				if ($this->result)
				{
					$this->rows = intval(mysqli_num_rows($this->result));
					// return the query resource for later processing
					return $this->result;
				}
			}
			else
			{
				print "Parameter Missing !!";
			}
		}	

		
		#################################################
		#				Utility Functions				#
		#################################################
		
		// Fetchs array
		public function fetch_array($result)
		{
			if (!empty($result))
			{
				return mysqli_fetch_array($result);
			}
			else
			{
				return false;
			}
		}
		
		// Gets table name
		public function table_name($result, $i)
		{
			$fieldinfo = mysqli_fetch_field($result);
			
			return $fieldinfo->table;
		}

		// Counts rows from last Select query
		public function count_select()
		{
			if ($this->rows)
			{
				return $this->rows;
			}
		}

		// Gets the number of affected rows after Operational query has executed
		public function count_affected()
		{
			if ($this->affected)
			{
				return $this->affected;
			}
		}

		// Checks whether a table has records		
		public function has_rows($table)
		{
			$rows = $this->count_all($table);
			
			if ($rows)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
		// Checks whether or not a row exists with specified criteria
		public function row_exists($command)
		{
			# Params:
			# 		$command = query command

			if (!$command)
			{
				exit("No Query Command Specified !!");
			}
		
			$this->query = $command;
			$this->result = mysqli_query($this->con, $command) or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));

			if ($this->result)
			{
				if (mysqli_num_rows($this->result))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
		}

		// Returns single fetched row
		public function fetch_row($command)
		{

			if (!$command)
			{
				exit("No Query Command Specified !!");
			}

			$this->query = $command;
			$this->result = mysqli_query($this->con, $command) or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));

			if ($this->result)
			{
				$this->rows = intval(mysqli_num_rows($this->result));
				$this->row = mysqli_fetch_object($this->result);
				return $this->row;
			}
		}
		
		
		// Returns single field value
		public function fetch_value($table, $field, $condition = null)
		{

			if (!$table || !$field)
			{
				exit("Arguments Missing !!");
			}

			$query = "select $field from $table";
			
			if ($condition != null)
			{
				$query = "select $field from $table where $condition";
			}
			
			$this->query = $query;
			$this->result = mysqli_query($this->con, $query) or $this->setError(mysqli_error($this->con), mysqli_errno($this->con));

			if ($this->result)
			{
				$this->rows = intval(mysqli_num_rows($this->result));
				$this->row = mysqli_fetch_object($this->result);
				return $this->row->$field;
			}
		}
		
		
		// Returns the last run query
		public function last_query()
		{
			if ($this->query)
			{
				return $this->alert_msg($this->query);
			}
		}
		
		
		// Gets today's date
		public function get_date($format = null)
		{
			# Params:
			#		$format = date format like Y-m-d
			
			if ($format)
			{
				$today = date($format);
			}
			else
			{
				$today = date("Y-m-d");
			}
			
			return $today;
		}
		
		// Gets currents time
		public function get_time($format = null)
		{
			# Params:
			#		$format = date format like H:m:s
			
			if ($format)
			{
				$time = date($format);
			}
			else
			{
				$time = date("H:m:s");
			}
			
			return $time;
		}

		// Adds slash to the string irrespective of the setting of getmagicquotesgpc
		public function escape_string($value)
		{
			if (is_string($value))
			{

				$value = stripslashes($value);
				

				if (!is_numeric($value))
				{
					$value = @mysqli_real_escape_string($this->con, $value);
				}
			}
			
			return $value;
		} 
		
		// This function can be used to discard any characters that can be used to manipulate the SQL queries or SQL injection

		/* EXAMPLE USE:
		
			if (is_valid($_REQUEST["username"]) === true && is_valid($_REQUEST["pass"]) === true)
			{
				//login now
			}
		*/
		
		public function is_valid($input)
		{
			$input = strtolower($input);
			
			if (str_word_count($input) > 1)
			{
				$loop = "true";
				$input = explode(" ",$input);
			}
			
			$bad_strings = array("'","--","select","union","insert","update","like","delete","1=1","or");
		
			if ($loop)
			{
				foreach($input as $value)
				{
					if (in_array($value, $bad_strings))
					{
					  return false;
					}
					else
					{
					  return true;
					}
				}
			}
			else
			{
				if (in_array($input, $bad_strings))
				{
				  return false;
				}
				else
				{
				  return true;
				}
			}
		}
	
		// lists tables of database
		public function list_tables()
		{
			$this->result = mysqli_query($this->con, "show tables");
			$this->query = "show tables";
			
			if ($this->result)
			{
				$tables = array();
				while($row = mysqli_fetch_array($this->result))
				{
					$tables[] = $row[0];
				}
				
				foreach ($tables as $table)
				{
					print $table . "<br />";
				}
			}
		}


		// provides info about given table
		/*public function table_info($table)
		{
			if ($table)
			{
				$this->result = mysqli_query($this->con, "select * from $table");
				$this->query = "select * from $table";

				$fields = mysqli_num_fields($this->result);
				$rows   = mysqli_num_rows($this->result);
				$table = mysqli_field_table($this->result, 0);

				print "	The '<strong>" . $table . "</strong>' table has <strong>" . $fields . "</strong> fields and <strong>" . $rows . "</strong>
						record(s) with following fields.\n<br /><ul>";

				for ($i=0; $i < $fields; $i++)
				{
					$type  = mysqli_field_type($this->result, $i);
					$name  = mysqli_field_name($this->result, $i);
					$len   = mysqli_field_len($this->result, $i);
					$flags = mysqli_field_flags($this->result, $i);
					
					print "<strong><li>" . $type . " " . $name . " " . $len . " " . $flags . "</strong></li>\n";
				}
				print "</ul>";
				
			}
			else
			{
				print "The table not specified !!";
			}
		}*/


		// displays any mysql errors generated
		public function display_errors()
		{
			if ($this->show_errors == false)
			{
				if ($this->emsg)
				{
					return '<div style="background:#f6f6f6; margin-top:5px;margin-bottom:5px;padding:5px; font-size:13px; font-family:verdana; border:1px solid #cccccc;">
							<span style="color:#ff0000;">MySQL Error Number</span> : ' . $this->eno . '<br />
							<span style="color:#ff0000;">MySQL Error Message</span> : ' . $this->emsg . '</div>';
				}
				else
				{
					return '	<br /><br /><div style="background:#f6f6f6; padding:5px; font-size:13px; font-family:verdana; border:1px solid #cccccc;">
							<strong>No Erros Found !!</strong>
							</div>';
				}
			}
		}

		// to display success message
		public function success_msg($msg)
		{
			print '	<br /><br /><div align="center" style="background:#EEFDD7; padding:5px; font-size:13px; font-family:verdana; border:1px solid #8DD607;">
					' . $msg . '
					</div><br />';
		}
	
		// to display failure message
		public function failure_msg($msg)
		{
			print '	<br /><br /><div align="center" style="background:#FFF2F2; padding:5px; font-size:13px; font-family:verdana; border:1px solid #FF8080;">
					' . $msg . '
					</div><br />';
		}

		// to display general alert message
		public function alert_msg($msg)
		{
			print '	<br /><br /><div align="center" style="background:#FFFFCC; padding:5px; font-size:13px; font-family:verdana; border:1px solid #CCCC33;">
					' . $msg . '
					</div><br />';
		}

	////////////////////////////////////////////////////////
	}
	
	
	//************* MSSQL CLASS **********************
	class QuickMSDB
	{
		public $con 			= null;		// for db connection
		public $dbselect		= true;		// for db selection
		private $result 		= null;		// for mssql result resource id
		private $row 			= null;		// for fetched row
		private $rows 			= null;		// for number of rows fetched
		private $affected 		= null;		// for number of rows affected
		private $insert_id 		= null;		// for last inserted id
		private $query 			= null;		// for the last run query
		private $show_errors 	= null;		// for knowing whether to display errors
		private $emsg 			= null;		// for mssql error description
		private $eno 			= null;		// for mssql error number
		
		
		// Intialize the class with connection to db
		public function __construct($host, $user, $password, $db, $persistent = false, $show_errors = false, $emoji_support = false)
		{
			if ($show_errors == true)
			{
				$this->show_errors = true;
			}
			
			$serverName = $host;
			$connectionInfo = array( "Database"=>$db, "UID"=>$user, "PWD"=>$password);
			
			if ($persistent == true)
			{
				$this->con = @sqlsrv_connect($serverName, $connectionInfo);
			}
			else
			{
				$this->con = @sqlsrv_connect($serverName, $connectionInfo);
			}
			
			if ($this->con)
			{
				return $this->con;
			}
			else
			{
				return false;
			}
		}
		
		// Close the connection to database
		public function __destruct()
		{
			$this->close();
		}

		// Close the connection to database
		public function close()
		{
			$result = @sqlsrv_close($this->con);
			
			return $result;
		}
	
		// stores mssql errors
		private function setError($msg, $no)
		{
			$this->emsg = $msg;
			$this->eno = $no;
			
			if ($this->show_errors == true)
			{
				print '	<div style="margin-top:5px;margin-bottom:5px;background:#f6f6f6; padding:5px; font-size:13px; font-family:verdana; border:1px solid #cccccc;">
						<span style="color:#ff0000;">MSSQL Error Number</span> : ' . $no . '<br />
						<span style="color:#ff0000;">MSSQL Error Message</span> : ' . $msg . '</div>';
			}
		}
		
	
		#################################################
		#				General Functions				#
		#################################################
		
		public function isSelect($command)
		{
			// Default trim()'s mask plus left parentheses
			$_TRIM_MASK_WITH_PAREN = "( \t\n\r\0\x0B";

			if ('SELECT' === strtoupper(substr(ltrim($command, $_TRIM_MASK_WITH_PAREN), 0, 6)))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	
		// Runs the SQL query (general execute query function)
		public function execute($command)
		{	
			$command = $this->convert_reserved($command);
			$command = $this->convert_limits($command);
			$command = $this->convert_count($command);
			$command = $this->convert_on_duplicate($command);
			
			# Params:
			# 		$command = query command
			
			if (!$command)
			{
				exit("No Query Command Specified !!");
			}
			
			$this->query = $command;
			
			// For Operational query
			if (!$this->isSelect($command))
			{
				$this->result = sqlsrv_query($this->con, $command, array(), array( "Scrollable" => 'static' ));

				if (stripos($command, "insert ") !== false)
				{
					if ($this->result)
					{
						
					}
				}

				if ($this->result)
				{
					// return the number of rows affected
					return $this->result;
				}
			}
			else
			{
				// For Selection query
				$this->result = @sqlsrv_query($this->con, $command, array(), array( "Scrollable" => 'static' ));
				
				if ($this->result)
				{
					$this->rows = @intval(sqlsrv_num_rows($this->result));
					// return the query resource for later processing
					return $this->result;
				}
			}
		}	
		
		// Convert reserved words
		public function convert_reserved($command)
		{
			$command = str_replace(".from", ".[from]", $command);
			$command = str_replace(".read", ".[read]", $command);
			$command = str_replace(".to", ".[to]", $command);
			$command = str_replace(".name", ".[name]", $command);
			$command = str_replace(".type", ".[type]", $command);
			$command = str_replace(".date", ".[date]", $command);
			$command = str_replace(".default", ".[default]", $command);
			
			return $command;
		}
		
		// Convert count
		public function convert_count($command)
		{
			$command = preg_replace('/ COUNT(\([a-zA-Z._]+\))/', ' COUNT$1 AS [COUNT$1] ', $command);

			return $command;
		}
		
		// Convert on duplicate key
		public function convert_on_duplicate($command)
		{
			if (preg_match('/([^\?]*)ON DUPLICATE KEY([^\?]*)/', $command, $matches))
			{
				$insert_sql = $matches[1];
				$insert_sql = str_replace("\n", '', $insert_sql);
				$insert_sql = preg_replace('/\s+/', ' ', $insert_sql);
				
				preg_match('/INSERT INTO ([a-zA-Z._]+) \(/', $command, $matches);
				$table_name = $matches[1];
				
				preg_match('/INSERT INTO ([a-zA-Z._]+) \([\n\r\s\t]+([a-zA-Z._]+),/', $command, $matches);
				$key_name = $matches[2];
				
				preg_match("/VALUES \([\n\r\s\t]+'([a-zA-Z0-9._]+)',/", $command, $matches);
				$value_name = $matches[1];
				
				$update_sql = preg_replace('/([^\?]*)ON DUPLICATE KEY/', '', $command);
				$update_sql = preg_replace('/UPDATE/', 'UPDATE ' . $table_name . ' SET', $update_sql);
				
				$command = "IF EXISTS (SELECT * FROM " . $table_name . " WHERE " . $key_name . " = '" . $value_name . "') " . trim($update_sql) . " WHERE " . $key_name . " = '" . $value_name . "' ELSE BEGIN " . trim($insert_sql) . " END";
			}

			return $command;
		}
		
		// Convert limits
		public function convert_limits($command)
		{
			if (preg_match('/LIMIT ([0-9]+), ([0-9]+)/', $command, $matches))
			{
				$lower_limit = $matches[1];
				$upper_limit = $matches[2];
				$and_limit = $upper_limit + $lower_limit;
				
				preg_match('/FROM ([a-zA-Z._]+)/', $command, $matches);
				$table_name = $matches[1];
				
				preg_match('/ORDER BY ([a-zA-Z._]+)/', $command, $matches);
				$id = $matches[1];
				
				$command = preg_replace('/LIMIT ([0-9]+), ([0-9]+)/', '', $command);
				$command = preg_replace('/SELECT /', 'SELECT TOP ' . $upper_limit . ' ', $command);
				
				if (preg_match('/WHERE /', $command))
				{
					$command = preg_replace('/FROM ([a-zA-Z._]+) /', 'FROM (SELECT *, ROW_NUMBER() OVER (ORDER BY ' . $id . ' DESC) AS RowNum FROM $1 ) AS ' . $table_name . ' ', $command);
					$command = preg_replace('/WHERE /', 'WHERE ' . $table_name . '.RowNum BETWEEN ' . $lower_limit . ' AND ' . $and_limit . ' AND ', $command);
				}
				else
				{
					$command = preg_replace('/FROM ([a-zA-Z._]+) /', 'FROM (SELECT *, ROW_NUMBER() OVER (ORDER BY ' . $id . ' DESC) AS RowNum FROM $1 ) AS ' . $table_name . ' WHERE ' . $table_name . '.RowNum BETWEEN ' . $lower_limit . ' AND ' . $and_limit . ' ', $command);
				}
			}
			else if (preg_match('/LIMIT ([0-9]+)/', $command, $matches))
			{
				$upper_limit = $matches[1];
				
				$command = preg_replace('/LIMIT ([0-9]+)/', '', $command);
				$command = preg_replace('/SELECT /', 'SELECT TOP ' . $upper_limit . ' ', $command);
			}

			return $command;
		}

		// Gets records from table
		public function select($table, $rows = "*", $condition = null, $order = null)
		{
			# Params:
			# 		$table = the name of the table
			#		$rows = rows to be selected
			# 		$condition = example: where id = 99
			#		$order = ordering field name

			if (!$table)
			{
				exit("No Table Specified !!");
			}
			
			$sql = "select $rows from $table";

			if($condition)
			{
				$sql .= ' where ' . $condition;
			}
			else if($order)
			{
				$sql .= ' order by ' . $order;
			}

			$this->query = $sql;
			$this->result = sqlsrv_query($this->con, $sql, array(), array( "Scrollable" => 'static' ));

			if ($this->result)
			{
				$this->rows = intval(sqlsrv_num_rows($this->result));
				// return the query resource for later processing
				return $this->result;
			}
		}	


		// Inserts records
		public function insert($table, $data)
		{
			# Params:
			# 		$table = the name of the table
			# 		$data = field/value pairs to be inserted
			
			if ($table)
			{
				if ($data)
				{
					$this->result = sqlsrv_query($this->con, "insert into $table set $data");
					$this->query = "insert into $table set $data";

					if ($this->result)
					{

						// return the number of rows affected
						return $this->affected;
					}
				}
				else
				{
					print "No Data Specified !!";
				}
			}
			else
			{
				print "No Table Specified !!";
			}
		}

		// Updates records
		public function update($table, $data, $condition)
		{
			# Params:
			# 		$table = the name of the table
			# 		$data = field/value pairs to be updated
			# 		$condition = example: where id = 99

			if ($table)
			{
				if ($data)
				{
					if ($condition)
					{
						$this->result = sqlsrv_query($this->con, "update $table set $data where $condition");
						$this->query = "update $table set $data where $condition";

						if ($this->result)
						{
							// return the number of rows affected
							return $this->affected;
						}
					}
					else
					{
						print "No Condition Specified !!";
					}
				}
				else
				{
					print "No Data Specified !!";
				}
			}
			else
			{
				print "No Table Specified !!";
			}
		}

		// Deletes records
		public function delete($table, $condition)
		{
			# Params:
			# 		$table = the name of the table
			# 		$condition = example: where id = 99

			if ($table)
			{
				if ($condition)
				{
					$this->result = sqlsrv_query($this->con, "delete from $table where $condition");
					$this->query = "delete from $table where $condition";

					if ($this->result)
					{
						// return the number of rows affected
						return $this->affected;
					}
				}
				else
				{
					print "No Condition Specified !!";
				}
			}
			else
			{
				print "No Table Specified !!";
			}
		}

		// returns table data in array
		public function load_array()
		{
			$arr = array();
			
			while ($row = sqlsrv_fetch_object($this->result))
			{
				$arr[] = $row;
			}

			return $arr;
		}


		// print a complete table from the specified table
		public function get_html($command, $display_field_headers = true, $table_attribs = 'border="0" cellpadding="3" cellspacing="2" style="padding-bottom:5px; border:1px solid #cccccc; font-size:13px; font-family:verdana;"')
		{
			if (!$command)
			{
				exit("No Query Command Specified !!");
			}

			$this->query = $command;
			$this->result = sqlsrv_query($this->con, $command);
			
			if ($this->result)
			{
				$this->rows = intval(sqlsrv_num_rows($this->result));
				
				$num_fields = sqlsrv_num_fields($this->result);

				print '<br /><br /><div>
						<table ' . $table_attribs . '>'
						. "\n" . '<tr>';

				if ($display_field_headers == true)
				{
					// printing table headers
					for($i = 0; $i < $num_fields; $i++)
					{
						$field = sqlsrv_fetch($this->result);
						print "<td bgcolor='#f6f6f6' style=' border:1px solid #cccccc;'><strong style='color:#666666;'>" . ucwords($field->name) . "</strong></td>\n";
					}
					print "</tr>\n";
				}
				
				// printing table rows
				while($row = sqlsrv_fetch($this->result))
				{
					print "<tr>";
				
					foreach($row as $td)
					{
						print "<td bgcolor='#f6f6f6'>$td</td>\n";
					}
				
					print "</tr>\n";
				}
				print "</table></div><br /><br />";
			}
		}
		
		public function last_insert_id()
		{
			$result_id = @sqlsrv_query($this->con, 'SELECT @@IDENTITY');

			if ($result_id)
			{
				if ($row = sqlsrv_fetch_array($result_id))
				{
					return $row[0];
				}
			}
			
			return 0;
		}
		
		// Counts all records from a table
		public function count_all($table)
		{
			if (!$table)
			{
				exit("No Table Specified !!");
			}
			
			$this->result = sqlsrv_query($this->con, "select count(*) as total from $table");
			$this->query = "select count(*) as total from $table";

			if ($this->result)
			{
				$this->row = sqlsrv_fetch_array($this->result);
				return intval($this->row["total"]);
			}
		}
		
		// Counts records based on specified criteria
		public function count_rows($command)
		{
			# Params:
			# 		$command = query command

			if (!$command)
			{
				exit("No Query Command Specified !!");
			}
		
			$this->query = $command;
			$this->result = sqlsrv_query($this->con, $command, array(), array( "Scrollable" => 'static' ));

			if ($this->result)
			{
				return intval(sqlsrv_num_rows($this->result));
			}
		}

		// Updates a row if it exists or adds if it doesn't already exist.
		public function insert_update($table, $data, $condition)
		{
			# Params:
			# 		$table = the name of the table
			# 		$data = field/value pairs to be added/updated
			# 		$condition = example: where id = 99

			if ($table)
			{
				if ($data)
				{
					if ($condition)
					{
						if ($this->row_exists("select * from $table where $condition"))
						{
							$this->result = sqlsrv_query($this->con, "update $table set $data where $condition");
							$this->query = "update $table set $data where $condition";

							if ($this->result)
							{
								// return the number of rows affected
								return $this->affected;
							}
						}
						else
						{
							$this->result = sqlsrv_query($this->con, "insert into $table set $data");
							$this->query = "insert into $table set $data";

							if ($this->result)
							{
								// return the number of rows affected
								return $this->affected;
							}
						}
					}
					else
					{
						print "No Condition Specified !!";
					}
				}
				else
				{
					print "No Data Specified !!";
				}
			}
			else
			{
				print "No Table Specified !!";
			}
		}


		// Runs the sql query with claus "limit x, x"
		public function select_limited($table, $start, $return_count, $condition = null, $order = null)
		{
			# Params:
			# 		$start = starting row for limit clause
			# 		$return_count = number of records to fetch
			# 		$condition = example: where id = 99
			# 		$order = ordering field name
			
			if ($table && $start >= 0 && $return_count)
			{
				if ($condition)
				{
					if ($order)
					{
						$this->result = sqlsrv_query($this->con, "select * from $table where $condition order by $order limit $start, $return_count");
						$this->query = "select * from $table where $condition order by $order limit $start, $return_count";
					}
					else
					{
						$this->result = sqlsrv_query($this->con, "select * from $table where $condition limit $start, $return_count");
						$this->query = "select * from $table where $condition limit $start, $return_count";
					}
				}
				else
				{
					if ($order)
					{
						$this->result = sqlsrv_query($this->con, "select * from $table order by $order limit $start, $return_count");
						$this->query = "select * from $table order by $order limit $start, $return_count";
					}
					else
					{
						$this->result = sqlsrv_query($this->con, "select * from $table limit $start, $return_count");
						$this->query = "select * from $table limit $start, $return_count";
					}
				}

				if ($this->result)
				{
					$this->rows = intval(sqlsrv_num_rows($this->result));
					// return the query resource for later processing
					return $this->result;
				}
			}
			else
			{
				print "Parameter Missing !!";
			}
		}	

		
		#################################################
		#				Utility Functions				#
		#################################################
		
		// Fetchs array
		public function fetch_array($result)
		{
			if (!empty($result))
			{
				return sqlsrv_fetch_array($result);
			}
			else
			{
				return false;
			}
		}
		
		// Gets table name
		public function table_name($result, $i)
		{
			return false;
		}

		// Counts rows from last Select query
		public function count_select()
		{
			if ($this->rows)
			{
				return $this->rows;
			}
		}

		// Gets the number of affected rows after Operational query has executed
		public function count_affected()
		{
			if ($this->affected)
			{
				return $this->affected;
			}
		}

		// Checks whether a table has records		
		public function has_rows($table)
		{
			$rows = $this->count_all($table);
			
			if ($rows)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		
		// Checks whether or not a row exists with specified criteria
		public function row_exists($command)
		{
			# Params:
			# 		$command = query command

			if (!$command)
			{
				exit("No Query Command Specified !!");
			}
		
			$this->query = $command;
			$this->result = sqlsrv_query($this->con, $command, array(), array( "Scrollable" => 'static' ));

			if ($this->result)
			{
				if (sqlsrv_num_rows($this->result))
				{
					return true;
				}
				else
				{
					return false;
				}
			}
		}

		// Returns single fetched row
		public function fetch_row($command)
		{

			if (!$command)
			{
				exit("No Query Command Specified !!");
			}

			$this->query = $command;
			$this->result = sqlsrv_query($this->con, $command, array(), array( "Scrollable" => 'static' ));

			if ($this->result)
			{
				$this->rows = intval(sqlsrv_num_rows($this->result));
				$this->row = sqlsrv_fetch_object($this->result);
				return $this->row;
			}
		}
		
		
		// Returns single field value
		public function fetch_value($table, $field, $condition = null)
		{

			if (!$table || !$field)
			{
				exit("Arguments Missing !!");
			}

			$query = "select $field from $table";
			
			if ($condition != null)
			{
				$query = "select $field from $table where $condition";
			}
			
			$this->query = $query;
			$this->result = sqlsrv_query($this->con, $query, array(), array( "Scrollable" => 'static' ));

			if ($this->result)
			{
				$this->rows = intval(sqlsrv_num_rows($this->result));
				$this->row = sqlsrv_fetch_object($this->result);
				return $this->row->$field;
			}
		}
		
		
		// Returns the last run query
		public function last_query()
		{
			if ($this->query)
			{
				return $this->alert_msg($this->query);
			}
		}
		
		
		// Gets today's date
		public function get_date($format = null)
		{
			# Params:
			#		$format = date format like Y-m-d
			
			if ($format)
			{
				$today = date($format);
			}
			else
			{
				$today = date("Y-m-d");
			}
			
			return $today;
		}
		
		// Gets currents time
		public function get_time($format = null)
		{
			# Params:
			#		$format = date format like H:m:s
			
			if ($format)
			{
				$time = date($format);
			}
			else
			{
				$time = date("H:m:s");
			}
			
			return $time;
		}

		// Adds slash to the string irrespective of the setting of getmagicquotesgpc
		public function escape_string($value)
		{
			if (is_string($value))
			{
				$value = stripslashes($value);
				
				$value = str_replace("'", "''", $value);
			}
			
			return $value;
		} 
		
		// This function can be used to discard any characters that can be used to manipulate the SQL queries or SQL injection

		/* EXAMPLE USE:
		
			if (is_valid($_REQUEST["username"]) === true && is_valid($_REQUEST["pass"]) === true)
			{
				//login now
			}
		*/
		
		public function is_valid($input)
		{
			$input = strtolower($input);
			
			if (str_word_count($input) > 1)
			{
				$loop = "true";
				$input = explode(" ",$input);
			}
			
			$bad_strings = array("'","--","select","union","insert","update","like","delete","1=1","or");
		
			if ($loop)
			{
				foreach($input as $value)
				{
					if (in_array($value, $bad_strings))
					{
					  return false;
					}
					else
					{
					  return true;
					}
				}
			}
			else
			{
				if (in_array($input, $bad_strings))
				{
				  return false;
				}
				else
				{
				  return true;
				}
			}
		}
	
		// lists tables of database
		public function list_tables()
		{
			$this->result = sqlsrv_query($this->con, "show tables");
			$this->query = "show tables";
			
			if ($this->result)
			{
				$tables = array();
				while($row = sqlsrv_fetch_array($this->result))
				{
					$tables[] = $row[0];
				}
				
				foreach ($tables as $table)
				{
					print $table . "<br />";
				}
			}
		}


		// provides info about given table
		public function table_info($table)
		{
			print "The table not specified !!";
		}


		// displays any mssql errors generated
		public function display_errors()
		{
			if ($this->show_errors == false)
			{
				if ($this->emsg)
				{
					return '<div style="background:#f6f6f6; margin-top:5px;margin-bottom:5px;padding:5px; font-size:13px; font-family:verdana; border:1px solid #cccccc;">
							<span style="color:#ff0000;">MSSQL Error Number</span> : ' . $this->eno . '<br />
							<span style="color:#ff0000;">MSSQL Error Message</span> : ' . $this->emsg . '</div>';
				}
				else
				{
					return '	<br /><br /><div style="background:#f6f6f6; padding:5px; font-size:13px; font-family:verdana; border:1px solid #cccccc;">
							<strong>No Erros Found !!</strong>
							</div>';
				}
			}
		}

		// to display success message
		public function success_msg($msg)
		{
			print '	<br /><br /><div align="center" style="background:#EEFDD7; padding:5px; font-size:13px; font-family:verdana; border:1px solid #8DD607;">
					' . $msg . '
					</div><br />';
		}
	
		// to display failure message
		public function failure_msg($msg)
		{
			print '	<br /><br /><div align="center" style="background:#FFF2F2; padding:5px; font-size:13px; font-family:verdana; border:1px solid #FF8080;">
					' . $msg . '
					</div><br />';
		}

		// to display general alert message
		public function alert_msg($msg)
		{
			print '	<br /><br /><div align="center" style="background:#FFFFCC; padding:5px; font-size:13px; font-family:verdana; border:1px solid #CCCC33;">
					' . $msg . '
					</div><br />';
		}

	////////////////////////////////////////////////////////
	}

?>