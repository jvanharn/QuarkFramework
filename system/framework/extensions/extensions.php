<?php
/**
 * Loads all the extensions for the Quark framework
 * 
 * This class communicates and uses the Handler objects to load all kinds of
 * different Quark Framework extensions for the application.
 * 
 * States explained:
 * Enabled/Disabled	= The extension get's loaded or not.
 * loaderror		= The extension was enabled, but it's handler/supplier (no longer) accepts it.
 * new				= The extension was not in the ini before, and was newly added
 * 
 * By default, if it finds an new extension, it will set them to a "new" state.
 * In most use cases (For Quark HS) this is preferred behaviour, this could
 * however suddenly change in any future release.
 * 
 * @package		Quark-Framework
 * @version		$Id: extensions.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		March 4, 2012
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

// Import Namespaces
use Quark\Error;
use Quark\Util\baseSingleton;
use Quark\Util\Singleton;
use \Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Load dependencies
\Quark\import(
	'Framework.Extensions.Handler',
	'Framework.Extensions.Supplier',
	'Framework.Extensions.HandlerRegistry',
	'Framework.Extensions.ExtensionRegistry',
	
	'Framework.Util.Type.Check',
	'Framework.Util.Type.Type',
true);

/**
 * Extension management and loading class
 * 
 * This class communicates and uses the Handler objects to load all kinds of
 * different Quark Framework extensions for the application.
 */
class Extensions implements Singleton{
	use baseSingleton;
	
	// Extension States
	/**
	 * Extension State: Enabled
	 * 
	 * The extension is enabled, and will be loaded.
	 */
	const STATE_ENABLED		= 'enabled';
	
	/**
	 * Extension State: New 
	 * 
	 * The extension was found in the extension directory, but there was no ini-
	 * entry found in the store. Thus was automatically set to "new".
	 */
	const STATE_NEW			= 'new';
	
	/**
	 * Extension State: Disabled
	 * 
	 * The extension was explicitly disabled by the user.
	 */
	const STATE_DISABLED	= 'disabled';
	
	/**
	 * Extension State: No Handler Found
	 * 
	 * Mostly new extension for which we could not find a suitable handler.
	 */
	const STATE_NOHANDLER	= 'nohandler';
	
	/**
	 * Extension State: Unmet Dependency
	 * 
	 * The extension has a dependency that was unresolvable during loading, and
	 * was therefore automatically disabled.
	 */
	const STATE_DEPENDENT	= 'dependent';
	
	/**
	 * Extension State: Loading Error
	 * 
	 * An error occured during the loading of the extension, and was therefore 
	 * automatically disabled.
	 */
	const STATE_LOADERROR	= 'loaderror';
	
	// Populate Modes
	/**
	 * Defines Population Mode Re-Fill
	 * 
	 * When this mode is activated the population will be empty'd and entirely
	 * refilled, using a BuildingSupplier.
	 */
	const POPULATE_REFILL	= 1;
	
	/**
	 * Defines Population Mode Update
	 * 
	 * When this mode is activated the current population will be updated using 
	 * a BuildingSupplier.
	 * (If it is not populated yet, it will first try from cache)
	 */
	const POPULATE_UPDATE	= 2;
	
	/**
	 * Defines Population Mode Cached
	 * 
	 * When this mode is activated the population will be loaded from a 
	 * CachingSupplier, which increases performance.
	 */
	const POPULATE_CACHED	= 3;
	
	/**
	 * Defines Population Mode Automatic
	 * 
	 * When this mode is activated the population will first be filled from a
	 * CachingSupplier, if these suppliers are unavailable, we will fill from a
	 * BuildingSupplier.
	 */
	const POPULATE_AUTO		= 4;
	
	// Protected Class Variables
	/**
	 * Extensions that are available (Not yet loaded)
	 * @var \SplPriorityQueue
	 */
	protected $enabled;
	
	/**
	 * Extensions that have currently been loaded through the extensions class.
	 * @var array
	 */
	protected $loaded;
	
	/**
	 * Loaded Extensions
	 * @var \Quark\Extensions\ExtensionRegistry
	 */
	protected $extensions;
	
	/**
	 * Loaded Extension Handlers
	 * @var \Quark\Extensions\HandlerRegistry
	 */
	protected $handlers;
	
	/**
	 * List of suppliers in order of importance
	 * @var array
	 */
	protected $suppliers;
	
	/**
	 * Initializes the registry's and scans for enabled extensions 
	 */
	protected function __construct(){
		// Initiate Registry's
		$this->handlers = new HandlerRegistry();
		$this->extensions = new ExtensionRegistry($this->handlers);
		
		$this->loaded = array();
	}
	
