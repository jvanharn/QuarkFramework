<?php
/**
 * Represents a collection of routes.
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 16, 2014
 * @copyright	Copyright (C) 2011-2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
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
	 * @return void
	 */
	public function attachRoute(Route $route);

	/**
	 * Detach a route from this collection.
	 * @param \Quark\System\Router\Route $route
	 * @return void
	 */
	public function detachRoute(Route $route);

	/**
	 * Filter routes from the collection.
	 * @param callable $filter Filter that takes the route as argument and returns a boolean where true is it stays, and false removes the route.
	 * @return void
	 */
	public function filterRoutes(callable $filter);

	/**
	 * Clear all routes from the collection.
	 * @return void
	 */
	public function clearRoutes();
}