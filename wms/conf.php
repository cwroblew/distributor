<?php
	// Application Configuration File

	error_reporting(0);
	// Turn on all error reporting for testing
	// error_reporting(E_ALL);
	
	// If you have installed wms directory in a different directory than
	// %DocumentRoot%/wms, change the TEST_SERVER_DIR setting below.
	
	$TEST_SERVER_DIR = '/distributor';
	$TEST_SERVER = FALSE;
	$TEST_DB_PREFIX   = '_auth';
	$COMPANY_LOGO = '';
	$CLIENT_NAME = 'Client Name';
	$CLIENT_EMAIL = 'client@clientdomain.com';
	$ERROR_EMAIL = 'me@mydomain.com';
	$FROM_EMAIL = 'do_not_reply@clientdomain.com';
	
	$APP_SUBDIR		  = '/wms';
	$APP_DIR		  = $TEST_SERVER_DIR . $APP_SUBDIR;
	
	$HOME_APP = '/mainmenu/mainmenu.php';
	$HOME_URL = $APP_DIR. $HOME_APP;
	
	// Allow for execution from the web or on the local host via crontab
	$ROOT_DIR		  = $_SERVER['DOCUMENT_ROOT'] != ""? $_SERVER['DOCUMENT_ROOT'] : 'path to document root';
	$ROOT_APP_DIR	  = $ROOT_DIR . $APP_DIR;

	$MAIN_TEMPLATE_DIR = $ROOT_APP_DIR . '/templates';
	$WMS_TEMPLATE_DIR = $ROOT_APP_DIR . '/templates';
	$ROOT_APP_PATH    = $ROOT_DIR.$TEST_SERVER_DIR;

	$PHP_SELF = $_SERVER["PHP_SELF"];

	$APP_FRAMEWORK_DIR = $ROOT_APP_DIR .'/framework';
	$PHPLIB			   = $ROOT_APP_DIR .'/phplib';

	// Insert the path in the PHP include_path so that PHP looks for PEAR, PHPLIB 
	// and our application framework classes in these directories
	
	ini_set( 'include_path',
			$PHPLIB . PATH_SEPARATOR . 
			$APP_FRAMEWORK_DIR . PATH_SEPARATOR . 
			ini_get('include_path'));

	ini_set('session.cache_limiter', 'private'); 

	$AUTHENTICATION_URL = $APP_DIR . '/login/login.php';
	$LOGOUT_URL			= $APP_DIR . '/logout/logout.php';
	$USERPROFILE_MNGR_DIR = $APP_DIR . '/userprofile/apps';
	$USERPROFILE_MNGR   = '/userprofile.php';
	
	$USERPROFILE_CHANGE_PWD_APP =  'userprofile_forgotten_pwd.php';
	$FORGOTTEN_PASSWORD_APP = $USERPROFILE_MNGR_DIR .'/'.$USERPROFILE_CHANGE_PWD_APP;
	$USERPROFILE_MODIFY_APP =  'userprofile_update_user_profile.php';
	$USER_UPDATE_PROFILE_URL = $USERPROFILE_MNGR_DIR .'/'.$USERPROFILE_MODIFY_APP;
	
	$APPLICATION_TEXT_NAME	= 'Client Management Console';
	
	$XMAILER_ID			= 'Admin Version 1.0';

	$DEFAULT_LANGUAGE  = 'US';
	$DEFAULT_DOMAIN	   = 'www.clientdomain.com';

	$BUTTON_PATH	= $TEST_SERVER_DIR .'/images';
	$IMAGE_PATH		= $TEST_SERVER_DIR .'/images';
	$CSS_PATH		= $TEST_SERVER_DIR .'/css';

	// Specific images and text for Client 
	// Currently displayed only on the login page
	
	$CLIENT_PATH    = '/DistributorPortal/'; // Folder for all files

	$CLIENT_IMAGE_TEXT_PATH = $ROOT_DIR.$CLIENT_PATH; // Server file path for reading file - does not need to be altered (and DO NOT TOUCH $ROOT_DIR
	$CLIENT_IMAGE_PATH = $CLIENT_PATH; // Path for server to display image - change if images are not in the same folder as the Text file
	$CLIENT_IMAGE_TEXT_FILE_NAME = '/DistributorPortal.txt'; // Text filename for information
		// Column 1: Number of image/text
		// Column 2: Image file name
		// Column 3: Text for image

	$JAVASCRIPT_PATH  = $TEST_SERVER_DIR .'/js';

