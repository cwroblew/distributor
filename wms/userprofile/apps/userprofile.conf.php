<?php
	require_once ("../../conf.php");

   $ROOT_PATH      = $ROOT_APP_DIR . '/userprofile';
   $APP_PATH       =  $ROOT_PATH . '/apps';
   $REL_APP_PATH       = $APP_DIR . '/userprofile/apps';

	$TEMPLATE_MNGR     = 'main';
   $TEMPLATE_DIR       = $APP_PATH . '/templates';
   
   require_once "userprofile.errors.php";
   require_once "userprofile.messages.php";

   $SECRET       = 916489;

   // Application names

   $USERPROFILE           =  'userprofile.php';
   $USERPROFILE_CHANGE_PWD_APP    =  'userprofile_passwd.php';
   $USERPROFILE_UPDATE_PROFILE_APP     =  'userprofile_update_user_profile.php';

   $APPLICATION_NAME   = 'userprofile.php';

   $USERPROFILE_REGISTER_MNGR           =  'userprofile_register.php';
   $USERPROFILE_UPDATE_MNGR             =  'userprofile_update_user.php';
   $USERPROFILE_UPDATE_PROFILE_MNGR     =  'userprofile_update_user_profile.php';
   $USERPROFILE_FORGOT_PASSWORD_MNGR    =  'userprofile_forgot_password.php';
   $USERPROFILE_FORGOTTEN_PASSWORD_MNGR    =  'userprofile_forgotten_pwd.php';
   $USERPROFILE_ACTIVATE_PASSWORD_MNGR  =  'userprofile_activate_password.php';

   /*  --------------START TABLE NAMES ---------------------- */
   //$APP_DB_URL        = 'mysql://root:foobar@localhost/auth';

   /*  --------------END TABLE NAMES ---------------------- */

   $USERPROFILE_REGISTER_TEMPLATE          =  'userprofile_register.htm';
   $USERPROFILE_THANKYOU_REGISTER_TEMPLATE =  'userprofile_thankyou_register.htm';
   $USERPROFILE_UPDATE_USER_TEMPLATE       =  'userprofile_update_user.htm';
   $USERPROFILE_STATUS_TEMPLATE                 =  'userprofile_status.htm';
   $STATUS_LOGIN_TEMPLATE               =  'userprofile_login_status.htm';
   $USERPROFILE_UPDATE_USER_PROFILE_TEMPLATE ='userprofile_update_user_profile.htm';
   $USERPROFILE_FORGOTTEN_PASSWORD_TEMPLATE   ='userprofile_forgotten_pwd.htm';
   $USERPROFILE_ACTIVATE_PASSWORD_TEMPLATE ='userprofile_activate_password.htm';
   $USERPROFILE_ACTIVATE_TEMPLATE       ='userprofile_activate_register.htm';
   $USERPROFILE_PWD_CHANGE_TEMPLATE     = 'userprofile_pwd_change.htm';
   $USERPROFILE_PWD_REQUEST_TEMPLATE    =  'userprofile_forgotten_pwd.htm';
   $USERPROFILE_PWD_EMAIL_TEMPLATE      =  'userprofile_forgotten_pwd_email.txt';
   $USERPROFILE_PWD_RESET_TEMPLATE      =  'userprofile_pwd_reset.htm';
   $USERPROFILE_FORGOT_PASSWORD_TEMPLATE = 'userprofile_forgot_password.htm';
   $USERPROFILE_SELECT_USER_TEMPLATE    =  'userprofile_select_user.htm';

	$MIN_USERNAME_SIZE= 3;
	$MIN_PASSWORD_SIZE= 6;

   $USERPROFILE_PWD_EMAIL_SUBJECT = 'Password Reset for Client';
   $USERPROFILE_PWD_EMAIL_FROM    = $FROM_EMAIL;
?>
