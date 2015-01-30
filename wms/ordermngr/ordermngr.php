<?php
require_once "ordermngr.conf.php";

class OrderMngrApp extends PHPApplication {
	//var $dbi;
	
	function run()
	{
		global $WMS_INDEX_TEMPLATE,$ORDER_MNGR, $WMS_DB_URL, $DISTRIBUTOR_APP_RUNNING;

		if (!$this->getTestAdmin () && ($this->checkOrderSuspended (TRUE) || $this->getAppStatus () != $DISTRIBUTOR_APP_RUNNING))  // Include warning time
		  {
			  global $DISTRIBUTOR_APP_RUNNING;
			  
			  if ($this->getAppStatus () != $DISTRIBUTOR_APP_RUNNING) // app not running
			  {
				  $status = $this->getAppStatusMessage ();
				  global $CLIENT_INDEX_TEMPLATE, $LOGIN_MNGR;
			  
				  $this->showStatus($CLIENT_INDEX_TEMPLATE, $status);
			  }
		  } else {
		$this->cmd = $this->getRequestField('cmd'); 
		$this->step = $this->getRequestField('step'); 
		$this->orordn = $this->getRequestField('orordn');

		$this->order = new Order ($this->dbi, $this->orordn); 

		$this->distributor = new Distributor ($this->dbi, $this->userId);
		$this->distributorOrderCode = $this->distributor->getDistributorOrderCodeForm ();

		$this->debug ("Cmd: $this->cmd<br />");

		$this->cmd = strtolower($this->cmd);

		if (!strcmp($this->cmd, 'add'))
		{
			$this->addDriver();

		} else if (!strcmp($this->cmd, 'modify'))
		{
			$this->modifyDriver();

		} else if (!strcmp($this->cmd, 'delete'))
		{
			$this->deleteDriver();

		} else if (!strcmp($this->cmd, 'display'))
		{
			$this->displayDriver();

		} else if (!strcmp($this->cmd, 'invoice'))
		{
				global $WMS_INDEX_TEMPLATE, $ORDER_MNGR;

				$this->debug ("ordermngr: " . $WMS_INDEX_TEMPLATE);
				$this->showScreen($WMS_INDEX_TEMPLATE, 'displayInvoice', $ORDER_MNGR);

		} else if (!strcmp($this->cmd, 'email'))
		{
			$this->emailInvoice ();

		} else if (!strcmp($this->cmd, 'emailbol'))
		{
			$this->emailBOL ();

		} else if (!strcmp($this->cmd, 'bol'))
		{
				global $WMS_INDEX_TEMPLATE, $ORDER_MNGR;

				$this->debug ("ordermngr: " . $WMS_INDEX_TEMPLATE);
				$this->showScreen($WMS_INDEX_TEMPLATE, 'displayBOL', $ORDER_MNGR);

		} else if ($this->cmd == "selectuser")
		{
			$this->selectUserDriver ();
		} else {
			$this->displayDriver();
		}
		}
	}
	
	function addDriver()
	{
		global $APP_DIR, $HOME_APP;
		
		switch ($this->step)
		{
			case 3:
				$this->addOrder ();

				// header("Location: $APP_DIR$HOME_APP");
				break;
			case 1:
			case 2:
			default:
				global $CLIENT_INDEX_TEMPLATE, $ORDER_MNGR;
				
				// Are we processing contract orders
				$isChildDist = $this->distributor->getChildDistributorId ();
				
				if ($this->distributorOrderCode == 'D' && !isset ($this->child))
				{
					$this->selectUserDriver ();
				} else {
					$this->OrderId = 0;
					$this->debug ("ordermngr: " . $CLIENT_INDEX_TEMPLATE);
					$this->showScreen($CLIENT_INDEX_TEMPLATE, 'orderScreen', $ORDER_MNGR);
				}
		}
	}
	
		function selectUserDriver ()
		{
			if ($this->step == 2)
			{
				$this->child = $this->getRequestField('SelectDistributor'); 
				$this->cmd = 'add';
				$this->addDriver(); // we only select when adding orders!

			} else {
				global $CLIENT_INDEX_TEMPLATE, $ORDER_MNGR;
				$this->debug ("mainmenu: " . $CLIENT_INDEX_TEMPLATE);
				
				$this->showScreen($CLIENT_INDEX_TEMPLATE, 'selectUserScreen', $ORDER_MNGR);
			}
		}
	function modifyDriver()
	{
		switch ($this->step)
		{
			case 3:
				// $this->alert($this->getMessage('ORDER_SUCCESSFUL'));
				$this->modifyOrder ();
				break;
			case 1:
			case 2:
			default:
				global $CLIENT_INDEX_TEMPLATE, $ORDER_MNGR;

				$this->debug ("ordermngr: " . $CLIENT_INDEX_TEMPLATE);
				$this->showScreen($CLIENT_INDEX_TEMPLATE, 'orderScreen', $ORDER_MNGR);
		}
	}
	
	function deleteDriver()
	{
		global $HOME_APP;
		
		if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
		{
			global $UNPROCESSED_DELETED_ORDER_STATUS;
			
			$this->child = $this->order->ordisn;
			$deleteOrderStatus = $UNPROCESSED_DELETED_ORDER_STATUS; // This order stays active because it doesn't go to AS400
		} else {
			global $DELETED_ORDER_STATUS;
			
			$deleteOrderStatus = $DELETED_ORDER_STATUS; // This order stays active because it doesn't go to AS400
		}
		$status = $this->order->deleteOrder ($this->orordn, $deleteOrderStatus);
		$err = "";
		if ($status != MDB2_OK) $err .= $status."<br />";
		global $CLIENT_INDEX_TEMPLATE, $ORDER_MNGR;
		if ($err)
		{
			$this->ordStatus = $this->getMessage('ORDER_DELETE_FAILED');
		} else {
			$this->ordStatus = $err.$this->getMessage('ORDER_CANCELLED_SUCCESSFUL');
		}
		$this->showScreen($CLIENT_INDEX_TEMPLATE, 'statusScreen', $ORDER_MNGR);
	}
	
	function displayDriver()
	{
		global $CLIENT_INDEX_TEMPLATE, $ORDER_MNGR;
		
		$this->OrderId = $this->orordn;
		
		$this->debug ("ordermngr: " . $CLIENT_INDEX_TEMPLATE);
		$this->showScreen($CLIENT_INDEX_TEMPLATE, 'orderDisplayScreen', $ORDER_MNGR);
	}
	
	function downloadDriver()
	{
	}
	
	function backupDriver()
	{
	}
	
	function viewDriver2()
	{
			global $WMS_INDEX_TEMPLATE, $ORDER_MNGR;
			$this->debug ("ordermngr: " . $WMS_INDEX_TEMPLATE);
			
			$this->showScreen($WMS_INDEX_TEMPLATE, 'orderManagerViewScreen', $ORDER_MNGR);
	}

	function mainDriver()
	{
			global $WMS_INDEX_TEMPLATE, $ORDER_MNGR;
			$this->debug ("ordermngr: " . $WMS_INDEX_TEMPLATE);
			
			$this->showScreen($WMS_INDEX_TEMPLATE, 'orderManagerScreen', $ORDER_MNGR);
	}
	
