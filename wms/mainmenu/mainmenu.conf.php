<?php
	require_once ("../conf.php");

   $ROOT_PATH      = $ROOT_APP_DIR . '/mainmenu';
   $APP_PATH       =  $ROOT_PATH . '';
   $REL_APP_PATH       = $APP_DIR . '/mainmenu';

   $TEMPLATE_DIR       = $APP_PATH . '/templates';
   
   require_once "mainmenu.errors.php";
   require_once "mainmenu.messages.php";

   // $PASSWORD_GENERATOR_CLASS = $CLASS_PATH   . '/' . 'rndConditionPass.class.php';

   // Application names

   $MAINMENU           =  'mainmenu.php';

   $APPLICATION_NAME   = 'mainmenu.php';

   $STATUS_TEMPLATE            =  'mainmenu_status.html';
   $MAINMENU_CONTENT_TEMPLATE  =  'mainmenu_content.html';
   $MAINMENU_SELECT_USER_TEMPLATE    =  'mainmenu_select_user.htm';
   $MAINMENU_DISTRIBUTOR_CUSTOM_TEMPLATE =  'mainmenu_content_bh.html';  // currently hard-coded - should be placed in db

	// Currently custom menu items are hard-coded here - should be in db
	
	$BH_CUSTOM_REPORT = 'wms/reports/invoice_register.php';
	$BH_CUSTOM_REPORT_DESC = 'Monthly Invoice Register';
?>
