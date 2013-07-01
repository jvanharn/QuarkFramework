<?php
/**
 * Basic MVC Application
 * 
 * @package		Quark-Framework
 * @version		$Id: mvc.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		27 december 2012
 * @copyright	Copyright (C) 2011-2012 Jeffrey van Harn. All rights reserved.
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
namespace Quark\System\Application;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Basic Application Implementation using the MVC pattern.
 * 
 * This is the most basic MVC implementation there is, it sports basic database 
 * initialisation and router setup for routes to their Controllers. Can be
 * easily extended using one of the many available application base traits.
 */
abstract class MVC extends Application{
	use \Quark\Util\baseSingleton,
		\Quark\System\Application\Base\Document,
		\Quark\System\Application\Base\Extensions,
		\Quark\System\Application\Base\Database,
		\Quark\System\Application\Base\Router;
	
	protected function initApplication(\Quark\Document\Layout\Layout $layout, $databaseDriver, array $databaseOptions){
		// Initiate the document model
		$this->initDocumentWithLayout($layout);
		
		// Initiate the extensions subsystems
		$this->initExtensions();
		
		// Initiate the database
		$this->initDatabaseWithDriverName($databaseDriver, $databaseOptions, $this->extensions);
		
		// Initiate the router
		$this->initRouter();
		
		// Find all controllers
		$controllers = array_filter(get_declared_classes(), function($classname) {
			return is_subclass_of($classname, '\Quark\System\MVC\Controller');
		});
		
		// Generate routes for the controllers
		
	}
}