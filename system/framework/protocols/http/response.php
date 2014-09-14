<?php
/**
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Protocols\HTTP;

use Quark\Error;
use Quark\Util\Type\InvalidArgumentTypeException;

/**
 * Interface Response
 * @package Quark\Services\HTTP
 */
interface IResponse extends IMessage {
	/**
	 * Get the protocol version the server replied with. (E.g. HTTP/1.1)
	 * @return string
	 */
	public function getVersion();

	/**
	 * Get the http status code. (E.g. 404)
	 * @return int
	 */
	public function getStatusCode();

	/**
	 * Get the http status text. (E.g. 'Not found.')
	 * @return string
	 */
	public function getStatusText();

	/**
	 * Check whether the response body was not empty.
	 */
	public function hasBody();
}

/**
 * Interface MutableResponse
 * @package Quark\Services\HTTP
 */
interface IMutableResponse extends IResponse, IMutableMessage {
	/**
	 * Set the HTTP version to be used in the request.
	 * @param string $version
	 */
	public function setVersion($version);

	/**
	 * Set the response status.
	 * @param int $code
	 * @param string|null $text
	 * @return void
	 */
	public function setStatus($code, $text=null);
}

/**
 * Class Response
 * @package Quark\Services\HTTP
 */
class Response extends Message implements IMutableResponse {
	/**
	 * @var array A list of all status codes and their text messages.
	 */
	public static $statusCodes = array(
		// 1xx Informational
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',

		// 2xx Success
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',

		// 3xx Redirection
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		306 => 'Switch Proxy',
		307 => 'Temporary Redirect', // keep method
		308 => 'Permanent Redirect', // keep method

		// 4xx Client Error
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => 'I\'m a teapot',
		419 => 'Authentication Timeout',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		440 => 'Login Timeout',
		444 => 'No Response',
		449 => 'Retry With',
		451 => 'Unavailable For Legal Reasons',

		// 5xx Server Error
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
	);

	/**
	 * @var string HTTP Version.
	 */
	protected $version;

	/**
	 * @var integer Http status code, message and version from response.
	 */
	protected $statusCode;

	/**
	 * @var string The status text accompanying the code.
	 */
	protected $statusText;

	/**
	 * @param integer $statusCode HTTP Status Code
	 * @param string $statusText HTTP Status Text
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function __construct($statusCode=200, $statusText='OK'){
		if(is_integer($statusCode))
			$this->statusCode = $statusCode;
		else throw new InvalidArgumentTypeException('statusCode', 'integer', $statusCode);

		if(is_string($statusText))
			$this->$statusText = $statusText;
		else throw new InvalidArgumentTypeException('statusText', 'string', $statusText);
	}

	/**
	 * @param string $startLine
	 */
	public function setStartLine($startLine){
		$this->startLine = $startLine;

		$exp = explode(' ', $this->startLine);
		$this->version = $exp[0];
		$this->statusCode = intval($exp[1]);
		$this->statusText = $exp[2];
	}

	/**
	 * Get the protocol version the server replied with. (E.g. HTTP/1.1)
	 * @return string
	 */
	public function getVersion(){
		return $this->version;
	}

	/**
	 * Set the HTTP version to be used in the request.
	 * @param string $version
	 */
	public function setVersion($version) {
		$this->version = $version;
	}

	/**
	 * Get the http status code. (E.g. 404)
	 * @return int
	 */
	public function getStatusCode(){
		return $this->statusCode;
	}

	/**
	 * Get the http status text. (E.g. 'Not found.')
	 * @return string
	 */
	public function getStatusText(){
		return $this->statusText;
	}

	/**
	 * Set the response status.
	 * @param integer $code
	 * @param string $text
	 * @return string
	 */
	public function setStatus($code, $text=null) {
		$this->statusCode = $code;
		if(empty($text))
			$this->statusText = self::$statusCodes[$code];
		else
			$this->statusText = $text;
	}

	/**
	 * Check whether the response body was not empty.
	 */
	public function hasBody(){
		return !empty($this->body);
	}
}