	/**
	 * Set the Extension List suppliers to their default values.
	 * 
	 * The default suppliers are the JSONCachedSupplier and the DiskBuildingSupplier.
	 */
	public function setDefaultSuppliers(){
		$this->suppliers = array(
			new Suppliers\JSONCachingSupplier(),
			new Suppliers\DiskBuildingSupplier()
		);
	}
	
	/**
	 * Set the Extension List suppliers to the given list of suppliers.
	 * @param array $suppliers 0-indexed array containing the supliers to use.
	 * @return boolean
	 */
	public function setSuppliers($suppliers){
		// Check parameter
		if(!is_array($suppliers))
			return false;
		else if(count($suppliers) <= 0)
			return false;

		// Check array contents
		foreach($suppliers as $sup){
			if(!is_object($sup) || (is_object($sup) &&!($sup instanceof Supplier)))
				return false;
		}

		// Set it.
		$this->suppliers = $suppliers;
		return true;
	}
	
	/**
	 * Scan for loadable/enabled extensions using the extension suppliers
	 * @param integer $mode A POPULATE_* constant from this class.
	 * @return boolean
	 * @throws \UnexpectedValueException When the suppliers array was not initialized yet
	 * @throws \DomainException Invalid $mode value.
	 */
	public function populate($mode=self::POPULATE_AUTO){
		if(is_null($this->suppliers))
			throw new \UnexpectedValueException('The suppliers array with suppliers to use to populate the extension registry was empty. Please fix this by initializing the supplier array with "setSuppliers" and "setDefaultSuppliers" respectively.');
		
		if($mode == self::POPULATE_REFILL || $mode == self::POPULATE_UPDATE){
			foreach($this->suppliers as $supplier){
				if($supplier instanceof BuildingSupplier){
					if($supplier->available()){
						if($mode == self::POPULATE_REFILL)
							$supplier->fill($this);
						else if($mode == self::POPULATE_UPDATE)
							$supplier->update($this);
						return true;
					}
				}
			}
			
			// We did not find anything
			Error::raiseWarning('Could not populate the ExtensionRegistry, I could not find an (available) BuildingSupplier for the extension refill.');
			return false;
		}else if($mode == self::POPULATE_CACHED){
			foreach($this->suppliers as $supplier){
				if($supplier instanceof CachingSupplier){
					if($supplier->available()){
						$supplier->fill($this);
						return true;
					}
				}
			}
			
			// We did not find anything
			Error::raiseWarning('Could not populate the ExtensionRegistry, I could not find an (available) CachingSupplier for the extension list population.');
			return false;
		}else if($mode == self::POPULATE_AUTO){
			foreach($this->suppliers as $supplier){
				if($supplier instanceof CachingSupplier){
					if($supplier->available()){
						$supplier->fill($this);
						return true;
					}
				}
			}
			
			foreach($this->suppliers as $supplier){
				if($supplier instanceof BuildingSupplier){
					if($supplier->available()){
						$supplier->update($this);
						return true;
					}
				}
			}
			
			// We did not find anything
			Error::raiseWarning('Could not populate the ExtensionRegistry, I could not find an (available) Caching/BuildingSupplier for the extension list population.');
			return false;
		}else throw new \DomainException('Parameter $mode defined did not adhere to the defined mode domain. Please use a POPULATE_* constant for consistency.');
	}
	
	/**
	 * Caches the current extension list with the suppliers.
	 */
	public function cache(){
		if(count($this->extensions) <= 0)
			return true;

		$found = false;
		foreach($this->suppliers as $supplier){
			if($supplier instanceof CachingSupplier){
				if($supplier->cacheable()){
					$supplier->cache($this);
					$found = true;
				}
			}
		}
		return $found;
	}
	
	/**
	 * Load an extension using it's handler.
	 * @param string $name Extension to load.
	 * @param boolean $ignore_state Whether or not to ignore if the extension is not enabled, and force the load.
	 * @return boolean
	 */
	public function load($name, $ignore_state=false){
		if($this->extensions->exists($name)){
			$entry = $this->extensions->get($name);
			
			if($entry['state'] == self::STATE_ENABLED || $ignore_state){
				if(!$this->handlers->exists($entry['handler']))
					return false;
				
				$handler = $this->handlers->getObject($entry['handler']);
				if($handler->load($name, $entry['path'])){
					$this->loaded[] = $name;
					return true;
				}else return false;
			}else return false;
		}else return false;
	}
	
