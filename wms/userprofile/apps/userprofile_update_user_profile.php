<?php 
   require_once "userprofile.conf.php";

   class useraccessUpdateUserProfileApp extends PHPApplication {
	  //var $dbi;
	  
		function run()
		{
			$cmd = $this->getRequestField('cmd'); 
			
			$cmd = strtolower($cmd);
			
			$this->distributor = new Distributor ($this->dbi, $this->userId);
			$this->distributorOrderCode = $this->distributor->getDistributorOrderCodeForm ();

			if (!strcmp($cmd, 'modify')) 
			{
				global $DEFAULT_PASSWORD;
				if ($this->checkNewUser ())
				{
					$this->addProfileDriver();
				} else {
					$this->modifyProfileDriver();
				}
			} else if (!strcmp($cmd, 'modify_profile')) {
			
				$this->modifyProfileDriver();
			} else if ($cmd == "selectuser")
			{
				$this->selectUserDriver ();
			} else {
				// Are we processing contract orders
				$isChildDist = $this->distributor->getChildDistributorId ();
				
				if ($this->distributorOrderCode == 'D' && !isset ($this->child))
				{
					$this->selectUserDriver ();
				} else {
			
					global $CLIENT_INDEX_TEMPLATE, $USERPROFILE_UPDATE_PROFILE_MNGR;
					$this->debug ("$USERPROFILE_UPDATE_PROFILE_MNGR: " . $CLIENT_INDEX_TEMPLATE);
					
					$this->showScreen($CLIENT_INDEX_TEMPLATE, 'modifyScreen', $USERPROFILE_UPDATE_PROFILE_MNGR);
				}
			}
		}

     function modifyUserDriver()
     {
          $step = $this->getRequestField('step');

          if ($step == 2) {

		  	$this->updateUser ();
		  } else {
             global $CLIENT_INDEX_TEMPLATE, $USERPROFILE_UPDATE_PROFILE_MNGR;
             print $this->showScreen($CLIENT_INDEX_TEMPLATE, 'thankyou_screen', $USERPROFILE_UPDATE_PROFILE_MNGR);
		  }
	 }
	 
		function addProfileDriver()
		{
			$step = $this->getRequestField('step');
			
			if ($step == 2) {
			
				$this->addProfile ();
			} else {
				global $CLIENT_INDEX_TEMPLATE, $USERPROFILE_UPDATE_PROFILE_MNGR;
				print $this->showScreen($CLIENT_INDEX_TEMPLATE, 'thankyou_screen', $USERPROFILE_UPDATE_PROFILE_MNGR);
			}
		}
		
		function modifyProfileDriver()
		{
			$step = $this->getRequestField('step');
			
			if ($step == 2) {
			
				$this->updateProfile ();
			} else {
				global $CLIENT_INDEX_TEMPLATE, $USERPROFILE_UPDATE_PROFILE_MNGR;
				print $this->showScreen($CLIENT_INDEX_TEMPLATE, 'thankyou_screen', $USERPROFILE_UPDATE_PROFILE_MNGR);
			}
		}
		
		function selectUserDriver ()
		{
			$step = $this->getRequestField('step');
			
			if ($step == 2)
			{
				$this->child = $this->getRequestField('SelectDistributor'); 
				$this->cmd = 'modify';
				global $CLIENT_INDEX_TEMPLATE, $USERPROFILE_UPDATE_PROFILE_MNGR;
				$this->debug ("$USERPROFILE_UPDATE_PROFILE_MNGR ($this->child): " . $CLIENT_INDEX_TEMPLATE);
				
				$this->showScreen($CLIENT_INDEX_TEMPLATE, 'modifyScreen', $USERPROFILE_UPDATE_PROFILE_MNGR);

			} else {
				global $CLIENT_INDEX_TEMPLATE, $ORDER_MNGR;
				$this->debug ("userprofile: " . $CLIENT_INDEX_TEMPLATE);
				
				$this->showScreen($CLIENT_INDEX_TEMPLATE, 'selectUserScreen', $ORDER_MNGR);
			}
		}

		function addProfile()
		{
			global $USERPROFILE_UPDATE_PROFILE_MNGR, $HOME_URL;

			$this->dsdisn = $this->getRequestField('dsdisn'); 
			$distributor = new Distributor ($this->dbi, $this->dsdisn);
			
			$this->getFormData ($distributor, 'getDistributorFieldList');
			$this->Password = $this->getRequestField('Password'); 
			$this->PasswordConfirm = $this->getRequestField('PasswordConfirm'); 
			$this->Email = $this->dsemal;

			if ($this->checkNewProfileInput())
			{
				$user = new User ($this->dbi);
				
				// $hash->UserId = $this->dsdisn;
				$hash->Login = $this->dsdisn;
				$hash->Password = md5 ($this->Password);
				$hash->PasswordConfirm = $this->PasswordConfirm;
				$hash->real_name = $this->dsname;
				$hash->Email = $this->dsemal;
				$hash->AccessType = 6;
				$hash->active = 'y';
				$status = $user->addUser ($hash);
				
				$hash = $this->processFormData($distributor, 'getDistributorFieldList', 'distributorTblFields');
				$status = $distributor->updateDistributor ($hash);

				$_SESSION[APP_NAME."_PASSWORD"] = md5 ($this->Password);
				unset ($_SESSION[APP_NAME."_NEW_USER"]);

				if ($status == MDB2_OK)
				{
					header("Location: $HOME_URL");
				} else {
					global $CLIENT_INDEX_TEMPLATE, $USERPROFILE_UPDATE_PROFILE_MNGR;
					$this->debug ("$USERPROFILE_UPDATE_PROFILE_MNGR: " . $CLIENT_INDEX_TEMPLATE);
					
					$this->showScreen($CLIENT_INDEX_TEMPLATE, 'modifyScreen', $USERPROFILE_UPDATE_PROFILE_MNGR);
				}
			}
		}
		
		function updateProfile()
		{
			global $USERPROFILE_UPDATE_PROFILE_MNGR, $HOME_URL;

			$this->dsdisn = $this->getRequestField('dsdisn'); 

			$distributor = new Distributor ($this->dbi, $this->dsdisn);

			$this->getFormData ($distributor, 'getDistributorFieldList');
			$this->Password = $this->getRequestField('Password'); 
			$this->PasswordConfirm = $this->getRequestField('PasswordConfirm'); 
			$this->Email = $this->dsemal;
			
			if ($this->checkProfileInput())
			{
				if (isset ($this->Password))
				{
					$hash->Password = md5 ($this->Password);
					$hash->PasswordConfirm = $this->PasswordConfirm;  // Need?
				}
				$hash->real_name = $this->dsname;
				$hash->Email = $this->dsemal;
				// $user = new User ($this->dbi, $this->userId);
				$user = new User ($this->dbi, $this->dsdisn);

				$status = $user->updateUser($hash);
				$hash = $this->processFormData($distributor, 'getDistributorFieldList', 'distributorTblFields');
				$hash->OrderFormCode = $distributor->getDistributorOrderCodeForm ();
				$status = $distributor->updateDistributor($hash);
				// $update_profile->access_page(); // protect this page too.
				if ($status == MDB2_OK)
				{
					header("Location: $HOME_URL");
				} else {
					global $CLIENT_INDEX_TEMPLATE, $USERPROFILE_UPDATE_PROFILE_MNGR;
					$this->debug ("$USERPROFILE_UPDATE_PROFILE_MNGR: " . $CLIENT_INDEX_TEMPLATE);
					
					$this->showScreen($CLIENT_INDEX_TEMPLATE, 'modifyScreen', $USERPROFILE_UPDATE_PROFILE_MNGR);
				}
			}
		}
		
	 function updateUser()
	 {
	 	global $USERPROFILE_UPDATE_PROFILE_MNGR;
		
          // $this->login = $this->getRequestField('login'); 
          $this->FirstName = $this->getRequestField('FirstName'); 
          $this->LastName = $this->getRequestField('LastName'); 
          $this->password = $this->getRequestField('password'); 
          $this->ConfirmPassword = $this->getRequestField('ConfirmPassword'); 
          $this->Email = $this->getRequestField('Email'); 

		  $this->checkInput();
		  
		$update_profile = new Users_profile;
		$update_profile->access_page(); // protect this page too.
		$update_profile->get_user_info(); // call this method to get all other information
		$update_profile->update_user($this->password, $this->ConfirmPassword, $this->FirstName, $this->LastName, '', $this->Email); // the update method

			$statusMsg = "Name: $this->FirstName $this->LastName, <br>Login: $update_profile->userName<br>Email: $this->Email";
             		$this->show_status($statusMsg . "<br>". $update_profile->the_msg,
                                		$USERPROFILE_UPDATE_PROFILE_MNGR);
     }

      function modifyScreen(&$t)
      {
	  	global $USERPROFILE_UPDATE_USER_PROFILE_TEMPLATE, $TEMPLATE_DIR;

		$this->debug ("main content: $USERPROFILE_UPDATE_USER_PROFILE_TEMPLATE");
		$template = new HTML_Template_IT($TEMPLATE_DIR);

		$template->loadTemplatefile($USERPROFILE_UPDATE_USER_PROFILE_TEMPLATE, false, true);

		if ($this->distributorOrderCode == 'D') // hard-coded 'D' Form
		{
			$distributor = new Distributor ($this->dbi, $this->child); // we want to modify the child record!
		} else {
			$distributor = new Distributor ($this->dbi, $this->userId);
		}

		$this->form = new Form ($this->dbi, 1, 'Distributor');

		$this->setFormData ($template, $distributor);

		$template->setVariable('cmd', 'modify');
		$this->doFinalTemplateWork ($template);
		$template->parse ();

		$t->setVariable('ManageContent', $template->get());
		  
		return 1;
      }

		function selectUserScreen (&$t)
		{
			global $USERPROFILE_SELECT_USER_TEMPLATE, $TEMPLATE_DIR;
	
			$this->debug ("main content: $USERPROFILE_SELECT_USER_TEMPLATE");
			$template = new HTML_Template_IT($TEMPLATE_DIR);
	
			$template->loadTemplatefile($USERPROFILE_SELECT_USER_TEMPLATE, false, true);
			
			$distributorList = $this->distributor->getDistributorChildList ();
			
			$template->setCurrentBlock('distributorBlock');
			foreach ($distributorList as $dsdisn => $dsname)
			{
				$template->setVariable ('Distributor', $dsdisn." - ".$dsname);
				$template->setVariable ('dsdisn', $dsdisn);
				$template->parse ('distributorBlock');
			}
			$this->doFinalTemplateWork ($template);
			$template->parse ();
			
			$t->setVariable('ManageContent', $template->get());
	
			return 1;
		}

	  function thankyou_screen ()
	  {
          return 1;
	  }
	  
		function checkPassword($pwd1, $pwd2)
		{
			global $MIN_PASSWORD_SIZE;
			
			if ($this->emptyError($pwd1, 'PASSWORD1_MISSING') or $this->emptyError($pwd2, 'PASSWORD2_MISSING'))
			{
				return false;
			}
			$error = "";
			if (strcmp($pwd1, $pwd2))
			{
				$error = 'PASSWORD_MISMATCH';
			} else if (strlen($pwd1) < $MIN_PASSWORD_SIZE) {
			
				$error = 'INVALID_PASSWORD';
			}
			if ($error != "")
			{
				$this->alert($error);
				return false;
			}
			return true;
		}

		function checkProfileInput()
		{
			// $this->emptyError($this->login, 'LOGIN_MISSING');
			// $this->emptyError($this->password, 'PASSWORD_MISSING');
			// $this->emptyError($this->ConfirmPassword, 'PASSWORD_MISSING');
			// $msg = "Email: [$this->Email]\nPW: [$this->Password]\nConf: [$this->PasswordConfirm]";
			if ($this->emptyError($this->Email, 'EMAIL_MISSING') or (isset ($this->Password)  and !$this->checkPassword($this->Password, $this->PasswordConfirm)))
			{
				return false;
			} else if (!$this->validateEmail ($this->Email))
			{
				$this->alert ('INVALID_EMAIL');
				return false;
			}
			return true;
		}

		function checkNewProfileInput()
		{
			if ($this->emptyError($this->Email, 'EMAIL_MISSING') or !$this->checkPassword($this->Password, $this->PasswordConfirm))
			{
				return false;
			} else if (!$this->validateEmail ($this->Email))
			{
				$this->alert ('INVALID_EMAIL');
				return false;
			}
			return true;
		}

		function checkNewUser ()
		{
			global $DEFAULT_PASSWORD;
			$userObj = new User($this->dbi, $_SESSION[APP_NAME."_USERNAME"]);
			if (!$userObj->getIsUser () and $_SESSION[APP_NAME."_PASSWORD"] ==  md5 ($DEFAULT_PASSWORD))
			{
				return TRUE;	
			} else {
				return FALSE;
			}
		}
		function authorize()
		{
			global $DEFAULT_PASSWORD;

			$pw = $_SESSION[APP_NAME."_PASSWORD"];
			if (!$this->userId or $this->userId != $this->UserName) 
			{
				$userObj = new User ($this->dbi, $this->userId);
				// this seems to be an error - checking to fix this
				// $userObj = new User ($this->dbi);
				// $_SESSION[APP_NAME."_USER_ID"] = $_SESSION[APP_NAME."_USERNAME"];
			} else {
				$userObj = new User ($this->dbi, $this->userId);
			}
			$userObj->setIsUser ($this->userId);
			// $userObj->setIsUser ($_SESSION[APP_NAME."_USER_ID"]);

			// New user?
			if (!$userObj->getIsUser () and $pw == md5 ($DEFAULT_PASSWORD))
			{
				$this->AccessType = $userObj->getAccessType ();
				$this->debug("User authorized.");
				return TRUE;
			}
/*
			} else if (!$userObj->getUserInfoByLogin ($this->UserName, $pw)) {
				$this->debug("User not authorized.");
	
				return $userObj->getIsUser();
			}
*/
			return $userObj->getIsUser();
		}
   }//class

   global $APP_DB_URL, $USERPROFILE_UPDATE_PROFILE_APP, $USERPROFILE_UPDATE_PROFILE_MNGR;

   $thisApp = new useraccessUpdateUserProfileApp(
                             array( 'appName'     => $USERPROFILE_UPDATE_PROFILE_MNGR,
							  		'appUrl'      => $USERPROFILE_UPDATE_PROFILE_APP,
                                    'appVersion'  => '1.0.1',
                                    'appType'     => 'WEB',
                                    'appDbUrl'    => $APP_DB_URL,
                                    'appAutoConnect'      => TRUE,
                                    'appAutoAuthorize'    => TRUE,
                                    'appAutoCheckSession' => TRUE,
                                    'appDebugger' => $OFF,
							  		'cacheType'   => "BROWSER"
                                   )
                            );

   $thisApp->bufferDebugging();
   $thisApp->run();
   $thisApp->dumpDebuginfo();

?>