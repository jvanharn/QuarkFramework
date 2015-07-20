<?php
/**
 * Extension Registry
 * 
 * @package		Quark-Framework
 * @version		$Id: extensionregistry.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		March 5, 2012
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
namespace Quark\Extensions;
use Quark\Util\Registry;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Registry that stores all the available and loaded extensions.
 * 
 * The value for a Registry entry should be formatted in the following way:
 * array(
 *   path => (string) '/Full/Path/to/the/extension/directory/',
 *   handler => (string) 'RegisteredHandlerName',
 *   state => (int) State type,
 *   info => (array) Key value pairs of info about the extension.
 * )
 */
class ExtensionRegistry extends Registry {
	/**
	 * Handler Registry
	 * @var \Quark\Extensions\HandlerRegistry
	 */
	protected $handlers;
	
	/**
	 * Handler Registry Constructor
	 * @param \Quark\Extensions\HandlerRegistry $handlers Reference to the current handler registry.
	 */
	public function __construct(HandlerRegistry $handlers) {
		$this->handlers = $handlers;
	}
	
	/**
	 * Get the handler for a given extension
	 * @param string $name Extension Name
	 * @return boolean|string
	 */
	public function getHandler($name){
		if($this->exists($name)){
			return $this->registry[$name]['handler'];
		}else return false;
	}
	
	/**
	 * Get the info for an extension.
	 * @param string $name Extension Name
	 * @return boolean|array
	 */
	public function getInfo($name){
		if($this->exists($name)){
			return $this->registry[$name]['info'];
		}else return false;
	}
	
	/**
	 * Get all registred extensions by their handler.
	 * @param string $handler Valid, existing handler to look for.
	 * @return array Always returns array populated with the extensions registred with the specified handler.
	 * @throws \InvalidArgumentException
	 * @throws \OutOfBoundsException
	 */
	public function getByHandler($handler){
		// Check parameter
		if(!is_string($handler))
			throw new \InvalidArgumentException('Parameter $handler should be of type "string", but found "'.gettype($handler).'".');
		if(!$this->handlers->exists($handler))
			throw new \OutOfBoundsException('Handler "'.$handler.'" does not exist.');
		
		// And loop
		$return = array();
		foreach($this->registry as $name => $value){
			if($value['handler'] == $handler)
				$return[$name] = $value;
		}
		
		// Return the results
		return $return;
	}

	/**
	 * Set the state of an extension.
	 * @param string $name Name of the extension.
	 * @param string $state One of the Extensions::STATE_* constants.
	 * @return bool
	 */
	public function setState($name, $state){
		if(!Extensions::isState($state))
			return false;
		foreach($this->registry as $ext => $value){
			if($ext == $name){
				$this->registry[$ext]['state'] = $state;
				return true;
			}
		}
		return false;
	}
	
	// Override the registry helper functions
	/**
	 * (Internal) Check if a value is a valid value for storage in the registry.
	 * @param mixed $value Value to check.
	 * @return boolean
	 */
	protected function validValue($value){
		// Check if the values exist and their types are correct
		if(!(is_array($value) &&
			(isset($value['path']) && is_string($value['path'])) &&
			(isset($value['type']) && is_string($value['type'])) &&
			(array_key_exists('handler', $value) && (is_string($value['handler']) || is_null($value['handler']))) &&
			(isset($value['state']) && is_string($value['state']) && Extensions::isState($value['state'])) &&
			(isset($value['priority']) && is_integer($value['priority']) && $value['priority'] >= 0 && $value['priority'] <= 100) &&
			(isset($value['dependencies']) && is_array($value['dependencies'])) &&
			(isset($value['info']) && is_array($value['info']))))
				return false;
		
		// Check if the handler exists.
		if(!$this->handlers->exists($value['handler']) && !is_null($value['handler'])){
			throw new \UnexpectedValueException('Handler "'.$value['handler'].'" does not exist!');
		}
		
		// Everything went well.
		return true;
	}
}