<?php
/**
 * Controller Route
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 27, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\MVC;
use Quark\Protocols\HTTP\IMutableResponse;
use Quark\System\Router\baseRoute;
use Quark\System\Router\IRoutableRequest;
use Quark\System\Router\Route as RouteInterface;
use Quark\System\Router\URLPathInfo;
use Quark\Util\Type\HttpException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class Route
 * @package Quark\System\MVC
 */
class Route implements RouteInterface {
	use baseRoute;

	/**
	 * Name of the controller to route.
	 * @var string
	 */
	protected $controller;

	/**
	 * Available methods.
	 * @var array
	 */
	protected $methods = array();

	/**
	 * The object to route the request to.
	 * @var Controller
	 */
	protected $object;

	/**
	 * @param string $controller Name of the controller to route to.
	 * @param array $methods Methods that are routable for this controller.
	 * @param Controller $object The object of the controller to route to.
	 */
	public function __construct($controller, array $methods, Controller $object){
		$this->controller = (string) $controller;
		$this->methods = $methods;
		$this->object = $object;
	}

	/**
	 * Checks if this route can route the given request.
	 * @param IRoutableRequest $request
	 * @return bool
	 */
	public function routable(IRoutableRequest $request){
		Route::parsePath($this->base, $request, $controller, $method, $arguments);
		return (strcasecmp($controller, $this->controller) === 0);
	}

	/**
	 * Activate this route and load the applicable resource.
	 *
	 * This function may ONLY be called after positive feedback (e.g. true) from the routable method.
	 * @param IRoutableRequest $request {@see Route::routable()}
	 * @param IMutableResponse $response The object where the response should be written to.
	 * @return void
	 */
	public function route(IRoutableRequest $request, IMutableResponse $response){
		Route::parsePath($this->base, $request, $controller, $method, $arguments);
		if(strcasecmp($controller, $this->controller) === 0){
			try {
				\call_user_func_array([$this->object, $method], $arguments);
			}catch(HttpException $e){
                throw $e; // Rethrow HttpExceptions -- let any HttpException-type exceptions through
            }catch(\Exception $e){
				throw new HttpException(500, $e->getMessage()); // ..  and catch the rest as http 500
			}
		}
	}

	/**
	 * Available (Named) parameters for this controller.
	 * @return array
	 */
	public function parameters() {
		return array(
			'method' => 'Controller method to call.',
			'arguments' => 'Arguments for this method.'
		);
	}

	/**
	 * Build an URL with this route.
	 * @param array $params First value is the method to call or the "method", the rest are the arguments or the "args index".
	 * @param boolean $optimized Whether or not the builder should try to go for compatibble url's (E.g. index.php?name=controller&method=methodname or optimized urls like /controller/methodname/
	 * @throws \InvalidArgumentException
	 * @return string The built URL.
	 */
	public function build(array $params, $optimized=false) {
		$pathParams = '';
		$arguments = array();
		if(!empty($params['arguments']) && is_array($params['arguments'])){
			foreach($params['arguments'] as $key => $value){
				if(is_integer($key))
					$pathParams .= '/'.$value;
				else
					$arguments[$key] = $value;
			}
		}

		$path = new URLPathInfo(
			$this->controller.'/'.(empty($params['method']) ? '' : $params['method']).$pathParams,
			$arguments
		);
		return $path->export();
	}

	/**
	 * Parses an path into the different components for a mvc setup.
	 * @param string $base Application base path.
	 * @param IRoutableRequest $request
	 * @param string $controller (OUT) Will be filled with the requested controller name.
	 * @param string $method (OUT) Will be filled with the requested controller method name.
	 * @param array $arguments (OUT) Arguments for the requested method.
	 * @return void
	 */
	public static function parsePath($base, IRoutableRequest $request, &$controller, &$method, &$arguments){
		$path = $request->getPathObject();
		$path->removeBasePath($base);
		$controller = isset($path->path[0]) ? $path->path[0] : $path->query['controller'];
		$method = isset($path->path[1]) ? $path->path[1] : 'index';
		$arguments = array_merge(array_slice($path->path, 2), $path->query);
	}
}

