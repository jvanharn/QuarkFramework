<?php
/**
 * Handles errors and logs them, gives appropriate messages to users
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn
 * @since		June 23, 2011
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Set the namespace
namespace Quark;

// Prevent access to this standalone file
use Quark\Document\Document;
use Quark\Document\ErrorBox;
use Quark\Document\ErrorFrame;
use Quark\Error\Debug;

if(!defined('DIR_BASE')) exit;

/**
 * This integer constant configures the maximum number of errors that are displayed/logged (Default: 10)
 */
if(!defined('MAX_ERRORS')) define('MAX_ERRORS', 10);

/**
 * This boolean constant is normally only used by (Module/Plugin) Developpers. HAVING THIS ON IN PRODUCTION ENVIRONMENTS WILL EXPOSE ALL YOUR DATA (Default: false)
 */
// This will unleash the full debugging power of the Framework, it helps you find the exact problem and even helps debug your queries
if(!defined('EXTENDED_DEBUG')) define('EXTENDED_DEBUG', true); 

/**
 * A PageTree specific error code that is thrown if the code reaches a point where normal logic would not go :)
 * So mostly because of a foul user extension, editing the code, or change in php syntax, function return values, etc., etc.
 * Nice to know: In Dutch a 'Boom' is actually a tree. Where in English it mostly describes something exploding.
 */
define('E_BOOM', 32768);

/**
 * For reporting errors. This class will handle the mailing/logging and all other things that the user specified in the config
 * 
 * @package Quark-Framework
 * @subpackage Error
 * @static
 */
class Error{
	/**
	 * Array containing all the raised errors.
	 * @var Array
	 */
	protected static $errors = Array();
	
	/**
	 * Error Translation Table
	 *
	 * A list of standard PHP Error codes and their (Constant) names(And some PageTree Specific error codes).
	 * @var Array
	 */
	protected static $errorCodes = array(
		// PHP Error Codes
		E_ERROR				=> 'E_ERROR',
		E_WARNING			=> 'E_WARNING',
		E_PARSE				=> 'E_PARSE',
		E_NOTICE			=> 'E_NOTICE',
		E_CORE_ERROR		=> 'E_CORE_ERROR',
		E_CORE_WARNING		=> 'E_CORE_WARNING',
		E_COMPILE_ERROR		=> 'E_COMPILE_ERROR',
		E_COMPILE_WARNING	=> 'E_COMPILE_WARNING',
		E_USER_ERROR		=> 'E_USER_ERROR',
		E_USER_WARNING		=> 'E_USER_WARNING',
		E_USER_NOTICE		=> 'E_USER_NOTICE',
		E_STRICT			=> 'E_STRICT',
		E_RECOVERABLE_ERROR	=> 'E_RECOVERABLE_ERROR',
		E_DEPRECATED		=> 'E_DEPRECATED',
		E_USER_DEPRECATED	=> 'E_USER_DEPRECATED',
		E_BOOM				=> 'E_BOOM' // PageTree Error Code, if something totally non-logical would happen(Just for mocking, but is actually used :P)
	);
	
