<?php

class Catalog
{
	private $catalogTbl;
	private $catalogBHTbl;
	private $catalogTypeTbl;
	private $catalogFormSubjectTbl;
	private $userActivityLog;
	private $dbi;
	private $CatalogId;
	public $catalogTypeTblFields;
	public $catalogTblFields;
	public $catalogFormSubjectTblFields;
	private $isCatalog;
	private $isNewCatalog;
	private $userNameMaxChar;
	private $numEntries = 0;
	private $NumberEntries;
	private $SortType;
	
	function __construct ($dbi = null, $uid = null)
	{
		global $CATALOG_TBL, $CATALOG_BH_TBL;
		global $CATALOG_ACTIVITY_LOG_TBL, $USER_NAME_MAX_CHAR;

		$this->catalogTbl      = $CATALOG_TBL;
		$this->catalogBHTbl    = $CATALOG_BH_TBL;
		$this->userActivityLog = $CATALOG_ACTIVITY_LOG_TBL;
		$this->dbi             = $dbi;

		$this->CatalogId  = ($uid != null) ? $uid : null;

		// These fields are used by ALL catalog tables:
		$this->catalogTblFields = array('ctcatn'       => 'text',
										'Description'  => 'text',
										'ctwght'       => 'number',
										'qtypal'       => 'number',
										'MinQty'       => 'number',
										'DisplayOrder' => 'number',
										'Special'     => 'text',
										'ProductType'  => 'text',
										'ProductGroup' => 'text',
										'ProductSize'  => 'text'
										);

		if (isset($this->CatalogId))
		{
			$this->isCatalog = $this->getCatalogInfo();
		} else {
			$this->isCatalog = FALSE;
		}
	}

