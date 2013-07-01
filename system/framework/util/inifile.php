<?php
/**
 * INI File parser
 * 
 * @package		Quark-Framework
 * @version		$Id: inifile.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		March 5, 2012
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
namespace Quark\Util;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Trick to allow for propper quotes
define('_QUOTE', '"');

class INIFile implements \Countable, \IteratorAggregate{
	/**
	 * Default section 
	 */
	const DEFAULT_SECTION = 'default';
	
	/**
	 * Whether or not changes have been made
	 * @var boolean
	 */
	private $changed = false;
	
	/**
	 * Path to the current file
	 * @var string
	 */
	private $filename;
	
	/**
	 * Hash table for INI-Values
	 * @var array
	 */
	private $registry = array();
	
	/**
	 * Constructs a new INI File object
	 * @param string $filename The path to the ini file.
	 * @throws \RuntimeException When the INI File was not accessible, readable, whatever.
	 */
	public function __construct($filename){
		// Set the file path
		if(!is_file($filename))	touch($filename);
		if(is_readable($filename)){
			// Set filename
			$this->filename = $filename;
			
			// Read and parse the inifile
			$this->read();
		}else throw new \RuntimeException('The INI file "'.$filename.'" specified, is not readable, or could not be created!');
	}
	
	/**
	 * Reads the INI-File into the class
	 * No need to execute, is already done in the constructor.
	 * @return bool
	 */
	public function read(){
		$this->registry = parse_ini_file($this->filename, true, INI_SCANNER_NORMAL);
		/*$this->registry = parse_ini_file($this->filename, true, INI_SCANNER_RAW);
		if(is_array($this->registry)){
			// Convert all standard types like null and booleans
			foreach($this->registry as $name => $section){
				foreach($section as $property => $value){
					if($value == 'null' || $value == 'nil' || $value == '')
						$this->registry[$name][$property] = null;
					else if($value == 'true' || $value == 'on')
						$this->registry[$name][$property] = true;
					else if($value == 'false' || $value == 'off')
						$this->registry[$name][$property] = false;
					else var_dump($value);
				}
			}
		}else return false;*/
	}
	
	/**
	 * Write the ini file back
	 * @return boolean
	 */
	public function write(){
		if(is_writable($this->filename)){
			return file_put_contents($this->filename, $this->_build(), LOCK_EX);
		}else return false;
	}
	
	/**
	 * (Re)Builds the inifile from an array
	 * @return string 
	 */
	protected function _build(){
		$ini = '; This is a Quark generated INI File! Please do not alter unless you know what you are doing!'.PHP_EOL;
		
		foreach($this->registry as $name => $section){
			$ini .= PHP_EOL.'['.$name.']'.PHP_EOL;
			foreach($section as $property => $value){
				if(is_array($value)){
					foreach($value as $val){
						$ini .= $property.'[] = '.$this->_encode($value).PHP_EOL;
					}
				}else $ini .= $property.' = '.$this->_encode($value).PHP_EOL;
			}
		}
		
		return $ini;
	}
	
	/**
	 * Encode an ini value
	 * @param mixed $value
	 * @return string
	 */
	protected function _encode($value){
		if(is_bool($value))
			return ($value?'true':'false');
		else if(is_numeric($value))
			return (String) $value;
		else if(is_string($value)){
			if(strstr($value, ' ') !== false || strstr($value, "\t") !== false || strstr($value, "\n") !== false || strstr($value, "\r") !== false)
				return '"'.str_replace(array("\n", "\r", "\t", "\""), array('\\n', '\\r', '\\t', '"_QUOTE"'), $value).'"';
			else return $value;
		}else return (String) $value;
	}
	
	// Registry Manipulation Methods
	/**
	 * Check if a property or section exists
	 * @param string $section
	 * @param string $property
	 * @return bool
	 */
	public function exists($section, $property=null){
		if(empty($property)) return isset($this->registry[$section]);
		else return (isset($this->registry[$section]) && isset($this->registry[$section][$property]));
	}
	
	/**
	 * Get a Property's value
	 * @param string $section
	 * @param string $property 
	 * @return mixed|null
	 */
	public function get($section, $property){
		if($this->exists($section, $property)){
			return $this->registry[$section][$property];
		}else{
			\Quark\Error::raiseWarning('User tried to acces non-existend section "'.$section.'" and/or property "'.$property.'" in INI File "'.$this->filename.'".');
			return null;
		}
	}
	
	/**
	 * Set the value for a property
	 * @param string $section
	 * @param string $property
	 * @param mixed $value
	 * @return boolean 
	 */
	public function set($section, $property, $value){
		// Make sure the sections exist
		if($section == self::DEFAULT_SECTION && !is_array(current($this->registry)))
			$this->registry = array(self::DEFAULT_SECTION => array());
		
		if($this->exists($section)){
			if(!is_object($value)) $value = (String) $value;
			$this->registry[$section][$property] = $value;
			return true;
		}else{
			\Quark\Error::raiseWarning('User tried to acces non-existend section "'.$section.'" and/or property "'.$property.'" in INI File "'.$this->filename.'".');
			return false;
		}
	}
	
	/**
	 * Create a section or catagory
	 * @param string $section Section name (Should only contain alpha numeric)
	 * @return boolean True when it was created, false when it already exists
	 */
	public function createSection($section){
		if(isset($this->registry[$section])) return false;
		else{
			$this->registry[$section] = array();
			return true;
		}
	}
	
	/**
	 * Remove a section or catagory
	 * @param string $section
	 * @return void 
	 */
	public function removeSection($section){
		unset($this->registry[$section]);
	}
	
	/**
	 * Remove a property from the ini
	 * @param string $section
	 * @param string $property 
	 * @return void
	 */
	public function remove($section, $property){
		if($this->exists($section, $property))
			unset($this->registry[$section][$property]);
	}
	
	/**
	 * Whether or not the file is empty
	 * @return boolean
	 */
	public function isEmpty(){
		return empty($this->registry);
	}
	
	/**
	 * Whether or not the ini file was changed
	 * @return boolean 
	 */
	public function hasChanged(){
		return $this->changed;
	}
	
	/**
	 * Writing changes back to the INI File, if they were made
	 * @return boolean
	 */
	public function applyChanges(){
		if($this->changed) $this->write();
		return $this->changed;
	}
	
	// Magic methods
	public function __get($name){
		return $this->get(self::DEFAULT_SECTION, $name);
	}
	
	public function __set($name, $value){
		return $this->set(self::DEFAULT_SECTION, $name, $value);
	}
	
	public function __isset($name){
		return $this->exists(self::DEFAULT_SECTION, $name);
	}
	
	public function __unset($name){
		return $this->remove(self::DEFAULT_SECTION, $name);
	}
	
	public function __sleep(){
		if($this->changed) $this->write();
		return array('filename');
	}
	
	public function __wakeup(){
		$this->read();
	}
	
	public function __toString(){
		return $this->_build();
	}
	
	// Implementation of Interfaces
	/**
	 * Countable Implementation
	 * @return int
	 */
	public function count() {
		return count($this->registry, COUNT_RECURSIVE)-count($this->registry);
	}
	
	/**
	 * Implementator of IteratorAggregate
	 * @return ArrayIterator 
	 */
	public function getIterator() {
		return new \ArrayIterator($this->registry);
	}
}