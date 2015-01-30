<?php
/*
* PHPApplication class
*
* @access public
*/

/*
	This class handles all the common functionality for the varous modules.
	This includes form processing and authorization.
*/

abstract class PHPApplication {

	public $appName;
	private $appUrl;
	private $appVersion;
	private $appType;
	private $appDbUrl;
	private $debugMode;
	private $autoConnect;
	private $autoCheckSession;
	private $autoAuthorize;
	private $sessionOK;
	private $extLogin;
	private $error;
	private $authorized;
	private $language;
	private $baseUrl;
	private $appPath;
	private $buttonPath;
	private $imagePath;
	private $templateDir;
	private $mainTemplateDir;
	private $messages;
	private $LogoutURL;
	private $accessUserDir;
	private $appTextName;
	private $sessionUser;
	private $loggedin;
	private $username;
	private $testAdmin;
	private $metaIndexFollow;
	public $pagelimit;
	private $pagestart;
	public $debugger;
	private $dbgmsg;
	private $hasError;
	public $pageProtect;
	private $server;
	private $selfUrl;
	public $dbi;
	public $userId;
	private $appDownMessage;
	private $appStatus;
	private $appStatusMsg;

	function __construct($param = null)
	{
		global $ON, $OFF, $TEMPLATE_DIR, $MAIN_TEMPLATE_DIR;
		
		global $MESSAGES, $META_INDEX_FOLLOW, $DEFAULT_LANGUAGE, $APPLICATION_TEXT_NAME,
               $REL_APP_PATH, $IMAGE_PATH, $BUTTON_PATH, $LOGOUT_URL, $TEST_SERVER_DIR, $USER_ACCESS_DIR;

		$dbgMsg = "Debugging\n\r";
		// initialize application
		$this->appName 	  = $this->setDefault($param['appName'], null);
		$this->appUrl 	  = $this->setDefault($param['appUrl'], null);
		$this->appVersion = $this->setDefault($param['appVersion'], null);
		$this->appType 	  = $this->setDefault($param['appType'], null);
		$this->appDbUrl   = $this->setDefault($param['appDbUrl'], null);
		
		$this->debugMode	= $this->setDefault($param['appDebugger'], null);
		
		$this->autoConnect 	= $this->setDefault($param['appAutoConnect'], TRUE);
		$this->autoCheckSession	= $this->setDefault($param['appAutoCheckSession'], TRUE);
		$this->autoAuthorize 	= $this->setDefault($param['appAutoAuthorize'], TRUE);
		
		$this->sessionOK       = FALSE;
		// $this->sessionOK       = $this->setDefault($param['appAutoAuthorize'], FALSE);
		// $this->extLogin        = $this->setDefault($param['appExtLogin'], FALSE); 
		
		$this->error		= array();
		$this->authorized	= FALSE;
		$this->language         = $DEFAULT_LANGUAGE;
		
		$this->appPath          = $REL_APP_PATH;
		$this->buttonPath       = $BUTTON_PATH;
		$this->imagePath        = $IMAGE_PATH;
		$this->templateDir	    = $TEMPLATE_DIR;
		$this->mainTemplateDir	= $MAIN_TEMPLATE_DIR;
		$this->messages	        = $MESSAGES;
		$this->LogoutURL        = $LOGOUT_URL;
		$this->accessUserDir    = $USER_ACCESS_DIR;
		$this->appTextName      = $APPLICATION_TEXT_NAME;
		$this->sessionUser      = $this->getRequestField('s');
		
		$this->session = $this->getRequestField('PHPSESSID');
		$this->loggedin = $this->getRequestField('loggedin');
		
		$this->username = $this->getRequestField('username');
		$this->metaIndexFollow = $META_INDEX_FOLLOW;
		
		$this->pagelimit = 25;
		$this->pagestart = $this->pagelimit;
		
		// If debuggger is ON then create a debugger object
		
		if ($this->debugMode == $ON)
		{
            if (empty($param['debugColor']))
            {
                $param['debugColor'] = 'red';
            }
            $this->debugger = new Debugger(array('color' 	=> $param['debugColor'],
                                                 'prefix' 	=> $this->appName,
                                                 'buffer'	=> $OFF));
        } 
		$this->dbgmsg = "";

        // load error handler
        $this->hasError = null;

        $this->setErrorHandler();

            if (!empty($this->appDbUrl) && $this->autoConnect && !$this->connect())
            {
               $this->alert('APP_FAILED');
            }

        // start session

        if (strstr($this->getType(), 'WEB'))
        {
			$this->baseUrl         = sprintf("%s%s", $this->getServer(), $TEST_SERVER_DIR);
			$this->cacheType = $this->setDefault($param['cacheType'], null);
			$this->setCache ($this->cacheType);
            session_start();
			// $this->debug ( "Session: ".print_r($_SESSION, true));
			if (!empty ($_SESSION))
			{
				$msg =  print_r($_SESSION, true)."<br />";
	            $this->userId      = (! empty($_SESSION[APP_NAME.'_USER_ID']))  ? $_SESSION[APP_NAME.'_USER_ID'] : null;
    	        $this->UserName    = (! empty($_SESSION[APP_NAME.'_USERNAME'])) ? $_SESSION[APP_NAME.'_USERNAME']: null;
        	    $this->user_email  = (! empty($_SESSION[APP_NAME.'_USERNAME'])) ? $_SESSION[APP_NAME.'_USERNAME']: null;
				$this->AppRedirect = (! empty($_SESSION[APP_NAME.'_AppRedirect'])) ? $_SESSION[APP_NAME.'_AppRedirect']: null;
			}
			$this->testAdmin = $this->getRequestField('test', ! empty($_SESSION[APP_NAME.'_TEST']) ? $_SESSION[APP_NAME.'_TEST']: null);
			// $this->checkAppDown ();	// Doesn't appear to be doing anything yet.
			
            if ($this->autoCheckSession) $this->checkSession();

            if ($this->autoAuthorize && ! $this->authorize()) // Authorized for this app?
			{
			   $this->alert('UNAUTHORIZED_ACCESS');
			}
			$this->checkAppStatus ('DataTransfer');  // check status (running/data transfer/maintenance
			if (!isset ($this->UserName))
			{
				$this->UserName = "";
			}
         }
     }

     function getEMAIL()
     {
        return $this->user_email;
     }

	function getPageStart ()
	{
		return $this->pagestart;
	}
	
	function setPageStart ($ps = 1)
	{
		$this->pagestart = $ps;
	}
	
	function getPageLimit ()
	{
		return $this->pagelimit;
	}

     function getNAME()
     {
         list($name, $host) = explode('@', $this->getEMAIL());
         return ucwords($name);
     }
	 
	 function getTestAdmin ()
	 {
		$this->debug("Test: $this->testAdmin");
		 return ($this->testAdmin);
	 }

     function checkSession()
     {		
        if ($this->sessionOK == TRUE)
        {
            return TRUE;
        }

        if (!empty($this->UserName))
        {
            $this->sessionOK = TRUE;

        } else {
			
            $this->sessionOK = FALSE;

            $this->reauthenticate();
        }
        return $this->sessionOK;
     }
	 
	 function checkAppDown ()
	 {
	 	global $DATA_TRANSFER_APP, $LOGIN_APP;
		
		$this->appDownMessage = "";
	 	if (!$this->testAdmin && $this->appName != $DATA_TRANSFER_APP)
		{
			//if ($this->appName == $LOGIN_APP)
			
			// $this->appDownMessage =  
		} else {
			return FALSE;
		}
	 }
	 
