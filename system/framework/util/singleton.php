<?php
/**
 * Contains the singleton base interface and dynamic implentation
 * 
 * @package		Quark-Framework
 * @version		$Id: singleton.php 47 2012-10-23 20:16:51Z Jeffrey $
 * @since		June 23, 2011
 * @author		Jeffrey van Harn
 * @copyright	Copyright (C) 2011 Jeffrey van Harn
 * @license		http://gnu.org/licenses/gpl.html GNU Public License Version 3
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

// Some standard pt file statements
namespace Quark\Util;
if(!defined('DIR_BASE')) exit; // Prevent acces to this standalone file

/**
 * Singleton Interface class
 * This class can be used to check if a class is a singleton class
 * @subPackage System
 */
interface Singleton{
	/**
	 * Get the instance of the class this gets called on
	 * @return object
	 */
	public static function getInstance();
	
	/**
	 * Check whether or not the object was already instantiated
	 * @return boolean
	 */
	public static function hasInstance();
}

trait baseSingleton {
	/**
	 * Contains the current objects instance.
	 * Private to prevent serialization
	 * @var Object
	 */
	private static $_instance;
	
	/**
	 * Protected Singleton Constructor.
	 * Standard protected, but not final. So if the object needs to be
	 * instantiated once with parameters, it can overwrite and call {@see registerInstance()}
	 */
	//protected function __construct(){}

	/**
	 * Can be called from a method after the document has been initialized to register a custom instance
	 */
	protected function registerInstance($inst){
		self::$_instance = $inst;
	}
	
	/**
	 * Get the instance of the object
	 * Return the instance of the Singleton class this gets called on, or throws
	 * an RuntimeException if it fails to.
	 * @return object|boolean Object's instance, or false on error.
	 */
	public static function getInstance(){
		if(isset(self::$_instance))
			return self::$_instance;
		else{
			try{
				$obj = new self;
				self::$_instance = $obj;
				return $obj;
			}catch(Exception $e){
				return false;
			}
		}
	}
	
	/**
	 * Check whether or not the object was already instantiated
	 * @return boolean
	 */
	public static function hasInstance(){
		return (self::$_instance == null);
	}
	
	/**
	 * Prevent cloning singleton classes
	 */
	final private function __clone(){}
	
	/**
	 * Prevent waking up singleton classes
	 */
	final private function __wakeup(){}
}

/**
 * Dynamic base Singleton class
 * Automaticly makes the getInstance function available to extending classes.
 * @subpackage System
 */
abstract class dynamicSingleton implements Singleton{
	/**
	 * Contains all instances of all extending singleton objects
	 * Private to prevent serialization.
	 * @var Array
	 */
	private static $_instances = array();
	
	/**
	 * Protected Singleton Constructor
	 * Standard protected, but not final. So if the object needs to be
	 * instantiated once with parameters, it can overwrite and call {@see registerInstance()}
	 */
	protected function __construct(){}

	/**
	 * Can be called from constructor to register the current instance
	 * @return Bool If registring was succesfull, or if it already existed
	 */
	protected function registerInstance(){
		// Get the classpath
		$classpath = self::getClassPath();

		// Check if it already exists
		if(isset(self::$_instances[$classpath])) return false;

		// Register and return true;
		self::$_instances[$classpath] = $this;
		return true;
	}

	/**
	 * Get the callable classpath of the called class.
	 * @return String The callable classpath
	 */
	protected static function getClassPath(){
		// Get the called class
		$class = get_called_class();

		// Get the classpath
		if(!__NAMESPACE__) return __NAMESPACE__.'\\'.$class;
		else return $class;
	}

	/**
	 * Get the instance of the object
	 * Return the instance of the Singleton class this gets called on, or throws
	 * an RuntimeException if it fails to.
	 * @return object|boolean Instance of the object it's called on
	 */
	public static function getInstance(){
		// Get the classpath
		$classpath = self::getClassPath();

		// Return the Object
		if(isset(self::$_instances[$classpath]))
			return self::$_instances[$classpath];
		else{
			try{
				$obj = new $classpath;
				self::$_instances[$classpath] = $obj;
				return $obj;
			}catch(Exception $e){
				return false;
			}
		}
	}
	
	/**
	 * Check whether or not the object was already instantiated
	 * @return boolean
	 */
	public static function hasInstance(){
		// Get the classpath
		$classpath = self::getClassPath();
		
		// Return whether or not
		return isset(self::$_instances[$classpath]);
	}
	
	/**
	 * Prevent cloning singleton classes
	 */
	final private function __clone(){}
	
	/**
	 * Prevent waking up singleton classes
	 */
	final private function __wakeup(){}
}