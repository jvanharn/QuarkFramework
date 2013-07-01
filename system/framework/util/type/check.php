<?php
/**
 * Type checking class.
 * 
 * @package		Quark-Framework
 * @version		$Id: check.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		December 17, 2012
 * @copyright	Copyright (C) 2012-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012-2013 Jeffrey van Harn
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

/**
 * Type Hinting Class
 * 
 * This class enables you to check for types given to methods/functions as
 * parameters more easily. It basically just checks for the type, and when it
 * is invalid.
 * 
 * Notes:
 * - Because 'array' is an reserved word, checking for arrays is supported but only with an alias.
 * - Please try to use the native type hinting features for arrays, interfaces, objects and 'callable' callbacks.
 */
class Check {
	// Variable Types
	const STRING	= 'string';
	const INTEGER	= 'integer';
	const DOUBLE	= 'double';
	const BOOLEAN	= 'boolean';
	const HASHMAP	= 'array';
	const OBJECT	= 'object';
	const RESOURCE	= 'resource';
	const NIL		= 'null';
	const NON_EMPTY	= 'non_empty';
	
	// Aliasses
	const INT	= self::INTEGER;
	const FLOAT	= self::DOUBLE;
	const BOOL	= self::BOOLEAN;
	const OBJ	= self::OBJECT;
	
	/**
	 * List of valid type strings.
	 * @var array
	 */
	public static $types = array(
		self::STRING,
		self::INTEGER,
		self::DOUBLE,
		self::BOOLEAN,
		self::HASHMAP,
		self::OBJECT,
		self::RESOURCE,
		self::NIL
	);
	
	/**
	 * Check the type of a method parameter.
	 * @param string $name Name of the function/method parameter name.
	 * @param string|array $type One type or multiple type strings that the value should match.
	 * @param mixed $value Value to check.
	 * @param boolean $return Should we return a boolean or throw an error.
	 * @return boolean
	 */
	public static function check($name, $type, $value, $return=false){
		// Check parameters
		if(!is_string($name)) throw new \InvalidArgumentException('Parameter $name should be of type "string".');
		if(!is_array($type) && !is_string($type)) throw new \InvalidArgumentException('Parameter $type should be of type "array" and contain of the constants of the Type class.');
		else if(is_string($type)) $type = array($type);

		// Check the type
		$valType = strtolower(gettype($value));
		$matchesType = false;
		foreach($type as $curType){
			if($curType == self::NON_EMPTY && empty($value)){
				if($return)
					return false;
				else \Quark\Error::raiseFatalError('Parameter $'.$name.' should be non-empty.', 'Wrong type was given to a method, enable debugging for more info.');
			}else if($valType == $curType){
				$matchesType = true;
			}
		}
		
		// Check for errors
		if($return)
			return $matchesType;
		else if(!$matchesType)
			\Quark\Error::raiseFatalError('Parameter $'.$name.' should be of type "'.implode('" or "', $type).'" but found "'.$valType.'".', 'Wrong type was given to a method, enable debugging for more info.');
	}
	
	/**
	 * Check if a string is a valid type string
	 * @param string $type
	 * @return boolean
	 */
	public static function isType($type){
		return (is_string($type) && in_array($type, self::$types));
	}
	
	/**
	 * Handles dynamic type calls
	 * @param string $name
	 * @param array $arguments
	 * @return boolean
	 */
	public static function __callStatic($type, $arguments){
		$type = strtolower($type);
		if(self::isType($type) && count($arguments) == 2){
			return self::check($arguments[0], $type, $arguments[1]);
		}else if(is_string($type)){ // Try to parse and check if multipart call
			$types = explode('or', $type);
			foreach($types as $key => $type){
				if($type == 'either' || $type == 'oneof' || $type == 'is')
					unset($types[$key]);
				if(!self::isType($type))
					throw new \RuntimeException('Invalid multipart call detected, the type "'.$type.'" was invalid.');
			}
			return self::check($arguments[0], $types, $arguments[1]);
		}else throw new \RuntimeException('Invalid type for static method call.');
	}
}