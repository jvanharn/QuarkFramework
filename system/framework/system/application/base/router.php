<?php
/**
 * Application Traits - Routing
 * 
 * Basic Application traits that get you started super quickly, by providing you
 * with the bare minimum to initialize each class.
 * 
 * @package		Quark-Framework
 * @version		$Id: router.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		27 december 2012
 * @copyright	Copyright (C) 2012-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012-2013 Jeffrey van Harn
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
namespace Quark\System\Application\Base;

// Import Router
use \Quark\System\Router\Router as RouterInitiatable;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Provides the basic Document and Layout initialization
 */
trait Router {
	/**
	 * Router Object.
	 * @var \Quark\System\Router\Router
	 */
	protected $router;
	
	/**
	 * Initiate the router with no default routes.
	 */
	protected function initRouter(array $routes=array()){
		$this->router = RouterInitiatable::createInstance(RouterInitiatable::DEFAULT_NAME, $routes);
	}
	
	/**
	 * Get the current application's router object.
	 * @return \Quark\System\Router\Router
	 */
	public function getRouter(){
		return $this->router;
	}
}