// define("APP_DB_NAME", 'CLIENT'); 
define("APP_DB_HOST", "localhost");
define("DB_NAME", 'client'.$TEST_DB_PREFIX);

  
	function __autoload($class) 
	{
		global $APP_FRAMEWORK_DIR;
		
	 	require_once $APP_FRAMEWORK_DIR . '/' .'class.'.$class . '.php';
	}

	require_once "HTML/Template/ITX.php";

	$CLIENT_INDEX_TEMPLATE  = 'client-index.htm';
	$WMS_INDEX_TEMPLATE     = 'wms-index.htm';
	$PLN_RESULTS_TEMPLATE   = 'client-customer-index.htm';
	$WMS_BLANK_INDEX_TEMPLATE = 'blank-index.htm';
	$WMS_PRINT_INDEX_TEMPLATE = 'print-page-index.htm';
	$WMS_EMAIL_INDEX_TEMPLATE = 'wms_email_index.htm';
	$WMS_EMAIL_BOL_INDEX_TEMPLATE = 'wms_email_bol_index.htm'; // style for BH BOL
	$WMS_ADMIN_TEMPLATE		= 'wms_admin.htm';
	$WMS_LEFT_NAV_TEMPLATE  = 'wms_left_menu.htm';
	$BLANK_TEMPLATE			=  $ROOT_APP_DIR . '/blank.htm';
	$STATUS_TEMPLATE		= 'status.htm';
	$ORDER_CONFIRMATION_TEMPLATE = 'order_email.txt';
	$BH_INDEX_TEMPLATE     = 'contractor-index.htm';
	$BH_EMAIL_INVOICE_STYLE = 'contractor_email_invoice_style.css';
	
	/*  --------------START TABLE NAMES ---------------------- */
