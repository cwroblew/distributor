<?php

class Distributor {

	private $distributorTable;
	private $distributorPriceTable;
	private $distributorDropTable;
	private $DistributorId;
	public $distributorTblFields;
	public $distributorPriceTblFields;
	public $distributorDropTblFields;
	private $isDistributor;
	private $AccessType;
	private $dbi;
	private $numEntries;
	private $NumberEntries;
	private $SortType;
	// private $user;
	
	function __construct ($dbi = null, $uid = null)
	{
         global $DISTRIBUTOR_TBL, $DISTRIBUTOR_PRICE_TBL, $DISTRIBUTOR_DROP_TBL;

         $this->distributorTable      = $DISTRIBUTOR_TBL;
         $this->distributorPriceTable = $DISTRIBUTOR_PRICE_TBL;
         $this->distributorDropTable  = $DISTRIBUTOR_DROP_TBL;

         $this->dbi             = $dbi;
		 // $this->user = new User ($this->dbi);

         $this->DistributorId  = ($uid != null) ? $uid : null;

         //$this->debugger = $debugger;

         $this->distributorTblFields = array('dsdisn' => 'number',
		                                     'DistParentNumber' => 'text',
		                                     'dsname' => 'text',
											 'dsemal' => 'text',
											 'dscont' => 'text',
											 'dsphon' => 'phone',
											 'dsfaxn' => 'phone',
											 'active' => 'text',
											 'OrderFormCode' => 'text',
											 'Address1' => 'text', 
											 'Address2' => 'text', 
											 'Address3' => 'text',
											 'City' => 'text', 
											 'State' => 'text', 
											 'Zip' => 'text',
											 'dslmdt' => 'now'
											);

         $this->distributorPriceTblFields = array('DistributorNo'  => 'text',
												  'CatalogNo'      => 'text',
												  'Price'          => 'number'
												  );

         $this->distributorDropTblFields = array('drdisn' => 'text',
												 'drdrop' => 'text',
												 'drname' => 'text',
												 'dradd1' => 'text',
												 'dradd2' => 'number',
												 'dradd3' => 'number',
												 'dradd4' => 'number',
												 'drst'   => 'number',
												 'drzipc' => 'number'
												);

        if (isset($this->DistributorId))
        {
            $this->isDistributor = $this->getDistributorInfo();
        } else {
            $this->isDistributor = FALSE;
        }
      }