	function isCatalogEntry ($cid = null)
	{
		if (!$cid) return $this->isCatalogEntry;
		
		$stmt = "SELECT ctcatn from $this->catalogTbl ".
		       "WHERE ctcatn = '$cid'";
		//echo "SQL: $stmt<br />\r\n";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isCatalogEntry = true;
		} else {
			$this->isCatalogEntry = false;
		}
		return $this->isCatalogEntry;
	}

	function isCatalogHeaderEntry ($desc = null)
	{
		if (!$desc) return $this->isCatalogEntry;
		$cleanDescription = addslashes($desc);
		$stmt = "SELECT ctcatn from $this->catalogTbl ".
		       "WHERE Description = '$cleanDescription'";
		// echo "SQL: $stmt<br />\r\n";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isCatalogHeaderEntry = true;
		} else {
			$this->isCatalogHeaderEntry = false;
		}
		return $this->isCatalogHeaderEntry;
	}

	function isCatalog()
	{
		return $this->isCatalog;
	}

	function getCatalogID()
	{
		return $this->CatalogId;
	}
	
	function getProductType ($cid=null)
	{
		if (!$cid) return isset ($this->ProductType)?$this->ProductType:0;
		
		$stmt = "SELECT ProductType FROM $this->catalogTbl " .
				"WHERE ctcatn = '$cid'";
		// echo "getProductType: $stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		if($result->numRows() > 0)
		{
			$row = $result->fetchRow();
			
			return $row->ProductType;
		
		} else {
		
			return 0;
		}
	}

	function getProductGroup ($cid=null)
	{
		if (!$cid) return isset ($this->ProductGroup)?$this->ProductGroup:0;
		
		$stmt = "SELECT ProductGroup FROM $this->catalogTbl " .
				"WHERE ctcatn = '$cid'";
		// echo "getProductGroup: $stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		if($result->numRows() > 0)
		{
			$row = $result->fetchRow();
			
			return $row->ProductGroup;
		
		} else {
		
			return 0;
		}
	}

	function getCatalogIdByName ($name = null)
	{
		$stmt = "SELECT ctcatn FROM $this->catalogTbl " .
				"WHERE Description = '$name'";
		// echo "SQl: $stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		if($result->numRows() > 0)
		{
			$row = $result->fetchRow();
			
			return $row->ctcatn;
		
		} else {
		
			return 0;
		}
	}
	
	function setCatalogID($uid = null)
	{
		if (! empty($uid))
		{
			$this->CatalogId = $uid;
		}
		return $this->CatalogId;
	}

	function getUID()
	{
		return (isset($this->CatalogId)) ? $this->CatalogId : NULL;
	}

	function getName()
	{
		return (isset($this->Name)) ?  $this->Name : NULL;
	}

	function getNumEntries ()
	{
		return $this->NumberEntries;
	}
	
	function getCatalogWeight ($ctcatn)
	{
		if (!$ctcatn) return null;
		
		$stmt   = "SELECT ctwght FROM $this->catalogTbl WHERE ctcatn = '$ctcatn'";
		// echo "$stmt<br /><br />";
		
		$result = $this->dbi->query($stmt);
		
		if ($result != null)
		{
			$row = $result->fetchRow();
			
			return $row->ctwght;
		}
		return null;
	}
	
	function getCatalogDescription ($ctcatn)
	{
		if (!$ctcatn) return null;
		
		$stmt   = "SELECT Description FROM $this->catalogTbl WHERE ctcatn = '$ctcatn'";
		// echo "$stmt<br /><br />";
		
		$result = $this->dbi->query($stmt);
		
		if ($result != null)
		{
			$row = $result->fetchRow();
			
			return $row->Description;
		}
		return null;
	}
	
	function getCatalogProductSize ($ctcatn)
	{
		if (!$ctcatn) return null;
		
		$stmt   = "SELECT ProductSize FROM $this->catalogTbl WHERE ctcatn = '$ctcatn'";
		// echo "$stmt<br /><br />";
		
		$result = $this->dbi->query($stmt);
		
		if($result->numRows() > 0)
		{
			$row = $result->fetchRow();
			return $row->ProductSize;
		}
		return null;
	}
	
	function getCatalogTypeFieldList()
	{
		return array('CatalogType', 'CatalogTypeDescription', 'RequireComment');
	}

	function getCatalogFieldList()
	{
		return array('ctcatn', 'Description', 'ctwght', 'qtypal', 'MinQty', 'Special', 'DisplayOrder', 'ProductType', 'ProductSize', 'ProductGroup');
	}

	function getCatalogTemplateFieldList()
	{
		return array('ctcatn' => 'Catalog #', 'Description' => 'Description', 
		             'ctwght' => 'Weight', 'qtypal' => 'Qty/pallet', 'MinQty' => 'Min qty', 'Special' => 'Contact Sales');
	}
	function getCatalogFileFieldList()
	{
		return array('ctcatn', 'ctwght', 'ProductType', 'ProductSize', 'ProductGroup');
	}


	  function getCatalogList ($cfl=null, $oftype='A')
	  {
		  global $CIDER_PRODUCT_GROUP;
	  	if (!$cfl) return null;
		
		$fields   = $this->$cfl();

		$fieldStr = implode(', ', $fields);

		// get listing based on whether 'Beer' only, 'Cider' only, or all
		switch ($oftype)
		{
			case 'A':
				break; // do nothing right now
			case 'B':
				$productTypes = " AND Description != 'CIDERBOYS' AND ProductGroup NOT IN $CIDER_PRODUCT_GROUP";
				break;
			case 'C':
				$productTypes = " AND (Description = 'CIDERBOYS' OR ProductGroup IN $CIDER_PRODUCT_GROUP)";
				break;
			case 'D':
				break;
		}
		if (!isset ($productTypes))
		{
			$productTypes = ''; // $oftype = 'A'
		}
		$stmt = "SELECT $fieldStr FROM $this->catalogTbl ".
				"WHERE DisplayOrder != 0 ".$productTypes.
				"ORDER BY DisplayOrder";

		// echo "$stmt <P>";  exit;

		$retArray = Array ();
		
		$result = $this->dbi->query($stmt);
		
		if ($result->numRows() > 0)
		{
             while($row = $result->fetchRow())
             {
             	foreach($fields as $f)
             	{
					// echo "<p>row: $f: " .  $row->$f."</p>";
			 		$retArray[$row->DisplayOrder]->$f = stripslashes ($row->$f);
				}
             }
		}
         return $retArray;
	  }

	  function getBHCatalogList ($cfl=null, $oftype='D')
	  {
		  global $BH_PRODUCT_TYPES;
	  	if (!$cfl) return null;
		
		$fields   = $this->$cfl();

		$fieldStr = implode(', ', $fields);

		// get listing based on whether 'Beer' only, 'Cider' only, or all
		switch ($oftype)
		{
			case 'D':
				$productTypes = " AND ProductType IN $BH_PRODUCT_TYPES";
				break;
		}
		if (!isset ($productTypes))
		{
			$productTypes = ''; // $oftype = 'A'
		}
		$stmt = "SELECT $fieldStr FROM $this->catalogBHTbl ".
				"WHERE DisplayOrder != 0 ".$productTypes.
				"ORDER BY DisplayOrder";

		// echo "$stmt <P>";  exit;

		$retArray = Array ();
		
		$result = $this->dbi->query($stmt);
		
		if ($result->numRows() > 0)
		{
             while($row = $result->fetchRow())
             {
             	foreach($fields as $f)
             	{
					// echo "<p>row: $f: " .  $row->$f."</p>";
			 		$retArray[$row->DisplayOrder]->$f = stripslashes ($row->$f);
				}
             }
		}
         return $retArray;
	  }

	function getCatalogInfo($uid = null)
	{
		$fields   = $this->getCatalogFieldList();

		$fieldStr = implode(', ', $fields);

		$this->setCatalogID($uid);

		if ($this->CatalogId == null)
		{
			return FALSE;
		}
		$stmt = "SELECT $fieldStr FROM $this->catalogTbl " .
				"WHERE ctcatn = $this->CatalogId";

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

	function getCatalogDisplayInfoList($limits=null, $ids=null)
	{
		$fields   = $this->getCatalogAdminDisplayFieldList();

		$fieldStr = implode(', ', $fields);

		 $where = "";

		 if (!empty ($ids))
		 {
		 	$idList = (is_array ($ids))?implode(',', $ids):$ids;
			if (strlen ($where > 0))
			{
		 		$where .= " AND ";
			} else {
				$where .= " WHERE ";
			}
			$where .= "ctcatn IN ($idList) ";
		 }
		 $limit = "";
		 if (!empty ($limits) and empty ($ids))
		 {
		 	$limit = "LIMIT $limits->start,$limits->end";
		 }
		 
		$stmt = "SELECT SQL_CALC_FOUND_ROWS ctcatn, $fieldStr FROM $this->catalogTbl $where " .
				"ORDER BY ctcatn $limit";

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
		 // echo "Num: $this->numEntries<br />";

             while($row = $result->fetchRow())
             {
             	foreach($fields as $f)
             	{
					// echo "<p>row: $f: " .  $row->$f."</p>";
			 		$retArray[$row->ctcatn]->$f = stripslashes ($row->$f);
				}
             }
         }
         return $retArray;
	}

	function getPromotionTypeButtons ($ptype=null)
	{
		if (!$ptype) return null;
		
		$stmt = "SELECT ButtonId FROM $this->promotionButtonTypeTable ".
		        "WHERE PromotionType = $ptype";
		// echo "Query: $stmt<br />";
		
         $retArray = array();
		 
         $result = $this->dbi->query($stmt);
         
         if ($result != null)
         {
             while($row = $result->fetchRow())
             {
                	$retArray[] = $row->ButtonId;
             }
         }

         return $retArray;
	}
	
	function getDisplayRadio ()
	{
		$fields   = $this->getCatalogDisplayTypeList ();
		
         $fieldStr = implode(',', $fields);

         $stmt   = "SELECT Type, DisplayTypeDisplay FROM $this->catalogDisplayTypeTable";

         // echo "$stmt <P>"; 

	     $retArray = array ();
		 
         $result = $this->dbi->query($stmt);
         
         if ($result->numRows() > 0)
         {
             while($row = $result->fetchRow())
             {
			 		$retArray[$row->Type] = $row->DisplayTypeDisplay;
             }
         }
         return $retArray;
	}
	
	function makeUpdateKeyValuePairs($fields = null, $data = null)
	{
		$setValues = array();

		while(list($k, $v) = each($fields))
		{
			if (isset($data->$k))
			{
				if (! (strcmp($v, 'text') or !strcmp($v, 'check')))
				{
					$v = $this->dbi->quote($data->$k);
					// $v = htmlentities ($data->$k);

					$setValues[] = "$k = $v";

				} else if (! strcmp($v, 'phone')) 
				{  // $phone = preg_replace('/\D/', '', $data->$k);
					$v = $this->dbi->quote(preg_replace('/\D/', '', $data->$k));

					$setValues[] = "$k = $v";
				} else if (!strcmp($v, 'now')){
					$valueList[] =  $this->dbi->quote(date ('Y-m-d H'));
				} else {

					$setValues[] = "$k = ".$data->$k;
				}
			}
		}
		return implode(', ', $setValues);
	}

	function updateCatalog($data = null)
	{
		$fieldList = $this->catalogTblFields;

		$keyVal = $this->makeUpdateKeyValuePairs($this->catalogTblFields, $data);

		$stmt = "UPDATE $this->catalogTbl SET $keyVal, ctlmdt = NOW() WHERE ctcatn = $data->ctcatn";
		// echo $stmt . "<p>";
		
		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}
	
	function updateCatalogReplace ($data = null)
	{
		$stmt = "UPDATE $this->catalogTbl SET ctwght=$data->ctwght, ProductType='$data->ProductType', ProductSize='$data->ProductSize' WHERE ctcatn='$data->ctcatn'";
		// echo $stmt . "<p>"; exit;
		
		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}
	function updateCatalogHeader ($data = null)
	{
		$fieldList = $this->catalogTblFields;

		if (! $this->isCatalogHeaderEntry ($data->Description))
		{
			return $this->addCatalog ($data);
		} else {
			$keyVal = $this->makeUpdateKeyValuePairs($this->catalogTblFields, $data);
	
			$stmt = "UPDATE $this->catalogTbl SET $keyVal, ctlmdt = NOW() ".
			        "WHERE Description = '$data->Description'";
			// echo "SQL: $stmt<br />";
			
			$result = $this->dbi->query($stmt);
	
			return $this->getReturnValue($result);
		}
	}
	
	function makeInsertValues ($fields, $data)
	{
		$valueList = array ();
		
		while(list($k, $v) = each($fields))
		{
			if (!strcmp($v, 'text') or !strcmp($v, 'check'))
			{
				$valueList[] = $this->dbi->quote(addslashes($data->$k));
			} else if (!strcmp($v, 'now')){
				$valueList[] =  $this->dbi->quote(date ('Y-m-d H:i:s'));
			} else {
				$valueList[] = $data->$k;
			}
			// echo "$k: [".$data->$k."]<br />";
		}
		return array (array_keys($fields), $valueList);
	}
	
	function addCatalog($data = null)
	{
		$fieldList = $this->catalogTblFields;
		$valueList = array();

		while(list($k, $v) = each($fieldList))
		{
			if (isset ($data->$k))
			{
				if (!strcmp($v, 'text') or !strcmp($v, 'check'))
				{
					$valueList[] = $this->dbi->quote(addslashes($data->$k));
				} else if (!strcmp($v, 'now')){
					$valueList[] =  $this->dbi->quote(date ('Y-m-d H:i:s'));
				} else {
					$valueList[] = $data->$k;
				}
			} else {
				$valueList[] = 0;
			}
			// echo "$k: [".$data->$k."]<br />";
		}
		$fields = implode(',', array_keys($fieldList));
		$values = implode(',', $valueList);

		$stmt = "INSERT INTO $this->catalogTbl ($fields) VALUES($values)";
		// echo "$stmt<br />";

		$result = $this->dbi->query($stmt);

		if ($this->dbi->getErrorCode () == -5)
		{  // duplicate entry
			$this->setCatalogID($this->getCatalogIdByName (addslashes($data->Description)));

			return true;
		}
		$this->CatalogId = $this->dbi->getLastInsertID ();

		return $this->getReturnValue($result);
	}

	function deleteCatalog($uid = null)
	{
		$this->setCatalogID($uid);

		$stmt = "DELETE from $this->user_tbl " .
				"WHERE ctcatn = $this->CatalogId";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}
	function clearCatalog ()
	{
		$stmt = "TRUNCATE TABLE $this->catalogTbl";
		// echo "SQL: $stmt<br />";
		
		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

	function getReturnValue($r = null)
	{
		return ($r == MDB2_OK) ? TRUE : $this->dbi->getError();
	}

	function logActivity($action = null, $name=null)
	{
		if (!isset ($name)) 
		{
		}

		$stmt = "INSERT INTO $this->userActivityLog SET " .
			"ctcatn  = $this->CatalogId, ".
			"CatalogNameId  = $name, ".
			"ActionType = $action, " .
				"ActionTimeStamp = NOW()";

		// echo "$stmt <P>";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}
}
?>
