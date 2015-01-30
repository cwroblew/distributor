<?php

   require_once "userprofile.conf.php";

   class userForgottenPwdApp extends PHPApplication {

      function run()
      {

          $this->resetPasswordDriver();

      }

      function resetPasswordDriver()
      {
		$this->step = $this->getRequestField('step'); 

          if (!$this->step)
          {
             global $CLIENT_INDEX_TEMPLATE, $USERMNGR_FORGOTTEN_APP;

             print $this->showScreen($CLIENT_INDEX_TEMPLATE, 'getUsername', $USERMNGR_FORGOTTEN_APP);

          } else if ($this->step == 2) {


            $this->sendEmail();

          } else if ($this->step == 3){

             global $CLIENT_INDEX_TEMPLATE,
                    $USERMNGR_FORGOTTEN_APP;

             print $this->showScreen($CLIENT_INDEX_TEMPLATE,
                                     'resetPwd',
                                     $USERMNGR_FORGOTTEN_APP);
          } else {

             $this->resetPassword();

          }
      }

      function sendEmail()
      {
          global $USERMNGR_FORGOTTEN_APP,
                 $USERPROFILE_PWD_EMAIL_TEMPLATE,
                 $USERPROFILE_PWD_EMAIL_SUBJECT,
                 $USERPROFILE_PWD_EMAIL_FROM,
                 $DEFAULT_DOMAIN,
                 $AUTHENTICATION_URL,
				 $CLIENT_INDEX_TEMPLATE,
                 $CHAR_SET;

		  $this->Email = $this->getRequestField('Email'); 
          $this->emptyError($this->Email, 'USERNAME_MISSING');

          if (!strstr($this->Email,'@'))
          {
             $this->Email = $this->Email . '@' . $DEFAULT_DOMAIN;
          }


          $message = $this->email();

          if (! $message)
          {
             $this->alert('USER_NOT_FOUND');
			 exit;
          }
          $headers  = "BCC: charlene@authorbytes.com\r\n";
          $headers .= "From: $USERPROFILE_PWD_EMAIL_FROM\r\n";
          $headers .= "Subject: $USERPROFILE_PWD_EMAIL_SUBJECT\r\n";
          $headers .= "Content-Type: text/plain;$CHAR_SET\r\n";
          $headers .= "X-Priority: 1 (High)\r\n";

          $status = mail($this->Email,
                         $USERPROFILE_PWD_EMAIL_SUBJECT,
                         $message,
                         $headers);

          if ($status)
          {
              $this->resetStatus = $this->getMessage('PWD_EMAIL_SENT');
          } else {
              $this->resetStatus = $this->getMessage('PWD_EMAIL_NOT_SENT');
          }
		$this->showScreen($CLIENT_INDEX_TEMPLATE, 'statusScreen', $USERMNGR_FORGOTTEN_APP);

      }

      function getCheckSum($uid)
      {
          global $SECRET;
          return $uid << 8 + $SECRET;
      }

      function checkPassword($pwd1, $pwd2)
      {

          global $MIN_PASSWORD_SIZE, $DUMMY_PASSWD;

          $this->emptyError($pwd1, 'PASSWORD1_MISSING');
          $this->emptyError($pwd2, 'PASSWORD2_MISSING');

          if (strcmp($pwd1, $pwd2))
          {
             $this->alert('PASSWORD_MISMATCH');

          } else if (!strcmp($pwd1, $DUMMY_PASSWD) ||
                     strlen($pwd1) < $MIN_PASSWORD_SIZE) {

             $this->alert('INVALID_PASSWORD');

          }      
      }     
      

      function resetPassword()
      {
          global $user_id,
                 $chk,
                 $password1,
                 $password2,
                 $AUTHENTICATION_URL;


			$password1 = $this->getRequestField('password1'); 
			$password2 = $this->getRequestField('password2'); 
			$login = $this->getRequestField('login'); 
			$chk = $this->getRequestField('chk'); 
          $calculatedChecksum = $this->getCheckSum($login);

          if ($calculatedChecksum != $chk)
          {
              $this->alert('INVALID_REQUEST');
          }

          $this->checkPassword($password1, $password2);

          $salt = chr(rand(64,90)) . chr(rand(64,90));

          $cryptPassword = md5($password1);

          $hash->Password = $cryptPassword;

          $userObj = new User(null, $login);

          // $userId = $userObj->getUserIDByName($login);
		  // $userObj->getUserInfo($userId);

          $status = $userObj->updateUser($hash);

          if ($status)
          {
             $this->resetStatus = $this->getMessage('USER_MODIFY_SUCCESSFUL');
          } else {
             $this->resetStatus = $this->getMessage('USER_MODIFY_FAILED');
          }
		  global $CLIENT_INDEX_TEMPLATE, $USERMNGR_FORGOTTEN_APP;
		$this->showScreen($CLIENT_INDEX_TEMPLATE, 'statusScreen', $USERMNGR_FORGOTTEN_APP);
      }

      function email()
      {

          global $USERPROFILE_PWD_EMAIL_TEMPLATE, $USERPROFILE_FORGOTTEN_PASSWORD_MNGR, $TEMPLATE_DIR;

  		$this->debug ("main content: $USERPROFILE_PWD_EMAIL_TEMPLATE");
		$template = new HTML_Template_IT($TEMPLATE_DIR);
		$template->loadTemplatefile($USERPROFILE_PWD_EMAIL_TEMPLATE, true, true);

        $userObj = new User();

          $login = $userObj->getLoginByEmail($this->Email);

          if (!$login)
          {
             return FALSE;
          }

          $chksum = $this->getCheckSum($login);
          $appURL = sprintf("%s?login=%d&chk=%d&step=%s",
                                         $this->getFQAN($USERPROFILE_FORGOTTEN_PASSWORD_MNGR),
                                         $login,
                                         $chksum,
                                         3);

          $template->setVariable('PASSWORD_URL', $appURL);

         return $template->get( );
      }

      function getUsername(&$t)
      {
		global $USERPROFILE_PWD_REQUEST_TEMPLATE, $TEMPLATE_DIR;

		// Content
		$this->debug ("main content: $USERPROFILE_PWD_REQUEST_TEMPLATE");
		$template = new HTML_Template_IT($TEMPLATE_DIR);
		$template->loadTemplatefile($USERPROFILE_PWD_REQUEST_TEMPLATE, true, true);

         $template->setVariable('ACTION','reset');
        //  $t->setVariable('BASE_URL', $this->base_url);
		$this->doFinalTemplateWork ($template);
		$template->parse ();
		
		// Back to main template
		$t->setVariable('ManageContent', $template->get());

		return 1;
      }

      function resetPwd(&$t)
      {
         global $USERPROFILE_PWD_RESET_TEMPLATE, $TEMPLATE_DIR;
		 
		$login = $this->getRequestField('login'); 
		$chk = $this->getRequestField('chk'); 

		// Content
		$this->debug ("main content: $USERPROFILE_PWD_RESET_TEMPLATE");
		$template = new HTML_Template_IT($TEMPLATE_DIR);
		$template->loadTemplatefile($USERPROFILE_PWD_RESET_TEMPLATE, true, true);

         $template->setVariable('ACTION','reset');
         $template->setVariable('USER_ID', $login);
         $template->setVariable('CHECKSUM', $chk);
         // $t->setVariable('BASE_URL', $this->base_url);

         //  $t->setVariable('BASE_URL', $this->base_url);
		$this->doFinalTemplateWork ($template);
		$template->parse ();

		// Back to main template
		$t->setVariable('ManageContent', $template->get());
        return TRUE;
      }

	function statusScreen (&$t)
	{
		global $USERPROFILE_STATUS_TEMPLATE, $TEMPLATE_DIR;
		
		// Content
		$this->debug ("main content: $USERPROFILE_STATUS_TEMPLATE");
		$template = new HTML_Template_ITX($TEMPLATE_DIR);

		$template->loadTemplatefile($USERPROFILE_STATUS_TEMPLATE, false, true);

		$template->setVariable('STATUS_MESSAGE', $this->resetStatus);
		$this->doFinalTemplateWork($template);

		$template->parse ();

		// Back to main template
		$t->setVariable('ManageContent', $template->get());

		return 1;
	}
	
      function authorize()
      {
         return TRUE;
      }

   }//class

   $SESSION_USERNAME = null;
   $SESSION_USER_ID  = null;
   global $APP_DB_URL;

   $thisApp = new userForgottenPwdApp(
                             array('appName'           => $APPLICATION_NAME,
                                   'appVersion'        => '1.0.0',
 							  		'appUrl'      => $USERPROFILE_FORGOTTEN_PASSWORD_MNGR,
                                  'appType'           => 'WEB',
                                   'appAutoAuthorize' => FALSE,
                                   'appAutoConnect'   => TRUE,
                                   'appAutoCheckSession' => FALSE,
                                   'appDbUrl'         => $APP_DB_URL,
                                   'appDebugger'       => $OFF,
							  		  'cacheType'   => "BROWSER"
                                   )
                            );

   $thisApp->bufferDebugging();

   $thisApp->run();

   $thisApp->dumpDebuginfo();

?>
