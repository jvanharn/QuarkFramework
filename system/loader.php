<?php
/**
 * The Class Loading System
 * 
 * Makes it easy to import/load individual classes, or complete packages.
 * 
 * @package		Quark-Framework
 * @version		$Id: loader.php 75 2013-04-17 20:53:45Z Jeffrey $
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
namespace Quark;

// Prevent individual file access
if(!defined('DIR_BASE'))
	exit('DIR_BASE Constant has to be defined o load the system.');

/**
 * Class/Component Loader
 * 
 * This class loads Core Classes, Helpers or Components that come standard with PageTree.
 * You can either load by component name(Separated with dots) or by filename.
 *
 * Note: All these functions are case-insensitive
 * 
 * @package Quark-Framework
 * @static
 */
class Loader{
	/**
	 * Type/category of include
	 */
	const TYPE_FRAMEWORK = 'framework';
	
	/**
	 * Type/category of include
	 */
	const TYPE_APPLICATION = 'application';
	
	/**
	 * Type/category of include
	 */
	const TYPE_LIBRARY = 'libraries';
	
	/**
	 * Maximal depth of a Component Path
	 */
	const MAX_DEPTH = 5;
	
	/**
	 * The list of loaded classes
	 */
	protected static $loaded = array();
	
	/**
	 * List of aliases for the Application
	 * @var array
	 */
	protected static $aliases = array('main', 'app');
	
	/**
	 * Application object reference
	 * @var \Quark\System\Application\Application
	 */
	protected static $application;
	
	/**
	 * Method to easily load the bare essentials needed to load a application.
	 * @param boolean $debug Whether or not to enable debug mode.
	 */
	public static function bootstrapFramework($debug=false){
		// Check for correct PHP Version
		if(version_compare(PHP_VERSION, '5.4.0') < 0)
			exit('<div style="width:600px;margin: 0 auto"><h1>Unqualified PHP Installation</h1><p>The minimal php version required to run any Quark Based application is PHP 5.4.0, we recommend one of the later bugfix releases though. You are running '.PHP_VERSION.'. Please upgrade or use another host.</p></div>');
		
		// Enable debugmode when needed
		if($debug){
			error_reporting(-1);			// Enables ALL php errors
			ini_set('display_errors', 1);	// Makes sure those errors are displayed
		}
		
		// Make sure the circular reference garbage collector is enabled
		if(!gc_enabled()) gc_enable();
		
		// Define some Shortcut constants
		if(!defined('DS'))
			define('DS', DIRECTORY_SEPARATOR); // Directory_Separator shortcut
		if(!defined('EOL')) // Default end of line character for output
			define('EOL', "\n");
		if(!defined('FILE_EOL'))
			define('FILE_EOL', PHP_EOL);
		
		// Set the default paths if they haven't been set yet
		if(!defined('DIR_SYSTEM')){
			// System paths
			define('DIR_SYSTEM', DIR_BASE.'system'.DS);
			define('DIR_FRAMEWORK', DIR_SYSTEM.'framework'.DS);
			define('DIR_LIBRARIES', DIR_SYSTEM.'libraries'.DS);
			define('DIR_EXTENSIONS', DIR_SYSTEM.'extensions'.DS);
			define('DIR_APPLICATION', DIR_SYSTEM.'application'.DS);
			
			// Set the paths to assets
			define('DIR_ASSETS', DIR_BASE.'assets'.DS);
			define('DIR_BUNDLES', DIR_ASSETS.'bundles'.DS);
			define('DIR_SKINS', DIR_ASSETS.'skins'.DS);

			// Set the paths to data
			define('DIR_DATA', DIR_BASE.'data'.DS);
			define('DIR_LOGS', DIR_DATA.'logs'.DS);
			define('DIR_TEMP', sys_get_temp_dir().'quark'.DS);
		}
		
		// Error Handling and Logging
		import(
			'Framework.Error',				// Framework Error Handling
			'Framework.Error.Exception',	// Framework Unhandled Exception to Error Handler/Converter
			'Framework.System.Log',			// System Logging
			'Framework.Util.Singleton'		// Is needed by a lot of classes, just include it here.
		);
	}
	
