<?php
/**
 * Application Traits - Extensions
 * 
 * Basic Application traits that get you started super quickly, by providing you
 * with the bare minimum to initialize each class.
 * 
 * @package		Quark-Framework
 * @version		$Id: extensions.php 69 2013-01-24 15:14:45Z Jeffrey $
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

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Provides a basic implementation for extension loading and management.
 */
trait Extensions {
	/**
	 * Extensions Object
	 * @var \Quark\Extensions\Extensions
	 */
	protected $extensions;
	
	/**
	 * Extensions Constructor
	 */
	protected function initExtensions(){
		// Get the default extension instance/object
		$this->extensions = \Quark\Extensions\Extensions::getInstance();
		
		// Set the default suppliers (INI Data Cache, Disk Extensiondir Parsing)
		$this->extensions->setDefaultSuppliers();
		
		// Use the suppliers to build the list of loadable extensions
		$this->extensions->populate();
		
		// Force cache the current list
		$this->extensions->cache();
		
		// Load the enabled extensions, so they are ready to use
		$this->extensions->loadAll();
	}
	
	/**
	 * Get the current extensions object
	 * @return \Quark\Extensions\Extensions
	 */
	public function getExtensions(){
		return $this->extensions;
	}
}