<?php

require_once "login.conf.php";

/*
Session variables must be defined before session_start()
method is called
*/

class loginApp extends PHPApplication {

	private $email;
	private $username;
	private $password;
	private $url;
	private $activate;
	private $ident;
	private $validate;
	private $id;
	private $specialId;
	private $Submit;
	private $remember;
	private $admin;
	private $emailLen;
	private $usernameLen;
	private $passwdLen;
	private $error;
	private $newUser;
	private $AccessType;
	private $testAdmin;

	function run()
	{
		global $MIN_USERNAME_SIZE, $MIN_PASSWORD_SIZE, $MAX_ATTEMPTS;
		global $WARNING_URL, $TEST_SERVER_DIR;
		global $PHP_SELF;
		global $DISTRIBUTOR_APP_RUNNING;

		$this->email = $this->getRequestField('email');
		$this->username = $this->getRequestField('username');
		$this->password = $this->getRequestField('password');
		$this->url = $this->getRequestField('url', $TEST_SERVER_DIR);
		if (isset ($this->url)) parse_str (parse_url ($this->url, PHP_URL_QUERY)); // get query parameters for redirect URL
		$this->testAdmin = isset ($test)? $test : null;
		$this->debug("URL: $this->url<br />Test: $this->testAdmin");

		$this->activate = $this->getRequestField('activate');
		$this->ident = $this->getRequestField('ident');
		$this->validate = $this->getRequestField('validate');
		$this->id = $this->getRequestField('id');
		$this->specialId = $this->getRequestField('lid');
		$this->Submit = $this->getRequestField('Submit');
		$this->remember = $this->getRequestField('remember');
		$this->admin = $this->getRequestField('a');
		$this->newUser = FALSE;
		
		$this->debug("URL: $this->url<br />Email: $this->email User: $this->username:".strlen ($this->username)." : $MIN_USERNAME_SIZE Pass: $this->password:".strlen ($this->password)." : $MIN_PASSWORD_SIZE");
		$this->debug("Submit: $this->Submit");
		
		$this->emailLen = strlen($this->email);
		$this->usernameLen = strlen ($this->username);
		$this->passwdLen = strlen($this->password);
		
		$this->error = "";
		
		$this->external = FALSE;
		if (strpos ($this->url,  'externalmgr') > 0)
		{
			$this->external = TRUE;
		}
		$this->debug("Login attempts : " . $this->getSessionField(APP_NAME.'_ATTEMPTS'));
		// $this->testAdmin = $this->getTestAdmin ();
		if (!isset ($this->testAdmin) && ($this->checkOrderSuspended (TRUE) || $this->getAppStatus () != $DISTRIBUTOR_APP_RUNNING) && !$this->external)  // Include warning time
		{
			
			$this->debug("Status: ".$this->getAppStatus ()." (not $DISTRIBUTOR_APP_RUNNING).");
				$status = $this->getAppStatusMessage ();
				global $CLIENT_INDEX_TEMPLATE, $LOGIN_MNGR;
				
				$this->showStatus($CLIENT_INDEX_TEMPLATE, $status);
		} else if ($this->isAuthenticated())
		{
			// return to caller HTTP_REFERRER
			$this->debug("User already authenticated.");
			$this->debug("Redirecting to $this->url.");
			$this->url = (isset($this->url)) ? $this->url : $this->getServer();             
			header("Location: $this->url");
		
		} else if (strlen($this->username) < $MIN_USERNAME_SIZE ||
			strlen($this->password) < $MIN_PASSWORD_SIZE ) {

			$this->login();		
		} else {
			// authenticate user
			
			$this->debug("Authenticate user: $this->username with password $this->password");
			
			if ($this->authenticate($this->username, $this->password))
			{
				$this->debug("User is successfully authenticated.");
				$_SESSION[APP_NAME."_USERNAME"] = $this->username;
				$_SESSION[APP_NAME."_PASSWORD"] = md5 ($this->password);
				$_SESSION[APP_NAME."_USER_ID"]  = $this->getUID();

				if ($this->newUser)
				{
					$_SESSION[APP_NAME."_NEW_USER"]  = "New User";
				} else if ($this->testAdmin && $this->AccessType >= DEFAULT_ADMIN_LEVEL)
				{
					$_SESSION[APP_NAME."_TEST"]  = "Test";
				}
				$this->debug("Session: ".print_r ($_SESSION,true));
				
				if (empty($this->url))
				{
					$this->url = $TEST_SERVER_DIR;
				}
				// Log user activity                 
				// $thisUser = new User($this->dbi, $this->getUID());
				// $thisUser->logActivity(LOGIN);
				
				header("Location: $this->url");
				
				$this->debug("Redirect user to caller application at url = $this->url.");
			
			} else {
				$this->debug("User failed authentication.");
				$this->login();
			}
		}
		$_SESSION[APP_NAME.'_ATTEMPTS'] = $this->getSessionField(APP_NAME.'_ATTEMPTS') + 1;
	}
	
	function login ()
	{
		global $CLIENT_INDEX_TEMPLATE, $LOGIN_MNGR;
		
		$template = $CLIENT_INDEX_TEMPLATE;

		if ($this->external)
		{
			global $PLN_RESULTS_TEMPLATE, $CUSTOMER_LOGIN_CONTENT_TEMPLATE;
			$template = $PLN_RESULTS_TEMPLATE;
			$this->content_template = $CUSTOMER_LOGIN_CONTENT_TEMPLATE;
		}
		$this->debug ("wms: " . $template);
		
		$this->showScreen($template, 'displayLogin', $LOGIN_MNGR);
	}
	
