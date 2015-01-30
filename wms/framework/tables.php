<?php
/*
* CVS ID: $Id$
*/
	// Distributor
	$DISTRIBUTOR_TBL      = APP_DB_NAME.".dis_distributors";
	$DISTRIBUTOR_DROP_TBL = APP_DB_NAME.".dis_drops";
	$DISTRIBUTOR_PRICE_TBL = APP_DB_NAME.".dis_prices";
	
	// Order
	$ORDER_TBL          = APP_DB_NAME.".dis_orders";
	$ORDER_COMMENTS_TBL = APP_DB_NAME.".dis_order_comments";
	$ORDER_FORMS_TBL          = APP_DB_NAME.".dis_order_forms";
	$ORDER_FORM_RULES_TBL          = APP_DB_NAME.".dis_order_form_rules";
	$ORDER_STATUS_TBL   = APP_DB_NAME.".dis_order_status";
	$ORDER_WEB_ORDER_NUMBER_TBL = APP_DB_NAME.".dis_weborder_number";
	$ORDER_BH_INFO_TBL          = APP_DB_NAME.".dis_bh_order_info";
	$ORDER_BH_INVOICE_NUMBER_TBL = APP_DB_NAME.".dis_bh_invoice_number";
	$ORDER_BH_ORDER_NUMBER_TBL = APP_DB_NAME.".dis_bhorder_number";
	$ORDER_HISTORY_TBL           = APP_DB_NAME.".dis_order_history"; // purged orders
	$ORDER_COMMENTS_HISTORY_TBL  = APP_DB_NAME.".dis_order_comments_history"; // purged orders
	
	// Invoice
	$INVOICE_HISTORY_BH_TBL = APP_DB_NAME.".dis_bh_invoice_history";
	
	// Catalog
	$CATALOG_TBL          = APP_DB_NAME.".dis_catalog_numbers";
	$CATALOG_BH_TBL          = APP_DB_NAME.".dis_bh_catalog_numbers";
		
	// Data Transfer
	$DATATRANSFER_TBL      = APP_DB_NAME . ".dis_data_transfer"; 

	$APP_USERS_TABLE = DB_NAME . ".dis_users";

	// Forms
	$FORM_TBL        = APP_DB_NAME.".dis_forms";
	
	// Authorization
	$AUTH_DB_TBL      = AUTH_DB_NAME . ".dis_users"; 
	$USER_TYPE_TABLE  = AUTH_DB_NAME.".dis_UserType";
	$AUTH_BUTTONS_TBL = AUTH_DB_NAME.'.dis_UserAdminButtons';
	$BUTTONS_TBL      = AUTH_DB_NAME.'.dis_buttons';
	$ACTIVITY_LOG_TBL = AUTH_DB_NAME.'.dis_activity';

	define("AUTH_DB_TBL",          DB_NAME.".dis_users"); 
	define("USER_TYPE_TABLE",      DB_NAME.".dis_UserType");
	define("AUTH_BUTTONS_TBL",     DB_NAME.'.dis_UserAdminButtons');
	define("BUTTONS_TBL",          DB_NAME.'.dis_Buttons');

?>