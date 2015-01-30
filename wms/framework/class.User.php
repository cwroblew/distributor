<?php

class User {

	private $userTable;
	private $userTypeTable    = USER_TYPE_TABLE;
	private $authButtonsTable = AUTH_BUTTONS_TBL;
	private $buttonsTable;
	private $UserId;
	public $userTblFields;
	private $isUser;
	private $AccessType;
	private $dbi;
	private $numEntries;
	private $NumberEntries;
	private $SortType;
	
	function __construct ($dbi = null, $uid = null)
	{
         global $AUTH_DB_TBL, $USER_TYPE_TABLE, $ENCRYPTION_TYPE_TABLE, $BUTTONS_TBL, 
                $MIN_USERNAME_SIZE, $MIN_PASSWORD_SIZE, $AUTH_DB_URL;

         $this->userTable         = $AUTH_DB_TBL;
         $this->encryptionTypeTbl = $ENCRYPTION_TYPE_TABLE;
		 $this->buttonsTable      = $BUTTONS_TBL;
         $this->userTypeTbl       = $USER_TYPE_TABLE;
		 // $this->userActivityLog   = $USER_ACTIVITY_LOG;
		 
		 $this->userDbUrl = $AUTH_DB_URL;

         $this->dbi = ($dbi != null) ? $dbi : $this->connect($this->userDbUrl);

         $this->minmumUsernameSize = $MIN_USERNAME_SIZE;
         $this->minmumPaswordSize  = $MIN_PASSWORD_SIZE;

         $this->UserId  = ($uid != null) ? $uid : null;

         //$this->debugger = $debugger;

         $this->userTblFields = array('Login'    => 'text',
                                      'Password' => 'text',
                                      'real_name' => 'text',
                                      'Email' => 'text',
                                      'AccessType' => 'text',
                                      'active' => 'text',
                                      'LastAccessDate' => 'now'
                                     );

        if (isset($this->UserId))
        {
            $this->isUser = $this->getUserInfo();
        } else {
            $this->isUser = FALSE;
        }
      }

      function getIsUser()
      {
          return $this->isUser;
      }
	  
	  function setIsUser ($uid=null)
	  {
        if (isset($uid))
        {
            $this->isUser = $this->getUserInfo($uid)?true:$this->getUserIDByName($uid);
        } else {
            $this->isUser = FALSE;
        }
		return $this->isUser;
	  }

      function getUserID()
      {
         return $this->UserId;
      }

      function setUserID($uid = null)
      {
         if (! empty($uid))
         {
            $this->UserId = $uid;
         }
         return $this->UserId;
      }

		function connect($db_url = null)
		{	
			if (!$db_url) return null;
			
			$this->dbi = new DBI($db_url);
			
			return $this->dbi;
		}

      function getUserIDByName($name = null)
      {
         if (! $name ) return null;

         $stmt   = "SELECT UserId FROM $this->userTable WHERE Login = '$name'";
		 // echo "$stmt<br /><br />"; exit;

         $result = $this->dbi->query($stmt);

         if ($result->numRows() > 0)
         {
             $row = $result->fetchRow();

             return $row->UserId;
         }
         return null;
      }

