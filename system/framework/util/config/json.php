<?php
/**
 * JSON Configuration file implementation.
 * 
 * @package		Quark-Framework
 * @version		$Id: json.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		24 december 2012
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
 * JSON Configuration file
 */
class JSON implements Config{
	/**
	 * Maximal Access Depth
	 */
	const MAX_DEPTH = 12;
	
	/**
	 * Path to the current config file.
	 * @var string
	 */
	protected $file;

	/**
	 * @var int Mode the file was opened in.
	 */
	protected $mode;
	
	/**
	 * Map of all the key value pairs.
	 * @var array
	 */
	protected $map;
	
	/**
	 * Whether changes were made to the config.
	 * @var array
	 */
	protected $changed = false;
	
	/**
	 * Open new config file.
	 * @param string $file Path to readable config file.
	 * @param int $mode Mode to open the file in.
	 * @throws \RuntimeException When file is unreadable
	 */
	public function __construct($file, $mode=self::MODE_READWRITE) {
		$this->file = $file;
		$this->mode = (int) $mode;

		if(!file_exists($file)){
			if($mode == self::MODE_READONLY)
				throw new \RuntimeException('The config file "'.$file.'" doesn\'t exist and was opened in read-only mode.');
			$this->map = array();
		}else{
			if(!is_readable($file))
				throw new \RuntimeException('The config file "'.$file.'" specified, is not readable.');
			if($mode == self::MODE_READWRITE && !is_writeable($file))
				throw new \RuntimeException('The config file "'.$file.'" specified, is not writable.');

			$this->map = json_decode(file_get_contents($file), true, self::MAX_DEPTH);
			if(!is_array($this->map))
				throw new ConfigFormatException('The json config file could not be properly parse by the php JSON_DECODE function. Please check the file for writing problems.');
		}
	}
	
	/**
	 * Get the type of the specified property.
	 * @param array $property Property path.
	 * @return integer
	 */
	public function type(array $property){
		$prop = $this->get($property);
		if(is_array($prop)){
			// make sure that all keys are integers.
			for (reset($prop); is_int(key($prop)); next($prop));
			return is_null(key($prop))? Config::COLLECTION : Config::DICTIONARY;
		}else return Config::PROPERTY;
	}
	
	/**
	 * Check if properties have changed in this file.
	 * @return boolean
	 */
	public function changed() {
		return $this->changed;
	}
	
	/**
	 * Get the value of the given property.
	 * @param array $property Property path.
	 * @return mixed
	 */
	public function get(array $property) {
		$cnt = count($property);
		if($cnt < 1)
			return $this->map;
		if($cnt > self::MAX_DEPTH)
			throw new \OutOfRangeException('Property path given is longer than '.self::MAX_DEPTH.' elements, which exceeds the set max depth.');

		$cur = $this->map;
		for($i=0; $i<$cnt; $i++){
			if(isset($cur[$property[$i]]))
				$cur = $cur[$property[$i]];
			else throw new \OutOfBoundsException('Key "'.$property[$i].'" could not be found.');
		}
		return $cur;
	}
	
	/**
	 * Check if a property is of a specific type.
	 * @param array $property Property path.
	 * @param integer $type Type of property. (Config::* Constant)
	 * @return boolean
	 */
	public function is(array $property, $type) {
		return $type == $this->type($property);
	}
	
	/**
	 * Set a property value.
	 * @param array $property Property path.
	 * @param mixed $value (New) Property Value.
	 * @param integer $type Type of property. (Config::* Constant)
	 * @return boolean
	 * @throws \UnexpectedValueException When property path is empty.
	 * @throws \OutOfRangeException When property path is larger than the max. allowed depth.
	 */
	public function set(array $property, $value, $type = self::PROPERTY) {
		if($this->mode == self::MODE_READONLY)
			throw new \BadMethodCallException('You cannot set properties on a readonly configuration object.');

		$cnt = count($property);
		if($cnt < 1)
			throw new \UnexpectedValueException('Expected property array to be at least one element long.');
		if($cnt > self::MAX_DEPTH)
			throw new \OutOfRangeException('Property path given is longer than '.self::MAX_DEPTH.' elements, which exceeds the set max depth.');
		
		$this->changed = true;

		if($cnt == 1){
			$this->map[$property[0]] = $value;
			return true;
		}

		$cur =& $this->map;
		for($i=0; $i<$cnt; $i++){
			if($i == $cnt-1)
				$cur[$property[$i]] = $value;
			else if(isset($cur[$property[$i]]))
				$cur =& $cur[$property[$i]];
			else return false;
		}
		return true;
	}

	/**
	 * Remove a property.
	 * @param array $property
	 * @return boolean
	 */
	public function remove(array $property){
		if($this->mode == self::MODE_READONLY)
			throw new \BadMethodCallException('You cannot set properties on a readonly configuration object.');

		$cnt = count($property);
		if($cnt < 1)
			throw new \UnexpectedValueException('Expected property array to be at least one element long.');
		if($cnt > self::MAX_DEPTH)
			throw new \OutOfRangeException('Property path given is longer than '.self::MAX_DEPTH.' elements, which exceeds the set max depth.');

		$this->changed = true;

		if($cnt == 1){
			unset($this->map[$property[0]]);
			return true;
		}

		$cur =& $this->map;
		for($i=0; $i<$cnt; $i++){
			if($i == $cnt-1)
				unset($cur[$property[$i]]);
			else if(isset($cur[$property[$i]]))
				$cur =& $cur[$property[$i]];
			else return false;
		}
		return true;
	}

	/**
	 * Get the keys of a collection or dictionary property.
	 * @param array $property Property path.
	 * @return int|array Int if the value is a collection, an array when it is a dictionary.
	 */
	public function keys(array $property){
		return array_keys($this->get($property));
	}
	
	/**
	 * Check if a property path exists/is valid.
	 * @param array $property Property path.
	 * @return boolean
	 */
	public function valid(array $property) {
		try{
			$this->get($property);
			return true;
		}catch(\OutOfBoundsException $e){
			return false;
		}
	}
	
	/**
	 * Writes the changed property's to the config file.
	 * @return boolean
	 */
	public function write() {
		if($this->mode == self::MODE_READONLY)
			throw new \BadMethodCallException('You cannot write a readonly configuration object to a file.');

		if($this->changed){
			$result = json_encode($this->map);
			if(is_string($result)){
				if(file_put_contents($this->file, $result) === false)
					throw new \RuntimeException('The config file could not be written, because I do not have permission to write on the given location "' . $this->file . '".');
			}else throw new \LogicException('Something went wrong while encoding the properties, could not write the config file, please report.');
			return true;
		}else return false;
	}

	/**
	 * Force the mode the config file was opened in to writable.
	 */
	public function forceWritable(){
		$this->mode = self::MODE_READWRITE;
	}
}