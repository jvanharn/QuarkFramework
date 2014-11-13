<?php
/**
 * Defines the structure of a route
 * 
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		February 10, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Router;
use Quark\Exception;
use Quark\Protocols\HTTP\IMutableResponse;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Route Interface.
 * 
 * Route leading to a resource.
 */
interface Route {
	/**
	 * Get the routes Fully Qualified name (Class name with complete namespace information).
	 * @return string
	 */
	public static function getName();

	/**
	 * Gives the base path of the Application to which this route was bound.
	 * @param string $path Path to the base application.
	 * @return bool Returns false when the path was invalid.
	 */
	public function setBase($path);

	/**
	 * Checks if this route can route the given request.
	 * @param IRoutableRequest $request
	 * @return bool
	 */
	public function routable(IRoutableRequest $request);

	/**
	 * Activate this route and load the applicable resource.
	 *
	 * This function may ONLY be called after positive feedback (e.g. true) from the routable method.
	 * @param IRoutableRequest $request {@see Route::routable()}
	 * @param IMutableResponse $response The object where the response should be written to.
	 * @return mixed|void
	 */
	public function route(IRoutableRequest $request, IMutableResponse $response);
	
	/**
	 * Get the available parameters for the url builder.
	 * @return array Associative array of parameter indexes and descriptions as value.
	 */
	public function parameters();
	
	/**
	 * Build a URI pointing to this resource/route with the given params.
	 * @param array $params Parameters.
	 * @param boolean $optimized Whether or not the builder should try to go for compatible url's (E.g. index.php?name=controller&method=methodname or optimized urls like /controller/methodname/
	 * @return string The URI that leads to the specified location.
	 * @throws RouteBuilderParameterException Exception that is thrown when the method cannot build the URI/URL with the given parameters because they are either incorrectly formatted or are missing essential/non-optional params.
	 */
	public function build(array $params, $optimized=false);
}

/**
 * Class baseRoute
 * @package Quark\System\Router
 */
trait baseRoute {
	/**
	 * Base of the url, e.g. '/subdir/'
	 * @var string
	 */
	protected $base = '/';

	/**
	 * Gives the base path of the Application to which this route was bound.
	 * @param string $path Path to the base application.
	 * @return bool Returns false when the path was invalid.
	 */
	public function setBase($path) {
		if(!is_string($path) || ($pathLength = strlen($path)) < 1)
			return false;

		if($path[0] == '/')
			$path = '/'.$path;

		if($path[$pathLength-1] == '/')
			$this->base = $path;
		else
			$this->base = $path.'/';

		return true;
	}

	/**
	 * Get the routes Fully Qualified name (Class name with complete namespace information).
	 * @return string
	 */
	public static function getName() { return __CLASS__; }
}

/**
 * Class RouteBuilderParameterException
 *
 * Exception that is thrown when the build function cannot build the URI/URL with the given parameters because they are either incorrectly formatted or are missing essential params.
 * Check the {@link Router::parameters()} method before calling build to prevent this in most cases or use this programatically to check if the build method of the given route can build the uri and move to the next one.
 * @package Quark\System\Router
 */
class RouteBuilderParameterException extends Exception { }