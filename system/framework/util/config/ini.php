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
	 * @param string $file
	 * @throws \RuntimeException
	 */
	public function __construct($file) {
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
		return $this->find($property);
	}

	public function is(array $property, $type) {
		return $type & $this->types($property);
	}

	public function set(array $property, $value, $type = self::VALUE) {
		$cnt = count($property);
		if($cnt < 1)
			throw new \UnexpectedValueException('Expected property array to be at least one element long.');
		if($cnt > self::MAX_DEPTH)
			throw new \OutOfRangeException('Property path given is longeer than 2 elements, you cannot do this in ini files.');
		
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

	public function types(array $property) {
		$prop = $this->find($property);
		return is_array($prop) ? Config::COLLECTION : Config::PROPERTY;
	}

	public function valid(array $property) {
		try{
			$this->find($property);
			return true;
		}catch(\OutOfBoundsException $e){
			return false;
		}
	}
	
	protected function find(array $property){
		$cnt = count($property);
		if($cnt < 1)
			throw new \UnexpectedValueException('Expected property array to be at least one element long.');
		if($cnt > self::MAX_DEPTH)
			throw new \OutOfRangeException('Property path given is longeer than 2 elements, you cannot do this in ini files.');
		
		$cur = $this->map;
		for($i=0; $i<$cnt; $i++){
			if(isset($cur[$property[$i]]))
				$cur = $cur[$property[$i]];
			else throw new \OutOfBoundsException('Key "'.$property[$i].'" could not be found.');
		}
		return $cur;
	}

	public function type(array $property) {
		
	}

	public function write() {
		
	}
}