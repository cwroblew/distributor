<?php

class Order
{
	private $orderTbl;
	private $orderStatusTbl;
	private $orderFormSubjectTbl;
	private $orderWebNumberTbl;
	private $invoiceHistoryBHTbl;
	private $userActivityLog;
	private $dbi;
	private $OrderId;
	public $orderStatusTblFields;
	public $orderTblFields;
	public $orderFormSubjectTblFields;
	private $isOrder;
	private $isNewOrder;
	private $isInvoiceHistory;
	private $userNameMaxChar;
	private $numEntries = 0;
	private $NumberEntries;
	private $SortType;
	
	function __construct ($dbi = null, $uid = null)
	{
		global $ORDER_TBL, $ORDER_COMMENTS_TBL, $ORDER_FORMS_TBL, $ORDER_FORM_RULES_TBL,
			   $ORDER_STATUS_TBL, $ORDER_WEB_ORDER_NUMBER_TBL, $ORDER_BH_INFO_TBL,
			   $ORDER_BH_INVOICE_NUMBER_TBL, $ORDER_BH_ORDER_NUMBER_TBL, 
			   $INVOICE_HISTORY_BH_TBL, $ORDER_ACTIVITY_LOG_TBL, $USER_NAME_MAX_CHAR, 
			   $ORDER_HISTORY_TBL, $ORDER_COMMENTS_HISTORY_TBL;
		global $DISTRIBUTOR_TBL;

		$this->orderTbl          = $ORDER_TBL;
		$this->orderHistoryTbl   = $ORDER_HISTORY_TBL;
		$this->orderFormsTbl     = $ORDER_FORMS_TBL;
		$this->orderFormRulesTbl     = $ORDER_FORM_RULES_TBL;
		$this->orderCommentsTbl  = $ORDER_COMMENTS_TBL;
		$this->orderCommentsHistoryTbl  = $ORDER_COMMENTS_HISTORY_TBL;
		$this->orderStatusTbl    = $ORDER_STATUS_TBL;
		$this->orderWebNumberTbl = $ORDER_WEB_ORDER_NUMBER_TBL;
		$this->orderHistoryTbl = $ORDER_HISTORY_TBL;
		$this->orderBHInfoTbl    = $ORDER_BH_INFO_TBL;
		$this->orderBHOrderNumberTbl = $ORDER_BH_ORDER_NUMBER_TBL;
		$this->orderBHInvoiceNumberTbl = $ORDER_BH_INVOICE_NUMBER_TBL;
		$this->invoiceHistoryBHTbl   = $INVOICE_HISTORY_BH_TBL;
		$this->userActivityLog = $ORDER_ACTIVITY_LOG_TBL;
		$this->dbi               = $dbi;

         $this->distributorTable      = $DISTRIBUTOR_TBL;

		$this->OrderId  = ($uid != null) ? $uid : null;

		$this->orderHistoryTblFields = $this->orderTblFields = 
								array('orstat'   => 'text',
		                              'orordn'   => 'text',
									  'orwebn'   => 'text',
									  'ordisn'   => 'text',
										'ordrop'   => 'text',
										'orordt'   => 'date',
										'orpono'   => 'text',
										'orrqdt'   => 'date',
										'orcatn'   => 'number',
										'oroqty'   => 'number',
										'orsqty'   => 'number',
										'ItemPrice' => 'number',
										'orshdt'   => 'date',
										'orlmdt'  => 'now' //,
										//'Created'  => 'now'
									);
		$this->orderHistoryTblFields['OrderPurgeDate'] = 'now';

		$this->orderStatusTblFields = array('OrderStatus'   => 'text',
		                              'StatusDescription'   => 'text'
										   );

		$this->orderCommentsHistoryTblFields = $this->orderCommentsTblFields = 
									array('ocordn' => 'text',
											'ocwebn' => 'text',
											'occom1'   => 'text',
											'occom2'   => 'text',
											'occhg1'   => 'text',
											'occhg2'   => 'text',
											'occmdt'   => 'now'
									);
		$this->orderCommentsHistoryTblFields['OrderCommentPurgeDate'] = 'now';
		$this->orderFormsTblFields = array('ctcatn'       => 'text',
										'Description'  => 'text',
										'qtypal'       => 'number',
										'MinQty'       => 'number',
										'DisplayOrder' => 'number',
										'Special'  => 'text',
										'OrderFormCode' => 'text'
										);
		$this->orderInfoTblFields = array('orordn'    => 'text',
		                                  'InvoiceNumber' => 'text',
										  'InvoiceDate' => 'now',
										  'NumPallets'  => 'number',
										  'NumKegs'     => 'number'
										   );

		$this->invoiceHistoryBHTblFields = array('OrderNumber'          => 'text',
												  'InvoiceNumber'   => 'number',
												  'InvoiceDate'     => 'date',
												  'NumPallets'      => 'number',
													'NumKegs'       => 'number',
													'InvoiceQty'        => 'number',
													'DistributorNo' => 'number',
													'CatalogNo'     => 'number',
													'Price'         => 'text',
													'Description'   => 'number',
												);

		if (isset($this->OrderId))
		{
			$this->isOrder = $this->getOrderInfo();
		} else {
			$this->isOrder = FALSE;
		}
	}

