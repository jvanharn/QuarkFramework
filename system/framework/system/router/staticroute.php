<?php
/**
 * Static Resource Route
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 11, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Router;
use Quark\Protocols\HTTP\Server\IServerResponse;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Static Route.
 * 
 * The simplest type of route. This default implementation of the route interface helps you load static resources on the
 * server in scenario's where this is not handled by the httpd. You provide it the local base path and the virtual base
 * path and it will automatically serve those files. It is not susceptible to the various ../ attacks.
 *
 * Note 1: No guarantees can be made about UTF-X compliance, it compares everything binary so if you use a file system that supports it, it will probably also work here.
 * Note 2:
 */
class StaticRoute implements Route {
	use baseRoute;

	/**
	 * @var string Local filesystem path.
	 */
	protected $localPath;

	/**
	 * @var string Virtual server path (Relative to $base).
	 */
	protected $virtualPath;

	/**
	 * Creates a static route.
	 * @param string $localPath Local absolute path on the file system. (MUST be a directory) (Beware that this path MUST exist)
	 * @param string $virtualPath The virtual path from where these files will be made available.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException Thrown when $virtualPath is of an invalid type. (E.g. not a string)
	 * @throws \InvalidArgumentException Thrown when $localPath is not a valid string or points to an non-existing path.
	 */
	public function __construct($localPath, $virtualPath){
		if(!empty($localPath) && is_string($localPath) && is_dir($localPath) && ($real = realpath($localPath)) !== false)
			$this->localPath = $real;
		else throw new \InvalidArgumentException('Parameter $localPath should be of type string, be non-empty and contain a path that actually exists.');
		if(!empty($virtualPath) && is_string($virtualPath))
			$this->virtualPath = $virtualPath;
		else throw new InvalidArgumentTypeException('virtualPath', 'string and non-empty', $virtualPath);
	}

	/**
	 * Checks if this route can route the given request.
	 * @param IRoutableRequest $request
	 * @return bool
	 */
	public function routable(IRoutableRequest $request){
		$resource = $request->getPathObject();
		$resource->removeBasePath($this->base);
		return (strcasecmp(substr($resource->path, 0, strlen($this->virtualPath)) , $this->virtualPath) === 0);
	}

	/**
	 * Activate this route and load the applicable resource.
	 *
	 * This function may ONLY be called after positive feedback (e.g. true) from the routable method.
	 * @param IRoutableRequest $request {@see Route::routable()}
	 * @param IServerResponse $response The object where the response should be written to.
	 * @return void
	 */
	public function route(IRoutableRequest $request, IServerResponse $response){
		$resource = $request->getPathObject();
		$resource->removeBasePath($this->base);

		// Check if the file exists on the file system
		if(($real = realpath($this->localPath.'/'.$resource->path)) !== false && is_file($real) && is_readable($real)){
			// Check if the file is inside our target directory $localPath
			if(strncmp($this->localPath, $real, strlen($this->localPath))){
				// Check the meta-type information table and apply the appropriate headers.
				// @todo add the appropriate headers.
				$response->setHeader('content-type', 'text/plain');

				// Respond with the asked file
				$response->setBody(fopen($real, 'rb'));
			}
		}

		// 404 not found
		// @todo
	}

	/**
	 * Get the available parameters for the url builder.
	 * @return array Associative array of parameter indexes and descriptions as value.
	 */
	public function parameters() {
		return array(
			'resource' => 'Required - The resource to load from the server in the form of a path. (e.g. /style/style.css to get the uri of a resource.)'
		);
	}

	/**
	 * Build a URI pointing to this resource/route with the given params.
	 * @param array $params Parameters you want to pass to the receiving end.
	 * @param boolean $optimized Whether or not the builder should try to go for compatible url's (E.g. index.php?name=controller&method=methodname or optimized urls like /controller/methodname/
	 * @throws \InvalidArgumentException
	 * @return string The URI that leads to the specified location.
	 */
	public function build(array $params, $optimized = false) {
		if(empty($params['resource']))
			throw new \InvalidArgumentException('Parameter resource is required to build a URI.');
		$path = new URLPathInfo($this->base.$params['resource']);
		return $path->export();
	}
}
