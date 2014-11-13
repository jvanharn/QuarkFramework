<?php
/**
 * Forking Quark application server.
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 2, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Application\Server;
use Quark\Document\Document;
use Quark\Document\Layout\BasicLayout;
use Quark\Document\Utils\Literal;
use Quark\Exception;
use Quark\Protocols\HTTP\IMutableResponse;
use Quark\Protocols\HTTP\IResponse;
use Quark\Protocols\HTTP\Response;
use Quark\System\Router\IRoutableRequest;
use Quark\System\Router\RoutableRequest;
use Quark\System\Router\Route;
use Quark\System\Router\Router;
use Quark\Util\Type\HttpException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Forking Application Server
 *
 * @property-read integer $port Listening socket port.
 * @property-read string $address Listening socket ipv4 address.
 *
 * @package Quark\System\Application\Server
 */
abstract class ForkingServer extends Server {
	/**
	 * @var int Master process identifier. The PID of the server process.
	 */
	protected $master = -1;

	/**
	 * @var int The Process Identifier of the current server or child fork.
	 */
	protected $pid = -1;

	/**
	 * @var bool Whether the current process is the parent or the child.
	 */
	protected $parent = true;

	/**
	 * @var array Array of child PIDs
	 */
	protected $children = array();

	/**
	 * Start the server/make the server listen.
	 */
	public function start() {
		$this->master = getmypid();

		// Start socket
		$this->_createSocket();
		$this->_bindSocket();
		$this->_listenSocket();

		// Start main loop
		$this->loop();

		// Close the whole thing again
		$this->stop();
	}

	/**
	 * Stop the server/stop listening.
	 */
	public function stop(){
		$this->listening = false;
		socket_close($this->socket);
	}

	/**
	 * The main server loop.
	 */
	protected function loop(){
		while($this->listening){
			// Try and accept the next connection
			if(($client = @socket_accept($this->socket)) === false){
				throw new SocketException(
					$this,
					SocketException::CANT_ACCEPT,
					socket_strerror(socket_last_error($this->socket)));
				continue;
			}

			// Incoming connection (Time to do something as the server process)
			$this->handleIncomingConnection();

			// Fork the server
			if(!$this->_forkProcess()){
				throw new ForkingException('Something went wrong when trying to fork for an incoming connection.');
			}

			// Check if parent
			if($this->parent)
				continue;

			// Handle the socket as the forked process/thread.
			try {
				$httpClient = new HttpClient($client);

				// Make sure the httpclient is correctly configured.
				$this->handleClientCreation($httpClient);

				$request = $httpClient->getRequest();
				$response = $request->createResponse();

				// Handle keep-alive
				if(strcasecmp($request->getHeader('Connection'), 'Keep-Alive') == 0){
					socket_set_option($client, SOL_SOCKET, SO_KEEPALIVE, 1);
					$response->setHeader('Connection', 'keep-alive');
				}else
					$response->setHeader('Connection', 'close');

				// Set bare minimum headers
				// @todo

				try {
					$this->handleAcceptedConnection($httpClient);
				}catch(HttpException $httpException){
					// @todo allow for custom error messages
					// @todo check for asked mime-types and reply accordingly.
					$httpException->writeTo($response);

					$response->setHeader('Connection', 'close');
					$httpClient->writeResponse($response);
					$httpClient->close();
				}

				// @todo handle stay alive connections.
				if(!$httpClient->isClosed()){
					$httpClient->close();
					// @todo log connection close
				}

				// Connection handled; stop this child.
				if(!defined('ROUTED_REQUEST'))
					define('ROUTED_REQUEST', 1);
				exit(1);
			}catch(\Exception $e){
				// @todo log this
				echo 'An error occurred in procid '.$this->pid.' ('.($this->parent?'parent':'child/fork').') whilst handling an incoming connection: '.$e->__toString().PHP_EOL;
			}
		}
	}

	/**
	 * Should handle an incoming already accepted connection.
	 *
	 * WARNING: Please beware that this method get's executed in the context of the PARENT PROCESS
	 * @return void
	 */
	protected function handleIncomingConnection(){}

	/**
	 * Should handle an incoming already accepted connection, just after the client is created and no data has been read.
	 *
	 * WARNING: Please beware that this method already get's executed in the CHILD PROCESS.
	 * NOTICE: This fires before handleAcceptedConnection
	 * @param HttpClient $client Accepted client connection.
	 * @return void
	 */
	protected function handleClientCreation(HttpClient $client){}

	/**
	 * Should handle an incoming already accepted connection.
	 *
	 * WARNING: Please beware that this method already get's executed in the CHILD PROCESS.
	 * @param HttpClient $client Accepted client connection.
	 * @return void
	 */
	abstract protected function handleAcceptedConnection(HttpClient $client);

	/**
	 * Fork an process.
	 * @return bool Whether or not the fork was successful.
	 */
	protected function _forkProcess(){
		// Fork
		$pid = pcntl_fork(); // returns child's pid if parent and 0 if child.
		if ($pid == -1) {
			$this->pid = -1;
			return false; // Something went wrong when forking.
		} else if ($pid > 0) {
			$this->children[] = $pid;
			$this->parent = true; // we are the parent
		} else {
			$this->parent = false; // we are the child
			//$this->_demonize(); // Detach all
		}
		$this->pid = getmypid();
		return true;
	}
}

/**
 * Class ForkingException
 *
 * An exception that occurs when the server is unable to fork.
 * @package Quark\System\Application\Server
 */
class ForkingException extends Exception { }

/**
 * Class CallbackForkingServer
 * @package Quark\System\Application\Server
 */
