<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define namespace
namespace Quark\Protocols\HTTP;

// Prevent individual file access
use Quark\Exception;
use Quark\Util\Type\InvalidArgumentTypeException;

if(!defined('DIR_BASE')) exit;

// Import required classes
\Quark\import(
	'Quark.Protocols.HTTP.Exception'
);

/**
 * Interface for a HTTP Message
 * @package Quark\Services\HTTP
 */
interface IMessage {
	/**
	 * RFC 1123 Date format used in response headers.
	 *
	 * As specified in the RFC 2616 HTTP/1.1 spec.
	 * Source: http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.18.1
	 */
	const DATE_RFC1123 = 'D, d M Y H:i:s';

	/**
	 * Get the first line of the HTTP Message defined as the "Start-Line".
	 * @return string
	 */
	public function getStartLine();

	/**
	 * Get all headers and their values.
	 * @return array
	 */
	public function getHeaders();

	/**
	 * Get the value of the header with the given token.
	 * @param string $token The token of the header to get.
	 * @return string|null
	 */
	public function getHeader($token);

	/**
	 * Get the request body data for POST and PUT requests.
	 * @param bool $raw Whether or not to retrieve the data as a raw resource/callable/string or try and force it as an string. This will generate an error when false an the stream returns an buffer larger than the set max body size.
	 * @param int $bufferLimit Maximal size of the returned body when it is converted from an resource. When -1 allows unlimited sizes. Does not apply to raw=true or when the raw data is already a string or comes from an callback.
	 * @return string|resource|callable
	 */
	public function getBody($raw=true, $bufferLimit=8192);
}

/**
 * Interface IMutableMessage
 * @package Quark\Services\HTTP
 */
interface IMutableMessage extends IMessage {
	/**
	 * Set the first line of the HTTP Message defined as the "Start-Line".
	 * @param string $startLine
	 * @return void
	 */
	public function setStartLine($startLine);

	/**
	 * Set a header on this request.
	 * @param string $token Header to set.
	 * @param string $value Header value.
	 * @return bool
	 */
	public function setHeader($token, $value);

	/**
	 * Remove an existing header.
	 * @param string $token The header token to remove.
	 * @return bool Whether or not the removal was successful.
	 */
	public function removeHeader($token);

	/**
	 * Set the request body data for POST and PUT requests.
	 * @param string|resource|callable $data String, stream or callback to use to retrieve the data for this request.
	 * @param bool $binary Whether or not the data given should treated as binary data.
	 * @throws \Quark\Exception When called on non POST or PUT requests or resource/string conversion fails.
	 * @throws \Quark\Exception When called with an invalid or non-acceptable $data parameter.
	 */
	public function setBody($data, $binary=false);
}

/**
 * Interface IClientMessage
 * @package Quark\Protocols\HTTP
 */
interface IClientMessage extends IMutableMessage {
	/**
	 * Set the request body data for POST and PUT requests.
	 * @param string|resource|callable $data String, stream or callback to use to retrieve the data for this request.
	 * @param bool $binary Whether or not the data given should treated as binary data.
	 * @param int $length Length of the data that will get send (Recommended to be set for streams).
	 * @throws \Quark\Exception When called on non POST or PUT requests or resource/string conversion fails.
	 * @throws \Quark\Exception When called with an invalid or non-acceptable $data parameter.
	 */
	public function setBody($data, $binary=false, $length=null);
}

/**
 * Class Message
 * @package Quark\Services\HTTP
 */
class Message implements IMutableMessage {
	/**
	 * Carriage Return / Line Feed
	 */
	const CRLF = "\r\n";

	/**
	 * Line Feed
	 */
	const LF = "\n";

	/**
	 * @var string
	 */
	protected $startLine;

	/**
	 * @var array Map of headers for this request.
	 */
	protected $headers = array();

	/**
	 * @var string The response body.
	 */
	protected $body = '';

	/*
	 * @param string $startLine The start-line of the http message to initialize the message with.
	 * @param array $headers The initial headers to set for the message.
	 * @param string|resource|callable $body The content for the message.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	/*public function __construct($startLine, $headers = array(), $body = ''){
		if(is_string($startLine))
			$this->startLine = $startLine;
		else throw new InvalidArgumentTypeException('startLine', 'string', $startLine);

		if(is_array($headers))
			$this->headers = $headers;
		else throw new InvalidArgumentTypeException('headers', 'array', $headers);

		$this->body = $body;
	}*/

	/**
	 * Get the first line of the HTTP Message defined as the "Start-Line".
	 * @return string
	 */
	public function getStartLine(){
		return $this->startLine;
	}

	/**
	 * Set the first line of the HTTP Message defined as the "Start-Line".
	 * @param string $startLine
	 */
	public function setStartLine($startLine){
		if(is_string($startLine))
			$this->startLine = $startLine;
	}

