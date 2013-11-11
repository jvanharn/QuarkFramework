<?php
/**
 * MVC Controller Implementation
 * 
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 11, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\MVC;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * MVC Controller Implementation base class.
 */
abstract class Controller {
	/**
	 * Contains the fully qualified class-name of the current object.
	 * @var string
	 */
	private $_classname;
	
	/**
	 * Contains the Model reference for this controller.
	 * @var \Quark\System\MVC\Model
	 * @access public
	 */
	protected $model;
	
	/**
	 * Contains the View reference for this controller.
	 * @var \Quark\System\MVC\View
	 * @access public
	 */
	protected $view;
	
	/**
	 * Array of routable methods.
	 * (Optional) When filled this will determine the methods that will be used for routing.
	 * @var array
	 */
	protected $routables;
	
	/**
	 * Construct the Controller.
	 */
	public function __construct(View $view=null, Model $model=null){
		$this->_classname = get_called_class();
		if(is_null($view))
			$this->view = $this->findAssociatedClass('View');
		else
			$this->view = $view;
		if(is_null($model))
			$this->model = $this->findAssociatedClass('Model');
		else
			$this->model = $model;
	}
	
	/**
	 * Get the current Route to this controller.
	 * 
	 * By default this will return a MVCRoute with the from controller method,
	 * and if the controller has filled the $routables class variable, it will
	 * use it's contents for the instantiation of the class.
	 * @return \Quark\System\Router\Router
	 */
	public function getRoute(){
		if(!empty($this->routables) && is_array($this->routables))
			return new MVCRoute($this->routables, $this);
		else return MVCRoute::fromController($this);
	}
	
	/**
	 * Tries to find a Model or View with the same base name as this controller in the same namespace as this one..
	 */
	private function findAssociatedClass($suffix){
		$parts = explode('\\', $this->_classname);
		
		// try to distill it from the basic class name
		$classname = implode('\\', array_slice($parts, 0, -1)).str_ireplace('Controller', '', end($parts)).$suffix;
		if(class_exists($classname, true))
			return new $classname();
		
		// try to distill it from the namespace name
		$classname = implode('\\', array_slice($parts, 0, -1)).$suffix;
		if(class_exists($classname, true))
			return new $classname();
		
		// could not find it :(
		throw new \RuntimeException('Unable to find the model and view associated with the controller "'.$parts[count($parts-2)].'\\'.$parts[count($parts-1)].'". Define your own constructor and load the models yourself, adjust your controller names to include the names of your controller or define your controller views and models in teh same namespace. If no model or view are defined for this controller(Which is offcourse discouraged in most situations), you can create your own constructor that initiates your references to null.');
	}
}

class MVCRoute implements \Quark\System\Router\Route {
	/**
	 * Name of the controller to route.
	 * @var string
	 */
	protected $name;
	
	/**
	 * Base of the url, e.g. 'http://www.example.com/subdir/'
	 * @var string
	 */
	protected $base = '';
	
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
	 * @param string $name Name of the controller to route to.
	 * @param array $methods Methods that are routable for this controller.
	 */
	public function __construct($name, array $methods, Controller $object){
		$this->name = (string) $name;
		$this->methods = $methods;
		$this->object = $object;
	}
	
	/**
	 * Create a MVCRouter from a controller object.
	 * @param \Quark\System\MVC\Controller $object Controller to create a route for.
	 * @param string $name Optionally the base name of the controller, which is used for getting the correct MVCRoute to process the request.
	 * @return \Quark\System\MVC\MVCRoute
	 */
	public static function fromController(Controller $object, $name=null){
		// get all methods
		$reflected = new ReflectionObject($object);
		$methods = $reflected->getMethods();
		
		// distill name if not given
		if(!is_string($name) || empty($name)){
			$name = str_ireplace('controller', '', $reflected->getShortName());
			if(empty($name))
				$name = end(explode('\\', $reflected->getNamespaceName()));
		}
		
		// filter methods and return
		return new MVCRoute($name, array_filter($methods, function($method){
			return ($method->isPublic() && $method->getDeclaringClass() == $method->getName() && substr($method->name, 0, 1) != '_');
		}), $object);
	}
	
	/**
	 * Gives the base url of the Application to which this route was bound.
	 * @param string $url URL to the base application.
	 */
	public function setBase($url) {
		$this->base = (string) $url;
	}
	
	/**
	 * Build a URL with this route.
	 * @param array $params First value is the method to call or the "method", the rest are the arguments or the "args index".
	 * @param boolean $optimized Whether or not the builder should try to go for compatibble url's (E.g. index.php?name=controller&method=methodname or optimized urls like /controller/methodname/
	 * @return string The built URL.
	 */
	public function build(array $params, $optimized=false) {
		$reqMethod = '';
		$reqArgs = array();
		
		// determine method
		if(isset($params['method']) || isset($params[0])){
			$index = isset($params['method']) ? 'method' : (isset($params[0]) ? 0 : null);
			if($index !== null && isset($this->methods[$params[$index]]))
				$reqMethod = $params[$index];
			else if(in_array('index', $this->methods))
				$reqMethod = 'index';
			else throw new InvalidArgumentException('No valid method was given! This route does not have an index, so I could not route it to there either.');
		}else if(in_array('index', $this->methods)){
			$reqMethod = 'index';
		}else throw new \InvalidArgumentException('No valid method was given! This route does not have an index, so I could not route it to there either.');
		
		// determine args
		if(isset($params['args']) && is_array($params['args'])){
			$reqArgs = $params['args'];
		}
		
		// build the url
		if($optimized){
			$pathArgs = '';
			$kvArgs = array();
			foreach($reqArgs as $key => $arg){
				if(is_integer($key))
					$pathArgs .= $arg.'/';
				else
					$kvArgs[$key] = $arg;
			}
			return $this->base.$this->name.'/'.($reqMethod == 'index' ? '' : $reqMethod.'/').$pathArgs.(empty($kvArgs)? '' : '?'.http_build_query($kvArgs));
		}else
			return $this->base.'?'.http_build_query(array_merge(array('controller' => $this->name, 'method' => $reqMethod), $reqArgs));
	}
	
	/**
	 * Available (Named) parameters for this controller.
	 * @return array
	 */
	public function parameters() {
		return array(
			'name' => 'The name of the MVC controller to load.',
			'method' => 'Method to call.',
			'args' => 'Arguments for this object'
		);
	}
	
	/**
	 * Check if the given url is routable.
	 * @param \Quark\System\Router\URL $url URL to check.
	 * @return boolean
	 */
	public function routable(\Quark\System\Router\URL $url) {
		$path = $url->pathinfo->path;
		$query = $url->pathinfo->query;
		if(count($path) >= 2 && $path[0] == $this->name && in_array($path[1], $this->methods)){
			return true;
		}else if(isset($query['controller']) && $query['controller'] == $this->name && isset($query['method']) && in_array($query['method'], $this->methods)){
			return true;
		}else return false;
	}
	
	/**
	 * Routes the given URL, and loads the specified resource.
	 * @param \Quark\System\Router\URL $url (Pre-checked) URL to route to the resource.
	 * @return boolean
	 */
	public function route(\Quark\System\Router\URL $url) {
		$path = $url->pathinfo->path;
		$query = $url->pathinfo->query;
		
		if(count($path) >= 2 && $path[0] == $this->name && in_array($path[1], $this->methods)){
			
		}else if(isset($query['controller']) && $query['controller'] == $this->name && isset($query['method']) && in_array($query['method'], $this->methods)){
			
		}else return false;
	}
}
