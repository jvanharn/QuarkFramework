<?php
/**
 * Central logging system that will log events and errors etc.
 * 
 * @package		Quark-Framework
 * @version		$Id: log.php 44 2012-09-24 13:42:02Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		March 20, 2008
 * @copyright	Copyright (C) 2006-2012 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2006-2009 Jeffrey van Harn
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

// Set the namespaces
namespace Quark\System;

// Prevent acces to this standalone file
if(!defined('DIR_BASE')) exit;

/**
 * Centralized logging class
 * 
 * Will be expanded in the future with different logging engines(Plain-text, mysql, sqlite, etc.)
 * @subpackage System
 */
class Log{
	/**
	 * Log level constants(Bitwise)
	 */
	const NONE		= 0;
	const DEBUG		= 1;
	const NOTICE	= 2;
	const WARNING	= 4;
	const ERROR		= 8;
	const EXCEPTION	= 16;
	const VIOLATION	= 32;
	const FATAL		= 64;
	const ALL		= 127;
	
	/**
	 * Export formatting constants
	 */
	const EXPORT_ARRAY	= 1;
	const EXPORT_XML	= 2;
	const EXPORT_JSON	= 3;
	
	/**
	 * All logs with the levels to log to them
	 */
	protected static $categories = array(
		// Default log, contains logged messages with this explicit catagory
		'system' => Log::NONE,
		
		// The error log(all errors: Error, Exception, Violation, Fatal)
		'error' => 120,
		
		// Get's added in Debug Mode, uncomment if that doesn't work
		//'debug' => Log::ALL
	);
	
	/**
	 * Log a message
	 * @param int $level One of the log level constants
	 * @param string $message Message to log
	 * @param string $cat An aditional category to log to, except for the default for the level
	 * @return bool
	 */
	public static function message($level, $message, $category=null){
		// Check the params
		if(!is_int($level)) throw new \InvalidArgumentException('$level has to be an "Integer", "'.gettype($level).'" given.');
		if(!is_string($message)) throw new \InvalidArgumentException('$message has to be a "Binary" or "String", "'.gettype($message).'" given.');
		if(!((is_string($category) && ctype_alpha($category)) || $category == null)) throw new \InvalidArgumentException('$category has to be a "Binary" or "String" and only contain Alpha characters, "'.gettype($category).'"('.$category.') given.');
		
		// Check if the catagory is set
		if(!empty($category)){
			// Lowercase it
			$category = strtolower($category);
			// Check if the category exists
			if(!isset(self::$categories[$category])) throw new \DomainException('The category "'.$category.'" does not exist/is not registred.');
		
		// Check if both the level and $catagory are null
		}else if(empty($catagory) && $level == 0){
			throw new \UnexpectedValueException('The $category and $level argument for the static '.__NAMESPACE__.'\Log::message() function, where both empty or null. Thus I could not find an adequate log to write this log message to.');
		}else $category = null;
		
		// Check the level
		$name = self::getLevelAsString($level);
		if($name == false) throw new \DomainException('The $level integer ('.$level.') is not in the domain of bitmask numbers.');
		
		// Check if the logging
		if(!is_dir(DIR_LOGS)){
			$mk = mkdir(DIR_LOGS, 0755);
			if(!$mk) throw new \RuntimeException('Could not create the logging directory at the configured "'.DIR_LOGS.'", so please do this yourself, or change the permissions of it\'s parent dir! (Make it writable)');
		}
		
		// Loop through categories
		foreach(self::$categories as $cat => $levels){
			// Only log if the $level has to be logged or if the cat matches
			if($level & $levels || $cat == $category){
				// Log the message(Will someday be replaced by an EngineFactory
				if(!file_put_contents(
					DIR_LOGS.$category.'.log',
					'['.date('r').'] ['.$name.'] ['.$_SERVER['REMOTE_ADDR'].'] ['.$_SERVER['REQUEST_METHOD'].'] > '.$message.PHP_EOL,
					(is_file(DIR_LOGS.$category.'.log')? FILE_APPEND : 0)
				)){
					throw new \RuntimeException('An error occured while trying to write to the file "'.DIR_LOGS.$category.'.log".');
					return false;
				}
			}
		}
		
		// And return
		return true;
	}
	
