<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Namespace
namespace Quark\Protocols\HTTP\Client;
use Quark\Protocols\HTTP\Request;
use Quark\Protocols\HTTP\Response;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class ClientRequest
 * @package Quark\Protocols\HTTP\Client
 */
abstract class ClientRequest extends Request {
	/**
	 * @var ClientRequest[]
	 */
	protected static $handlers = array();

	/**
	 * Send this request and retrieve the request.
	 * @return Response The response on this request.
	 * @throws \Quark\Exception
	 */
	abstract public function send();

	/**
	 * Make a request to the given url and http method. Chooses the best available wrapper for your current situation.
	 * @param string $url Http address to make the request to.
	 * @param string $method The HTTP method with which to make the request.
	 * @return Request
	 */
	public static function create($url, $method=self::METHOD_GET){
		$class = get_called_class();
		if($class == __CLASS__){
			return new self::$handlers[0]($url, $method); // Choose first registered option
		}else return new $class($url, $method);
	}

	/**
	 * Get all the available request handlers.
	 * @return string[]
	 */
	public static function available(){
		return self::$handlers;
	}

	/**
	 * Registers a new request handler.
	 * @param string $classname The name of the handler request class.
	 * @return void
	 * @ignore
	 */
	public static function register($classname){
		if(is_subclass_of($classname, __CLASS__))
			self::$handlers[] = $classname;
	}
}

\Quark\import(
	'Quark.Protocols.HTTP.Client.CurlRequest',
	'Quark.Protocols.HTTP.Client.StreamRequest'
);