	function isDistributorEntry ($did = null)
	{
		if (!$did) return $this->isDistributorEntry;
		
		$stmt = "SELECT dsdisn from $this->distributorTable ".
		       "WHERE dsdisn = '$did'";
		//echo "SQL: $stmt<br />\r\n";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->setDistributorId ($did);
			$this->isDistributorEntry = true;
		} else {
			$this->isDistributorEntry = false;
		}
		return $this->isDistributorEntry;
	}
      function getIsDistributor()
      {
          return $this->isDistributor;
      }
      function getDistributorID()
      {
         return $this->DistributorId;
      }

      function setDistributorId($uid = null)
      {
         if (! empty($uid))
         {
            $this->DistributorId = $uid;
        }
         return $this->DistributorId;
      }

      function getDistributorIDByName($name = null)
      {
         if (! $name ) return null;

         $stmt   = "SELECT DistributorId FROM $this->distributorTable WHERE Login = '$name'";
		 // echo "$stmt<br /><br />";

         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->DistributorId;
         }
         return null;
      }

      function getDistributorName($did = null)
      {
         if (! $did ) return null;

         $stmt   = "SELECT dsname FROM $this->distributorTable WHERE dsdisn = '$did'";
		 // echo "$stmt<br /><br />";

         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->dsname;
         }
         return null;
      }

      function getDistributorState($did = null)
      {
         if (! $did ) return null;

         $stmt   = "SELECT State FROM $this->distributorTable WHERE dsdisn = '$did'";
		 // echo "$stmt<br /><br />";

         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->State;
         }
         return null;
      }

	  function getChildDistributorId ()
	  {
         $stmt   = "SELECT dsdisn FROM $this->distributorTable ".
		           "WHERE DistParentNumber = '$this->DistributorId' LIMIT 1";
		 // echo "$stmt<br /><br />"; exit;

         $result = $this->dbi->query($stmt);

         if ($result->numRows() > 0)
         {
             $row = $result->fetchRow();

             return $row->dsdisn;
         }
         return null;
	  }

 	  function getParentDistributorId ($uid)
	  {
         $stmt   = "SELECT DistParentNumber FROM $this->distributorTable ".
		           "WHERE dsdisn = '$uid'";
		 // echo "$stmt<br /><br />"; exit;

         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->DistParentNumber;
         }
         return null;
	  }
	  
	  function getDistributorOrderCodeForm ()
	  {
		  return $this->OrderFormCode;
	  }

    function getDistributorTypeList($distributorType=null)
      {
         $stmt   = "SELECT DistributorTypeId, DistributorType FROM $this->distributorTypeTable ORDER BY DistributorType";
		//  echo "SQL: $stmt<br />";

         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray[$row->DistributorTypeId] = $row->DistributorType;
             }
         }
         return $retArray;
      }

      function getUID()
      {
         return (isset($this->DistributorId)) ? $this->DistributorId : NULL;
      }

      function getEmail()
      {
         return (isset($this->dsemal)) ?  $this->dsemal : NULL;
      }

      function getUsername()
      {
         return (isset($this->Username)) ?  $this->Username : NULL;
      }

	  function getFULLNAME ()
	  {
	  	 $firstname = (isset ($this->FIRSTNAME)) ? $this->FIRSTNAME . " " : NULL;
		 $lastname = (isset ($this->LASTNAME)) ? $this->LASTNAME : NULL;

	  	 return $firstname . $lastname;
	  }
	  
      function getPASSWORD()
      {
         return (isset($this->PASSWORD)) ? $this->PASSWORD : NULL;
      }

      function getFirstName()
      {
         return (isset($this->FirstName)) ? $this->FirstName : NULL;
      }

      function getLastName()
      {
         return (isset($this->LastName)) ? $this->LastName : NULL;
      }
	  
      function getCOMPANY()
      {
         return (isset($this->COMPANY)) ? $this->COMPANY : NULL;
      }
	  
      function getActive()
      {
         return (isset($this->active)) ? $this->active : NULL;
      }

      function getType($uid)
      {  
	  	 if (!isset ($this->DistributorId)) $this->DistributorId = $uid;

         $stmt   = "SELECT AccessType FROM $this->distributorTable WHERE DistributorId = $this->DistributorId";
		 // echo $stmt . "<br />";
		 
         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->AccessType;
         }
         return null;
      }

      function getDistributorType()
      {  
         $stmt = "SELECT DistributorType FROM $this->distributorTable, $this->distributorTypeTbl ".
		 		 "WHERE AccessType = DistributorTypeId AND DistributorId = $this->DistributorId";
		 // echo $stmt . "<br />";
		 
         $result = $this->dbi->query($stmt);

         if ($result != null)
         {
             $row = $result->fetchRow();

             return $row->AccessType;
         }

         return null;
      }
	  
      function getDistributorPrice($CatalogNo)
      {  
         $stmt = "SELECT Price FROM $this->distributorPriceTable ".
		 		 "WHERE DistributorNo = $this->DistributorId AND CatalogNo = '$CatalogNo'";
		 // echo $stmt . "<br />";
		 
         $result = $this->dbi->query($stmt);

         if ($result->numRows() > 0)
         {
             $row = $result->fetchRow();

             return $row->Price;
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

	  function getDistributorTableFieldList ()
	  {
	  	return $this->distributorTblFields;
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
	
      function getDistributorFieldList()
      {
         return array('dsdisn', 'DistParentNumber', 'dsname', 'dsemal', 'dscont', 'dsphon',
		              'dsfaxn', 'active', 'OrderFormCode', 'Address1', 'Address2', 'Address3',
					 'City', 'State', 'Zip', 'dslmdt');
      }
	  function getDistributorPricingFieldList()
	  {
         return array('DistributorNo', 'CatalogNo', 'Price');
	  }
	  
	  function getDistributorDropFieldList()
	  {
         return array('drdisn', 'drdrop', 'drname', 'dradd1', 'dradd2', 'dradd3',
		              'dradd4', 'drst', 'drzipc');
	  }

	function getDistributorCSVFieldList()
	{
		return array('dsdisn', 'dsname', 'dsemal', 'dscont', 'dsphon', 'dsfaxn', 'active',
		             'OrderFormCode', 'DistParentNumber', 'Address1', 'Address2', 'Address3',
					 'City', 'State', 'Zip', 'dslmdt');
	}
	
      function getDistributorInfo($uid = null)
      {
         $fields   = $this->getDistributorFieldList();

         $fieldStr = implode(',', $fields);

         $this->setDistributorId($uid);

		 if ($this->DistributorId == null)
		 {
		 	return FALSE;
		 }
         $stmt   = "SELECT $fieldStr FROM $this->distributorTable " .
                   "WHERE dsdisn = '$this->DistributorId'";
         // echo "$stmt <P>";

         $result = $this->dbi->query($stmt);

         if ($result->numRows() > 0)
         {
             $row = $result->fetchRow();

             foreach($fields as $f)
             {
			//echo "<p>row: $f: " .  $row->$f;
				if ($this->distributorTblFields [$f] == "phone")
				{
					$this->$f = $this->dbi->formatPhone($row->$f);
				} else {
					$this->$f  = $row->$f;
				}
             }
             return TRUE;
         }
         return FALSE;
      }

      function getParentInfo()
      {
         $fields   = $this->getDistributorFieldList();

         $fieldStr = implode(',', $fields);

         $stmt   = "SELECT $fieldStr FROM $this->distributorTable " .
                   "WHERE dsdisn = '$this->ParentDistributor'";
         // echo "$stmt <P>";

         $result = $this->dbi->query($stmt);

         if ($result->numRows() > 0)
         {
             $row = $result->fetchRow();

             foreach($fields as $f)
             {
			//echo "<p>row: $f: " .  $row->$f;
				if ($this->distributorTblFields [$f] == "phone")
				{
					$this->$f = $this->dbi->formatPhone($row->$f);
				} else {
					$this->$f  = $row->$f;
				}
             }
             return $parent;
         }
         return FALSE;
      }

      function getDistributorIDbyEmail($email = null)            // needed for EIS
      {
         $stmt   = "SELECT id FROM $this->distributorTable " .
                   "WHERE Email = '$email'";

         $result = $this->dbi->query($stmt);
         
         if($result->numRows() > 0)
         {
            $row = $result->fetchRow();
            
            return $row->DistributorId;
            
         } else {
          
            return 0;
         }
      }

      function getDistributorEmailList()
      {
         $stmt   = "SELECT DistributorId, Email FROM $this->distributorTable";
		 // echo "getDistributorList: $stmt<br />";

         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray[$row->DistributorId] = $row->Email;
             }
         }
         return $retArray;
      }
	  
      function getDistributorNameList()
      {
         $stmt   = "SELECT dsdisn, dsname FROM $this->distributorTable ".
		 		   "WHERE Active != 'I' ".
		 		   "ORDER BY dsname";
		 // echo "getDistributorList: $stmt<br />";

         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray[$row->dsdisn] = $row->dsname;
             }
         }
         return $retArray;
      }
	  
 	  function getDistributorChildList ()
	  {
         $stmt   = "SELECT dsdisn, dsname FROM $this->distributorTable ".
		 		   // "WHERE Active != 'I' AND ". // Active?
				   "WHERE DistParentNumber = '$this->DistributorId' ".
				   "OR dsdisn = '$this->DistributorId' ".
		 		   "ORDER BY dsname";
		 // echo "getDistributorChildList: $stmt<br />";

         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray[$row->dsdisn] = $row->dsname;
             }
         }
         return $retArray;
	  }

      function getDistributorLoginList()
      {
         $stmt   = "SELECT DistributorId, Login FROM $this->distributorTable ".
		 		   "WHERE Active != 'I' ";
		 // echo "getDistributorLoginList: $stmt<br />";

         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray[$row->DistributorId] = $row->Login;
             }
         }
         return $retArray;
      }

	function getDropbyDistributorList ()
	{
         $fields   = $this->getDistributorDropFieldList();

         $fieldStr = implode(',', $fields);

         $stmt   = "SELECT drdrop, drname, dradd1 FROM $this->distributorDropTable ".
		           "WHERE drdisn = '$this->DistributorId'";
		 // echo "getDropbyDistributorList: $stmt<br />";

         $result = $this->dbi->query($stmt);

         $retArray = array();

         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                $retArray [$row->drdrop] = "$row->drdrop - $row->drname - $row->dradd1";
             }
         }
         return $retArray;
	}
		  
	function getCSVDistributorList($las400=null)
	{
		if (!$las400) return null;
		
		$fields   = $this->getDistributorCSVFieldList();
		
		$fieldStr = implode(', ', $fields);
		
		$stmt = "SELECT $fieldStr FROM $this->distributorTable " .
				"WHERE dslmdt >= '$las400' ".
				"ORDER BY dsdisn ";
		// echo "getCSVDistributorList: $stmt <P>"; exit;
		
		$retArray = array ();
		
		$result = $this->dbi->query($stmt);
		
		if ($result->numRows() > 0)
		{
			$i = 0;
			while($row = $result->fetchRow())
			{
				foreach($fields as $f)
				{
					// echo "<p>row: $f: " .  $row->$f."</p>";
					if ($this->distributorTblFields [$f] == 'date' or $this->distributorTblFields [$f] == 'now')  // special date formating for AS400
					{
						$retArray[$i][$f] = date_format (date_create ($row->$f), 'Ymd');
					} else {
						$retArray[$i][$f] = stripslashes ($row->$f);
					}
				}
			$i++;
			}
		}
		return $retArray;
	}
	
	function getAuthorizedButtons ($DistributorId = null)
	{
		if (! $DistributorId) return null;
		
	  	$sql = "SELECT ab.ButtonId, ButtonDescription ".
			   "FROM $this->authButtonsTable ab, $this->buttonsTable b ".
		       "WHERE DistributorId = $this->DistributorId AND ab.ButtonId = b.ButtonId";

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
	
	function checkNewDistributor ($email = null, $distributor = null)
	{
		if (! $email and !$distributor) return null;
		
	  	$stmt = "SELECT COUNT(*) AS test FROM $this->distributorTable ".
		       "WHERE Email = '$email' OR Login = '$distributor'";
		// echo "$stmt<br />"; exit;
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}

	function checkLostDistributor ($email = null)
	{
		if (! $email) return null;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->distributorTable ".
		       "WHERE Email = '$email'  AND Active = 'y'";

		$result = $this->dbi->query($sql);
		
		return $this->getReturnValue($result);
	}

	function checkReplaceDistributor ($distributorId = null, $email = null)
	{
		if (! $distributorId or ! $email) return null;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->distributorTable ".
		       "WHERE Password = '$email' AND DistributorId = $distributorId";

		$result = $this->dbi->query($sql);
		
		return $this->getReturnValue($result);
	}

	function checkActiveDistributor ($distributorId = null)
	{
		if (! $distributorId) return null;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->distributorTable ".
		       "WHERE DistributorId = $distributorId AND Active = 'I'";

		$result = $this->dbi->query($sql);
		
		return $this->getReturnValue($result);
	}

	function checkValidateDistributorId ($distributorId = null)
	{
		if (! $distributorId) return null;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->distributorTable ".
		       "WHERE DistributorId = $distributorId AND TempEmail <> ''";

		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}

	function checkValidateDistributor ($distributor = null, $password = null)
	{
		if (! $distributor or !$password) return null;
		
		$password = (strlen($password) < 32) ? md5($password) : $password;
		
	  	$sql = "SELECT COUNT(*) AS test FROM $this->distributorTable ".
		       "WHERE BINARY Login = '$distributor' AND Password = '$password' AND ".
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
				if (! strcmp($v, 'text') or ! strcmp($v, 'phone'))
				{
					$v = $this->dbi->quote(addslashes($data->$k));
					
					$setValues[] = "$k = $v";
		
				} else if (! strcmp($v, 'date')) 
				{  // $phone = preg_replace('/\D/', '', $data->$k);
					if (strlen ($data->$k) == 6)
					{
						$v = $this->dbi->quote(substr ($data->$k, 0, 2)."/".substr ($data->$k, 2, 2)."/".substr ($data->$k, 4, 2));
					} else {
						$v = $this->dbi->quote(substr ($data->$k, 0, 1)."/".substr ($data->$k, 1, 2)."/".substr ($data->$k, 3, 2));
					}
					$setValues[] = "$k = $v";
				} else if (!strcmp($v, 'now')){
					$setValues[] =  "$k = ".$this->dbi->quote(date ('Y-m-d H:i:s'));
				} else {
				
					$setValues[] = "$k = '".$data->$k."'";
				}
			} 
		}
		return implode(', ', $setValues);
	}

      function updateDistributor($data = null)
      {
          $this->setDistributorId();

          $fieldList = $this->distributorTblFields;

		  if (!$data->DistParentNumber or $data->DistParentNumber == "")
		  {
			  $data->DistParentNumber = $this->getParentDistributorId ($this->DistributorId);
		  }

          $keyVal = $this->makeUpdateKeyValuePairs($this->distributorTblFields, $data);

          $stmt = "UPDATE $this->distributorTable SET $keyVal ".
		          "WHERE dsdisn = '$this->DistributorId'";
		  // echo $stmt . "<p>";

          $result = $this->dbi->query($stmt);

          return $this->getReturnValue($result);
      }

      function addDistributor($data = null)
      {
          $fieldList = $this->distributorTblFields;
          $valueList = array();

          while(list($k, $v) = each($fieldList))
          {
             if (!strcmp($v, 'text') or !strcmp($v, 'check') or !strcmp($v, 'date'))
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

          $stmt   = "INSERT INTO $this->distributorTable ($fields) VALUES($values)";
          // echo $stmt;
          $result = $this->dbi->query($stmt);

          return $this->getReturnValue($result);
      }

       function addPrices($data = null)
      {
          $fieldList = $this->distributorPriceTblFields;
          $valueList = array();

          while(list($k, $v) = each($fieldList))
          {
             if (!strcmp($v, 'text') or !strcmp($v, 'check') or !strcmp($v, 'date'))
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

          $stmt   = "INSERT INTO $this->distributorPriceTable ($fields) VALUES($values)";
          // echo $stmt;
          $result = $this->dbi->query($stmt);

          return $this->getReturnValue($result);
      }

     function deleteDistributor($uid = null)
      {
         $this->setDistributorId($uid);

         $stmt = "DELETE from $this->distributorTable " .
                 "WHERE DistributorId = $this->DistributorId";

         $result = $this->dbi->query($stmt);

		 $this->logActivity ('Delete');

         return $this->getReturnValue($result);
      }
	function updateVisit ($login= null, $pass=null)
	{
		if (! $login or ! $pass) return null;

		$stmt = "UPDATE $this->distributorTable SET LastVisit = date('Y-m-d H:i:s') ".
		        "WHERE Login = '".$login."' AND Password = '".md5($pass);

         $result = $this->dbi->query($stmt);

		 $this->logActivity ('Update Visit');

         return $this->getReturnValue($result);
	}
	
	function updateValidate ($validationKey = null, $keyId= null)
	{
		if (! $validationKey or ! $keyId) return null;

		$stmt = "UPDATE $this->distributorTable SET Email = TempEmail, TempEmail = '' ".
		        "WHERE Password = '".md5($keyId)."' AND DistributorId = 'validationKey'";

         $result = $this->dbi->query($stmt);

		 $this->logActivity ('Validate Distributor');

         return $this->getReturnValue($result);
	}
	
	function updateActivate ($activate_key= null, $keyId= null)
	{
		if (! $validationKey or ! $keyId) return null;

		$stmt = "UPDATE $this->distributorTable SET Active = 'y' ".
		        "WHERE Password = '".md5($keyId)."' AND DistributorId = 'validationKey'";

         $result = $this->dbi->query($stmt);
		 
		 $this->logActivity ('Activate Distributor');

         return $this->getReturnValue($result);
	}
	
	function updatePassword ($newPassword = null, $oldPassword = null, $DistributorId = null)
	{
		if (! $newPassword or ! $newPassword or ! $DistributorId) return null;

		$stmt = "UPDATE $this->distributorTable SET Password = '".md5($newPassword)."'".
		        " WHERE Password = '".md5($newPassword)."' AND DistributorId = $DistributorId";

         $result = $this->dbi->query($stmt);

		 $this->logActivity ('Update Password');

         return $this->getReturnValue($result);
	}
	
	function clearPricing ()
	{
		$stmt = "TRUNCATE TABLE $this->distributorPriceTable";
		// echo "SQL: $stmt<br />";
		
		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

      function getReturnValue($r = null)
      {
          return ($r == MDB2_OK) ? TRUE : FALSE;
      }

     function logActivity($action = null)
     {
     	$now = time();

     	$stmt = "INSERT INTO  $this->distributorActivityLog SET " .
     	        "DistributorId     = $this->DistributorId, ".
     	        "ACTION_TYPE = $action, " .
     	        "ACTION_TS = $now";
        // echo "$stmt <P>";

        $result = $this->dbi->query($stmt);

        return $this->getReturnValue($result);
     }
}
?>