	function addOrder ()
	{
		global $VIEW_FORM_MODULE, $SHIP_DATE_ADVANCE, $MODIFY_ORDER_ADVANCE, $OVER_WEIGHT, $APP_SUBDIR, $HOME_APP, $ORDER_MNGR, $NEW_ORDER_STATUS, $DEFAULT_DROP, $SPECIAL_DISPLAY ;
		
		$this->form = new Form ($this->dbi, $VIEW_FORM_MODULE, 'OrderMngr');
		$this->catalog = new Catalog ($this->dbi);

		$orderFormTemplateItems = $this->order->getCatalogList ('getOrderFormTemplateFormFieldList', $this->distributor->OrderFormCode);
		// $catalogItems = $this->catalog->getCatalogList ('getCatalogFieldList', $this->distributor->OrderFormCode);
		// $catalogItems = $this->catalog->getCatalogList ('getCatalogFieldList');
		$numCatalog = count ($orderFormTemplateItems);

		$this->getFormData ($this->order, 'getOrderFieldList');

		if ($this->isAdmin ())
		{
			$MaxWeight = 9999999; // no weight limit
			$StdShipRequestDays = -999; // no minimum date to change
			$StdMinShipRequestDays = -999;
			$MinQtyRequired = 0;
		} else {
			$MaxWeight = $OVER_WEIGHT;
			$StdShipRequestDays = $SHIP_DATE_ADVANCE;
			$StdMinShipRequestDays = $MODIFY_ORDER_ADVANCE;
			$MinQtyRequired = 1;
		}
		// $this->orrqdt = $this->ShipMonth."-".$this->ShipDay."-".$this->ShipYear;
		// if (!$this->checkDate ($this->orrqdt) or !$this->dateRange ($this->orrqdt, $StdShipRequestDays))
		if (!$this->orrqdt) // JS not running
		{
			$this->ShipYear = $this->getRequestField('ShipYear'); 
			$this->ShipMonth = $this->getRequestField('ShipMonth'); 
			$this->ShipDay = $this->getRequestField('ShipDay');
			 
			$this->orrqdt = $this->ShipYear.'-'.$this->ShipMonth.'-'.$this->ShipDay;
		}
		if (!$this->checkDate ($this->orrqdt) or !$this->dateRange ($this->orrqdt, $StdShipRequestDays))
		{
			$this->alert ('INVALID_SHIP_DATE: '.$this->orrqdt);  // Date > $SHIP_DATE_ADVANCE
			exit;
		}
		$this->OrderWeight = $this->getRequestField('OrderWeight');

		if ($this->OrderWeight > $MaxWeight)
		{
			$this->alert ('OVER_WEIGHT');
			exit;
		} else if ($this->OrderWeight <= 0.000001)
		{
			$this->alert ('ZERO_WEIGHT');
			exit;
		}
		$err = "";
		// $ordisn = $this->getRequestField('ordisn');
		if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
		{
			global $UNPROCESSED_BYAS400_STATUS;
			
			$ordDist = new Distributor ($this->dbi, $this->ordisn);
			$webOrderType = 'B';
			// $this->child = $this->order->ordisn;
			$newOrderStatus = $UNPROCESSED_BYAS400_STATUS; // This order stays active because it doesn't go to AS400
			$orderNumFunction = 'getMaxBHOrder'; 
		} else {
			global $NEW_ORDER_STATUS;
			
			$ordDist = $this->distributor;
			$webOrderType = 'W';
			$newOrderStatus = $NEW_ORDER_STATUS;
			$orderNumFunction = 'getMaxWebOrder'; 
		}
/*
		if ($this->distributor->getParentDistributorId ($ordisn) != "")
		{
			$webOrderType = 'B';
			$newOrderStatus = 'U';
		} else {
			$newOrderStatus = $NEW_ORDER_STATUS;
		}
*/
		$orwebn = $webOrderType.$this->order->$orderNumFunction ();

		$this->getFormData ($this->order, 'getOrderInfoFieldList');
		$this->getFormArrayData ($this->order, 'getOrderFormFieldList');

		if ($newOrderStatus == 'U') $this->orordn = $orwebn; // Not uploaded to AS/400 but we still need an orordn

		$orderInfo = $this->processFormData ($this->order, 'getOrderInfoFieldList', 'orderTblFields');
		for ($i = 0; $i < $numCatalog; $i++)
		{
			// $this->getCurrentPageData ($this->order, 'getOrderFieldList');
			if ($this->oroqty [$i] == 0 or $this->oroqty [$i] == $SPECIAL_DISPLAY ) continue;
			if ($MinQtyRequired && $this->oroqty [$i] < $this->MinQty [$i] && $this->oroqty [$i] != 0)
			{
				$this->alert ('MIN_QTY_ERROR');
				$err = "Minimum quantity on item ".$this->ctcatn [$i]." is ".$this->MinQty [$i].".<br />";
				exit;
			}
		}
		for ($i = 0; $i < $numCatalog; $i++)
		{
			// $this->getCurrentPageData ($this->order, 'getOrderFieldList');
			if ($this->oroqty [$i] == 0 or $this->oroqty [$i] == $SPECIAL_DISPLAY ) continue;
			if ($MinQtyRequired && $this->oroqty [$i] < $this->MinQty [$i] && $this->oroqty [$i] != 0)
			{
				$this->alert ('MIN_QTY_ERROR');
				$err = "Minimum quantity on item ".$this->ctcatn [$i]." is ".$this->MinQty [$i].".<br />";
				exit;
			}
			$hash = $this->processFormArrayData ($orderInfo, $this->order, 'getOrderFormFieldList', 'orderTblFields', $i);
			// $hash = $orderInfo . $orderLine;
			$hash->orsqty = 0;
			$hash->orstat = $newOrderStatus;
			$hash->ordrop = $DEFAULT_DROP;
			$hash->orwebn = $orwebn;
			if ($this->distributorOrderCode == 'D')
			{ // Catalog number has a price associated with it
				$hash->ItemPrice = $ordDist->getDistributorPrice ($hash->orcatn);
				if (!$hash->ItemPrice) $hash->ItemPrice = 0.0;
			} else {
				$hash->ItemPrice = 0.0;
			}
			$status = $this->order->addOrder ($hash);
			if ($status != MDB2_OK) $err .= print_r($status,true)."<br />";
		}
		for ($i = 1; $i <= 2; $i++)
		{
			$comment [] = $this->getRequestField('OrderComment'.$i);
		}
		if ($comment)
		{
			$ocordn = "";
			if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
			{
				$ocordn = $orwebn;
			}
			$status = $this->order->addOrderComment ($orwebn, $comment, $ocordn);
			if ($status != MDB2_OK) $err .= print_r($status,true)."<br />";
		} 
		global $CLIENT_INDEX_TEMPLATE, $ORDER_MNGR;
		$this->debug ("ordermngr: " . $CLIENT_INDEX_TEMPLATE);
		
		if (!$err)
		{
			if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
			{
				$this->comm->occom1 = isset ($this->order->occom1)?$comment[0]:"";
				$this->comm->occom2 = isset ($this->order->occom2)?$comment[1]:"";
				$this->comm->occhg1 = "";
				$this->comm->occhg2 = "";
				$this->sendOrderConfirmationEmail ();
			}
			$this->ordStatus = $this->getMessage('ORDER_SUCCESSFUL');
		} else {
			global $ERROR_EMAIL;
			$this->ordStatus = $err.$this->getMessage('ORDER_ADD_FAILED');
		}
		$this->showScreen($CLIENT_INDEX_TEMPLATE, 'statusScreen', $ORDER_MNGR);
	}
	
