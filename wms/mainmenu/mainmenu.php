<?php
	require_once "mainmenu.conf.php";

	class mainmenuApp extends PHPApplication {

		function run()
		{
			global $MAINMENU_MNGR, $DISTRIBUTOR_APP_RUNNING;
			
			$cmd = $this->getRequestField('cmd'); 
			$cmd = strtolower($cmd);

			if (($this->checkCancel () and isset ($_SESSION[APP_NAME."_NEW_USER"])) or (!$this->getTestAdmin () && Authentication::loginsSuspended ()))
			{
				Authentication::logout();
				$this->reauthenticate ();
			} else if (!$this->getTestAdmin () && ($this->checkOrderSuspended (TRUE) || $this->getAppStatus () != $DISTRIBUTOR_APP_RUNNING))  // Include warning time
			{
				global $DISTRIBUTOR_APP_RUNNING;
				
				$this->debug ("mainmenu status: " . $this->getAppStatus ());
				if ($this->getAppStatus () != $DISTRIBUTOR_APP_RUNNING) // app not running
				{
					$status = $this->getAppStatusMessage ();
					global $CLIENT_INDEX_TEMPLATE, $LOGIN_MNGR;
				
					$this->showStatus($CLIENT_INDEX_TEMPLATE, $status);
				}
			}
			if ($cmd == "selectuser")
			{
				$this->selectUserDriver ();
			} else {
				global $CLIENT_INDEX_TEMPLATE, $MAINMENU;
				$this->debug ("mainmenu: " . $CLIENT_INDEX_TEMPLATE);
				
				$this->showScreen($CLIENT_INDEX_TEMPLATE, 'mainmenuScreen', $MAINMENU);
			}
		}
	
		function selectUserDriver ()
		{
			$step = $this->getRequestField('step'); 

			if ($step == 2)
			{
				global $HOME_URL;
				
				$dsdisn = $this->getRequestField('SelectDistributor'); 

				$_SESSION[APP_NAME."_USER_ID"]  = $dsdisn;
				$_SESSION[APP_NAME."_ADMIN"]  = $this->UserName;
				header("Location: $HOME_URL");
			} else {
				global $CLIENT_INDEX_TEMPLATE, $MAINMENU;
				$this->debug ("mainmenu: " . $CLIENT_INDEX_TEMPLATE);
				
				$this->showScreen($CLIENT_INDEX_TEMPLATE, 'selectUserScreen', $MAINMENU);
			}
		}
		function mainmenuScreen (&$t)
		{
			global $MAINMENU_CONTENT_TEMPLATE, $TEMPLATE_DIR;
	
			// Content
			$this->debug ("main content: $MAINMENU_CONTENT_TEMPLATE");
			$template = new HTML_Template_IT($TEMPLATE_DIR);
			$template->loadTemplatefile($MAINMENU_CONTENT_TEMPLATE, true, true);

			$distNum = $this->getUID ();

			// This is currently hard-coded - should be getting info from db
			if ($distNum == '60745185') 
			{
				$this->reportMenu ($template);
			}

			$template->setVariable ('dsdisn', $distNum);
			$template->setVariable ('RND', rand ());

			$this->doFinalTemplateWork($template);

			$template->touchBlock ('contentBlock');
			$template->parse ();
			
			// Back to main template
			$t->setVariable('ManageContent', $template->get());
	
			return 1;
		}
		
		
		function reportMenu (&$t)
		{
			global $MAINMENU_DISTRIBUTOR_CUSTOM_TEMPLATE, $TEMPLATE_DIR, $BH_CUSTOM_REPORT,
			       $BH_CUSTOM_REPORT_DESC;
	
			// Content
			$this->debug ("custom content: $MAINMENU_DISTRIBUTOR_CUSTOM_TEMPLATE");
			$template = new HTML_Template_IT($TEMPLATE_DIR);
			$template->loadTemplatefile($MAINMENU_DISTRIBUTOR_CUSTOM_TEMPLATE, true, true);
			$template->setCurrentBlock("bhCustomBlock");

			$template->setVariable('bhCustomMenuItem', $BH_CUSTOM_REPORT);
			$template->setVariable('bhCustomMenuItemDesc', $BH_CUSTOM_REPORT_DESC);

			$this->doFinalTemplateWork ($template);
			$template->parse ();
			
			// Back to main template
			$t->setVariable('DistCustMenu', $template->get());
		}

		function selectUserScreen (&$t)
		{
			global $MAINMENU_SELECT_USER_TEMPLATE, $TEMPLATE_DIR;
	
			$distributor = new Distributor ($this->dbi);

			$this->debug ("main content: $MAINMENU_SELECT_USER_TEMPLATE");
			$template = new HTML_Template_IT($TEMPLATE_DIR);
	
			$template->loadTemplatefile($MAINMENU_SELECT_USER_TEMPLATE, false, true);
			
			$distributorList = $distributor->getDistributorNameList ();
			
			$template->setCurrentBlock('distributorBlock');
			foreach ($distributorList as $dsdisn => $dsname)
			{
				$template->setVariable ('Distributor', $dsdisn." - ".$dsname);
				$template->setVariable ('dsdisn', $dsdisn);
				$template->parse ('distributorBlock');
			}
			$template->parse ();
			
			$t->setVariable('ManageContent', $template->get());
	
			return 1;
		}
	
		function checkCancel ()
		{
			$cancel = $this->getRequestField('cancel');
			if ($cancel == 'y') return TRUE;
			else return FALSE;
		}
		function authorize()
		{
			return TRUE;
		}
	}//class

	global $APPLICATION_NAME, $APP_DB_URL, $MAINMENU;

	$thisApp = new mainmenuApp(
									array( 'appName'	 => $APPLICATION_NAME,
											'appVersion' => '1.0.0',
 											'appUrl'	 => $MAINMENU,
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