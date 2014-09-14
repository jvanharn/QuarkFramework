<?php
/**
 * Quark-based application server interface.
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 2, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Application\Server;

// Prevent individual file access
use Quark\System\Router\RouteCollection;
use Quark\Util\Type\InvalidArgumentTypeException;

if(!defined('DIR_BASE')) exit;

/**
 * Interface IServer
 *
 * Defines the basic functionality of a Quark application server.
 *
 * @property-read integer $port Listening socket port.
 * @property-read string $address Listening socket ipv4 address.
 *
 * @package Quark\System\Application\Server
 */
interface IServer {
	/**
	 * Create the server and configure it's listening socket.
	 * @param int $port
	 * @param string $address
	 */
	function __construct($port=8080, $address='0.0.0.0');

	/**
	 * Start the server/make the server listen.
	 */
	function start();

	/**
	 * Stop listening to said socket.
	 */
	function stop();
}

/**
 * Interface ICallbackServer
 * @package Quark\System\Application\Server
 */
interface ICallbackServer extends IServer {
	/**
	 * Set the handler for an incoming call to the server.
	 * @param callable $handler
	 */
	function setConnectionHandler(callable $handler);
}

/**
 * Interface IRoutingServer
 *
 * Serves an application by using routes to identify what application to run.
 * This construction /requires/ an dedicated main process that does nothing but route the requests and (several) others
 * to server the actual content.
 * @package Quark\System\Application\Server
 */
interface IRoutingServer extends IServer, RouteCollection { }

/**
 * Class baseServerUtils
 *
 * Class that contains all the parts that a server commonly uses.
 * @package Quark\System\Application\Server
 */
abstract class Server implements IServer {
	/**
	 * @var integer Listening socket port.
	 */
	protected $port;

	/**
	 * @var string Listening socket ipv4 address.
	 */
	protected $address;

	/**
	 * @var resource Socket server.
	 */
	protected $socket;

	/**
	 * @var bool Whether or not where listening.
	 */
	protected $listening = false;

	/**
	 * Create the server and configure it's listening socket.
	 * @param int $port
	 * @param string $address
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function __construct($port = 8080, $address = '0.0.0.0') {
		if(is_integer($port))
			$this->port = $port;
		else throw new InvalidArgumentTypeException('port', 'integer', $port);

		if(is_string($address))
			$this->address = $address;
		else throw new InvalidArgumentTypeException('address', 'string', $address);
	}

	/**
	 * Makes read-only variables available.
	 * @param $name
	 * @return int|string
	 * @throws \RuntimeException
	 */
	public function __get($name){
		switch($name){
			case 'port':
				return $this->port;
			case 'address':
				return $this->address;
			default:
				throw new \RuntimeException('Given variable doesn\'t exist on this class.');
		}
	}

	/**
	 * Creates the socket.
	 * @throws SocketException
	 */
	protected function _createSocket() {
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if($this->socket === false){
			throw new SocketException(
				$this,
				SocketException::CANT_CREATE_SOCKET,
				socket_strerror(socket_last_error())
			);
		}

		socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
	}

	/**
	 * Binds the socket.
	 * @throws SocketException
	 */
	protected function _bindSocket() {
		if(socket_bind($this->socket, $this->address, $this->port) === false){
			throw new SocketException(
				$this,
				SocketException::CANT_BIND_SOCKET,
				socket_strerror(socket_last_error($this->socket))
			);
		}
	}

	/**
	 * Listen the socket.
	 * @throws SocketException
	 */
	protected function _listenSocket(){
		if(socket_listen($this->socket, 5) === false){
			throw new SocketException(
				$this,
				SocketException::CANT_BIND_SOCKET,
				socket_strerror(socket_last_error($this->socket))
			);
		}
		$this->listening = true;
	}

	/**
	 * Make the current process an daemon.
	 *
	 * Detaches from the terminal using posix commands and listens for any SIGHUP, SIGTERM and SIGINT signals (and stops the server) using pcntl.
	 * @return bool Whether or not the detaching succeeded.
	 */
	protected function _demonize(){
		if(posix_setsid() == -1)
			return false;

		$stopServerCommand = array($this, 'stop');

		pcntl_signal(SIGINT, $stopServerCommand);
		pcntl_signal(SIGTERM, $stopServerCommand);
		pcntl_signal(SIGHUP, $stopServerCommand);
	}
}