	function isOrder($oid=null)
	{
		if (!$oid and $this->OrderId) return $this->isOrder;
		
		$stmt = "SELECT orordn from $this->orderTbl ".
		       "WHERE orordn = '$oid'";
		// echo "SQL: $stmt<br />\r\n";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isOrder = true;
		} else {
			$this->isOrder = false;
		}
		return $this->isOrder;
	}
	
	function isOrderFormEntry ($cid = null)
	{
		if (!$cid) return false;
		
		$stmt = "SELECT ctcatn from $this->orderFormsTbl ".
		        "WHERE ctcatn = '$cid'";
		// echo "isOrderFormEntry: $stmt<br />";

		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isOrderFormEntry = true;
		} else {
			$this->isOrderFormEntry = false;
		}
		return $this->isOrderFormEntry;
	}

	function isOrderEntry($oid=null, $cid=null, $wid = null)
	{
		if ((!$oid and !$wid) or !$cid) return $this->isOrder;
		
		$where = "";
		if ($oid) $where .= "orordn = '$oid' ";
		if ($wid)
		{
			if (strlen ($where) > 0) $where .= "OR ";
			$where .= "orwebn = '$wid' ";
		}		
		$stmt = "SELECT orcatn from $this->orderTbl ".
		       "WHERE ($where) AND orcatn = '$cid'";
		// echo "SQL: $stmt<br />\r\n"; exit;
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isOrderEntry = true;
		} else {
			$this->isOrderEntry = false;
		}
		return $this->isOrderEntry;
	}

	function isOrderComment ($oid=null, $cid=null, $wid = null)
	{
		if ((!$oid and !$wid) or !$cid) return $this->isOrder;
		
		if ($oid) $where = "orordn = '$oid'";
		else $where = "orwebn = '$wid'";
		
		$stmt = "SELECT orordn from $this->orderCommentsTbl ".
		       "WHERE $where AND orcatn = $cid";
		// echo "SQL: $stmt<br />\r\n";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isOrderEntry = true;
		} else {
			$this->isOrderEntry = false;
		}
		return $this->isOrderEntry;
	}

	function isOrderFormHeaderEntry ($desc = null)
	{
		// if (!$desc) return $this->isOrderEntry;
		$cleanDescription = addslashes($desc);
		$stmt = "SELECT ctcatn from $this->orderFormsTbl ".
		        "WHERE Description = '$cleanDescription'";
		// echo "SQL: $stmt<br />\r\n";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isOrderFormHeaderEntry = true;
		} else {
			$this->isOrderFormHeaderEntry = false;
		}
		return $this->isOrderFormHeaderEntry;
	}

	function isInvoiceHistory($inv)
	{
		if (!$inv) return $this->isInvoiceHistory;
		
		$stmt = "SELECT InvoiceNumber from $this->invoiceHistoryBHTbl ".
		       "WHERE InvoiceNumber = '$inv'";
		// echo "SQL: $stmt<br />\r\n"; exit;
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isInvoiceHistory = true;
		} else {
			$this->isInvoiceHistory = false;
		}
		return $this->isInvoiceHistory;
	}

	function getInvoice ()
	{
		$stmt = "SELECT InvoiceNumber FROM $this->orderBHInfoTbl ".
				"WHERE orordn = '$this->orordn'";
		// echo "getInvoice $stmt<br />";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$row = $result->fetchRow();
			$this->InvoiceNumber = $row->InvoiceNumber;
			
			return $row->InvoiceNumber;
		} else {
			return 0;
		}
	}

	function isInvoice ()
	{
		if (!isset ($this->orordn)) return false; // new order
		
		$stmt = "SELECT InvoiceNumber FROM $this->orderBHInfoTbl ".
				"WHERE orordn = '$this->orordn'";
		// echo "isInvoice $stmt<br />";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$this->isInvoice = true;
		} else {
			$this->isInvoice = false;
		}
		return $this->isInvoice;
	}

	static function checkOrderSuspended ($warning=false)
	{
		global $SUSPEND_WARNING, $SUSPEND_STARTTIME, $SUSPEND_ENDTIME;
		
		$curTime = strtotime (date_format (date_create("now", timezone_open('America/Chicago')), 'H:i:s'));
		// $curTime = ($curTime < 12 * 60 * 60)? $curTime + 24 * 60 * 60: $curTime;
		$suspendTime = strtotime ($SUSPEND_STARTTIME);
		$minutes=60;
		if ($warning) $suspendTime -=  $SUSPEND_WARNING * $minutes;
		$endTime = strtotime ($SUSPEND_ENDTIME);
		// $endTime = ($endTime < 12 * 60 * 60)? $endTime + 24 * 60 * 60: $endTime;
		// echo "Cur: $curTime<br />Sus: $suspendTime<br />End: $endTime<br />"; exit;
		if ($curTime < $endTime or $curTime > $suspendTime) $suspend = TRUE;
		else $suspend = FALSE;
		return $suspend;
	}
	
	function getCatalogIdByName ($name = null)
	{
		global $CATALOG_TBL;
		$stmt = "SELECT ctcatn FROM $CATALOG_TBL " .
				"WHERE Description = '$name'";
		// echo "getCatalogIdByName: $stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		if($result->numRows() > 0)
		{
			$row = $result->fetchRow();
			
			return $row->ctcatn;
		
		} else {
		
			return 0;
		}
	}
	
	function getOrderItemDescription ($cid = null)
	{
		if (!$cid) return "";
		
		$stmt = "SELECT Description from $this->orderFormsTbl ".
		        "WHERE ctcatn = '$cid'";
		// echo "getOrderItemDescription: $stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		if($result->numRows() > 0)
		{
			$row = $result->fetchRow();
			
			return $row->Description;
		
		} else {
		
			return 0;
		}
	}
	
	function getOrderID()
	{
		return $this->OrderId;
	}

	function setOrderID($uid = null)
	{
		if (! empty($uid))
		{
			$this->OrderId = $uid;
		}
		return $this->OrderId;
	}

	function getorstat()
	{
		return $this->orstat;
	}

	function getUID()
	{
		return (isset($this->OrderId)) ? $this->OrderId : NULL;
	}

	function getName()
	{
		return (isset($this->Name)) ?  $this->Name : NULL;
	}

	function getNumEntries ()
	{
		return $this->NumberEntries;
	}
	
	// This getter function performs an update too because we want to ensure that the BK Invoice Number is unique
	function getMaxBHInvoiceNumber ()
	{
        $stmt = "SET AUTOCOMMIT=0";
		$result = $this->dbi->query($stmt);

        $stmt = "BEGIN";
		$result = $this->dbi->query($stmt);

		$stmt = "SELECT BH_invoice_number FROM $this->orderBHInvoiceNumberTbl";
		// echo "$stmt<br />";
		
		$result = $this->dbi->query($stmt);

		if ($row = $result->fetchRow())
		{
			$invoiceNumber = $row->BH_invoice_number + 1;
			$this->setInvoiceNumber ($invoiceNumber);
			
			if ($invoiceNumber > 99999) $invoiceNumber = "10001";
			if ($this->updateMaxBHInvoice ($invoiceNumber))
			{
			    $stmt = "COMMIT";
				$result = $this->dbi->query($stmt);
			} else {
		        $stmt = "ROLLBACK";
				$result = $this->dbi->query($stmt);
			}
		} else {
			$invoiceNumber = "10001";
			$stmt = "ROLLBACK";
			$result = $this->dbi->query($stmt);
		}
		return $invoiceNumber;
	}
	
	function setInvoiceNumber ($invno)
	{
		if (! empty($invno))
		{
			$this->InvoiceNumber = $invno;
		}
		return $this->InvoiceNumber;
	}
	
	function getMaxWebOrder ()
	{
        $stmt = "SET AUTOCOMMIT=0";
		$result = $this->dbi->query($stmt);

        $stmt = "BEGIN";
		$result = $this->dbi->query($stmt);

		$stmt = "SELECT WebOrderNumber FROM $this->orderWebNumberTbl";
		// echo "$stmt<br />";
		
		$result = $this->dbi->query($stmt);

		if ($row = $result->fetchRow())
		{
			$webId = $row->WebOrderNumber + 1;
			if ($webId > 9999) $webId = "0001";
			if ($this->updateMaxWebOrder ($webId))
			{
			    $stmt = "COMMIT";
				$result = $this->dbi->query($stmt);
			} else {
		        $stmt = "ROLLBACK";
				$result = $this->dbi->query($stmt);
			}
		} else {
			$webId = "0001";
			$stmt = "ROLLBACK";
			$result = $this->dbi->query($stmt);
		}
		return str_pad ($webId, 4, "0", STR_PAD_LEFT);
	}
	
	function getMaxBHOrder ()
	{
        $stmt = "SET AUTOCOMMIT=0";
		$result = $this->dbi->query($stmt);

        $stmt = "BEGIN";
		$result = $this->dbi->query($stmt);

		$stmt = "SELECT BHOrderNumber FROM $this->orderBHOrderNumberTbl";
		// echo "$stmt<br />";
		
		$result = $this->dbi->query($stmt);

		if ($row = $result->fetchRow())
		{
			$webId = $row->BHOrderNumber + 1;
			if ($webId > 9999) $webId = "0001";
			if ($this->updateMaxBHOrder ($webId))
			{
			    $stmt = "COMMIT";
				$result = $this->dbi->query($stmt);
			} else {
		        $stmt = "ROLLBACK";
				$result = $this->dbi->query($stmt);
			}
		} else {
			$webId = "0001";
			$stmt = "ROLLBACK";
			$result = $this->dbi->query($stmt);
		}
		return str_pad ($webId, 4, "0", STR_PAD_LEFT);
	}
	
	function getOrderFormLineDescription ($ctcatn)
	{
		if (!$ctcatn) return null;
		
		$stmt   = "SELECT Description FROM $this->orderFormsTbl WHERE ctcatn = '$ctcatn'";
		// echo "$stmt<br /><br />";
		
		$result = $this->dbi->query($stmt);
		
		if ($result != null)
		{
			$row = $result->fetchRow();
			
			return $row->Description;
		}
		return null;
	}
	function getOrderTypeFieldList()
	{
		return array('OrderType', 'OrderTypeDescription', 'RequireComment');
	}

	function getOrderFieldList()
	{
		return array('orstat', 'orordn', 'ordisn', 'orwebn', 'ordrop', 'orordt', 'orcatn',
		             'orpono', 'orrqdt', 'oroqty', 'orsqty', 'ItemPrice', 'orshdt', 'orlmdt');
	}
	
	function getOrderInfoFieldList ()
	{
		return array('orordn', 'orwebn', 'ordisn', 'ordrop', 'orordt', 'orpono', 'orrqdt', 
		             'orshdt');
	}
	
	function getDistributorOrderInfoFieldList ()
	{
		return array('orordn', 'ordisn', 'orwebn', 'ordrop', 'orordt',  'orpono', 'orrqdt',
		             'orshdt');
	}
	
	function getOrderLineFieldList ()
	{
		return array('orcatn', 'oroqty');
	}
	
	function getInvoiceFieldList ()
	{
		return array ('orordn', 'InvoiceNumber', 'InvoiceDate', 'NumPallets', 'NumKegs');
	}
	
	function getInvoiceHistoryFieldList()
	{
		return array('OrderNumber', 'InvoiceNumber', 'InvoiceDate', 'NumPallets', 'NumKegs',
		             'InvoiceQty', 'DistributorNo', 'CatalogNo', 'Price', 'Description');
	}
	
	function getOrderFormFieldList()
	{
		return array('orstat', 'orcatn', 'oroqty', 'MinQty');
	}
	
	function getOrderFormSubjectFieldList()
	{
		return array('OrderId', 'OrderTypeId');
	}

	function getInoviceFormFieldList()
	{
		return array('oroqty', 'ProductSize', 'orcatn', 'Description', 'Price');
	}
	
	function getOrderDisplayFieldList ()
	{
		return array('orordn', 'orordt', 'orrqdt', 'orpono', 'StatusDescription');
	}

	function getOrderDisplayColumnList ()
	{
		return array('Order #', 'Order Date', 'Ship Date', 'P.O. #', 'Status');
	}

	function getOrderBHDisplayFieldList ()
	{
		return array('orordn', 'ordisn', 'orordt', 'orrqdt', 'orpono', 'StatusDescription');
	}

 	function getOrderBHDisplayColumnList ()
	{
		return array('Order #', 'Distributor', 'Order Date', 'Ship Date', 'P.O. #', 'Status');
	}

     function getOrderDisplayTypeList()
      {
         return array('DisplayType', 'Type', 'DisplayTypeDisplay');
      }
	
	public function getOrderCSVFieldList ()
	{
		return array ('orstat', 'orwebn', 'orordn', 'ordisn', 'ordrop', 'orordt', 'orpono',
		              'orrqdt', 'orcatn', 'oroqty', 'orsqty', 'orshdt', 'orlmdt');
	}
	public function getOrderCommentsCSVFieldList ()
	{
		return array ('ocwebn', 'ocordn', 'occom1', 'occom2', 'occhg1', 'occhg2', 'occmdt');
	}
	
	public function getOrderCommentFieldList ()
	{
		return array ('occom1', 'occom2', 'occhg1', 'occhg2', 'occmdt');
	}

	public function getInvoiceFormFieldList ()
	{
		return array ('occom1', 'occom2', 'occhg1', 'occhg2', 'occmdt');
	}

 	function getOrderFormTemplateFormFieldList()
	{
		return array('ctcatn', 'Description', 'qtypal', 'MinQty', 'Special', 'DisplayOrder', 
		             'OrderFormCode');
	}

	function getOrderFormTemplateFieldList()
	{
		return array('ctcatn' => 'Catalog #', 'Description' => 'Description', 
		             'ctwght' => 'Weight', 'qtypal' => 'Qty/pallet', 'MinQty' => 'Min qty',
					 'Special' => 'Contact Sales');
	}
	function getFormLoopInfo()
	{
		$loop->FieldListVariable = "orderTblFields";
		$loop->FormFieldListFunction = "getOrderFieldList";
		$loop->DefaultFormFieldListFunction = null;
	}
	
	  function getOrderList ($cfl=null, $uid = null, $dateLimit = null, $sortby)
	  {
	  	if (!$cfl or !$uid) return null;
		
		$fields   = $this->$cfl();

		$fieldStr = implode(', ', $fields);
		
		$where = "";
		if ($dateLimit)
		{
			$where = " AND DATEDIFF(NOW() ,orrqdt) <= $dateLimit";
		}
		if (!isset ($sortby))
		{
			$sortby = "";
		} else {
			$sortby .= ",";
		}
		$stmt = "SELECT $fieldStr FROM $this->orderTbl,  $this->orderStatusTbl ".
		        "WHERE ordisn = '$uid' ".
				"AND orstat in (SELECT OrderStatus FROM $this->orderStatusTbl ".
				               "WHERE OrderFormCode = 'A') ".
				"AND orstat not in ('D', 'N', 'X') AND orstat = OrderStatus $where ".
		        "ORDER BY $sortby orrqdt  ASC";

		// echo "$stmt <P>"; exit;

		$retArray = Array ();
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
             while($row = $result->fetchRow())
             {
             	foreach($fields as $f)
             	{
					// echo "<p>row: $f: " .  $row->$f."</p>";
			 			$retArray[$row->orordn]->$f = stripslashes ($row->$f);
				}
             }
		}
         return $retArray;
	  }

	  function getBHOrderList ($cfl=null, $uid = null, $dateLimit = null, $sortby = null)
	  {
	  	if (!$cfl or !$uid) return null;
		
		$fields   = $this->$cfl();

		$fieldStr = implode(', ', $fields);
		
		$where = "";
		if ($dateLimit)
		{
			global $UNPROCESSED_BYAS400_INV_STATUS, $UNPROCESSED_DELETED_ORDER_STATUS;
			
			// Display all orders until they're invoiced
			// After invoicing, only display until $dateLimit
			
			$where = " AND (orstat NOT IN ('$UNPROCESSED_DELETED_ORDER_STATUS', '$UNPROCESSED_BYAS400_INV_STATUS') AND DATEDIFF(NOW() ,orrqdt) <= (2 * $dateLimit) OR orstat IN ('$UNPROCESSED_DELETED_ORDER_STATUS', '$UNPROCESSED_BYAS400_INV_STATUS') AND DATEDIFF(NOW() ,orrqdt) <= $dateLimit) ";
		}
		if (!isset ($sortby))
		{
			$sortby = "";
		} else {
			$sortby .= ",";
		}
		$stmt = "SELECT $fieldStr FROM $this->orderTbl,  $this->orderStatusTbl ".
		        "WHERE (ordisn = '$uid' ".
				"OR ordisn in (SELECT dsdisn FROM $this->distributorTable ".
				              "WHERE DIstParentNumber = '$uid')) ".
				"AND orstat in (SELECT OrderStatus FROM $this->orderStatusTbl ".
				               "WHERE OrderFormCode = 'D') ".
				"AND orstat = OrderStatus $where ".
		        "ORDER BY $sortby orrqdt  ASC";

		// echo "getBHOrderList: $stmt <P>"; exit;

		$retArray = Array ();
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
             while($row = $result->fetchRow())
             {
             	foreach($fields as $f)
             	{
					// echo "<p>row: $f: " .  $row->$f."</p>";
			 			$retArray[$row->orordn]->$f = stripslashes ($row->$f);
				}
             }
		}
         return $retArray;
	  }

	function getOrderInfo($uid = null)
	{
		$fields   = $this->getOrderFieldList();

		$fieldStr = implode(', ', $fields);

		$this->setOrderID($uid);

		if ($this->OrderId == null)
		{
			return FALSE;
		}
		$stmt = "SELECT $fieldStr FROM $this->orderTbl " .
				"WHERE orordn = '$this->OrderId' LIMIT 0,1"; // We need common info to ALL orders

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

	function getDistributorOrderInfo($uid = null)
	{
		$fields   = $this->getDistributorOrderInfoFieldList();

		$fieldStr = implode(', ', $fields);

		$this->setOrderID($uid);

		if ($this->OrderId == null)
		{
			return FALSE;
		}
		$stmt = "SELECT DISTINCT $fieldStr FROM $this->orderTbl " .
				"WHERE orordn = '$this->OrderId'";

		// echo "$stmt <P>"; 

		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$row = $result->fetchRow();

			foreach($fields as $f)
			{
				// echo "<p>row: $f: " .  $row->$f;
				$curOrder->$f  = $row->$f;
			}
			return $curOrder;
		}
		return null;
	}
	function getMainOrderInfo($uid = null)
	{
		$fields   = $this->getOrderInfoFieldList();

		$fieldStr = implode(', ', $fields);

		$this->setOrderID($uid);

		if ($this->OrderId == null)
		{
			return FALSE;
		}
		$stmt = "SELECT DISTINCT $fieldStr FROM $this->orderTbl " .
				"WHERE orordn = '$this->OrderId'";

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
			return true;
		}
		return null;
	}

	function getInvoiceInfo ()
	{
		$fields   = $this->getInvoiceFieldList ();

		$fieldStr = implode(', ', $fields);

		$stmt = "SELECT $fieldStr FROM $this->orderBHInfoTbl ".
				"WHERE orordn = '$this->orordn'";
		// echo "getInvoiceInfo $stmt<br />";
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$row = $result->fetchRow();

			foreach($fields as $f)
			{
				// echo "<p>row: $f: " .  $row->$f;
				$this->$f  = $row->$f;
			}
			return true;
		}
		return null;
	}

	function getOrderStatus($status = null)
	{
		if (!$status) return FALSE;

		$stmt = "SELECT StatusDescription FROM $this->orderStatusTbl " .
				"WHERE OrderStatus = '$status'";

		// echo "$stmt <P>"; 

		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$row = $result->fetchRow();

			return $row->OrderStatus;
		}
		return FALSE;
	}
	// Need to verify this function belongs here - duplicated from class.Catalog.php

	  function getCatalogList ($cfl=null, $oftype='A', $distState='WI')
	  {
		  global $BH_PRODUCT_TYPES;
	  	if (!$cfl) return null;
		
		$fields   = $this->$cfl();

		$fieldStr = implode(', ', $fields);

		// get listing based on whether 'Beer' only, 'Cider' only, or all
		switch ($oftype)
		{
			case 'A':
				$orderCodes = "('B', 'C')";
				break; // do nothing right now
			case 'B':
				$orderCodes = "('B')";
				break;
			case 'C':
				$orderCodes = "('C')";
				break;
			case 'D':
				$orderCodes = "('D')";
				break;
		}
		if (!isset ($productTypes))
		{
			$productTypes = ''; // $oftype = 'A'
		}
		$stmt = "SELECT $fieldStr FROM $this->orderFormsTbl ".
				"WHERE DisplayOrder != 0 AND OrderFormCode in $orderCodes ".
				"AND (ctcatn NOT IN (SELECT DISTINCT CatalogNumber FROM $this->orderFormRulesTbl) ".
				"OR ctcatn IN (SELECT CatalogNumber FROM $this->orderFormRulesTbl ".
				              "WHERE RuleType = 1 AND RuleValue = '".$distState."' ".
							  "AND Allow = 'Y')) ".
				"ORDER BY DisplayOrder";

		// echo "$stmt <P>";

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

		function getBHInvoiceInfo ($inv)
		{
			// This information is to be stored into the invoice history table
			
			if (!$inv) return false;
			
			global $DISTRIBUTOR_PRICE_TBL, $UNPROCESSED_BYAS400_INV_STATUS;
			
			$stmt = "SELECT inv.orordn, inv.InvoiceNumber, inv.InvoiceDate, inv.NumPallets, ".
			        "inv.NumKegs, ord.ordisn, ord.oroqty, ord.ItemPrice as Price, frm.Description ".
					"FROM $this->orderTbl as ord, $this->orderBHInfoTbl as inv, ".
					"$this->orderFormsTbl as frm ".
					"WHERE ord.orordn = inv.orordn ".
					"AND ord.orordn != '' ".
					"AND ord.ordisn = prc.DistributorNo ".
					"AND ord.orcatn = prc.CatalogNo ".
					"AND ord.orcatn = frm.ctcatn ".
					"AND ord.orstat = '$UNPROCESSED_BYAS400_INV_STATUS' ".
					"AND inv.InvoiceNumber = $inv ";
			// echo "getBHInvoiceInfo - $stmt<br />";
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
		$stmt = "SELECT $fieldStr FROM $this->orderFormsTbl ".
				"WHERE DisplayOrder != 0 AND OrderFormCode = '$oftype' ".
				"ORDER BY DisplayOrder";

		// echo "$stmt <P>";

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

	function getOrderLineInfo($uid = null, $cid)
	{
		$fields   = $this->getOrderLineFieldList();

		$fieldStr = implode(', ', $fields);

		$this->setOrderID($uid);

		foreach($fields as $f)
		{
			$this->$f  = null;  // clear field
		}
		if ($this->OrderId == null)
		{
			return FALSE;
		}
		$stmt = "SELECT $fieldStr FROM $this->orderTbl " .
				"WHERE orordn = '$this->OrderId' AND orcatn = '$cid'";

		// echo "$stmt <P>"; exit;

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

	function getOrderLineStatus($uid = null, $cid)
	{
		if (!$uid or !$cid) return FALSE;
		$stmt = "SELECT orstat FROM $this->orderTbl " .
				"WHERE orwebn = '$uid' AND orcatn = '$cid'";

		// echo "$stmt <P>";

		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
			$row = $result->fetchRow();

			return $row->orstat;
		}
		return null;
	}

	function getOrderInfoList($uid = null, $wherefield="orordn")
	{
		$fields   = $this->getOrderFieldList();

		$fieldStr = implode(', ', $fields);

		$this->setOrderID($uid);

		if ($this->OrderId == null)
		{
			return FALSE;
		}
		$stmt = "SELECT orcatn, $fieldStr FROM $this->orderTbl " .
				"WHERE $wherefield = '$uid' ";

		// echo "getOrderInfoList $stmt <P>"; exit;

	     $retArray = array ();
		 
		$result = $this->dbi->query($stmt);

         if ($result->numRows() > 0)
         {
             while($row = $result->fetchRow())
             {
             	foreach($fields as $f)
             	{
					// echo "<p>row: $f: " .  $row->$f."</p>";
			 		$retArray[$row->orcatn]->$f = stripslashes ($row->$f);
				}
             }
		}
         return $retArray;
	}
	
	function getCSVOrderList($las400)
	{
		global $NEW_ORDER_STATUS, $ACTIVE_ORDER_STATUS, $DELETED_ORDER_STATUS, 
		      $CHANGED_ORDER_STATUS, $SHIPPED_ORDER_STATUS, $PROCESSED_DELETED_STATUS; 
		// Downloading only New, Deleted or Changed	Orders
		$fields   = $this->getOrderCSVFieldList();
		
		$fieldStr = implode(', ', $fields);
		
		$stmt = "SELECT $fieldStr FROM $this->orderTbl " .
				"WHERE orstat in ('$NEW_ORDER_STATUS', '$CHANGED_ORDER_STATUS', '$DELETED_ORDER_STATUS') ".
				"ORDER BY orordn, orwebn";
		
		// echo "$stmt <P>"; 
		
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
					if ($this->orderTblFields [$f] == 'date' or $this->orderTblFields [$f] == 'now')  // special date formating for AS400
					{
						if ($row->$f == "0000-00-00")
						{
							$retArray[$i][$f] = 0;
						} else {
							$retArray[$i][$f] = date_format (date_create ($row->$f), 'Ymd');
						}
					} else {
						$retArray[$i][$f] = stripslashes ($row->$f);
					}
				}
			$i++;
			}
		}
		return $retArray;
	}
	
	function getCSVOrderCommentsList($las400)
	{
		global $NEW_ORDER_STATUS, $ACTIVE_ORDER_STATUS, $DELETED_ORDER_STATUS, 
		      $CHANGED_ORDER_STATUS, $SHIPPED_ORDER_STATUS, $PROCESSED_DELETED_STATUS; 
		// Downloading only New, Deleted or Changed	Orders

		$fields   = $this->getOrderCommentsCSVFieldList();
		
		$fieldStr = implode(', ', $fields);
		
		$stmt = "SELECT distinct $fieldStr FROM $this->orderTbl, $this->orderCommentsTbl " .
				"WHERE orstat in ('$NEW_ORDER_STATUS', '$CHANGED_ORDER_STATUS', '$DELETED_ORDER_STATUS') AND orordn = ocordn ".
				"ORDER BY ocordn, ocwebn";
		
		// echo "$stmt <P>\n"; 
		
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
					if ($this->orderCommentsTblFields [$f] == 'date' or $this->orderCommentsTblFields [$f] == 'now')  // special date formating for AS400
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
	
	function getOrderCatlogList ()
	{
		$sql = "SELECT orcatn FROM $this->orderTbl WHERE ";
	}

	function getOrderDisplayInfoList($limits=null, $ids=null)
	{
		$fields   = $this->getOrderAdminDisplayFieldList();

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
			$where .= "OrderId IN ($idList) ";
		 }
		 $limit = "";
		 if (!empty ($limits) and empty ($ids))
		 {
		 	$limit = "LIMIT $limits->start,$limits->end";
		 }
		$stmt = "SELECT SQL_CALC_FOUND_ROWS OrderId, $fieldStr FROM $this->orderTbl $where " .
				"ORDER BY OrderId $limit";

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
			 		$retArray[$row->OrderId]->$f = stripslashes ($row->$f);
				}
             }
         }
         return $retArray;
	}
	
	function getOrderComments ($oid)
	{
		if (!$oid) return null;
		
		$fields   = $this->getOrderCommentFieldList();
		
		$fieldStr = implode(', ', $fields);
		
		$stmt = "SELECT $fieldStr FROM $this->orderCommentsTbl " .
				"WHERE ocordn = '$oid'";
		
		// echo "$stmt <P>";
		
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
					if ($this->orderCommentsTblFields [$f] == "date" or $this->orderCommentsTblFields [$f] == 'now')
					{
						$this->$f = $this->dbi->formatDate($row->$f);
					} else {
			 			$this->$f = stripslashes ($row->$f);
					}
				}
				$i++;
			}
			return true;
		}
		return false;
	}

	function getOrderCommentsforHistory ($oid=null, $wid=null)
	{
		if (!$oid and !$wid) return null;
		
		$fields   = $this->getOrderCommentFieldList();
		
		$fieldStr = implode(', ', $fields);
		
		// don't care about Web Order Number, since these are all orders from AS/400
		
		$stmt = "SELECT $fieldStr FROM $this->orderCommentsTbl " .
				"WHERE ocordn = '$oid' ".
				"AND ocordn NOT IN (SELECT orordn FROM $this->orderTbl) "; 
		
		// echo "getOrderCommentsforHistory: $stmt <P>";
		
		$result = $this->dbi->query($stmt);
		
		if ($result->numRows() > 0)
		{
		// echo "$stmt <P>";
			$i = 0;
			while($row = $result->fetchRow())
			{
				foreach($fields as $f)
				{
					// echo "<p>row: $f: " .  $row->$f."</p>";
			 		$comments->$f = stripslashes ($row->$f);
				}
				$i++;
			}
			return $comments;
		}
		return false;
	}

		function getTotal ()
		{
			$stmt = "SELECT count(OrderId) AS TotalCount FROM $this->orderTbl ";
			
			$result = $this->dbi->query($stmt);
			
			if ($result != null)
			{
				$row = $result->fetchRow();
				
				return $row->TotalCount;
			}
			return null;
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
		$fields   = $this->getOrderDisplayTypeList ();
		
         $fieldStr = implode(',', $fields);

         $stmt   = "SELECT Type, DisplayTypeDisplay FROM $this->orderDisplayTypeTable";

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
	//
	// Purge new web orders not assigned an AS/400 order number within $PURGE_NOAS400_DAYS (conf.php)
	// Or order shipped w/o line item
	// And AS/400 is no longer downloading data
	//
	function countPurgeUnprocessedLineItems ($cfl=null, $pdy=null)
	{
		global $NEW_ORDER_STATUS, $CHANGED_ORDER_STATUS, $DELETED_ORDER_STATUS, 
		       $ACTIVE_ORDER_STATUS, $PURGE_NOAS400_DAYS;
		
	  	if (!$cfl or !$pdy) return null;
		
		$fields   = $this->$cfl();

		$fieldStr = implode(', ', $fields);
		
/*		$stmt = "SELECT $fieldStr FROM $this->orderTbl ".
		        "WHERE (orstat in ('$NEW_ORDER_STATUS', '$CHANGED_ORDER_STATUS', '$DELETED_ORDER_STATUS') ".
				"AND DATEDIFF(now(),orordt) > $PURGE_NOAS400_DAYS) ".
				"OR (orstat = '$ACTIVE_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orordt) > $PURGE_NOAS400_DAYS ".
				"AND DATEDIFF(now(),orrqdt) > 0) ".
		        "ORDER BY orrqdt  ASC";
*/
		$stmt = "SELECT COUNT(orordn) as PurgeCount FROM $this->orderTbl ".
		        "WHERE ((orstat in ('$NEW_ORDER_STATUS', '$CHANGED_ORDER_STATUS', '$DELETED_ORDER_STATUS') ".
				"AND DATEDIFF(now(),orordt) > $pdy) ".
/* not performing this test at this time - don't know how acurate the request date is
				"OR (orstat = '$ACTIVE_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orrqdt) > $pdy ".
				"AND orshdt = '0000-00-00' AND orsqty = 0) ".
*/
				"OR (orstat = '$ACTIVE_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orshdt) > $pdy ".
				"AND orsqty = 0)) ".
				"AND DATEDIFF(now(),orlmdt) > $pdy ".
		        "ORDER BY orrqdt  ASC";

		// echo "$stmt <P>"; // exit;

		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
				$row = $result->fetchRow();
				
				return $row->PurgeCount;
		}
         return false;
	}
	
	//
	// Purge all orders older that shipped more than $PURGE_SHIP_DAYS (conf.php)
	// or new web orders not assigned an AS/400 order number within $PURGE_NOAS400_DAYS (conf.php) - Not done here any more.
	// And older than the $PURGE_NOAS400_DAYS more than the last modified date 
	//  (Make sure the AS/400 is done with the record)
	//
	function countPurgeOrders ($cfl=null)
	{
		global $SHIPPED_ORDER_STATUS, $NEW_ORDER_STATUS, $PURGE_SHIP_DAYS, $PURGE_NOAS400_DAYS;
		
	  	if (!$cfl) return null;
		
		$fields   = $this->$cfl();

		$fieldStr = implode(', ', $fields);
		
		$stmt = "SELECT COUNT(orordn) as PurgeCount FROM $this->orderTbl ".
		        "WHERE ((orstat = '$SHIPPED_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orshdt) > $PURGE_SHIP_DAYS) ".
				"OR (orstat != '$SHIPPED_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orordt) > $PURGE_SHIP_DAYS) ".
				"AND DATEDIFF(now(),orlmdt) > $PURGE_NOAS400_DAYS) ".
/* === need????
				"OR (orshdt = '0000-00-00' ".
				"AND DATEDIFF(now(),orlmdt) > $PURGE_SHIP_DAYS) ".
*/
		        "ORDER BY orrqdt  ASC";

		// echo "$stmt <P>";
exit;
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
				$row = $result->fetchRow();
				
				return $row->PurgeCount;
		}
         return false;
	}
	
	function sendOrderConfirmationEmail ($rcpt=null, $subject=null, $msg=null, $from=null, $bcc)
	{
		global $BCC_EMAIL_ALERT, $FROM_EMAIL, $TEST_MODE, $TEST_MODE_EMAIL;
		
		if (!$msg or !$rcpt or !$subject or !$from) return false;

/*
		if (isset ($this->orordn))
		{
			$orderList = $this->getOrderInfoList ($this->orordn);
			
			$msg = "Distributor: $this->ordisn\nOrder: $this->orordn\n\n";
			foreach ($orderList as $cat => $orderInfo)
			{
				$msg .= "$cat - $orderInfo->oroqty\n";
			}
			$msg .= "\n* Actual quantity and ship date are subject to availability.";
			$msg .= "\n\nThank you for your order!";
*/
			if ($TEST_MODE)
			{ // DO NOT send emails to distributors during testing
				// echo "Test Mode"; exit;
				$rcpt = "$TEST_MODE_EMAIL"; 
			} else if (isset ($bcc) and $bcc != "") 
			{
				// echo "Oh NO!!!! NOT Test Mode"; exit;
				$rcpt .= ",$bcc";
			}

			mail ($rcpt, $subject, $msg, "from: $from");
/*
		}
*/
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

	function updateOrder($data = null)
	{
		global $CHANGED_ORDER_STATUS;
		
		$data->ordrop = "PT";  // hard coded drop.  Will change sometime?
		$fieldList = $this->orderTblFields;
		// if (!isset ($data->orstat)) $data->orstat = "M"; 

		$keyVal = $this->makeUpdateKeyValuePairs($this->orderTblFields, $data);

		$where = "";
		if ($data->orordn) $where .= "orordn = '$data->orordn' ";
		if ($data->orwebn)
		{
			if (strlen ($where) > 0) $where .= "OR ";
			$where .= "orwebn = '$data->orwebn' ";
		}		
		$stmt = "UPDATE $this->orderTbl SET $keyVal, orlmdt = NOW() ".
		        "WHERE ($where) AND orcatn = '$data->orcatn'";
		// echo $stmt . "\r\n<p>"; exit;
		
		$result = $this->dbi->query($stmt);
		
		if ($return = $this->getReturnValue($result))
		{
			if (isset ($data->DisplayOrder))
			{
				$stmt2 = "UPDATE $this->orderTbl ".
						 "SET DisplayOrder = 0, orlmdt = NOW() ".
						 "WHERE DisplayOrder = '$data->DisplayOrder' ".
						 "AND orordn != '$data->orordn' ".
						 "AND orcatn = $data->orcatn";
				$result2 = $this->dbi->query($stmt2);
				
				return $this->getReturnValue($result2);
			} else {
				return $return;
			}
		} else {
			return false;
		}
	}
	
	function updateOrderbyOrderNumber ($data)
	{
		global $CHANGED_ORDER_STATUS;
		
		$data->ordrop = "PT";  // hard coded drop.  Will change sometime?
		$fieldList = $this->orderTblFields;
		// if (!isset ($data->orstat)) $data->orstat = "M"; 

		$keyVal = $this->makeUpdateKeyValuePairs($this->orderTblFields, $data);

		$stmt = "UPDATE $this->orderTbl SET $keyVal, orlmdt = NOW() ".
		        "WHERE orordn = '$data->orordn'";
		// echo $stmt . "\r\n<p>";
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}
	
	function updateOrderComment ($data, $oid)
	{
		if (!$oid) return false;

		$fields   = $this->getOrderCommentFieldList();

		$keyVal = $this->makeUpdateKeyValuePairs($this->orderCommentsTblFields, $data);

		$stmt = "UPDATE $this->orderCommentsTbl SET $keyVal, occmdt = NOW() ".
		        "WHERE ocordn = '$oid'";
		// echo "$stmt<br />"; exit;
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}
	
	function updateOrderNumber ($ocwebn, $ocordn)
	{
		$stmt = "UPDATE $this->orderCommentsTbl SET ocordn = '$ocordn', occmdt = NOW() ".
		        "WHERE ocwebn = '$ocwebn'";
		// echo "$stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}
	
	function updateBHOrderInfo ($inv, $idate=null, $pal=0, $keg=0)
	{
		if (!$inv) return false;

		$stmt = "SELECT orordn FROM $this->orderBHInfoTbl ".
		        "WHERE orordn = '$this->orordn' LIMIT 0,1";
		// echo "$stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		if (!$idate) $idate = "now()";
		else 
		{
			list($month,$day,$year) = preg_split('?[/-]+?', $idate);
			if (strlen($year)==2) $year = "20$year";

			$idate = '"' . $year."-".$month."-".$day . '"';
		}
		
		if ($result->numRows() > 0)
		{
			$stmt2 = "UPDATE $this->orderBHInfoTbl ".
					"SET InvoiceNumber = $inv, ".
					"InvoiceDate = $idate, ".
					"NumPallets = $pal, ".
					"NumKegs = $keg ".
					"WHERE orordn =  '$this->orordn' ";
			// echo "$stmt2<br />";
			
			$result = $this->dbi->query($stmt2);
			return $this->getReturnValue($result);
		} else {
			$this->addBHOrderInfo ($pal);
		}
		return $this->getReturnValue($result);
	}
	
	function updateOrderStatus ($stat)
	{
		if (!$stat) return false;

		$stmt = "UPDATE $this->orderTbl SET orstat = '$stat' ".
		        "WHERE orordn = '$this->orordn'";
		// echo "$stmt<br />"; 
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}
	
	function updateMaxWebOrder ($wid = null)
	{
		if (!$wid) return false;
		
		$stmt = "UPDATE $this->orderWebNumberTbl ".
				"SET WebOrderNumber = $wid";
		// echo "$stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}
	
	function updateMaxBHOrder ($wid = null)
	{
		if (!$wid) return false;
		
		$stmt = "UPDATE $this->orderBHOrderNumberTbl ".
				"SET BHOrderNumber = $wid";
		// echo "$stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}
	
	function updateOrderForm($data = null)
	{
		$fieldList = $this->orderFormsTblFields;

		$keyVal = $this->makeUpdateKeyValuePairs($this->orderFormsTblFields, $data);

		$stmt = "UPDATE $this->orderFormsTbl SET $keyVal, ctlmdt = NOW() WHERE ctcatn = $data->ctcatn";
		// echo "updateOrderForm: $stmt <p>";
		
		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}
	
	function updateOrderFormHeader ($data = null)
	{
		$fieldList = $this->orderFormsTblFields;

		if (! $this->isOrderFormHeaderEntry ($data->Description))
		{
			return $this->addOrderForm ($data);
		} else {
			$keyVal = $this->makeUpdateKeyValuePairs($this->orderFormsTblFields, $data);
	
			$stmt = "UPDATE $this->orderFormsTbl SET $keyVal, ctlmdt = NOW() ".
			        "WHERE Description = '$data->Description'"; 
			// echo"SQL: $stmt<br />";
			
			$result = $this->dbi->query($stmt);
	
			return $this->getReturnValue($result);
		}
	}
	
	function updateMaxBHInvoice ($wid = null)
	{
		if (!$wid) return false;
		
		$stmt = "UPDATE $this->orderBHInvoiceNumberTbl ".
				"SET BH_invoice_number = $wid";
		// echo "$stmt<br />";
		
		$result = $this->dbi->query($stmt);
		
		return $this->getReturnValue($result);
	}
	
	function updateOrderPrices ($status = null, $priceTable = null)
	{
		if (!$status or !$priceTable) return false;
		
		global $DISTRIBUTOR_PRICE_TBL;
			
		// get records to be updated
		$sql = "SELECT DISTINCT ordisn, orcatn, Price ".
			   "FROM $this->orderTbl, $DISTRIBUTOR_PRICE_TBL ".
		       "WHERE orstat in ($status) ".
			   "AND ordisn = DistributorNo AND orcatn = CatalogNo";
		// echo "$sql<br />";

		$result = $this->dbi->query($sql);
		
		if ($result->numRows() > 0)
		{
			$cntErr = 0;
			while($row = $result->fetchRow())
			{
				$sql2 = "UPDATE $this->orderTbl ".
				        "SET ItemPrice = $row->Price ".
						"WHERE orstat in ($status) ".
						"AND ordisn = '".$row->ordisn."' AND orcatn = $row->orcatn";
						
				// echo "$sql2<br />";
		
				$result2 = $this->dbi->query($sql2);
				if (!$this->getReturnValue($result2)) $cntErr++;
			}
			if (!$cntErr) return TRUE;
		}
		return FALSE;
	}
	//
	// Purge new line order items that have not been processed by the AS/400 within $PURGE_NOAS400_DAYS
	// And Changed, Deleted (unprocessed) or still Active the request date has passed.
	// And AS/400 is no longer downloading data ($PURGE_NOAS400_DAYS)
	// Only process comments if entire order is purged
	//
	function purgeUnprocessedLineItems ($cfl=null, $pdy=null)
	{
		global $NEW_ORDER_STATUS, $CHANGED_ORDER_STATUS, $DELETED_ORDER_STATUS,
		       $ACTIVE_ORDER_STATUS, $SHIPPED_ORDER_STATUS, $PURGE_NOAS400_DAYS;
		
	  	if (!$cfl or !$pdy) return null;
		// $fields   = $this->$cfl();
		$fields = $this->getOrderFieldList ();

		$fieldStr = implode(', ', $fields);
		
/* old
		$stmt = "SELECT $fieldStr FROM $this->orderTbl ".
		        "WHERE ((orstat in ('$NEW_ORDER_STATUS', '$CHANGED_ORDER_STATUS', '$DELETED_ORDER_STATUS') ".
				"AND DATEDIFF(now(),orordt) > $PURGE_NOAS400_DAYS) ".
				"OR (orstat = '$ACTIVE_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orordt) > $PURGE_NOAS400_DAYS ".
				"AND DATEDIFF(now(),orrqdt) > 0)) ".
				"AND DATEDIFF(now(),orlmdt) > $PURGE_NOAS400_DAYS ".
		        "ORDER BY orrqdt  ASC";
*/
		$stmt = "SELECT $fieldStr FROM $this->orderTbl ".
		        "WHERE ((orstat in ('$NEW_ORDER_STATUS', '$CHANGED_ORDER_STATUS', '$DELETED_ORDER_STATUS') ".
				"AND DATEDIFF(now(),orordt) > $pdy) ".
/* not performing this test at this time - don't know how acurate the request date is
				"OR (orstat = '$ACTIVE_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orrqdt) > $pdy ".
				"AND orshdt = '0000-00-00' AND orsqty = 0) ".
*/
				"OR (orstat = '$ACTIVE_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orshdt) > $pdy ".
				"AND orsqty = 0) ".
				"OR (orstat = '$SHIPPED_ORDER_STATUS' ".
				"AND orsqty = 0)) ".
				"AND DATEDIFF(now(),orlmdt) > $pdy ".
		        "ORDER BY orrqdt  ASC";
		// echo "purgeUnprocessedLineItems: $stmt <P>";

		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
             while($row = $result->fetchRow())
             {
             	// Add record to Order History table
				$this->addOrderHistory($row);
				// Delete record from Order table
				$this->deleteOrderLinePurge($row->orordn, $row->orwebn, $row->orcatn);
             }
			 return TRUE;
		}
         return FALSE;
	}
	//
	// Only process comments if entire order is purged
	//
	function purgeUnprocessedOrderComments ()
	{
		global $PURGE_NOAS400_DAYS;
		
		// $fields   = $this->$cfl();
		$fields = $this->getOrderCommentsCSVFieldList ();

		$fieldStr = implode(', ', $fields);
		
		$stmt = "SELECT $fieldStr FROM $this->orderCommentsTbl ".
		        "WHERE ocordn not in (SELECT DISTINCT orordn FROM $this->orderTbl) ".
		        "ORDER BY ocordn  ASC";

		// echo "purgeUnprocessedOrderComments: $stmt <P>";

		$retArray = Array ();
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
             while($row = $result->fetchRow())
             {
             	// Add record to Order History table
				$this->addOrderCommentsHistory($row);
				// Delete record from Order table
				$this->deleteOrderCommentsPurge($row->ocordn, $row->ocwebn);
             }
		}
         return $retArray;
	}
	//
	// Purge all orders older that shipped more than $PURGE_SHIP_DAYS (conf.php)
	// or new web orders not assigned an AS/400 order number withing $PURGE_NOAS400_DAYS (conf.php)
	// And purge their comments.
	//
	function purgeOrders ($cfl=null)
	{
		global $SHIPPED_ORDER_STATUS, $NEW_ORDER_STATUS, $PURGE_SHIP_DAYS, $PURGE_NOAS400_DAYS;
		
	  	if (!$cfl) return null;
		
		// $fields   = $this->$cfl();
		$fields = $this->getOrderFieldList ();

		$fieldStr = implode(', ', $fields);
		
		$stmt = "SELECT $fieldStr FROM $this->orderTbl ".
		        "WHERE ((orstat = '$SHIPPED_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orshdt) > $PURGE_SHIP_DAYS) ".
				"OR (orstat != '$SHIPPED_ORDER_STATUS' ".
				"AND DATEDIFF(now(),orordt) > $PURGE_SHIP_DAYS) ".
				"AND DATEDIFF(now(),orlmdt) > $PURGE_NOAS400_DAYS) ".
/* === need????
				"OR (orshdt = '0000-00-00' ".
				"AND DATEDIFF(now(),orlmdt) > $PURGE_SHIP_DAYS) ".
*/
		        "ORDER BY orrqdt  ASC";

		// echo "purgeOrders: $stmt <P>";

		$retArray = Array ();
		
		$result = $this->dbi->query($stmt);

		if ($result->numRows() > 0)
		{
             while($row = $result->fetchRow())
             {
             	// Add record to Order History table
				$this->addOrderHistory($row);
				// Delete record from Order table
				$this->deleteOrderLinePurge($row->orordn, $row->orwebn, $row->orcatn);
				if ($comm = $this->getOrderCommentsforHistory ($row->orordn, $row->orwebn))
				{
					$comm->ocordn = $row->orordn;
					$comm->ocwebn = $row->orwebn;
					// echo "Data: ".print_r ($comm,true)."<br />";
					$this->addOrderCommentsHistory($comm);
					$this->deleteOrderCommentsPurge($row->orordn, $row->orwebn);
				}
             }
			 return TRUE;
		}
         return FALSE;
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
	
	function addOrder($data = null, $stat = 'N')
	{
		$data->ordrop = "PT";  // hard coded drop.  Will change sometime?
		$fieldList = $this->orderTblFields;
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

		$stmt = "INSERT INTO $this->orderTbl ($fields) VALUES($values)";

		// echo "$stmt<br />";

		$result = $this->dbi->query($stmt);

/*		if ($this->dbi->getErrorCode () == -5)
		{  // duplicate entry
			$this->setOrderID($this->getOrderIdByNameId ($data->OrderNameId));

			return true;
		}
		$this->OrderId = $this->dbi->getLastInsertID ();
*/
		return $this->getReturnValue($result);
	}
	
	private function addOrderHistory ($data = null)
	{
		// Data should be prepped because it just came from the db
		// We really don't want to unescape everything and then escape it again
		
		$fieldList = $this->orderHistoryTblFields;
		$valueList = array();

		while(list($k, $v) = each($fieldList))
		{
			// echo "$k: ".$data->$k."<br />";
			if (!strcmp($v, 'text') or !strcmp($v, 'check') or !strcmp($v, 'date'))
			{
				$valueList[] = $this->dbi->quote(addslashes($data->$k));
			} else if (!strcmp($v, 'now')){
				if ($k == 'orlmdt') // want to retain actual value from order table
				{
					$valueList[] = $this->dbi->quote(addslashes($data->$k));
				} else {
					$valueList[] =  $this->dbi->quote(date ('Y-m-d H:i:s'));
				}
			} else if (isset ($data->$k)) {
				$valueList[] = $data->$k;
			}
			// echo "$k: [".$data->$k."]<br />";
		}
		$fields = implode(',', array_keys($fieldList));
		$values = implode(',', $valueList);
		
		$stmt = "INSERT INTO $this->orderHistoryTbl ($fields) VALUES($values)";
		// echo "addOrderHistory: $stmt<br />"; // exit;

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}
	
	function addOrderComment($wid, $data = null, $oid = null)
	{
		$fieldList = $this->orderCommentsTblFields;
		$fields = implode(',', array_keys($fieldList));
		if ($wid != "")
		{
			if ($oid and $oid != "")
			{
				$values = $this->dbi->quote(addslashes($oid)).",".$this->dbi->quote(addslashes($wid));
			} else {
				$values = "'',".$this->dbi->quote(addslashes($wid));
			}
			foreach ($data as $value)
			{
				$values .= ",". $this->dbi->quote(addslashes($value));
			}
			$values .= ',"","",NOW()';  // blank out last 2 comments (shouldn't exist)
			$where = "ocwebn = '$wid'";
		} else if ($oid)
		{
			$values = $this->dbi->quote(addslashes($oid)).',"", "",""';
			foreach ($data as $value)
			{
				$values .= ",". $this->dbi->quote(addslashes($value));
			}
			$values .= ',NOW()';  // blank out last 2 comments (shouldn't exist)
			$where = "ocordn = '$oid'";
		}
		
		$stmt = "INSERT INTO $this->orderCommentsTbl ($fields) VALUES($values) ";
		// echo "$stmt<br />";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

	private function addOrderCommentsHistory ($data = null)
	{
		// Data should be prepped because it just came from the db
		// We really don't want to unescape everything and then escape it again
		
		$fieldList = $this->orderCommentsHistoryTblFields;
		$valueList = array();

		while(list($k, $v) = each($fieldList))
		{
			// echo "$k: ".$data->$k."<br />";
			if (!strcmp($v, 'text') or !strcmp($v, 'check') or !strcmp($v, 'date'))
			{
				$valueList[] = $this->dbi->quote(addslashes($data->$k));
			} else if (!strcmp($v, 'now')){
				if ($k == 'occmdt') // want to retain actual value from order table
				{
					$valueList[] = $this->dbi->quote(addslashes($data->$k));
				} else {
					$valueList[] =  $this->dbi->quote(date ('Y-m-d H:i:s'));
				}
			} else if (isset ($data->$k)) {
				$valueList[] = $data->$k;
			}
			// echo "$k: [".$data->$k."]<br />";
		}
		$fields = implode(',', array_keys($fieldList));
		$values = implode(',', $valueList);
		
		$stmt = "INSERT INTO $this->orderCommentsHistoryTbl ($fields) VALUES($values)";
		// echo "addOrderCommentsHistory: $stmt<br />";

		$result = $this->dbi->query($stmt);
		return $this->getReturnValue($result);
	}
	
	function addOrderForm ($data = null)
	{
		$fieldList = $this->orderFormsTblFields;
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

		$stmt = "INSERT INTO $this->orderFormsTbl ($fields) VALUES($values)";
		// echo "$stmt<br />";

		$result = $this->dbi->query($stmt);

		if ($this->dbi->getErrorCode () == -5)
		{  // duplicate entry
			$catId = $this->getCatalogIdByName (addslashes($data->Description));
			// echo"Cat ID: $catId<br />";
			// $this->setCatalogID($this->getCatalogIdByName (addslashes($data->Description)));

			return true;
		}
		$this->CatalogId = $this->dbi->getLastInsertID ();

		return $this->getReturnValue($result);
	}
	
	function addBHOrderInfo ($pal)
	{
		$stmt = "INSERT INTO $this->orderBHInfoTbl (orordn, InvoiceNumber, InvoiceDate, ".
		        "NumPallets) ".
		        "VALUES('$this->orordn', $this->InvoiceNumber, now(), $pal)";
		// echo "addBHOrderInfo $stmt<br />";

		$result = $this->dbi->query($stmt);

	}

	function addBHInvoice ()
	{
		// This information is to be stored into the invoice history table
		
		global $DISTRIBUTOR_PRICE_TBL, $UNPROCESSED_BYAS400_INV_STATUS;
		
		$fields   = $this->getInvoiceHistoryFieldList();

		$fieldStr = implode(', ', $fields);

		$selStmt = "SELECT inv.orordn, inv.InvoiceNumber, inv.InvoiceDate, inv.NumPallets, ".
				"inv.NumKegs, ord.oroqty, ord.ordisn, ord.orcatn, ord.itemPrice as Price, frm.Description ".
				"FROM $this->orderTbl as ord, $this->orderBHInfoTbl as inv, ".
				"$this->orderFormsTbl as frm ".
				"WHERE ord.orordn = inv.orordn ".
				"AND ord.orordn != '' ".
				"AND ord.orcatn = frm.ctcatn ".
				"AND ord.orstat = '$UNPROCESSED_BYAS400_INV_STATUS' ".
				"AND inv.InvoiceNumber = $this->InvoiceNumber ";
		// echo "getBHInvoiceInfo - $selStmt<br />";
		
		$stmt = "INSERT into $this->invoiceHistoryBHTbl ".
		        "($fieldStr) $selStmt ";
		// echo "getBHInvoiceInfo - $stmt<br />";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

	function deleteOrder($oid = null, $cos = null)
	{  // not an actual delete - set order status to delete
		if (!$oid and !$cos) return false;
		
		$stmt = "UPDATE $this->orderTbl " .
				"SET orstat = '$cos', oroqty = 0 WHERE orordn = '$oid'";
		// echo "$stmt<br />";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

	// Zeroes quantity rather than an actual delete
	
	function deleteOrderLine($oid = null, $cid = null, $cos = null)
	{  // not an actual delete - set order status to delete
		
		if (!$oid and !$cid and !$cos) return false;
		
		$stmt = "UPDATE $this->orderTbl " .
				"SET orstat = '$cos', oroqty = 0 ".
				"WHERE orordn = '$oid' AND orcatn = '$cid'";
		// echo "$stmt<br />";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

	function deleteOrderLinePurge($oid = null, $wid = null, $cid = null)
	{  // delete purged records - should be archived in history file
		
		if (!$oid and !$wid and !$cid) return false;
		
		$stmt = "DELETE FROM $this->orderTbl " .
				"WHERE orordn = '$oid' AND orwebn = '$wid' and orcatn = '$cid'";
		// echo "deleteOrderLinePurge: $stmt<br />"; // exit;

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

	function deleteOrderCommentsPurge($oid = null, $wid = null)
	{  // delete purged records - should be archived in history file
		
		if (!$oid and !$wid) return false;
		
		$stmt = "DELETE FROM $this->orderCommentsTbl " .
				"WHERE ocordn = '$oid' AND ocwebn = '$wid' ";
		// echo "deleteOrderCommentsPurge: $stmt<br />";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}
	
	function deleteInvoiceHistory ($inv = null)
	{
		if (!$inv) return false;
		
		$stmt = "DELETE FROM $this->invoiceHistoryBHTbl " .
				"WHERE InvoiceNumber = '$inv' ";
		// echo "deleteInvoiceHistory: $stmt<br />"; exit;

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

	/*
	 * Actually we are just going to clear out NumPallets and NumKegs
	 */
	 
	function deleteOrderInfo ($inv = null)
	{
		if (!$inv) return false;
		
		$stmt = "UPDATE $this->orderBHInfoTbl " .
				"SET NumPallets = 0, NumKegs = 0 ".
				"WHERE InvoiceNumber = '$inv' ";
		// echo "deleteOrderInfo: $stmt<br />"; exit;

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}

	function clearOrderForm ($ofc)
	{
		if (!$ofc) return false;
		
		$stmt = "DELETE FROM $this->orderFormsTbl ".
		        "WHERE OrderFormCode in $ofc";
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
			"OrderId  = $this->OrderId, ".
			"OrderNameId  = $name, ".
			"ActionType = $action, " .
				"ActionTimeStamp = NOW()";

		// echo "$stmt <P>";

		$result = $this->dbi->query($stmt);

		return $this->getReturnValue($result);
	}
}
?>
