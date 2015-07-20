<?php
/**
 * Handler Registry
 * 
 * @package		Quark-Framework
 * @version		$Id: handlerregistry.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		December 15, 2012
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
namespace Quark\Extensions;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Registry that stores all the available and loaded extensions.
 * 
 * The value for a Registry entry should be formatted in the following way:
 * array(
 *   classname => (string) '\Quark\Full\Path\To\ClassName',
 *   types => (array) Array of strings containing the directory extension names that can be loaded by this handler.
 * )
 */
class HandlerRegistry extends \Quark\Util\Registry {
	
	/**
	 * All extension handlers available
	 * @var Array 
	 */
	protected $registry = array(
		// Driver
		'driver' => array(
			'path' => 'Quark.Extensions.Handlers.Driver',
			'classname' => '\\Quark\\Extensions\\Handlers\\DriverHandler',
			'types' => array('driver', 'engine')
		)
	);
	
	/**
	 * All instantiated handler object references.
	 * @var array
	 */
	protected $objects = array();
	
	/**
	 * Get a handler for a extension typename
	 * (A type is the extension part like "mysql.driver", then drive is the type, mostly the same as the handler name)
	 * @param string $type Type to check for
	 * @return bool|string[] The handler names.
	 */
	public function getByType($type){
		$handlers = array();
		foreach($this->registry as $name => $handler){
			if(in_array($type, $handler['types'])){
				$handlers[] = $name;
			}
		}
		// Not found
		return empty($handlers) ? false : $handlers;
	}
	
	/**
	 * Get the object for a specific handler
	 * @param string $name Handler name to get the object for
	 * @return bool|Handler Boolean on error, object on success
	 */
	public function getObject($name){
		// Check if the handler is registered
		if($this->exists($name)){
			$handler = $this->registry[$name];
			if(!isset($this->objects[$name])){
				// Import if needed
				if(isset($handler['path']))
					\Quark\import($handler['path'], true);
				
				// Check if the class exists
				if(class_exists($handler['classname'], false)){
					$class = $handler['classname'];
					$obj = new $class;
					if($obj instanceof Handler){
						$this->objects[$name] = $obj;
						return $obj;
					}else throw new \RuntimeException('The given Extension Handler class "'.$class.'" doesn\'t implement the "Handler" interface! Thus I had to exit.');
				}else throw new \RuntimeException('The class "'.$handler['classname'].'" for the Extension handler "'.$name.'" did not exist, or could not be loaded!');
			}else return $this->objects[$name];
		}else return false;
	}
	
	// Override the registry helper functions
	/**
	 * (Internal) Check if a value is a valid value for storage in the registry.
	 * @param mixed $value Value to check.
	 * @return boolean
	 */
	protected function validValue($value){
		// Check if the values exist and their types are correct
		if(!(is_array($value) &&
			(isset($value['classname']) && is_string($value['classname'])) &&
			(isset($value['types']) && is_array($value['types']))))
				return false;
		
		// Check if the class exists
		if(!class_exists($value['classname'], false)){
			\Quark\Error\Error::raiseWarning('Handler class must already be loaded before registring!');
			return false;
		}
		
		// Check if the types aren't already used
		foreach($types as $type){
			$check = $this->getByType($type);
			if($check == false){
				\Quark\Error\Error::raiseWarning('Type "'.$type.'" is already registred for handler "'.$check.'". Please either change or turn off the other handler.');
				return false;
			}
		}
		
		// Everything went well.
		return true;
	}
}