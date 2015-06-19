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
use Quark\Protocols\HTTP\Response;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class ClientResponse
 * @package Quark\Protocols\HTTP\Client
 */
class ClientResponse extends Response {
	/**
	 * Builds a client response object from the header and response blob's
	 * @param string $headerString The string with the headers of the request (And the first status line).
	 * @param string $responseBody The body of the request.
	 * @throws \RuntimeException
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function __construct($headerString, $responseBody){
		if(!is_string($headerString) || empty($headerString))
			throw new InvalidArgumentTypeException('headerString', 'string', $headerString);
		if(!is_string($responseBody) || empty($responseBody))
			throw new InvalidArgumentTypeException('responseBody', 'string', $responseBody);

		$this->body = $responseBody;

		$headers = explode("\n", $headerString);
		if(count($headers) <= 2)
			throw new \RuntimeException('Tried to parse an invalid header string.');

		$this->setStartLine(array_shift($headers));
		$this->headers = self::parseHeaders($headers);
	}
}