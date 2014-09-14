<?php
/**
 * Nicer, Adaptable Exceptions
 * 
 * @package		Quark-Framework
 * @version		$Id: exception.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		June 26, 2011
 * @copyright	Copyright (C) 2006-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
\Quark\import('Error', 'Error.Debug', true);

/**
 * Quark Framework Exception.
 * 
 * This class wraps the standard Exception class and provides you some extra
 * exposable variables like specific user messages for when the system is not in
 * debugmode, automatic debug logging etc.
 *   <pre>throw new \Quark\Exception("Your exception message(Explain what happened)", "Explain to the user that something went wrong.", PHP_ERROR_CODE, $previous_exception);</pre>
 * For a full list off PHP errors check:
 * {@link http://www.php.net/manual/en/errorfunc.constants.php}
 * If you wan to also be able to display a message to the user instead of only
 * the standard one and you don't care about catching errors, then please use one
 * of the static functions inside {@see \Quark\Error}
 */
class Exception extends \Exception{
	/**
	 * Holds the userMessage
	 * @var String
	 */
	protected $userMessage;
	
	/**
	 * Whether or not the exception was already logged.
	 * @var boolean
	 */
	private $logged = false;

	/**
	 * Constructor makes sure the timezone is set, and logs the exception.
	 * @param String $debugMessage A message that fully describes the error, so a webmaster can fix it.
	 * @param String $userMessage A message that can be shown tot the general internet user, without disclosing any system information.
	 * @param int $debugCode Debug ErrorCode (Same as normal $code)
	 * @param \Exception $previous Previous exception
	 */
	public function __construct($debugMessage, $userMessage=null, $debugCode=E_USER_ERROR, \Exception $previous=null){
		// Set the Exception debug message and debugCode
		parent::__construct($debugMessage, $debugCode, $previous);
		
		// Set the (Non-Disclosing) user message
		$this->userMessage = (empty($userMessage)? Error::getUserMessage($this->getCode()) : $userMessage);
	}
	
	/**
	 * Returns the current (assigned) Human Readable version of the PHP Error
	 * @return string
	 */
	final function getErrorCodeAsString(){
		return Error::getErrorCodeAsString($this->code);
	}
	
	/**
	 * Returns the User Message
	 * @return String The user message
	 */
	final function getUserMessage(){
		// Log the message
		if(!$this->logged){
			$this->logged = true;
			System\Log::message(System\Log::EXCEPTION, (string) $this);
		}
		
		return $this->userMessage;
	}

	/**
	 * @return string
	 */
	public function __toString(){
		// Log the message
		if(!$this->logged){
			$this->logged = true;
			System\Log::message(System\Log::EXCEPTION, (string) $this);
		}
		return parent::__toString();
	}
}

/**
 * Default exception handler.
 * 
 * Makes sure exceptions get some fancy markup and exposes some more related data.
 * @private
 */
function __exc_handler($exception){
	// Check if it is a quark exception, and the exception isn't inside the exception class, otherwise convert
	$traces = $exception->getTrace();
	$trace = array_shift($traces); // Get the first trace thingy
	if(is_a($exception, '\\Quark\\Exception') && $trace['class'] != '\\Quark\\Exception'){
		
	}
	
	// Print the errormessage
	print($exception->__toString());
	
	// Safely exit the App
	//if(class_exists('\\\Quark\\Application', false)) // Make sure that the SafeDestruct function exists
	//	\\Quark\Application::SafeDestruct();
	
	// Return
	return true;
}

// Catch exceptions
//set_exception_handler('\\Quark\\__exc_handler'); // Exceptions are always handled by PageTree