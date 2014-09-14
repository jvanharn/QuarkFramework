<?php
// For dev versions only
namespace {
	error_reporting(-1);
	ini_set('display_errors', 1);
}
/************************************************************************
 *   ___                       _
 *  / _ \  _   _   __ _  _ __ | | __
 * | | | || | | | / _` || '__|| |/ /   ______
 * | |_| || |_| || (_| || |   |   <   |______|
 *  \__\_\ \__,_| \__,_||_|   |_|\_\
 *         _____                                                    _
 *        |  ___|_ __  __ _  _ __ ___    ___ __      __ ___   _ __ | | __
 *        | |_  | '__|/ _` || '_ ` _ \  / _ \\ \ /\ / // _ \ | '__|| |/ /
 *        |  _| | |  | (_| || | | | | ||  __/ \ V  V /| (_) || |   |   <
 *        |_|   |_|   \__,_||_| |_| |_| \___|  \_/\_/  \___/ |_|   |_|\_\
 *
 * Copyright (C) 2011-2014 Jeffrey van Harn
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
 ************************************************************************/
/**
 * The Index file of the QuarkNAS administration service
 * 
 * @package		QuarkFramework
 * @author		Jeffrey van Harn
 * @since		June 21, 2011
 * @since		0.0.1
 * @copyright	Copyright (C) 2011 Jeffrey van Harn
 * @license		http://gnu.org/licenses/gpl.html GNU Public License Version 3
 */

/**********************
 * # Define Paths     *
 **********************/
/*namespace {
	// Directory_Separator shortcut
	define('DS', DIRECTORY_SEPARATOR);

	// Set the base path
	define('DIR_BASE', dirname(__FILE__).DS);

	// Set the paths to classes
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
	define('DIR_TEMP', sys_get_temp_dir().DS.'quark'.DS);
}*/

/**********************
 * # Bootstrap System *
 **********************/
/*namespace Quark{
	// Make Bootstrapping the system possible with the loader
	require_once(DIR_SYSTEM.'loader.php');

	// Make sure the circular reference garbage collector is enabled
	if(!gc_enabled()) gc_enable();
	
	// Error Handling and Logging
	import(
		'Framework.Error',
		'Framework.Error.Exception',
		'Framework.System.Log'
	);
}*/

/**********************
 * # Load Application *
 **********************/
/*namespace Quark {
	// Autoload
	Loader::startApplication('QuarkSample');*/
	//Loader::startApplication('QuarkSampleIntro', 'IntroApplication');

	// Manually load the "IntroApplication"
	/*use QuarkSampleIntro\IntroApplication;
	require_once(DIR_APPLICATION.'introapplication.php');
	Loader::registerApplicationAlias('QuarkSampleIntro');
	$application = new IntroApplication();
	Loader::setApplication($application);
	$application->display();*/
//}

/****************************
 * # Quick App Load Example *
 ****************************/
// The below is an example of how to do the above in just  3 lines of code. However, this only works when you use the default configuration and locations of all the directories.
namespace Quark {
	// Set the base path
	define('DIR_BASE', dirname(__FILE__).DIRECTORY_SEPARATOR);

	// Make Bootstrapping the system possible with the loader
	require_once(DIR_BASE.'system'.DIRECTORY_SEPARATOR.'loader.php');

	// Bootstrap Application (Sets the required constants and the Debugmode)
	Loader::bootstrapFramework(true); // Change to false if you want to disable debugmode.

	// Load app
	Loader::startApplication('QuarkSample');
}