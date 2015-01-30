<?php

/*
 * Purge older orders
 *	1 Ship date more than 730 days if status is "S"
 *	2 Order Request date more than 730 days if status is not "S"
 *	3 Web order not activated by AS400 within 3 days (moving this to daily)
 *	4 Delete date more than 730 days (actually falls under #2)
 */
	require_once "as400_cron.conf.php";
	
	class cronPurgeOrders extends PHPApplication {
	
		function run()
		{
			if (isset ($_SERVER['argv']))
			{
				$msg = "Running from shell\nArgs: ".print_r ($_SERVER['argv'],true)."\n";
				$args =  $_SERVER['argv'];
			} else if (isset ($_REQUEST['arg1'])) {
				$msg = "Running from web<br />Args: ".print_r ($_REQUEST['arg1'],true)."<br />";
				$args[1] = strtolower ($_REQUEST['arg1']);
				$args[2] = isset ($_REQUEST['arg2']) ? strtolower ($_REQUEST['arg2']) : "";
			}
			// $this->debug ( "args: ".print_r($args, true));

			// $this->dataTransfer = new DataTransfer ($this->dbi);
			// $this->dataTransfer->setIsProcessingData ();
			
			$this->order = new Order ($this->dbi);
			//
			// Purge unprocessed orders (unprocessed by AS/400
			//
/* Skip for now
			echo "Order Lines not processed: ".$this->order->countPurgeUnprocessedLineItems ('getOrderInfoFieldList')."<br />";
			$this->order->purgeUnprocessedLineItems ('getOrderDisplayFieldList');
			
			$this->order->purgeUnprocessedOrderComments ();
*/
			//
			// Purge old orders
			//
			echo "Orders: ".$this->order->countPurgeOrders ('getOrderInfoFieldList')."<br />";
			// echo "BH Orders: ".count ($this->order->countPurgeOrders ('getOrderFieldList'));
			
			$this->order->purgeOrders ('getOrderDisplayFieldList');
			// $this->order->purgeOrders ('getOrderBHDisplayFieldList');
			
			// $this->dataTransfer->unsetIsProcessingData ();
			echo "Done<br />";
		}
			
	}//class
	
	global $APP_DB_URL;
	
	$thisApp = new cronPurgeOrders(
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