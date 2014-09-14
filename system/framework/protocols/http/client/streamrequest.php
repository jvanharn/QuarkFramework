<?php
/**
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Protocols\HTTP\Client;
use Quark\Error;
use Quark\Protocols\HTTP\ClientRequest;
use Quark\Protocols\HTTP\Response;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class StreamRequest
 * @package Quark\Services\HTTP
 */
class StreamRequest extends ClientRequest {
	const VERSION = '1.0';

	/**
	 * @var array The HTTP parameters given to stream_context_create.
	 */
	protected $params = array(
		'user_agent' => 'QuarkClient/1.0',
		'max_redirects' => 10,
		'ignore_errors' => true // always return the body
	);

	/**
	 * @param string $url Http address to make the request to.
	 * @param string $method The HTTP method with which to make the request.
	 * @throws \InvalidArgumentException
	 */
	public function __construct($url, $method = self::METHOD_GET) {
		$this->params['user_agent'] = 'Mozilla/4.0 ('.PHP_OS.'; '.php_uname('s').' '.php_uname('r').' on '.php_uname('m').') QuarkHTTPStreamRequest/'.self::VERSION.' (like Gecko or WebKit) QuarkClient/'.self::VERSION;

		if(empty($url))
			throw new \InvalidArgumentException('Unable to construct a request with an empty URL.');
		if(empty($method))
			throw new \InvalidArgumentException('Unable to construct a request without a valid request method.');

		// Set URL
		$this->url = $url;

		// Set method
		$this->setMethod($method);
	}

	/**
	 * Set the HTTP method.
	 * @param string $method HTTP Method
	 * @throws \InvalidArgumentException
	 */
	public function setMethod($method){
		if(is_string($method)){
			switch($method){
				case self::METHOD_GET:
					$this->params['method'] = 'GET';
					break;
				case self::METHOD_POST:
					$this->params['method'] = 'POST';
					break;
				case self::METHOD_HEAD:
					$this->params['method'] = 'HEAD';
					break;
				case self::METHOD_PUT:
					$this->params['method'] = 'PUT';
					break;
				case self::METHOD_DELETE:
					$this->params['method'] = 'DELETE';
					break;
				default:
					throw new \InvalidArgumentException('Invalid value for $method, should be one of the Request::METHOD_* constants.');
			}
			$this->method = $method;
		}else
			throw new \InvalidArgumentException('Invalid value for $method, should be one of the Request::METHOD_* constants.');
	}

	/**
	 * Set the request body data for POST and PUT requests.
	 * @param array|string|resource $data Array of items for POST, String or stream to set as the data for this request.
	 * @param int $length Length of the data that will get send (Recommended to be set for streams).
	 * @param bool $binary Whether or not the data given should treated as binary data.
	 * @throws \Exception When called on non POST or PUT requests or resource/string conversion fails.
	 */
	public function setBody($data, $length = null, $binary = false) {
		if(!($this->method == self::METHOD_POST || $this->method == self::METHOD_PUT))
			throw new \RuntimeException('Can only call Request::setBody on Post and Put requests.');

		if($binary == true)
			throw new \Exception('Binary posts are not supported with this request wrapper, install cURL instead.');

		if(is_array($data)){
			$this->params['content'] = http_build_query($data);
			$this->headers['content-type'] = 'application/x-www-form-urlencoded';
		}else if(is_string($data)){
			$this->params['content'] = $data;
			if(!isset($this->headers['content-type']))
				$this->headers['content-type'] = 'text/plain';
		}else if(is_resource($data))// @todo
			throw new \RuntimeException('Oops, this request wrapper doesn\'t support streams, sorry. Try to install cURL instead.');
		else
			throw new \InvalidArgumentException('Argument $data for method setBody should be of type array, string or resource, "'.gettype($data).'" given.');
	}

	/**
	 * @ignore
	 * @throws \HttpRuntimeException
	 */
	public function getBody(){
		throw new \HttpRuntimeException('The body cannot be retrieved from HTTPRequests because they are immediately written to the socket when set.');
	}

	/**
	 * Send this request and retrieve the request.
	 * @return Response The response on this request.
	 * @throws \Quark\Exception
	 */
	public function send() {
		// Compile the headers
		$this->params['header'] = '';
		foreach($this->headers as $header => $content)
			$this->params['header'] .= $header.': '.$content."\n";

		// Create the stream context
		$context = stream_context_create(array('http' => $this->params));

		// Create the handle
		$handle = fopen($this->url, 'r', false, $context);

		// Get contents
		$content = stream_get_contents($handle);
		fclose($handle);

		// Return
		$status = '';
		$headers = self::_parseHeaders($http_response_header, $status);
		return new Response($status, $headers, $content);
	}

	/**
	 * Parse headers into a dimensional array.
	 * @param string $raw Array of headers to parse.
	 * @param string $status A reference to a variable where the status string can be saved in.
	 * @return array
	 */
	private static function _parseHeaders($raw, &$status){
		$status = array_shift($raw);

		$headers = array();
		for($i=0; $i<count($raw); $i++){
			$exp = explode(':', $raw[$i]);
			$headers[trim($exp[0])] = trim($exp[1]);
		}
		return $headers;
	}
}

ClientRequest::register('Quark\\Protocols\\HTTP\\Client\\StreamRequest');