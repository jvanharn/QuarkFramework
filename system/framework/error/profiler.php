<?php
/**
 * Makes profiling a piece of cake
 * 
 * @package		PageTree-Core
 * @version		$Id: profiler.php 23 2012-01-23 18:52:43Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		March 20, 2008 (v0.0.2)
 * @copyright	Copyright (C) 2006-2009 Jeffrey van Harn
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

// Define Namespace
namespace Quark;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
\Quark\import('Error', 'Error.Debug', true);

/**
 * Generate extended debug reports and memory profiles easily
*/
class Profiler {
	/**
	 * Contains the raw ticks logged by the tick functions
	 */
	private $raw_ticks = array();
	
	/**
	 * How many times it was ticked already this cycle
	 */
	private $ticked = 0;
	
	/**
	 * Types to register
	 */
	protected $types = 0;
	
	/**
	 * Profiler Constants
	 */
	const TIMER = 1;
	const MEMORY = 2;
	const TRACE = 4;
	const REFERENCES = 8;
	
	/**
	 * Exporting Constants
	 */
	const EXPORT_GRAPH = 1; // Exports it as PNG
	const EXPORT_XML = 2;
	const EXPORT_ARRAY = 3;
	const EXPORT_HTML = 4;
	
	/**
	 * Class constructor
	 * @param int $type What info to include in the report
	 */
	public function __construct(){
		
	}
	
	/**
	 * Register a profile, and what type
	 * @param int $type Type of profiler to add
	 * @return void
	 */
	public function register($type){
		$args = func_get_args();
		foreach($args as $type){
			if(is_int($type) && !($type & $this->types))
				$this->types += $type;
		}
	}
	
	/**
	 * Start registering and ticking those profiles
	 * @param int $ticks How many ticks to register. 0 for infinite
	 * @return bool
	 */
	public function start($ticks=0){
		// Reset the generated profile array
		$this->profile = array();
		// Register the tick function
		return register_tick_function(array(&$this, '_register_tick'), $this->types, (int) $ticks);
	}
	
	/**
	 * Stop registering tick activity
	 * @return bool|int Bool false on failure, or integer(Ticks registered)
	 */
	public function stop(){
		// Unregister tick function
		if(!unregister_tick_function(array(&$this, '_register_tick'))) return false;
		// Return number of ticks
		return $this->ticked;
	}
	
	/**
	 * Makes a readable and accessible set of the registred ticks
	 */
	private function _generate_result_set($types){
		// Clean result set
		$results = array();
		// Loop through the ticks
		for($i=0; $i<$this->ticked; $i++){
			// Clean tick
			$results[$i] = array();
			
			// Add timer
			if(self::TIMER & $this->types && self::TIMER & $types)
				$results[$i][] = $this->raw_ticks[self::TIMER][$i];
			// Add Memory info
			if(self::MEMORY & $this->types && self::MEMORY & $types)
				$results[$i][] = ($this->raw_ticks[self::MEMORY][$i]-$this->raw_ticks[self::MEMORY][$i-1]);
			// Add Tracing info
			if(self::TRACE & $this->types && self::TRACE & $types)
				$results[$i][] = $this->raw_ticks[self::TRACE][$i];
			// Add References info
			if(self::REFERENCES & $this->types && self::REFERENCES & $types)
				$results[$i][] = $this->raw_ticks[self::REFERENCES][$i];
		}
		return $results;
	}
	
	/**
	 * Export the profiling report
	 * @param int $type Exporting type: Export as graph, etc.
	 */
	public function export($type){
		// Create a graph(Only from numeric values)
		if($type == self::EXPORT_GRAPH){
			throw new \Exception('Not yet supported');
			
		// Create an XML file
		}else if($type == self::EXPORT_XML){
			throw new \Exception('Not yet supported');
		
		// Return an array with data
		}else if($type == self::EXPORT_ARRAY){
			return $this->_generate_result_set($this->types);
			
		// Markup the results with some HTML
		}else if($type == self::EXPORT_HTML){
			throw new \Exception('Not yet supported');
		
		// Or an error
		}else
			throw new \Exception('The export type you gave was invalid.');
	}
	
	/**
	 * Saves the raw ticks
	 */
	public function _register_tick($types, $max_ticks){
		// Check for max ticks
		if($max_ticks != 0 && $this->ticked > $max_ticks){
			$this->stop();// Stop the ticking
			return;
		}else $this->ticked++;
		
		// Time the tick
		if(self::TIMER & $types){
			$t = explode(' ', microtime());
			$this->raw_ticks[self::TIMER][] = array($t[1], $t[0]);
			unset($t);
		}
		
		// Check for Memory usage
		if(self::MEMORY & $types)
			$this->raw_ticks[self::MEMORY][] = memory_get_usage(); // Report the memory usage
		
		// Trace the currently executed function
		if(self::TRACE & $types){
			$trace = debug_backtrace(false);
			array_shift($trace);
			$this->raw_ticks[self::TRACE][] = $trace;
		}
		
		// Check references
		if(self::REFERENCES & $types){
			// Get the line
			$trace = array_shift(debug_backtrace(false));
			$file = file($trace['file']);
			$line = $file[$trace['line']];
			unset($trace, $file);
			// Try to retrieve a assigned var
			preg_match('/\$([a-zA-Z0-9\_]+)[ .+]=[ .+]/', $line, $matches);
			var_dump($matches);
			// Tick the info
			$this->raw_ticks[self::REFERENCES][] = (isset($matches[1])?$matches[1]:false);
		}
		
	}
	
	/**
	 * Make sure the tick function is unregistered
	 */
	public function __destruct(){
		@unregister_tick_function(array(&$this, '_register_tick'));
	}
}