	function modifyOrder ()
	{
		global $VIEW_FORM_MODULE, $MODIFY_ORDER_ADVANCE, $SHIP_DATE_ADVANCE, $MODIFY_ORDER_ADVANCE, $OVER_WEIGHT, $APP_SUBDIR, $HOME_APP, $ORDER_MNGR, $ACTIVE_ORDER_STATUS, $DEFAULT_DROP, $MODIFY_ORDER_SUBJECT, $SPECIAL_DISPLAY, $COMMENTS_REQUIRED;
		
		$this->form = new Form ($this->dbi, $VIEW_FORM_MODULE, 'OrderMngr');
		$this->catalog = new Catalog ($this->dbi);

		$orderFormTemplateItems = $this->order->getCatalogList ('getOrderFormTemplateFormFieldList', $this->distributor->OrderFormCode);

		$numCatalog = count ($orderFormTemplateItems);
		
		if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
		{
			global $UNPROCESSED_BYAS400_STATUS;
			
			$this->child = $this->order->ordisn;
			$ordDist = new Distributor ($this->dbi, $this->child);
			$changeOrderStatus = $UNPROCESSED_BYAS400_STATUS; // This order stays active because it doesn't go to AS400
			$deleteOrderStatus = $UNPROCESSED_BYAS400_STATUS; // This order stays active because it doesn't go to AS400
			/*
			 * if order is in Invoice status need to change entire order to active
			 * and remove order from invoice history and order info
			 * Display pop-up warning?
			 */
			if ($this->InvoiceNumber = $this->order->getInvoice ())
			{
				global $UNPROCESSED_BYAS400_STATUS;
				
				$status = $this->order->deleteInvoiceHistory ($this->order->InvoiceNumber);
				$status2 = $this->order->deleteOrderInfo ($this->order->InvoiceNumber);

				$status3 = $this->order->updateOrderStatus ($UNPROCESSED_BYAS400_STATUS);
			}
		} else {
			global $CHANGED_ORDER_STATUS;
			
			$ordDist = $this->distributor;
			$changeOrderStatus = $CHANGED_ORDER_STATUS;
			$deleteOrderStatus = $CHANGED_ORDER_STATUS; // Do not use DELETED_ORDER_STATUS - used for entire order deleted not line item
		}
/* remove when above if clause tested
		if ($this->order->ordisn != $this->distributor->getDistributorId ())
		{
			$this->child = $this->order->ordisn;
		}
*/
		$this->getFormData ($this->order, 'getOrderFieldList');

		// Admin has no order restrictions
		if ($this->isAdmin ())
		{
			$MaxWeight = 9999999; // no weight limit
			$StdShipRequestDays = 0; // no minimum date to change
			$StdMinShipRequestDays = -999;
			$MinQtyRequired = 0;
		} else {
			$MaxWeight = $OVER_WEIGHT;
			$StdShipRequestDays = $SHIP_DATE_ADVANCE;
			$StdMinShipRequestDays = $MODIFY_ORDER_ADVANCE;
			$MinQtyRequired = 1;
		}
		if (!$this->orrqdt) // JS not running
		{
			$this->ShipYear = $this->getRequestField('ShipYear'); 
			$this->ShipMonth = $this->getRequestField('ShipMonth'); 
			$this->ShipDay = $this->getRequestField('ShipDay');
			 
			$this->orrqdt = $this->ShipYear.'-'.$this->ShipMonth.'-'.$this->ShipDay;
		}
		if (!$this->checkDate ($this->orrqdt) or !$this->dateRange ($this->orrqdt, $StdMinShipRequestDays))
		{
			$this->alert ('INVALID_SHIP_DATE');  // Date > $MODIFY_ORDER_ADVANCE
			exit;
		}
		$this->OrderWeight = $this->getRequestField('OrderWeight');

		if ($this->OrderWeight > $MaxWeight)
		{
			$this->alert ('OVER_WEIGHT');
			exit;
		}
		for ($i = 3; $i <= 4; $i++)
		{
			$comment = $this->getRequestField('OrderComment'.$i);
			if ($comment != "")
			{
				$OrderComment [] = $comment;
			}
		}
		if ($COMMENTS_REQUIRED and !$OrderComment)
		{
			$this->alert ('NO_COMMENT');
			exit;
		}
		$err = "";

		$this->getFormData ($this->order, 'getOrderInfoFieldList');
		$this->getFormArrayData ($this->order, 'getOrderFormFieldList');
		
		// $this->order->getOrderInfo ($this->orordn);

		$orderInfo = $this->processFormData ($this->order, 'getOrderInfoFieldList', 'orderTblFields');
		
		$update = "Delete";  // assume delete unless at least one entry w/quantity

		if ($this->OrderWeight <= 0.00000001)
		{
			$status = $this->order->deleteOrder ($this->orordn);
			if ($status != MDB2_OK) $err .= $status."<br />";
		} else {
			if ($orderInfo->orrqdt != $this->order->orrqdt or $orderInfo->orpono != $this->order->orpono)
			{
				$orderInfo->orstat = $CHANGED_ORDER_STATUS;
				$this->order->updateOrderbyOrderNumber ($orderInfo);
				$update = "Update";
			}
			for ($i = 0; $i < $numCatalog; $i++)
			{
				if ($this->orcatn [$i] != "")  // Skip headers
				{
					$formStatus = $this->order->getOrderLineInfo($this->orordn, $this->orcatn [$i]);
					if ($this->oroqty [$i] == $SPECIAL_DISPLAY) continue;

					if ($MinQtyRequired && $this->oroqty [$i] < $this->MinQty [$i] && $this->oroqty [$i] != 0)
					{
						$this->alert ('MIN_QTY_ERROR');
						$err = "Minimum quantity on item ".$this->ctcatn [$i]." is ".$this->MinQty [$i].".<br />";
						break;
					}
					$this->update = "";
					$this->orstat = $ACTIVE_ORDER_STATUS;
					$this->ordrop = $DEFAULT_DROP;
					$hash = $this->processFormArrayData ($orderInfo, $this->order, 'getOrderLineFieldList', 'orderTblFields', $i);

					// $hash = $orderInfo . $orderLine;
					// The following probably is only needed for added row items, but ...
					if ($this->distributorOrderCode == 'D')
					{ // Catalog number has a price associated with it
						$hash->ItemPrice = $ordDist->getDistributorPrice ($hash->orcatn);
						if (!$hash->ItemPrice) $hash->ItemPrice = 0.0;
					} else {
						$hash->ItemPrice = 0.0;
					}
					if (isset ($this->update) and $this->update == "Update")
					{
						$hash->orstat = $changeOrderStatus;
						$status = $this->order->updateOrder ($hash);
					} else if (($this->update == "Insert" or !$formStatus) and isset ($hash->oroqty) and $hash->oroqty > 0)
					{
						$hash->orstat = $changeOrderStatus;
						$hash->orsqty = 0;
						$status = $this->order->addOrder ($hash);
					} else if ($this->update == "Delete" or !isset ($hash->oroqty) or $hash->oroqty == 0)
					{
						$status = $this->order->deleteOrderLine ($this->orordn, $this->orcatn [$i], $deleteOrderStatus);
					}
					if (isset ($status) and $status != MDB2_OK) $err .= $status."<br />";
				}
			}
		}
/*
		for ($i = 3; $i <= 4; $i++)
		{
			$comment [] = $this->getRequestField('OrderComment'.$i);
		}
*/		if (isset ($OrderComment))
		{
			if ($orderComments = $this->order->getOrderComments ($this->orordn))
			{
				$this->comm->ocwebn = isset ($this->orwebn)?$this->orwebn:"";
				$this->comm->occom1 = isset ($this->order->occom1)?$this->order->occom1:"";
				$this->comm->occom2 = isset ($this->order->occom2)?$this->order->occom2:"";
				$this->comm->occhg1 = isset ($OrderComment [0])?$OrderComment [0]:"";
				$this->comm->occhg2 = isset ($OrderComment [1])?$OrderComment [1]:"";
				$status = $this->order->updateOrderComment ($this->comm, $this->orordn);
			} else {
				$this->comm->ocwebn = isset ($this->orwebn)?$this->orwebn:"";
				$this->comm->occom1 = "";
				$this->comm->occom2 = "";
				$this->comm->occhg1 = isset ($OrderComment [0])?$OrderComment [0]:"";
				$this->comm->occhg2 = isset ($OrderComment [1])?$OrderComment [1]:"";
				$status = $this->order->addOrderComment ($this->orwebn, $OrderComment, $this->orordn);
			}
			if ($status != MDB2_OK) $err .= $status."<br />";
		} else {
			$this->comm->occom1 = $this->comm->occom2 = $this->comm->occhg1 = $this->comm->occhg2 = "";
		}
		global $CLIENT_INDEX_TEMPLATE, $ORDER_MNGR;
		if (!$err)
		{
			$this->sendOrderConfirmationEmail ();
			if ($this->OrderWeight <= 0.00000001)
			{
			$this->ordStatus = $this->getMessage('ORDER_CANCELLED_SUCCESSFUL');
			} else {
			$this->ordStatus = $this->getMessage('ORDER_MODIFY_SUCCESSFUL');
			}
		} else {
			$this->ordStatus = $err.$this->getMessage('ORDER_MODIFY_FAILED');
		}
		$this->showScreen($CLIENT_INDEX_TEMPLATE, 'statusScreen', $ORDER_MNGR);
	}
	function sendOrderConfirmationEmail ()
	{
		global $ORDER_CONFIRMATION_TEMPLATE;
		
		// Content
		$this->debug ("main content: $ORDER_CONFIRMATION_TEMPLATE");
		$template = new HTML_Template_IT($this->getTemplateDir());
		$template->loadTemplatefile($ORDER_CONFIRMATION_TEMPLATE, true, true);
		
		$dist = new Distributor ($this->dbi);
		$dsname = $dist->getDistributorName ($this->ordisn);
		
		$template->setVariable ('dsdisn', $this->ordisn);
		$template->setVariable ('dsname', $dsname);
		$template->setVariable ('ordrop', $this->ordrop);
		$template->setVariable ('orordn', $this->orordn);
		$template->setVariable ('orordt', $this->orordt);
		$template->setVariable ('orpono', $this->orpono);
		$template->setVariable ('orrqdt', $this->orrqdt);
		$template->setVariable ('occom1', $this->comm->occom1);
		$template->setVariable ('occom2', $this->comm->occom2);
		$template->setVariable ('occhg1', $this->comm->occhg1);
		$template->setVariable ('occhg2', $this->comm->occhg2);

		$orderItems = $this->order->getOrderInfoList ($this->orordn);
		$template->setCurrentBlock("orderItemBlock");

		$orderItems = $this->order->getOrderInfoList($this->orordn, "orordn");

		foreach ($orderItems as $key => $orderItem)
		{
			$template->setVariable ('orcatn', $key);
			$template->setVariable ('Description', str_pad ($this->order->getOrderItemDescription ($key), 71));
			$template->setVariable ('oroqty', $orderItem->oroqty);
			$template->parseCurrentBlock("orderItemBlock");
		}
		$template->parse ();

		if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
		{
			global $BCC_BH_EMAIL_ALERT, $MODIFY_BH_ORDER_SUBJECT, $BH_FROM_EMAIL;
			$child = new Distributor ($this->dbi, $this->ordisn);
			$to = $child->getEmail ();
			$subject = $MODIFY_BH_ORDER_SUBJECT;
			$bcc = $BCC_BH_EMAIL_ALERT;
			$from = $BH_FROM_EMAIL;
		} else {
			global $BCC_EMAIL_ALERT, $MODIFY_ORDER_SUBJECT, $FROM_EMAIL;
			$to = $this->distributor->getEmail();
			$subject = $MODIFY_ORDER_SUBJECT.$this->orordn;
			$bcc = $BCC_EMAIL_ALERT;
			$from = $FROM_EMAIL;
		}
		$this->order->sendOrderConfirmationEmail ($to, $subject, $template->get(), $from, $bcc);
	}
	
	function orderScreen(&$t)
	{
		global $ORDERMNGR_CONTENT_TEMPLATE, $TEMPLATE_DIR;

		// Content
		$this->debug ("main content: $ORDERMNGR_CONTENT_TEMPLATE");
		$template = new HTML_Template_IT($TEMPLATE_DIR);
		$template->loadTemplatefile($ORDERMNGR_CONTENT_TEMPLATE, true, true);

		if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
		{
			global $UNPROCESSED_BYAS400_STATUS;
			
			if (!isset ($this->child)) $this->child = $this->order->ordisn;
			$this->orderBHForm ($template);
		} else {
			$this->orderForm ($template);
		}
		$template->parse ();
		
		// Back to main template
		$t->setVariable('ManageContent', $template->get());

		return 1;
	}
	
