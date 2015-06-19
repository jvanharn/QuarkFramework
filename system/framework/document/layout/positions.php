<?php
/**
 * Layout Position Manager Class
 * 
 * @package		Quark-Framework
 * @version		$Id: positions.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		7 december 2012
 * @copyright	Copyright (C) 2012 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012 Jeffrey van Harn
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
namespace Quark\Document\Layout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Layout Position Manager
 * 
 * Used to manage positions within the layout class, and provides a standardized
 * way to share layout positions between different layout implementations and 
 * reference between multiple aliases of the same positions. This class aims to
 * maximize Layout class position interoperability while providing a
 * consistent api for Application and Extensions developers alike.
 */
class Positions implements \IteratorAggregate {
	/**
	 * @var string The default position.
     * @access public
	 */
	protected $default = 'MAIN_CONTENT';
	
	/**
	 * @var array Position references hashmap with title and description
	 */
	protected $positions = array();
	
	/**
	 * @var array Position aliases hashmap
	 */
	protected $aliases = array();
	
	/**
	 * Construct a new layout position manager.
	 * 
	 * The initial position array and aliases should at least contain the MAIN_CONTENT reference.
	 * @param array $positions Array with initial positions in the format 'REFERENCE_NAME' => ['title', 'some description']
	 * @param array $aliases Array with initial aliases in the format 'ALIAS_NAME' => 'REFERENCE_NAME'
	 */
	public function __construct($positions, $aliases){
		$this->positions = $positions;
		$this->aliases = $aliases;
		
		// Check if the minimal default references are available
		if(!$this->exists('MAIN_CONTENT'))
			throw new \DomainException('The given position and aliases did not contain the minimally required position references! These are: "MAIN_CONTENT".');
	}
	
	/**
	 * Add a available layout position to the list
	 * @param string $reference Internal position reference name.
	 * @param string $name Position title in human readable format.
	 * @param string $description Short description about the position.
	 */
	public function add($reference, $name, $description){
		$this->positions[$reference] = [$name, $description];
	}
	
	/**
	 * Remove a position reference (Alias or Position)
	 * @param string $reference Position reference name
	 * @return boolean
	 */
	public function remove($reference){
		if(isset($this->positions[$reference])){
			unset($this->positions[$reference]);
			return true;
		}else if(isset($this->aliases[$reference])){
			unset($this->aliases[$reference]);
		}else return false;
	}
	
	/**
	 * Make an alias for a position reference
	 * @param string $alias New alias reference name
	 * @param string $reference Real position name
	 * @return boolean
	 */
	public function alias($alias, $reference){
		if(is_string($alias) && is_string($reference) && $this->exists($reference, false)){
			$this->aliases[$alias] = $reference;
			return true;
		}else return false;
	}
	
	/**
	 * Check whether a position reference name is valid/exists
	 * @param string $reference Position name
	 * @param boolean $alias Whether or not to also check for aliasses by that name
	 * @return boolean
	 */
	public function exists($reference, $alias=true){
		return (isset($this->positions[$reference]) || ($alias && isset($this->aliases[$reference])));
	}
	
	/**
	 * Resolve position or alias to the proper position reference name
	 * @param string $reference
	 * @return false|string Returns false on unresolvable alias or position or the resolved position on success.
	 */
	public function resolve($reference){
		if(isset($this->positions[$reference]))
			return $reference;
		else if(isset($this->aliases[$reference]))
			return $this->aliases[$reference];
		else return false;
	}
	
	
	/**
	 * Get an array of unique positions, and their full names(Without aliasses
	 * @return array Array with internal names as keys and full names and descriptions as array values.
	 */
	public function getPositions(){
		return $this->positions;
	}
	
	/**
	 * Get an array of all aliases and their referenced keys
	 * @return array Alias keys as name
	 */
	public function getAliases(){
		return $this->aliases;
	}
	
	/**
	 * Get an array of all internal reference places that are available for usage
	 * @return array Number indexed array of all available indexes
	 */
	public function getReferences(){
		return array_merge(array_keys($this->positions), array_keys($this->aliases));
	}
	
	/**
	 * Makes the default variable accessible.
	 * @param string $name Variable name.
	 * @return string
	 */
	public function __get($name){
		if($name == 'default') return $this->default;
		else throw new \RuntimeException('Inaccessible or non-existent property accessed.');
	}

    /**
	 * Makes the default variable accessible.
	 * @param string $name Variable name.
     * @param string $value Variable value.
	 */
	public function __set($name, $value){
		if($name == 'default'){
            if(!$this->exists($value))
                throw new \InvalidArgumentException('The default position must already exist, if you want to set it as the default.');
            $this->default = $value;
        }else throw new \RuntimeException('Inaccessible or non-existent property accessed.');
	}
	
	/**
	 * Traversable implementation
	 * @return \ArrayIterator Iterator for the positions registered
	 * @ignore
	 */
	public function getIterator() {
		return new \ArrayIterator($this->positions);
	}
}