	/**
	 * Simple way to start your default application.
	 * 
	 * Searches the DIR_APPLICATION directory for a file like 'application.php'
	 * and then tries to load, and display that application.
	 * @param string $name (Optional) RootNamespace for the application, e.g. 'QuarkHS', or 'MyAppName'
	 * @param string $controller (Optional) The name of the controller to load. Defaults to "Application".
	 */
	public static function startApplication($name=null, $controller='Application'){
		// Check if we have to guess the namespace or not
		if($name == null)
			$before = get_declared_classes();
		
		// Register alias
		self::registerApplicationAlias($name);
		
		// Controller lowercased
		$control_lower = strtolower($controller);
		
		// First try to load the application.php in the app directory
		if(is_file(DIR_APPLICATION.$control_lower.'.php'))
			require_once DIR_APPLICATION.$control_lower.'.php';
		else throw new \RuntimeException('Loader could not find the "'.$control_lower.'.php" file in the application path.');
		
		// Try to find differences if the namespace was not given.
		if($name == null){
			// First try the Application namespace, which is default.
			if(class_exists('\\Application\\'.$controller, false))
				$classname = '\\Application\\'.$controller;
			else{
				$after = get_declared_classes();
				$diff = array_splice($after, count($before));
				
				$chances = array();
				foreach($diff as $class){
					if(stristr(strtolower($class), $control_lower) !== false)
						$chances[] = $class;
				}

				// Check if we found it
				$cnt = count($chances);
				if($cnt == 1)
					$classname = $chances[0];
				else if($cnt > 1){
					foreach(array_reverse($chances) as $class){
						if(strtolower(substr($class, -11)) == $control_lower){
							$classname = $class;
							break;
						}
					}
					if(!isset($classname))
						throw new \RuntimeException('Could not automatically find the class to load.');
				}else
					throw new \RuntimeException('Could not automatically find the class to load. Try to define your application\'s namespace yourself or use the default "Application" base namespace.');
			}
		}else{
			$classname = $name.'\\'.$controller;
			if(!class_exists($classname, false))
				throw new \RuntimeException('Could not find the Application class "'.$classname.'" in the given namespace.');
		}

		try {
			// Create and save reference
			self::$application = new $classname();

			// Display the application
			self::$application->display();
		}catch(\Exception $exception){
			// Handle any thrown exceptions
			if(class_exists('\\Quark\\Error', false))
				Error::prettyPrintErrorMessage(
					'An uncaught exception occurred in the Application that was running, which led to an application abort.',
					$exception->getCode(),
					'An exception occurred in the application.',
					$exception->getMessage(),
					\Quark\Error\Debug::traceToString($exception->getTrace(), defined('EXTENDED_DEBUG') && EXTENDED_DEBUG)
				);
			else
				print((string) $exception);
		}
	}
	
	/**
	 * Set the application object reference.
	 * @param \Quark\System\Application\Application $object
	 */
	public static function setApplication(\Quark\System\Application\Application $object){
		self::$application = $object;
	}
	
	/**
	 * Get the current application reference.
	 * @return \Quark\System\Application\Application
	 * @throws \RuntimeException
	 */
	public static function getApplication(){
		if(is_object(self::$application))
			return self::$application;
		else throw new \RuntimeException('Failed to get the current application: No application was started yet that the Loader is aware of.');
	}
	
	/**
	 * Register an application alias for loading application files.
	 * @param string $alias Alias to add should contain camelcased name (a-Z).
	 * @throws \InvalidArgumentException
	 */
	public static function registerApplicationAlias($alias){
		if(is_string($alias))
			self::$aliases[] = strtolower($alias);
		else throw new \InvalidArgumentException('Alias should be a string.');
	}
	