	function orderForm (&$t)
	{
		global $ORDERMNGR_FORM_TEMPLATE, $TEMPLATE_DIR, $VIEW_FORM_MODULE, 
		       $ORDER_FORM_COLUMNS, $DEFAULT_DROP, $OVER_WEIGHT, $OVER_WEIGHT_CLASS, 
			   $SHIP_DATE_ADVANCE, $MODIFY_ORDER_ADVANCE,
			   $MODIFY_ORDER_ADVANCE, $READ_ONLY_ATTRIBUTE, $DISPLAY_NONE, $SELECTED, $BOLD,
			   $MIN_ORDERFORM_COLUMN;

		// Content
		$this->debug ("main content: $ORDERMNGR_FORM_TEMPLATE");
		$template = new HTML_Template_IT($TEMPLATE_DIR);
		$template->loadTemplatefile($ORDERMNGR_FORM_TEMPLATE, true, true);

		$this->form = new Form ($this->dbi, $VIEW_FORM_MODULE, 'OrderMngr');
		$this->catalog = new Catalog ($this->dbi);
		
		// Admin has no order restrictions
		if ($this->isAdmin ())
		{
			$MaxWeight = 9999999; // no weight limit
			$StdShipRequestDays = 0; // no minimum date to change
			$StdMinShipRequestDays = -999;
			$MinQtyRequired = 0;
		} else {
			$MaxWeight = $OVER_WEIGHT;
			$StdShipRequestDays = $SHIP_DATE_ADVANCE;
			$StdMinShipRequestDays = $MODIFY_ORDER_ADVANCE;
			$MinQtyRequired = 1;
		}

		// display Distributor Order info
		$this->setFormData ($template, $this->distributor);
		$template->setVariable ('orordn', $this->orordn);
		$template->setVariable ('orordt', date ('m/d/y'));
		$template->setVariable ('MaxWeight', $MaxWeight);
		$template->setVariable ('StdShipRequestDays', $StdShipRequestDays);
		$template->setVariable ('StdMinShipRequestDays', $StdMinShipRequestDays);
		$template->setVariable ('MinQtyRequired', $MinQtyRequired);
		
		// display drop list
		// Not doing drops at this time'
		//$this->processDrop ($template)

		// display Order columns
		$template->setCurrentBlock("orderColumnBlock");
		$orderFormTemplateItems = $this->order->getCatalogList ('getOrderFormTemplateFormFieldList', $this->distributor->OrderFormCode, $this->distributor->State);
		// $catalogItems = $this->catalog->getCatalogList ('getCatalogFieldList', $this->distributor->OrderFormCode);
		// $catalogItems = $this->catalog->getCatalogList ('getCatalogFieldList');

		$orderList = $this->order->getOrderInfoList ($this->orordn);

		$numCatalog = count ($orderFormTemplateItems);

		$rowCount = 0;
		$totCount = 0;
		$i = 0;
		$totWeight = 0.0;

		$orderModifiable = true;
		$year = date ("Y");
		if ($this->cmd == "add")
		{
			// Setup ship date dropdown
			// Only want to allow orders for 1 year
			
			$month = date ("n");
			$shipDate = date_create ();
			$advShipStr = "+$SHIP_DATE_ADVANCE days";
			date_modify ($shipDate, $advShipStr);
			$shipDay = date_format ($shipDate, "w");
			if ($shipDay == 0 or $shipDay == 6) // Sunday or Saturday
			{
				if ($shipDay == 0) $mod = 1;
				else if ($shipDay == 6) $mod = 2;
				date_modify ($shipDate, '+'.$mod.' day');
			}
			$fmtShipDate = date_format ($shipDate, "n-j-Y");
		} else {   // Date > $MODIFY_ORDER_ADVANCE for non-admins?
			if ($this->order->orstat == "S") 
			{
				$orderModifiable = FALSE;
			} else {
				$orderModifiable = $this->dateRange ($this->order->orrqdt, $StdMinShipRequestDays);
			}
			$fmtShipDate = date_format (date_create($this->order->orrqdt), "n-j-Y");
		}

		$dtArray = explode ('-', $fmtShipDate);
		// $dtArray = getdate (strtotime ($shipDate));

		$this->year_arr = array ($year, $year + 1); // total of one year
		$this->setupDateSelector($template, 'Ship', $dtArray [0], $dtArray [1], $dtArray [2]);		

		$OrderRowId = 0;
		foreach ($orderFormTemplateItems as $catalogItem)
		{
			$template->setCurrentBlock("orderItemBlock");
			$this->setFormListData ($template, $this->catalog, $catalogItem);

			$this->processSpecials ($template, $catalogItem);
			$itemWeight = $this->catalog->getCatalogWeight ($catalogItem->ctcatn);
			if (!$catalogItem->ctcatn)
			{
				$template->setVariable ('display', $DISPLAY_NONE);
				$template->setVariable ('special', $BOLD);
			} else if (is_array ($orderList) and array_key_exists ($catalogItem->ctcatn, $orderList))
			{
				$totWeight += ($itemWeight * $orderList [$catalogItem->ctcatn]->oroqty);
				// $totWeight += ($this->catalog->getCatalogWeight ($catalogItem->ctcatn) * $orderList [$catalogItem->ctcatn]->oroqty);
				$this->setFormListData ($template, $this->order, $orderList [$catalogItem->ctcatn]);
			} else {  // New order
				// $template->setVariable('orordt', date ("n/d/y"));
			}
			$template->setVariable('OrderRowId', ++$OrderRowId);

			if (!$orderModifiable)
			{
				$template->setVariable('ReadOnly', $READ_ONLY_ATTRIBUTE);
			}
			$template->setVariable('ctwght', $itemWeight);
			$template->parseCurrentBlock("orderItemBlock");
			$template->setVariable('LineId', $totCount);

			$rowCount++;
			$totCount++;

			if (($rowCount > $MIN_ORDERFORM_COLUMN and $rowCount >= ceil ($numCatalog / 2)) or $totCount == $numCatalog or $catalogItem->ctcatn == 9999926)
			{
				$template->setVariable('OrderColumnNum', $i+1);
				$template->parse("orderColumnBlock");
				$template->setCurrentBlock("orderColumnBlock");

				$rowCount = 0;
				$i++; 
			}
		}
		$template->parse("orderColumnBlock");
		
		$this->processSubmitButtons ($template, $orderModifiable);

		if (!$orderModifiable)
		{
			$template->setVariable('ReadOnlyYN', "Y");
			$template->setVariable('ReadOnly', $READ_ONLY_ATTRIBUTE);
		} else {
			$template->setVariable('ReadOnlyYN', "N");
		}
		$template->setVariable('OrderWeight', number_format ($totWeight, 0));
		if ($totWeight > $OVER_WEIGHT)
		{
			$template->setVariable('ExtraClasses', $OVER_WEIGHT_CLASS);
		}
		$this->processOrderCommentsForm ($template, $orderModifiable);
		
		$template->setVariable('cmd', $this->cmd);
		$this->doFinalTemplateWork ($template);
		$template->parse ();
		
		// Back to main template
		$t->setVariable('Content', $template->get());
	}
	
