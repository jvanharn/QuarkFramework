<?php
/**
 * INI Configuration file implementation.
 * 
 * @package		Quark-Framework
 * @version		$Id: ini.php 69 2013-01-24 15:14:45Z Jeffrey $
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
 * INI-Configuration File Class
 * @todo Implement levelled property reading and writing using dots.
 */
class INI implements Config {
	/**
	 * Maximal access Depth
	 */
	const MAX_DEPTH = 2;
	
	/**
	 * Path to the current config file.
	 * @var string
	 */
	protected $file;

	/**
	 * @var int File read mode.
	 */
	protected $mode;
	
	/**
	 * Map of all the key value pairs.
	 * @var array
	 */
	protected $map;
	
	/**
	 * Changes made to the config.
	 * @var array
	 */
	protected $changes = array();
	
	/**
	 * Open the given file path.
	 * @param string $file Path to readable config file.
	 * @param int $mode Mode to open the file in.
	 * @throws \RuntimeException
	 */
	public function __construct($file, $mode=self::MODE_READWRITE) {
		//@todo implement the File-Modes (see JSON for a working implementation)
		if(is_readable($file)){
			$this->file = $file;
			$this->map = parse_ini_file($this->file, true, INI_SCANNER_RAW);
			
			// Convert all standard types like null and booleans
			foreach($this->map as $name => $section){
				foreach($section as $property => $value){
					if($value == 'null' || $value == 'nil' || $value == '')
						$this->map[$name][$property] = null;
					else if($value == 'true' || $value == 'on')
						$this->map[$name][$property] = true;
					else if($value == 'false' || $value == 'off')
						$this->map[$name][$property] = false;
				}
			}
		}else throw new \RuntimeException('The config file "'.$file.'" specified, is not readable.');
	}

	public function changed() {
		return !empty($this->changes);
	}

	public function get(array $property) {
		$cnt = count($property);
		if($cnt < 1)
			throw new \UnexpectedValueException('Expected property array to be at least one element long.');
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

	public function is(array $property, $type) {
		return $type == $this->type($property);
	}

	public function set(array $property, $value, $type = self::PROPERTY) {
		$cnt = count($property);
		if($cnt < 1)
			throw new \UnexpectedValueException('Expected property array to be at least one element long.');
		if($cnt > self::MAX_DEPTH)
			throw new \OutOfRangeException('Property path given is longer than '.self::MAX_DEPTH.' elements, which exceeds the set max depth.');
		
		$cur = &$this->changes;
		for($i=0; $i<$cnt; $i++){
			if($i == $cnt-1){
				$cur[$property[$i]] = $value;
				return true;
			}else
				$cur[$property[$i]] &= array();
		}
		return false;
	}

	public function valid(array $property) {
		try{
			$this->get($property);
			return true;
		}catch(\OutOfBoundsException $e){
			return false;
		}
	}

	public function type(array $property) {
		$prop = $this->find($property);
		return is_array($prop) ? Config::COLLECTION : Config::PROPERTY;
	}

	public function write() {
		// TODO: Implement write() method.
	}

	/**
	 * Remove a property.
	 * @param array $property
	 * @return boolean
	 */
	public function remove(array $property){
		// TODO: Implement remove() method.
	}

	/**
	 * Get the keys of a collection or dictionary property.
	 * @param array $property Property path.
	 * @return int|array Int if the value is a collection, an array when it is a dictionary.
	 */
	public function keys(array $property){
		// TODO: Implement keys() method.
	}
}