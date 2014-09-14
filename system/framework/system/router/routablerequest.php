<?php
/**
 * Defines a request object that represents all the basic data required to process a request.
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 16, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Router;
use Quark\Protocols\HTTP\IRequest;
use Quark\Protocols\HTTP\Request;
use Quark\Protocols\HTTP\Server\ServerRequest;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Interface IRoutableRequest
 * @package Quark\System\Router
 */
interface IRoutableRequest extends IRequest {
	/**
	 * Get the route used to route the current request.
	 * @return Route|null
	 */
	public function getRoute();

	/**
	 * Check whether this request is (already) routed.
	 */
	public function isRouted();

	/**
	 * Set the route assigned to this request.
	 * @param Route $route
	 * @throws \InvalidArgumentException When route is null.
	 */
	public function setRoute(Route $route);
}

/**
 * Routable Request Class.
 *
 * Represents all relevant data for a (un)routed incoming application request.
 */
class RoutableRequest extends ServerRequest {
	/**
	 * @var Route The route used to
	 */
	protected $route;

	/**
	 * Get the route used to route the current request.
	 * @return Route|null
	 */
	public function getRoute(){
		return $this->route;
	}

	/**
	 * Check whether this request is (already) routed.
	 */
	public function isRouted(){
		return !empty($this->route);
	}

	/**
	 * Set the route assigned to this request.
	 * @param Route $route
	 * @throws \InvalidArgumentException When route is null.
	 */
	public function setRoute(Route $route){
		if(!empty($route))
			$this->route = $route;
		else throw new \InvalidArgumentException('Argument route should be non-null.');
	}

	/*
	 * Creates an RoutableRequest from a Request object.
	 * @param Request $request
	 * @return RoutableRequest
	 */
	/*public static function fromRequest(Request $request){
		$routable = new RoutableRequest($request->url, $request->method);

		$routable->startLine = $request->startLine;
		$routable->headers = $request->headers;
		$routable->body = $request->body;
		$routable->version = $request->version;

		return $routable;
	}*/
}