	function processSpecials (&$t, $catalogItem)
	{
		global $READ_ONLY_ATTRIBUTE, $SPECIAL_DISPLAY, $FIELD_AUTO_FILL;

		if ($catalogItem->Special == "Y")
		{
			$t->setVariable('ReadOnly', $READ_ONLY_ATTRIBUTE);
			$t->setVariable('oroqty', $SPECIAL_DISPLAY);
			$t->setVariable('display', $FIELD_AUTO_FILL);
		}
	}
	function orderBHForm (&$t)
	{
		global $ORDERMNGR_ORDER_FORM_BH_TEMPLATE, $TEMPLATE_DIR, $VIEW_FORM_MODULE, 
		       $ORDER_FORM_COLUMNS, $DEFAULT_DROP, $OVER_WEIGHT, $OVER_WEIGHT_CLASS, 
			   $SHIP_DATE_ADVANCE, $MODIFY_ORDER_ADVANCE,
			   $MODIFY_ORDER_ADVANCE, $READ_ONLY_ATTRIBUTE, $DISPLAY_NONE, $SELECTED, $BOLD,
			   $MIN_ORDERFORM_COLUMN;
		global $CONTRACTOR_LOGO;

		// Content
		$this->debug ("main content: $ORDERMNGR_ORDER_FORM_BH_TEMPLATE");
		$template = new HTML_Template_IT($TEMPLATE_DIR);
		$template->loadTemplatefile($ORDERMNGR_ORDER_FORM_BH_TEMPLATE, true, true);

		$this->form = new Form ($this->dbi, $VIEW_FORM_MODULE, 'OrderMngr');
		$this->catalog = new Catalog ($this->dbi);
		
		// Admin has no order restrictions
		if ($this->isAdmin ())
		{
			$MaxWeight = 9999999; // no weight limit
			$StdShipRequestDays = 0; // no minimum date to change
			$StdMinShipRequestDays = -999;
			$MinQtyRequired = 0;
		} else {
			$MaxWeight = $OVER_WEIGHT;
			$StdShipRequestDays = $SHIP_DATE_ADVANCE;
			$StdMinShipRequestDays = $MODIFY_ORDER_ADVANCE;
			$MinQtyRequired = 1;
		}
		$InvoiceWarning = $this->order->isInvoice () ? 'TRUE' : 'FALSE';

		// display Distributor Order info
		$childInfo = new Distributor ($this->dbi, $this->child);
		$this->setFormData ($template, $childInfo);
		$template->setVariable ('dsdisn', $this->child);
		// $template->setVariable ('dsname', $childInfo->dsname);
		$template->setVariable ('orordn', $this->orordn);
		$template->setVariable ('BHLogo', $CONTRACTOR_LOGO);
		$template->setVariable ('orordt', date ('m/d/y'));
		$template->setVariable ('MaxWeight', $MaxWeight);
		$template->setVariable ('StdShipRequestDays', $StdShipRequestDays);
		$template->setVariable ('StdMinShipRequestDays', $StdMinShipRequestDays);
		$template->setVariable ('MinQtyRequired', $MinQtyRequired);
		$template->setVariable ('InvoiceWarning', $InvoiceWarning);
		
		// display drop list
		// Not doing drops at this time'
		//$this->processDrop ($template)

		// display Order columns
		
		$template->setCurrentBlock("orderColumnBlock");
		$orderFormTemplateItems = $this->order->getBHCatalogList ('getOrderFormTemplateFormFieldList', $this->distributor->OrderFormCode);

		$orderList = $this->order->getOrderInfoList ($this->orordn);
		$numCatalog = count ($orderFormTemplateItems);

		$rowCount = 0;
		$totCount = 0;
		$i = 0;
		$totWeight = 0.0;

		$orderModifiable = true;
		$year = date ("Y");
		if ($this->cmd == "add")
		{
			// Setup ship date dropdown
			// Only want to allow orders for 1 year
			
			$month = date ("n");
			$shipDate = date_create ();
			$advShipStr = "+$SHIP_DATE_ADVANCE days";
			date_modify ($shipDate, $advShipStr);
			$shipDay = date_format ($shipDate, "w");
			if ($shipDay == 0 or $shipDay == 6) // Sunday or Saturday
			{
				if ($shipDay == 0) $mod = 1;
				else if ($shipDay == 6) $mod = 2;
				date_modify ($shipDate, '+'.$mod.' day');
			}
			$fmtShipDate = date_format ($shipDate, "n-j-Y");
		} else {   // Date > $MODIFY_ORDER_ADVANCE for non-admins?
			if ($this->order->orstat == "I") 
			{
				/* Allow invoiced items to be modified
				 * $orderModifiable = FALSE;
				*/
			}
			$fmtShipDate = date_format (date_create($this->order->orrqdt), "n-j-Y");
		}
		$dtArray = explode ('-', $fmtShipDate);
		// $dtArray = getdate (strtotime ($shipDate));

		$this->year_arr = array ($year, $year + 1); // total of one year
		$this->setupDateSelector($template, 'Ship', $dtArray [0], $dtArray [1], $dtArray [2]);		

		$OrderRowId = 0;
		foreach ($orderFormTemplateItems as $catalogItem)
		{
			$template->setCurrentBlock("orderItemBlock");
			$this->setFormListData ($template, $this->catalog, $catalogItem);

			$this->processSpecials ($template, $catalogItem);
			$itemWeight = $this->catalog->getCatalogWeight ($catalogItem->ctcatn);
			if (!$catalogItem->ctcatn)
			{
				$template->setVariable ('display', $DISPLAY_NONE);
				$template->setVariable ('special', $BOLD);
			} else if (is_array ($orderList) and array_key_exists ($catalogItem->ctcatn, $orderList))
			{
				$totWeight += ($itemWeight * $orderList [$catalogItem->ctcatn]->oroqty);

				$this->setFormListData ($template, $this->order, $orderList [$catalogItem->ctcatn]);
			} else {  // New order
				// $template->setVariable('orordt', date ("n/d/y"));
			}
			$template->setVariable('OrderRowId', ++$OrderRowId);

			if (!$orderModifiable)
			{
				$template->setVariable('ReadOnly', $READ_ONLY_ATTRIBUTE);
			}
			$template->setVariable('ctwght', $itemWeight);
			$template->setVariable('MinQty', 1);
			$template->parseCurrentBlock("orderItemBlock");
			$template->setVariable('LineId', $totCount);

			$rowCount++;
			$totCount++;

			if (($rowCount > $MIN_ORDERFORM_COLUMN and $rowCount >= ceil ($numCatalog / 2)) or $totCount == $numCatalog or $catalogItem->ctcatn == 9999926)
			{
				$template->setVariable('OrderColumnNum', $i+1);
				$template->parse("orderColumnBlock");
				$template->setCurrentBlock("orderColumnBlock");

				$rowCount = 0;
				$i++; 
			}
		}
		$template->parse("orderColumnBlock");
		
		$this->processSubmitButtons ($template, $orderModifiable);

		if (!$orderModifiable)
		{
			$template->setVariable('ReadOnlyYN', "Y");
			$template->setVariable('ReadOnly', $READ_ONLY_ATTRIBUTE);
		} else {
			$template->setVariable('ReadOnlyYN', "N");
		}
		$template->setVariable('OrderWeight', number_format ($totWeight, 0));
		if ($totWeight > $OVER_WEIGHT)
		{
			$template->setVariable('ExtraClasses', $OVER_WEIGHT_CLASS);
		}
		$this->processOrderCommentsForm ($template, $orderModifiable);
		
		$template->setVariable('cmd', $this->cmd);
		$this->doFinalTemplateWork ($template);
		$template->parse ();
		
		// Back to main template
		$t->setVariable('Content', $template->get());
	}
	
	function processDrop (&$t)
	{
/* Not doing drops at this time'

		$t->setCurrentBlock("dropBlock");
		$drops = $this->distributor->getDropbyDistributorList ();
		
		foreach ($drops as $key => $drop)
		{
			$t->setVariable ('ordrop', $key);
			$t->setVariable ('DropDisplay', $drop);
			if ((isset ($this->order->ordrop) and $this->order->ordrop == $key) or $key == $DEFAULT_DROP) $select = $SELECTED;
			else $select = "";
			$t->setVariable ('selected', $select);
			$t->parseCurrentBlock("dropBlock");
		}
*/		
	}
	function processSubmitButtons (&$t, $orderModifiable = FALSE)
	{
		global $ADD_ORDER_SUBMIT, $MODIFY_ORDER_SUBMIT, $DISPLAY_NONE;

		$disableSubmit = "";
		$disableDelete = "";
		$disableDisplay = "";
		$disableMain = "";
		$disableReset = "";
		if (!$orderModifiable)
		{
			$disableSubmit = $DISPLAY_NONE;
			$disableDelete = $DISPLAY_NONE;
			$disableMain   = $DISPLAY_NONE;  // Only displayed for new orders
			$disableReset  = $DISPLAY_NONE;
		} else if ($this->cmd == "add")
		{
			$disableDelete = $DISPLAY_NONE;  // Only displayed for modify orders
			$disableDisplay = $DISPLAY_NONE;
		} else {
			$disableMain   = $DISPLAY_NONE;  // Only displayed for new orders
		}
		$t->setVariable('DisableSubmitButton',  $disableSubmit);
		$t->setVariable('DisableMainButton',    $disableMain);
		$t->setVariable('DisableDisplayButton', $disableDisplay);
		$t->setVariable('DisableDeleteButton',  $disableDelete);
		$t->setVariable('DisableResetButton',  $disableReset);
		if ($this->cmd == "add")
		{
			$t->setVariable('SubmitOrder', $ADD_ORDER_SUBMIT);
		} else if ($this->cmd == "modify")
		{
			$t->setVariable('SubmitOrder', $MODIFY_ORDER_SUBMIT);
		}
	}
	function processOrderCommentsForm (&$t, $orderModifiable = FALSE)
	{
		global $READ_ONLY_ATTRIBUTE, $DISPLAY_NONE, $FIELD_AUTO_FILL;
		
		$fields = $this->order->getOrderCommentFieldList ();
		$commentList = $this->order->getOrderComments ($this->orordn);
			$i = 1;
			foreach ($fields as $f)
			{
				if ($i >= 3) break;
				$t->setVariable('CommentLine', $i);
				if (isset ($this->order->$f))
				{
					$t->setVariable('OrderComment'.$i, $this->order->$f);
				}
				if ($this->cmd == "")
				{
					$t->setVariable('display'.$i, $FIELD_AUTO_FILL);
				}
				$i++;
			}
			if (!$orderModifiable or $this->cmd == "modify") // cannot modify new order comments
			{
				$t->setVariable('ReadOnlyCommentAdd', $READ_ONLY_ATTRIBUTE);
			}
		// exit;
		if ($this->cmd == "add")  // New Order Comments
		{
			$t->setVariable('DisplayOrderAddComment', "");
			$t->setVariable('DisplayOrderModifyComment', $DISPLAY_NONE);
		} else { // Modify Order Comments
			$t->setVariable('DisplayOrderAddComment', $READ_ONLY_ATTRIBUTE);
			$t->setVariable('DisplayOrderModifyComment', "");
		}
	}
	
	function orderDisplayScreen (&$t)
	{
		global $ORDERMNGR_CONTENT_TEMPLATE, $TEMPLATE_DIR;

		// Content
		$this->debug ("main content: $ORDERMNGR_CONTENT_TEMPLATE");
		$template = new HTML_Template_ITX($TEMPLATE_DIR);
		$template->loadTemplatefile($ORDERMNGR_CONTENT_TEMPLATE, false, true);

		$this->orderEntries ($template);

		$template->parse ();
		
		// Back to main template
		$t->setVariable('ManageContent', $template->get());

		return 1;
	}
	
