<?php

/*
	ARG1			ARG2	FUNCTION
	toas400					toAS400
	fromAS400				fromAS400
	order			as400	toAS400Order
	order			web		fromAS400Order
	distributor		as400	toAS400Distributor
	distributor		web		fromAS400Distributor
	ordertemplate	(val)	updateOrderTemplate  -- Blank or pb for Client - bh for Contractor
	catalog			----	replaceCatalog
	prices			----	fromAS400Pricing
	comments		as400	toAS400OrderComments
	comments		web		fromAS400OrderComments
	toas400			----	toAS400
	fromas400		----	fromAS400
	confirm			----	confirmOrder
	purge			(val)	purgeUnprocessedLineItems -- blank for $PURGE_NOAS400_DAYS (default)
*/
	require_once "as400_cron.conf.php";
	
	class toas400App extends PHPApplication {
	
		function run()
		{
			if (isset ($_REQUEST['arg1'])) {
				$this->msg = "Running from web<br />Args: ".print_r ($_REQUEST['arg1'],true)."<br />";
				$args[1] = strtolower ($_REQUEST['arg1']);
				$args[2] = isset ($_REQUEST['arg2']) ? strtolower ($_REQUEST['arg2']) : "";
			} else if (isset ($_SERVER['argv']))
			{
				$this->msg = "Running from shell\nArgs: ".print_r ($_SERVER['argv'],true)."\n";
				$args =  $_SERVER['argv'];
			}
			$this->debug ( "args: ".print_r($args, true));

			$this->dataTransfer = new DataTransfer ($this->dbi);
			$this->dataTransfer->setIsProcessingData ();
			
			if (!strcmp($args [1], 'order'))
			{
				if (!strcmp($args [2], 'as400'))
				{
					$this->toAS400Order ();
				} else if (!strcmp($args [2], 'web'))
				{
					$this->fromAS400Order ();
				}
			} else if (!strcmp($args [1], 'distributor'))
			{
				if (!strcmp($args [2], 'as400'))
				{
					$this->toAS400Distributor ();
				} else if (!strcmp($args [2], 'web'))
				{
					$this->fromAS400Distributor ();
				}
			} else if (!strcmp($args [1], 'ordertemplate'))
			{
echo "OrderTemplate ".print_r ($args,true)."<br />";
				if ($args [2] == "" or !strcmp ($args [2], 'pb'))
				{
echo "Processing PB OrderTemplate<br />";
					$this->updateOrderTemplate ();
				} if (!strcmp ($args [2], 'bh')) {
					$this->updateBFOrderTemplate ();
				}
			} else if (!strcmp($args [1], 'catalog'))
			{
				if (!strcmp ($args [2], 'update'))
				{
					$this->updateCatalog ();
				} else {
					$this->replaceCatalog ();
				}
			} else if (!strcmp($args [1], 'prices'))
			{
				$this->fromAS400Pricing ();
			} else if (!strcmp($args [1], 'comments'))
			{
				if (!strcmp($args [2], 'as400'))
				{
					$this->toAS400OrderComments ();
				} else if (!strcmp($args [2], 'web'))
				{
					$this->fromAS400OrderComments ();
				}
			} else if (!strcmp(strtolower ($args [1]), 'toas400'))
			{
					$this->toAS400 ();
			} else if (!strcmp(strtolower ($args [1]), 'fromas400'))
			{
					$this->fromAS400 ();
			} else if (!strcmp(strtolower ($args [1]), 'confirm'))
			{
					$this->confirmOrder (strtoupper ($args[2]));
			} else if (!strcmp(strtolower ($args [1]), 'purge'))
			{
					$this->purgeUnprocessedLineItems ($args[2]);
			} else {
				mail ('me@mydomain.com', 'Error in as400_cron.php',$this->msg, 'from:me@mydomain.com');
			}
		}
		
		function toAS400 ()
		{
			$this->toAS400Distributor ();
			$this->toAS400Order ();
			$this->toAS400OrderComments ();
			echo "To AS 400 is complete\n<br />";
		}
		
		function fromAS400 ()
		{
			$this->fromAS400Distributor ();
			$this->replaceCatalog ();
			$this->fromAS400Pricing ();
			$this->updateOrderTemplate ();
			$this->updateBFOrderTemplate ();
			$this->fromAS400Order ();
			echo "fromAS400Order<br />"; 
			$this->purgeUnprocessedLineItems ();
			$this->dataTransfer->unsetIsProcessingData ();
		}
		
		function confirmOrder ($args=null)
		{
			global $MODIFY_ORDER_SUBJECT;
			
			if (!$args) return false;
			
			$orders = explode (',', $args);
			// $orders = split (',', $args);
			// echo "orders: ".print_r ($orders,true)."<br />";
			
			$this->order = new Order ($this->dbi);
			$this->catalog = new Catalog ($this->dbi);
			
			foreach ($orders as $order)
			{
				$this->order->getOrderInfo ($order);
				$this->sendOrderConfirmationEmail ($order, $MODIFY_ORDER_SUBJECT);
			}
		}
		// Purges all unprocessed Order line items more than $purgeDays old
		// Default value for $purgeDays is $PURGE_NOAS400_DAYS
		//
		function purgeUnprocessedLineItems ($purgeDays = null)
		{
			if (!$purgeDays)
			{
				global $PURGE_NOAS400_DAYS;
				$purgeDays = $PURGE_NOAS400_DAYS;
			}
			$order = new Order ($this->dbi);
			echo "Order Lines not processed: ".$order->countPurgeUnprocessedLineItems ('getOrderInfoFieldList', $purgeDays)."<br />";
			$order->purgeUnprocessedLineItems ('getOrderDisplayFieldList', $purgeDays);
			
			$order->purgeUnprocessedOrderComments ();
			echo "Finished purging Order Lines not processed<br />";
		}
		function toAS400Order ()
		{
			global $TO_AS400_ORDERS, $ROOT_APP_PATH, $CSV_DELIMINATOR, $CSV_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;

			$this->order = new Order ($this->dbi);
			
			$outputOrderList = $this->order->getCSVOrderList ($this->dataTransfer->OrderToAS400);

			$filename = $ROOT_APP_PATH."/".$TO_AS400_ORDERS;
			$this->msg =  "File: $filename\r\n<br />";
			echo $this->msg."<br />";
			if ($this->processToCSVFile ($filename, $outputOrderList) > 0)
			{
				$this->dataTransfer->updateDataTransfer ('OrderToAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
				/* add process date/time stamp here */
			} else {
				if (isset ($this->err))
				{
					$this->emailError ($TO_AS400_ORDERS);
					unset ($this->err);
				}
			}
		}
		
		function toAS400OrderComments ()
		{
			global $TO_AS400_ORDER_COMMENTS, $ROOT_APP_PATH, $CSV_DELIMINATOR, $CSV_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;

			$this->order = new Order ($this->dbi);
			
			$outputOrderCommentsList = $this->order->getCSVOrderCommentsList ($this->dataTransfer->OrderToAS400);
			
			$filename = $ROOT_APP_PATH."/".$TO_AS400_ORDER_COMMENTS;
			$this->msg =  "File: $filename\r\n<br />";
			echo $this->msg."<br />";
			if ($this->processToCSVFile ($filename, $outputOrderCommentsList) > 0)
			{
				$this->dataTransfer->updateDataTransfer ('OrderCommentsToAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
				/* add process date/time stamp here */
			} else {
				if (isset ($this->err))
				{
					$this->emailError ($TO_AS400_ORDER_COMMENTS);
					unset ($this->err);
				}
			}
		}
		
		function fromAS400Order ()
		{
			global $FROM_AS400_ORDERS, $ROOT_APP_PATH, $CSV_DELIMINATOR, $CSV_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT, $ADD_ORDER_SUBJECT, $MODIFY_ORDER_SUBJECT;
				   
			// start & end are for debug purposes so we don't have to go through ALL records			
			$this->start = $this->getRequestField('start', 0);
			$this->end = $this->getRequestField('end');
			$this->msg .= "Start: ".$this->start." End: <br />";

			$this->order = new Order ($this->dbi);
			$fields = $this->order->getOrderCSVFieldList ();
			
			$filename = $ROOT_APP_PATH."/".$FROM_AS400_ORDERS;
			$fh = fopen ($filename, "r");
			echo "File: $filename\r\n<br />";
			$this->msg .=  "File: $filename\r\n<br />";
			$first = true;
			$curOrder = "";
			$webStat = FALSE;
			$curStatus = $lastStatus = "";
			$stat = "";
			if ($fh) 
			{
				$j = 0;
				while ($this->start > $j) // Debugging - start with record at start
				{
					$line = fgets($fh, 4096);
					$j++;
				}
				echo "Skipped $j Records<br />";
				$start = date_create ();
				while (!feof($fh)) 
				{
					$j++;
					$this->msg .= "$j, ";
					$i =0; 
					$line = fgets($fh, 4096);
					if (strlen (trim ($line)) == 0) continue;
					if ($this->start > $j) continue; // Debugging - start with record at start
					if (isset ($this->end) and $this->end > $j) continue; // Debugging - end with record at end
					$values = explode ($CSV_DELIMINATOR, $line);					
					// $values = split ($CSV_DELIMINATOR, $line);					

					foreach ($values as $value)
					{
						if (count ($fields) <= $i) break; // skip extra fields in file
						$this->{$fields[$i]} = trim ($value, $CSV_QUOTE." ");
						$i++;
					}
					if ($this->orwebn == " ")
					{
						$this->orwebn = "";
					}
					$hash = $this->processFormData ($this->order, 'getOrderCSVFieldList', 'orderTblFields');  // We're not processing a form, but that's the method name for getting data ready for insert/update in the db.
					$subject = "";

					if (!isset ($hash->ItemPrice)) $hash->ItemPrice = 0;
					if ($this->order->isOrderEntry ($this->orordn, $this->orcatn, $this->orwebn))
					{
						$lastStatus = $curStatus;
						$curStatus = $this->order->getOrderLineStatus($this->orwebn, $this->orcatn);
						$this->order->updateOrder ($hash);
						$subject = $MODIFY_ORDER_SUBJECT;
					} else {
						$this->order->addOrder ($hash);
						$subject = $ADD_ORDER_SUBJECT;
						// $this->order->sendOrderConfirmationEmail ($ADD_ORDER_SUBJECT);
					}
					if ($curOrder != $this->orordn)
					{
						if ($curOrder != "" and $webStat)
						{
							$this->order->updateOrderNumber ($curWebOrder, $curOrder);
							$this->sendOrderConfirmationEmail ($curOrder, $subject);
							$webStat = FALSE;
						}
						$curOrder = $this->orordn;
						$curWebOrder = $this->orwebn;
						$first = true;
						$stat .= "Web: $curWebOrder, AS400: $curOrder\n";
					}
					if ($curStatus == "N") {$webStat = TRUE; }
					if ($j != 0 and fmod ($j, 500) < 0.00001)
					{
						set_time_limit (900);
						echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
					}
				}
				if ($webStat)
				{
					$this->order->updateOrderNumber ($curWebOrder, $curOrder);
					$this->sendOrderConfirmationEmail ($curOrder, $subject);
				}
				$this->msg .=  "Processed $j records\r\n<br />";
				echo "Processed $j records\r\n<br />";
				fclose ($fh);
				$this->dataTransfer->updateDataTransfer ('OrderFromAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
			} else {
				$this->emailError ($FROM_AS400_ORDERS);
			}
			$this->debug ( "Msg: [".$this->msg."]");
		}
		
		function updateOrderComments ()
		{
			$this->order->updateOrderNumber ($this->orwebn, $this->orordn);
		}
		
		function sendOrderConfirmationEmail ($curOrder,$subject)
		{
			global $ORDER_CONFIRMATION_TEMPLATE, $MODIFY_ORDER_SUBJECT, $BCC_EMAIL_ALERT,
			       $FROM_EMAIL, $TEMPLATE_MNGR;
			
			// Content
			$this->debug ("main content: $ORDER_CONFIRMATION_TEMPLATE");
			$template = new HTML_Template_IT($this->getTemplateDir($TEMPLATE_MNGR));
			$template->loadTemplatefile($ORDER_CONFIRMATION_TEMPLATE, true, true);
			
			$this->comm = $this->order->getOrderComments ($curOrder);
			$this->catalog = new Catalog ($this->dbi);
			
			$distributorOrder = $this->order->getDistributorOrderInfo($curOrder);

			$distributor = new Distributor ($this->dbi, $distributorOrder->ordisn);

			$distEmail = $distributor->getEmail ();
			$template->setVariable ('dsdisn', $distributorOrder->ordisn);
			$template->setVariable ('dsname', $distributor->dsname);
			$template->setVariable ('ordrop', $distributorOrder->ordrop);
			$template->setVariable ('orordn', $distributorOrder->orordn);
			$template->setVariable ('orordt', $distributorOrder->orordt);
			$template->setVariable ('orpono', $distributorOrder->orpono);
			$template->setVariable ('orrqdt', $distributorOrder->orrqdt);
			$template->setVariable ('occom1', isset ($this->order->occom1)?  $this->order->occom1: "");
			$template->setVariable ('occom2', isset ($this->order->occom2)?  $this->order->occom2: ""); 
			$template->setVariable ('occhg1', isset ($this->order->occhg1)?  $this->order->occhg1: "");
			$template->setVariable ('occhg2', isset ($this->order->occhg2)? $this->order->occhg2: "");
	
			$template->setCurrentBlock("orderItemBlock");
			
			$orderItems = $this->order->getOrderInfoList($curOrder, "orordn");
	
			foreach ($orderItems as $key => $orderItem)
			{
				$template->setVariable ('orcatn', $key);
				$template->setVariable ('Description', str_pad ($this->order->getOrderItemDescription ($key), 51));
				$template->setVariable ('oroqty', $orderItem->oroqty);
				$template->parseCurrentBlock("orderItemBlock");
			}
			$template->parse ();
	
			$bcc = $BCC_EMAIL_ALERT;
			$this->order->sendOrderConfirmationEmail ($distributor->getEmail(), $MODIFY_ORDER_SUBJECT.$curOrder, $template->get(), $FROM_EMAIL, $bcc);
		}
	
		function fromAS400OrderComments ()
		{
			global $FROM_AS400_ORDER_COMMENTS, $ROOT_APP_PATH, $CSV_DELIMINATOR, $CSV_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;
			
			$this->order = new Order ($this->dbi);
			$fields = $this->order->getOrderCommentsCSVFieldList ();
			
			$filename = $ROOT_APP_PATH."/".$FROM_AS400_ORDER_COMMENTS;
			$fh = fopen ($filename, "r");
			$this->msg =  "File: $filename\r\n<br />";
			echo "File: $filename\r\n<br />";
			$first = true;
			$curOrder = "";
			if ($fh) 
			{
				$j = 0;
				$start = date_create ();
				while (!feof($fh)) 
				{
					$i =0; 
					$line = fgets($fh, 4096);
					if (strlen (trim ($line)) == 0) continue;
					$values = explode ($CSV_DELIMINATOR, $line);					
					// $values = split ($CSV_DELIMINATOR, $line);
					foreach ($values as $value)
					{
						if (count ($fields) <= $i) break; // skip extra fields in file
						$this->{$fields[$i]} = trim ($value, $CSV_QUOTE." ");
						$i++;
					}
					if ($curOrder != $this->orordn)
					{
						$curOrder = $this->orordn;
						$first = true;
					}
					$hash = $this->processFormData ($this->order, 'getOrderCommentsCSVFieldList', 'orderCommentsTblFields');  // We're not processing a form, but that's the method name for getting data ready for insert/update in the db.
					if ($this->order->isOrderComment ($this->orordn, $this->orcatn, $this->orwebn))
					{
						$this->order->updateOrderComment ($hash, $this->orordn);
					} else {
						$this->order->addOrderComment ("", $hash, $this->orordn);
					}
					if ($j != 0 and fmod ($j, 500) < 0.00001)
					{
						echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
					}
					$j++;
				}
				echo "Processed $j records\r\n<br />";
				$this->msg .=  "Processed $j records\r\n<br />";
				fclose ($fh);
				$this->dataTransfer->updateDataTransfer ('OrderFromAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
			} else {
				$this->emailError ($FROM_AS400_ORDER_COMMENTS);
			}
		}
		
		function toAS400Distributor ()
		{
			global $TO_AS400_DISTRIBUTORS, $ROOT_APP_PATH, $CSV_DELIMINATOR, $CSV_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;

			$this->distributor = new Distributor ($this->dbi);
			
			$outputDistributorList = $this->distributor->getCSVDistributorList ($this->dataTransfer->DistributorToAS400);

			$filename = $ROOT_APP_PATH."/".$TO_AS400_DISTRIBUTORS;
			$this->msg =  "File: $filename\r\n<br />";
			echo "File: $ROOT_APP_PATH/$TO_AS400_DISTRIBUTORS<br />";
			if ($this->processToCSVFile ($filename, $outputDistributorList) > 0)
			{
				$this->dataTransfer->updateDataTransfer ('DistributorToAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
				/* add process date/time stamp here */
			} else {
				if (isset ($this->err))
				{
					$this->emailError ($TO_AS400_DISTRIBUTORS);
					unset ($this->err);
				}
			}
		}
		
		function fromAS400Distributor ()
		{
			global $FROM_AS400_DISTRIBUTORS, $ROOT_APP_PATH, $CSV_DELIMINATOR, $CSV_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;
			
			$this->distributor = new Distributor ($this->dbi);
			$fields = $this->distributor->getDistributorCSVFieldList ();
			
			$filename = $ROOT_APP_PATH."/".$FROM_AS400_DISTRIBUTORS;
			// echo "Diff: ".$this->checkFile ($filename, $this->dataTransfer->DistributorFromAS400)."<br />";
			$fh = fopen ($filename, "r");
			echo "File: $filename\r\n<br />";
			$this->msg =  "File: $filename\r\n<br />";
			$first = true;
			$curDistributor = "";
			if ($fh) 
			{
				$j = 0;
				$start = date_create ();
				$err = array ();
$distr = "";				
				while (!feof($fh)) 
				{
					$i =0; 
					$line = fgets($fh, 4096);
					if (strlen (trim ($line)) == 0) continue;
					$values = explode ($CSV_DELIMINATOR, $line);					
					// $values = split ($CSV_DELIMINATOR, $line);
					foreach ($values as $value)
					{
						if (count ($fields) <= $i) break; // skip extra fields in file
						$this->{$fields[$i]} = trim ($value, $CSV_QUOTE." ");
						$i++;
					}
					if ($curDistributor != $this->dsdisn)
					{
						$curDistributor = $this->dsdisn;
						$first = true;
					}
					$hash = $this->processFormData ($this->distributor, 'getDistributorFieldList', 'distributorTblFields');  // We're not processing a form, but that's the method name for getting data ready for insert/update in the db.
					if ($this->distributor->isDistributorEntry ($this->dsdisn))
					{
						$status = $this->distributor->updateDistributor ($hash);
					} else {
						$status = $this->distributor->addDistributor ($hash);
					}
// $distr .= "$hash->dsdisn: $hash->dsname <$hash->dsemal>\n";				
					if ($status != MDB2_OK)
					{
						$err [] = $status;
					} else {
						if (fmod ($j, 500) < 0.00001 and $j != 0)
						{
							echo "Processed $j records\r\n<br />";
							$this->msg .=  "Processed $j records\r\n<br />";
					}
						$j++;
					}
				}
// echo $distr;
				echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
				fclose($fh);
				if (count ($err) > 0)
				{
					$this->emailError ($FROM_AS400_DISTRIBUTORS. ":" . print_r ($err,true));
				}
				$this->dataTransfer->updateDataTransfer ('DistributorFromAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
				// echo "Time to process\r\n<br />".date_diff($start, date_create ());
			} else {
				$this->emailError ($FROM_AS400_DISTRIBUTORS);
			}
		}
		
		function processToCSVFile ($filename, $list)
		{
			global $CSV_DELIMINATOR, $CSV_QUOTE;
			
			$fh = fopen ($filename, "w");

			$this->msg =  "File: $filename\r\n<br />";
			if ($fh) 
			{
				$j = 0;
				foreach ($list as $line)
				{
					$first = true;
					foreach ($line as $f => $value)
					{
						if ($first)
						{
							$str = $CSV_QUOTE.$value.$CSV_QUOTE;
							$first = false;
						} else {
							$str .= $CSV_DELIMINATOR.$CSV_QUOTE.$value.$CSV_QUOTE;
						}
					}
					fwrite ($fh, $str."\r\n");
					if ($j != 0 and fmod ($j, 500) < 0.00001)
					{
						echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
					}
					$j++;
				}
				echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
				fclose ($fh);
				return $j - 1;
			} else {
				echo "Error processing file<br />";
				$this->err = "Error processing file\n";
				return 0;
			}
		}
		
		function updateOrderTemplate ()
		{
			global $FROM_AS400_CATALOG_TEMPLATE, $ROOT_APP_PATH, $CATALOG_DELIMINATOR,
			       $CATALOG_QUOTE, $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;

			$this->catalog = new Catalog ($this->dbi);
			$this->order = new Order ($this->dbi);
			
			// First need to empty Order form for Order Form Code
			
			$this->order->clearOrderForm ("('B','C')"); // remove both B & C codes
			
			$fields = array_flip ($this->order->getOrderFormTemplateFieldList ());
			$numFields = count ($fields);

			$filename = $ROOT_APP_PATH."/".$FROM_AS400_CATALOG_TEMPLATE;

			$fh = fopen ($filename, "r");
			echo "File: $filename\r\n<br />";
			$this->msg =  "File: $filename\r\n<br />";
			if ($fh) 
			{
				$j = 0;
				$start = date_create ();
				$header = fgets($fh, 4096);
				$columns = explode ($CATALOG_DELIMINATOR, rtrim ($header));  // remove new line characters
				// $columns = split ($CATALOG_DELIMINATOR, rtrim ($header));  // remove new line characters

				while (!feof($fh)) 
				{
					$i =0; 
					$line = fgets($fh, 4096);
					if (strlen (trim ($line)) == 0) continue;
					$values = explode ($CATALOG_DELIMINATOR, rtrim ($line));
					// $values = split ($CATALOG_DELIMINATOR, rtrim ($line));
					$curCatalog = "";
					if (isset ($this->ctcatn)) unset ($this->ctcatn);
					if (isset ($this->qtypal)) unset ($this->qtypal);
					if (isset ($this->MinQty)) unset ($this->MinQty);
					if (isset ($this->Special)) unset ($this->Special);

					foreach ($values as $value)
					{
						// $f = $fields [$columns [$i]];
						if ($numFields <= $i) break; // skip extra fields in file
						if (strlen (rtrim ($value, $CATALOG_QUOTE)) > 0)
						{
							$this->{$fields [$columns [$i]]} = rtrim ($value, $CATALOG_QUOTE);
						}
						$i++;
					}
					if (isset ($this->ctcatn))
					{
						if ($curCatalog != $this->ctcatn)
						{
							$curCatalog = $this->ctcatn;
							$first = true;
						}
						// $this->catalog->getCatalogInfo($this->ctcatn);
						// $this->ctwght = $this->catalog->ctwght;
						// $this->ProductType = $this->catalog->ProductType;
						$productGroup = $this->catalog->getProductGroup ($this->ctcatn);

						// Replacing $CIDER_PRODUCT_TYPES_LIST with $CIDER_PRODUCT_GROUP_LIST
						// global $CIDER_PRODUCT_TYPES_LIST;
						global $CIDER_PRODUCT_GROUP_LIST;
						if (in_array ($productGroup, $CIDER_PRODUCT_GROUP_LIST))
						{
							$this->OrderFormCode = 'C'; // SP Cider product
						} else {
							$this->OrderFormCode = 'B'; // SP Beer product
						}
					}
					$hash = $this->processFormData ($this->order, 'getOrderFormTemplateFormFieldList', 'orderFormsTblFields');  // We're not processing a physical form, but that's the method name for getting data ready for insert/update in the db.
					if ($hash->Description == "CIDERBOYS")
					{
						$hash->OrderFormCode = 'C'; // SP Cider product
					}
					$hash->DisplayOrder = $j + 1;
					if ($hash->Special == "*") $hash->Special = "Y";
					else $hash->Special = "N";
					
					// $hash->OrderFormCode = $this->OrderFormCode;

					if (!isset ($this->ctcatn) or $this->ctcatn == "")
					{
						$this->order->updateOrderFormHeader ($hash);
					} else if ($this->order->isOrderFormEntry ($this->ctcatn))
					{
						$this->order->updateOrderForm ($hash);
					} else {
						// echo "Cat: $this->ctcatn<br />";
						$this->order->addOrderForm ($hash);
					}
					if (fmod ($j, 500) < 0.00001 and $j != 0)
					{
						echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
					}
					$j++;
				}
				echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
				fclose($fh);
				$this->dataTransfer->updateDataTransfer ('OrderFormFromAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
			} else {
				$this->emailError ($FROM_AS400_CATALOG_TEMPLATE);
			}
		}
		
		function updateBFOrderTemplate ()
		{
			global $FROM_AS400_BF_CATALOG_TEMPLATE, $ROOT_APP_PATH, $CATALOG_DELIMINATOR, $CATALOG_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;
			
			$this->catalog = new Catalog ($this->dbi);
			$this->order = new Order ($this->dbi);

			// First need to empty Order form for Order Form Code
			
			$this->order->clearOrderForm ("('D')"); // remove both D codes

			$fields = array_flip ($this->order->getOrderFormTemplateFieldList ());

			$filename = $ROOT_APP_PATH."/".$FROM_AS400_BF_CATALOG_TEMPLATE;

			$fh = fopen ($filename, "r");
			echo "File: $filename\r\n<br />";
			$this->msg =  "File: $filename\r\n<br />";
			$this->OrderFormCode = 'D';
			if ($fh) 
			{
				$j = 0;
				$start = date_create ();
				$header = fgets($fh, 4096);
				$columns = explode ($CATALOG_DELIMINATOR, rtrim ($header));  // remove new line characters
				// $columns = split ($CATALOG_DELIMINATOR, rtrim ($header));  // remove new line characters

				while (!feof($fh)) 
				{
					$i =0; 
					$line = fgets($fh, 4096);
					if (strlen (trim ($line)) == 0) continue;
					$values = explode ($CATALOG_DELIMINATOR, rtrim ($line));
					// $values = split ($CATALOG_DELIMINATOR, rtrim ($line));
					$curCatalog = "";
					if (isset ($this->ctcatn)) unset ($this->ctcatn);
					if (isset ($this->qtypal)) unset ($this->qtypal);
					if (isset ($this->MinQty)) unset ($this->MinQty);
					if (isset ($this->Special)) unset ($this->Special);
// echo "Values: ".print_r ($values,true)."<br />";
					foreach ($values as $value)
					{
						// $f = $fields [$columns [$i]];
						if (count ($fields) <= $i) break; // skip extra fields in file
						if (strlen (rtrim ($value, $CATALOG_QUOTE)) > 0)
						{
							$this->{$fields [$columns [$i]]} = rtrim ($value, $CATALOG_QUOTE);
						}
						$i++;
					}
					if (isset ($this->ctcatn))
					{
						if ($curCatalog != $this->ctcatn)
						{
							$curCatalog = $this->ctcatn;
							$first = true;
						}
						// $this->catalog->getCatalogInfo($this->ctcatn);
						// $this->ctwght = $this->catalog->ctwght;
						// $this->ProductGroup = $this->catalog->ProductGroup;
					}
					$hash = $this->processFormData ($this->catalog, 'getCatalogFieldList', 'catalogTblFields');  // We're not processing a form, but that's the method name for getting data ready for insert/update in the db.

					$hash->DisplayOrder = $j + 1;
					if ($hash->Special == "*") $hash->Special = "Y";
					else $hash->Special = "N";
					$hash->OrderFormCode = $this->OrderFormCode;
					if (!isset ($this->ctcatn) or $this->ctcatn == "")
					{
						$this->order->updateOrderFormHeader ($hash);
					} else if ($this->order->isOrderEntry ($this->ctcatn))
					{
						$this->order->updateOrderForm ($hash);
					} else {
						// echo "Cat: $this->ctcatn<br />";
						$this->order->addOrderForm ($hash);
					}
					if (fmod ($j, 500) < 0.00001 and $j != 0)
					{
						echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
					}
					$j++;
				}
				echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
				fclose($fh);
				$this->dataTransfer->updateDataTransfer ('CatalogFromAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
			} else {
				$this->emailError ($FROM_AS400_CATALOG_TEMPLATE);
			}
		}
		
		function replaceCatalog ()
		{
			global $FROM_AS400_CATALOG, $ROOT_APP_PATH, $CSV_DELIMINATOR, $CSV_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;
			
			$this->catalog = new Catalog ($this->dbi);
			
			$status = $this->catalog->clearCatalog ();
			if ($status == MDB2_OK)
			{
				$fields = $this->catalog->getCatalogFileFieldList ();

				$filename = $ROOT_APP_PATH."/".$FROM_AS400_CATALOG;
	
				$fh = fopen ($filename, "r");
				echo "File: $filename\r\n<br />";
				$first = true;
				$curOrder = "";
				if ($fh) 
				{
					$j = 0;
					$start = date_create ();
					while (!feof($fh)) 
					{
						$i =0; 
						$line = fgets($fh, 4096);
						
						if (strlen (trim ($line)) == 0) continue;
						$values = explode ($CSV_DELIMINATOR, $line);					
						// $values = split ($CSV_DELIMINATOR, $line);
						foreach ($values as $value)
						{
						if (count ($fields) <= $i) break; // skip extra fields in file
							$this->{$fields[$i]} = trim ($value, $CSV_QUOTE."\n");
							$i++;
						}
						$hash = $this->processFormData ($this->catalog, 'getCatalogFileFieldList', 'catalogTblFields');  // We're not processing a form, but that's the method name for getting data ready for insert/update in the db.
						$this->catalog->addCatalog ($hash);
						
						if ($j != 0 and fmod ($j, 500) < 0.00001)
						{
							echo "Processed $j records\r\n<br />";
							$this->msg .=  "Processed $j records\r\n<br />";
						}
						$j++;
					}
					echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
					fclose ($fh);
					$this->dataTransfer->updateDataTransfer ('OrderFromAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
				} else {
					$this->emailError ($FROM_AS400_CATALOG);
				}
			} else {
				$this->emailError ("clearing catalog table");
			}
		}
		
		function updateCatalog ()
		{
			global $FROM_AS400_CATALOG, $ROOT_APP_PATH, $CSV_DELIMINATOR, $CSV_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;
			
			$this->catalog = new Catalog ($this->dbi);
			
/*			// $status = $this->catalog->clearCatalog ();
			if ($status == MDB2_OK)
			{
*/				$fields = $this->catalog->getCatalogFileFieldList ();

				$filename = $ROOT_APP_PATH."/".$FROM_AS400_CATALOG;
	
				$fh = fopen ($filename, "r");
			echo "File: $filename\r\n<br />";
				$first = true;
				$curOrder = "";
				if ($fh) 
				{
					$j = 0;
					$start = date_create ();
					while (!feof($fh)) 
					{
						$i =0; 
						$line = fgets($fh, 4096);
						
						if (strlen (trim ($line)) == 0) continue;
						$values = explode ($CSV_DELIMINATOR, $line);					
						// $values = split ($CSV_DELIMINATOR, $line);
						foreach ($values as $value)
						{
						if (count ($fields) <= $i) break; // skip extra fields in file
							$this->{$fields[$i]} = trim ($value, $CSV_QUOTE."\n");
							$i++;
						}
						$hash = $this->processFormData ($this->catalog, 'getCatalogFileFieldList', 'catalogTblFields');  // We're not processing a form, but that's the method name for getting data ready for insert/update in the db.
						$this->catalog->updateCatalogReplace ($hash);
						
						if ($j != 0 and fmod ($j, 500) < 0.00001)
						{
							echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
						}
						$j++;
					}
					echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
					fclose ($fh);
					$this->dataTransfer->updateDataTransfer ('OrderFromAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
				} else {
					$this->emailError ($FROM_AS400_CATALOG);
				}
/*
			} else {
				$this->emailError ("clearing catalog table");
			}
*/
		}
		
		function fromAS400Pricing ()
		{
			global $FROM_AS400_PRICING, $ROOT_APP_PATH, $CSV_DELIMINATOR, $CSV_QUOTE,
			       $MYSQL_DATE_FORMAT, $MYSQL_TIME_FORMAT;
			
			$this->distributor = new Distributor ($this->dbi);
			
			$status = $this->distributor->clearPricing ();
			if ($status == MDB2_OK)
			{
				$fields = $this->distributor->getDistributorPricingFieldList ();

				$filename = $ROOT_APP_PATH."/".$FROM_AS400_PRICING;
	
				$fh = fopen ($filename, "r");
				echo "File: $filename\r\n<br />";
				$first = true;
				$curOrder = "";
				if ($fh) 
				{
					$j = 0;
					$start = date_create ();
					while (!feof($fh)) 
					{
						$i =0; 
						$line = fgets($fh, 4096);
					if (strlen (trim ($line)) == 0) continue;
						$values = explode ($CSV_DELIMINATOR, $line);
						// $values = split ($CSV_DELIMINATOR, $line);
						foreach ($values as $value)
						{
						if (count ($fields) <= $i) break; // skip extra fields in file
							$this->{$fields[$i]} = trim ($value, $CSV_QUOTE." ");
							$i++;
						}
						$hash = $this->processFormData ($this->distributor, 'getDistributorPricingFieldList', 'distributorPriceTblFields');  // We're not processing a form, but that's the method name for getting data ready for insert/update in the db.

						$this->distributor->addPrices ($hash);
						
						if ($j != 0 and fmod ($j, 500) < 0.00001)
						{
							echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
						}
						$j++;
					}
					echo "Processed $j records\r\n<br />";
						$this->msg .=  "Processed $j records\r\n<br />";
					fclose ($fh);
					$this->dataTransfer->updateDataTransfer ('PricingFromAS400', date ($MYSQL_DATE_FORMAT." ".$MYSQL_TIME_FORMAT));
				} else {
					$this->emailError ($FROM_AS400_CATALOG);
				}
			} else {
				$this->emailError ("clearing catalog table");
			}
		}
		
		function checkFile ($file, $lastDate)
		{
			$fileDate = filemtime ($file);
			
			return $this->dateDiff ($fileDate, $lastDate);
		}
		
		function emailError ($fn)
		{
			global $ERROR_EMAIL, $FROM_EMAIL;
			
			$to = $ERROR_EMAIL;
			$from = $FROM_EMAIL;
			$subject = "Problem with $fn";
			
			mail ($to, $subject, "Error: $fn", "from: $from");
		}
		
		function dateDiff ($start, $end)
		{
			$intStart = strtotime ($start);
			$intEnd = strtotime ($end);
			
			return ($intEnd - $intStart);
			// $tomorrow  = mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"));
		}
	
	}//class
	
	global $APP_DB_URL;
	
	$thisApp = new toas400App(
							array( 'appName'      => $APPLICATION_NAME,
									'appDbUrl'    => $APP_DB_URL,
							  		'appUrl'      => $TOAS400_MNGR,
									'appVersion'  => '1.0.5',
									'appType'     => 'CLI',
									'appAutoAuthorize' => FALSE,
									'appAutoConnect'   => TRUE,
									'appAutoCheckSession' => FALSE,
									'appDebugger' => $OFF
								)
							);
	
	$thisApp->bufferDebugging();
	$thisApp->run();
	$thisApp->dumpDebuginfo();

?>