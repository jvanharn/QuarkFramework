<?php
/**
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2014
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Namespace
namespace Quark\Protocols\HTTP\Server;
use Quark\Protocols\HTTP\IMutableResponse;
use Quark\Protocols\HTTP\Response;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Interface IServerResponse
 * @package Quark\Protocols\HTTP\Server
 */
interface IServerResponse extends IMutableResponse {
	/**
	 * Add the given string/bytes to the response.
	 * @param string $bytes String to add to the response.
	 * @return void
	 */
	public function write($bytes);
}

/**
 * Class ServerResponse
 * @package Quark\Protocols\HTTP\Server
 */
class ServerResponse implements IServerResponse {
	/**
	 * @var string HTTP Version to be set.
	 */
	protected $version = 'HTTP/1.1';

	/**
	 * @var string|null The custom status text or null.
	 */
	protected $statusText = null;

	/**
	 * Set the response body.
	 *
	 * Warning: Please beware that this class *only* accepts strings as a body, as they have to be immediately flushed.
	 * @param callable|resource|string $data
	 * @param bool $binary
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 * @return void
	 */
	public function setBody($data, $binary=false){
		if(is_string($data))
			$this->write($data); // Immediately flush
		else
			throw new InvalidArgumentTypeException('data', 'string', $data);
	}

	/**
	 * Add the given string/bytes to the response.
	 * @param string $bytes String to add to the response.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 * @return void
	 */
	public function write($bytes){
		if(is_string($bytes))
			print($bytes); // Echo.
		else
			throw new InvalidArgumentTypeException('bytes', 'string', $bytes);
	}

	/**
	 * Get the first line of the HTTP Message defined as the "Start-Line".
	 * @return string
	 */
	public function getStartLine() {
		return $this->getVersion().' '.$this->getStatusCode().' '.$this->getStatusText();
	}

	/**
	 * Get all headers and their values.
	 * @return array
	 */
	public function getHeaders() {
		return headers_list();
	}

	/**
	 * Get the request body data for POST and PUT requests.
	 * @param bool $raw Whether or not to retrieve the data as a raw resource/callable/string or try and force it as an string. This will generate an error when false an the stream returns an buffer larger than the set max body size.
	 * @param int $bufferLimit Maximal size of the returned body when it is converted from an resource. When -1 allows unlimited sizes. Does not apply to raw=true or when the raw data is already a string or comes from an callback.
	 * @throws \RuntimeException
	 * @return null
	 */
	public function getBody($raw = true, $bufferLimit = 8192) {
		throw new \RuntimeException('Unable to retrieve the body contents as they get flushed as they get written.');
	}

	/**
	 * Set the first line of the HTTP Message defined as the "Start-Line".
	 * @param string $startLine
	 * @return void
	 */
	public function setStartLine($startLine) {
		header($startLine);
	}

	/**
	 * Set a header on this request.
	 * @param string $token Header to set.
	 * @param string $value Header value.
	 * @return bool
	 */
	public function setHeader($token, $value) {
		header($token.': '.$value);
	}

	/**
	 * Get the value of the header with the given token.
	 * @param string $token The token of the header to get.
	 * @return string|null
	 */
	public function getHeader($token) {
		return @headers_list()[$token];
	}

	/**
	 * Remove an existing header.
	 * @param string $token The header token to remove.
	 * @return bool Whether or not the removal was successful.
	 */
	public function removeHeader($token) {
		header($token.': ');
	}

	/**
	 * Get the protocol version the server replied with. (E.g. HTTP/1.1)
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * Get the http status code. (E.g. 404)
	 * @return int
	 */
	public function getStatusCode() {
		return http_response_code();
	}

	/**
	 * Get the http status text. (E.g. 'Not found.')
	 * @return string
	 */
	public function getStatusText() {
		if(is_null($this->statusText))
			return Response::$statusCodes[$this->getStatusCode()];
		return $this->statusText;
	}

	/**
	 * Check whether the response body was not empty.
	 */
	public function hasBody() {
		// TODO: Implement hasBody() method.
	}

	/**
	 * Set the HTTP version to be used in the request.
	 * @param string $version
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * Set the response status.
	 * @param int $code
	 * @param string $text
	 * @return void
	 */
	public function setStatus($code, $text=null) {
		if(empty($text)){
			http_response_code($code);
			$this->statusText = null;
		}else{
			header($this->getVersion().' '.$code.' '.$text);
			$this->statusText = $text;
		}
	}
}