	function orderEntries (&$t)
	{
		global $TEMPLATE_DIR, $MODIFY_ORDER_SHIPPED_DISPLAY;

		$order = new Order ($this->dbi);

		// Are we processing contract orders
		if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
		{
			global $ORDERMNGR_DISPLAY_BH_TEMPLATE;
			
			$displayFieldList = 'getOrderBHDisplayFieldList';
			$displayColumnList = 'getOrderBHDisplayColumnList';
			$orderListFunction = 'getBHOrderList';
			$orderNumberChar = 'B';
			$orderDisplayTemplate = $ORDERMNGR_DISPLAY_BH_TEMPLATE;
		} else {
			global $ORDERMNGR_DISPLAY_TEMPLATE;
			
			$displayFieldList = 'getOrderDisplayFieldList';
			$displayColumnList = 'getOrderDisplayColumnList';
			$orderListFunction = 'getOrderList';
			$orderNumberChar = 'S';
			$orderDisplayTemplate = $ORDERMNGR_DISPLAY_TEMPLATE;
		}
		// Content
		$this->debug ("main content: $orderDisplayTemplate");
		$template = new HTML_Template_IT($TEMPLATE_DIR);

		$template->loadTemplatefile($orderDisplayTemplate, false, true);
		
		$this->sortby = $this->getRequestField('sortby');
		$this->orderList = $order->$orderListFunction ($displayFieldList, $this->userId, $MODIFY_ORDER_SHIPPED_DISPLAY, $this->sortby, $orderNumberChar);
		$fields = $order->$displayFieldList();
		// Not paging at this time
		// $this->pagelinks = $this->paginate($order->getNumEntries (), 'orderDisplay', $this->pagelimit);
		
		$template->setCurrentBlock('headerColumnBlock');
		$Columns = $order->$displayColumnList ();
		$i = 0;
		
		foreach ($Columns as $Column)
		{
			$template->setVariable ('FieldName', $fields [$i]);
			$template->setVariable ('ColumnName', $Column);
			$template->parse ('headerColumnBlock');
			$i++;
		}
		$numColumns = $i;

		// $i = $this->pagestart+1;
		$template->setCurrentBlock('orderEntriesBlock');
		$i = 0;
		foreach ($this->orderList as $key => $entry)
		{
			$template->setVariable ('orordn', $key);
			$template->setVariable ('row', $i);
			
			if ($i == 1) $i = 0;
			else $i++;
			
			$template->setCurrentBlock('dataColumnBlock');
			$this->setFormRowData ($template, $entry, $fields, 'dataColumnBlock');
	
			if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
			{
				$buttonHeader = "BOL - Invoice";
				$buttons = " <a href='{APP_PATH}/{APP_NAME}?cmd=bol&amp;orordn=$entry->orordn' class='BH-ButtonSubmit'>BOL</a>";
				$buttons .= "<a href='{APP_PATH}/{APP_NAME}?cmd=invoice&amp;orordn=$entry->orordn' class='BH-ButtonSubmit'>Invoice</a>";
				$buttonWidth = 120;
				
				$template->setVariable('ButtonHeader', $buttonHeader);
				$template->setVariable('RowButtons', $buttons);
				$template->setVariable('ButtonWidth', $buttonWidth);
			}
			$template->setVariable('NumberColumns', $numColumns);
			$template->setVariable('RND', rand ());
			$template->parse ('orderEntriesBlock');
			// $i++;
		}
		$template->setVariable('DistributorName', $this->distributor->dsname);
		$this->doFinalTemplateWork ($template);
		
		$t->setVariable('Content', $template->get());
	}

	function orderManagerViewScreen (&$t)
	{
		if (count ($this->OrderIdArr) > 1)
		{
			$this->generalError ('SINGLE_ID');
			$this->mainDriver();
			return;
		} else {
			$this->OrderId = $this->OrderIdArr [0];
		}
		global $ORDERMNGR_CONTENT_TEMPLATE, $TEMPLATE_DIR;

		// Content
		$this->debug ("main content: $ORDERMNGR_CONTENT_TEMPLATE");
		$template = new HTML_Template_ITX($TEMPLATE_DIR);
		$template->loadTemplatefile($ORDERMNGR_CONTENT_TEMPLATE, false, true);

		$this->orderEntry ($template);
		
		// Back to main template
		$template->parse ();
		
		$t->setVariable('ManageContent', $template->get());

		return 1;
	}
	
	function orderEntry (&$t)
	{
		global $ORDERMNGR_FORM_TEMPLATE, $TEMPLATE_DIR;

		// Review Template
		$this->debug ("main content: $ORDERMNGR_FORM_TEMPLATE");
		$template = new HTML_Template_ITX($TEMPLATE_DIR);

		$template->loadTemplatefile($ORDERMNGR_FORM_TEMPLATE, false, true);
		$this->orderForm($template);

		$template->setVariable('AdminActions', $template->get());
		$template->setVariable('DisplayOnlyJS', $template->get());
		
		$t->setVariable('Content', $template->get());
		$t->parse ();
		
		return 1;
	}
	
	function displayOrders (&$t, $formFunction)
	{
		global $NUM_ORDERORS;
		
		$formLoop = $this->form->getFormLoopInfo();
		for ($i=1; $i <= $NUM_ORDERORS; $i++)
		{
			$status = $this->order->getOrderFunderInfo ($i);
			if (!$status)
				break;
			$this->$formFunction ($t, $this->order, $formLoop, '', $i);
		}
	}
	
	function orderManagerTemplateScreen (&$t)
	{
		global $ORDERMNGR_CONTENT_TEMPLATE, $TEMPLATE_DIR, $REL_ROOT_PATH,$WMS_LEFT_NAV_TEMPLATE;

		// Content
		$this->debug ("main content: $ORDERMNGR_CONTENT_TEMPLATE");
		$template = new HTML_Template_ITX($TEMPLATE_DIR);
		$template->loadTemplatefile($ORDERMNGR_CONTENT_TEMPLATE, false, true);

		// Left Nav
		$this->leftnavScreen ($template,1,$WMS_LEFT_NAV_TEMPLATE);
		
		// Back to main template
		$template->parse ();
		
		$t->setVariable('ManageContent', $template->get());

		return 1;
	}
	
	function displayInvoice (&$t)
	{
		global $ORDERMNGR_INVOICE_FORM_BH_TEMPLATE, $TEMPLATE_DIR, $CONTRACTOR_LOGO,
		       $VIEW_FORM_MODULE;

		// Content
		$this->debug ("main content: $ORDERMNGR_INVOICE_FORM_BH_TEMPLATE");
		$template = new HTML_Template_ITX($TEMPLATE_DIR);
		$template->loadTemplatefile($ORDERMNGR_INVOICE_FORM_BH_TEMPLATE, false, true);

		$this->form = new Form ($this->dbi, $VIEW_FORM_MODULE, 'OrderMngr');
		$this->catalog = new Catalog ($this->dbi);
		$this->dist = new Distributor ($this->dbi,  $this->order->ordisn);

		$distInfo = $this->dist->dsname."<br />".$this->dist->Address1."<br />".$this->dist->City." ".$this->dist->State." ".$this->dist->Zip;
		$parentInfo = $this->distributor->dsname."<br />".$this->distributor->Address1."<br />".$this->distributor->City." ".$this->distributor->State." ".$this->distributor->Zip;
		
		if ($this->order->isInvoice ())
		{
			 $status = $this->order->getInvoiceInfo ();
			 if (!$this->palqty) $this->palqty = $this->order->NumPallets;
		} else {
			$invoice = $this->order->getMaxBHInvoiceNumber ();
			$this->order->updateBHOrderInfo ($invoice);
			 $this->palqty = 0;
		}
		$invoiceModifiable = true;
		$orderStatus = $this->order->getorstat ();
		if ($orderStatus == "B") 
		{
			global $UNPROCESSED_BYAS400_INV_STATUS;
			
			// Not yet...
			// $this->order->updateOrderStatus ($UNPROCESSED_BYAS400_INV_STATUS);
		} else if ($orderStatus == "I")
		{
			$invoiceModifiable = false;
		}

		// display Distributor Order info
		$this->setFormData ($template, $this->dist);
		$template->setVariable ('orordn', $this->orordn);
		$invDate = date ("m/d/y");
		if (isset ($this->order->InvoiceDate)) $invDate = $this->order->InvoiceDate;
		$template->setVariable ('BHLogo', $CONTRACTOR_LOGO);
		$template->setVariable ('InvoiceDate', $invDate);
		$template->setVariable ('InvoiceNo',$this->order->InvoiceNumber);
		$template->setVariable ('orpono',$this->order->orpono);
		// $template->setVariable ('BillofLading','10001');
		$template->setVariable('InvoicePage', 1);
		$template->setVariable('ParentDistributorInfo', $parentInfo);
		$template->setVariable('SoldTo', $distInfo);
		$template->setVariable('ShipTo', $distInfo);
		
		$this->invoiceEntries ($template);

		if ($this->palqty > 0)
		{
			$template->setVariable('palqty', $this->palqty);
			$PalletAmount = $this->palqty * 10;
			$template->setVariable('PalletAmount', number_format ($PalletAmount, 2));  // hard coded pallet cost
			// $template->setVariable('PalletAmount', number_format ($this->palqty * 10, 2));  // hard coded pallet cost
			$this->invTotal += $PalletAmount;
			$template->setVariable('TotalPrice', number_format ($this->invTotal, 2));
		}
		if (isset ($this->emailTemplate))
		{
			global $BH_EMAIL_INVOICE_STYLE;
			// Not used yet
			$style = $this->getStyle ($BH_EMAIL_INVOICE_STYLE);
			$template->setVariable('InvoiceEmailStyle', $style);
		}
		if (!$invoiceModifiable)
		{
			global $READ_ONLY_ATTRIBUTE;
			$template->setVariable('ReadOnly', $READ_ONLY_ATTRIBUTE);
		}
		$this->doFinalTemplateWork ($template);
		$template->parse ();

		// Back to main template
		if (isset ($this->emailTemplate))
		{
			$t->setVariable('ManageContent', $template->get());
			return $t->get();
		} else {
			$t->setVariable('ManageContent', $template->get());
		}

		return 1;
	}
	
