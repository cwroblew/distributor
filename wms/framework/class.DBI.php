<?php

/*
	Database abstraction class
	
	Purpose: this class provides database abstraction using the PEAR
	MDB2 package.
*/

require_once 'MDB2.php';

class DBI {

	private $VERSION = "1.0.0";
	
	public $errorCode;
	public $error;
	
	function __construct($DB_URL)
	{
		$this->db_url = $DB_URL;
		
		$this->connect();
		
		if ($this->connected == TRUE)
		{
			// set default mode for all resultset
			
			$this->dbh->setFetchMode(MDB2_FETCHMODE_OBJECT);
		} 
	}
	
	function connect()
	{
		// connect to the database
		
		$options = array (
						  'portability'      => MDB2_PORTABILITY_NONE,
						  'result_buffering' => true
						 );
		$status = $this->dbh =& MDB2::connect($this->db_url, $options);

		if (PEAR::isError($status))
		{
			$this->connected = FALSE;
			// echo "DB: ".print_r ($this->dbh, true)."<br />";		
			$this->error = $status->getMessage();
			// echo "Error: $this->error<br />URL: ".print_r ($this->db_url,true)."<br />";		
		} else {
		
			$this->connected = TRUE;
		}
		return $this->connected;
	}
	
	function isConnected()
	{
		return $this->connected;
	}
	
	function disconnect()
	{
		if (isset($this->dbh)) {
			$this->dbh->disconnect();
			return 1;
		} else {
			return 0;
		}
	}
	
	function query($statement)
	{
		$result =  $this->dbh->query($statement);
		
		if (PEAR::isError($result))
		{
			$this->setError($result->getMessage());
			$this->setErrorCode($result->getCode());
			
			return null;
		
		} else {
		
			return $result;
		}
	}
	
	function getLastInsertID ()
	{
		return $this->dbh->lastInsertID ();
	}
	
	function setError($msg = null)
	{
		global $TABLE_DOES_NOT_EXIST, $TABLE_UNKNOWN_ERROR;
		
		$this->error = $msg;
		
		if (strpos($msg, 'no such table'))
		{
			$this->error_type = $TABLE_DOES_NOT_EXIST;
		
		} else {
		
			$this->error_type = $TABLE_UNKNOWN_ERROR;
		}
	}
	
	function setErrorCode($code = null)
	{
		$this->errorCode = $code;
	}
	
	function isError()
	{
		return (!empty($this->error)) ? 1 : 0;
	}
	
	function isErrorType($type = null)
	{
		return ($this->error_type == $type) ? 1 : 0;
	}
	
	function getError()
	{
		return $this->error;
	}
	
	function getErrorCode()
	{
		return $this->errorCode;
	}
	
	function quote($str)
	{
		return "'" . $this->dbh->escape($str) . "'";
	}
	
	function apiVersion()
	{
		return $VERSION;
	}
	
	function formatDate ($date)
	{
		$str = sprintf ("%s", $date);

		if (strlen ($str) == 6)
		{
			return substr ($str, 0, 2)."/".substr ($str, 2, 2)."/".substr ($str, 4, 2);
		} else {
			return substr ($str, 0, 1)."/".substr ($str, 1, 2)."/".substr ($str, 3, 2);
		}
	}
	
	function formatPhone ($phone=null)
	{
		if (!$phone or $phone == 0) return "";
		$str = sprintf ("%s", $phone);
		
		return substr ($str, 0, 3)."-".substr ($str, 3, 3)."-".substr ($str, 6, 4);
	}
}
?>