      function getLoginByEmail($email = null)
      {
         if (! $email ) return null;

         $stmt   = "SELECT Login FROM $this->userTable WHERE Email = '$email'";
		 // echo "$stmt<br /><br />"; exit;

         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->Login;
         }
         return null;
      }

      function getUserTypeList($userType=null)
      {
         $stmt   = "SELECT UserTypeId, UserType FROM $this->userTypeTable ORDER BY UserType";
		//  echo "SQL: $stmt<br />";

         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray[$row->UserTypeId] = $row->UserType;
             }
         }
         return $retArray;
      }

      function getUID()
      {
         return (isset($this->UserId)) ? $this->UserId : NULL;
      }

      function getEMAIL()
      {
         return (isset($this->EMAIL)) ?  $this->EMAIL : NULL;
      }

      function getUSERNAME()
      {
         return (isset($this->USERNAME)) ?  $this->USERNAME : NULL;
      }

	  function getFULLNAME ()
	  {
	  	 $firstname = (isset ($this->FIRSTNAME)) ? $this->FIRSTNAME . " " : NULL;
		 $lastname = (isset ($this->LASTNAME)) ? $this->LASTNAME : NULL;

	  	 return $firstname . $lastname;
	  }
	  
      function getFirstName()
      {
         return (isset($this->FirstName)) ? $this->FirstName : NULL;
      }

      function getLastName()
      {
         return (isset($this->LastName)) ? $this->LastName : NULL;
      }
	  
      function getACTIVE()
      {
         return (isset($this->ACTIVE)) ? $this->ACTIVE : NULL;
      }

      function getAccessType2($uid=null)
      {  
	  	 if (!isset ($this->UserId)) $this->UserId = $uid;
         $stmt   = "SELECT AccessType FROM $this->userTable WHERE UserId = $this->UserId";
		 // echo $stmt . "<br />";
		 
         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->AccessType;
         }
         return null;
      }

      function getUserType()
      {  
         $stmt = "SELECT UserType FROM $this->userTable, $this->userTypeTbl ".
		 		 "WHERE AccessType = UserTypeId AND UserId = $this->UserId";
		 // echo $stmt . "<br />";
		 
         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->UserType;
         }

         return null;
      }
	  
	function getNumEntries ()
	{
		return $this->NumberEntries;
	}
	
	function getSortType ()
	{
		// echo "$this->SortType<br />";
		return $this->SortType;
	}
	
	  function getAccessType ()
	  {
	  	return $this->AccessType;
	  }

	  function getUserTableFieldList ()
	  {
	  	return $this->userTblFields;
	  }
	function setEmptyFields ($fieldList)
	{
        $fields = $this->$fieldList ();

         foreach ($fields as $f)
         {
            $this->$f = '';
			// echo $f."=[".$this->$f."]<br />";
         }
	}
	
      function getUserFieldList()
      {
         return array('UserId', 'Login', 'Password', 'real_name', 'FirstName', 'LastName', 
		              'Company', 'Email', 'AccessType', 'active', 'LastAccessDate');
      }

      function getUserFormFieldList()
      {
         return array('UserId', 'Login', 'Password', 'PasswordConfirm', 'real_name', 'Email');
      }

	function getUserAdminDisplayFieldList ()
	{
		return array('FirstName', 'LastName', 'Email', 'Created');
	}

	function getUserAdminDeleteDisplayFieldList ()
	{
		return array('FirstName', 'LastName', 'Email');
	}

	function getUserAdminDisplayColumnList ()
	{
		return array('FirstName' => 'First Name', 'LastName' => 'Last Name', 
		             'Email' => 'Email', 'Created' => 'Created');
	}

	function getUserAdminDeleteColumnList ()
	{
		return array('Name' => 'Name', 'Email' => 'Email');
	}

	function getUserDisplayInfoList($display="Active", $access=1, $limits=null, $sortby=null, $desc=null, $ids=null)
	{
		$fields   = $this->getUserAdminDisplayFieldList();

		$fieldStr = implode(', ', $fields);

		 $where = "WHERE AccessType <= $access ";
		 switch ($display)
		 {
		 	case "NeedApproval":
				$where .= " AND Pending = 'Y' " ;
				break;
		 	case "Viewed":
				$where .= " AND Viewed = 'Y' ";
				break;
		 	case "NotViewed":
				$where .= " AND Viewed != 'Y' ";
				break;
			case "Active":
				$where .= " AND Active = 'y' ";
				break;			
		 }
		if (strlen ($where) > 0)
		{
			$where .= " AND ";
		} else {
			$where .= " WHERE ";
		}
		$where .= "Email != '' ";  // Check that applicant has at least finished the first page
		 if (!empty ($ids))
		 {
		 	$idList = (is_array ($ids))?implode(',', $ids):$ids;
			if (strlen ($where) > 0)
			{
		 		$where .= " AND ";
			} else {
				$where .= " WHERE ";
			}
			$where .= "UserId IN ($idList) ";
		 }
		 $limit = "";
		 if (!empty ($limits) and empty ($ids))
		 {
		 	$limit = "LIMIT $limits->start,$limits->end";
		 }
		 if (isset ($sortby))
		 {
		 	if (isset ($desc))
			{
				$orderType = $desc;
				$this->SortType = '';
			} else {
				$orderType = '';
				$this->SortType = 'DESC';
			}
			// echo "orderType: $orderType - SortType: $this->SortType<br />";
		 	$orderBy = "ORDER BY $sortby ".$orderType;
		 } else {
		 	$orderBy = "ORDER BY Created DESC, UserId";
		 }
		 
		$stmt = "SELECT SQL_CALC_FOUND_ROWS UserId, $fieldStr FROM $this->userTable $where " .
				"$orderBy $limit";

		// echo "$stmt <P>"; 

	     $retArray = array ();
		 
		$result = $this->dbi->query($stmt);

		 $this->numEntries = $result->numRows();
		 
         if ($this->numEntries > 0)
         {
			$sql2 = "SELECT FOUND_ROWS() NumRows;";
			$result2 = $this->dbi->query($sql2);
			$row2 = $result2->fetchRow();
			$this->NumberEntries = $row2->NumRows;

             while($row = $result->fetchRow())
             {
             	foreach($fields as $f)
             	{
					// echo "<p>row: $f: " .  $row->$f."</p>";
			 		$retArray[$row->UserId]->$f = stripslashes ($row->$f);
				}
             }
         }
         return $retArray;
	}

      function getUserInfo($uid = null)
      {
         $fields   = $this->getUserFieldList();

         $fieldStr = implode(',', $fields);

         $this->setUserID($uid);

		 if ($this->UserId == null)
		 {
		 	return FALSE;
		 }
         $stmt   = "SELECT $fieldStr FROM $this->userTable " .
                   "WHERE UserId = '$this->UserId' OR ".
				   "Login = '$this->UserId'";
         // echo "$stmt <P>";

         $result = $this->dbi->query($stmt);

         if ($result->numRows() > 0)
         {
             $row = $result->fetchRow();

             foreach($fields as $f)
             {
			//echo "<p>row: $f: " .  $row->$f;
               $this->$f  = $row->$f;
             }
             return TRUE;
         }
         return FALSE;
      }

	function getUserInfoByLogin($user = null, $pass = null)
	{
		if (! $user or ! $pass) return null;

		$fields   = $this->getUserFieldList();

		$fieldStr = implode(',', $fields);

		$password = (strlen($pass) < 32) ? md5($pass) : $pass;
// echo "Pass: $pass - Password: $password<br />";

         $stmt   = "SELECT $fieldStr FROM $this->userTable ".
		           "WHERE Login = '$user' AND Password = '$password'";
         // echo "$stmt <P>"; 

         $result = $this->dbi->query($stmt);

         if ($result->numRows() > 0)
         {
             $row = $result->fetchRow();
			 $this->setIsUser ($row->UserId);

             foreach($fields as $f)
             {
			//echo "<p>row: $f: " .  $row->$f;
               $this->$f  = $row->$f;
             }
             return TRUE;
         }
         return FALSE;
	}

      function getUserIdbyEmail2($email = null)
      {
	  	if (!$email) return null;
		
         $stmt   = "SELECT UserId FROM $this->userTable " .
                   "WHERE Email = '$email'";
         // echo "SQL: $stmt<br />";
		 
		 $result = $this->dbi->query($stmt);
         
         if($result->numRows() > 0)
         {
            $row = $result->fetchRow();
            
            return $row->UserId;
            
         } else {
          
            return 0;
         }
      }

      function getUserList()
      {
         $stmt   = "SELECT UserId, EMAIL FROM $this->userTable";
		 // echo "getUserList: $stmt<br />";

         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray[$row->UserId] = $row->EMAIL;
             }
         }
         return $retArray;
      }
	  
      function getUserLoginList()
      {
         $stmt   = "SELECT UserId, Login FROM $this->userTable";
		 // echo "getUserLoginList: $stmt<br />";

         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray[$row->UserId] = $row->Login;
             }
         }
         return $retArray;
      }
	  
	function getAuthorizedButtons ($UserId = null)
	{
		if (! $UserId) return null;
		
	  	$sql = "SELECT ab.ButtonId, ButtonDescription ".
			   "FROM $this->authButtonsTable ab, $this->buttonsTable b ".
		       "WHERE UserId = $this->UserId AND ab.ButtonId = b.ButtonId";

		// echo "getAuthorizedButtons: $sql<br />";
		
         $result = $this->dbi->query($sql);
		 
		 $retArray = array ();
         
         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                	$retArray[$row->ButtonId] = $row->ButtonDescription;
             }
         }

         return $retArray;
	}
	
	function checkNewUser ($email = null, $user = null)
	{
		if (! $email and !$user) return null;
		
	  	$stmt = "SELECT COUNT(*) AS test FROM $this->userTable ".
		       "WHERE Email = '$email' OR Login = '$user'";
		// echo "$stmt<br />"; exit;
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}

	function checkLostUser ($email = null)
	{
		if (! $email) return null;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->userTable ".
		       "WHERE Email = '$email'  AND Active = 'y'";

		$result = $this->dbi->query($sql);
		
		return $this->getReturnValue($result);
	}

	function checkReplaceUser ($userId = null, $email = null)
	{
		if (! $userId or ! $email) return null;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->userTable ".
		       "WHERE Password = '$email' AND UserId = $userId";

		$result = $this->dbi->query($sql);
		
		return $this->getReturnValue($result);
	}

	function checkActiveUser ($userId = null)
	{
		if (! $userId) return null;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->userTable ".
		       "WHERE UserId = $userId AND Active = 'n'";

		$result = $this->dbi->query($sql);
		
		return $this->getReturnValue($result);
	}

	function checkValidateUserId ($userId = null)
	{
		if (! $userId) return null;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->userTable ".
		       "WHERE UserId = $userId AND TempEmail <> ''";

		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}

	function checkValidateUser ($user = null, $password = null)
	{
		if (! $user or !$password) return null;
		
		$password = (strlen($password) < 32) ? md5($password) : $password;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->userTable ".
		       "WHERE BINARY Login = '$user' AND Password = '$password' AND ".
			   "Active = 'y'";
		// echo "$sql<br />";

		$result = $this->dbi->query($sql);
		
		return $this->getReturnValue($result);
	}

      function makeUpdateKeyValuePairs($fields = null, $data = null)
      {
          $setValues = array();

          while(list($k, $v) = each($fields))
          {
             if (isset($data->$k))
             {
                  if (! strcmp($v, 'text'))
                  {
                     $v = $this->dbi->quote(addslashes($data->$k));

                     $setValues[] = "$k = $v";

                  } else {

                     $setValues[] = "$k = '".$data->$k."'";
                  }
             }
          }
          return implode(', ', $setValues);
      }

      function updateUser($data = null)
      {
          // $this->setUserID();

          $fieldList = $this->userTblFields;

          $keyVal = $this->makeUpdateKeyValuePairs($this->userTblFields, $data);
		  
          $stmt = "UPDATE $this->userTable SET $keyVal WHERE Login = '$this->Login'";
		  // echo $stmt . "<p>"; exit;
		  
          $result = $this->dbi->query($stmt);

		// $this->logActivity ('Update User');

          return $this->getReturnValue($result);
      }

      function updateUserByLogin($data = null)
      {
          $this->setUserID();

          $fieldList = $this->userTblFields;

          $keyVal = $this->makeUpdateKeyValuePairs($this->userTblFields, $data);

          $stmt = "UPDATE $this->userTable SET $keyVal WHERE login = '$login'";
		  // echo $stmt . "<p>"; exit;
		  
          $result = $this->dbi->query($stmt);

		// $this->logActivity ('Update User');

          return $this->getReturnValue($result);
      }

      function addUser($data = null)
      {
          $fieldList = $this->userTblFields;
          $valueList = array();

          while(list($k, $v) = each($fieldList))
          {
             if (!strcmp($v, 'text'))
             {
                $valueList[] = $this->dbi->quote(addslashes($data->$k));
             } else if (!strcmp($v, 'now')){
				$valueList[] =  $this->dbi->quote(date ('Y-m-d H:i:s'));
			} else {
                $valueList[] = $data->$k;
             }
          }
          $fields = implode(',', array_keys($fieldList));
          $values = implode(',', $valueList);

          $stmt   = "INSERT INTO $this->userTable ($fields) VALUES($values)";
          // echo $stmt; exit;

          $result = $this->dbi->query($stmt);

		  // $this->logActivity ('Insert User');

          return $this->getReturnValue($result);
      }

      function deleteUser($uid = null)
      {
         $this->setUserID($uid);

         $stmt = "DELETE from $this->userTable " .
                 "WHERE UserId = $this->UserId";

         $result = $this->dbi->query($stmt);

		 $this->logActivity ('Delete');

         return $this->getReturnValue($result);
      }

	function updateVisit ($login= null, $pass=null)
	{
		if (! $login or ! $pass) return null;

		$stmt = "UPDATE $this->userTable SET LastVisit = date('Y-m-d H:i:s') ".
		        "WHERE Login = '".$login."' AND Password = '".md5($pass);

         $result = $this->dbi->query($stmt);

		 $this->logActivity ('Update Visit');

         return $this->getReturnValue($result);
	}
	
	function updateValidate ($validationKey = null, $keyId= null)
	{
		if (! $validationKey or ! $keyId) return null;

		$stmt = "UPDATE $this->userTable SET Email = TempEmail, TempEmail = '' ".
		        "WHERE Password = '".md5($keyId)."' AND UserId = 'validationKey'";

         $result = $this->dbi->query($stmt);

		 $this->logActivity ('Validate User');

         return $this->getReturnValue($result);
	}
	
	function updateActivate ($activate_key= null, $keyId= null)
	{
		if (! $validationKey or ! $keyId) return null;

		$stmt = "UPDATE $this->userTable SET Active = 'y' ".
		        "WHERE Password = '".md5($keyId)."' AND UserId = 'validationKey'";

         $result = $this->dbi->query($stmt);
		 
		 $this->logActivity ('Activate User');

         return $this->getReturnValue($result);
	}
	
	function updatePassword ($newPassword = null, $oldPassword = null, $UserId = null)
	{
		if (! $newPassword or ! $newPassword or ! $UserId) return null;

		$stmt = "UPDATE $this->userTable SET Password = '".md5($newPassword)."'".
		        " WHERE Password = '".md5($newPassword)."' AND UserId = $UserId";

         $result = $this->dbi->query($stmt);

		 $this->logActivity ('Update Password');

         return $this->getReturnValue($result);
	}
	
      function getReturnValue($r = null)
      {
          return ($r == MDB2_OK) ? TRUE : FALSE;
      }

     function logActivity($action = null)
     {
     	$now = time();

     	$stmt = "INSERT INTO  $this->userActivityLog SET " .
     	        "UserId     = $this->UserId, ".
     	        "ACTION_TYPE = $action, " .
     	        "ACTION_TS = $now";
        // echo "$stmt <P>";

        $result = $this->dbi->query($stmt);

        return $this->getReturnValue($result);
     }
   }
?>
