<?php
	require_once ("../conf.php");

   $ROOT_PATH      = $ROOT_APP_DIR . '/externalmgr';
   $APP_PATH       =  $ROOT_PATH . '';
   $REL_APP_PATH   = $APP_DIR . '/externalmgr';

   $TEMPLATE_DIR   = $APP_PATH . '/templates';
   
   require_once "externalmgr.errors.php";
   require_once "externalmgr.messages.php";

   // Application names

   $EXTERNALMGR        = 'externalmgr.php';

   $APPLICATION_NAME   = 'externalmgr.php';

   $STATUS_TEMPLATE            =  'externalmgr_status.html';
   $EXTERNALMGR_CONTENT_TEMPLATE  =  'externalmgr_content.html';

	$LAB_RESULTS_COOKIE = array ('name' => 'CustomerNo', 'Value' => '');
	$EXTERNAL_LOGIN_URL = 'http://clientdomain.com';
?>
