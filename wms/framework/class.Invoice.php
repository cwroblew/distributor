<?php

class Invoice
{
	private $invoiceHistoryBHTbl;
	private $userActivityLog;
	private $dbi;
	private $InvoiceNumber;
	public $invoiceHistoryBHTblFields;
	private $isInvoice;
	private $SortType;
	
	function __construct ($dbi = null, $uid = null)
	{
		global $INVOICE_HISTORY_BH_TBL;

		$this->invoiceHistoryBHTbl = $INVOICE_HISTORY_BH_TBL;

		$this->dbi = $dbi;

		$this->InvoiceNumber  = ($uid != null) ? $uid : null;

		$this->invoiceHistoryBHTblFields = array('OrderNumber'    => 'text',
												  'InvoiceNumber' => 'number',
												  'InvoiceDate'   => 'date',
												  'NumPallets'    => 'number',
												  'NumKegs'       => 'number',
												  'InvoiceQty'    => 'number',
												  'DistributorNo' => 'number',
												  'CatalogNo'     => 'number',
												  'Price'         => 'text',
												  'Description'   => 'number',
												);

		if (isset($this->InvoiceNumber))
		{
			$this->isInvoice = $this->getBHInvoiceHistoryInfo();
		} else {
			$this->isInvoice = FALSE;
		}
	}

	function isInvoice($oid=null)
	{
		if (!$oid and $this->InvoiceNumber) return $this->isInvoice;
		
		$stmt = "SELECT InvoiceNumber from $this->invoiceHistoryBHTbl ".
		       "WHERE InvoiceNumber = '$oid'";
		// echo "SQL: $stmt<br />\r\n";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isInvoice = true;
		} else {
			$this->isInvoice = false;
		}
		return $this->isInvoice;
	}
	
	function getInvoiceNumber()
	{
		return $this->InvoiceNumber;
	}

	function setInvoiceNumber($uid = null)
	{
		if (! empty($uid))
		{
			$this->InvoiceNumber = $uid;
		}
		return $this->InvoiceNumber;
	}

	function getInvoiceHistoryFieldList()
	{
		return array('InvoiceNumber', 'InvoiceNumber', 'InvoiceDate', 'NumPallets', 'NumKegs',
		             'InvoiceQty', 'DistributorNo', 'CatalogNo', 'Price', 'Description');
	}
	
	function getBHInvoiceHistoryInfo ($uid = null)
	{
		$fields   = $this->getInvoiceHistoryFieldList();

		$fieldStr = implode(', ', $fields);

		$this->setInvoiceNumber($uid);

		if ($this->InvoiceNumber == null)
		{
			return FALSE;
		}
		$stmt = "SELECT $fieldStr FROM $this->invoiceHistoryBHTbl " .
				"WHERE InvoiceNumber = '$this->InvoiceNumber' LIMIT 0,1"; // We need common info to ALL Invoices

		// echo "$stmt <P>"; 

		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$row = $result->fetchRow();

			foreach($fields as $f)
			{
				// echo "<p>row: $f: " .  $row->$f;
				$this->$f  = $row->$f;
			}
			return TRUE;
		}
		return FALSE;
	}

	function getBHInvoiceHistoryList($uid = null)
	{
		$fields   = $this->getInvoiceHistoryFieldList();

		$fieldStr = implode(', ', $fields);

		$this->setInvoiceNumber($uid);

		if ($this->InvoiceNumber == null)
		{
			return FALSE;
		}
		$stmt = "SELECT $fieldStr FROM $this->invoiceHistoryBHTbl ";

		// echo "getBHInvoiceHistoryList $stmt <P>"; exit;

	     $retArray = array ();
		 
		$result = $this->dbi->query($stmt);

         if ($result->numRows() > 0)
         {
             while($row = $result->fetchRow())
             {
             	foreach($fields as $f)
             	{
					// echo "<p>row: $f: " .  $row->$f."</p>";
			 		$retArray[$row->InvoiceNumber]->$f = stripslashes ($row->$f);
				}
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
				if (! (strcmp($v, 'text') or !strcmp($v, 'check')) or !strcmp($v, 'date'))
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

					$setValues[] = "$k = '".$data->$k."'";
				}
			}
		}
		return implode(', ', $setValues);
	}
	// This function is not currently being used for actual update - see Order class
	
	function updateBHInvoiceHistory($data = null)
	{
		return FALSE; // This function is not currently being used for actual update - see Order class
		$fieldList = $this->invoiceHistoryBHTblFields;

		$keyVal = $this->makeUpdateKeyValuePairs($this->invoiceHistoryBHTblFields, $data);

		$where = "";
		$stmt = "UPDATE $this->invoiceHistoryBHTbl SET $keyVal ".
		        "WHERE ($where) AND InvoiceNumber = '$data->InvoiceNumber'";
		// echo $stmt . "\r\n<p>"; exit;
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result2);
	}
	
	function makeInsertValues ($fields, $data)
	{
		$valueList = array ();
		
		while(list($k, $v) = each($fields))
		{
			if (!strcmp($v, 'text') or !strcmp($v, 'check') or !strcmp($v, 'date'))
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
	
	function addBHInvoiceHistory($data = null)
	{
		$fieldList = $this->invoiceHistoryBHTblFields;
		$valueList = array();

		while(list($k, $v) = each($fieldList))
		{
			if (!strcmp($v, 'text') or !strcmp($v, 'check') or !strcmp($v, 'date'))
			{
				$valueList[] = $this->dbi->quote(addslashes($data->$k));
			} else if (!strcmp($v, 'now')){
				$valueList[] =  $this->dbi->quote(date ('Y-m-d H:i:s'));
			} else if (isset ($data->$k)) {
				$valueList[] = $data->$k;
			}
			// echo "$k: [".$data->$k."]<br />";
		}
		$fields = implode(',', array_keys($fieldList));
		$values = implode(',', $valueList);

		$stmt = "INSERT INTO $this->invoiceHistoryBHTbl ($fields) VALUES($values)";
		// echo "$stmt<br />";

		$result = $this->dbi->query($stmt);

/*		if ($this->dbi->getErrorCode () == -5)
		{  // duplicate entry
			$this->setInvoiceNumber($this->getInvoiceNumberByNameId ($data->InvoiceNameId));

			return true;
		}
		$this->InvoiceNumber = $this->dbi->getLastInsertID ();
*/
		return $this->getReturnValue($result);
	}
	// Purging????
		
	function deleteBHInvoiceHistory ($inv)
	{
		return FALSE; // Are we doing this?
		if (!$inv) return false;
		
		$stmt = "DELETE FROM $this->invoiceHistoryBHTbl ".
		        "WHERE InvoiceNumber = $inv";
		// echo "$stmt<br />";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

	function getError ()
	{
		return $this->dbi->getError();
	}

	function getReturnValue($r = null)
	{
		return ($r == MDB2_OK) ? TRUE : FALSE;
	}
	// Need to either use this method or lose it

	function logActivity($action = null, $name=null)
	{
		if (!isset ($name)) 
		{
		}

		$stmt = "INSERT INTO $this->userActivityLog SET " .
			"InvoiceNumber  = $this->InvoiceNumber, ".
			"ActionType = $action, " .
				"ActionTimeStamp = NOW()";

		// echo "$stmt <P>";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}
}
?>
