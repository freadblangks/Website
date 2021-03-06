<?php
// Make sure we have built in support for MySQL
if (!function_exists('mysql_connect'))
	exit('This PHP environment doesn\'t have MySQL support built in. MySQL support is required if you want to use a MySQL database to run this site. Consult the PHP documentation for further assistance.');


class DBLayer
{
	var $prefix;
	var $link_id;
	var $query_result;

	var $saved_queries = array();
	var $num_queries = 0;


	function DBLayer($db_host, $db_user, $db_pass, $db_name, $db_prefix, $p_connect)
	{
		$this->prefix = $db_prefix;

		if ($p_connect)
			$this->link_id = @mysql_pconnect($db_host, $db_user, $db_pass);
		else
			$this->link_id = @mysql_connect($db_host, $db_user, $db_pass);
		//ako je connectalo onda:
		if ($this->link_id)
		{
			if (@mysql_select_db($db_name, $this->link_id))
				return $this->link_id;
			else
				error('Unable to select the website database because it does not exist or you entered an incorrect name for it. <br><br></strong>MySQL reported:<strong> '.mysql_error().'.<br><br></strong>Possible fix:<strong> Open  your "config.php" file and find the $db_name variable. Enter the correct database name (Where the website tables are stored.) <br><br>You got the SQL file (That you need to import.) in the root of this website download. Import this file with any SQL client. (Navicat, HeidiSQL, SQLyog, ...) And make sure the database name is the same as you entered in the  $db_name variable', __FILE__, __LINE__);
		}
		else
			error('Unable to connect to the MySQL server. The MySQL server is offline or you do not have access to connect to it. (Username and or password may be wrong.)<br><br></strong>MySQL reported: <strong>'.mysql_error().'.<br><br></strong>Possible fix:<strong><br> Recheck your MySQL username and password in website\'s config.php file.<br> Try start MySQL server on your host, maybe there is no connection.<br> Give \'admin\' rights to user ('.$db_user.') that you using to connect to the MySQL', __FILE__, __LINE__);
	}

	//***************AXE - WORK!
	function select_db($dbsel)
	{
	// select the database
	return @mysql_select_db($dbsel, $this->link_id);

	}

    //***************
	function start_transaction()
	{
		return;
	}


	function end_transaction()
	{
		return;
	}


	function query($sql, $unbuffered = false)
	{
		

		if ($unbuffered)
			$this->query_result = @mysql_unbuffered_query($sql, $this->link_id);
		else
			$this->query_result = @mysql_query($sql, $this->link_id);

		if ($this->query_result)
		{
			++$this->num_queries;

			return $this->query_result;
		}
		else
		{
			return false;
		}
	}


	function result($query_id = 0, $row = 0)
	{
		return ($query_id) ? @mysql_result($query_id, $row) : false;
	}

	function fetch_assoc($query_id = 0)
	{
		return ($query_id) ? @mysql_fetch_assoc($query_id) : false;
	}
	
	function fetch_array($query_id = 0)
	{
		return ($query_id) ? @mysql_fetch_array($query_id) : false;
	}


	function fetch_row($query_id = 0)
	{
		return ($query_id) ? @mysql_fetch_row($query_id) : false;
	}


	function num_rows($query_id = 0)
	{
		return ($query_id) ? @mysql_num_rows($query_id) : false;
	}


	function affected_rows()
	{
		return ($this->link_id) ? @mysql_affected_rows($this->link_id) : false;
	}


	function insert_id()
	{
		return ($this->link_id) ? @mysql_insert_id($this->link_id) : false;
	}


	function get_num_queries()
	{
		return $this->num_queries;
	}


	function get_saved_queries()
	{
		return $this->saved_queries;
	}


	function free_result($query_id = false)
	{
		return ($query_id) ? @mysql_free_result($query_id) : false;
	}


	function escape($str)
	{
		if (is_array($str))
			return '';
		else if (function_exists('mysql_real_escape_string'))
			return mysql_real_escape_string($str, $this->link_id);
		else
			return mysql_real_escape_string($str);
	}


	function error()
	{
		$result['error_sql'] = @current(@end($this->saved_queries));
		$result['error_no'] = @mysql_errno($this->link_id);
		$result['error_msg'] = @mysql_error($this->link_id);

		return $result;
	}


	function close()
	{
		if ($this->link_id)
		{
			if ($this->query_result)
				@mysql_free_result($this->query_result);

			return @mysql_close($this->link_id);
		}
		else
			return false;
	}
}
