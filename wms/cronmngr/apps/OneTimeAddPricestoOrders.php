<?php

/*
 * OneTimeAddPricestoOrders
 *	As the module says. One time add prices to Orders
 *	This is for Contractor orders.
 *	Expect this to only be processed once, but we will add a few extras to allow for changes to
 *	order pricing due to things out of our control
 *		Inputs:
 *			dtst - Date start - allow price alterations to only be effective for orders w/i 
 *				date range
 *			dten - Date end
 *			new  - Only allow price alterations for new orders (ie not invoiced - but bol?)
*/
	require_once "as400_cron.conf.php";
	
	class OneTimeAddPricestoOrders extends PHPApplication {
	
		function run()
		{
			global $DISTRIBUTOR_PRICE_TBL;
			if (isset ($_SERVER['argv']))
			{
				$msg = "Running from shell\nArgs: ".print_r ($_SERVER['argv'],true)."\n";
				$args =  $_SERVER['argv'];
			} else if (isset ($_REQUEST['arg1'])) {
				$msg = "Running from web<br />Args: ".print_r ($_REQUEST['arg1'],true)."<br />";
				$args[1] = strtolower ($_REQUEST['arg1']);
				$args[2] = isset ($_REQUEST['arg2']) ? strtolower ($_REQUEST['arg2']) : "";
			}
			global $PRICING_STATUSES;
			
			$this->order = new Order ($this->dbi);
// separate status array into a string
			$status = "'".implode ("','", $PRICING_STATUSES)."'";
			$this->order->updateOrderPrices ($status, $DISTRIBUTOR_PRICE_TBL);		
			echo "Done?<br />";	
		}
			
	}//class
	
	global $APP_DB_URL;
	
	$thisApp = new OneTimeAddPricestoOrders(
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