	/**
	 * Check if a component was already loaded
	 * @param String $classPath The classpath to check
	 * @return Bool Whether or not it was already loaded
	 */
	public static function componentLoaded($classPath){
		return array_key_exists(strtolower($classPath), self::$loaded);
	}
	
	/**
	 * Loads a Class by Component Path
	 * 
	 * Use the structure: Package.Classname
	 * Example component paths:
	 * - System.Extension
	 * - System.*
	 * Will all load the same class
	 * @param string $classPath Component/class path
	 * @return bool Whether or not the loading was successful
	 */
	public static function importComponent($classPath){
		// Check param
		if(!is_string($classPath)) throw new \InvalidArgumentException('Parameter $classPath has to be of type "string", "'.gettype($classPath).'" given.');
		
		// Parse the class path/component path
		if(!empty($classPath))
			$path = self::_parseClassPath($classPath);
		else throw new \InvalidArgumentException('The string given is empty.');

		// Check if it was included already
		if(self::componentLoaded(implode('.', $path))) return true;
		
		// Import a package
		if(end($path) == '*'){
			array_pop($path);
			return self::_loadPackage($path, $path[0]);
		}
		
		// Check the type, and react accordingly
		$loaded = false;
		if($path[0] == self::TYPE_FRAMEWORK || $path[0] == self::TYPE_APPLICATION){
			// Try to include the main component first (Not all packages have main includes so ignore a fail)
			self::_loadComponent(array($path[0], $path[1], $path[1]));
			
			// Try to load the class itself
			$loaded = self::_loadComponent($path);
			
			if(!$loaded) self::_raiseWarning('The class component "'.implode('.',array_slice($path, 2)).'" of package "'.$path[1].'" in the '.$path[0].' failed to load. Have you checked that the whole framework was uploaded, and that all directory and file names are lowercased?');
		}else if($path[0] == self::TYPE_LIBRARY){
			$loaded = self::_loadLibrary($path);
			if(!$loaded) self::_raiseWarning('The Application Utility Library "'.$path[0].'" failed to load. Are you sure you spelled the name correctly, and the library has been uploaded?');
		}
		
		return $loaded;
	}

	/**
	 * Import one or multiple packages at once
	 * @param string|array $packages The package name or an array of package names
	 * @param string $type One of the TYPE_* constants of this class. Namely Application, Utility or Framework
	 * @param boolean $required Whether or not the package is required
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public static function importPackage($packages, $type=self::TYPE_FRAMEWORK, $required=false){
		// Check the arguments
		if(is_string($packages)) $packages = array($packages);
		else if(!is_array($packages)) throw new \InvalidArgumentException('Argument $packages should be of type "String" or "Array", but received "'.gettype($packages).'".');
		else if(!is_string($type)) throw new \InvalidArgumentException('Argument $type should be of type "String", but found "'.gettype($type).'".');
		else if(!is_bool($required)) throw new \InvalidArgumentException('Argument $required should be of type boolean but got "'.gettype($required).'".');
		
		// Var to check if we failed anything
		$failed = false;
		
		// Loop through them
		foreach($packages as $pack){
			// Parse the class path/component path
			if(!empty($pack))
				$path = self::_parsePackagePath($pack);
			else throw new \InvalidArgumentException('The string given is empty.');
			
			// Import the package
			$import = self::_loadPackage($path, $type);
			
			// Check if required
			if($import == false){
				if($required) throw new \RuntimeException('The package "'.$pack.'" does not exist or I could not find it in the Quark Framework. I excepted because the package was required.');
				$failed = true;
			}
		}
		
		// Did all go well?
		return !$failed;
	}
	
	/**
	 * Filter untrusted PHP Class-Paths
	 * 
	 * Sanitize input to only allow a-z, 0-9, underscores and \
	 * @param string $classpath The php-classpath to sanitize
	 * @return string
	 * @access private
	 */
	public static function sanitizeClassPath($classpath){
		$name = '';
		
		// Split the string into separate characters
		$chars = str_split(strtolower($classpath));
		
		// Examine every character
		$allowed = array_merge(range('a','z'), range('0','9'), array('\\','_'));
		foreach($chars as $char){
			if(in_array($char, $allowed)){
				$name .= $char;
			}
		}
		
		// Return the result
		return $name;
	}
	
