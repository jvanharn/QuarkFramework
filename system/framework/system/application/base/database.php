<?php
/**
 * Application Traits - Databases
 * 
 * Basic Application traits that get you started super quickly, by providing you
 * with the bare minimum to initialize each class.
 * 
 * @package		Quark-Framework
 * @version		$Id: database.php 69 2013-01-24 15:14:45Z Jeffrey $
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
 * Provides the basic implementation for database initialization
 */
trait Database {
	/**
	 * Database Connection
	 * @var \Quark\Database\Database
	 */
	protected $database;
	
	/**
	 * Initialize the database, trying to find the first best database driver.
	 * 
	 * Warning, the behaviour of this function can be completely random if you have multiple database drivers installed/enabled. Please disable any drivers you don't want to have loaded.
	 * @param array $settings Connection settings/parameters.
	 * @param \Quark\Extensions\Extensions $extensions Needed to search the driver in
	 */
	protected function initDatabase(array $settings, \Quark\Extensions\Extensions $extensions){
		// Search for a suitable database driver
		$list = $extensions->getExtensionRegistry()->getByHandler('driver');
		
		// Check each extension
		foreach($list as $name => $driver){
			if($driver['state'] == \Quark\Extensions\Extensions::STATE_ENABLED){
				$this->initDatabaseWithDriverName($name, $settings, $extensions);
				return;
			}
		}
		
		// None was found
		throw new \RuntimeException('Could not find a suitable database driver!');
	}
	
	/**
	 * Initialize the database with the given Database driver extension name.
	 * @param string $name Name of the extension/driver.
	 * @param array $settings Array with connection settings.
	 * @param \Quark\Extensions\Extensions $extensions Current extensions object.
	 * @throws \RuntimeException
	 */
	protected function initDatabaseWithDriverName($name, array $settings, \Quark\Extensions\Extensions $extensions){
		// Find driver
		if($extensions->exists($name)){
			// Check it's state
			$state = $extensions->get($name, 'state');
			if($state != \Quark\Extensions\Extensions::STATE_ENABLED){
				switch($state){
					case \Quark\Extensions\Extensions::STATE_NEW:
					case \Quark\Extensions\Extensions::STATE_DISABLED:
						$extensions->set($name, 'key', \Quark\Extensions\Extensions::STATE_ENABLED);
						break;
					default:
						throw new \RuntimeException('Could not initialize database with driver extension "'.$name.'"; the extension could not be loaded, has no proper handler or has something wrong with it in general.');
				}
			}
			
			// Try to load it
			if(!$extensions->loaded($name))
				$extensions->load($name, true);
			
			// Try to find the driver classname with the driver name string
			$class = $extensions->get($name)['classname'];
			$this->initDatabaseWithDriver(new $class($settings));
		}else
			throw new \RuntimeException('Invalid database driver name given. Driver "'.$name.'" does not exist in the extension registry, so I could not use it to open a connection.');
	}
	
	/**
	 * Initialize the database with the given driver.
	 * @param \Quark\Database\Driver $driver Driver to create the database with.
	 */
	protected function initDatabaseWithDriver(\Quark\Database\Driver $driver){
		$this->database = \Quark\Database\Database::createInstance($driver);
	}
	
	/**
	 * Get the current database object
	 * @return \Quark\Database\Database
	 */
	public function getDatabase(){
		return $this->database;
	}
}