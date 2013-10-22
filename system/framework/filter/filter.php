<?php
/**
 * A text filtering and validation class
 * 
 * @package		Quark-Framework
 * @version		$Id: filter.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 2, 2011
 * @copyright	Copyright (C) 2011 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2011 Jeffrey van Harn
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
namespace Quark\Filter;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
\Quark\import('Filter.filters');
\Quark\import('Filter.validators');

/**
 * Filtering class.
 * 
 * This filtering class should make filtering easy. May be difficult in the begin but once you get
 * the principle it is easy, especially if you have to apply the same filter on many strings.
 * It is based upon an filtering class made for the first PageTree series.
 * 
 * Basic Example:
 * <code>
 * Filter::apply('S$o&m3e @\/\/3$0/\/\3 Str!ing', array(Filter::FILTER => array('chars' => CONTAINS_ALPHA)));
 * </code>
 * 
 * @subpackage Filter
 */
class Filter{
	/**
	 * Filter Type "Filter"
	 */
	const FILTER = 1;
	
	/**
	 * Filter Type "Validator"
	 */
	const VALIDATOR = 2;
	
	/**
	 * List of the Registred Filters
	 * @var array 
	 */
	protected static $filters = array(
		self::FILTER => array(
			'CHARS'				=> array('\\Quark\\Filter\\Filters', 'CHARS'),
			'BLACKLIST_CHARS'	=> array('\\Quark\\Filter\\Filters', 'BLACKLIST_CHARS'),
			'ENCODE_EMAIL'		=> array('\\Quark\\Filter\\Filters', 'ENCODE_EMAIL')
		),
		self::VALIDATOR => array(
			'CHARS'				=> array('\\Quark\\Filter\\Validators', 'CHARS'),
			'BLACKLIST_CHARS'	=> array('\\Quark\\Filter\\Validators', 'BLACKLIST_CHARS'),
			'EMAIL'				=> array('\\Quark\\Filter\\Validators', 'EMAIL'),
			'EMAIL_EXISTS'		=> array('\\Quark\\Filter\\Validators', 'EMAIL_EXISTS'),
			'SIZE'				=> array('\\Quark\\Filter\\Validators', 'SIZE'),
		)
	);
	
	// Filtering functions
	/**
	 * Apply filters and validators on a mixed variable (External wrapping function for execFilter)
	 *
	 * @param mixed $input The input to filter
	 * @param array $filters The filter(s) to apply
	 * @throws RuntimeException
	 * @return mixed
	 */
	public static function apply($input, $filters){
		// Check if input is empty
		if(empty($input)) return false;
		
		// Iterate and apply the individual filters
		foreach($filters as $type => $list){
			foreach($list as $filter => $options){
				if(is_numeric($filter) && is_string($options)){
					if(self::isRegistered($filter, $type))
						$input = self::execFilter($input, $options, $type);
					else \Quark\Error::raiseError('The '.(($type==self::FILTER)?'filter':'validator').' "'.$options.'" doesn\'t exist, thus I could not apply it on the string!');
				}else if(is_string($filter) && is_array($options)){
					if(self::isRegistered($filter, $type))
						$input = self::execFilter($input, $filter, $type, $options);
					else \Quark\Error::raiseError('The '.(($type==self::FILTER)?'filter':'validator').' "'.$filter.'" doesn\'t exist, thus I could not apply it on the string!');
				}else if(is_string($filter) && is_string($options)){
					if(self::isRegistered($filter, $type))
						$input = self::execFilter($input, $filter, $type, array($options));
					else \Quark\Error::raiseError('The '.(($type==self::FILTER)?'filter':'validator').' "'.$filter.'" doesn\'t exist, thus I could not apply it on the string!');
				}else throw new RuntimeException('Filter::Apply()\'s $filters syntaxis was incorrect, and could not be processed. Stopped at filter "'.$filter.'" and options "'.var_export($options, true).'"');
			}
		}
		
		// And return
		return $input;
	}
	
