<?php


   // logout.conf

	require_once ("../conf.php");

   $PHP_SELF = $_SERVER["PHP_SELF"];


   $HOME_URL         = $TEST_SERVER_DIR . '/mainmenu/mainmenu.php';
   // $AUTHENTICATION_URL = $TEST_SERVER_DIR . '/login/login.php';

   $APPLICATION_NAME = 'LOGOUT';
   $DEFAULT_LANGUAGE = 'US';

   $REL_ROOT_PATH      = $ROOT_APP_DIR . '/logout';

   require_once "logout.errors.php";
   require_once "logout.messages.php";

?>
