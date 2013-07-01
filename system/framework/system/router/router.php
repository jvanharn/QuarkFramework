<?php
/**
 * Routes all Requests
 * 
 * @package		Quark-Framework
 * @version		$Id: router.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 2, 2011
 * @copyright	Copyright (C) 2011-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2011-2013 Jeffrey van Harn
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License (License.txt) for more details.
 */

// Define Namespace
namespace Quark\System\Router;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Route Collection Interface
 * 
 * This defines that an object can collect Route's.
 */
interface RouteCollection {
	/**
	 * Attach a route to the collection.
	 * @param \Quark\System\Router\Route $route
	 */
	public function attachRoute(Route $route);
	
	/**
	 * Detach a route from this collection.
	 * @param \Quark\System\Router\Route $route
	 */
	public function detachRoute(Route $route);
	
	/**
	 * Filter routes from the collection.
	 * @param callable $filter Filter that takes the route as argument and returns a boolean where true is it stays, and false removes the route.
	 */
	public function filterRoutes(callable $filter);
	
	/**
	 * Clear all routes from the collection.
	 */
	public function clearRoutes();
}

/**
 * Router Class
 */
class Router implements RouteCollection, \IteratorAggregate, \Quark\Util\Multiton {
	/**
	 * Registry of instances of this class.
	 * @var array
	 */
	private static $_instances = array();
	
	/**
	 * Registered routes for this router.
	 * @var \SplStack
	 */
	protected $routes;
	
	/**
	 * @param array $routes Array of routes.
	 */
	protected function __construct(array $routes){
		$this->clearRoutes();
		foreach($routes as $route)
			$this->attachRoute($route);
	}
	
	/**
	 * Checks all the Route's and tries to load the requested resource.
	 * @param \Quark\System\Router\URI $url URL to route, or null to use the current.
	 * @return bool Whether or not the request was succesfully routed.
	 */
	public function route(URL $url=null){
		// find a suitable route
		foreach($this->routes as $route){
			if($route->routable($url))
				return $route->route($url);
		}
		return false;
	}
	
	/**
	 * Build a URI for the given route.
	 * @param \Quark\System\Router\Route $route Route to build the URI for.
	 * @return string|bool The resulting URI or false if it failed to build with the given parameters.
	 */
	public function build(Route $route, array $params){
		return $route->route($params);
	}
	