	/**
	 * Export the contents of a log, or all logs
	 * @param string $category Optional category to export
	 * @return array|string|bool
	 */
	public static function export($category=null, $format=self::EXPORT_ARRAY){
		// Check the params
		if(!(!empty($category) && is_string($category) && ctype_alpha($category))) throw new \InvalidArgumentException('$category has to be a non-empty "Binary" or "String" and only contain Alpha characters, "'.gettype($category).'"('.$category.') given.');
		if(!is_file(DIR_LOGS.$category.'.log')) throw new \InvalidArgumentException('The $category "'.$category.'" is not a valid category: The log-file does not exist.');
		if(!is_int($format)) throw new \InvalidArgumentException('$format has to be an "Integer", "'.gettype($format).'" given.');
		
		// Prepare the selected format
		switch($format){
			case self::EXPORT_JSON:
			case self::EXPORT_ARRAY: $return = array(); break;
			case self::EXPORT_XML:
				$document = new \DOMDocument('1.0');
				$root = $document->createElement('log');
				$root = $document->appendChild($root);
				break;
			default: throw new \Exception('Invalid $format("'.$format.'") given to Log::export().');
		}
		
		// First parse the logs
		$lines = file(DIR_LOGS.$category.'.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach($lines as $raw_line){
			// Parse Line (Yes it is faster than a regex)
			$line = explode('] [', str_replace('] > ','] [', substr($raw_line, 1)), 5);
			
			// Do something(Depends on export type)
			if($format == self::EXPORT_ARRAY || $format == self::EXPORT_JSON){
				$return[] = array(
					'date' => $line[0],
					'level' => $line[1],
					'ip' => $line[2],
					'method' => $line[3],
					'message' => $line[4]
				);
			}else if($format == self::EXPORT_XML){
				// Create the entry, with the message
				$entry = $root->createElement('entry', $line[4]);
				
				// Set the entry info
				$entry->setAttribute('date', $line[0]);
				$entry->setAttribute('level', $line[1]);
				$entry->setAttribute('ip', $line[2]);
				$entry->setAttribute('method', $line[3]);
				
				// Append the log item
				$root->appendChild($entry);
			}
		}
		
		// Return..
		if($format == self::EXPORT_ARRAY) return $return;
		if($format == self::EXPORT_XML) return $document->saveXML();
		if($format == self::EXPORT_JSON) return json_encode($return);
	}
	
	/**
	 * Get a level name as string
	 * @param int $level
	 * @return string|bool
	 */
	public static function getLevelAsString($level){
		if(self::DEBUG == $level) return 'Debug';
		else if(self::NOTICE == $level) return 'Notice';
		else if(self::WARNING == $level) return 'Warning';
		else if(self::ERROR == $level) return 'Error';
		else if(self::EXCEPTION == $level) return 'Exception';
		else if(self::VIOLATION == $level) return 'Violation';
		else if(self::FATAL == $level) return 'Fatal Error';
		else return false;
	}
	
	/**
	 * Get all the registered categories
	 * @param bool $bitmask Whether or not to also give the bitmasks.
	 * @return array|bool
	 */
	public static function getCategories($bitmask=false){
		if($bitmask) return self::$categories;
		else return array_keys(self::$categories);
	}
	
	/**
	 * Add a category, and the levels to log to it
	 * @param string $name Category name
	 * @param int $levels The levels to log to the category
	 * @param bool $overwrite Whether or not to overwrite a category if it already exists
	 * @return bool
	 */
	public static function addCategory($name, $levels, $overwrite=false){
		// Check the params
		if(!(is_string($name) && ctype_alpha($name))) throw new \InvalidArgumentException('$name has to be a "String" and only contain Alpha characters, "'.gettype($name).'" given.');
		if(!is_int($levels)) throw new \InvalidArgumentException('$levels has to be an "Integer", "'.gettype($levels).'" given.');
		
		// Check if it exists
		if(!$overwrite && isset(self::$categories[strtolower($name)])) return false;
		
		// Add and return
		self::$categories[strtolower($name)] = $levels;
		return true;
	}
	
	/**
	 * Remove a log Category
	 * @param string $name Log name
	 * @return bool
	 */
	public static function remCategory($name){
		// Check if it exists
		if(isset(self::$categories[$name])) return false;
		
		// Remove and return
		unset(self::$categories[$name]);
		return true;
	}
	
	/**
	 * Clear the contents of all logs/categories
	 * @return bool
	 */
	public static function clearLogs(){
		$logs = scandir(DIR_LOGS);
		foreach($logs as $log){
			if(substr($log, -4, 0) == '.log')
				unlink(DIR_LOGS.$log);
		}
		return true;
	}
}

/**
 * Shortcut for {@see Log::message}
 */
function logMessage($level, $message, $category=null){
	return Log::message($level, $message, $category);
}