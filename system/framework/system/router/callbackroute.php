<?php
/**
 * Static Callback Route
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 25, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Router;
use Quark\Protocols\HTTP\Server\IServerResponse;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Callback Route.
 *
 * This implementation of the route interface provides you with the means to define a couple of url
 * parameters or whole predefined urls, and let it load a specified class/execute a specific action.
 */
class CallbackRoute implements Route {
	/**
	 * @var string Base URI.
	 */
	protected $base = '';

	/**
	 * @var string|callable
	 */
	protected $routable;

	/**
	 * @var callable
	 */
	protected $route;

	/**
	 * Creates a callback route.
	 * @param string|callable $routable Callback that checks whether the given request is routable or the exact *path* of the url to route.
	 * @param callable $route The callback to call when $routable is called.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function __construct($routable, $route){
		if(!empty($routable) && (is_string($routable) || is_callable($routable)))
			$this->routable = is_string($routable) ? parse_url($routable) : $routable;
		else throw new InvalidArgumentTypeException('routable', 'string|callable', $routable);
		if(is_callable($route))
			$this->route = $route;
		else throw new InvalidArgumentTypeException('route', 'callable', $route);
	}

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
		$this->base = $url;
	}

	/**
	 * Checks if this route can route the given request.
	 * @param IRoutableRequest $request
	 * @return bool
	 */
	public function routable(IRoutableRequest $request){
		if(is_array($this->routable)){
			$parsed = parse_url($request->getPath());
			if(strcasecmp($parsed['path'], $this->routable['path']) != 0)
				return false;
			if(isset($this->routable['query'])){
				parse_str($this->routable['query'], $qrs);
				parse_str($parsed['query'], $qrt);
				foreach($qrs as $key => $value){
					if(isset($qrt[$key])){
						if(!empty($value) && $qrt[$key] != $value)
							return false;
					}else return false;
				}
			}
			return true;
		}else
			return call_user_func($this->routable, $request);
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
		call_user_func($this->route, $request, $response);
	}

	/**
	 * Get the available parameters for the url builder.
	 * @return array Associative array of parameter indexes and descriptions as value.
	 */
	public function parameters() {
		return array();
	}

	/**
	 * Build a URI pointing to this resource/route with the given params.
	 * @param array $params Parameters you want to pass to the receiving end.
	 * @param boolean $optimized Whether or not the builder should try to go for compatible url's (E.g. index.php?name=controller&method=methodname or optimized urls like /controller/methodname/
	 * @throws \RuntimeException When the $routable was set to an callback.
	 * @return string The URI that was given as the $routable parameter.
	 */
	public function build(array $params, $optimized = false) {
		if(is_array($this->routable))
			return $this->routable;
		else throw new \RuntimeException('Routes of type CallableRoute cannot build URIs because they do not poses enough information about the routes.');
	}
}
