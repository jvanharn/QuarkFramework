<?php
/**
 * Abstract Base class for the Application
 * 
 * @package		Quark-Framework
 * @version		$Id: application.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		December 25, 2011
 * @copyright	Copyright (C) 2011-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2011-2013 Jeffrey van Harn
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

// Import required classes
\Quark\import('Framework.Util.Singleton');

/**
 * Basic Quark Application class
 * 
 * This is the bare minimum that is required for a Quark application to be
 * implemented. The display function get's called after initialization, and you
 * should place you output rendering logics there. Initialisation of the
 * framework should happen in the application constructor.
 */
abstract class Application implements \Quark\Util\Singleton{
	/**
	 * Initiates the Application
	 */
	abstract protected function __construct();
	
	/**
	 * Should display the application
	 */
	abstract public function display();
	
	/**
	 * Stop the Application (Application Exit Point)
	 * 
	 * This makes sure the application stop's directly, and can notify display classes to stop output.
	 */
	public function stop($message, $cancel_output=true){
		define('APP_EXIT', true);
		if($cancel_output) define('PREVENT_OUTPUT', true);
		exit($message);
	}
}