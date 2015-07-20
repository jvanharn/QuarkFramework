<?php
/**
 * Maps a Config object to an array
 * 
 * @package		Quark-Framework
 * @version		$Id: mapper.php 69 2013-01-24 15:14:45Z Jeffrey $
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
 * Maps config classes to another type
 * 
 * Specification/Property Map should be formatted as such:
 * (Beware the properties type may only contain key value pairs,
 * additionally it can only map one of the three properties, because of the
 * nature of php arrays)
 * [
 *   // Value
 *   'propname' => (bool) optional
 *   'propname' => [type => &Config::VALUE, optional => (bool)]
 *   // Properties
 *   'propname' => [type => &Config::PROPERTIES, props => [shallow properties], optional => (bool)]
 *   // Collection
 *   'propname' => [type => &Config::COLLECTION, def => [properties], optional => (bool)]
 * ]
 */
class Mapper {
	/**
	 * Configuration reference
	 * @var \Quark\Util\Config\Config
	 */
	protected $config;
	
	/**
	 * Create a new mapper for the given Configuration object.
	 * @param \Quark\Util\Config\Config $config
	 */
	public function __construct(Config &$config){
		$this->config = $config;
	}
	
	/**
	 * Map the config to an array using the given specification.
	 * 
	 * Get info from the configuration file, according to the given
	 * specification, and parse it into an array.
	 * @param array $spec Properly formatted specification, or property map.
	 * @return array
	 */
	public function toArray($spec, array $base=array()){
		if(!(is_array($spec) || is_bool($spec)))
			throw new \InvalidArgumentException('Argument $spec must be of type array or boolean, "'.gettype($spec).'" given.');
		if(empty($base) || $this->config->valid($base)){
			if(is_bool($spec) || Config::PROPERTY == $spec['type']){
				return $this->config->get($base);
			}else if(Config::DICTIONARY == $spec['type']){
				$return = array();
				foreach($spec['struct'] as $prop => $cspec){
					$path = array_merge($base, [$prop]);
					$return[$prop] = $this->toArray($cspec, $path);
				}
				return $return;
			}else if(Config::COLLECTION == $spec['type']){
				$return = array();
				$current = $base;
				$current[count($base)] = 0;
				for($i=0; $this->config->valid($current); $i++){
					$return[] = $this->toArray($spec['struct'], $current);
					$current[count($base)] = $i+1;
				}
				return $return;	
			}else throw new MapperException('Invalid property type specified.');
		}else if($spec === false || (isset($spec['optional']) && $spec['optional'] === false))
			throw new MapperException('Property "'.\end($base).'" was not defined in the config file, could not map to array.');
		else return null;
	}
	
	/**
	 * 
	 * @param array $spec
	 * @param array $base
	 */
	public function toObject(array $spec, array $base=array()){
		// @todo
	}
}

class MapperException extends \RuntimeException { }