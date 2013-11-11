<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Services\HTTP;

use Quark\Error;

/**
 * Class Response
 * @package Quark\Services\HTTP
 */
class Response {
	/**
	 * @var string|array Http status code, message and version from response.
	 */
	protected $status;

	/**
	 * @var array Response headers taken from the handle.
	 */
	protected $headers;

	/**
	 * @var string The response body.
	 */
	protected $body;

	/**
	 * @param string $http_status
	 * @param array $headers
	 * @param string $body
	 * @access private
	 */
	public function __construct($http_status, $headers, $body){
		$this->status = $http_status;
		$this->headers = $headers;
		$this->body = $body;
	}

	/**
	 * Alias of getBody.
	 * @see getBody()
	 */
	public function __toString(){
		return $this->getBody();
	}

	/**
	 * Get the protocol version the server replied with. (E.g. HTTP/1.1)
	 * @return string
	 */
	public function getHttpVersion(){
		self::_parseStatus();
		return $this->status['http_version'];
	}

	/**
	 * Get the http status code. (E.g. 404)
	 * @return int
	 */
	public function getResponseCode(){
		self::_parseStatus();
		return $this->status['response_code'];
	}

	/**
	 * Get the http status text. (E.g. 'Not found.')
	 * @return string
	 */
	public function getResponseStatus(){
		self::_parseStatus();
		return $this->status['response_status'];
	}

	/**
	 * Get all the headers as array
	 * @return array
	 */
	public function getHeaders(){
		return $this->headers;
	}

	/**
	 * Get the given header by the given header name (Case-insensitive).
	 * @param string $name Header name.
	 * @return string|null
	 */
	public function getHeader($name){
		foreach($this->headers as $key => $value){
			if(strcasecmp($key, $name) == 0)
				return $value;
		}
		return null;
	}

	/**
	 * Get the response body as a string.
	 * @return string
	 */
	public function getBody(){
		return $this->body;
	}

	/**
	 * Check whether the response body was not empty.
	 */
	public function hasBody(){
		return !empty($this->body);
	}

	/**
	 * Parses the status string into an array.
	 */
	private function _parseStatus(){
		if(is_array($this->status)) return;
		$exp = explode(' ', $this->status);
		$this->status = array(
			'http_version' => $exp[0],
			'response_code' => intval($exp[1]),
			'response_status' => $exp[2]
		);
	}
}