	/**
	 * Convert a PHP Class Reference to a Loadable Classpath
	 * 
	 * Conversion is mainly applied on the namespaces part.
	 * @param string $classpath The php class name (Function expects a valid classpath, please sanitize it first with {@see sanitizeClassPath})
	 * @return string Class path
	 * @see sanitizeClassPath
	 * @access private
	 */
	public static function convertClassPath($classpath){
		return trim(str_replace('\\', '.', strtolower($classpath)), '.');
	}
	
	/**
	 * Parses and normalizes an ClassPath string
	 * @param string $classPath
	 * @return array
	 */
	private static function _parseClassPath($classPath){
		// Base Parse the string
		$exp = explode('.', strtolower($classPath), self::MAX_DEPTH);
		$cnt = count($exp);
		
		// Import a framework package's main include
		if($cnt == 1){
			if($exp[0] == 'application' || in_array($exp[0], self::$aliases))
				return array(self::TYPE_APPLICATION, 'application');
			else
				return array(self::TYPE_FRAMEWORK, $exp[0], $exp[0]);
		}
		
		// Import just one component from the framework
		else if($cnt == 2){
			if($exp[0] == 'library' || $exp[0] == 'libraries')
				return array(self::TYPE_LIBRARY, $exp[1]);
			else if($exp[0] == 'application' || in_array($exp[0], self::$aliases))
				return array(self::TYPE_APPLICATION, $exp[1]);
			else if($exp[0] == 'framework')
				return array(self::TYPE_FRAMEWORK, $exp[1], $exp[1]);
			else return array(self::TYPE_FRAMEWORK, $exp[0], $exp[1]);// @TODO Overkill? Just make one line of both?
		}
		
		// Import a specific Util or Framework Component
		else if($cnt >= 3){
			$type = array_shift($exp);
			if($type == 'library' || $type == 'libraries'){
				//if(class_exists('\\Quark\\Error', false)) \Quark\Error::raiseWarning('I found a class path that I can accept, but there probably is one or more dot(s) too many in the path: "'.$classPath.'". You can fix this by using only one dot when importing Utility Classes/Packages', 'If you are the developper, please check if your component paths match with the coding guidelines, or enable debugmode.');
				// Although we can parse these, it's not really the best loader to use for your external library, we recommend manually including the necessary files from your lib and/or registering an additional autoloader in there.
				array_unshift($exp, self::TYPE_LIBRARY);
			}else if($type == 'quark' && $exp[0] == 'libraries'){
				// do nothing, this is a fix for autoloading libraries that are using the \Quark\Libraries\LibraryName\* namespace.
			}else if($type == 'framework' || $type == 'quark'){
				array_unshift($exp, self::TYPE_FRAMEWORK);
			}else if($type == 'application' || in_array($type, self::$aliases)){
				array_unshift($exp, self::TYPE_APPLICATION);
			}else return array_merge(array(self::TYPE_FRAMEWORK, $type), $exp);
			return $exp;
		}
	}
	
