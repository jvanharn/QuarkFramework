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
namespace Quark\Util\Quark;

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
	 * Current type bitmask.
	 * @var integer
	 */
	protected $type;
	
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
	}
	
	/**
	 * Get the index $name from the current config file and path.
	 * @param string $name Index name.
	 * @return \Quark\Util\Config\ConfigMapper|mixed Gets another configmapper on collection or properties typed value, otherwise the value.
	 * @throws \OutOfBoundsException When key is invalid.
	 */
	public function get($name) {
		// Build current path
		$path = array_merge($this->base, array($name));
		
		// Check if valid path
		if($this->config->valid($path))
			throw new \OutOfBoundsException('Invalid key given.');
		
		// Check what to return
		return new Property($this->config, $path);
	}
	
	/**
	 * Get the value of the current property.
	 * @return mixed Property value.
	 * @throws \UnderflowException When the element has no value.
	 */
	public function value() {
		if($this->config->is($this->base, Config::VALUE))
			return $this->config->get($this->base);
		else throw new \UnderflowException("Current config element has no Value.");
	}
	
	/**
	 * Check if index name exists.
	 * @param string $name
	 * @return boolean
	 */
	public function has($name){
		if($this->is(Config::COLLECTION) || $this->is(Config::PROPERTIES)){
			$path = array_merge($this->base, array($name));
			return $this->config->valid($path);
		}else return false;
	}
	
	/**
	 * Get the type bitmask of the currently referenced property path.
	 * @return integer
	 */
	public function types(){
		if($this->type === null)
			$this->type = $this->config->types($this->base);
		return $this->type;
	}
	
	/**
	 * Check if the current property path is of the given type.
	 * @param integer $type
	 * @return boolean
	 */
	public function is($type){
		if(is_integer($type))
			return $this->types() & $type;
		else return false;
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
		throw new \RuntimeException("Cannot set values with the ConfigMapper.");
	}
	
	/**
	 * @ignore
	 */
	public function offsetUnset($offset) {
		throw new \RuntimeException("Cannot remove values with the ConfigMapper.");
	}
	
	// Iterator Implementation
	/**
	 * @ignore
	 */
	public function current() {
		return $this->get($this->index);
	}
	
	/**
	 * @ignore
	 */
	public function key() {
		return $this->index;
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
	public function valid() {
		return $this->has($this->index);
	}
	
}