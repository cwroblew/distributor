<?php
	require_once "externalmgr.conf.php";

	class externalmgrApp extends PHPApplication {

		function run()
		{
			global $EXTERNALMGR_MNGR, $LAB_RESULTS_COOKIE, $EXTERNAL_LOGIN_URL;
			
			$cmd = $this->getRequestField('cmd'); 
			$cmd = strtolower($cmd);

			if ($cmd == "logout")
			{
				$this->appLogout ();
			} else {
				$this->appLogin ();
			}
		}
		
		function appLogin ()
		{
			global $EXTERNAL_LOGIN_URL;
			
			setcookie ("CustomerNo", $this->userId, 0, '/', 'clientdomain.com');

			header("Location: $EXTERNAL_LOGIN_URL");			
		}
		function appLogout ()
		{
			global $LOGOUT_URL;
			
	        $fullUrl = $this->getSelfUrl();
			$pos = strlen ($fullUrl);

			if ($pos = strpos($fullUrl, '?'))
			{
				$url = substr ($fullUrl, 0, $pos);
			}

			setcookie ("CustomerNo", '', 0, '/', 'clientdomain.com');
			header("Location: $LOGOUT_URL?url=$url");
			exit;
		}
		
		function authorize()
		{
			return TRUE;
		}
	}//class

	global $APPLICATION_NAME, $APP_DB_URL, $EXTERNALMGR;

	$thisApp = new externalmgrApp(
									array( 'appName'	 => $APPLICATION_NAME,
											'appVersion' => '1.0.0',
 											'appUrl'	 => $EXTERNALMGR,
											'appType'	 => 'WEB',
											'appDbUrl'	 => $APP_DB_URL,
											'appAutoAuthorize' => TRUE,
											'appAutoConnect'   => TRUE,
											'appAutoCheckSession' => TRUE,
											'appDebugger' => $OFF,
							  				'cacheType'   => "NONE"
											)
									);

	$thisApp->bufferDebugging();
	$thisApp->run();
	$thisApp->dumpDebuginfo();
?>