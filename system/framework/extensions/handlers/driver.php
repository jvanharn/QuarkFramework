<?php
/**
 * Extension handler for database driver extensions.
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <support AT lessthanthree.nl>
 * @since		March 4, 2012
 * @copyright	Copyright (C) 2012 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Extensions\Handlers;

use Quark\Extensions\Handler,
	Quark\Extensions\baseHandler;
use Quark\Util\Config\Config;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Loads Database Drivers
 */
class DriverHandler implements Handler{
	use baseHandler {
		baseHandler::test as baseTest;
	}
	
	/**
	 * Config Mapper Driver Structure
	 * 
	 * On top of the regular config properties this handler requires the properties:
	 * database - The name of the database it supplies a driver for, e.g. mysql or postgre. (Lowercased!)
	 * classname - Fully qualified classname of the main driver class. (E.g. \Quark\Database\Driver\PostgreDriver)
	 * 
	 * Note: if you are implementing an unofficial/alternate database driver, consider using an alternative name to prevent classname collisions like "FastMySQLDriver" or "CMSNAMEMySQLDriver".
	 * This to prevent collisions with official drivers coming out later, or that already have been distributed. If you have implemented a db driver and want to have it distributed email us at team (at) lessthanthree [DOT] nl or join the mailinglist.
	 * Because the procedures for drivers are (just as with handlers) a bit more strict than for regular extensions, we hope to provide some extra consistency and stability in our most core-ish building blocks.
	 * @var array
	 * @access private
	 */
	public static $map = array(
		'type' => Config::DICTIONARY,
		'struct' => [
			'title'			=> false,
			'description'	=> true,
			'version'		=> false,
			'author'		=> false,
			'copyright'		=> true,
			
			'database'		=> false,
			'classname'		=> false,
			
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
			],
			
			'settings' => [
				'type' => Config::COLLECTION,
				'struct' => [
					'type' => Config::PROPERTY,
					'struct' => [
						'index'			=> false,
						'name'			=> true,
						'description'	=> true
					],
					'optional' => true
				],
				'optional' => false
			]
		]
	);
	
	/**
	 * Tests whether a extension path can be loaded by the driver.
	 * @param string $path Path to the extension.
	 * @return boolean
	 */
	public function test($path){
		// Basic tests
		if(!$this->baseTest($path))
			return false;
		
		// Check if the necessary components are there
		if(!is_file($path.'driver.php') || !is_file($path.'query.php') || !is_file($path.'result.php') || !is_file($path.'statement.php'))
			return false;
		
		// Everything went well
		return true;
	}
	
	/**
	 * Loads the driver and registers it's engines.
	 * @param string $name Name of the extension
	 * @param string $path Path to the extension directory
	 * @return boolean
	 */
	public function load($name, $path) {
		// Include the files
		require_once $path.'driver.php';
		require_once $path.'query.php';
		require_once $path.'result.php';
		require_once $path.'statement.php';
		
		// Done
		return true;
	}
	
	/**
	 * Always returns 90
	 * @return int 
	 */
	public function defaultPriority(){
		return 90;
	}
}