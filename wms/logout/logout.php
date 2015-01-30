<?php

	require_once "logout.conf.php";
	
	
	class logoutApp extends PHPApplication {
	
		function run()
		{
			global $AUTHENTICATION_URL, $APP_DIR;
			
			if ($this->isAuthenticated ())
			{
				$this->logout(); // the method to log off
			
			}
			$this->url = $this->getRequestField('url', 'http://pointbeer.com'.$APP_DIR);

			header("Location: $AUTHENTICATION_URL?url=$this->url");
		}
		
		function logout()
		{
			session_unset();
			session_destroy();
			// exit;
		}
		
		function isAuthenticated()
		{
			$SESS_UNAME = $this->getSessionField(APP_NAME.'_USERNAME');
			
			if (!empty($SESS_UNAME))
			{
				return 1;
			
			} else {
			
				return 0;
			}
		}
	}
	
	/* Session variables must be defined before session_start() method is called */
	$count            = 0;
	$SESSION_USERNAME = null;
	$SESSION_PASSWORD = null;
	$SESSION_USER_ID = null;
	
	global $AUTH_DB_URL;
	
	$thisApp = new logoutApp(array ('appName'          => $APPLICATION_NAME,
									'appVersion'       => '1.0.2',
									'appUrl'	       => $LOGOUT_URL,
									'appType'          => 'WEB',
									'appDbUrl'         => $APP_DB_URL,
									'appAutoAuthorize' => FALSE,
									'appAutoConnect'   => FALSE,
									'appAutoCheckSession' => TRUE,
									'appDebugger'      => $OFF,
							  		'cacheType'   => "BROWSER" 
							 	   )
							 );
	
	$thisApp->bufferDebugging();
	$thisApp->debug("This is $thisApp->appName application");
	$thisApp->run();
	$thisApp->dumpDebuginfo();
?>