	#region Header Methods
	/**
	 * Set the headers for this request.
	 *
	 * Please note that this will replace any headers that were already set.
	 * @param array $headers The headers to set, in the format array('Content-Type: text/plain', 'Content-Length: 100').
	 * @throws \Quark\Exception When the headers parameter is incorrectly formatted.
	 */
	public function setHeaders($headers){
		if(is_array($headers))
			$this->headers = $headers;
		else
			throw new Exception('Parameter $headers should be of type array, but got "'.gettype($headers).'"');
	}

	/**
	 * Set a header on this request.
	 * @param string $token Header to set.
	 * @param string $value Header value.
	 * @return bool
	 */
	public function setHeader($token, $value){
		if(is_string($token) /*&& isset($this->headers[$token])*/){
			$this->headers[$token] = $value;
			return true;
		}else return false;
	}

	/**
	 * Get the value of the header with the given token.
	 * @param string $token The token of the header to get.
	 * @return string|null
	 */
	public function getHeader($token){
		if(isset($this->headers[$token]))
			return $this->headers[$token];
		else return null;
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

	#region Body Methods
	/**
	 * Set the request body data for POST and PUT requests.
	 * @param string|resource $data String or stream to set on the
	 * @param bool $binary Whether or not the data given should treated as binary data.
	 * @throws \Quark\Exception When called on non POST or PUT requests or resource/string conversion fails.
	 */
	public function setBody($data, $binary=false) {
		$this->body = $data;
	}

	/**
	 * Get the request body data for POST and PUT requests.
	 * @param bool $raw Whether or not to retrieve the data as a raw resource/callable/string or try and force it as an string. This will generate an error when false an the stream returns an buffer larger than the set max body size.
	 * @param int $bufferLimit Maximal size of the returned body when it is converted from an resource. When -1 allows unlimited sizes. Does not apply to raw=true or when the raw data is already a string or comes from an callback.
	 * @throws \RuntimeException When $this->body contains a data-type that is unexpected.
	 * @throws \Quark\Exception When the buffer exceeds the $bufferLimit.
	 * @return string|resource|callable
	 */
	public function getBody($raw=true, $bufferLimit=8192){
		if($raw == true || is_string($this->body))
			return $this->body;
		else if(is_resource($this->body)){
			$buffer = stream_get_contents($this->body, $bufferLimit);
			if($bufferLimit >= 0 && strlen($buffer) >= $bufferLimit){
				if(feof($this->body))
					return $buffer;
				else
					throw new Exception('The resource resulted in a buffer larger than the $bufferLimit.');
			}else return $buffer;
		}else if(is_callable($this->body)){
			return call_user_func($this->body, $this);
		}else throw new \RuntimeException('The $this->body of an Http Message contained an body of a type that is not in the domain of expected types. ');
	}
	#endregion

	#region Export/Import Methods
	/**
	 * Save the Http message to a string.
	 *
	 * Please note that writing {@link write()} this directly to a stream is more efficient when using larger messages.
	 * @see writeTo
	 * @param int $bodyBufferLimit The maximum size of the body to be passed to getBody.
	 * @return string
	 */
	public function save($bodyBufferLimit=8192){
		$message  = $this->getStartLine().self::CRLF;
		$message .= $this->saveHeaders().self::CRLF;
		return $message.$this->getBody(false, $bodyBufferLimit);
	}

	/**
	 * Save this Http Message to the given stream, file-handle or other type of stream.
	 * @param resource $stream The resource to write to.
	 * @param int $bodyBufferLimit The maximal size of the body to be written to the target stream when the body is an resource. Defaults to -1 which means that there is no limit.
	 * @return void
	 */
	public function writeTo($stream, $bodyBufferLimit=-1){
		fwrite($stream, $this->getStartLine().self::CRLF);
		fwrite($stream, $this->saveHeaders().self::CRLF);
		if(is_resource($this->body)){
			if($bodyBufferLimit == -1){
				while(!feof($this->body))
					fwrite($stream, fread($this->body, 8192));
			}else{
				$size = 0;
				while(!feof($this->body) && $size < $bodyBufferLimit)
					$size += fwrite($stream,
						fread($this->body,
							min(8192, $bodyBufferLimit - $size)));
			}
		}else fwrite($stream, $this->getBody(false, $bodyBufferLimit));
	}

	/**
	 * Get all the headers of this message as a string. (So no start-line etc.)
	 * @return string
	 */
	public function saveHeaders(){
		$headers = '';
		foreach($this->headers as $token => $value){
			$headers .= $token.': '.$value.self::CRLF;
		}
		return $headers;
	}

	/**
	 * @see save
	 * @return string
	 */
	public function __toString(){
		return $this->save();
	}

	/**
	 * Parses the given *single* http message in the form of a string and populates the given target object with the data that is extracted.
	 * @param string $message The message to parse.
	 * @param IMutableMessage $targetObject The target object of type IMessage to fill with the parsed data..
	 * @return void
	 */
	public static function parseInto($message, IMutableMessage &$targetObject){
		$parts = explode(self::CRLF.self::CRLF, $message, 2);
		$targetObject->setBody($parts[1]); // Set the body
		$headers = array_filter(explode(self::CRLF, $parts[0])); // Split the headers
		$targetObject->setStartLine(array_shift($headers)); // Set the startline
		$result = self::parseHeaders($headers); // Parse & sanitize the headers
		foreach($result as $header)
			$targetObject->setHeader($header[0], $header[1]);
	}

	/**
	 * Automatically reads the stream until it hits the body and sets the remaining stream as the body, parses the rest.
	 * @param resource $stream The stream resource to read from.
	 * @param IMutableMessage $targetObject
	 * @param int $maxHeaderSize Max size of the header in bytes or -1.
	 * @throws SocketReadException Thrown when no data was received before the socket closed.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException If the stream ain't a resource.
	 * @throws \LengthException When the length of the header starts to exceed the allotted $maxHeaderSize
	 * @return void
	 */
	public static function streamInto($stream, IMutableMessage &$targetObject, $maxHeaderSize=4098){
		if(!is_resource($stream) && !is_null($stream))
			throw new InvalidArgumentTypeException('stream', 'resource', $stream);

		// Check for a socket or file handle and act accordingly
		$meta = @stream_get_meta_data($stream);

		$buffer = '';
		$size = 0;
		if($meta !== false){ // A file handle or non socket_* created handle
			// Search for the end of the header.
			while(!feof($stream)){
				$buffer .= fread($stream, 2);
				if(strpos($buffer, self::CRLF.self::CRLF) !== false)
					break;

				$size += 2;
				if($maxHeaderSize > 0 && $size > $maxHeaderSize)
					throw new \LengthException('The length of the incoming message\'s header is larger than the allotted '.$maxHeaderSize.' bytes.');
			}
		}else{
			// Socket created handle.
			while(true){
				// @todo this is prone to abuse. Set a timeout which is checked for every time this runs. Change 0 into MSG_DONTWAIT
				//if(($num = socket_recv($stream, $chars, 2, MSG_WAITALL)) === false)
				if(($num = socket_recv($stream, $chars, 2, 0)) === false)
					break; // Socket close.

				if($num === 0)
					break; // No bytes send, fixes firefox indefinite hanging with recv flags set to 0

				$buffer .= $chars;
				$size += $num;

				if(strpos($buffer, self::CRLF.self::CRLF) !== false || $num < 2)
					break;

				if($maxHeaderSize > 0 && $size > $maxHeaderSize)
					throw new \LengthException('The length of the incoming message\'s header is larger than the allotted '.$maxHeaderSize.' bytes.');
			}
		}

		// Check buffer size
		if(strlen($buffer) != $size)
			throw new \LengthException('Something went wrong internally which resulted in having a different resulting buffer size that counted recieved bytes.');
		if($size == 0)
			throw new SocketReadException('Resource stream seems empty/unable to be read and could thus not stream it into the given object.');

		// Process header
		$headers = array_filter(explode(self::CRLF, $buffer)); // Split the headers
		$targetObject->setStartLine(array_shift($headers)); // Set the startline
		$result = self::parseHeaders($headers); // Parse & sanitize the headers
		foreach($result as $header)
			$targetObject->setHeader($header[0], $header[1]);

		// Set body
		$targetObject->setBody($stream);
	}

	/**
	 * Split the headers up into their sanitised key and value pairs.
	 * @param array $headers Headers to parse (split by newline, no status line).
	 * @throws HeaderException When the headers are incorrectly formatted.
	 * @return array Array of arrays where 0=key and 1=value. [['key', 'val'], ..]
	 */
	protected static function parseHeaders($headers){
		$current = -1;
		$result = array();
		for($i=0; $i<count($headers); $i++){
			$header = trim($headers[$i]);
			if(empty($header)) continue;

			if($headers[$i][0] == ' ' || $headers[$i][0] == "\t"){
				if($current == -1)
					throw new HeaderException('Incorrectly formatted header hit, can\'t continue parsing. Header list started with a multi-line header.');
				$result[$current][1] .= self::LF.$header;
			}else{
				$split = explode(':', $header, 2);
				if(!isset($split[1])) // Incorrectly formatted
					throw new HeaderException('Incorrectly formatted header hit, can\'t continue parsing. Header did not have valid name delimiter (:).');
				$result[] = array(
					\Quark\Filter\filter_string($split[0], ['chars' => CONTAINS_ALPHANUMERIC.'-']),
					ltrim($split[1])
				);
				$current++;
			}
		}
		return $result;
	}
	#endregion
}