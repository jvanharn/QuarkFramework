<?php
/**
 * Configuration Interface
 * 
 * @package		Quark-Framework
 * @version		$Id: config.php 69 2013-01-24 15:14:45Z Jeffrey $
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
 * Configuration Interface
 */
interface Config {
	/**
	 * Open the config file in read only mode.
	 */
	const MODE_READONLY = 1;

	/**
	 * Open the config in read and write mode.
	 */
	const MODE_READWRITE = 2;

	/**
	 * Plain Text Value with or without property's
	 */
	const PROPERTY = 1;
	
	/**
	 * Path has (Unique) properties with name and value
	 */
	const DICTIONARY = 2;
	
	/**
	 * Path is Collection of paths of the same or multiple types (Array, zero indexed)
	 */
	const COLLECTION = 3;

	/**
	 * Open a configuration file.
	 * @param string $file File path to the configuration file to parse.
	 * @param int $mode Mode to open the file in.
	 */
	public function __construct($file, $mode=self::MODE_READWRITE);
	
	/**
	 * Get the plain value of a property (If it is a valid property or value).
	 * @param array $property Property path.
	 * @return mixed Property value.
	 */
	public function get(array $property);

	/**
	 * Set the value of the property.
	 * @param array $property
	 * @param mixed $value
	 * @param int $type
	 * @return boolean
	 */
	public function set(array $property, $value, $type=self::PROPERTY);

	/**
	 * Remove a property.
	 * @param array $property
	 * @return boolean
	 */
	public function remove(array $property);
	
	/**
	 * Check if the property path is valid.
	 * @param array $property
	 * @return boolean
	 */
	public function valid(array $property);
	
	/**
	 * Check if the property path is of said type
	 * @param array $property 
	 * @param integer $type Type constant from Config interface.
	 * @return boolean
	 */
	public function is(array $property, $type);
	
	/**
	 * Get the type of the given property.
	 * @param array $property Property path to check.
	 * @return integer Type of the property.
	 */
	public function type(array $property);

	/**
	 * Get the keys of a collection or dictionary property.
	 * @param array $property Property path.
	 * @return int|array Int if the value is a collection, an array when it is a dictionary.
	 */
	public function keys(array $property);
	
	/**
	 * Check if the config properties have changed since opening.
	 * @return boolean
	 */
	public function changed();
	
	/**
	 * Explicitly write the current state of the config object to the config file.
	 * @return boolean
	 */
	public function write();
}

class ConfigFormatException extends \RuntimeException {}