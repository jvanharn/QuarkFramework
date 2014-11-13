<?php
/**
 * Application server client object.
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 2, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Protocols\HTTP\Server;
use Quark\Protocols\HTTP\IResponse;
use Quark\Protocols\HTTP\Message;
use Quark\Protocols\HTTP\Request;
use Quark\Protocols\HTTP\Response;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Interface IClient
 *
 * Represents a client, makes it easier to restrict the length of requests and to distinguish between headers/body etc.
 * This class mainly manages a client connection after it is accepted. (also extended connections where the socket
 * remains open and multiple http requests come in over the same connection are handled by this construction).
 *
 * Please note that when maxRequestSize is reached in cases when the errorHandler is not set the error responses are automatically generated.
 * @package Quark\System\Application\Server
 */
interface IClient {
	/**
	 * Get the currently handled request (this object may span several requests).
	 * @return Request
	 */
	public function getRequest();

	/**
	 * Write a single response object and end the current request.
	 * @param IResponse $response The response to write.
	 * @return void
	 */
	public function respond(IResponse $response);

	/**
	 * Write the given bytes to the client. (Wont close/end the current request).
	 * @param string $bytes The string to send.
	 * @return void
	 */
	public function write($bytes);

	/**
	 * Flushes and ends the current request.
	 *
	 * When the client is persistent it will also move the request pointer to the next request.
	 * Warning: Beware that in most cases this will result in the same behaviour as {@link close()}!
	 * @return void
	 */
	public function flush();

	/**
	 * Check if the *current request* has been flushed already.
	 * @return bool
	 */
	public function hasFlushed();

	/**
	 * Close the connection with the client.
	 * @return void
	 */
	public function close();

	/**
	 * Check if the connection with the client is still open.
	 * @return bool
	 */
	public function isClosed();
}

/**
 * Interface IPersistentClient
 *
 * Provides the means to have an HTTP Client that keeps the connection open and requests multiple resources over a single (accepted) socket.
 * @package Quark\Protocols\HTTP\Server
 */
interface IPersistentClient extends IClient {
	const STAGE_ACCEPTED = 0;
	const STAGE_READ_REQUEST = 1;
	const STAGE_WROTE_RESPONSE = 2;
	const STAGE_CLOSED_CONNECTION = 3;

	/**
	 * Get the currently handled request (this object may span several requests) or the request with the given index.
	 * @param integer $index An index of 0 or higher will result in that index being returned. Defaults to -1 which will result in the current request.
	 * @return Request
	 */
	public function getRequest($index=-1);

	/**
	 * Get the index of the current request.
	 * @return integer
	 */
	public function index();

	/**
	 * Check if the current request or the request with the given index has been flushed already.
	 * @param integer $index An index of 0 or higher will result in that index being checked. Defaults to -1 which will result in the current request being checked.
	 * @return bool
	 */
	public function hasFlushed($index=-1);

	/**
	 * Get the stage the current connection with the client is in.
	 */
	public function getStage();
}

/**
 * Class Client
 * @package Quark\Protocols\HTTP\Server
 */
class SocketClient implements IPersistentClient {

	/**
	 * @var int Max size of an incoming request.
	 */
	protected $maxRequestSize = 8192;

	/**
	 * @var resource
	 */
	private $socket;

	/**
	 * @var int Stage of the connection.
	 */
	private $stage = self::STAGE_ACCEPTED;

	/**
	 * @var Request Cached request object of the last parsed request.
	 */
	private $request;

	/**
	 * @var callable Handler for incoming http requests that where in some way erroneous.
	 */
	private $errorHandler;

	/**
	 * @param resource $socket The accepted socket.
	 * @param callable $errorHandler Handler for incoming http requests that where in some way erroneous. Takes 4 arguments, which is this object, the statusCode (e.g. 500), statusText (e.g. Internal server Error), errorMessage (e.g. Server ran out of memory.)
	 * @param int $maxRequestSize Max size of the request in bytes.
	 */
	public function __construct($socket, callable $errorHandler=null, $maxRequestSize=8192){
		$this->socket = $socket;
		$this->maxRequestSize = $maxRequestSize;
		$this->errorHandler = $errorHandler;
	}