	private static function _parsePackagePath($packagePath){
		$exp = explode('.', strtolower($packagePath), self::MAX_DEPTH);
		$cnt = count($exp);
		
		if($cnt == 1){
			return array(self::TYPE_FRAMEWORK, $exp[0]);
		}
		
		else if($cnt == 2){
			if($exp[0] == 'library' || $exp[0] == 'libraries')
				return array(self::TYPE_LIBRARY, $exp[1]);
			else if($exp[0] == 'application' || in_array($exp[0], self::$aliases))
				return array(self::TYPE_APPLICATION, $exp[1]);
			else if($exp[0] == 'framework')
				return array(self::TYPE_FRAMEWORK, $exp[1], $exp[1]);
			else return array(self::TYPE_FRAMEWORK, $exp[0], $exp[1]);
		}
		
		else if($cnt >= 3){
			$type = array_shift($exp);
			if($type == 'framework' || $type == 'quark'){
				array_unshift($exp, self::TYPE_FRAMEWORK);
			}else if($type == 'library' || $type == 'libraries'){
				if(class_exists('\\Quark\\Error', false)) \Quark\Error::raiseWarning('I found a class path that I can accept, but there probably is one or more dot(s) too many in the path: "'.$packagePath.'". You can fix this by using only one dot when importing Utility Classes/Packages', 'If you are the developper, please check if your component paths match with the coding guidelines, or enable debugmode.');
				array_unshift($exp, self::TYPE_LIBRARY);
			}else if($type == 'application' || in_array($type, self::$aliases)){
				array_unshift($exp, self::TYPE_APPLICATION);
			}else throw new \DomainException('Tried to parse invalid packagepath: After three or more path components the first must alsways be the path type, which is one of Loader::TYPE_UTILITY, Loader::TYPE_FRAMEWORK or Loader::TYPE_APPLICATION.');
			return $exp;
		}
	}
	