	function checkOrderSuspended ($warning=false)
	{
		global $SUSPEND_WARNING, $SUSPEND_STARTTIME, $SUSPEND_ENDTIME;
		
		$curTime = strtotime (date_format (date_create("now", timezone_open('America/Chicago')), 'H:i:s'));
		// $curTime = ($curTime < 12 * 60 * 60)? $curTime + 24 * 60 * 60: $curTime;
		$suspendTime = strtotime ($SUSPEND_STARTTIME);
		$minutes=60;
		if ($warning) $suspendTime -=  $SUSPEND_WARNING * $minutes;
		$endTime = strtotime ($SUSPEND_ENDTIME);
		// $endTime = ($endTime < 12 * 60 * 60)? $endTime + 24 * 60 * 60: $endTime;
		if ($curTime < $endTime or $curTime > $suspendTime)
		{
			$suspend = TRUE;
			 global $APP_DB_URL, $DISTRIBUTOR_APP_DATA_TRANSFER;
					
			$dbi = $this->connect($APP_DB_URL);
			$obj = new DataTransfer ($dbi);
			
			$obj->updateDataTransferStatus ($DISTRIBUTOR_APP_DATA_TRANSFER);
		}
		else
		{
			$suspend = FALSE;
			 global $APP_DB_URL, $DISTRIBUTOR_APP_RUNNING;
					
			$dbi = $this->connect($APP_DB_URL);
			$obj = new DataTransfer ($dbi);
			
			$obj->updateDataTransferStatus ($DISTRIBUTOR_APP_RUNNING);
		}
		return $suspend;
	}
	
