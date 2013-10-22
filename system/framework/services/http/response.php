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

class Response {
	/**
	 * @var array Misc info about the response.
	 */
	protected $info;

	/**
	 * @var string Http status code, message and version from response.
	 */
	protected $status = array();

	/**
	 * @var array Response headers taken from the handle.
	 */
	protected $headers;

	/**
	 * @var string The response body.
	 */
	protected $body;

	/**
	 * @param array $info
	 * @param string $response
	 * @access private
	 */
	public function __construct($info, $response){
		$this->info = $info;
		$this->headers = self::parseHeaders(substr($response, 0, $info['header_size']), $this->status);
		$this->body = substr($response, $info['header_size']);
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
		return $this->status['http_version'];
	}

	/**
	 * Get the http status code. (E.g. 404)
	 * @return int
	 */
	public function getResponseCode(){
		return $this->status['response_code'];
	}

	/**
	 * Get the http status text. (E.g. 'Not found.')
	 * @return string
	 */
	public function getResponseStatus(){
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
	 * Parse headers into a dimensional array.
	 * @param string $str Header string to parse.
	 * @param array $status Give a reference to an array to also get all the http status string info
	 * @return array
	 */
	public static function parseHeaders($str, &$status=null){
		$raw = explode("\n", $str);
		$headers = array();
		for($i=0; $i<count($raw); $i++){
			if($i == 0 && strpos($raw[$i], ':') == -1){
				if($status != null && is_array($status)){
					$exp = explode(' ', $raw[$i]);
					$status['http_version'] = $exp[0];
					$status['response_code'] = intval($exp[1]);
					$status['response_status'] = $exp[2];
				}
			}else{
				$exp = explode(':', $raw[$i]);
				$headers[trim($exp[0])] = trim($exp[1]);
			}
		}
		return $headers;
	}
}