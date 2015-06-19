<?php
/**
 * Routes all Requests
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 2, 2011
 * @copyright	Copyright (C) 2011-2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Router;
use Quark\Loader;
use Quark\Protocols\HTTP\IMutableResponse;
use Quark\Protocols\HTTP\IRequest;
use Quark\Protocols\HTTP\IResponse;
use Quark\Protocols\HTTP\Request;
use Quark\Protocols\HTTP\Server\IServerResponse;
use Quark\Util\Multiton;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Router Class
 *
 * Helps classes route the incoming requests to the appropriate actions and vice versa.
 */
class Router implements RouteCollection, \IteratorAggregate, Multiton {
	/**
	 * Registry of instances of this class.
	 * @var array
	 */
	private static $_instances = array();
	
	/**
	 * Registered routes for this router.
	 * @var \SplStack<Route>
	 */
	protected $routes;

	/**
	 * @var string The application's base path relative to the host.
	 */
	protected $base = '/';

	/**
	 * @var string The application's base host, port and protocol URL; everything but the path, query and hash (E.g. http://www.example.com or https://example.com:443)
	 */
	protected $site;

	/**
	 * @param array $routes Array of routes.
	 * @param string $site The application's base host, port and protocol URL.
	 * @param string $base The application's base path relative to the host.
	 */
	protected function __construct(array $routes, $site=null, $base=null){
		$this->clearRoutes();
		foreach($routes as $route)
			$this->attachRoute($route);

		if(!empty($site)) $this->setSite($site);
		if(!empty($base)) $this->setBasePath($base);
	}

	/**
	 * Set the application's base host, port and protocol URL.
	 * @param string $url So everything but the path, query and hash (E.g. http://www.example.com or https://example.com:443)
	 * @throws \InvalidArgumentException When url is improperly formatted.
	 */
	public function setSite($url){
		if(is_string($url) && \Quark\Filter\validate_string($url, array('url' => null)))
			$this->site = rtrim($url, '/');
		else throw new \InvalidArgumentException('Invalid URL given.');
	}

	/**
	 * Set the application's base path, relative to the domain/host.
	 * @param string $path The application's base path relative to the host. (e.g. /Quark/)
	 * @throws \InvalidArgumentException
	 */
	public function setBasePath($path){
		if(is_string($path))
			$this->base = '/'.trim($path, '/').'/';
		else throw new \InvalidArgumentException('Invalid path given.');
	}

	/**
	 * Checks all the Route's and tries to load the requested resource.
	 *
	 * If you have an IRequest implementation that does not implement IRoutableRequest, you can convert it in the following way:
	 * 	if(!($request instanceof RoutableRequest))
	 * 		$request = RoutableRequest::fromRequest($request);
	 * @param IRoutableRequest $request Request to route, or null to use the current.
	 * @param IMutableResponse $response The response the route can write it's response to/into or null to create a new one using the request.
	 * @return bool Whether or not the request was successfully routed.
	 */
	public function route(IRoutableRequest $request=null, IMutableResponse $response=null){
		if(is_null($response))
			$response = $request->createResponse();

		// Find a suitable route
		/** @var $route Route */
		foreach($this->routes as $route){
			if($route->routable($request) && ($return = $route->route($request, $response)) !== false)
				return $return;
		}
		return false;
	}

	/**
	 * Checks all the Route's and returns the first route that can route the given request.
	 * @param \Quark\System\Router\IRoutableRequest $request Request to route, or null to use the current.
	 * @return Route|null Route that can route the given request or null.
	 */
	public function findRoute(IRoutableRequest $request=null){
		// find a suitable route
		/** @var $route Route */
		foreach($this->routes as $route){
			if($route->routable($request))
				return $route;
		}
		return null;
	}

	/**
	 * Build a URI for the given route.
	 * @param string $type Type of route this has URL has to point to.
	 * @param array $params Any parameters for this route.
	 * @return string|bool The resulting URI or false if it failed to build with the given parameters.
	 */
	public function build($type, array $params){
		/** @var $route Route */
		foreach($this->routes as $route){
			if($route instanceof $type)
				return $route->build($params);
		}
		return false;
	}

	/**
	 * Build a URI for the given route.
	 * @param \Quark\System\Router\Route $route Route to build the URI for.
	 * @param array $params
	 * @param bool $useQuery Whether or not to force Query String usage instead of possible url rewriting.
	 * @return string|bool The resulting URI or false if it failed to build with the given parameters.
	 */
	public function buildWithRoute(Route $route, array $params, $useQuery=false){
		return $route->build($params, !$useQuery);
	}
	
	/**
	 * Attach a route to this router, so this router is aware of the given route.
	 *
	 * Also updates the route's base!
	 * @param \Quark\System\Router\Route $route
	 */
	public function attachRoute(Route $route) {
		$this->routes->push($route);
		$route->setBase($this->base);
	}
	
	/**
	 * Detaches all currently known routes from this router.
	 */
	public function clearRoutes() {
		$this->routes = new \SplStack();
	}
	
	/**
	 * Detach the given route from this router.
	 * @param \Quark\System\Router\Route $route Filter that takes the route as argument and returns a boolean where true is it stays, and false removes the route.
	 */
	public function detachRoute(Route $route) {
		foreach($this->routes as $index => $current){
			if($current == $route)
				unset($this->routes[$index]);
		}
	}
	
	/**
	 * Remove certain routes from the router by filtering them with a callback.
	 * @param callable $filter Function that receives a route and
	 */
	public function filterRoutes(callable $filter) {
		foreach($this->routes as $index => $route){
			if(!$filter($route))
				unset($this->routes[$index]);
		}
	}

	/**
	 * Get a instance of the router class.
	 * @param string $name Name of the router instance.
	 * @throws \OutOfBoundsException
	 * @return \Quark\System\Router\Router
	 */
	public static function getInstance($name=self::DEFAULT_NAME){
		// Check instance name and return
		if(!isset(self::$_instances[$name]))
			throw new \OutOfBoundsException('Named router instance "'.$name.'" could not be found for "'.get_called_class().'"".');
		
		return self::$_instances[$name];
	}
	
	/**
	 * Check if there is an instance with the given name.
	 * @param string $name Instance name.
	 * @return boolean
	 */
	public static function hasInstance($name=self::DEFAULT_NAME){
		return isset(self::$_instances[$name]);
	}

	/**
	 * Create a new instance of this class.
	 * @param string $name Instance name.
	 * @param array $routes
	 * @return Router
	 */
	public static function createInstance($name=self::DEFAULT_NAME, array $routes=array()){
		self::$_instances[$name] = new Router($routes);
		return self::$_instances[$name];
	}
	
	/**
	 * @access private
	 * @return \Iterator
	 */
	public function getIterator() {
		return $this->routes;
	}
}

/**
 * Shortcut to the Router's buildURI method
 * @see Router::buildURI
 */
function _uri($component, $params=array(), $secure=false){
	$app = Loader::getApplication();
	if(method_exists($app, 'getRouter'))
		return $app->getRouter()->buildURI($component, $params, $secure);
	else throw new \RuntimeException('Application does not have a getRouter method, so the _uri method cannot be used in this application.');
}