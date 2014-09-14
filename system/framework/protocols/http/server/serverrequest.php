<?php
/**
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2014
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Protocols\HTTP\Server;
use Quark\Protocols\HTTP\IRequest;
use Quark\System\Router\URL;
use Quark\System\Router\URLPathInfo;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class ServerRequest
 *
 * This class emulates the incoming request when Quark is run behind a webserver/httpd.
 * It is read-only (in contrast to the default implementation) and maps all methods to native php/hphp calls.
 * @package Quark\Protocols\HTTP\Server
 */
class ServerRequest implements IRequest {
	/**
	 * Get the first line of the HTTP Message defined as the "Start-Line".
	 * @return string
	 */
	public function getStartLine() {
		return
			$this->getMethod().' '.
			$this->getURI().' '.
			$this->getVersion();
	}

	/**
	 * Get all headers and their values.
	 * @return array
	 */
	public function getHeaders() {
		if(function_exists('apache_request_headers'))
			return apache_request_headers();
		$headers = array();
		if(!empty($_SERVER['HTTP_ACCEPT']))
			$headers['Accept'] = $_SERVER['HTTP_ACCEPT'];
		if(!empty($_SERVER['HTTP_ACCEPT_CHARSET']))
			$headers['Accept-Charset'] = $_SERVER['HTTP_ACCEPT_CHARSET'];
		if(!empty($_SERVER['HTTP_ACCEPT_ENCODING']))
			$headers['Accept-Encoding'] = $_SERVER['HTTP_ACCEPT_ENCODING'];
		if(!empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
			$headers['Accept-Language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		if(!empty($_SERVER['HTTP_CONNECTION']))
			$headers['Connection'] = $_SERVER['HTTP_CONNECTION'];
		if(!empty($_SERVER['HTTP_HOST']))
			$headers['Host'] = $_SERVER['HTTP_HOST'];
		if(!empty($_SERVER['HTTP_REFERER']))
			$headers['Referer'] = $_SERVER['HTTP_REFERER'];
		if(!empty($_SERVER['HTTP_USER_AGENT']))
			$headers['User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
		return $headers;
	}

	/**
	 * Get the request body data for POST and PUT requests.
	 * @param bool $raw Whether or not to retrieve the data as a raw resource/callable/string or try and force it as an string. This will generate an error when false an the stream returns an buffer larger than the set max body size.
	 * @param int $bufferLimit Maximal size of the returned body when it is converted from an resource. When -1 allows unlimited sizes. Does not apply to raw=true or when the raw data is already a string or comes from an callback.
	 * @return string|resource|callable
	 */
	public function getBody($raw = true, $bufferLimit = 8192) {
		if($raw == true)
			return fopen('php://input', 'r');
		else
			return file_get_contents('php://input', null, null, null, $bufferLimit);
	}

	/**
	 * Get the HTTP version used in the request.
	 * @return string
	 */
	public function getVersion() {
		// No idea
		return 'HTTP/1.1';
	}

	/**
	 * Get the method used for the request as a constant value.
	 * @return string One of the IRequest::METHOD_* constants.
	 */
	public function getMethod() {
		return \Quark\Filter\filter_string(strtoupper($_SERVER['REQUEST_METHOD']), ['CHARS' => CONTAINS_ALPHA_UPPER]);
	}

	/**
	 * Get the requested uri path.
	 * @return string
	 */
	public function getPath(){
		$path = \Quark\Filter\filter_string($_SERVER['REQUEST_URI'], ['CHARS' => CONTAINS_URL_PATH]);
		if($path[0] == '/')
			return $path;
		else
			return '/'.$path;
	}

	/**
	 * Get the request's hostname.
	 * @return string
	 */
	public function getHost(){
		if(!empty($_SERVER['HTTP_HOST']))
			return \Quark\Filter\filter_string($_SERVER['HTTP_HOST'], ['CHARS' => CONTAINS_ALPHANUMERIC.'-']); // @todo accept intl domain names http://en.wikipedia.org/wiki/Internationalized_domain_name
		else
			return \Quark\Filter\filter_string($_SERVER['SERVER_ADDR'], ['CHARS' => CONTAINS_HEXADECIMAL.'.:']); // Yay ipv6 ready
	}

	/**
	 * Get the requested resource URI.
	 * @return string Mostly a fully qualified uri or an asterisk (*) when a resource does not apply for the def. method.
	 */
	public function getURI() {
		return
			($this->isSecured()?'https':'http').'://'.$this->getHost().$this->getPath();
	}

	/**
	 * Get the requested resource URI.
	 * @return URL Mostly a fully qualified uri or an asterisk (*) when a resource does not apply for the def. method.
	 */
	public function getURLObject() {
		return new URL($this->getURI());
	}

	/**
	 * Check whether or not this request was done over a secured channel. (e.g. HTTPS or at the moment of writing HTTP/2.0)
	 * @return bool
	 */
	public function isSecured() {
		return !empty($_SERVER['HTTPS']);
	}

	/**
	 * (Try to) Get the requested resource path as an URLPathInfo object.
	 * @return URLPathInfo|null When the path is set as an asterisk (possible with some of the more exotic request methods) will return null.
	 */
	public function getPathObject() {
		if($this->getPath() == '*')
			return null;
		$parsed = parse_url($this->getPath());
		return new URLPathInfo($parsed['path'], $parsed['query'], $parsed['fragment']);
	}
}