	/**
	 * Get the stage the current request is in.
	 */
	public function getStage(){
		return $this->stage;
	}

	/**
	 * Get the Http Request object for the current request.
	 * @return Request
	 */
	public function getRequest(){
		if($this->stage >= self::STAGE_READ_REQUEST)
			return $this->request;
		else
			return ($this->request = $this->readRequest());
	}

	/**
	 * Write a Http Response object to the client socket.
	 * @param Response $response
	 * @throws \HttpRuntimeException
	 */
	public function writeResponse(Response $response){
		if($this->stage != self::STAGE_READ_REQUEST)
			throw new \HttpRuntimeException('The client connection is not in the correct state to be writing a response to it right now. Please make sure you first retrieve the request before you write the response, the response is not already written and make sure the connection is not closed.');

		socket_write($this->socket, (string) $response);

		$this->stage = self::STAGE_WROTE_RESPONSE;
	}

	/**
	 * Checks whether the socket was closed.
	 *
	 * When the client socket is closed, this is permanent. A new client object has to be created to handle new requests.
	 */
	public function isClosed(){
		if($this->stage == self::STAGE_CLOSED_CONNECTION)
			return true;

		$data = stream_get_meta_data($this->socket);
		if($data['timed_out'] === true){
			$this->stage = self::STAGE_CLOSED_CONNECTION;
			return true;
		}

		return false;
	}

	/**
	 * Manually close the connection.
	 *
	 * During normal operation you do not need to close the connection manually; this is handled by the class itself.
	 */
	public function close(){
		socket_close($this->socket);
		$this->stage = self::STAGE_CLOSED_CONNECTION;
	}

	/**
	 * Read the given amount of bytes.
	 * @param int $size
	 * @return null|string
	 */
	protected function read($size=1024){
		$buffer = @socket_read($this->socket, $size, PHP_BINARY_READ);
		if($buffer === false)
			return null;
		else return $buffer;
	}

	/**
	 * @return Request|null
	 */
	protected function readRequest(){
		// Read the entire response into a buffer.
		/*$buffer = $this->read($this->maxRequestSize+1);
		$buflen = strlen($buffer);
		if($buflen >= $this->maxRequestSize){
			$this->callErrorHandler(413, 'Request Entity Too Large', 'The request was of or exceeded the maximal alloted size of a Http Request.');
			return null;
		}*/

		// @todo IS EXACTLY THE SAME AS THE HTTPCLIENT!!!!!!!!
		// Parse the request
		$request = new Request('localhost', '/');
		$this->stage = self::STAGE_READ_REQUEST;
		try {
			Message::streamInto($this->socket, $request, $this->maxRequestSize); // @todo this is the max *request size*, although the parameter is *header size*, probably have to change the parameters. Do use this method as it is infinitely more efficient and prevents buffer overflows/out of memory exceptions (to an extend).
		}catch(\LengthException $e){
			$this->callErrorHandler(413, 'Request Entity Too Large', 'The request was of or exceeded the maximal allotted size of a Http Request.');
			return null;
		}
		return $request;
	}

	/**
	 * Calls the error handler if available otherwise just writes the message as response and closes the connection.
	 * @param integer $statusCode
	 * @param string $statusText
	 * @param string $errorMessage
	 */
	protected function callErrorHandler($statusCode, $statusText, $errorMessage){
		if(empty($this->errorHandler)){
			$response = $this->request->createResponse($statusCode);
			$response->setStatus($statusCode, $statusText);
			$response->setBody('<h2>'.$statusText.'</h2><p>'.$errorMessage.'</p>');
			$this->writeResponse($response);
		}else{
			call_user_func($this->errorHandler, $this, $statusCode, $statusText, $errorMessage);
		}
		$this->close();
	}
}