	/**
	 * Load a framework component (Tries to automatically fix case with {@see self::_fixCase})
	 * @param array $classPath Parsed classpath to load.
	 * @param string $basePath The directory to search in.
	 * @return boolean 
	 */
	private static function _loadComponent($classPath, $basePath=null){
		// @TODO Log load component call
		
		// Check $basePath param
		if(empty($basePath))
			$basePath = self::_rootPath($classPath[0]);
		if($basePath === false)
			throw new \RuntimeException('Could not find the root path for inclusion type "'.$classPath[0].'".');
		
		// Guess the path (Do not include the first value, which is the category)
		$path = $basePath.trim(implode(DIRECTORY_SEPARATOR, array_slice($classPath, 1, -1)), '.').DIRECTORY_SEPARATOR;
		
		// Check if it exists and try to correct if wrong
		$filename = end($classPath).'.php';
		if(!is_file($path.$filename)){
			$filename = self::_fixCase($path, $filename);
			if($filename === false) return false;
		}
		
		// Try to Include
		$inc = include_once($path.$filename);
		if($inc !== false){
			// Register the component
			self::$loaded[implode('.', $classPath)] = $path.$filename;
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Loads a complete core-package
	 * 
	 * Loads all the recognizable classes in a package(Core only).
	 * If you want to load more packages in one call use {@see Loader::importPackage()}
	 * @param array $classpath The parsed class path.
	 * @param string $type Type of package, and thus where to look for it.
	 * @return bool
	 */
	private static function _loadPackage($packagepath, $type=self::TYPE_FRAMEWORK){
		// Guess the path
		$rpath = self::_rootPath($type);
		if($rpath === false)
			throw new \RuntimeException('Could not find the root path for inclusion type "'.$type.'".');
		
		$pathBuild = array_slice($packagepath, 1);
		$path = $rpath;
		foreach($pathBuild as $subdir){
			if(!is_dir($path.DS.strtolower($subdir))){
				$fixed = self::_fixCase($path, $subdir);
				if(!$fixed){
					self::_raiseWarning('Package "'.implode('.', $packagepath).'" does not seem to exist, are you absolutely sure? Maybe the framework wasn\'t uploaded correctly, or is still being uploaded.');
					return false;
				}else $path = $path.DS.$fixed;
			}else $path = $path.DS.$subdir;
		}
		
		// Scan the package dir
		$files = scandir($path);
		
		// Make sure the main package file get's loaded first(If it exists)
		self::_loadComponent(array_merge($packagepath, [end($packagepath)]), $rpath);
		
		// Loop through the files
		foreach($files as $file){
			if(is_file($path.$file) && pathinfo($file, PATHINFO_EXTENSION) == 'php'){
				// Build file classpath
				$cpath = implode($packagepath, '.').strtolower(substr($file, 0, -4));
				
				// Try to Include
				$inc = include_once($path.$file);
				if($inc !== false){
					// Register the component
					self::$loaded[$cpath] = $path.$file;
					return true;
				}else{
					return false;
				}
			}
		}
		
		// All went well
		return true;
	}
	
	/**
	 * Loads a quark basic application library.
	 * @param array $classPath The class path to load.
	 * @return bool
	 */
	private static function _loadLibrary($classPath){
		// Check classpath length
		$numparts = count($classPath);
		if($numparts < 2){
			System\Log::message(System\Log::ERROR, 'Unable to load classpath "'.implode('.', $classPath).'" as a library; it only contained 1 (or less) part.');
			return false;
		}
		
		// Determine root file path
		$base = realpath(self::_rootPath($classPath[0])).DIRECTORY_SEPARATOR;
		
		// Guess the path of the library
		$path = $base.$classPath[1].DIRECTORY_SEPARATOR;
		if(!is_dir($path)){
			$path = self::_fixCase($base, $classPath[1]);
			if($path === false){
				System\Log::message(System\Log::ERROR, 'Unable to load classpath "'.implode('.', $classPath).'" as a library; library could not be found in library include path (DIR_LIBRARY).');
				return false;
			}
		}
		
		// When it's only two parts only the lib is defined, we then need to figure out what to load
		if($numparts == 2){
			// Determine if there is a main file to load, or if we will just load everything in the main directory
			if(is_file($path.strtolower($classPath[1]).'.php'))
				return self::_tryIncludeComponentPath($path.strtolower($classPath[1]).'.php', implode('.', $classPath));
			else{
				// include all the files in the main directory
				$directory = new \DirectoryIterator($path);
				$included = false;
				foreach($directory as $file){
					if($file->getExtension() == 'php'){
						self::_tryIncludeComponentPath($path.$file, implode('.', $classPath).'.'.$file->getBasename('php'));
						$included = true;
					}
				}
				if(!$included) System\Log::message(System\Log::NOTICE, 'Failed to include main library files in classpath "'.implode('.', $classPath).'".');
				return $included;
			}
		}else if($numparts > 2){
			// Check if it exists and try to correct if wrong
			$path .= trim(implode(DIRECTORY_SEPARATOR, array_slice($classPath, 2, -1)), '.').DIRECTORY_SEPARATOR;
			$filepath = self::_fixPathCase($path, end($classPath).'.php');
			if($filepath === false){
				System\Log::message(System\Log::ERROR, 'Unable to load classpath "'.implode('.', $classPath).'" as a library; library could not be found in library path variable (DIR_LIBRARY).');
				return false;
			}

			// Try to Include
			return self::_tryIncludeComponentPath($filepath, implode('.', $classPath));
		}
		
		// We should not get here (Why not just throw in an else? - Well, the requirement of the code above this comment is to run ONLY when it has more than two elements in the classpath. So, for readability, and easier bug finding; we did it this way.)
		throw new \LogicException("This exception should not have been thrown, if you have modified any code; try to revert it. Otherwise you could help a bunch by reporting this bug.");
	}
	
	/**
	 * Try to include a file, and when succesfull register the given classpath.
	 * @param string $filepath File system path to a file to include
	 * @param string $classpath Classpath to register when the file inclusion was succesfull.
	 * @return boolean
	 */
	private static function _tryIncludeComponentPath($filepath, $classpath){
		$inc = include_once($filepath);
		if($inc !== false){
			// Register the component
			self::$loaded[(string) $classpath] = $filepath;
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * Tries to find the correct file/path for a given item on systems with case sensitive file-systems.
	 * @param string $path The base path to test on.
	 * @param string $name Name to try and fix the case of.
	 * @return boolean|string
	 */
	private static function _fixPathCase($path, $name){
		if(file_exists($path.$name))
			return $path.$name;
		
		if(is_dir($path)){
			$name = strtolower($name);
			$dir = scandir($path);
			foreach($dir as $item){
				if(strtolower($item) == $name)
					return $path.$item;
			}
		}
		return false;
	}
	
	/**
	 * Tries to fix the case of the class name in such a way that it can be loaded, or fail
	 * 
	 * Note: Only nescesery for Case-Sensitive file-systems. (Like most Unix based systems)
	 * @param string $path The directory where the file should be. (Should end with an slash)
	 * @param string $name The name of the file that we should try to correct.
	 * @param bool   $file Whether or not a file has to be fixed, or a directory name
	 */
	private static function _fixCase($path, $name, $file=false){
		if(is_dir($path)){
			$name = strtolower($name);
			$dir = scandir($path);
			foreach($dir as $item){
				if(!$file || is_file($path.$item)){
					if(strtolower($item) == $name)
						return $item;
				}
			}
		}
		return false;
	}
	
	/**
	 * Return the path of the root component (Utils, Framework or Application)
	 */
	private static function _rootPath($root){
		$root = 'DIR_'.strtoupper($root);
		if(defined($root))
			return constant($root);
		else return false;
	}
	
	/**
	 * Checks if the error reporting classes were already loaded otherwise just throws an runtime exception
	 * @param string $debugMessage The debug message for the error.
	 */
	private static function _raiseWarning($debugMessage){
		if(class_exists('\Quark\Error', false))
			\Quark\Error::raise($debugMessage, 'There was a problem with an include for this script.', E_WARNING);
		else throw new \RuntimeException($debugMessage);
	}
}

/**
 * Shortcut function for {@see Loader::importComponent}
 * @param string $component Component path(s) to load/import
 * @param bool $required Whether or not to exit the application if the component could not be loaded
 * @return bool
 */
function import($component, $required=true){
	// Distile components to load from arguments
	if(is_array($component))		// Multiple components were given in an array
		$comps = $component;
	else if(!is_bool($required)){	// Multiple components were given
		$comps = func_get_args();
		if(is_bool($comps[(count($comps)-1)])) // With a $required argument at the end
			$required = array_pop($comps);
		else $required = true;
	}else							// Only one component was given
		$comps = array($component);
	
	// Loop through components, exit on failure, if required
	foreach($comps as $comp){
		$r = \Quark\Loader::importComponent($comp);
		if($required && !$r) throw new \Exception('Could not load required component: "'.$comp.'"; Import through Loader::importComponent failed.');
	}
	
	// Everything went well
	return true;
}

/**
 * Check if a component was imported (And optionally import it)
 * @see class_exists
 * @param string $component The component to check
 * @param bool $import Whether or not to try and import the component if it was not loaded yet
 * @return bool
 */
function imported($classPath, $import=false){
	$exists = Loader::componentLoaded($classPath);
	if(!$exists && $import){
		return Loader::importComponent($classPath);
	}else return $exists;
}

/**
 * Auto Namespace-aware Loader function
 * 
 * This function loads Framework classes only.
 * It also sanatizes the input, not recommended that you call this function directly(Since it is slower)
 * @param string $classname
 * @return bool
 * @access private
 */
function _ClassLoader($classname){
	// Sanitize input
	$path = Loader::sanitizeClassPath(Loader::convertClassPath($classname));
	
	// Notify the developper that he's doing something wrong
	if(class_exists('\\Quark\\System\\Log', false))
		\Quark\System\logMessage(\Quark\System\Log::WARNING, 'A class is dynamically loaded, you can probably improve performance if you pre-load the class "'.$classname.'" before using it.');
	
	// Try to load the class
	try{
		$import = Loader::importComponent($path);
		if(!$import) \Quark\Error::raiseWarning ('Tried to autoload the class '.$classname.', but failed. Make sure all classes you try to use exist, and are imported using the Quark Loader before trying to use them.', 'Something went wrong whilst trying to autoload a class. Please inform the website admin, if you can.');
		return $import;
	}catch(\Exception $e){
		return false;
	}
}
spl_autoload_register('\\Quark\\_ClassLoader');