	/**
	 * Attach a route to this router, so this router is aware of the given route.
	 * @param \Quark\System\Router\Route $route
	 */
	public function attachRoute(Route $route) {
		$this->routes->push($route);
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
	 * @param callable $filter
	 */
	public function filterRoutes(callable $filter) {
		foreach($this->routes as $index => $route){
			if(!$filter($route))
				unset($this->routes[$index]);
		}
	}
	
	/**
	 * Get a instance of the router class.
	 * @return \Quark\System\Router\Router
	 */
	public static function getInstance($name=self::DEFAULT_NAME){
		// Check instance name and return
		if(!isset(self::$_instances[$name]))
			throw new \OutOfBoundsException('Named database instance "'.$name.'" could not be found.');
		
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
	 * @return \Quark\System\Router\Router
	 */
	public static function createInstance($name=self::DEFAULT_NAME, array $routes=array()){
		self::$_instances[$name] = new Router($routes);
		return self::$_instances[$name];
	}
	
	/**
	 * @access private
	 */
	public function getIterator() {
		return new \ArrayIterator($this->routes);
	}
}

class Routerz {
	protected $uri;
	protected $parsed;
	protected $component;
	protected $arguments;
	
	protected function __construct($uri=null){
		// Find (correct) parseble uri
		if(is_null($uri)) $uri = $this->guessCurrentURI();
		
		// Save the uri
		$this->uri = $uri;
		
		// Parse it
		$this->_parse();
	}
	
	protected function guessCurrentURI(){
		// Secure
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
			$uri = 'https://';
		else $uri = 'http://';
		
		// Hostname
		if(isset($_SERVER['SERVER_NAME']))
			$uri .= $_SERVER['SERVER_NAME'];
		else if(isset($_SERVER['HTTP_HOST']))
			$uri .= $_SERVER['HTTP_HOST'];
		
		// Path and Query string
		if(isset($_SERVER['REQUEST_URI']))
			$uri .= '/'.ltrim($_SERVER['REQUEST_URI'], '/');
		
		return $uri;
	}
	
	/**
	 * Whether or not the URI is secured via HTTPS
	 * @return bool
	 */
	public function isSecure(){
		return ($this->parsed['scheme'] == 'https');
	}
	
	/**
	 * Get the URI's parsed version, thus broken down into it's separate parts as given in the RFCs
	 * @return array 
	 */
	public function getParsedURI(){
		return $this->parsed;
	}
	
	/**
	 * Get the URI given to parse
	 * @return string
	 */
	public function getURI(){
		return $this->uri;
	}
	
	/**
	 * Get the URI's component
	 * @return string
	 */
	public function getComponent(){
		return $this->component;
	}
	
	/**
	 * Get the URI's argument list
	 * @return array The argument list
	 */
	public function getArguments(){
		return $this->arguments;
	}
	
	/**
	 * Create a URI from a component and parameters
	 * @param string $component A component to link to
	 * @param array $arguments Params for the component
	 * @return String The completed url
	 */
	public function buildURI($component, $arguments=array(), $secure=false){
		// Check parameters
		if(!is_string($component)) throw new InvalidArgumentException('Argument $string for Router::buildURI should be of type "String" but got "'.gettype($component).'"');
		
		// Protocol
		if($secure)
			$uri = 'https://';
		else $uri = 'http://';
		
		// Domain
		$uri .= $this->parsed['host'].(($this->parsed['port']==80)?'':':'.$this->parsed['port']).'/';
		
		// Component
		if(Application::getInstance()->checkComponent($component)) $uri.= '?component='.$component;//$uri .= $component.'/';
		else raiseError('Unexisting component name used for Router::createURI');
		
		// Params
		/*if(!empty($arguments)){
			$comp = importComponent($name);
			if(is_object($comp) && $comp instanceof customRouterURI)
				$uri .= (String) $comp->buildParams($arguments);
			else{
				foreach($arguments as $param){
					$uri .= $param.'/';
				}
			}
		}*/
		if(!empty($arguments))
			$uri .= '&'.http_build_query($arguments);
		
		return $uri;
	}
	
	/**
	 * Parses a url into the specific url components that can be used inside this framework
	 * @param string $uri URI to parse
	 * @return array Parsed array of components of the url.
	 * @private
	 */
	private function _parse(){
		// Parse
		$url = parse_url($this->uri);
		
		// Save
		$this->parsed = array();
		if(isset($url['scheme'])) $this->parsed['scheme'] = $url['scheme'];
		else $this->parsed['scheme'] = 'http';
		if(isset($url['host'])) $this->parsed['host'] = $url['host'];
		else $this->parsed['host'] = @$_SERVER['SERVER_NAME'];
		if(isset($url['port'])) $this->parsed['port'] = $url['port'];
		else $this->parsed['port'] = 80;
		if(isset($url['path'])) $this->parsed['path'] = $url['path'];
		else $this->parsed['path'] = '/';
		if(isset($url['query'])) parse_str($url['query'], $this->parsed['query']);
		else $this->parsed['query'] = array();
		if(isset($url['fragment'])) $this->parsed['fragment'] = $url['fragment'];
		else $this->parsed['fragment'] = '';
		
		// Components
		if(isset($_GET['component'])){
			// Default Component
			$this->component = $_GET['component'];
		
			// Arguments
			$this->arguments = $this->parsed['query'];
		}else if(empty($this->parsed['path']) || $this->parsed['path'] == '/' || $this->parsed['path'] == '//'){
			// Default Component
			$this->component = 'pages';
		
			// Arguments
			$this->arguments = $this->parsed['query'];
		}else if(!empty($this->parsed['path'])){
			// Component
			$exp = explode('/', $this->parsed['path']);
			$comp = filter_string(array_shift($exp), array('chars' => CONTAINS_ALPHA));
			if(Application::getInstance()->checkComponent($comp))
				$this->component = $comp;
			else $this->component = 'notfound';
			
			// Arguments
			$this->arguments = array_merge($exp, $this->parsed['query']);
		}else{
			// Default Component
			$this->component = 'pages';
		
			// Arguments
			$this->arguments = $this->parsed['query'];
		}
	}
}

/**
 * Shorcut to the Router's buildURI method
 * @see Router::buildURI
 */
function _uri($component, $params=array(), $secure=false){
	$app = Application::getInstance();
	return $app->getRouter()->buildURI($component, $params, $secure);
}