	/**
	 * Load extensions by a handler name
	 * @param string $handler
	 * @return boolean 
	 */
	public function loadByHandler($handlername){
		if(!$this->handlers->exists($handlername)) return false;
		$handler = $this->handlers->getObject($handlername);
		
		if(is_null($this->enabled))
			$this->fillQueue();
		
		$return = true;
		$this->enabled->top();
		while($this->enabled->valid()){
			$this->enabled->next();
			$name = $this->enabled->current();
			$entry = $this->extensions->get($name);
			if($entry['state'] == self::STATE_ENABLED && $entry['handler'] == $handlername){
				if(!$handler->load($name, $entry['path'])){
					$return = false;
					Error::raiseWarning('Failed to load extension "'.$name.'" while trying to load all extensions with handler "'.$handlername.'".');
				}else $this->loaded[] = $name;
			}
		}
		return $return;
	}
	
	/**
	 * Loads all enabled extensions
	 * @return boolean 
	 */
	public function loadAll(){
		if(is_null($this->enabled))
			$this->fillQueue();
		
		if($this->enabled->isEmpty())
			return true;
		
		$return = true;
		$this->enabled->rewind();
		while($this->enabled->valid()){
			$name = $this->enabled->current();
			$entry = $this->extensions->get($name);
			if(!$this->handlers->exists($entry['handler']))
				Error::raiseWarning('Failed to load handler "'.$entry['handler'].'" for extension "'.$name.'".');
			$handler = $this->handlers->getObject($entry['handler']);

			if(!$handler->load($name, $entry['path'])){
				$return = false;
				Error::raiseWarning('Failed to load extension "'.$name.'" while trying to load all extensions.');
			}else $this->loaded[] = $name;

			$this->enabled->next();
		}
		return $return;
	}
	
	/**
	 * Check if a extension is loaded
	 * @param string $name
	 * @return boolean
	 * @throws \InvalidArgumentException 
	 */
	public function loaded($name){
		// Check param
		if(!is_string($name)) throw new \InvalidArgumentException('Parameter $name should be of type "string", but found "'.gettype($name).'".');
		
		// Return
		return in_array($name, $this->loaded);
	}
	
	// Extension management methods
	/**
	 * Add/register new extension.
	 *
	 * Registers a new extension in the registry, and automatically tries to find a suitable handler.
	 * @param string $path Path of the extension to try and load.
	 * @param string $name (Optional) Name to give, otherwise the name is distilled from the path.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 * @return boolean
	 */
	public function register($path, $name=null){
		if(!is_string($path))
			throw new InvalidArgumentTypeException('path', 'string', $path);
		if(!is_string($name) && !is_null($name))
			throw new InvalidArgumentTypeException('name', 'string or null', $name);
		
		if(is_null($name))
			$name = self::findName($path);
		$type = self::findType($name);
		
		// First find a handler
		$handlers = $this->handlers->getByType($type);
		if(!$handlers)
			return $this->registerWithUnknownHandler($name, array(
				'path'		=> $path,
				'type'		=> $type
			));
		
		$handler = null;
		$handlerName = null;
		foreach($handlers as $eh){
			$handler = $this->handlers->getObject($eh);
			$handlerName = $eh;
			if($handler->test($path) === true)
				break;
			else $handler = null;
		}
		
		if($handler == null){
			// No handler was found
			return $this->registerWithUnknownHandler($name, array(
				'path'		=> $path,
				'type'		=> $type
			));
		}else{
			// Get info
			$info = $handler->info($path);
			if(!is_array($info))
				return false; // Handler could not retrieve the info
			
			// Handler found
			return $this->extensions->register($name, array(
				'path'			=> $path,
				'type'			=> $type,
				'handler'		=> $handlerName,
				'state'			=> self::STATE_NEW,
				'priority'		=> $handler->defaultPriority(),
				'dependencies'	=> $handler->dependencies($path),
				'info'			=> $info
			));
		}
	}

	/**
	 * Remove an extension.
	 * @param string $name Name of the extension to remove.
	 * @param boolean $physically Whether or not to also try to remove the directory and all it's contents.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function remove($name, $physically=false){
		if(!is_string($name))
			throw new InvalidArgumentTypeException('name', 'non-empty string', $name);
		if(!is_bool($physically))
			throw new InvalidArgumentTypeException('physically', 'boolean', $physically);
		
		// Physically Remove from disk
		if($physically){
			$path = $this->extensions->get($name)['path'];
			foreach (scandir($path) as $item) {
				if ($item != '.' || $item != '..')
					unlink($path.DS.$item);
			}
			rmdir($path);
		}
		
		// Un-Register
		$this->extensions->unregister($name);
	}

	/**
	 * Modify the info.
	 * @param string $name Name of the extension.
	 * @param string $key Info key to modify.
	 * @param mixed $value Value for the key.
	 * @throws \InvalidArgumentException
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function modify($name, $key, $value){
		if(!is_string($name))
			throw new InvalidArgumentTypeException('name', 'non-empty string', $name);
		if(!$this->extensions->exists($name))
			throw new \InvalidArgumentException('The given extension name doesn\'t exist.');
		if(!is_null($value))
			throw new InvalidArgumentTypeException('value', 'null', $value);
		
		// Get the old value
		$old = $this->extensions->get($name);
		
		// Set the new value
		$old['info'][$key] = $value;
		
		// Reregister modified version
		$this->extensions->register($name, $old, true);
	}
	
	/**
	 * Check if an extension exists.
	 * @param string $name Name of the extension to check.
	 * @return boolean
	 */
	public function exists($name){
		return $this->extensions->exists($name);
	}
	