	/**
	 * Non-Disclosing error messages for users of the interwebs.
	 * @var Array
	 */
	protected static $userMessages = array(
		0					=> 'An unknown Error occurred.', // Default Message
		E_ERROR				=> 'An undisclosed Error occurred inside the PageTree System. If this error persists, try to contact the webmaster at:{WM_EMAIL}.',
		E_WARNING			=> 'An undisclosed Error occurred inside the PageTree System. If this error persists, try to contact the webmaster at:{WM_EMAIL}.',
		E_PARSE				=> 'An undisclosed Fatal Error occurred inside the PageTree System. If this error persists, try to contact the webmaster at:{WM_EMAIL}.',
		E_NOTICE			=> 'There was an uncaught Exception inside the PageTree System. If this error persists, try to contact the webmaster at:{WM_EMAIL}.',
		E_CORE_ERROR		=> '',
		E_CORE_WARNING		=> 'An unidentified Error occurred in an PageTree Extension. If this error persists, try to contact the webmaster at:{WM_EMAIL}.',
		E_COMPILE_ERROR		=> 'An unidentified Fatal Error occurred in an PageTree Extension. If this error persists, try to contact the webmaster at:{WM_EMAIL}.',
		E_COMPILE_WARNING	=> 'There was an uncaught Exception in an PageTree Extension. If this error persists, try to contact the webmaster at:{WM_EMAIL}.',
		E_USER_ERROR		=> 'There was an uncaught Exception in an PageTree Extension. If this error persists, try to contact the webmaster at:{WM_EMAIL}.',
		E_USER_NOTICE		=> 'There was an uncaught Exception in an PageTree Extension. If this error persists, try to contact the webmaster at:{WM_EMAIL}.',
		E_STRICT			=> 'E_STRICT',
		E_RECOVERABLE_ERROR	=> 'The owner of this website or the author from one of the installed extensions probably did not use a function correctly. If this error persists, try to contact the webmaster at:{WM_EMAIL}',
		E_DEPRECATED		=> 'E_DEPRECATED',
		E_USER_DEPRECATED	=> 'E_USER_DEPRECATED',
		E_BOOM				=> 'BOOOOM, end of story, the system blew up. The webmaster probably messed with the code, or an hacker tried to mess with it. If this problem persists(which it will probably do), then try to contact the webmaster at:{WM_EMAIL}.<br />If you are the webmaster, try to look in your error.log, or try to enable (extended) debug_mode.'
	);
	
	/**
	 * Returns the textual version or Constant Name of the given Error Code
	 * @param  int $code An the errorCode that will be translated to its string value.
	 * @return string
	 */
	public static function getErrorCodeAsString($code){
		if(array_key_exists($code, self::$errorCodes))
			return self::$errorCodes[$code];
		else return false;
	}

	/**
	 * Returns the user message that goes with the error code
	 * @param  int $code The error code of which you want the user message.
	 * @return string
	 */
	public static function getUserMessage($code){
		// Check the param
		if(!is_int($code))
			return 'User-message codes have to be of type integer but got "'.gettype($code).'".';
		
		// Return the message
		if(array_key_exists($code, self::$userMessages))
			return self::$userMessages[$code];
		else return self::$userMessages[0];
	}

	/**
	 * Raises or triggers an Error.
	 *
	 * It prints the userMessage(or standard Message if none provided or full message when in
	 * debug mode or none when the user shut error printing off) and logs/emails it.
	 * @param string $debugMessage Debug message for logging and debugging
	 * @param string $userMessage (Optional) Message that can be displayed to the user
	 * @param int $debugCode (Optional) Error code in PHP format(See: {@link http://www.php.net/manual/en/errorfunc.constants.php List of PHP error codes} )
	 * @return boolean
	 */
	public static function raiseError($debugMessage, $userMessage=null, $debugCode=E_USER_ERROR){
		self::raise($debugMessage, $userMessage, $debugCode);
	}
	
	/**
	 * Raises or triggers an Warning.
	 *
	 * It prints the userMessage(or standard Message if none provided or full message when in
	 * debug mode or none when the user shut error printing off) and logs/emails it.
	 * @param string $debugMessage Debug message for logging and debugging
	 * @param string $userMessage (Optional) Message that can be displayed to the user
	 * @param int $debugCode (Optional) Error code in PHP format(See: {@link http://www.php.net/manual/en/errorfunc.constants.php List of PHP error codes})
	 * @return boolean
	 */
	public static function raiseWarning($debugMessage, $userMessage=null, $debugCode=E_USER_WARNING){
		self::raise($debugMessage, $userMessage, $debugCode);
	}
	
	/**
	 * Raises or triggers an Fatal Error.
	 * 
	 * It prints the userMessage(or standard Message if none provided or full message when in
	 * debug mode or none when the user shut error printing off), logs/emails it and then it exits.
	 * @param string $debugMessage Debug message for logging and debugging
	 * @param string $userMessage (Optional) Message that can be displayed to the user
	 * @param int $debugCode (Optional) Error code in PHP format(See: {@link http://www.php.net/manual/en/errorfunc.constants.php List of PHP error codes})
	 * @return boolean
	 */
	public static function raiseFatalError($debugMessage, $userMessage=null, $debugCode=E_USER_ERROR){
		self::raise($debugMessage, $userMessage, $debugCode);
		exit('Fatal Error raised inside the Application.');
	}

