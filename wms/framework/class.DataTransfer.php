<?php

/*
	The DataTransfer class handles  the DataTransfer table which contains information about  
	data transfers. 
*/

class DataTransfer
{
	private $dataTransferTbl;
	private $dbi;
	private $dataTransferTblFields;
	private $isProcessingData;
	
	function __construct($dbi = null)
	{
		global $DATATRANSFER_TBL;
		
		$this->dataTransferTbl  = $DATATRANSFER_TBL;
		$this->dbi              = $dbi;
		$this->isProcessingData = FALSE;
		
		$this->dataTransferTblFields = array ('OrderToAS400'    => 'date',
											  'OrderFromAS400'  => 'date',
											  'DistributorToAS400'    => 'date',
											  'DistributorFromAS400'  => 'date',
											  'Processing' => 'text',
											  'Status' => 'text'
											 );
		
		$this->getDataTransferInfo();
	}
	
	function getDataTransferFieldList()
	{
		return array('OrderToAS400', 'OrderFromAS400', 'DistributorToAS400', 
		             'DistributorFromAS400', 'Processing', 'Status');
	}
	
	function getDataTransferInfo()
	{
		$fields   = $this->getDataTransferFieldList();
		
		$fieldStr = implode(',', $fields);
		
		$stmt = "SELECT $fieldStr FROM $this->dataTransferTbl ";
		// echo "getDataTransferInfo: $stmt <P>"; 
		
		$result = $this->dbi->query($stmt);
		// echo "Result ($stmt): ".print_r ($result,true)."<br />"; exit;
		
		if ($result != null)
		{
			$row = $result->fetchRow();
			
			foreach($fields as $f)
			{
				// echo "<p>row: $f: " .  $row->$f;
				$this->$f  = isset ($row->$f)? $row->$f : null;
			}           
			return TRUE;             
		}		
		return FALSE;
	}
	
	function isProcessingData ()
	{
		return $this->isProcessingData;
	}
	
	function setIsProcessingData ()
	{
		$stmt = "UPDATE $this->dataTransferTbl SET Processing = 'Y'";
		// echo "$stmt<br />\n\r";	   
		
		$result = $this->dbi->query($stmt);

		$this->isProcessingData = $this->getReturnValue($result) == MDB2_OK;
		
		return $this->isProcessingData;
	}
	
	function unsetIsProcessingData ()
	{
		$stmt = "UPDATE $this->dataTransferTbl SET Processing = 'N'";
		//echo "$stmt<br />\n\r";	   
		
		$result = $this->dbi->query($stmt);

		$this->isProcessingData = $this->getReturnValue($result) == MDB2_OK;
		
		return $this->isProcessingData;
	}
	
	function updateDataTransfer ($field, $value)
	{
		if (!$field or !$value) return null;
		
		$stmt = "UPDATE $this->dataTransferTbl ".
			    "SET $field = '$value'";
		//echo "$stmt<br />\n\r";	   
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}
	
	function updateDataTransferStatus ($value = "R")
	{
		$stmt = "UPDATE $this->dataTransferTbl ".
			    "SET Status = '$value'";
		//echo "$stmt<br />\n\r";	   
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}

	function getReturnValue($r = null)
	{
		return ($r == MDB2_OK) ? TRUE : $this->dbi->getError();
		
	}
}
?>