	/**
	 * Get the specified property for the given extension.
	 * 
	 * See the {@see \Quark\Extensions\ExtensionRegistry} for info about what properties are available.
	 * @param string $name Name of the extension.
	 * @param string $key Key to retrieve.
	 * @return mixed|null Value of the property or null on non-existent property.
	 */
	public function get($name, $key='info'){
		if($this->extensions->exists($name)){
			$entry = $this->extensions->get($name);
			if(isset($entry[$key])){
				return $entry[$key];
			}else return null;
		}else return null;
	}
	
	/**
	 * Set the specified property for the given extension.
	 * 
	 * See the {@see \Quark\Extensions\ExtensionRegistry} for info about what properties are available.
	 * @param string $name Name of the extension.
	 * @param string $key Key to retrieve.
	 * @param mixed $value New value of the property.
	 * @return boolean
	 */
	public function set($name, $key, $value){
		if($this->extensions->exists($name)){
			$entry = $this->extensions->get($name);
			if(isset($entry[$key])){
				$entry[$key] = $value;
				return $this->extensions->register($name, $entry, true);
			}else return false;
		}else return false;
	}

	/**
	 * Set the state of the given extension.
	 * @param string $name Full extension name.
	 * @param string $state The state of the extension. One of the Extension::STATE_* constants.
	 * @return boolean
	 */
	public function setState($name, $state){
		return $this->extensions->setState($name, $state);
	}
	
	// Static Methods
	/**
	 * Check whether a string is a valid extension state or not.
	 * @param string $state String to check for validity.
	 * @return boolean
	 */
	public static function isState($state){
		return in_array($state, array(self::STATE_ENABLED, self::STATE_DISABLED, self::STATE_NEW, self::STATE_NOHANDLER, self::STATE_DEPENDENT, self::STATE_LOADERROR));
	}
	
	/**
	 * Find the name by parsing a path.
	 * @param string $path Path to parse.
	 * @return string Distilled name.
	 */
	public static function findName($path){
		$pos = strrpos($path, DS);
		if($pos === false){
			$pos = strrpos($path, ' ');
			if($pos !== false)
				return substr($path, $pos+1);
			else return $path;
		}else return substr($path, $pos+1);
	}
	
	/**
	 * Find the type by parsing a name.
	 * @param string $name Name to parse.
	 * @return string|boolean Distilled type or false on failure.
	 */
	public static function findType($name){
		$pos = strrpos($name, '.');
		if($pos === false){
			return false;
		}else return substr($name, $pos+1);
	}
	
	// Registry Methods
	/**
	 * Get the current extension registry
	 * @return \Quark\Extensions\ExtensionRegistry
	 */
	public function getExtensionRegistry(){
		return $this->extensions;
	}
	
	/**
	 * Get the Extension Handler Registry
	 * @return \Quark\Extensions\HandlerRegistry
	 */
	public function getHandlerRegistry(){
		return $this->handlers;
	}
	
	// Protected methods
	/**
	 * Fills the 'enabled' priority queue from the extension registry.
	 */
	protected function fillQueue(){
		$this->enabled = new \SplPriorityQueue();
		
		foreach($this->extensions as $name => $entry){
			if($entry['state'] == self::STATE_ENABLED)
				$this->enabled->insert($name, $entry['priority']);
		}
	}

	/**
	 * Registers a extension with the unknown properties filled with defaults.
	 * @param string $name Extension name
	 * @param array $known Array with at least "path" and "type" props set.
	 * @return bool
	 * @ignore
	 */
	protected function registerWithUnknownHandler($name, $known){
		return $this->extensions->register($name, array_merge(array(
			'handler'		=> null,
			'state'			=> self::STATE_NOHANDLER,
			'priority'		=> 0,
			'dependencies'	=> array(),
			'info'			=> array()
		), $known));
	}
}