<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Protocols\HTTP;

use Quark\Error;
use Quark\System\Router\URL;
use Quark\System\Router\URLPathInfo;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Import required classes
\Quark\import(
	'Quark.Protocols.HTTP.Message',
	'Quark.Util.Type.Exception',
	'Quark.Protocols.HTTP.Exception'
);

/**
 * Interface Request
 * @package Quark\Services\HTTP
 */
interface IRequest extends IMessage {
	const VERSION_HTTP1		= 'HTTP/1.0';
	const VERSION_HTTP1_1	= 'HTTP/1.1';
	const VERSION_HTTP2		= 'HTTP/2.0';

	const METHOD_GET	= 'GET';
	const METHOD_POST	= 'POST';
	const METHOD_HEAD	= 'HEAD';
	const METHOD_PUT	= 'PUT';
	const METHOD_DELETE	= 'DELETE';

	/**
	 * Get the HTTP version used in the request.
	 * @return string
	 */
	public function getVersion();

	/**
	 * Get the method used for the request as a constant value.
	 * @return string One of the IRequest::METHOD_* constants.
	 */
	public function getMethod();

	/**
	 * Get the requested path.
	 * @return string Mostly a resource path (everything after http://example.com/) or an asterisk (*) when a resource does not apply for the defined method.
	 */
	public function getPath();

	/**
	 * (Try to) Get the requested resource path as an URLPathInfo object.
	 * @return URLPathInfo|null When the path is set as an asterisk (possible with some of the more exotic request methods) will return null.
	 */
	public function getPathObject();

	/**
	 * (Try to) Get the requested resource URI.
	 *
	 * Warning: Might not be possible to retrieve when the host header is not set, or there was no way to retrieve the IP-address the request came in from)
	 * @return string Mostly a fully qualified uri or an asterisk (*) when a resource does not apply for the def. method.
	 */
	public function getURI();

	/**
	 * (Try to) Get the requested resource URL as an URL object.
	 * @return URL|null When the path is set as an asterisk (possible with some of the more exotic request methods) will return null.
	 */
	public function getURLObject();

	/**
	 * Check whether or not this request was done over a secured channel. (e.g. HTTPS or at the moment of writing HTTP/2.0)
	 * @return bool
	 */
	public function isSecured();
}

/**
 * Interface IMutableRequest
 * @package Quark\Services\HTTP
 */
interface IMutableRequest extends IRequest, IMutableMessage {
	/**
	 * Set the HTTP version to be used in the request.
	 * @param string $version
	 */
	public function setVersion($version);

	/**
	 * Set the request method.
	 * @param string $method IRequest::METHOD_* constant.
	 * @return void
	 */
	public function setMethod($method);

	/**
	 * Set the requested resource path.
	 * @param string $path Mostly a resource path (everything after http://example.com/) or an asterisk (*) when a resource does not apply for the defined method.
	 */
	public function setPath($path);

	/**
	 * Set whether or not the connection is/should be secured.
	 * @param bool $secured
	 * @return void
	 */
	public function setSecured($secured);
}

/**
 * HTTP Request
 * @package Quark\Services\HTTP
 */
class Request extends Message implements IMutableRequest {
	/**
	 * @var boolean Whether or not this request should run/was done over a secured connection.
	 */
	protected $secured;

	/**
	 * @var string The requested path/resource.
	 */
	protected $path;

	/**
	 * @var int Method used for this request.
	 */
	protected $method;

	/**
	 * @var string Version used for this request.
	 */
	protected $version;

