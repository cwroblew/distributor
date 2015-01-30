<?php
/*
	Constants used by WMS
*/
	require_once ("tables.php");
		
	// Other constant values
	
	$TRUE 	                = 1;
	$FALSE	                = 0;
	$ON		                = 1;
	$OFF		            = 0;
	$SUCCESS	            = 1;
	
	$WWW_NEWLINE = '<BR>';
	$NEWLINE     = "\r\n";
	
	$TABLE_DOES_NOT_EXIST  = 1;
	$TABLE_UNKNOWN_ERROR  = 666;
	
	define('LOGIN', 1);
	define('LOGOUT', 2);
	
	// change this constants to the right mail settings
	define("WEBMASTER_MAIL", "me@mydomain.com"); 
	define("WEBMASTER_NAME", "The webmaster"); 
	define("ADMIN_MAIL", "me@mydomain.com"); 
	define("ADMIN_NAME", "ADMIN"); 
	
	// change this vars if you need...
	define("PW_LENGTH", 6);
	define("LOGIN_LENGTH", 6);
	
	define("COOKIE_NAME", "userName"); 
	// define("COOKIE_PATH", $HOME_PATH);
	define("MIN_ACCESS_LEVEL", 1);
	define("MAX_ACCESS_LEVEL", 10);
	define("DEFAULT_ACCESS_LEVEL", 6);
	define("DEFAULT_ADMIN_LEVEL", 9);
	define("DEFAULT_CONTRACT_ADMIN_LEVEL", 8);
	define("DEFAULT_SPECIAL_LEVEL", 7);
	
	// Mime constants
	
	define('BASE64', 'base64');
	define('BIT7', '7bit');
	define('QP', 'quoted_printable');
	define('NOSUBJECT', '(No Subject)');
	define('WARNING', 'This is a MIME encoded message');
	define('OCTET', 'application/octet-stream');
	define('TEXT', 'text/plain');
	define('HTML', 'text/html');
	define('JPEG', 'image/jpg');
	define('GIF', 'image/gif');
	define('CRLF', "\r\n");
	define('CHARSET', 'us-ascii');
	define('INLINE', 'inline');
	define('ATTACH', 'attachment');
	define('BODY', CRLF.'BODY'.CRLF);
?>