// define("BUTTONS_TBL", '');
define("LOGIN_DB_TBL", '');
define("APP_NAME", 'CLIENT');
	/*  --------------END TABLE NAMES ---------------------- */

	if ($TEST_SERVER == '/CLIENT/dis_distributor')
	{
		define("APP_DB_NAME", 'CLIENT'); 
		define("AUTH_DB_NAME", 'client'.$TEST_DB_PREFIX); 
		$AUTH_DB_URL      = array(
								'phptype'  => 'mysql',
								'username' => 'USER',
								'password' => 'PASSWORD',
								'hostspec' => 'localhost', // 'localhost',
								'database' => AUTH_DB_NAME
							);
		$APP_DB_URL      = array(
								'phptype'  => 'mysql',
								'username' => 'USER',
								'password' => 'PASSWORD', 
								'hostspec' => 'localhost', 
								'database' => APP_DB_NAME
							);
	} else {
		define("APP_DB_NAME", 'distributors'); 
		define("AUTH_DB_NAME", 'distributors');  // user table in same database as everything else
		$AUTH_DB_URL      = array(
								'phptype'  => 'mysql',
								'username' => 'USER',
								'password' => 'PASSWORD',
								'hostspec' => 'MYSQL_HOST', 
								'database' => AUTH_DB_NAME
							);
		$APP_DB_URL      = array(
								'phptype'  => 'mysql',
								'username' => 'USER',
								'password' => 'PASSWORD', 
								'hostspec' => 'MYSQL_HOST', 
								'database' => APP_DB_NAME
							);
	}
	require_once $APP_FRAMEWORK_DIR.'/constants.php';

	/*  --------------END TABLE NAMES ---------------------- */
	
	$META_INDEX_FOLLOW	= 'noindex, nofollow';  // Should a spider enter this page? - Reset only for pages that this is changed.
	$TEMPLATE_TYPE       = 'wms';  

	define ('APP_VERSION', '1.3.0');
	
	$MYSQL_DATE_FORMAT = 'Y-m-d';
	$MYSQL_TIME_FORMAT = 'h:i:s';
	
	// HTML code and CSS Style
	$READ_ONLY_ATTRIBUTE = 'readonly="readonly"';
	$DISPLAY_NONE = 'style="display:none"';
	$SELECTED = 'selected="selected"';
	$BOLD = 'style="font-weight:bold"';
	$FIELD_AUTO_FILL = ' class="FormAutoFill"';

	$DEFAULT_PASSWORD = 'Default';
	
	$CACHE_TYPE_NONE = "NONE";
	$CACHE_TYPE_BROWSER = "BROWSER";
	$CACHE_TYPE_NOVALIDATE = "NOVALIDATE";

	$NEW_ORDER_STATUS     = "N";
	$ACTIVE_ORDER_STATUS  = "A";
	$DELETED_ORDER_STATUS = "D";
	$CHANGED_ORDER_STATUS = "M";
	$SHIPPED_ORDER_STATUS = "S";
	$PROCESSED_DELETED_STATUS = "X";
	$UNPROCESSED_BYAS400_STATUS = "U";  // Not to be processed by AS/400
	$UNPROCESSED_BYAS400_INV_STATUS = "I";
	$UNPROCESSED_BYAS400_BOL_STATUS = "B";
	$UNPROCESSED_DELETED_ORDER_STATUS = "C";
	$POINT_STATUSES = array ($NEW_ORDER_STATUS, $ACTIVE_ORDER_STATUS, $DELETED_ORDER_STATUS, 
							 $CHANGED_ORDER_STATUS, $SHIPPED_ORDER_STATUS, 
							 $PROCESSED_DELETED_STATUS);
	$CONTRACTOR_STATUSES = array ($UNPROCESSED_BYAS400_STATUS, $UNPROCESSED_BYAS400_INV_STATUS,
							    $UNPROCESSED_BYAS400_BOL_STATUS, 
								$UNPROCESSED_DELETED_ORDER_STATUS);
	
	$BCC_EMAIL_ALERT = "me@mydomain.com, client@clientdomain.com";  // Order Confirmations
	$BCC_BH_EMAIL_ALERT = "contractor@contractordomain.com";  // BH Order Invoice
	$BCC_BH_BOL_EMAIL_ALERT = "contractor@contractordomain.com";  // BH Order BOL
	$CRON_EMAIL_ALERT = "me@mydomain.com";

	$ADD_ORDER_SUBJECT ="Your Brewery Order# ";
	$MODIFY_ORDER_SUBJECT = "Your Brewery Order# ";
	$PASSWORD_RESET_SUBJECT = "Password Reset";
	$ADD_BH_ORDER_SUBJECT ="Contract Order Confirmation";
	$MODIFY_BH_ORDER_SUBJECT = "Contract Order Confirmation";
	$BH_FROM_EMAIL = 'do_not_reply@contractordomain.com';
	
	$TEST_MODE = FALSE;
	$TEST_MODE_EMAIL = "me@mydomain.com";
	
	$DATA_TRANSFER_APP = 'toas400_cron.php';
	$LOGIN_APP = 'login.php';
	$SELECT_USER = 'SelectUser';
	
	$ORDERS_TO_BE_SUSPENDED = "Order site will close in ";  // need to add to this exact minutes
	//$ORDERS_SUSPENDED = "Order site is now closed, will reopen at 4AM CST";
	$ORDERS_SUSPENDED = "Order site is now closed, will reopen at 7AM CST";
	$ORDERS_RUNNING = "Order site is unavailable";
	$ORDERS_MAINTENANCE = "Order site is temporarily closed for maintenance";
	$MIN_ORDERFORM_COLUMN = 20;
	
	$SUSPEND_STARTTIME = "19:15:00"; // Shut down distributor access for data transfer CST
	// $SUSPEND_ENDTIME = "07:00:00";   // Re-start time for distributor access
	$SUSPEND_ENDTIME = "05:00:00";   // Re-start time for distributor access
	$SUSPEND_WARNING = 15; // Warning starts at # of minutes before $SUSPEND_STARTTIME
	
	date_default_timezone_set('America/Chicago');
	$GMT_OFFSET = timezone_offset_get (new DateTimeZone('America/Chicago'), date_create ("now")) /60;  // in minutes

	$DB_SYNTAX_ERROR = 'syntax error';

	$DISTRIBUTOR_APP_RUNNING = "R";
	$DISTRIBUTOR_APP_DATA_TRANSFER = "D";
	$DISTRIBUTOR_APP_MAINTENANCE = "M";
	
	$IMAGE_FILE = 'SPImage';
	$IMAGE_TEXT = 'SPText';
	$IMAGE_FILE_DELIMINATOR = "|";
	$IMAGE_FILE_QUOTE = "^";
	
	$CONTRACTOR_LOGO = 'ContractLogo.png';
	
	// Beer / Cider
	// Replacing CIDER_PRODUCT_TYPES with CIDER_PRODUCT_GROUP
	
	$CIDER_PRODUCT_GROUP = '("29")';
	$CIDER_PRODUCT_GROUP_LIST = array ('29'); // 29 - Cider Boys
	$CIDER_PRODUCT_TYPES = '("CBCRN", "CBARK", "CBAPL", "CBPRS", "CBPCH", "CBRSP", "CBVAR", "CBPIN")';
	$CIDER_PRODUCT_TYPES_LIST = array ("CBCRN", "CBARK", "CBAPL", "CBPRS", "CBPCH", "CBRSP", "CBVAR", "CBPIN");
	$ORDER_FORM_CODES = array ('A', 'B', 'C', 'D'); // A - All, B - Beer, C - Cider (not intended to be alphabetical), D - Contractor
	$BH_PRODUCT_TYPES = '("BFDUN", "BFHEF", "BFLAG", "BFLGR", "BFPAL", "BFRED", "BFSUM")';

	// Purge age
	$PURGE_SHIP_DAYS = 730; // purge records after ~2 year (will be off in leap years but do we care?
//	$PURGE_SHIP_DAYS = 900; // purge records after ~2 year (will be off in leap years but do we care?
	$PURGE_NOAS400_DAYS = 4; // Purge any web order line items that don't come back with an Sxxxx order number within three days. These items will still have an 'N' status.
?>