	 function checkAppStatus ($statusClass = 'DataTransfer')
	 {
		 global $DISTRIBUTOR_APP_RUNNING, 
		 		$DISTRIBUTOR_APP_DATA_TRANSFER, 
				$DISTRIBUTOR_APP_MAINTENANCE;
		 global $ORDERS_SUSPENDED, $ORDERS_RUNNING, $ORDERS_MAINTENANCE;
		 global $APP_DB_URL;
				
		$dbi = $this->connect($APP_DB_URL);
		$obj = new DataTransfer ($dbi);
		
		$this->appStatus = $obj->Status;
		
		switch ($this->appStatus)
		{
			case $DISTRIBUTOR_APP_RUNNING:
				$this->appStatusMsg = $ORDERS_RUNNING;
				break;
			case $DISTRIBUTOR_APP_DATA_TRANSFER:
				$this->appStatusMsg = $ORDERS_SUSPENDED;
				break;
			case $DISTRIBUTOR_APP_MAINTENANCE:
				$this->appStatusMsg = $ORDERS_MAINTENANCE;
				break;
			default:
				$this->appStatusMsg = null;		
		}
 		$this->debug ("Application Status: $this->appStatus<br />
$this->appStatusMsg");
	 }
	 
	 function getAppStatusMessage ()
	 {
		return $this->appStatusMsg;
	 }

	 function getAppStatus ()
	 {
		return $this->appStatus;
	 }

     function getBaseURL()
     {
        return $this->baseUrl;
     }

     function getServer()
     {
        $this->setUrl();
        return $this->server;
     }

     function getSelfUrl()
     {
        $this->setUrl();
        return $this->selfUrl;
     }

     function getAppPath()
     {
        return $this->appPath;
     }

     function getFQAP()
     {
        // get fully qualified application path

        return sprintf("%s%s",$this->server, $this->appPath);
     }

     function getFQAN($thisApp = null)
     {
        return sprintf("%s/%s", $this->getFQAP(), $thisApp);
     }

     function getTemplateDir($type='main')
     {
	 	global $TEMPLATE_MNGR;
		
 		$this->debug ("TEMPLATE_MNGR: $TEMPLATE_MNGR, type: [$type]");
	 	if ($type == $TEMPLATE_MNGR)
		{
 			$this->debug ("Template Directory: $this->mainTemplateDir");
			return $this->mainTemplateDir;
		}
		else
		{
        	return $this->templateDir;
		}
     }

     function setUrl()
     {
         $row_protocol = $this->getEnvironment('SERVER_PROTOCOL');

         $port  = $this->getEnvironment('SERVER_PORT');

         if ($port == 80)
         {
             $port = null;
         } else {
             $port = ':' . $port;
         }
         $protocol = strtolower(substr($row_protocol,0, strpos($row_protocol,'/')));

         $this->server = sprintf("%s://%s%s", 
                                $protocol, 
                                $this->getEnvironment('HTTP_HOST'), 
                                $port);

         $this->selfUrl = sprintf("%s://%s%s%s", $protocol,
                                   $this->getEnvironment('HTTP_HOST'),
                                   $port,
	                              $this->getEnvironment('REQUEST_URI'));
     }

     function getServer_extra()
     {
     	return $this->server;
     }

     function terminate()
     {
        if (isset($this->dbi))
        { 
	   if ($this->dbi->connected) {
             $this->dbi->disconnect();
           }
        }   
     	//Asif Changed
     	session_destroy();
        exit;
     }

     function reauthenticate()
     {
         global $AUTHENTICATION_URL;   
		             
	     header("Location: $AUTHENTICATION_URL?url=$this->selfUrl");
		 exit;
     }

     function authorize($username = null)
	 {
	 }
	 
	 function setCache ($cacheType=null)
	 {
	 	$interval = 60;
	 	switch ($cacheType)
		{
			case "NOVALIDATE":
				$now = time ();
				$prety_lmtime = gmdate ('D, d M Y H:i:s', $now). ' GMT';
				$prety_emtime = gmdate ('D, d M Y H:i:s', $now + $interval). ' GMT';
				// Backwards Compatibility
				header ("Last Modified: $prety_lmtime");
				header ("Expires: $prety_emtime");
				// HTTP/1.1 Support
				header ("Cache-Control: public, max-age=$interval");
				break;
			case "BROWSER":
				$now = time ();
				$prety_lmtime = gmdate ('D, d M Y H:i:s', $now). ' GMT';
				$prety_emtime = gmdate ('D, d M Y H:i:s', $now + $interval). ' GMT';
				// Backwards Compatibility
/*				header ("Last Modified: $prety_lmtime");
				header ("Expires: $prety_emtime");
				// HTTP/1.1 Support
				header ("Cache-Control: private, max-age=$interval,s-maxage=0");
				header ("Cache-Control: post-check=0, pre-check=0", false); 
*/
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", false);
				header("Pragma: no-cache");
				break;
			case "NONE":
				// Backwards Compatibility
/*
				header ("Expires: 0");
				header ("pragma: no-cache");
				// HTTP/1.1 Suport
				header ("Cache-Control: no-cache,no-store,max-age=0,s-maxage=0, must-revalidate");
				header ("Cache-Control: post-check=0, pre-check=0", false);
*/
				header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
				header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
				header("Cache-Control: no-store, no-cache, must-revalidate");
				header("Cache-Control: post-check=0, pre-check=0", false);
				header("Pragma: no-cache");

				break;
			default:
				break;
		}
	 }

     function setErrorHandler()
     {
        // create error handler
	    $this->errHandler = new ErrorHandler(
                                array ('name' => $this->appName));
     }

     function getErrorMessage_extra($code)
     {
        return $this->errHandler->error_message[$code];
     }

     function showPopup($code)
     {
		return $this->errHandler->alert($code, 0);
     }

     function getMessage($code = null, $hash = null)
     {
        $msg = $this->messages[$this->language][$code];

        if (! empty($hash))
        {
            foreach ($hash as $key => $value)
            {
               $key = '/{' . $key . '}/';
               $msg = preg_replace($key, $value, $msg);
            }
        }
        return $msg;
     }

     function alert($code = null, $flag = null)
     {
		return $this->errHandler->alert($code, $flag);
     }

     function bufferDebugging()
     {
        global $ON;

        if (defined("DEBUGGER_LOADED") && $this->debugMode == $ON)
        {
            $this->debugger->set_buffer();
        }
     }

     function dumpDebuginfo()
     {
        global $ON;

        if (defined("DEBUGGER_LOADED") && $this->debugMode == $ON)
        {
            $this->debugger->flush_buffer();
        }
     }

     function debug($msg)
     {
        global $ON;
        if ($this->debugMode == $ON) {
            $this->debugger->write($msg);
        }
     }

     function run()
     {
        // run the application
        $this->writeln("You need to override this method.");
     }

     function connect($db_url = null)
     {	
		if (empty($db_url))
		{
			if (! empty($this->appDbUrl))
			{
				$this->dbi = new DBI($this->appDbUrl);
				
				return $this->dbi->connected;
			}
		} else {
			$dbi = new DBI($db_url);
			
			return $dbi;
		}
     }

     function disconnect()
     {
         $this->dbi->disconnect();
         $this->dbi->connected = FALSE;

         return $this->dbi->connected;
     }

     function getErrorMessage($code = null)
     {
        return $this->errHandler->get_error_message($code);
     }

     function showDebuggerBanner()
     {
        global $ON;

        if ($this->debugMode == $ON)
        {
            $this->debugger->print_banner();
        }
     }

     function getVersion()
     {
        // return version
        return $this->appVersion;
     }

     function getName_extra()
     {
        // return name
        return $this->appName;
     }

     function getType()
     {
        // return type
        return $this->appType;
     }

     function setError($err = null)
     {
        // set error condition
        if (isset($err))
        {
           array_push($this->error, $err);
           $this->hasError = TRUE;
           return 1;
        } else {
           return 0;
        }
     }

     function hasError()
     {
        return $this->hasError;
     }

     function resetError()
     {
        $this->hasError = FALSE;
     }

     function getError()
     {
        // return error condition
        return arrayPop($this->error);
     }

     function getErrorArray()
     {
        return $this->error;
     }

     function dumpArray($a)
     {
        if (strstr($this->getType(), 'WEB'))
        {
           echo '<pre>';
           print_r($a);
           echo '</pre>';
        } else {
           print_r($a);
        }
     }

     function dump()
     {
        if (strstr($this->getType(), 'WEB'))
        {
           echo '<pre>';
           print_r($this);
           echo '</pre>';
        } else {
           print_r($this);
        }
     }

     function checkRequiredFields($fieldType = null, $fieldData = null, $errorCode = null)
     {
         $err = array();

         while(list($field, $func) = each ($fieldType))
         {
            $ok = $this->$func($fieldData[$field]);

            if (! $ok )
            {
               $this->alert(null, $errorCode{$field});
            }

         }
         return $err;
     }

     function number($num = null)
     {
        if (is_array($num))
        {
           foreach ($num as $i)
           {
              if (! is_numeric($i))
              {
              	return 0;
              }
           }
           return 1;

        } else if (is_numeric($num))
        {
            return 1;
        } else {
            return 0;
        }
     }

     function name($name = null)
     {
        if (!strlen($name) || is_numeric($name))
        {
          return 0;
        } else {
          return 1;
        }
     }

     function email($email = null)
     {
        if (strlen($email) < 5 || ! strpos($email,'@'))
        {
            return 0;
        } else {
            return 1;
        }
     }

	// Extensive Email address validation
	function validateEmail($email, $domainCheck = false, $verify = false, $return_errors=false) 
	{
		$this->debug ("<pre>");
		# Check syntax with regex
		if (preg_match('/^([a-zA-Z0-9\._\+-]+)\@((\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,7}|[0-9]{1,3})(\]?))$/', $email, $matches)) 
		{
			$user = $matches[1];
			$domain = $matches[2];
							
			$this->debug ("User: $user<br />Domain: $domain<br />");
			
			# Check availability of DNS MX records
			if ($domainCheck && function_exists('checkdnsrr')) 
			{
				# Construct array of available mailservers
				if(getmxrr($domain, $mxhosts, $mxweight)) 
				{
					for($i=0;$i<count($mxhosts);$i++)
					{
						$mxs[$mxhosts[$i]] = $mxweight[$i];
					}
					asort($mxs);
					$mailers = array_keys($mxs);
				} elseif(checkdnsrr($domain, 'A')) {
					$mailers[0] = gethostbyname($domain);
				} else {
					$mailers=array();
				}
				$total = count($mailers);
				# Query each mailserver
				if($total > 0 && $verify) 
				{
					# Check if mailers accept mail
					for($n=0; $n < $total; $n++) 
					{
						# Check if socket can be opened
						$this->debug ("Checking server $mailers[$n]...<br />");
						$connect_timeout = 2;
						$errno = 0;
						$errstr = 0;
						$probe_address = 'me@mydomain.com';
						# Try to open up socket
						if($sock = @fsockopen($mailers[$n], 25, $errno , $errstr, $connect_timeout)) 
						{
							$response = fgets($sock);
							$this->debug ("Opening up socket to $mailers[$n]... Success!<br />");
							stream_set_timeout($sock, 5);
							$meta = stream_get_meta_data($sock);
							$this->debug ("$mailers[$n] replied: $response<br />");
							$cmds = array(
								"HELO authorbytes.com",  # Be sure to set this correctly!
								"MAIL FROM: <$probe_address>",
								"RCPT TO: <$email>",
								"QUIT",
							);
							# Hard error on connect -> break out
							if(!$meta['timed_out'] && !preg_match('/^2\d\d[ -]/', $response)) 
							{
								if (preg_match('/^4\d\d[ #]/', $response))
								{
									$error = "400 Error - need to retry\n";
								}
								else
								{
									$error = "Error: $mailers[$n] said: $response\n";
								}
								break;
							}
							foreach($cmds as $cmd) 
							{
								$before = microtime(true);
								fputs($sock, "$cmd\r\n");
								$response = fgets($sock, 4096);
								$t = 1000*(microtime(true)-$before);
								$this->debug (htmlentities("$cmd\n$response") . "(" . sprintf('%.2f', $t) . " ms)<br />");
								if(!$meta['timed_out'] && preg_match('/^5\d\d[ -]/', $response)) 
								{
									$error = "Unverified address: $mailers[$n] said: $response";
									break 2;
								}
							}
							fclose($sock);
							$this->debug ("Succesful communication with $mailers[$n], no hard errors, assuming OK<br />");
							break;
						} elseif($n == $total-1) {
							$error = "None of the mailservers listed for $domain could be contacted";
						}
					}
				} elseif($total <= 0) {
					$error = "No usable DNS records found for domain '$domain'";
				}
			}
		} else {
			$error = 'Address syntax not correct';
		}
		$this->debug ("</pre>");

		if($return_errors) 
		{
			# Give back details about the error(s).
			# Return FALSE if there are no errors.
			# Keep this in mind when using it like:
			# if(checkEmail($addr)) {
			# Because of this strange behaviour this
			# is not default ;-)

			if(isset($error)) return htmlentities($error); else return false;
		} else {
			# 'Old' behaviour, simple to understand
			if(isset($error))
			{
				$this->alert ('INVALID_EMAIL');
				return false; 
			} else {
				return true;
			}

		}
	}
	
	function checkDnsrr($host,$recType='') 
	{
		if(!empty($host)) 
		{
			if($recType=='') $recType="MX";
			exec("nslookup -type=$recType $host",$output);
			foreach($output as $line) 
			{
				if(preg_match("/^$host/", $line)) 
				{
					return true;
				}
			}
			return false;
		}
		return false;
	}
	
	function checkDate ($dt = null)
	{
		if (!$dt) return false;

		$dtArray = getdate (strtotime ($dt));
		
		$valid = checkdate ($dtArray ['mon'], $dtArray ['mday'], $dtArray ['year']);
		
		if ($valid)
		{
			$valid = strcmp ("1969-12-31", $dtArray ['year']."-".$dtArray ['mon']."-".$dtArray ['mday']);
		}
		return $valid;
	}

	function dateRange ($dt, $range)
	{
		if ($range == 0) return false;  // no range

		$d = strtotime ($dt);
		$today = getdate ();
		$day1 = mktime (0, 0, 0, $today ["mon"], $today ["mday"], $today ["year"]);

		$minutes=60;
		$hours=$minutes*60;
		$days=$hours*24;
		$diff = ($d - $day1)/$days;  // Add one day past midnight

		$a = date ("m/d/y", $day1);

		if ($diff < ($range)) 
		{
			return false;  // Date is less than range
		}
		return true; // Date is beyond range
	}
	
     function currency($amount = null)
     {
        return 1;
     }

     function month($mm = null)
     {
        if ($mm >=1 && $mm <=12)
        {
           return 1;
        } else {
           return 0;
        }
     }

     // ASIF what is thie method doing in this class???
     function comboOption($optVal = null)
     {
       if ($optVal != 0)
       {
       	return 1;
       }else {
       	return 0;
       }
     }

     function day($day = null)
     {
        if ($day >=1 && $day <=31)
        {
           return 1;
        } else {
           return 0;
        }
     }

     function year($year = null)
     {
        return ($this->number($year));
     }

     function oneZeroFlag($flag = null)
     {
        if ($flag == 1 || $flag == 0)
        {
            return 1;

        } else {

            return 1;
        }
     }

     function plainText($text = null)
     {
        return 1;
     }

     function debugArray($hash = null)
     {
        $this->debugger->debug_array($hash);
     }

     function writeln($msg)
     {
        // print
        global $WWW_NEWLINE;
        global $NEWLINE;
        echo $msg ,(strstr($this->appType, 'WEB')) ? $WWW_NEWLINE :  $NEWLINE;
     }

     function showStatus($mainTemplate = null, $msg = null,$returnURL = null, $returnType = null)
     {
        global $STATUS_TEMPLATE, $TEMPLATE_MNGR, $HOME_APP;
		
		$this->debug ($this->appName . ": ".$mainTemplate);
        $template = new HTML_Template_IT($this->getTemplateDir($TEMPLATE_MNGR));
        // $template->loadTemplatefile($mainTemplate, true, true);
       $this->doCommonTemplateWork($template, $mainTemplate, $this->appName);

        $t = new HTML_Template_IT($this->getTemplateDir($TEMPLATE_MNGR));
        $t->loadTemplatefile($STATUS_TEMPLATE, true, true);

        $t->setVariable('STATUS_MESSAGE', $msg);
        if (!preg_match('/^http:/', $returnURL) && (!preg_match('/^\//', $returnURL)))
        {
            $appPath = sprintf("%s/%s", $this->appPath, $returnURL);

        } else {

            $appPath = $returnURL;
        }
        $t->setVariable('RETURN_URL', $appPath);
        $t->setVariable('MAIN_MENU', $HOME_APP);

		if (isset ($returnType))
		{
         	$t->setVariable('RETURN_TYPE', $returnType);
		}
        $t->setVariable('BASE_URL', $this->baseUrl);
		
		$t->parse ();

		$template->setVariable('ManageContent', $t->get());
        $template->show();
     }

    function setEscapedVar($hash)
    {
    	while(list($key, $value) = each ($hash))
    	{
    	   $this->escapedVarHash{$key} = preg_replace("/\s/","+",$value);
    	}
    }

    function getEscapedVar($key)
    {
    	return $this->escapedVarHash{$key};
    }

    function setUID($uid = null)
    {
       $this->userId = $uid;
    }

    function getUID()
    {
       return $this->userId;
    }
    
    //To Kabir: I added this -- Asif
    function getUserName()
    {
       return $this->UserName;	
    }

    function emptyError($field, $errCode, $errMsg=null)
    {
      if (empty($field))
      {
         $this->alert($errCode, $errMsg);
		 return true;
      }
	  return false;
    }

    function notissetError($field, $errCode, $errMsg=null)
    {
      if (!isset($field))
      {
         $this->alert($errCode, $errMsg);
		 return true;
      }
	  return false;
    }

    function valueError($test, $errMsg=null)
    {
      if (empty($field))
      {
         $this->alert($errCode, $errMsg);
		 return true;
      }
	  return false;
    }

    function zeroError($field, $errCode)
    {
      if ($field == 0)
      {
         $this->alert($errCode, null);
		 return true;
      }
	  return false;
    }

    function generalError($errMsg=null)
    {
         $this->alert($errMsg, null);
    }

    function getRequestField($field, $default = null)
    {
		// Don't forget to remove whitespace from input string
    	return (! empty($_REQUEST[$field] )) ? trim ($_REQUEST[$field]) : $default;
    }
    
    function processAllRequestField()
    {
		$data = array ();
 		// loop through form input
		foreach ($_REQUEST as $key => $value) {
			if ($key != "step" && $key != "PHPSESSID")
				$data[$key] = $value;
		}
		return $data;
    }
    
    function getSessionField($field, $default = null)
    {
    	return (! empty($_SESSION[$field] )) ? $_SESSION[$field] : $default;
    }
    
    
    function setDefault($value, $default)
    {
       return (isset($value)) ? $value : $default;
    }

    function fileextension($filename)
    {
       return substr(basename($filename), strrpos(basename($filename), ".") + 1);
    }

    function outputTemplate(&$t)
    {
       $t->show( );
       return true;
    }

	function setReturnURL (&$t, $mngr)
	{
        if (!preg_match('/^http:/', $mngr) && (!preg_match('/^\//', $mngr)))
        {
            $appPath = sprintf("%s/%s", $this->appPath, $mngr);

        } else {

            $appPath = $$mngr;
        }
        $t->setVariable('RETURN_URL', $appPath);
	}
	
    function showScreen($templateFile = null, $func = null, $appName, $extra_func = null)
    {
		global $TEMPLATE_MNGR;

        $menuTemplate = new HTML_Template_IT($this->getTemplateDir($TEMPLATE_MNGR), array('use_preg'=>false));

 		$this->debug ("Application Template: $templateFile");
       $this->doCommonTemplateWork($menuTemplate, $templateFile, $appName);

        if ($func != null and $extra_func == null)
        {
           $status = $this->$func($menuTemplate);
        }
		else if ($func != null and $extra_func != null)
		{
           $status = $this->$func($menuTemplate, $extra_func);
		}
        // $this->doFinalTemplateWork($menuTemplate, $templateFile, $appName);
        if ($status)
        {
            return $this->outputTemplate($menuTemplate);

        } else {
$this->debug ("Error");

            return null;
        }
    }

    function doCommonTemplateWork(&$t, $templateFile, $appName)
    {
	  	global $MAIN_ADMIN_TEMPLATE, $TEMPLATE_DIR, $MAIN_NAV_TEMPLATE, 
		       $MAIN_CONTENT_TEMPLATE, $USER_ACCESS_DIR, $REL_ROOT_PATH, $CLIENT_NAME, 
			   $CHAR_SET, $COMPANY_LOGO, $HOME_APP;

		$t->loadTemplatefile($templateFile, true, true);

		$this->debug ("Application: $this->appName");
        $t->setVariable(array(
                         'CharSet'      => $CHAR_SET,
                         'APP_PATH'     => $this->getAppPath(),
                         'APP_NAME'     => $this->appUrl,
                         'AuthorName'   => $CLIENT_NAME,
                         'AccessUser'   => $this->accessUserDir,
						 'BUTTON_PATH'  => $this->buttonPath,
						 'IMAGE_PATH'   => $this->imagePath,
                         'BASE_URL'     => $this->getBaseURL(),
						 'LogoutURL'    => $this->LogoutURL,
						 'UserName'     => $this->UserName,
						 'Website'      => $this->baseUrl,
						 'APP_TEXT_NAME'    => $this->appTextName,
						 'MetaIndexFollow'  => $this->metaIndexFollow,
                        )
                   );
    }

    function showScreenPlain($templateFile = null, $func = null, $appName, $extra_func = null)
    {
        $menuTemplate = new HTML_Template_ITX($this->getTemplateDir($appName));

        $this->doCommonTemplateWorkPlain($menuTemplate, $templateFile, $appName);

        if ($func != null)
        {
           $status = $this->$func($menuTemplate);
        }

        if ($status)
        {
            return $this->outputTemplate($menuTemplate);

        } else {

            return null;
        }
    }
	
    function getScreen($templateFile = null, $func = null, $appName, $extra_func = null)
    {
		global $TEMPLATE_MNGR;

        $menuTemplate = new HTML_Template_IT($this->getTemplateDir($TEMPLATE_MNGR), array('use_preg'=>false));

 		$this->debug ("Application Template: $templateFile");
       $this->doCommonTemplateWork($menuTemplate, $templateFile, $appName);

        if ($func != null and $extra_func == null)
        {
           $template = $this->$func($menuTemplate);
        }
		else if ($func != null and $extra_func != null)
		{
           $template = $this->$func($menuTemplate, $extra_func);
		}
        if ($template)
        {
			// $this->outputTemplate($template); exit;
            return $template;

        } else {
$this->debug ("Error");

            return null;
        }
    }

	function doSiteExtras (&$t)
	{
		global $CLIENT_IMAGE_PATH, $CLIENT_IMAGE_TEXT_PATH, $CLIENT_IMAGE_TEXT_FILE_NAME, 
		       $IMAGE_FILE, $IMAGE_TEXT, $IMAGE_FILE_DELIMINATOR;
		
		$filename = $CLIENT_IMAGE_TEXT_PATH.$CLIENT_IMAGE_TEXT_FILE_NAME;
			$msg = "";
		$fh = fopen ($filename, 'r');
		if ($fh)
		{
			while (!feof($fh)) 
			{
				$line = fgets($fh, 4096);
				if (strlen ($line) <= 0) continue;
				$values = explode ($IMAGE_FILE_DELIMINATOR, $line);

       			$t->setVariable(array('SPImage'.$values[0] => $CLIENT_IMAGE_PATH.$values[1],
				                      'SPText'.$values[0] => $values[2]
									  )
								);
				$msg .= $line.": ".$CLIENT_IMAGE_PATH.$values[1]."\n\r".$values[2]."\n\r";
			}
		}
	}

    function doCommonTemplateWorkPlain(&$t, $templateFile, $appName)
    {
       $t->loadTemplatefile($templateFile, true, true);

		$this->debug ("Application: $this->appName");

        $t->setVariable(array(
                         'APP_PATH'     => $this->getAppPath(),
                         'APP_NAME'     => $this->appUrl,
						 'BUTTON_PATH'  => $this->buttonPath,
 						 'IMAGE_PATH'  => $this->imagePath,
                         'BASE_URL'     => $this->getBaseURL()
                        )
                   );
    }

    function doFinalTemplateWork(&$t)
    {
		global $COMPANY_LOGO, $PHP_SELF, $AUTHENTICATION_URL, $APP_DIR, $HOME_APP;
		
		$this->debug ("Final Template Work");
		
        $t->setVariable(array(
                         'APP_PATH'     => $this->getAppPath(),
                         'APP_NAME'     => $this->appUrl,
                         'AccessUser'     => $this->accessUserDir,
						 'BUTTON_PATH'  => $this->buttonPath,
						 'IMAGE_PATH'  => $this->imagePath,
                         'BASE_URL'     => $this->getBaseURL(),
                         'APP_DIR'     => $APP_DIR,
                         'HOME_APP'     => $HOME_APP,
						 'LogoutURL'    => $this->LogoutURL,
						 'LoginURL'    => $AUTHENTICATION_URL
                        )
                   );
 		$this->doSiteExtras ($t);
   }

	// Not used much, but used to create special date select input sections
    function processInnerTemplate (&$t, $template, $tag)
    {
		$fp = fopen($this->templateDir.'/'.$template, "rb");

        $contents = fread ($fp, filesize ($this->templateDir.'/'.$template));

        $t->setVariable($tag, $contents);
    }
	
	// this code was copied from elsewhere and needs to be modified to fit in with our new style
	
	function paginate($form, $count = null, $limit = 10) {
		global $admin;
	
		$numrows = $count;
		$pagelinks = "<div class=pagelinks>";
		if ($numrows > $limit) {
	
			if ($this->page == 1) {
				$pagelinks .= "<span class='pageprevdead'>&lt; PREV</span>";
			} else {
				$pageprev = $this->page - 1;
				$pagelinks .= "<input type='submit' id='submitPrev' name='submitPrev' value='&lt PREV ' class='submitBtn' onclick='javascript:document.$form.PageNumber.value=$pageprev;'/>";
			} 
	
			$numofpages = ceil($numrows / $limit);
			$range = $admin['pageRange']['value'];
			if ($range == "" or $range == 0) $range = 7;
			$lrange = max(1, $this->page - (($range-1) / 2));
			$rrange = min($numofpages, $this->page + (($range-1) / 2));
			if (($rrange - $lrange) < ($range - 1)) {
				if ($lrange == 1) {
					$rrange = min($lrange + ($range-1), $numofpages);
				} else {
					$lrange = max($rrange - ($range-1), 0);
				} 
			} 
		    $this->debug ("Pages: $numofpages");
			
			if ($lrange > 1) {
				$pagelinks .= "..";
			} else {
				$pagelinks .= "&nbsp;&nbsp;";
			} 
			for($i = 1; $i <= $numofpages; $i++) {
				if ($i == $this->page) {
					$pagelinks .= "<span class='pagenumdead'>$i</span>";
				} else {
					if ($lrange <= $i and $i <= $rrange) {
						$pagelinks .= "<input type='submit' id='submit$i' name='submit$i' value='$i' class='submitBtn' onclick='javascript:document.".$form.".PageNumber.value=$i;'/>";
						//$pagelinks .= "<a class='pagenumlink' href='#' onclick='javascript:document.petitionDisplay.PageNumber.value=$i;document.petitionDisplay.submit();'>" . $i . "</a>&nbsp;";
					} 
				} 
			} 
			if ($rrange < $numofpages) {
				$pagelinks .= "..";
			} else {
				$pagelinks .= "&nbsp;&nbsp;";
			} 
			if (($numrows - ($limit * $this->page)) > 0) {
				$pagenext = $this->page + 1;
				$pagelinks .= "<input type='submit' id='submitNext' name='submitNext' value='NEXT &gt' class='submitBtn' onclick='javascript:document.$form.PageNumber.value=$pagenext;'/>";
				//"<a class='pagenextlink' href='#' onclick='javascript:document.petitionDisplay.PageNumber.value=$pagenext;document.petitionDisplay.submit();return false;'>NEXT &gt;</a>";
			} else {
				$pagelinks .= "<span class='pagenextdead'>NEXT &gt;</span>";
			} 
		} else {
			$pagelinks .= "<span class='pageprevdead'>
		  &lt; PREV</span>&nbsp;&nbsp;";
			$pagelinks .= "<span class='pagenextdead'>
		  NEXT &gt;</span>&nbsp;&nbsp;";
		} 
		$pagelinks .= "</div>";
		return $pagelinks;
	} 
		
    function getEnvironment($key)
    {
        return $_SERVER[$key];
    }

	// This loops through the form to get inoformation.  If $cat is set, it handles multiple
	// data inputs for a group that may be repeated depending on type (ie lps, cds, dvds, 
	// etc).
	function getFormData ($obj, $objFieldListFunction, $cat="", $fid = null)
	{
		$formInfo = $obj->$objFieldListFunction($fid);
		
		foreach( $formInfo AS $key )
		{ 
			$index = $cat.$key;
			if (empty($_POST[$cat.$key]) and ! empty ($_POST["Hidden".$cat.$key]))
			{
				$index = "Hidden".$cat.$key;
				$this->debug ("getForm: ".$index." = ".$_POST["Hidden".$cat.$key]);
			}
			if ( !empty( $_POST[ $index ] ) )
			{ 
				$this->{$cat.$key} = $_POST[ $index ]; 
				$this->debug ("getForm: ".$cat.$key." = ".$this->{$cat.$key});
			} 
			else 
			{ 
				$this->{$cat.$key} = NULL; 
			} 
		} 
	}
	// This gets the data in HTML arrays
	function getFormArrayData ($obj, $objFieldListFunction, $cat="", $fid = null)
	{
		$formInfo = $obj->$objFieldListFunction($fid);
		$rows = count ($_POST[$formInfo [1]]);

		for ($i = 0; $i < $rows; $i++)
		{
			foreach( $formInfo AS $key )
			{ 
				$f = $cat.$key;
				if (empty($_POST[ $f ][$i]) and ! empty ($_POST["Hidden".$cat.$key][$i]))
				{
					$f = "Hidden".$cat.$key;
					$this->debug ("getForm: ".$f." = ".$_POST["Hidden".$cat.$key][$i]);
				}
				if ( !empty( $_POST[ $f ] ) )
				{ 
					if (is_array ($_POST[ $f ])) $value = $_POST[ $f ][$i];
					else                         $value = $_POST[ $f ];

					$this->{$f} [$i]= $value; 

					$this->debug ("getForm: ".$cat.$key." = ".$this->{$f} [$i]);
				} 
				else 
				{ 
					$this->{$f} [$i] = NULL; 
				} 
			}
		}
	}
	
	// This handles multi-page forms
	function getCurrentPageData ($obj, $objFieldListFunction, $cat="", $arr="", $fid = null)
	{
		$formInfo = $obj->$objFieldListFunction($fid);
		
		foreach( $formInfo AS $key )
		{ 
			$index = $cat.$key.$arr;
			if (empty($_POST[$cat.$key.$arr]) and ! empty ($_POST["Hidden".$cat.$key.$arr]))
			{
				$index = "Hidden".$cat.$key.$arr;
				$this->debug ("getCurrentPageData: ".$index." = ".$_POST["Hidden".$cat.$key.$arr]);
			}
			if ( !empty( $_POST[ $index ] ) )
			{ 
				$this->{$cat.$key} = $_POST[ $index ]; 
				$this->debug ("getCurrentPageData: ".$cat.$key." = ".$this->{$cat.$key});
			} 
			else if (isset( $_POST[ $index ] ) )
			{ 
				$this->{$cat.$key.$arr} = ''; 
			}
		} 
	}
	
	// Take the data that was collected in the getFormData/getCurrentPageData
	// and put it into an array to be input into a table
	function processCurrentPageData($obj, $objFieldListFunction, $objTableFieldList, $cat="", $arr="")
	{
		$fields = $obj->$objFieldListFunction();
		$formFields = $this->Form->getFormFieldNamesList ($this->PageId);

		$hash = null;
		foreach ($fields as $f)
		{
			$dbg = "Field: ".$f.$arr."<br />";
			if (in_array ($f, $formFields))
			{ 
				if (array_key_exists ($cat.$f, $obj->{$objTableFieldList})) $dbg .= "Found $f<br />";
				if (array_key_exists ($cat.$f, $obj->{$objTableFieldList}) and $obj->{$objTableFieldList} [$f] == 'check') $dbg .= "Field: Check<br />";
				if (isset ($this->{$cat.$f})) $dbg .= "Form $f: ".$this->{$cat.$f}."<br />";
				if (isset ($obj->{$f})) $dbg .= "DB $f: ".$obj->{$f}."<br />";
				$this->debug ("DBG: $dbg");
				if (array_key_exists ($cat.$f, $obj->{$objTableFieldList}) and $obj->{$objTableFieldList} [$f] == 'check')
				{
					if (isset ($this->{$cat.$f.$arr}) or isset ($obj->{$f}))
					{
						if (isset ($this->{$cat.$f.$arr}))
						{
							if ($this->{$cat.$f.$arr} == 'Y')
							{
								$this->debug ("Object $f: ".$cat.$f.$arr. " Checked field " .$cat.$f.$arr.'_ON='. $this->{$cat.$f.$arr});
								$hash->{$f} = $this->{$cat.$f.$arr};
							} else {
								$this->debug ("Object $f: ".$cat.$f.$arr. " Checked field " .$cat.$f.$arr.'_ON='. 'N');
								$hash->{$f} = 'N';
							}
						}
						else if (isset ($obj->{$f}))
						{
							$this->debug ("Object $f: ".$cat.$f.$arr. " Checked field " .$cat.$f.$arr.'_ON='. 'N');
							$hash->{$f} = 'N';
						}
					} else {
						$this->debug ("Object $f: ".$cat.$f.$arr. " Checked field " .$cat.$f.$arr.'_OFF=');
						$hash->{$f} = "null";
					}
				}
				else if (isset ($this->{$cat.$f.$arr}))
				{
					$out = (isset ($obj->{$cat.$f})) ? $obj->{$cat.$f.$arr} : "";
					$this->debug ("processCurrentPageData old $f: ".$cat.$f.$arr.": ".$out);
					$hash->{$f} = $this->{$cat.$f.$arr};
				}
				else if (($obj->{$objTableFieldList} [$f] == 'text' or $obj->{$objTableFieldList} [$f] == 'number') and isset ($obj->{$cat.$f.$arr})) 
				{
					$this->debug ("processCurrentPageData old $f: ".$obj->{$cat.$f.$arr});
					$hash->{$f} = $obj->{$cat.$f.$arr};
				} else {
					$hash->{$f} = "null";
				}
			}
		}
		return $hash;
	}
  
	// Take the data that was collected in the getFormData/getCurrentPageData
	// and put it into an array to be input into a table
	function processFormData(&$obj, $objFieldListFunction, $objTableFieldList, $cat="")
	{
		$fields = $obj->$objFieldListFunction();
		
		foreach ($fields as $f)
		{
			if (array_key_exists ($cat.$f, $obj->{$objTableFieldList}) and $obj->{$objTableFieldList} [$f] == 'check')
			{
				if (isset ($this->{$cat.$f}) or isset ($obj->{$f}))
				{
					if (isset ($this->{$cat.$f}) and $this->{$cat.$f} == 'Y')
					{
						$this->debug ("Object: ".$cat.$f. " Checked field " .$cat.$f.'_ON='. $this->{$cat.$f});
						$hash->{$f} = $this->{$cat.$f};
					}
					else
					{
						$this->debug ("Object: ".$cat.$f. " Checked field " .$cat.$f.'_OFF='. $this->{$cat.$f});
						$hash->{$f} = "N";
					}
				} else {
						$this->debug ("Object: ".$cat.$f. " Checked field " .$cat.$f.'_OFF='. $this->{$cat.$f});
						$hash->{$f} = "N";
				}
			}
			else if (isset ($this->{$cat.$f}))
			{
				$out = (isset ($obj->{$cat.$f})) ? $obj->{$cat.$f} : "";
				$this->debug ("processForm old: ".$cat.$f.": ".$out. "New: ".$this->{$cat.$f});
				if ($obj->{$objTableFieldList} [$f] == 'date')
				{
					if (is_numeric ($this->{$cat.$f}) and $this->{$cat.$f} != 0)
					{
						$str = $this->{$cat.$f};

						if (strlen ($str) < 6)
						{
							$hash->{$f} = "20".substr ($str, 3, 2)."-0".substr ($str, 0, 1)."-".substr ($str, 1, 2);
						} else if (strlen ($str) < 8){
							$hash->{$f} = "20".substr ($str, 4, 2)."-".substr ($str, 0, 2)."-".substr ($str, 2, 2);
						} else {
							$hash->{$f} = substr ($str, 0, 4)."-".substr ($str, 4, 2)."-".substr ($str, 6, 2);
						}
					} else if ($this->{$cat.$f} != 0) {
						$hash->{$f} = date ('Y-m-d', strtotime ($this->{$cat.$f}));
					} else {
						$hash->{$f} = "";
					}
				} else if ($obj->{$objTableFieldList} [$f] == 'phone')
				{
					$str = $tmpStr = $this->{$cat.$f};
					if (is_numeric ($str) and strlen ($str) < 9)
					{
						$str =  str_pad ($str, 9, "0", STR_PAD_LEFT);
					} 
					$hash->{$f} = implode ("", explode ("-", $str));
				} else {
					$hash->{$f} = $this->{$cat.$f};
				}
			} else if (isset ($obj->{$cat.$f}) and $obj->{$objTableFieldList} [$f] == 'text')
			{
				$hash->{$f} = "";
			} else if (isset ($obj->{$cat.$f}) and $obj->{$objTableFieldList} [$f] == 'number')
			{
				$hash->{$f} = 0;
				$this->debug ("processForm old: ".$cat.$f.": 0");
			} else if ($obj->{$objTableFieldList} [$f] == 'now')
			{
				$hash->{$f} = "";  // Insert or update will overwrite this with current datetime stamp
				$this->debug ("processForm old: ".$cat.$f.": now");
			} else {
				$hash->{$f} = null;
			}
		}
		return $hash;
	}
	
	function processFormArrayData($hash, $obj, $objFieldListFunction, $objTableFieldList, $index, $cat="")
	{
		$fields = $obj->$objFieldListFunction();
		
		foreach ($fields as $f)
		{
			if (is_array ($this->{$cat.$f})) $value = $this->{$cat.$f} [$index];
			else $value =  $this->{$cat.$f};

			if (array_key_exists ($cat.$f, $obj->{$objTableFieldList}) and $obj->{$objTableFieldList} [$f] == 'check')
			{
				if (isset ($value) or isset ($obj->{$cat.$f}))
				{
					if (isset ($value) and $this->{$cat.$f} == 'Y')
					{
						$this->debug ("Object: ".$cat.$f. " Checked field " .$cat.$f.'_ON='. $value);
						$hash->{$f} = $value;
					}
					else
					{
						$this->debug ("Object: ".$cat.$f. " Checked field " .$cat.$f.'_OFF='. $value);
						$hash->{$f} = "N";
					}
				} else {
						$this->debug ("Object: ".$cat.$f. " Checked field " .$cat.$f.'_OFF='. $value);
						$hash->{$f} = "N";
				}
			}
			else if (isset ($value))
			{
				$out = (isset ($obj->{$cat.$f})) ? $obj->{$cat.$f} : "";
				$this->debug ("processForm old: ".$cat.$f.": ".$out. "New: ".$value);
				if (isset ($obj->{$objTableFieldList} [$f]) and $obj->{$objTableFieldList} [$f] == 'date')
				{
					if (is_numeric ($value))
					{
						$str = $value;

					} else {
						$hash->{$f} = date ('Y-m-d', strtotime ($value));
					}
				} else {
					$hash->{$f} = $value;
				}
			}
			else if (isset ($obj->{$cat.$f}) and $obj->{$objTableFieldList} [$f] == 'text')
			{
				$hash->{$f} = "";
			}
			else if (isset ($obj->{$cat.$f}) and $obj->{$objTableFieldList} [$f] == 'number')
			{
				$hash->{$f} = 0;
				$this->debug ("processForm old: ".$cat.$f.": 0");
			}
			else
			{
				$hash->{$f} = null;
			}
			if (isset ($obj->{$cat.$f})
			    and ($hash->{$f} != 0 and $hash->{$f} != "") and $obj->{$cat.$f} != $value)
			{
				$this->update = "Update";
			} else if (isset ($hash->{$f}) and ($hash->{$f} != 0 or $hash->{$f} != "") and (!isset ($obj->{$cat.$f}) or $obj->{$cat.$f} == ""))
			{
				if (!isset ($this->update) or $this->update == "")
				{
					$this->update = "Insert";
				}
			} else if ((!isset ($value) or $value == 0) and isset ($obj->{$cat.$f}) 
			           and ($obj->{$cat.$f} != "" and $obj->{$cat.$f} != 0))
			{
				$this->update = "Delete";
			}
		}
		return $hash;
	}
	
	function dateConvert ($USFormat, $MySQLFormat=null)
	{
		if ($USFormat and $MySQLFormat) return null;
		if ($USFormat)
		{
			return date ('Y-m-d', strtotime ($USFormat));
		} else if ($MySQLFormat)
		{
			return date ('m-d-y', strtotime ($MySQLFormat));
		}
	}
  
	// Automate the population of a form for data that can modify a record in the db
	// Multipage
	function setFormPageData(&$t, $obj, $formObj, $cat="", $arr="")
	{
		$this->debug ("Function: $formObj->FormFieldListFunction, Table List: $formObj->FieldListVariable<br />Cat: $cat");
		$formFields = $obj->{$formObj->FormFieldListFunction}();
		$formPageFields = $this->Form->getFormFieldNamesList ($this->PageId + 1);
		$checked = "checked='checked'";
		
		foreach( $formFields AS $field )
		{ 
			if (in_array ($field, $formPageFields))
			{ 
				$index = $cat.$field.$arr;
	
				if ( !empty($obj->{$cat.$field}))
				{ 
					if (array_key_exists ($field, $obj->{$formObj->FieldListVariable}) and $obj->{$formObj->FieldListVariable}[$field] == 'check')
					{
						if ($obj->{$cat.$field} == 'Y')
						{
							$this->debug ("setForm: ".$cat.$field.$arr. "Checked field " .$cat.$field.'_ON: '. $checked);
							$t->setVariable ($cat.$field.$arr.'_ON',  $checked);
							$t->setVariable ($cat.$field.$arr.'_OFF',  '');
						}
						else
						{
							$t->setVariable ($cat.$field.$arr.'_ON',  '');
							$t->setVariable ($cat.$field.$arr.'_OFF',  $checked);
						}
						$t->setVariable ($cat.$field, htmlentities ($obj->{$cat.$field}));
					}
					else if ($obj->{$formObj->FieldListVariable} [$field] == 'text' and isset ($obj->{$cat.$field}))
					{
						$this->debug ("setForm: ".$cat.$field.$arr." = ".$obj->{$cat.$field});
						$t->setVariable ($cat.$field.$arr, $obj->{$cat.$field});
					}
					else if ($obj->{$formObj->FieldListVariable} [$field] == 'number' and isset ($obj->{$cat.$field}))
					{
						$this->debug ("setForm: ".$cat.$field.$arr." = ".$obj->{$cat.$field});
						$t->setVariable ($cat.$field.$arr, htmlentities ($obj->{$cat.$field}));
					}
				}
			}
		} 
	}

	function setDefaultFormData(&$t, $obj, $formObj, $formId, $cat="")
	{
		$this->debug ("Function: $formObj->DefaultFormFieldListFunction, Table List: $formObj->FieldListVariable<br />Cat: $cat");
		$formFields = $obj->{$formObj->DefaultFormFieldListFunction}($formId);
		$checked = "checked='checked'";
		
		foreach( $formFields AS $id => $default )
		{ 
			list ($defaultName, $defaultTypeId) = $default;
			$index = $cat.$defaultName;

			if ( !empty($obj->{$defaultName}))
			{ 
				$this->debug ("setDefaultForm: ".$cat.$defaultName." = ".$obj->{$defaultName});
				if (array_key_exists ($defaultName, $obj->{$formObj->FieldListVariable}) and $defaultTypeId == 1)
				{
					if ($obj->{$defaultName} == 'Y')
					{
						$this->debug ("setDefaultForm: ".$cat.$defaultName. "Checked field " .$cat.$defaultName.'_ON: '. $checked);
						$t->setVariable ($cat.$defaultName.'_ON',  $checked);
						$t->setVariable ($cat.$defaultName.'_OFF',  '');
					}
					else
					{
						$t->setVariable ($cat.$defaultName.'_ON',  '');
						$t->setVariable ($cat.$defaultName.'_OFF',  $checked);
					}
				}
				$t->setVariable ($cat.$defaultName, htmlentities ($obj->{$defaultName}));
			}
		} 
	}

	// Automate the population of a form for data that can modify a record in the db
	function setFormData(&$t, $obj, $formObj=null, $cat="", $arr="")
	{
		if (!$formObj)
		{
			$objClass = get_class ($obj);
			$objFunction = "get{$objClass}FieldList";
			$formFields = $obj->{$objFunction} ();
			$objTableList = strtolower ($objClass)."TblFields";
			$this->debug ("Function: get{$objClass}FieldList, Table List: $objTableList<br />Cat: $cat");
		} else {
			$this->debug ("Function: $formObj->FormFieldListFunction, Table List: $formObj->FieldListVariable<br />Cat: $cat");
			$formFields = $obj->{$formObj->FormFieldListFunction}();
			$objTableList = $formObj->FieldListVariable;
		}
		$checked = "checked='checked'";
		
		foreach( $formFields AS $field )
		{ 
			$index = $cat.$field.$arr;

			if ( !empty($obj->{$cat.$field}))
			{ 
				$this->debug ("setForm: ".$cat.$field." = ".htmlentities ($obj->{$cat.$field}));
				if (array_key_exists ($field, $obj->{$objTableList}) and $obj->{$objTableList}[$field] == 'check')
				{
					if ($obj->{$cat.$field} == 'Y')
					{
						$this->debug ("setForm: ".$cat.$field. "Checked field " .$cat.$field.'_ON: '. $checked);
						$t->setVariable ($cat.$field.$arr.'_ON',  $checked);
						$t->setVariable ($cat.$field.$arr.'_OFF',  '');
					}
					else
					{
						$t->setVariable ($cat.$field.$arr.'_ON',  '');
						$t->setVariable ($cat.$field.$arr.'_OFF',  $checked);
					}
				} else if ($obj->{$objTableList}[$field] == 'date')
				{
					$t->setVariable ($cat.$field.$arr, $this->formatDate ($list->{$cat.$field}));
				} else {
					$this->debug ("setForm: $field".htmlentities ($obj->{$cat.$field}));
					$t->setVariable ($cat.$field.$arr, htmlentities ($obj->{$cat.$field}));
				}
			}
		} 
	}

	function setFormListData(&$t, $obj, $list, $cat="", $arr="")
	{
		$objClass = get_class ($obj);
		$objFunction = "get{$objClass}FieldList";
		$formFields = $obj->{$objFunction} ();
		$objTableList = strtolower ($objClass)."TblFields";
		// $this->debug ("Function: get{$objClass}FieldList, Table List: $objTableList<br />Cat: $cat");
		$checked = "checked='checked'";
		
		foreach( $formFields AS $field )
		{ 
			$index = $cat.$field.$arr;

			if ( !empty($list->{$cat.$field}))
			{ 
				// $this->debug ("setForm: ".$cat.$field." = ".htmlentities ($list->{$cat.$field}));
				if (array_key_exists ($field, $obj->{$objTableList}) and $obj->{$objTableList}[$field] == 'check')
				{
					if ($list->{$cat.$field} == 'Y')
					{
						$this->debug ("setForm: ".$cat.$field. "Checked field " .$cat.$field.'_ON: '. $checked);
						$t->setVariable ($cat.$field.$arr.'_ON',  $checked);
						$t->setVariable ($cat.$field.$arr.'_OFF',  '');
					}
					else
					{
						$t->setVariable ($cat.$field.$arr.'_ON',  '');
						$t->setVariable ($cat.$field.$arr.'_OFF',  $checked);
					}
				} else if ($obj->{$objTableList}[$field] == 'date')
				{
					$t->setVariable ($cat.$field.$arr, $this->formatDate ($list->{$cat.$field}));
				} else {
					// $this->debug ("setForm: ".htmlentities ($list->{$cat.$field}));
					$t->setVariable ($cat.$field.$arr, htmlentities ($list->{$cat.$field}));
				}
			}
		} 
	}

	// Populate a form that has multiple rows similar to using $cat
	function setFormRowData(&$t, $obj, $formFields, $block, $cat="", $arr="")
	{
		$checked = "checked='checked'";
		
		foreach( $formFields AS $field)
		{ 
			$index = $cat.$field.$arr;

			if (empty($obj->{$cat.$field}))
			{
				$t->setVariable ('ColumnData', htmlentities ($obj->{$cat.$field}));
			} else { 
				$this->debug ("setForm: ".$cat.$field." = ".htmlentities ($obj->{$cat.$field}));

				if ($obj->{$cat.$field} == 'Y')
				{
					$this->debug ("setForm: ".$cat.$field. "Checked field " .$cat.$field.'_ON: '. $checked);
					$t->setVariable ($cat.$field.$arr.'_ON',  $checked);
					$t->setVariable ($cat.$field.$arr.'_OFF',  '');
				}
				else
				{
					$t->setVariable ($cat.$field.$arr.'_ON',  '');
					$t->setVariable ($cat.$field.$arr.'_OFF',  $checked);
				}
			}
			$this->debug ("setForm: ".htmlentities ($obj->{$cat.$field}));
			$t->setVariable ('ColumnData', htmlentities ($obj->{$cat.$field}));
			$t->parseCurrentBlock ($block);
		} 
	}

	/*
	 * Function to create date selector
	 * To preset years create $this->year_arr
	 */
	  function setupDateSelector(&$t, $dateVar, $month, $day, $year)
	  {
         $t->setCurrentBlock('month'.$dateVar.'Block');
		 $month_arr = array ("", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
		 for ($i=0; $i<=12; $i++)
		 {
		     if ($i == 0 && $month == 0)
			 {
				$t->setVariable($dateVar.'MonthValue', "?");
				$t->setVariable($dateVar.'MonthSelected', 'selected = "selected"');
				$t->setVariable($dateVar.'MonthDisplay', $month_arr [$i]);
			 }
			 else
			 {
				$t->setVariable($dateVar.'MonthValue', $i);
				$t->setVariable($dateVar.'MonthDisplay', $month_arr [$i]);

			 	if ($i == $month)
				{
					$t->setVariable($dateVar.'MonthSelected', 'selected = "selected"');
				}
				else
				{
					$t->setVariable($dateVar.'MonthSelected', "");
				}
			 }
             $t->parseCurrentBlock();
		 }
         $t->setCurrentBlock('day'.$dateVar.'Block');
		 for ($i=0; $i<=31; $i++)
		 {
		     if ($i == 0)
			 {
				$t->setVariable($dateVar.'DayValue', "?");
				$t->setVariable($dateVar.'DayDisplay', "");
				if ($day == 0)
					$t->setVariable($dateVar.'DaySelected', 'selected = "selected"');
			 }
			 else
			 {
				$t->setVariable($dateVar.'DayValue', $i);
				$t->setVariable($dateVar.'DayDisplay', $i);
			 	if ($i == $day)
				{
					$t->setVariable($dateVar.'DaySelected', 'selected = "selected"');
				}
				else
				{
					$t->setVariable($dateVar.'DaySelected', "");
				}
			 }
             $t->parseCurrentBlock();
		 }
         $t->setCurrentBlock('year'.$dateVar.'Block');
		 if (!isset ($this->year_arr))
		 {
		 	$year = date ("Y");
			$this->year_arr = array ();
			for ($i = 0; $i < 10; $i++) // if $this->year_arr is not set, use 10 years
			{
		 		$this->year_arr [] = $year + $i;
			}
		}
		 foreach ($this->year_arr as $i)
		 {
		     if ($i == 0)
			 {
				$t->setVariable($dateVar.'YearValue', "?");
				$t->setVariable($dateVar.'YearDisplay', "Year");
			 	if ($year == 0)
					$t->setVariable($dateVar.'YearSelected', 'selected = "selected"');
			}
			else
			{
				$t->setVariable($dateVar.'YearValue', $i);
				$t->setVariable($dateVar.'YearDisplay', $i);
			 	if ($i == $year)
				{
					$t->setVariable($dateVar.'YearSelected', 'selected = "selected"');
				}
				else
				{
					$t->setVariable($dateVar.'YearSelected', "");
				}
			}
             $t->parseCurrentBlock();
		 }
	  }
	  
    function showPage($contents = null)
    {
        global $THEME_TEMPLATE;
        global $THEME_TEMPLATE_DIR, $REL_TEMPLATE_DIR;
        global $PHOTO_DIR, $DEFAULT_PHOTO, $REL_PHOTO_DIR;
  
        $themeObj = new Theme($this->dbi, null,'home');

        $this->themeObj = $themeObj;
        $this->theme = $themeObj->getUserTheme($this->getUID());
       
        $themeTemplate = new HTML_Template_ITX($THEME_TEMPLATE_DIR);
  
        $themeTemplate->loadTemplatefile($THEME_TEMPLATE[$this->theme], true, true);
        $themeTemplate->setCurrentBlock('mmainBlock');
        $themeTemplate->setCurrentBlock('printBlock');
        $themeTemplate->setVariable('printBlock', '&nbsp;');
        $themeTemplate->parseCurrentBlock('printBlock');
        $themeTemplate->setCurrentBlock('pageBlock');
        $themeTemplate->setVariable('pblock', null);
        $themeTemplate->setVariable('TEMPLATE_DIR', $REL_TEMPLATE_DIR);
        $themeDir = $THEME_TEMPLATE_DIR . '/' . dirname($THEME_TEMPLATE[$this->theme]);
               
        $themeTemplate->setVariable('SERVER_NAME', $this->getServer());
        $themeTemplate->setVariable('BASE_HREF', $REL_TEMPLATE_DIR);
        $themeTemplate->setVariable('CONTENT_BLOCK', $contents);
        $themeTemplate->parseCurrentBlock('contentBlock');
        $themeTemplate->parseCurrentBlock('mmainBlock');
    }

	function formatDate ($str)
	{
		$month =  intval (substr ($str, 5, 2));
		$day = intval (substr ($str, 8, 2));
		$year = intval (substr ($str, 2, 2));
		return $month."/".$day."/".$year;
	}

	// Gets the buttons/links for the current user
	function getAuthorizedButtons ()
	{
		$this->buttons = $this->pageProtect->getAuthorizedButtons ();
		return $this->buttons;
	}

}
?>