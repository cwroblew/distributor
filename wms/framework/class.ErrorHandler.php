<?php

/*
* Centalizes all error messages.
* Supports internationalization of error messages.
*
* @author EVOKNOW, Inc. <php@evoknow.com>
* @access public
*
* Other comments:
*	Need to fix bug where processing continues even though there is an error.
*/

class ErrorHandler
{

	function ErrorHandler($params = null)
	{
		global $DEFAULT_LANGUAGE;
		
		$this->language = $DEFAULT_LANGUAGE;
		
		$this->caller_class = (!empty($params['caller'])) ? $params['caller'] : null;
		
		$this->error_message = array();
		
		//error_reporting(E_ERROR | E_WARNING | E_NOTICE);
		
		$this->load_error_code();
	}
	
	function alert($code = null, $flag = null)
	{
		$msg = $this->get_error_message($code);
		if (!strlen($msg))
		{
			$msg = $code;
		}
		
		if ($flag == null)
		{
			echo "<script type='text/javascript'>alert('$msg');history.go(-1);</script>";
		
		} else if (!strcmp($flag,'close')){
		
			echo "<script type='text/javascript'>alert('$msg');window.close();</script>";
		
		} else {
		
			echo "<script type='text/javascript'>alert('$code - $msg');wait(1000);</script>";
		}
	}
	
	function get_error_message($code = null)
	{
		if (isset($code))
		{
			if (is_array($code))
			{
				$out = array();
				foreach ($code as $entry)
				{
					array_push($out, $this->error_message[$entry]);
				}
				return $out;
			
			} else {
			
				return (! empty($this->error_message[$code])) ? $this->error_message[$code] : null;
			}
		} else {
		
			return (! empty($this->error_message['MISSING'])) ? $this->error_message['MISSING'] : null;
		}
	}
	
	function load_error_code()
	{
		global $ERRORS;
		
		if (empty($ERRORS[$this->language]))
		{
			return FALSE;
		}
		
		while (list($key, $value) = each ($ERRORS[$this->language])) {
			$this->error_message[$key] = $value;
		}
		return TRUE;
	}
}
?>
