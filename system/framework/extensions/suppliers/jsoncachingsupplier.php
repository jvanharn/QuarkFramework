<?php
/**
 * INI File Caching Supplier
 * 
 * @package		Quark-Framework
 * @version		$Id: inicachingsupplier.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		15 december 2012
 * @copyright	Copyright (C) 2012 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012 Jeffrey van Harn
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
namespace Quark\Extensions\Suppliers;

// Import dependencies
use Quark\Extensions\CachingSupplier;
use Quark\Extensions\Extensions;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Cached Extension Supplier
 * 
 * Cached Supplier that simply stores the state etc. in a JSON file in the extension directory root.
 * Which makes it human readable and easy to store.
 * 
 * Main JSON File structure:
 * [
 * 	"mysql.driver": {							; The name of the extension
 * 		"state": enabled|disabled|loaderror|new	; The state of the extension (Explanation below)
 * 		"path": "/path/to/the/extension/dir"	; Path to the extension directory
 * 		"type": "driver"						; The type, or extension that is used for Handler recognition (Mostly the same as the handler name)
 * 		"handler": "driver"						; The handler to be used to load it.
 * 		"priority": 10							; The priority of the extension (Influences loading order)
 * 	}
 * ]
 */
class JSONCachingSupplier implements CachingSupplier{
	const DEFAULT_FILENAME = 'extensions.json';
	
	protected $path;

	/**
	 * @param string $filename
	 */
	public function __construct($filename=self::DEFAULT_FILENAME) {
		$this->path = DIR_DATA.$filename;
	}

	/**
	 * @return bool
	 */
	public function cacheable() {
		return is_writable(dirname($this->path));
	}

	/**
	 * @return bool
	 */
	public function available() {
		return is_file($this->path);
	}

	/**
	 * @param Extensions $registry
	 * @return bool
	 */
	public function cache(Extensions $registry) {
		// Make sure we overwrite
		if(file_exists($this->path) && is_writable($this->path))
			@unlink($this->path);

		// Get extensions registry
		$extensions = $registry->getExtensionRegistry();
		
		// If the registry is empty, abort
		if(count($extensions) == 0)
			return true;

		$result = array();

		// Loop over the plugins
		foreach($extensions as $name => $props){
			// Create the entry
			$result[$name] = array(
				'path' => $props['path'],
				'type' => $props['handler'],
				'handler' => $props['handler'],
				'state' => $props['state'],
				'priority' => $props['priority'],

				'dependencies' => $props['dependencies'],
				'info' => $props['info']
			);
		}

		return (file_put_contents($this->path, json_encode($result)) !== false);
	}

	/**
	 * @param Extensions $registry
	 * @return bool
	 */
	public function fill(Extensions $registry) {
		// Get the extension registry
		$extensions = $registry->getExtensionRegistry();
		$handlers = $registry->getHandlerRegistry();

		$cache = json_decode(file_get_contents($this->path), true);

		// If the registry is empty, abort
		if(count($cache) == 0)
			return true;
		
		// Loop over the plugins
		foreach($cache as $name => $props){
			// Check if the path still exists, or if it's a garbage-entry(If it doesnt even have a path or status, it's just bogus.
			if((isset($props['path']) && !is_dir($props['path'])) || !isset($props['path']) || !isset($props['state']) || !isset($props['handler']) || !isset($props['info'])){
				unset($cache[$name]);
				continue;
			}
			
			// Check the handler is loadable
			if(!$handlers->exists($props['handler'])){
				$props['state'] = Extensions::STATE_LOADERROR;
			}

			// Insert in Queue
			$extensions->register($name, array(
				'path'			=> $props['path'],
				'type'			=> $props['type'],
				'handler'		=> $props['handler'],
				'state'			=> $props['state'],
				'priority'		=> intval($props['priority']),
				'dependencies'	=> $props['dependencies'],
				'info'			=> $props['info']
			));
		}
	}
}