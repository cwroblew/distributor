<?php
	$filename = "../../conf.php";
	if (!file_exists ( $filename ))
		if (file_exists ('path to document root/dis_distributor/wms/conf.php'))
			$filename = 'path to document root/dis_distributor/wms/conf.php';
		else
		{
			exit;
		}
	
	require_once ($filename);

	$ROOT_PATH      = $ROOT_APP_DIR . '/cronmngr';
	$APP_PATH       =  $ROOT_PATH . '/apps';
	$REL_APP_PATH       = $APP_DIR . '/cronmngr/apps';
	
	$TEMPLATE_DIR       = $APP_PATH . '/templates';
	
	require_once "as400_cron.errors.php";
	require_once "as400_cron.messages.php";
	
	$CHAR_SET     = 'charset=iso-8859-1';
	
	// Application names
	
	$TOAS400_MNGR              =  'toas400';
	
	$APPLICATION_NAME   = 'toas400_cron.php';
	
	// File Names
	$TO_AS400_ORDERS = 'web_orders';
	$TO_AS400_ORDER_COMMENTS = 'web_order_comments';
	$TO_AS400_DISTRIBUTORS = 'web_distributors';
	$FROM_AS400_ORDERS = 'as400_orders';
	$FROM_AS400_ORDER_COMMENTS = 'as400_order_comments';
	$FROM_AS400_DISTRIBUTORS = 'as400_distributors';
	$FROM_AS400_PRICING = 'as400_prices';
	$FROM_AS400_CATALOG_TEMPLATE = 'order_entry_template.csv';
	$FROM_AS400_BF_CATALOG_TEMPLATE = 'order_entry_template_d.csv';
	$FROM_AS400_CATALOG = 'as400_catalog_numbers';
	
	// Other Constants
	$TOAS400_PWD_EMAIL_SUBJECT = 'CLIENT Cron - ';
	$TOAS400_PWD_EMAIL_FROM    = 'FileManager@clientdomain.com';
	
	$CSV_DELIMINATOR = "~";
	$CSV_QUOTE = "^";
	$CATALOG_DELIMINATOR = ",";
	$CATALOG_QUOTE = "";
	
	$PRICING_STATUSES = $CONTRACTOR_STATUSES; // There may be more and want to handle all
?>
