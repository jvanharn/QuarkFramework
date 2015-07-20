<?php
/**
 * Configuration Object DOM Access
 * 
 * @package		Quark-Framework
 * @version		$Id: property.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		25 december 2012
 * @copyright	Copyright (C) 2011-2012 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2011-2012 Jeffrey van Harn
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
namespace Quark\Util\Config;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Assoc DOM Access for Configuration objects.
 */
class Property implements \ArrayAccess, \Iterator {
	/**
	 * Stores the wrapped Config object.
	 * @var \Quark\Util\Config\Config
	 */
	protected $config;
	
	/**
	 * Base property path.
	 * @var array
	 */
	protected $base;
	
	/**
	 * Current type.
	 * @var integer
	 */
	protected $type;
	
	/**
	 * (Cached) Result of Config->keys().
	 * Contains either the max index value, or the actual keys.
	 * @var integer|array|null
	 */
	protected $keys;

	/**
	 * Iterator Index
	 * @var integer
	 */
	protected $index = 0;
	
	/**
	 * @param \Quark\Util\Config\Config $config 
	 * @param array $base 
	 */
	public function __construct(Config $config, array $base=null){
		$this->config = $config;
		if($base != null && is_array($base))
			$this->base = $base;
		else{
			$this->type = Config::DICTIONARY;
			$this->base = array();
		}
	}
	
	/**
	 * Get the index $name from the current config file and path.
	 * @param string $name Index name.
	 * @return \Quark\Util\Config\Property Gets another property on collection or properties typed value, otherwise the value.
	 * @throws \OutOfBoundsException When key is invalid.
	 */
	public function get($name) {
		// Build current path
		$path = array_merge($this->base, array($name));

		// Check if valid path
		if(!$this->config->valid($path))
			throw new \OutOfBoundsException('Invalid key given ('.implode($path, ', ').').');
		
		// Check what to return
		return new Property($this->config, $path);
	}

	/**
	 * Set the index $name in the current config file and path.
	 * @param string $name Index name.
	 * @param mixed $value
	 * @param int $type
	 * @return boolean
	 */
	public function set($name, $value, $type=Config::PROPERTY) {
		// Build current path
		$path = array_merge($this->base, array($name));

		// Check what to return
		return $this->config->set($path, $value, $type);
	}

	/**
	 * Remove the index $name from the current config file and path.
	 * @param string $name Index name.
	 * @return boolean
	 * @throws \OutOfBoundsException When key is invalid.
	 */
	public function remove($name) {
		// Build current path
		$path = array_merge($this->base, array($name));

		// Check what to return
		return $this->config->remove($path);
	}
	
	/**
	 * Get the value of the current property.
	 * @return mixed Property value.
	 * @throws \UnderflowException When the element has no value, because it isn't a property.
	 */
	public function value() {
		/*if($this->config->is($this->base, Config::PROPERTY))
			return $this->config->get($this->base);
		else throw new \UnderflowException("Current config element has no Value.");*/
		return $this->config->get($this->base);
	}
	
	/**
	 * Check if index name exists.
	 * @param string $name
	 * @return boolean
	 */
	public function has($name){
		if($this->is(Config::COLLECTION) || $this->is(Config::DICTIONARY))
			return $this->config->valid(array_merge($this->base, array($name)));
		else return false;
	}
	
	/**
	 * Get the type of the currently referenced property path.
	 * @return integer
	 */
	public function type(){
		if($this->type === null)
			$this->type = $this->config->type($this->base);
		return $this->type;
	}
	
	/**
	 * Check if the current property path is of the given type.
	 * @param integer $type
	 * @return boolean
	 */
	public function is($type){
		if(is_integer($type))
			return $this->type();
		else return false;
	}

	/**
	 * Get the wrapped config object.
	 * @return Config
	 */
	public function getConfig(){
		return $this->config;
	}
	
	// Magic Access
	/**
	 * @ignore
	 */
	public function __get($name) {
		return $this->get($name);
	}

	/**
	 * @ignore
	 */
	public function __set($name, $value) {
		return $this->set($name, $value);
	}
	
	/**
	 * @ignore
	 */
	public function __toString() {
		return $this->value();
	}
	
	// ArrayAccess Implementation
	/**
	 * @ignore
	 */
	public function offsetExists($offset) {
		return $this->has($offset);
	}

	/**
	 * @ignore
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * @ignore
	 */
	public function offsetSet($offset, $value) {
		$this->set($offset, $value);
	}
	
	/**
	 * @ignore
	 */
	public function offsetUnset($offset) {
		$this->remove($offset);
	}
	
	// Iterator Implementation
	/**
	 * @ignore
	 */
	public function current() {
		return $this->get($this->key());
	}
	
	/**
	 * @ignore
	 */
	public function key() {
		if(!$this->valid())
			throw new \OutOfRangeException('Current key is out of range.');
		if($this->type() == Config::COLLECTION)
			return $this->index;
		else if($this->type() == Config::DICTIONARY)
			return $this->keys[$this->index];
		else
			throw new \BadMethodCallException('Cannot iterate over a property.');
	}
	
	/**
	 * @ignore
	 */
	public function next() {
		$this->index++;
	}

	/**
	 * @ignore
	 */
	public function rewind() {
		$this->index = 0;
	}
	
	/**
	 * @ignore
	 */
	public function valid(){
		if($this->type() == Config::COLLECTION)
			return $this->index < $this->keys;
		else if($this->type() == Config::DICTIONARY)
			return $this->index < count($this->keys);
		else
			throw new \BadMethodCallException('Cannot iterate over a property.');
	}
}