	/**
	 * Executes a filter callback function on a input string
	 * @param string $input Input string to apply the filter on
	 * @param string $filter The filter name to apply
	 * @param int $type Filter type
	 * @param array $options Additional parameters to send to the filter calback function
	 * @return string|bool|int Result of the callback on success integer -1 on error
	 */
	protected static function execFilter($input, $filter, $type, $options=array()){
		// Check the type
		$filter = strtoupper($filter);
		if(!($type == self::VALIDATOR || $type == self::FILTER)){
			\Quark\Error::raiseWarning('$Type for FilterRegistry::execFilter is not valid, has to be one of FilterRegistry::FILTER or FilterRegistry::VALIDATOR but got "'.$type.'"');
			return false;
		}
		
		// Check if the filter exists
		if(array_key_exists($filter, self::$filters[$type])){
			// Retrieve filter details
			$callback = self::$filters[$type][$filter];
			// Check if the filter function exists
			if((is_array($callback) && (method_exists($callback[0],$callback[1]) || method_exists($callback[0], '__call'))) || (is_string($callback) && function_exists($callback))){
				// Check the type
				if($type == self::FILTER)
					// Call the filter
					return call_user_func($callback, $input, $options);
				else if($type == self::VALIDATOR)
					// Call the validator
					return (bool) call_user_func($callback, $input, $options);
				else{
					throw new \RuntimeException('Somewhere, something went terribly wrong... The type should be either filter or validator but turned out to be "'.$type.'".');
					return -1;
				}
			}else{
				\Quark\Error::raiseError('Callback registered for Filter: "'.$filter.'" does not exist.');
				return -1;
			}
		}else{
			\Quark\Error::raiseWarning('Filter is not registered: "'.$filter.'", therefore could not apply the filter on the input string.');
			return -1;
		}
	}
	
	// Filter Registry Functions
	/**
	 * Register a filter callback function
	 * 
	 * The callback receives 2 parameters, the string to be filtered and the options array
	 * that the user gave to the filter.
	 * 
	 * Warning: You cannot override a filter/validator with one of another type!
	 * @param string $name The name of the filter or validator
	 * @param int $type The type: validator or filter
	 * @param mixed $callback A callback function that can be handled by call_user_func()
	 * @param bool $override Whether or not to override if it already exists
	 * @return bool
	 */
	public static function register($name, $type, $callback, $override=false){
		// Make it uppercase
		$name = strtoupper($name);
		
		// Check the type
		if(!($type == self::VALIDATOR || $type == self::FILTER)){
			\Quark\Error::raiseWarning('Type "'.$type.'" is not valid, has to be one of FilterRegistry::FILTER or FilterRegistry::VALIDATOR but got "'.$type.'"');
			return false;
		}
		
		// Check if it exists
		if(!array_key_exists($name, self::$filters[$type]) || $override == true){
			// Check if the filter function exists
			if((is_array($callback) && (method_exists($callback[0],$callback[1]) || method_exists($callback[0], '__call'))) || (is_string($callback) && function_exists($callback))){
				self::$filters[$type][$name] = $callback; // Register it
				return true;
			}else{
				\Quark\Error::raiseError('Callback registered for Filter: "'.$name.'" does not exist.');
				return false;
			}
		}else{
			\Quark\Error::raiseWarning('Filter "'.$name.'" already exists.');
			return false;
		}
	}
	
	/**
	 * Unregister a filter callback
	 * @param string $name The filter's name
	 * @param int $type The filter type
	 * @return bool
	 */
	public static function unregister($name, $type){
		$name = strtoupper($name);
		if(array_key_exists($name, self::$filters[$type])){
			unset(self::$filters[$type][$name]);
			return true;
		}else return false;
	}
	
	/**
	 * Check if a filter is registered
	 * @param string $name The filter's name
	 * @param int $type The filter's type
	 * @return bool
	 */
	public static function isRegistered($name, $type){
		return array_key_exists(strtoupper($name), self::$filters[$type]);
	}
	
	/**
	 * Get the registred callback of one filter
	 * @param string $name Filter name
	 * @param int $type Filtertype
	 * @return array
	 */
	public static function getCallback($name, $type){
		$name = strtoupper($name);
		if(array_key_exists($name, self::$filters[$type]))
			return self::$fitlers[$type][$name];
		else return false;
	}
}

/**
 * Filters/validates the given input with the given $filters
 * 
 * If you give the $input parameter as array, then the quick_syntax will be used
 * @see \Quark\Filter\Filter::apply()
 * @param mixed $input A string, int, etc. to be filtered with the given filters
 * @param mixed $filters The filter(s) with which to process the input
 * @return mixed Filtered input on success, bool false on failure
 */
function filter_string($input, $filters){
	return Filter::apply($input, array(Filter::FILTER=>$filters));
}

function validate_string($input, $validators){
	if(is_string($input) || is_numeric($input))
		return Filter::apply((string) $input, array(Filter::VALIDATOR=>$validators));
	else return false;
}