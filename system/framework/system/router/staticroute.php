<?php
/**
 * Static Resource Route
 * 
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 11, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Router;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Static Route.
 * 
 * The simplest type of route. This default implementation of the route
 * interface provides you with the means to very simply define a couple of url
 * parameters or whole predefined urls, and let it load a specified class/execute a specific action.
 */
class StaticRoute implements Route {
	/**
	 * Get the routes Fully Qualified name (Class name with complete namespace information).
	 * @return string
	 */
	public static function getName() { return __CLASS__; }

	/**
	 * Gives the base url of the Application to which this route was bound.
	 * @param string $url URL to the base application.
	 */
	public function setBase($url) {
		// TODO: implement
	}

	/**
	 * Check if the URL parts given are routable.
	 * @param \Quark\System\Router\URL $url
	 * @return bool
	 */
	public function routable(URL $url) {
		// TODO: implement
	}

	/**
	 * Activate this route and load the applicable resource.
	 *
	 * This function may ONLY be called after positive feedback (e.g. true) from the routable method.
	 * @param \Quark\System\Router\URL $url {@see Route::routable()}
	 */
	public function route(URL $url) {
		// TODO: implement
	}

	/**
	 * Get the available parameters for the url builder.
	 * @return array Associative array of parameter indexes and descriptions as value.
	 */
	public function parameters() {
		// TODO: implement
	}

	/**
	 * Build a URI pointing to this resource/route with the given params.
	 * @param array $params Parameters you want to pass to the receiving end.
	 * @param boolean $optimized Whether or not the builder should try to go for compatible url's (E.g. index.php?name=controller&method=methodname or optimized urls like /controller/methodname/
	 * @return string The URI that leads to the specified location.
	 */
	public function build(array $params, $optimized = false) {
		// TODO: implement
	}
}
