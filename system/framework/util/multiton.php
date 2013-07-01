<?php
/**
 * Contains the mulititon base interface and a simple implementation.
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

// Prevent acces to this standalone file
if(!defined('DIR_BASE')) exit;

/**
 * Multiton Pattern Interface.
 * 
 * Design pattern similar to the singleton, which allows only one instance of a
 * class to be created. The multiton pattern expands on the singleton concept to
 * manage a map of named instances as key-value pairs.
 * 
 * This implementation is very simple, and Quark specific. Where most implementations require you to
 */
interface Multiton {
	/**
	 * The default instance name.
	 */
	const DEFAULT_NAME = 'default';
	
	/**
	 * Get a instance of this class.
	 * @return \Quark\Util\Multiton
	 */
	public static function getInstance($name=self::DEFAULT_NAME);
	
	/**
	 * Check if there is an instance with the given name.
	 * @param string $name Instance name.
	 * @return boolean
	 */
	public static function hasInstance($name=self::DEFAULT_NAME);
	
	/**
	 * Create a new class instance with the given name.
	 * @param string $name Create a instance with the given name.
	 * @param mixed $arg1 Other instantiation arguments.
	 */
	//public static function createInstance($name=self::DEFAULT_NAME);
}

/**
 * Base Multiton Implementation
 * 
 * This portable implementation of the multiton pattern is easy to use, and even
 * easier to implement. It uses class reflection to instantiate the class that
 * uses this trait.
 * However due to this class using reflection, it is also strongly encouraged to
 * create /your own/ implementation of the multiton pattern, vs using this trait
 * as a basis. This makes for faster code, more consistency and working code
 * completion that tells you something interesting on how to call/instantiate
 * your class.
 * @deprecated Use is discouraged but implementation will stay for at least the first major version (v1.x.x series).
 */
trait baseMultiton {
	protected static $_instances = array();
	
	/**
	 * Get a instance of this class.
	 * 
	 * Can be instantiated with arguments if you want to automatically make a
	 * new instance with the given name.
	 * @return \Quark\Util\Multiton
	 */
	public static function getInstance($name=self::DEFAULT_NAME){
		if(!isset(self::$_instances[$name]))
			return call_user_func_array(['self', 'createInstance'], func_get_args());
		return self::$_instances[$name];
	}
	
	/**
	 * Check if there is an instance of this multiton with the given name.
	 * @param string $name Instance name.
	 * @return boolean
	 */
	public static function hasInstance($name=self::DEFAULT_NAME){
		return isset(self::$_instances[$name]);
	}
	
	/**
	 * Create a new class instance with the given name.
	 * 
	 * When the class already exists it overwrites that named instance.
	 * @param string $name Create a instance with the given name.
	 * @param mixed $arg1 Other instantiation arguments.
	 * @param mixed $arg2 Etc.
	 * @return \Quark\Util\Multiton
	 */
	public static function createInstance($name=self::DEFAULT_NAME){
		try {
			$class = new ReflectionClass(get_called_class());
			$arguments = func_get_args();
			array_shift($arguments);
			self::$_instances[$name] = $class->newInstanceArgs($arguments);
			return self::$_instances[$name];
		}catch(ReflectionException $e){
			throw new \BadMethodCallException("Unable to instantiate this multiton class: Either an invalid number of arguments were given, or the constructor was inaccessible.", E_ERROR, $e);
		}
	}
}