	function warn()
	{
		global $WARNING_URL;
		$this->debug("Came to warn the user $WARNING_URL");
		header("Location: $WARNING_URL");
	}
	
	function displayLogin(&$t)
	{
		global $LOGIN_CONTENT_TEMPLATE, $TEMPLATE_DIR, $REL_TEMPLATE_DIR,
				$FORGOTTEN_PASSWORD_APP, $USER_ACCESS_DIR;
		global $MAX_ATTEMPTS;
		
		if ($this->getSessionField(APP_NAME.'_ATTEMPTS') > $MAX_ATTEMPTS)
		{
			$this->warn();
		}
		$template = new HTML_Template_IT($TEMPLATE_DIR);
		
		if (!isset ($this->content_template))
		{
			$this->content_template = $LOGIN_CONTENT_TEMPLATE;
		}

		$this->debug ("login content: $this->content_template");
		$template->loadTemplatefile($this->content_template, false, true);

		$template->setVariable('AppRedirect', $this->getServer());

		// $this->debug ( "Session: $userSession");
		$template->setVariable('UserName', $this->UserName);
		// $template->setVariable('SessionId', $userSession);
		$template->setVariable('REDIRECT_URL', $this->url);
		$template->setVariable('FORGOTTEN_PASSWORD_APP', $FORGOTTEN_PASSWORD_APP);
	
		$template->setVariable('Id', $this->specialId);
		$this->debug ( "Error: $this->error");
		
		$template->setVariable('Error', $this->error);
	
		$this->doFinalTemplateWork($template);
		$template->parse ();
		$t->setVariable('ManageContent', $template->get());
		
		return 1;
	}
	
	function displayAdminLogin(&$t)
	{
		global $TEMPLATE_DIR, $LOGIN_ADMIN_TEMPLATE, $MAX_ATTEMPTS;
				
		if ($this->getSessionField(APP_NAME.'_ATTEMPTS') > $MAX_ATTEMPTS)
		{
			$this->warn();
		}
		$template = new HTML_Template_IT($TEMPLATE_DIR);
		
		$this->debug ("login admin: " . $LOGIN_ADMIN_TEMPLATE);
		$template->loadTemplatefile($LOGIN_ADMIN_TEMPLATE, true, true);
		
		$template->setVariable('APP_ADMIN_NAME', $this->app_name);
		
		$this->doFinalTemplateWork($template);
		$template->parse ();
		
		$t->setVariable('ManageContent', $template->get());
		
		return 1;
	}

	function isAuthenticated()
	{
		return (!empty($_SESSION[APP_NAME."_USERNAME"])) ? TRUE : FALSE;
	}

	function authenticate($user = null, $passwd = null)
	{
		global $AUTH_DB_URL, $DEFAULT_PASSWORD, $HOME_URL, $SELECT_USER;
		
		$this->debug("Function authenticate user: $user with password $passwd");
		$authObj = new Authentication($user, $passwd, $AUTH_DB_URL);
		
		if ($authObj->authenticate())
		{
			$uid = $authObj->getUID();
			$this->debug("Setting user id to $uid");
			$this->AccessType = $authObj->getAccessType ();
			if ($this->AccessType >= DEFAULT_ADMIN_LEVEL)
			{
				$this->setUID($uid);
				$this->url = $HOME_URL."?cmd=".$SELECT_USER;
			} else {
				$this->setUID($user);	// Admin - logged in as user
			}
			return TRUE;
		} else if ($passwd == $DEFAULT_PASSWORD) 
		{
			global $APP_DB_URL, $USER_UPDATE_PROFILE_URL;
			
			if ($appDbi = $this->connect ($APP_DB_URL))  // Connect to the App DB for Distributor info
			{
				$distributor = new Distributor ($appDbi,$user);
				$userObj = new User ($this->dbi);
				$userObj->setIsUser ($userObj->getUserIDByName ($user));
				
				$this->debug("Testing New User: ".$userObj->getUID ());
				if (!$userObj->getIsUser () and $distributor->getIsDistributor ($appDbi) and $distributor->getActive () != 'I')
				{
					$this->setUID($user);
					$this->url = $USER_UPDATE_PROFILE_URL;
					$this->debug("New User Must reset password - $this->url");
					$this->newUser = TRUE;
					$this->AccessType = $authObj->getAccessType ();
					return TRUE;
				}
			}
		}
		$this->debug("Login Failure");
		$this->error = "Login Failure Please Re-enter";
		return FALSE;
	}
}

global $AUTH_DB_URL;

$thisApp = new loginApp(
						array(
							  'appName'             => $APPLICATION_NAME,
							  'appUrl'              => $LOGIN_MNGR,
							  'appVersion'          => '1.0.5',
							  'appType'             => 'WEB',
							  'appDbUrl'            => $AUTH_DB_URL,
							  'appAutoAuthorize'    => FALSE,
							  'appAutoCheckSession' => FALSE,
							  'appAutoConnect'      => TRUE,
							  'appDebugger'         => $OFF,
							  'cacheType'           => "NONE"
							 )
						 );
$thisApp->bufferDebugging();
$thisApp->debug("This is $thisApp->appName application");
$thisApp->run();
$thisApp->dumpDebuginfo();

?>
