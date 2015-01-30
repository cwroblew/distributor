<?php
	// login.conf
	
	require_once ("../conf.php");
	
	$PHP_SELF = $_SERVER["PHP_SELF"];
	$REL_APP_PATH       = $APP_DIR . '/login';
	
	$LOGIN_CONTENT_TEMPLATE  = 'login_content.htm';
	$CUSTOMER_LOGIN_CONTENT_TEMPLATE  = 'customer_login_content.htm';
	$LOGIN_ADMIN_TEMPLATE    = 'login_admin_content.htm';
	
	$APPLICATION_NAME = 'login.php';
	$DEFAULT_LANGUAGE = 'US';
	
	$LOGIN_MNGR        =  'login.php';
	$TEMPLATE_MNGR     = 'main';
	$MAIN_TEMPLATE_DIR = $WMS_TEMPLATE_DIR;
	
	$MAX_ATTEMPTS = 250;
	
	$REL_ROOT_PATH     = $ROOT_APP_DIR . '/login';
	$TEMPLATE_DIR      = $REL_ROOT_PATH . '/templates';
	
	$WARNING_URL       = $TEMPLATE_DIR . '/warning.html';
	
	require_once "login.errors.php";
	require_once "login.messages.php";

	$MIN_USERNAME_SIZE= 3;
	$MIN_PASSWORD_SIZE= 3;
	
?>