	/**
	 * Raises or triggers an Error or Warning.
	 *
	 * It prints the userMessage(or standard Message if none provided or full message when in
	 * debug mode or none when the user shut error printing off) and logs/emails it.
	 * @param string $debugMessage Debug message for logging and debugging
	 * @param string $userMessage (Optional) Message that can be displayed to the user
	 * @param int $debugCode (Optional) Error code in PHP format(See: {@link http://www.php.net/manual/en/errorfunc.constants.php List of PHP error codes} )
	 * @return void
	 */
	public static function raise($debugMessage, $userMessage=null, $debugCode=E_USER_ERROR){
		// Check if we don't exceed the MAX_ERRORS. If we do, exit. (Prevents error loops)
		if(count(self::$errors) > MAX_ERRORS){
			if(class_exists('\\Quark\\Document\\Document', false) && Document::hasInstance())
				Document::getInstance()->display();
			exit('More than '.MAX_ERRORS.' errors occurred, I therefore exited.');
		}
		
		// Validate params (This spits out exceptions to prevent infinite loops :P)
		if(!is_string($debugMessage))
			exit('The parameter $debugMessage should be of type "String" but got "'.gettype($debugMessage).'".');
		if(!is_null($userMessage) && !is_string($userMessage))
			exit('The parameter $userMessage should be null or of type "String" but got "'.gettype($userMessage).'".');
		if(!is_numeric($debugCode) || !self::getErrorCodeAsString($debugCode))
			exit('The $debugCode parameter is not an Integer as expected. Check the PHP Manual for what Error Constants(E_*) you can use for this parameter.');
		
		// Make sure the debug functions are loaded
		require_once 'debug.php';
		
		// Get a reference to the configuration class, so we can read the user's configuration variables
		// @todo This object no longer exists...
		//if(class_exists('Config', false)){
		//	$conf = Config::getInstance();
		//	if(!is_object($conf)) throw new RuntimeException('Could not successfully get an instance of the System Configuration object. Therefore could not pass the error.', E_CORE_ERROR, new ErrorException('This is the usermessage, for the debugMessage, check the error log. "'.$userMessage.'"', $debugCode));
		//}
		
		// Get the traceroute
		$trace = debug_backtrace(0);			 // Traceroute
		array_shift($trace);					 // Remove this function from the stack
		self::preventErrorCallLoop($trace);		 // Prevent looping by checking if the current method was called by raise itself.
		$trace = self::filterStackTrace($trace); // Filter the $trace further
		
		// Get the human readable variants from things
		$debugCodeString = self::getErrorCodeAsString($debugCode);
		$debugType = ucfirst(strtolower(substr($debugCodeString, strrpos($debugCodeString, '_')+1)));
		
		// Generate an message containing the vague outline of the error
		$message = 'An <strong>'.$debugType.'</strong> of type <i>"'.$debugCodeString.'"</i> was raised inside the '.\Quark\Error\Debug::getLastFunctionAsString($trace).'.'.PHP_EOL;
		
		// Add the debugging information
		if(true) // @todo check if we're in production
			self::prettyPrintErrorMessage($message, $debugCode, $userMessage, $debugMessage, Error\Debug::traceToString($trace, EXTENDED_DEBUG));
		else // We are in production!
			self::prettyPrintErrorMessage($message, $debugCode, $userMessage);
		/*if((isset($conf) && $conf->get('error', 'debug_mode')) || (defined('EXTENDED_DEBUG') && EXTENDED_DEBUG === true)){
			// Use the Document Classes if already loaded
			if(imported('Document', true) && Document::hasInstance()){
				// Get the document
				$doc = Document::getInstance();
				import('Document.Errors', true);
				
				// Create the error box for this error
				$box = new ErrorBox(array('title'=>'Quark Debug Message'));
				$doc->place($box);
				
				// Add Sumary Message
				$box->appendChild(new ErrorBox_Frame(array(
					'type' => ErrorBox_Frame::Text,
					'text' => $message,
				)));
				
				// Add the overview/statistics
				$box->appendChild(new ErrorBox_Frame(array(
					'type' => ErrorBox_Frame::Category,
					'hidable' => true,
					'title' => '<!--img src="assets/images/icons/dashboard.png"/--> Simplified Summary (User Message)',
					'text' => (empty($userMessage)? Error::getUserMessage($debugCode) : $userMessage)
				)));
				
				// Add the actual debug message
				$box->appendChild(new ErrorBox_Frame(array(
					'type' => ErrorBox_Frame::Category,
					'hidable' => true,
					'title' => '<!--img src="assets/images/icons/bandaid.png"/--> Debug Message',
					'text' => $debugMessage
				)));
				
				// Add the traceroute
				$box->appendChild(new ErrorBox_Frame(array(
					'type' => ErrorBox_Frame::Category,
					'hidable' => true,
					'title' => '<!--img src="assets/images/icons/bug.png"/--> Traceroute',
					'text' => '<pre style="margin:0;overflow-x:auto;">'.Error\Debug::traceToString($trace, EXTENDED_DEBUG).'</pre>'
				)));
			}
			
			// Use plain text
			else{
				// Echo the message
				echo '<div style="border:#999 1px solid; background:#ddd;font: 11px Verdana, Tahoma, Geneva, sans-serif;padding: 0px 5px 5px 8px;"><h3>PageTree Debug Message</h3><p>'.$message.'<br/>This error occured before the <u>Document</u> subsystem could be loaded, therefore this simplified error message.</p>';

				// Add the actual debug and user messages
				echo '<h4>Simplified Sumary (UserMessage)</h4><pre>'.(empty($userMessage)? self::getUserMessage($debugCode) : $userMessage).'</pre>';
				echo '<h4>Debug Message</h4><pre>'.$debugMessage.'</pre>';

				// Add the traceroute
				echo '<h4>Traceroute</h4><pre>'.Error\Debug::traceToString($trace, EXTENDED_DEBUG).'</pre></div>';
			}
		}
			
		// Debug modes are all off, only show the user the basic info
		else{
			// Build the message
			$errMessage = '<div style="border:#00ffff 1px solid; background:#77ffff;font: 11px Verdana, Tahoma, Geneva, sans-serif;"><h4>An error occurred inside the Application :o ('.$debugType.')</h4><p>'.$userMessage.'</p></div>';
			
			// Use the Document Classes if already loaded
			if(imported('Document', true) && Document::hasInstance()){
				// Get the document
				$doc = Document::getInstance();
				
				// Add an HTML String
				import('Document.Utils', true);
				$doc->place(new \Quark\Document\Literal($errMessage));
				
				// There, neatly placed inside the document, at the very top
			}
			
			// or just echo it (:
			else echo $errMessage;
		}*/
		
		// Log the error
		// @TODO Log the error
		//\Quark\System\logMessage((int) $info['log_level'], essage);
	}