class CallbackForkingServer extends ForkingServer implements ICallbackServer {
	/**
	 * @var callable
	 */
	protected $connectionHandler;

	/**
	 * Should handle an incoming already accepted connection.
	 *
	 * WARNING: Please beware that this method already get's executed in the CHILD PROCESS.
	 * @param HttpClient $client Accepted client connection.
	 * @return void
	 */
	protected function handleAcceptedConnection(HttpClient $client) {
		call_user_func($this->connectionHandler, $client);
	}

	/**
	 * Set the handler for an incoming call to the server.
	 * @param callable $handler
	 */
	function setConnectionHandler(callable $handler) {
		$this->connectionHandler = $handler;
	}
}

/**
 * Class RoutingForkingServer
 *
 * To keep double iterations at bay this server's RouteCollection methods call their counterparts on the {@link Quark\System\Router\Router} class.
 *
 * @see Quark\System\Router\Router
 * @property-read Router $router
 * @package Quark\System\Application\Server
 */
class RoutingForkingServer extends ForkingServer implements IRoutingServer {
	/**
	 * @var Router The used router instance for routing requests to the correct parts of the system.
	 */
	protected $router;

	/**
	 * Create the server and configure it's listening socket.
	 * @param int $port
	 * @param string $address
	 * @param array $routes An array of routes to initialize the server with.
	 * @param string $hostname The hostname for the website to listen to.
	 */
	public function __construct($port = 8080, $address = '0.0.0.0', array $routes=array(), $hostname=null) {
		parent::__construct($port, $address);

		$this->router = Router::createInstance(
			(empty($hostname) ? $address.':'.$port : $hostname),
			$routes
		);
	}

	/**
	 * @access private
	 * @param string $name
	 * @return Router|int|string|void
	 */
	public function __get($name){
		if($name == 'router')
			return $this->router;
		else
			return parent::__get($name);
	}

	/**
	 * Should handle an incoming already accepted connection.
	 *
	 * WARNING: Please beware that this method get's executed in the context of the PARENT PROCESS
	 * @return void
	 */
	protected function handleIncomingConnection(){}

	/**
	 * This method makes sure the HttpClient gives us the correct IRequest implementation.
	 * @param HttpClient $client
	 */
	protected function handleClientCreation(HttpClient $client){
		$client->setRequestFactory(function(){
			return new RoutableRequest('0.0.0.0', '/'); // @todo use the configured default server ip.
		});
	}

	/**
	 * Handles an incoming already accepted connection by routing it whenever possible.
	 * @param HttpClient $client Accepted client connection.
	 * @throws \Quark\Util\Type\HttpException
	 * @return void
	 */
	protected function handleAcceptedConnection(HttpClient $client) {
		// Route the request
		/** @var IRoutableRequest $request */
		$request = $client->getRequest();
		$response = $request->createResponse();
		if($this->router->route($request, $response) === false){ // Here's the routing magic.
			// Resource not found. (404 Not Found)
			throw new HttpException(404, 'I was unable to find the resource you are looking for, are you sure it was here?<br/>Maybe you want to try the <a href="/">Homepage</a>?');
		}else if($response->hasBody()){ // Routing was successful
			$client->writeResponse($response);
		}

		// Check if the response has been written already..
		if($client->getStage() < HttpClient::STAGE_WROTE_RESPONSE) {
			// ..it hasn't; check if it at least filled the document object.
			if(Document::hasInstance() && Document::getInstance()->hasContent()){
				// Yay! Content!
				Document::getInstance()->toResponse($documentResponse = $request->createResponse());
				$client->writeResponse($documentResponse);
			}else{
				// No content: Internal server error; Nobody wrote a response :/
				throw new HttpException(500, 'Your request got routed correctly, but nobody generated an actual response.. It is probably wise to inform the webmaster or file a bug.');
			}
		}
	}

	/**
	 * Attach a route to the collection.
	 * @param \Quark\System\Router\Route $route
	 * @return void
	 */
	public function attachRoute(Route $route) {
		$this->router->attachRoute($route);
	}

	/**
	 * Detach a route from this collection.
	 * @param \Quark\System\Router\Route $route
	 * @return void
	 */
	public function detachRoute(Route $route) {
		$this->router->detachRoute($route);
	}

	/**
	 * Filter routes from the collection.
	 * @param callable $filter Filter that takes the route as argument and returns a boolean where true is it stays, and false removes the route.
	 * @return void
	 */
	public function filterRoutes(callable $filter) {
		$this->router->filterRoutes($filter);
	}

	/**
	 * Clear all routes from the collection.
	 * @return void
	 */
	public function clearRoutes() {
		$this->router->clearRoutes();
	}

	/**
	 * The error to write.
	 * @param \Quark\Protocols\HTTP\IMutableResponse $response
	 * @param string $errorMessage
	 * @return Response
	 */
	private function _writeError(IMutableResponse $response, $errorMessage){
		$document = Document::createInstance(new BasicLayout());
		$document->place(new Literal([
			'html' =>
				'<div style="margin:40px auto;max-width:700px;background:#FFEBEE;font-family: Roboto, Noto, Lato, \'Open Sans\', sans-serif;box-shadow:0 2px 5px rgba(0,0,0,0.26)">'.
					'<h1 style="background:#F44336;color: white;padding: 5px 0 5px 14px;margin:0 0 3px 0;">'.
						$response->getStatusCode().': '.$response->getStatusText().
					'</h1>'.
					'<p style="padding: 10px;line-height: 1.4em">'.$errorMessage.'</p>'.
				'</div>'
		]));
		$document->toResponse($response);

		$response->setHeader('Connection', 'close');
		return $response;
	}
}