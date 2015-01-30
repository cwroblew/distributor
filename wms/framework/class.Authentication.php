<?php

/*
*
* Application class
*
* @author EVOKNOW, Inc. <php@evoknow.com>
* @access public
* CVS ID: $Id$
*/

  class Authentication {

	private $UserName;
	private $password;
	private $AccessType;
	
     function __construct ($UserName = null, $password = null, $dbUrl = null)
     {
        global $AUTH_DB_TBL;

     	$this->status = FALSE;
     	$this->UserName = $UserName;
     	$this->password = $password;
        $this->authTbl = $AUTH_DB_TBL;

        $this->dbUrl = ($dbUrl == null) ? null : $dbUrl;
		// echo "DB: ".print_r ($this->dbUrl,true)."<br />";
        if ($dbUrl == null)
        {
           global $AUTH_DB_URL;

           $this->dbUrl = $AUTH_DB_URL;
        }

        $this->status = FALSE;
     }
	 function getAccessType ()
	 {
	 	return isset ($this->AccessType)? $this->AccessType : 0;
	 }

	static function loginsSuspended ()
	{
		global $SUSPEND_WARNING, $SUSPEND_STARTTIME, $SUSPEND_ENDTIME;

		$curTime = strtotime (date_format (date_create("now", timezone_open('America/Chicago')), 'H:i:s'));

		$suspendTime = strtotime ($SUSPEND_STARTTIME);
		$endTime = strtotime ($SUSPEND_ENDTIME);
		$minutes=60;
		$diffStart = ($suspendTime - $curTime)/$minutes;
		$diffEnd = ($curTime - $endTime)/$minutes;

		if ($diffStart < 0 and $diffEnd > 0) 
		{
			return true;  // Time is less than range
			// return false; // turn off suspend login for testing
		}
		return false; // Time is beyond range
	}
	
     function authenticate()
     {
        $dbi = new DBI ($this->dbUrl);

        $query  = "SELECT UserId, Password, AccessType from " . $this->authTbl;
        $query .= " WHERE Login = '" . $this->UserName . "' AND active = 'y'";
		// echo "Authenticate: $query<br />";
		
        $result = $dbi->query($query);

        if ($row = $result->fetchRow())
        {
			$this->AccessType = $row->AccessType;

          if (md5($this->password) == $row->Password)
           {
              $this->status  = TRUE;
              $this->UserId = $row->UserId;
			  return TRUE;

           } else {
              $this->status = FALSE;
           }
        } else {
			$this->AccessType = MIN_ACCESS_LEVEL;
			$this->status = FALSE;
	   }
        $dbi->disconnect();

     	return $this->status;
     }
	 
	 function isAdmin ($uname=null)
	 {
	 	if (!$uname) return FALSE;

        $dbi = new DBI ($this->dbUrl);

        $query  = "SELECT AccessType from $this->authTbl ".
        		  "WHERE Login = '$uname' AND active = 'y'";
		// echo "Authenticate: $query<br />";
		
        $result = $dbi->query($query);

        if ($row = $result->fetchRow())
        {
          if ($row->AccessType >= DEFAULT_ADMIN_LEVEL)
           {
			  $status = TRUE;

           } else {
              $status = FALSE;
           }
        } else {
			$status = FALSE;
	   }
     	return $status;
	 }

 	 function isSpecial ($uname=null)
	 {
	 	if (!$uname) return FALSE;

        $dbi = new DBI ($this->dbUrl);

        $query  = "SELECT AccessType from $this->authTbl ".
        		  "WHERE Login = '$uname' AND active = 'y'";
		// echo "Authenticate: $query<br />";
		
        $result = $dbi->query($query);

        if ($row = $result->fetchRow())
        {
          if ($row->AccessType >= DEFAULT_SPECIAL_LEVEL)
           {
			  $status = TRUE;

           } else {
              $status = FALSE;
           }
        } else {
			$status = FALSE;
	   }
     	return $status;
	 }

     function getUID()
     {
         return $this->UserId;
     }
	 
	 static function logout()
	 {
		session_unset();
		session_destroy();
	 }
  }

?>