	/**
	 * Prettifies an error message of any kind by adding some markup.
	 *
	 * Intended for INTERNAL use only. Hence the @ignore PHPDoc tag. It wont destroy the world though, if you do.
	 * @param string $summary
	 * @param int $debugCode
	 * @param string $userMessage
	 * @param string $debugMessage
	 * @param string $trace
	 * @access private
	 * @ignore
	 */
	public static function prettyPrintErrorMessage($summary, $debugCode=E_USER_ERROR, $userMessage='', $debugMessage='', $trace=''){
		// Use the Document Classes if already loaded
		if(imported('Document', true)){
			// Double check for Document class or do plain text.
			if(!class_exists('\\Quark\\Document\\Document', false)){
				print(PHP_EOL.'!!!! Incorrect @imported result; imported said Document was imported yet class_exists said otherwise. Please report this to the developers of Quark!!'.PHP_EOL);
				goto plainTextErrorMessage;
			}

			$result = Loader::importComponent('Document.Errors');

			// Dirty, I know. Doing it anyway.
			if(!$result) goto plainTextErrorMessage;

			// Create the error box for this error
			$box = new ErrorBox(array('title'=>'Quark Debug Message'));

			// Add Summary Message
			$box->addFrame(new ErrorFrame(array(
				'type' => ErrorFrame::Text,
				'text' => $summary,
			)));

			// Add the overview/statistics
			$box->addFrame(new ErrorFrame(array(
				'type' => ErrorFrame::Category,
				'hidable' => true,
				'title' => '<!--img src="assets/images/icons/dashboard.png"/--> Simplified Summary (User Message)',
				'text' => (empty($userMessage)? Error::getUserMessage($debugCode) : $userMessage)
			)));

			// Add the actual debug message
			if(!empty($debugMessage))
				$box->addFrame(new ErrorFrame(array(
					'type' => ErrorFrame::Category,
					'hidable' => true,
					'title' => '<!--img src="assets/images/icons/bandaid.png"/--> Debug Message',
					'text' => $debugMessage
				)));

			// Add the traceroute
			if(!empty($trace))
				$box->addFrame(new ErrorFrame(array(
					'type' => ErrorFrame::Category,
					'hidable' => true,
					'title' => '<!--img src="assets/images/icons/bug.png"/--> Traceroute',
					'text' => '<pre style="margin:0;overflow-x:auto;">'.$trace.'</pre>'
				)));

			// Check if we can use the document
			if(Document::hasInstance() && false){ // @todo
				// Check if the error occurred inside the document save method, because that would result in an empty page.
				$trace = debug_backtrace(0);
				foreach($trace as $method){
					if($method['function'] == 'save' && $method['class'] == 'Quark\\Document\\Document'){
						print($box->independentSave());
						return;
					}
				}

				// We can safely use the document
				$doc = Document::getInstance();
				$doc->place($box);

			// ..or use print
			}else print($box->independentSave());
		}

		// Use plain text
		else{
			// Label. See goto in prev if.
			plainTextErrorMessage:

			// Echo the message
			echo '<div style="border:#999 1px solid; background:#ddd;font: 11px Verdana, Tahoma, Geneva, sans-serif;padding: 0px 5px 5px 8px;">'.PHP_EOL;
			echo '<h3>Quark Debug Message</h3>'.PHP_EOL;
			echo '<p>'.$summary.'<br/>This error occurred before the <u>Document</u> subsystem could be loaded or in an application that does not use the Document subsystem, therefore this simplified error message.</p>';

			// Add the actual debug and user messages
			echo '<h4>Simplified Summary (UserMessage)</h4><pre>'.(empty($userMessage)? self::getUserMessage($debugCode) : $userMessage).'</pre>'.PHP_EOL;
			echo '<h4>Debug Message</h4><pre>'.$debugMessage.'</pre>'.PHP_EOL;

			// Add the trace route
			echo '<h4>Trace route</h4><pre>'.$trace.'</pre>'.PHP_EOL;
			echo '</div>'.PHP_EOL;
		}
	}
	
