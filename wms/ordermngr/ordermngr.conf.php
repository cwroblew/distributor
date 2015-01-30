<?php
	require_once ("../conf.php");

	$ROOT_PATH          = $ROOT_APP_DIR . '/ordermngr';
	$APP_PATH           =  $ROOT_PATH . '';
	$REL_APP_PATH       = $APP_DIR . '/ordermngr';
	
	$FORM_ROOT_PATH    = $ROOT_APP_DIR . '/formentry';
	$FORM_APP_PATH     =  $FORM_ROOT_PATH . '';
	$FORM_REL_APP_PATH = $APP_DIR . '/orders';

	require_once "ordermngr.errors.php";
	require_once "ordermngr.messages.php";

	$CHAR_SET     = 'charset=iso-8859-1';

	// Application names

	$ORDER_MNGR             =  'ordermngr.php';
	$TEMPLATE_MNGR  = 'main';
	$MAIN_TEMPLATE_DIR = $WMS_TEMPLATE_DIR;
	$TEMPLATE_DIR     = $APP_PATH . '/templates';
	$FORM_TEMPLATE_DIR = $FORM_APP_PATH . '/templates';
	
	$APPLICATION_NAME  = 'OrderManager';
	$MANAGER           = 'Order Manager';

	$ORDERMNGR_CONTENT_TEMPLATE =  'ordermngr_content.html';
	$ORDERMNGR_DISPLAY_TEMPLATE =  'ordermngr_datalist.html';
	$ORDERMNGR_DISPLAY_BH_TEMPLATE =  'BH_ordermngr_datalist.html';
	$ORDERMNGR_FORM_TEMPLATE    =  'order_form.html';
	$ORDERMNGR_ORDER_FORM_BH_TEMPLATE = 'BH_order_form.html';
	$ORDERMNGR_INVOICE_FORM_BH_TEMPLATE = 'BH_invoice.html';
	$ORDERMNGR_BOL_FORM_BH_TEMPLATE = 'BH_bol.html';
	$ORDER_REVIEW_TEMPLATE      =  'order_review.html';  // located in $FORM_TEMPLATE_DIR
	$ORDERMNGR_STATUS_TEMPLATE  = 'ordermngr_status.htm';
   $ORDERMNGR_SELECT_USER_TEMPLATE    =  'ordermngr_select_user.htm';

	$VIEW_FORM_MODULE = 2;
	$ORDER_FORM_COLUMNS = 2;

	$OVER_WEIGHT_CLASS = "OverWeight";
	$OVER_WEIGHT = 50000;
	
	$DEFAULT_DROP = "PT";
	$SHIP_DATE_ADVANCE = 30;
	$MODIFY_ORDER_ADVANCE = 5;          // Orders cannot be modifed
	$MODIFY_ORDER_SHIPPED_DISPLAY = 30;  // Orders that have shipped more than 14 days - Modified to 30 days 9/13/2010
	$SPECIAL_DISPLAY = "Call Sales";
	
	$ADD_ORDER_SUBMIT = "Submit Order";
	$MODIFY_ORDER_SUBMIT = "Submit Changes";
	
	$DELETE_ORDER = "Delete Order";
	$ORDER_FORM_CANCEL = "Return to Order List";
	
	$COMMENTS_REQUIRED = FALSE;

 	require_once 'Mail.php' ;
	require_once 'Mail/mime.php' ;

 ?>