	function invoiceEntries (&$template)
	{
		global $ORDERMNGR_FORM_TEMPLATE, $TEMPLATE_DIR, $VIEW_FORM_MODULE, 
		       $ORDER_FORM_COLUMNS, $DEFAULT_DROP, $OVER_WEIGHT, $OVER_WEIGHT_CLASS, 
			   $SHIP_DATE_ADVANCE, $MODIFY_ORDER_ADVANCE,
			   $MODIFY_ORDER_ADVANCE, $READ_ONLY_ATTRIBUTE, $DISPLAY_NONE, $SELECTED, $BOLD,
			   $MIN_ORDERFORM_COLUMN;

		// $this->orordn = 'U0001';
		$orderList = $this->order->getOrderInfoList ($this->orordn);

		$rowCount = 0;
		$totCount = 0;
		$i = 0;
		$totWeight = 0.0;
		$this->invTotal = 0.0;
		$this->kegCount = 0;
		$caseCount = 0;
		$halfCount = 0;
		$quarterCount = 0;
		$sixthCount = 0;

		$year = date ("Y");

		$OrderRowId = 0;
		$this->catalog = new Catalog ($this->dbi);
		foreach ($orderList as $orderItem)
		{
			if ($orderItem->oroqty == 0) continue;  // do not display
			$template->setCurrentBlock("orderItemBlock");
			$this->setFormListData ($template, $this->order, $orderItem);

 			$totWeight += ($this->catalog->getCatalogWeight ($orderItem->orcatn) * $orderItem->oroqty);
			$prodSize = $this->catalog->getCatalogProductSize ($orderItem->orcatn);
			if ($prodSize == "1/2BL" or $prodSize == "1/4BL" or $prodSize == "1/6BL")
			{
				$this->kegCount += $orderItem->oroqty;
				switch ($prodSize) 
				{
					case '1/2BL':
						$halfCount += $orderItem->oroqty;
						break;
					case '1/4BL':
						$quarterCount += $orderItem->oroqty;
						break;
					case '1/6BL':
						$sixthCount += $orderItem->oroqty;
						break;
				}
			} else {
				$caseCount += $orderItem->oroqty;
			}
			$template->setVariable('ProductSize', $prodSize);
			$template->setVariable('Description', $this->order->getOrderFormLineDescription ($orderItem->orcatn));
			$price = $orderItem->ItemPrice;
			$amount = $price * $orderItem->oroqty;
			$this->invTotal += $amount;
			$template->setVariable('Price',  number_format ($price,2));

			$template->setVariable('Amount',  number_format ($amount,2));
			$template->setVariable('OrderRowId', ++$OrderRowId);
			$this->setFormListData ($template, $this->order, $orderItem);

			$template->setVariable ('row', $i);
			if ($i == 1) $i = 0;
			else $i++;

			$template->parseCurrentBlock("orderItemBlock");
		}
		$template->setCurrentBlock("orderItemBlock");
		
		$kegCost = $this->kegCount * 30;
		$template->setVariable('oroqty', $this->kegCount);
		$template->setVariable('Description', "Keg Deposit Charge");
		$template->setVariable('Price',  number_format (30,2));
		$template->setVariable('Amount',  number_format ($kegCost, 2));
		$this->invTotal += $kegCost;
		
		$template->parseCurrentBlock("orderItemBlock");
		
		$amcAmount = $caseCount * .25 + $halfCount * 1.72 + $sixthCount * .57;
		$this->invTotal += $amcAmount;

		$template->setVariable('AMCAmount', number_format ($amcAmount, 2));

		$template->setVariable('TotalWeight', number_format ($totWeight, 0));
		$template->setVariable('TotalPrice', number_format ($this->invTotal, 2));

/*
		$template->setVariable('cmd', $this->cmd);
		$this->doFinalTemplateWork ($template);
		$template->parse ();
		
		// Back to main template
		$t->setVariable('Content', $template->get());
*/
	}
		
	function displayBOL (&$t)
	{
		global $ORDERMNGR_BOL_FORM_BH_TEMPLATE, $TEMPLATE_DIR, $CONTRACTOR_LOGO,
		       $UNPROCESSED_BYAS400_BOL_STATUS, $VIEW_FORM_MODULE;

		// Content
		$this->debug ("main content: $ORDERMNGR_BOL_FORM_BH_TEMPLATE");
		$template = new HTML_Template_ITX($TEMPLATE_DIR);
		$template->loadTemplatefile($ORDERMNGR_BOL_FORM_BH_TEMPLATE, false, true);

		$this->form = new Form ($this->dbi, $VIEW_FORM_MODULE, 'OrderMngr');
		$this->catalog = new Catalog ($this->dbi);
		$this->dist = new Distributor ($this->dbi,  $this->order->ordisn);

		$distInfo = $this->dist->dsname."<br />".$this->dist->Address1."<br />".$this->dist->City." ".$this->dist->State." ".$this->dist->Zip;
		$parentInfo = $this->distributor->dsname."<br />".$this->distributor->Address1."<br />".$this->distributor->City." ".$this->distributor->State." ".$this->distributor->Zip;

		// display Distributor Order info
		$this->setFormData ($template, $this->dist);
		$template->setVariable ('orordn', $this->orordn);
		$curDate = date ("m/d/y");
		$template->setVariable ('BHLogo', $CONTRACTOR_LOGO);
		$template->setVariable ('orordn', $this->order->orordn);
		$template->setVariable ('orordt', $curDate);
		$fmtShipDate = date_format (date_create($this->order->orrqdt), "n/j/y");
		$template->setVariable ('orrqdt', $fmtShipDate);
		$template->setVariable ('InvoiceNo','10001');
		$template->setVariable ('orpono',$this->order->orpono);
		$template->setVariable ('BillofLading','10001');
		$template->setVariable('InvoicePage', 1);
		$template->setVariable('ParentDistributorInfo', $parentInfo);
		$template->setVariable('SoldTo', $distInfo);
		$template->setVariable('ShipTo', $distInfo);
		
		// $this->bolEntries ($template);

		$orderList = $this->order->getOrderInfoList ($this->orordn);

		$rowCount = 0;
		$totCount = 0;
		$i = 0;
		$bottleWeight = 0.0;
		$kegWeight = 0.0;
		$totAmount = 0.0;
		$this->kegCount = 0;
		$bottleCount = 0;

		$year = date ("Y");

		$OrderRowId = 0;
		$this->catalog = new Catalog ($this->dbi);
		foreach ($orderList as $orderItem)
		{
			if ($orderItem->oroqty == 0) continue;  // do not display this item
			$template->setCurrentBlock("orderItemBlock");
			// $this->setFormListData ($template, $this->order, $orderItem);

			$prodSize = $this->catalog->getCatalogProductSize ($orderItem->orcatn);
			if ($prodSize == "1/2BL" or $prodSize == "1/4BL" or $prodSize == "1/8BL")
			{
				$this->kegCount += $orderItem->oroqty;
 				$kegWeight += $this->catalog->getCatalogWeight ($orderItem->orcatn) * $orderItem->oroqty;
			} else {
				$bottleCount += $orderItem->oroqty;
	 			$bottleWeight += $this->catalog->getCatalogWeight ($orderItem->orcatn) * $orderItem->oroqty;
			}
			$template->setVariable('PKGQuantity', $orderItem->oroqty);
			$template->setVariable('Weight', $this->catalog->getCatalogWeight ($orderItem->orcatn) * $orderItem->oroqty);
			$template->setVariable('Description', $this->order->getOrderItemDescription ($orderItem->orcatn));
			$template->parseCurrentBlock("orderItemBlock");
		}
/* Replacing w/ actual line items above
		$template->setCurrentBlock("orderItemBlock");
		$template->setVariable('PKGQuantity', $bottleCount);
		$template->setVariable('PKGType', 'Bottles');
		$template->setVariable('Weight', $bottleWeight);
		$template->setVariable('Description', 'Cases of Bottled Beer:');
		$template->parseCurrentBlock("orderItemBlock");
		
		$template->setCurrentBlock("orderItemBlock");
		$template->setVariable('PKGQuantity', $this->kegCount);
		$template->setVariable('PKGType', 'Kegs');
		$template->setVariable('Weight', $kegWeight);
		$template->setVariable('Description', 'Kegs of Beer:');
		$template->parseCurrentBlock("orderItemBlock");
*/
		$template->setVariable('TotalPKGQuantity', $bottleCount + $this->kegCount);
		$template->setVariable('TotalPKGType', 'Pcs');
		$template->setVariable('TotalWeight', $bottleWeight + $kegWeight);

		$template->setVariable('ShipTo', $distInfo);
		$this->doFinalTemplateWork ($template);
		$template->parse ();
		
//		if ($this->order->orstat == "U") 
//		{
// always update status
			$this->order->updateOrderStatus ($UNPROCESSED_BYAS400_BOL_STATUS);
//		}
		// Back to main template
		if (isset ($this->emailTemplate))
		{
			$t->setVariable('ManageContent', $template->get());
			return $t->get();
		} else {
			$t->setVariable('ManageContent', $template->get());
		}
		return 1;
	}
	
