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
use Quark\Protocols\HTTP\IRequest;
use Quark\Protocols\HTTP\IResponse;
use Quark\Protocols\HTTP\Server\IServerResponse;
use Quark\System\Router\IRoutableRequest;

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
	 * @var Route Route that is used to reach this object.
	 */
	protected $route;
	
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
	 * (Optional) Name of this controller.
	 * @var string
	 */
	protected $name;

	/**
	 * Array of routable methods.
	 * (Optional) When filled this will determine the methods that will be used for routing.
	 * @var array
	 */
	protected $methods;

	/**
	 * @var IRequest|null The Http Request object for the current HTTP request.
	 */
	protected $request;

	/**
	 * @var IResponse|null The Http Response object for the current HTTP response.
	 */
	protected $response;
	
	/**
	 * Construct the Controller.
	 */
	public function __construct($name=null, Route $route=null, View $view=null, Model $model=null){
		$this->_classname = get_called_class();

		if(!is_null($route))
			$this->route = $route;

		if(is_null($view))
			$this->view = $this->findAssociatedClass('View');
		else
			$this->view = $view;

		if(is_null($model))
			$this->model = $this->findAssociatedClass('Model');
		else
			$this->model = $model;

		if(!is_null($this->name))
			$this->name = $name;
	}

	/**
	 * Get the simple class name (Without the FQ Namespace Path and without the word 'controller').
	 * @return string
	 */
	final public function getName(){
		return basename(strtolower($this->_classname), 'controller');
	}

	/**
	 * Get the fully qualified class name of this controller.
	 * @return string
	 */
	final public function getFullyQualifiedClassName(){
		return $this->_classname;
	}

	/**
	 * Get a list of all routable methods.
	 * @return array
	 */
	final public function getRoutableMethods(){
		if(empty($this->methods)){
			// get all methods
			$reflected = new \ReflectionObject($this);
			$methods = $reflected->getMethods();

			// distill name if not given
			if(!is_string($this->name) || empty($this->name)){
				$this->name = str_ireplace('controller', '', $reflected->getShortName());
				if(empty($this->name))
					$this->name = end(explode('\\', $reflected->getNamespaceName()));
			}

			$this->methods = array_filter($methods, function($method){
				/** @var $method \ReflectionMethod */
				return ($method->isPublic() && $method->getDeclaringClass() == $method->getName() && substr($method->name, 0, 1) != '_');
			});
		}
		return $this->methods;
	}

	/**
	 * Set the current request's context objects so you can call any of the controller method's.
	 * @param IRoutableRequest $request
	 * @param IServerResponse $response
	 * @access private
	 */
	public function setContext(IRoutableRequest $request, IServerResponse $response){
		$this->request = $request;
		$this->response = $response;
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
		if($this->route == null)
			$this->route = new Route($this->getName(), $this->getRoutableMethods(), $this);
		return $this->route;
	}

	/**
	 * Create an url or link that will point to the given method name on this controller instance.
	 * @param string $method
	 * @param array $arguments The arguments for the given method.
	 * @return string
	 */
	public function link($method, array $arguments=array()){
		if($this->route == null)
			$this->getRoute();
		return $this->route->build([
			'controller' => $this->getName(), // Not needed for some mvc-like routes but set anyway.
			'method' => $method,
			'arguments' => $arguments
		]);
	}
	
	/**
	 * Tries to find a Model or View with the same base name as this controller in the same namespace as this one..
	 */
	private function findAssociatedClass($suffix){
		$parts = explode('\\', $this->_classname);
		
		// try to distill it from the basic class name
		$className = implode('\\', array_slice($parts, 0, -1)).str_ireplace('Controller', '', end($parts)).$suffix;
		if(class_exists($className, true))
			return new $className();
		
		// try to distill it from the namespace name
		$className = implode('\\', array_slice($parts, 0, -1)).$suffix;
		if(class_exists($className, true))
			return new $className();
		
		// could not find it :(
		throw new \RuntimeException('Unable to find the model and view associated with the controller "'.$parts[count($parts-2)].'\\'.$parts[count($parts-1)].'". Define your own constructor and load the models yourself, adjust your controller names to include the names of your controller or define your controller views and models in teh same namespace. If no model or view are defined for this controller(Which is offcourse discouraged in most situations), you can create your own constructor that initiates your references to null.');
	}
}
