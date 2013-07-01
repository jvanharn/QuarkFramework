<?php
/**
 * XML Configuration file implementation.
 * 
 * @package		Quark-Framework
 * @version		$Id: xml.php 69 2013-01-24 15:14:45Z Jeffrey $
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
 * XML Configuration file
 */
class XML implements Config {
	protected $file;
	protected $xml;
	protected $changed = false;

	public function __construct($file) {
		if(is_readable($file)){
			$this->file = $file;
			$this->xml = simplexml_load_file($this->file);
		}else throw new \RuntimeException('The config file "'.$file.'" specified, is not readable.');
	}

	public function changed() {
		return $this->changed;
	}

	public function get(array $property) {
		return $this->find($property);
	}

	public function is(array $property, $type) {
		$prop = $this->find($property);
		if	(($type == Config::COLLECTION && $prop->count() > 0) &&
			($type == Config::PROPERTIES && $prop->attributes()->count() > 0) && 
			($type == Config::VALUE && strlen((String) $prop) > 0))
			return true;
		else return false;
	}

	public function set(array $property, $value, $type = self::VALUE) {
		$this->changed = true;
		$cnt = count($property);
		if($cnt < 1)
			throw new \OutOfRangeException('Property must at least be one element long.');
		
		$set = array_pop($property);
		$prop = $this->find($property);
		if($type == self::VALUE){
			$prop[$set] = $value;
			return true;
		}else{
			if(is_numeric($set)){
				return $prop->addAttribute($set, $value);
			}else{
				return $prop->addChild($set, $value);
			}
		}
	}

	public function types(array $property) {
		$hash = 0;
		$prop = $this->find($property);
		if($prop->count() > 0)
			$hash += Config::COLLECTION;
		else if($prop->attributes()->count() > 0)
			$hash += Config::PROPERTIES;
		else if(strlen((String) $prop) > 0)
			$hash += Config::VALUE;
		
		return $hash;
	}

	public function valid(array $property) {
		try{
			$this->find($property);
			return true;
		}catch(OutOfBoundsException $e){
			return false;
		}
	}
	
	protected function find($property){
		$cnt = count($property);
		if($cnt < 1)
			throw new \OutOfRangeException('Property must at least be one element long.');
		
		$cur = $this->xml;
		for($i=0; $i<$cnt; $cnt++){
			if(isset($this->xml{$property[$i]}))
				$cur = $this->xml{$property[$i]};
			else throw new \OutOfBoundsException('Invalid key.');
		}
		return $cur;
	}
}