		function emailBOL ()
		{
			global $BH_FROM_EMAIL, $BCC_BH_BOL_EMAIL_ALERT, $WMS_EMAIL_BOL_INDEX_TEMPLATE, $ORDER_MNGR, 			       $CLIENT_INDEX_TEMPLATE, $TEST_MODE, $TEST_MODE_EMAIL;

			$this->orordn = $this->getRequestField('orordn'); 

			if ($TEST_MODE)
			{// Send only to Tester
				$to = $TEST_MODE_EMAIL;
			} else {
				// Send all emails
				$to = "$BCC_BH_BOL_EMAIL_ALERT";
			}

			$crlf = "\n";
			$from = $BH_FROM_EMAIL;
			$this->emailTemplate = true;
			$this->orpono = $this->order->orpono;
			$html = $this->getScreen($WMS_EMAIL_BOL_INDEX_TEMPLATE, 'displayBOL', $ORDER_MNGR);

			$mime = new Mail_mime(array('eol' => $crlf));
			$text = "you must be able to view HTML documents to see this email";
			$hdrs = array(
						  'From'    => $from,
						  'Subject' => 'BOL'
						  );
			$mime->setTXTBody($text);
			$mime->setHTMLBody($html);
			// $mime->addAttachment($file, 'text/plain');
			
			$body = $mime->get();
			$hdrs = $mime->headers($hdrs);
			$mail =& Mail::factory('mail');
			$mail->send($to, $hdrs, $body);
			
			$this->ordStatus = $this->getMessage('BOL_EMAILED');
			$this->showScreen($CLIENT_INDEX_TEMPLATE, 'statusScreen', $ORDER_MNGR);
		}
	
	function statusScreen (&$t)
	{
		global $ORDERMNGR_STATUS_TEMPLATE, $MAIN_LEFT_NAV_TEMPLATE, $TEMPLATE_DIR,
		$INDEX_FILENAME;
		
		// Content
		$this->debug ("main content: $ORDERMNGR_STATUS_TEMPLATE");
		$template = new HTML_Template_ITX($TEMPLATE_DIR);

		$template->loadTemplatefile($ORDERMNGR_STATUS_TEMPLATE, false, true);

		$template->setVariable('STATUS_MESSAGE', $this->ordStatus);
		$this->doFinalTemplateWork($template);

		$template->parse ();

		// Back to main template
		$t->setVariable('ManageContent', $template->get());

		return 1;
	}
	
		function selectUserScreen (&$t)
		{
			global $ORDERMNGR_SELECT_USER_TEMPLATE, $TEMPLATE_DIR;
	
			$this->debug ("main content: $ORDERMNGR_SELECT_USER_TEMPLATE");
			$template = new HTML_Template_IT($TEMPLATE_DIR);
	
			$template->loadTemplatefile($ORDERMNGR_SELECT_USER_TEMPLATE, false, true);
			
			$distributorList = $this->distributor->getDistributorChildList ();
			
			$template->setCurrentBlock('distributorBlock');
			foreach ($distributorList as $dsdisn => $dsname)
			{
				$template->setVariable ('Distributor', $dsdisn." - ".$dsname);
				$template->setVariable ('dsdisn', $dsdisn);
				$template->parse ('distributorBlock');
			}
			$this->doFinalTemplateWork ($template);
			$template->parse ();
			
			$t->setVariable('ManageContent', $template->get());
	
			return 1;
		}

		function emailInvoice ()
		{
			global $BH_FROM_EMAIL, $BCC_BH_EMAIL_ALERT, $WMS_EMAIL_INDEX_TEMPLATE, $ORDER_MNGR, 			       $CLIENT_INDEX_TEMPLATE, $TEST_MODE, $TEST_MODE_EMAIL;

			$this->orordn = $this->getRequestField('orordn'); 
			$this->palqty = $this->getRequestField('palqty', 0); 
			$this->InvoiceDate = $this->getRequestField('InvoiceDate'); 

/** Removing because we'll assume that they have JS enabled. 
 ** PHPAppliction class considers "0" as empty for getRequestField (above).
 ** We want to allow 0 pallets
 **
			if ($this->palqty == 0)
			{
				$this->alert (NO_PALLET_QTY);
				exit;
			}
*/
			$distChild = new Distributor ($this->dbi,  $this->order->ordisn);
			// $to = $this->distributor->dsemal;
			if ($TEST_MODE)
			{// Send only to Tester
				$to = $TEST_MODE_EMAIL;
			} else {
				// Send all emails
				$to = $this->distributor->dsemal.",".$distChild->dsemal;
				$to .= ",$BCC_BH_EMAIL_ALERT";
			}
			$crlf = "\n";
			$from = $BH_FROM_EMAIL;
			$this->emailTemplate = true;
			$this->orpono = $this->order->orpono;
			$html = $this->getScreen($WMS_EMAIL_INDEX_TEMPLATE, 'displayInvoice', $ORDER_MNGR);

			$mime = new Mail_mime(array('eol' => $crlf));
			$text = "you must be able to view HTML documents to see this email";
			$hdrs = array(
						  'From'    => $from,
						  'Subject' => 'Invoice'
						  );
			$mime->setTXTBody($text);
			$mime->setHTMLBody($html);
			// $mime->addAttachment($file, 'text/plain');
			
			$body = $mime->get();
			$hdrs = $mime->headers($hdrs);

			$mail =& Mail::factory('mail');
			$mail->send($to, $hdrs, $body);
			
			if ($this->order->getorstat () == "B") 
			{
				global $UNPROCESSED_BYAS400_INV_STATUS;
				
				$this->order->updateOrderStatus ($UNPROCESSED_BYAS400_INV_STATUS);
			}
			/* Update Invoice Pallet Quantity and Keg Counts */
			
			$this->order->getInvoiceInfo ();
			$this->order->updateBHOrderInfo ($this->order->InvoiceNumber, $this->InvoiceDate, $this->palqty, $this->kegCount);
			if ($this->order->isInvoiceHistory ($this->order->InvoiceNumber))
			{
				// Already exists, do not insert again
				
				$status = MDB2_OK;
			} else {
				// New entry for Invoice History
				$status = $this->order->addBHInvoice ();
			}
			// $this->order->updateBHOrderInfo ($invoice, $this->palqty, $this->kegCount);
			if ($status != MDB2_OK) 
			{
				$this->ordStatus .= $this->getMessage('INVOICE_ERROR');
			} else {
				$this->ordStatus = $this->getMessage('INVOICE_EMAILED');
			}
			$this->showScreen($CLIENT_INDEX_TEMPLATE, 'statusScreen', $ORDER_MNGR);
/*			// return to invoice display
				global $WMS_INDEX_TEMPLATE;

				$this->debug ("ordermngr: " . $WMS_INDEX_TEMPLATE);
				$this->showScreen($WMS_INDEX_TEMPLATE, 'displayInvoice', $ORDER_MNGR);
*/			
		}
	
	function status (&$t)
	{
		global $ORDERMNGR_STATUS_TEMPLATE, $TEMPLATE_DIR, $INDEX_FILENAME;
		
		$this->debug ("main content: $ORDERMNGR_STATUS_TEMPLATE");
		$template = new HTML_Template_ITX($TEMPLATE_DIR);

		$template->loadTemplatefile($ORDERMNGR_STATUS_TEMPLATE, false, true);

		$template->setVariable ('LivePage', $INDEX_FILENAME);

		$this->doFinalTemplateWork($template);
		
		$t->setVariable('Content', $template->get());
		$t->parse ();
		
		return 1;
	}
	
	function isAdmin ()
	{
		$auth = new Authentication ();

		if (!isset ($_SESSION[APP_NAME."_ADMIN"]))
		{
			return $auth->isSpecial ($this->UserName);
		} else {
			return $auth->isAdmin ($_SESSION[APP_NAME."_ADMIN"]);
	}
	}

	function authorize()
	{
		return true;
	}
}//class

global $APP_DB_URL, $ORDER_MNGR;

$thisApp = new OrderMngrApp (array ('appName'	      => $APPLICATION_NAME,
									  'appUrl'	      => $ORDER_MNGR,
									  'appVersion'    => '1.0.2',
									  'appType'	      => 'WEB',
									  'appDbUrl'      => $APP_DB_URL,
									  'appAutoAuthorize'    => TRUE,
									  'appAutoConnect'      => TRUE,
									  'appAutoCheckSession' => TRUE,
									  'appDebugger'   => $OFF,
							  		  'cacheType'   => "BROWSER"
									 )
							  );

$thisApp->bufferDebugging();
$thisApp->debug("This is $thisApp->appName application");
$thisApp->run();
$thisApp->dumpDebuginfo();

?>