	/**
	 * Filters the stack trace from any raise wrapper functions
	 * @param array $trace
	 * @return array
	 */
	private static function filterStackTrace(array $trace){
		$len = count($trace);
		for($i=0; ($i<$len && $i<3); $i++){
			if((isset($trace[$i]['class']) && stristr($trace[$i]['class'], 'error') !== false) ||
				(stristr($trace[$i]['function'], 'raise') !== false) ||
				(stristr($trace[$i]['function'], '__err_handler') !== false))
				array_shift($trace);
			else break;
		}
		return $trace;
	}

	/**
	 * Exits when it detects a call to raise() in the given stack trace.
	 * @param $trace
	 */
	private static function preventErrorCallLoop($trace) {
		foreach($trace as $item){
			if(isset($item['class']) && $item['class'] == __CLASS__ && $item['function'] == 'raise' && $item['type'] == '::')
				exit(
					'Error loop detected! An error probably occurred inside the error raising mechanism, and that started an error loop inside the Quark error reporting system. If you have EXTENDED_DEBUG turned on, this message will be followed by th stacktrace.'.
					((defined('EXTENDED_DEBUG') && EXTENDED_DEBUG) ? '<pre>'.Debug::traceToString($trace, true).'</pre>':'')
				);
		}
	}
}

class_alias('\\Quark\\Error', '\\Quark\\Error\\Error');

/**
 * Shortcut/alias for {@link Error::raiseError()}
 */
function raiseError($debugMessage, $userMessage=null, $debugCode=E_USER_ERROR){
	Error::raise($debugMessage, $userMessage, $debugCode);
}

/**
 * This function will make PageTree's error handler handle all the errors
 * @private
 */
function __err_handler($errno, $errstr, $errfile){
	// Check if the error occured in this file, of so, dont handle
	if($errfile != __FILE__)
		return Error::raise($errstr,null,$errno) || true;
	else return false;
}

// Catch errors
//\set_error_handler('\\Quark\\__err_handler'); // We now also always handle errors