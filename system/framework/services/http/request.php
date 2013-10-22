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
use Quark\Exception;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Check if cURL is installed
if(!in_array('curl', get_loaded_extensions()))
	Error::raiseFatalError(
		'Unable to make HTTP requests as this module currently requires the cURL PHP-extension to be installed.',
		'Unable to make HTTP requests as this module currently requires the cURL PHP-extension to be installed.');

/**
 * Protocol Class (Stream over Protocol)
 *
 * Defines the basis of what a (web) protocol should be able to do, and how it should communicate.
 * @package Quark\Services\HTTP
 */
class Request {
	const METHOD_GET	= 1;
	const METHOD_POST	= 2;
	const METHOD_HEAD	= 3;
	const METHOD_PUT	= 4;
	const METHOD_DELETE	= 5;

	/**
	 * @var resource cURL handle used to make this request.
	 */
	protected $handle;

	/**
	 * @var string The requested URL.
	 */
	protected $url;

	/**
	 * @var int Method used for this request.
	 */
	protected $method;

	/**
	 * @var array Manually set headers for this request.
	 */
	protected $headers = array();

	#region Magic Methods
	public function __construct($url, $method=self::METHOD_GET){
		$this->handle = curl_init();

		if(!empty($url))
			throw new Exception('Unable to construct a request with an empty URL.');
		if(!empty($method))
			throw new Exception('Unable to construct a request without a valid request method.');

		// Set URL
		$this->url = $url;
		curl_setopt($this->handle, CURLOPT_URL, $url);

		// Set method
		if(is_int($method)){
			switch($method){
				case self::METHOD_GET:
					//curl_setopt($this->handle, CURLOPT_HTTPGET, true);
					break;
				case self::METHOD_POST:
					curl_setopt($this->handle, CURLOPT_POST, true);
					break;
				case self::METHOD_HEAD:
					curl_setopt($this->handle, CURLOPT_HEADER, true);
					break;
				case self::METHOD_PUT:
					curl_setopt($this->handle, CURLOPT_PUT, true);
					break;
				case self::METHOD_DELETE:
					curl_setopt($this->handle, CURLOPT_CUSTOMREQUEST, 'DELETE');
					break;
				default:
					throw new Exception('Invalid value for $method, should be one of the Request::METHOD_* constants.');
			}
			$this->method = $method;
		}else
			throw new Exception('Invalid value for $method, should be one of the Request::METHOD_* constants.');

		// Set a couple of defaults
		curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, true);
	}

	public function __destruct(){
		if(!is_null($this->handle))
			curl_close($this->handle);
	}

	public function __get($var){
		switch($var){
			case 'url':
				return $this->url;
			case 'method':
				return $this->method;
			default:
				throw new Exception('Undefined request variable given.');
		}
	}
	#endregion

	#region Request Body Methods
	/**
	 * Set the request body data for POST and PUT requests.
	 * @param string|resource $data String or stream to set on the
	 * @param int $length Length of the data that will get send (Recommended to be set for streams).
	 * @param bool $binary Whether or not the data given should treated as binary data.
	 * @throws \Quark\Exception When called on non POST or PUT requests or resource/string conversion fails.
	 */
	public function setBody($data, $length=null, $binary=false){
		// Force binary for streams
		if(is_resource($data)) $binary = true;
		curl_setopt($this->handle, CURLOPT_BINARYTRANSFER, (bool) $binary);

		// Set the data
		if($this->method == self::METHOD_POST){
			if(is_resource($data)){
				$h = $data;
				$data = '';
				while(!feof($h))
					$data .= fread($h, ($length == null) ? 1024 : $length);
			}
			curl_setopt($this->handle, CURLOPT_POSTFIELDS, (string) $data);
		}else if($this->method == self::METHOD_PUT){
			if(!is_resource($data)){
				$fp = fopen('php://temp/maxmemory:256000', 'w+');
				if(!$fp) throw new Exception('Couldn\'t open a temporary memory stream to stream the request data into cURL.');
				fwrite($fp, $data);
				fseek($fp, 0);
				if(!is_int($length) || $length <= 0)
					$length = strlen($data);
				$data = $fp;
			}else if(!is_int($length) || $length <= 0){
				$length = null;
				$meta = stream_get_meta_data($data);

				// Try to treat this as a local thing
				if($meta['seekable'] == true){
					fseek($data, 0, SEEK_END);
					$length = ftell($data);
					fseek($data, 0);
				}

				// Treat this as a http stream and find it's contents
				else if($meta['wrapper_type'] == 'http' || $meta['wrapper_type'] == 'https' && isset($meta['wrapper_data']) && is_array($meta['wrapper_data'])){
					foreach($meta['wrapper_data'] as $header){
						if(stripos($header, 'Content-Length') > -1){
							$length = intval(trim(substr($header, strpos($header, ':')+1)));
							break;
						}
					}
					if($length == null)
						throw new Exception('Unable to determine the stream length by examining its meta-data, please manually give data length for this request.');
				}

				// Fall back on unread_bytes and throw a warning
				else if(isset($meta['unread_bytes']) && intval($meta['unread_bytes']) > 0){
					$length = intval($meta['unread_bytes']);
					Error::raiseWarning('Whilst trying to determine the length of the file to send with this request I had no way of determining the stream\'s length. Please provide the length of the stream, or completely read the stream into a string and provide that as $data parameter.');
				}

				// Unable to determine the length of the given stream
				else
					throw new Exception('Unable to auto-magically determine the length of the given stream/resource, please provide it manually by populating the $length parameter.');
			}
			curl_setopt($this->handle, CURLOPT_BINARYTRANSFER, (bool) $binary);
			curl_setopt($this->handle, CURLOPT_INFILE, $data);
			curl_setopt($this->handle, CURLOPT_INFILESIZE, $length);
		}else throw new Exception('Unable to set the request body for this http request method! Current method (Constant > Request::METHOD_*: "'.$this->method.'").');
	}
	#endregion

	#region Advanced Header Methods
	/**
	 * Set the headers for this request.
	 *
	 * Please note that this will replace any headers that were already set.
	 * @param array $headers The headers to set, in the format array('Content-type: text/plain', 'Content-length: 100').
	 * @throws \Quark\Exception When the headers parameter is incorrectly formatted.
	 */
	public function setHeaders($headers){
		if(is_array($headers))
			$this->headers = $headers;
		else
			throw new Exception('Parameter $headers should be of type array, but got "'.gettype($headers).'"');
	}

	/**
	 * Add a header to this request.
	 * @param string $header Header to set.
	 */
	public function addHeader($header){
		if(is_string($header))
			array_push($this->headers, $header);
	}

	/**
	 * Remove a manually set header.
	 * @param string $type The type of header to remove, may also be a complete header.
	 * @return bool Whether or not the removal was successful.
	 */
	public function removeHeader($type){
		$found = false;
		foreach($this->headers as $i => $header){
			if(stripos($header, $type) > -1){
				unset($this->headers[$i]);
				$found = true;
			}
		}
		return $found;
	}

	/**
	 * Get all manually set headers.
	 * @return array
	 */
	public function getHeaders(){
		return $this->headers;
	}
	#endregion

	#region Execution/Sending Methods
	/**
	 * Send this request and retrieve the request.
	 * @param bool $close Defaults to false. Whether or not you want to continue using this request object, set to true to preserve resources and thus improve performance.
	 * @return Response The response on this request.
	 * @throws \Quark\Exception
	 */
	public function send($close=false){
		// Copy to make the request
		$response_handle = $this->handle;
		if(!$close) $this->handle = curl_copy_handle($this->handle);

		// Set headers
		curl_setopt($response_handle, CURLOPT_HTTPHEADER, $this->headers);

		// Send
		$response = curl_exec($response_handle);
		$info = curl_getinfo($response_handle);

		// Check for errors
		$errorNumber = curl_errno($response_handle);
		if($errorNumber > 0)
			throw new Exception('An error occurred whilst trying to execute a http request using cURL: ('.$errorNumber.') '.self::_getErrorCodeString($errorNumber).': "'.curl_error($response_handle).'".');

		// Close connection
		curl_close($response_handle);

		// Create and return a resource response wrapper
		return new Response($info, $response);
	}

	/**
	 * Close the request
	 */
	public function close(){
		if(!is_null($this->handle))
			curl_close($this->handle);
	}

	/**
	 * Convert cURL error messages to readable strings.
	 * @param $errno
	 * @return mixed
	 */
	private static function _getErrorCodeString($errno){
		$error_codes = array(
			1 => 'CURLE_UNSUPPORTED_PROTOCOL',
			2 => 'CURLE_FAILED_INIT',
			3 => 'CURLE_URL_MALFORMAT',
			4 => 'CURLE_URL_MALFORMAT_USER',
			5 => 'CURLE_COULDNT_RESOLVE_PROXY',
			6 => 'CURLE_COULDNT_RESOLVE_HOST',
			7 => 'CURLE_COULDNT_CONNECT',
			8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
			9 => 'CURLE_REMOTE_ACCESS_DENIED',
			11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
			13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
			14 => 'CURLE_FTP_WEIRD_227_FORMAT',
			15 => 'CURLE_FTP_CANT_GET_HOST',
			17 => 'CURLE_FTP_COULDNT_SET_TYPE',
			18 => 'CURLE_PARTIAL_FILE',
			19 => 'CURLE_FTP_COULDNT_RETR_FILE',
			21 => 'CURLE_QUOTE_ERROR',
			22 => 'CURLE_HTTP_RETURNED_ERROR',
			23 => 'CURLE_WRITE_ERROR',
			25 => 'CURLE_UPLOAD_FAILED',
			26 => 'CURLE_READ_ERROR',
			27 => 'CURLE_OUT_OF_MEMORY',
			28 => 'CURLE_OPERATION_TIMEDOUT',
			30 => 'CURLE_FTP_PORT_FAILED',
			31 => 'CURLE_FTP_COULDNT_USE_REST',
			33 => 'CURLE_RANGE_ERROR',
			34 => 'CURLE_HTTP_POST_ERROR',
			35 => 'CURLE_SSL_CONNECT_ERROR',
			36 => 'CURLE_BAD_DOWNLOAD_RESUME',
			37 => 'CURLE_FILE_COULDNT_READ_FILE',
			38 => 'CURLE_LDAP_CANNOT_BIND',
			39 => 'CURLE_LDAP_SEARCH_FAILED',
			41 => 'CURLE_FUNCTION_NOT_FOUND',
			42 => 'CURLE_ABORTED_BY_CALLBACK',
			43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
			45 => 'CURLE_INTERFACE_FAILED',
			47 => 'CURLE_TOO_MANY_REDIRECTS',
			48 => 'CURLE_UNKNOWN_TELNET_OPTION',
			49 => 'CURLE_TELNET_OPTION_SYNTAX',
			51 => 'CURLE_PEER_FAILED_VERIFICATION',
			52 => 'CURLE_GOT_NOTHING',
			53 => 'CURLE_SSL_ENGINE_NOTFOUND',
			54 => 'CURLE_SSL_ENGINE_SETFAILED',
			55 => 'CURLE_SEND_ERROR',
			56 => 'CURLE_RECV_ERROR',
			58 => 'CURLE_SSL_CERTPROBLEM',
			59 => 'CURLE_SSL_CIPHER',
			60 => 'CURLE_SSL_CACERT',
			61 => 'CURLE_BAD_CONTENT_ENCODING',
			62 => 'CURLE_LDAP_INVALID_URL',
			63 => 'CURLE_FILESIZE_EXCEEDED',
			64 => 'CURLE_USE_SSL_FAILED',
			65 => 'CURLE_SEND_FAIL_REWIND',
			66 => 'CURLE_SSL_ENGINE_INITFAILED',
			67 => 'CURLE_LOGIN_DENIED',
			68 => 'CURLE_TFTP_NOTFOUND',
			69 => 'CURLE_TFTP_PERM',
			70 => 'CURLE_REMOTE_DISK_FULL',
			71 => 'CURLE_TFTP_ILLEGAL',
			72 => 'CURLE_TFTP_UNKNOWNID',
			73 => 'CURLE_REMOTE_FILE_EXISTS',
			74 => 'CURLE_TFTP_NOSUCHUSER',
			75 => 'CURLE_CONV_FAILED',
			76 => 'CURLE_CONV_REQD',
			77 => 'CURLE_SSL_CACERT_BADFILE',
			78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
			79 => 'CURLE_SSH',
			80 => 'CURLE_SSL_SHUTDOWN_FAILED',
			81 => 'CURLE_AGAIN',
			82 => 'CURLE_SSL_CRL_BADFILE',
			83 => 'CURLE_SSL_ISSUER_ERROR',
			84 => 'CURLE_FTP_PRET_FAILED',
			84 => 'CURLE_FTP_PRET_FAILED',
			85 => 'CURLE_RTSP_CSEQ_ERROR',
			86 => 'CURLE_RTSP_SESSION_ERROR',
			87 => 'CURLE_FTP_BAD_FILE_LIST',
			88 => 'CURLE_CHUNK_FAILED'
		);
		return $error_codes[$errno];
	}
	#endregion
}