	/**
	 * @param string $hostname Hostname to call.
	 * @param string $path Path to the request you want to load.
	 * @param string $method The HTTP method with which to make the request (One of the IRequest::METHOD_* constants)
	 * @param bool $secured Whether or not the connection was secured. (E.g. with HTTPS, or HTTP 2.0 Secure)
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function __construct($hostname, $path, $method=self::METHOD_GET, $secured=false){
		if(is_string($hostname))
			$this->setHeader('Hostname', $hostname);
		else throw new InvalidArgumentTypeException('hostname', 'string', $hostname);

		if(is_string($path))
			$this->path = $path;
		else throw new InvalidArgumentTypeException('path', 'string', $path);

		if(is_string($method))
			$this->method = $method;
		else throw new InvalidArgumentTypeException('method', 'string', $method);

		$this->secured = (bool) $secured;
	}

	/**
	 * Make some of the internal vars read-only.
	 * @param string $var Variable name.
	 * @return int|string
	 * @throws \Exception
	 */
	public function __get($var){
		switch($var){
			case 'path':
				return $this->path;
			case 'method':
				return $this->method;
			default:
				throw new \Exception('Undefined request variable given.');
		}
	}

	#region StartLine Parsing
	/**
	 * Set the first line of the HTTP Message defined as the "Start-Line".
	 * @param string $startLine
	 * @throws HeaderException When the start-line could not be parsed and was mal-formatted.
	 */
	public function setStartLine($startLine){
		if(is_string($startLine)){
			$this->startLine = $startLine;

			$split = explode(' ', $startLine);
			if(count($split) >= 3){
				$this->method = array_shift($split);
				$this->version = array_pop($split);
				$this->path = implode(' ', $split);
			}else
				throw new HeaderException('Invalid header found, could not parse.');
		}
	}
	#endregion

	#region IMutableRequest Implementation
	/**
	 * Get the HTTP version used in the request.
	 * @return string
	 */
	public function getVersion(){
		return $this->version;
	}

	/**
	 * Set the HTTP version to be used in the request.
	 * @param string $version
	 */
	public function setVersion($version){
		$this->version = $version;
	}

	/**
	 * Get the requested resource URI.
	 * @return URL Mostly a fully qualified uri or an asterisk (*) when a resource does not apply for the def. method.
	 */
	public function getURLObject(){
		return new URL($this->getURI());
	}

	/**
	 * Get the requested resource URI.
	 * @return string|null When the path is set as an asterisk (possible with some of the more exotic request methods) will return null.
	 */
	public function getURI(){
		if($this->getPath() == '*')
			return null;
		return ($this->isSecured() ? 'https://' : 'http://').$this->getHeader('Hostname').'/'.$this->getPath();
	}

	/**
	 * Get the method used for the request as a constant value.
	 * @return string One of the IRequest::METHOD_* constants.
	 */
	public function getMethod(){
		return $this->method;
	}

	/**
	 * Set the request method.
	 * @param string $method IRequest::METHOD_* constant.
	 * @throws \InvalidArgumentException
	 * @return void
	 */
	public function setMethod($method){
		if(!(is_string($method) && !empty($method)))
			throw new \InvalidArgumentException('Expected $method to be a string and non-empty.');
		$this->method = $method;
	}

	/**
	 * Get the requested path.
	 * @return string Mostly a resource path (everything after http://example.com/) or an asterisk (*) when a resource does not apply for the defined method.
	 */
	public function getPath() {
		return $this->path;
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

	/**
	 * Set the requested resource path.
	 * @param string $path Mostly a resource path (everything after http://example.com/) or an asterisk (*) when a resource does not apply for the defined method.
	 */
	public function setPath($path) {
		$this->path = $path;
	}

	/**
	 * Check whether or not this request was done over a secured channel. (e.g. HTTPS or at the moment of writing HTTP/2.0)
	 * @return bool
	 */
	public function isSecured() {
		return $this->secured;
	}

	/**
	 * Set whether or not the connection is/should be secured.
	 * @param bool $secured
	 * @return void
	 */
	public function setSecured($secured) {
		$this->secured = (bool) $secured;
	}
	#endregion

	/**
	 * Create a basic response to the given request.
	 * @param int $code
	 * @param string $text
	 * @return \Quark\Protocols\HTTP\Response
	 */
	public function createResponse($code=200, $text='OK'){
		return new Response($this->version, $code, $text);
	}
}
