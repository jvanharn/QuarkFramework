<?php
/**
 * Handler Interface
 * 
 * @package		Quark-Framework
 * @version		$Id: handler.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		16 december 2012
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
namespace Quark\Extensions;

use	\Quark\Util\Config\Config,
	\Quark\Util\Config\Mapper,
	\Quark\Util\Config\JSON;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

\Quark\import(
	'Framework.Util.Config.Config',
	'Framework.Util.Config.Mapper',
	'Framework.Util.Config.INI',
true);

/**
 * Handler Interface
 * 
 * Every Extension Handler should implement this interface. It helps the
 * Extensions class get info about extensions in a consistent way, and lets it
 * handle extensions in various aspects.
 */
interface Handler{
	/**
	 * Check if a extension is loadable by the Handler/Loader
	 * @param string $path The path to the extension dir, that has to be checked with a trailing slash
	 * @return boolean True if the extension is loadable by the handler, false if not.
	 */
	public function test($path);
	
	/**
	 * Get info about the extension on the given path
	 * @param string $path The path to the extension with a trailing slash
	 * @return boolean|array Boolean on failure array otherwise
	 */
	public function info($path);
	
	/**
	 * Get the list of dependencies for the given extension.
	 * 
	 * Format: [
	 *   'dependency.load.path' => [ // For utility packages.
	 *     type => 'utility',
	 *     version => 'some version string'
	 *   ],
	 *   'extension.name' => [ // For other extensions
	 *     type => 'extension',
	 *     version => ...
	 *   ],
	 *   'framework' => [ // For minimal quark version
	 *     type => 'framework',
	 *     version => ...
	 *   ],
	 *   'application' => [ // When the current application exposes it's version number, the minimal application version
	 *     type => 'application',
	 *     version => ...
	 *   ]
	 * ]
	 * All version strings must be parsable by the version utility class.
	 * @param string $path The path to the extension directory
	 * @return array
	 */
	public function dependencies($path);
	
	/**
	 * Load an extension
	 * Only gets called when the extension is enabled.
	 * Is called before the extension is registred.
	 * @param string $name Name as registred in the extension registry with a trailing slash
	 * @param string $path The path to the extension directory
	 * @return boolean
	 */
	public function load($name, $path);
	
	/**
	 * Should give the default extension priority for this extension type (Standard: 10)
	 * @return integer Between 0 and 100 where higher numbers have higher priority.
	 */
	public function defaultPriority();
}

/**
 * Provides a basic handler config implementation based on an XML Config file.
 */
trait baseHandler {
	/**
	 * File Structure for Config Mapper
	 * @var array
	 */
	public static $default = array(
		'type' => Config::DICTIONARY,
		'struct' => [
			'title'			=> false,
			'description'	=> false,
			'version'		=> false,
			'author'		=> false,
			'copyright'		=> false,
			'dependencies' => [
				'type' => Config::COLLECTION,
				'struct' => [
					'type' => Config::PROPERTY,
					'struct' => [
						'name'		=> false,
						'type'		=> false,
						'version'	=> false
					],
					'optional' => true
				]
			]
		]
	);
	
	/**
	 * Stored info for paths.
	 * @var array
	 */
	protected $info = array();
	
	/**
	 * Tests whether or not the extension can be loaded by this handler.
	 * @param string $path Path to the extension to test.
	 * @return boolean
	 */
	public function test($path){
		// Check if the directory exists
		if(!is_dir($path)) return false;
		
		// Check if config exists
		if(!is_file($path.'info.json')) return false;
		
		// Everything went well.
		return true;
	}
	
	/**
	 * Get info about the extension.
	 * @param string $path Path to the extension to get info about.
	 * @return boolean
	 * @throws \RuntimeException When the extension info file cannot be found.
	 */
	public function info($path){
		if(!is_file($path.'info.json'))
			throw new \RuntimeException('Extension descriptor/info file could not be found.');
		$called = get_called_class();
		try{
			if(isset($called::$map))
				$map = $called::$map;
			else $map = self::$default;
			
			if(!isset($this->info[$path]))
				$this->info[$path] = (new Mapper(new JSON($path.'info.json')))->toArray($map);
			return $this->info[$path];
		}catch(\Quark\Util\Config\MapperException $e){
			// @todo: Properly handle Config Mapper or ConfigFormat Exceptions
			throw new \RuntimeException('Settings file for extension on path "'.$path.'" was not correctly formatted.', E_ERROR, $e);
			return false;
		}
	}
	
	/**
	 * Get a properly formatted list of dependencies for the given extension.
	 * @param string $path Path to the extension to get dependency information about.
	 * @return array
	 */
	public function dependencies($path){
		$inf = $this->info($path);
		$return = array();
		foreach($inf['dependencies'] as $dep){
			$return[$dep['name']] = array('type' => $dep['type'], 'version' => $dep['version']);
		}
		return $return;
	}
	
	/**
	 * Get the default priority for this type of extension (20).
	 * @return int
	 */
	public function defaultPriority(){
		return 10;
	}
}