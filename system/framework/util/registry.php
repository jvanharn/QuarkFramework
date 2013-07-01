<?php
/**
 * Registry Utility Class
 * 
 * @package		Quark-Framework
 * @version		$Id: registry.php 69 2013-01-24 15:14:45Z Jeffrey $
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
namespace Quark\Util;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple implementation of a key/value based registry.
 */
class Registry implements \Countable, \IteratorAggregate{
	/**
	 * Hashmap where items are registred.
	 * @var array
	 */
	protected $registry = array();
	
	/**
	 * Register a value in the registry.
	 * @param string $name Name for the item in the registry. (Should by default be a non-empty string)
	 * @param mixed $value Value for the item. (Should by default be any non-empty value)
	 * @param boolean $overwrite Whether or not to overwrite a item with the same name. (Be carefull)
	 * @return boolean Whether or not the item was saved.
	 * @throws \InvalidArgumentException When one of the arguments/parameters is qualified as invalid.
	 */
	public function register($name, $value, $overwrite=false){
		// Check params
		if(!$this->validKey($name))
			throw new \InvalidArgumentException('Param $name was qualified as an invalid key, please check the documentation for more info.');
		else if(!$this->validValue($value))
			throw new \InvalidArgumentException('Param $value was qualified as an invalid value for this registry, please check the documentation for more info.');
		else if(!is_bool($overwrite))
			throw new \InvalidArgumentException('Param $overwrite should be of type "boolean".');
		
		// Check for write protection
		if(!$overwrite && $this->exists($name))
			return false;
		
		// Save the value
		$this->registry[$name] = $value;
		return true;
	}
	
	/**
	 * Unregister an item by $name.
	 * @param string $name The name of the item to delete/remove/unregister.
	 * @return boolean Whether or not the item $name was succesfully unregistered/removed.
	 */
	public function unregister($name){
		if($this->exists($name)){
			unset($this->registry[$name]);
			return true;
		}else return false;
	}
	
	/**
	 * Check if the item with $name exists/is registred.
	 * @param string $name The item name to check.
	 * @return boolean Whether or not the item with $name exists.
	 */
	public function exists($name){
		if($this->validKey($name))
			return isset($this->registry[$name]);
		else return false;
	}
	
	/**
	 * Get the value of the given item.
	 * @param string $name The name of the item.
	 * @return mixed Value associated with $name.
	 * @throws \OutOfBoundsException When the $name given does not exist.
	 * @throws \InvalidArgumentException When the $name given was not of type string or non-empty.
	 */
	public function get($name){
		if($this->validKey($name)){
			if($this->exists($name))
				return $this->registry[$name];
			else throw new \OutOfBoundsException('The param $name given was not an existing registred item.'.$name);
		}else throw new \InvalidArgumentException('Param $name has to be of type "string" and should be non-empty.');
	}
	
	// Protected helper methods (Can be overridden by extending classes)
	protected function validKey($name){
		return (is_string($name) && !empty($name));
	}
	
	protected function validValue($value){
		return (!empty($value));
	}
	
	// Magic Methods
	/**
	 * @see \Quark\Util\Registry::get()
	 */
	public function __get($name){
		return $this->get($name);
	}
	
	/**
	 * @see \Quark\Util\Registry::set()
	 */
	public function __set($name, $value){
		return $this->register($name, $value);
	}
	
	/**
	 * @see \Quark\Util\Registry::exists()
	 */
	public function __isset($name){
		return $this->exists($name);
	}
	
	/**
	 * @see \Quark\Util\Registry::unregister()
	 */
	public function __unset($name){
		return $this->unregister($name);
	}
	
	// Interface Implementations
	/**
	 * Countable implementation.
	 * @return integer
	 * @ignore
	 */
	public function count() {
		return count($this->registry);
	}
	
	/**
	 * IteratorAggregate implementation.
	 * @return \ArrayIterator
	 * @ignore
	 */
	public function getIterator() {
		return new \ArrayIterator($this->registry);
	}
}