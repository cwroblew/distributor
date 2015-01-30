<?php

/*
	The Form class handles all the Form tables.  
	This allows for individual forms to be database driven instead of having to have a class for each form. 
*/

class Form
{
	private $formTbl;
	private $formLoopTbl;
	private $formFieldsTbl;
	private $dbi;
	private $formTblFields;
	private $formLoopTblFields;
	private $formFieldsTblFields;
	private $FormId;
	private $isForm;
	
	function __construct($dbi = null, $uid = null, $cid = null)
	{
		global $FORM_TBL, $FORM_LOOP_TBL, $FORM_FIELDS_TBL, $ACTIVITY_LOG_TBL;
		
		$this->formTbl          = $FORM_TBL;
		$this->formLoopTbl    = $FORM_LOOP_TBL;
		$this->formFieldsTbl  = $FORM_FIELDS_TBL;
		$this->dbi              = $dbi;
		
		$this->FormId  =  $this->FormId  = ($uid != null) ? $uid : null;
		
		$this->formTblFields = array(   'Module'           => 'text',
										'ContentTemplate'  => 'text',
										'ThankyouTemplate' => 'text',
										'NumberPages'      => 'number',
										'FieldListVariable'     => 'text',
										'FormFieldListFunction' => 'text',
										'DefaultFormFieldListFunction' => 'text',
										'Name'             => 'text'
										);
		
		$this->formLoopTblFields = array('FieldListVariable'     => 'text',
										 'FormFieldListFunction' => 'text',
										 'DefaultFormFieldListFunction' => 'text'
										);
		
		$this->formFieldsTblFields = array( 'TableId'                => 'number',
											'FormId'                 => 'number',
											'PageId'                 => 'number',
											'FieldName'              => 'text',
											'Required'               => 'text',
											'RequiredWithField'      => 'text',
											'RequiredWithFieldValue' => 'text'
											);
		//echo $this->FormId;
		if (isset($this->FormId))
		{
			$this->isForm = $this->getFormInfo($this->FormId);
		} else {
			$this->isForm = FALSE;
		}
	}
	
	function isForm()
	{
		return $this->isForm;
	}
	
	function getFormID()
	{
		return (isset($this->FormId)) ? $this->FormId : NULL;
	}
	
	function setFormID($uid = null)
	{
		if (! empty($uid))
		{
			$this->FormId = $uid;
		}
		return $this->FormId;
	}
	
	function setFormModule($module = null)
	{
		if (! empty($module))
		{
			$this->Module = $module;
		}
		return $this->Module;
	}
	
	function setFormName($name = null)
	{
		if (! empty($name))
		{
			$this->Name = $name;
		}
		return $this->Name;
	}
	
	function getFormIDByName($name = null)
	{
		if (! $name ) return null;
		
		$stmt   = "SELECT FormId FROM $this->formTbl WHERE Name = '$name'";
		
		$result = $this->dbi->query($stmt);
		
		if ($result != null)
		{
			$row = $result->fetchRow();
			
			return $row->FormId;
		}
		return null;
	}
	
	function getFormFieldList()
	{
		return array('FormId', 'Module', 'ContentTemplate', 'ThankyouTemplate', 'Name', 
					 'FieldListVariable', 'FormFieldListFunction',
					  'DefaultFormFieldListFunction');
	}
	
	function getFormLoopFieldList ()
	{
		return array ('FieldListVariable', 'FormFieldListFunction', 
					  'DefaultFormFieldListFunction');
	}
	
	function getFormFieldsFieldList()
	{
		return array('TableId', 'FormId', 'PageId', 'FieldName', 'Required', 
					 'RequiredWithField', 'RequiredWithFieldValue');
	}
	
	function getFormInfo($uid=null)
	{
		$fields   = $this->getFormFieldList();
		
		$fieldStr = implode(',', $fields);
		
		if ($this->FormId == null)
		{
			$this->setFormID ($uid);
		}
		$stmt = "SELECT $fieldStr FROM $this->formTbl " .
				"WHERE FormId = '$this->FormId'";
		// echo "getFormInfo: $stmt <P>"; 
		
		$result = $this->dbi->query($stmt);
		
		if ($result != null)
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
	
	function getFormLoopInfo()
	{
		$fields   = $this->getFormLoopFieldList();
		
		$fieldStr = implode(',', $fields);
		
		$stmt = "SELECT $fieldStr FROM $this->formLoopTbl " .
				"WHERE FormId = $this->FormId ";
		// echo "$stmt <P>"; 
		
		$result = $this->dbi->query($stmt);
		
		if ($result != null)
		{
			$row = $result->fetchRow();
			
			foreach($fields as $f)
			{
				// echo "<p>row: $f: " .  $row->$f;
				$loop->$f  = $row->$f;
			}
			return $loop;             
		}
		return FALSE;
	}
	
	function getFormList()
	{
		$stmt = "SELECT FormId, Login, FormType, AccessType ".
				"FROM $this->formTbl, $this->form_type_tbl ".
				"WHERE AccessType = FormTypeId ORDER BY Login";
		// echo "SQL: $stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		$retArray = array();
		
		if ($result != null)
		{
			while($row = $result->fetchRow())
			{
				$retArray[$row->FormId] = array ($row->Login, $row->FormType, $row->AccessType);
			}
		}
		return $retArray;
	}
	
	function getFormFieldNamesList($pid = 1)
	{
		$stmt = "SELECT FieldName FROM $this->formFieldsTbl ".
				"WHERE FormId = $this->FormId AND PageId = $pid";
		// echo "SQL: $stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		$retArray = array();
		
		if ($result != null)
		{
			while($row = $result->fetchRow())
			{
				$retArray[] = $row->FieldName;
			}
		}
		return $retArray;
	}
}
?>
