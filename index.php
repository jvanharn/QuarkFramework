<?php
// For dev versions only
namespace {
	error_reporting(-1);
	ini_set('display_errors', 1);
}
/************************************************************************
 *   ____                   _    _   _           _____ 
 *  / __ \                 | |  | \ | |   /\    / ____|
 * | |  | |_   _  __ _ _ __| | _|  \| |  /  \  | (___  
 * | |  | | | | |/ _` | '__| |/ / . ` | / /\ \  \___ \ 
 * | |__| | |_| | (_| | |  |   <| |\  |/ ____ \ ____) |
 *  \___\_\\__,_|\__,_|_|  |_|\_\_| \_/_/    \_\_____/ 
 * 
 * Copyright (C) 2011-2012 Jeffrey van Harn
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
 * The Quark NAS system project began when I (Jeffrey) bought myself a nice NAS
 * for storing my videos, photos, music and stuffs. My family always asked me if
 * I had a specific song or film, and on a given moment; I couldn't put up with 
 * it anymore. So: I bought a NAS. Finally having the device put together(It was
 * based on a cheap Atom d5xx MB) I installed FreeNAS. Which didnt do anything
 * because it didn't support my NIC. So I installed the FreeNAS 8 Beta. Which
 * did make the NIC work, but lacked some important features like Transmission.
 * 
 * After failing misserably with FreeNAS and trying every other file-sharing-nas
 * -like distro, I installed Ubuntu Server, and went about writing the softwarez
 * myself. I already had a semi-completed CMS system called PageTree, with which
 * I could make a flying start writing the app in PHP.
 * So if you see a lot of similar coding, you know why. Chances you were
 * wondering are estimated at 0.00001%, just to have informed you, if you do (:
 * 
 * @package		QuarkHS
 * @version		$Id: index.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn
 * @since		June 21, 2011
 * @since		0.0.1
 * @copyright	Copyright (C) 2011 Jeffrey van Harn
 * @license		http://gnu.org/licenses/gpl.html GNU Public License Version 3
 */

/**********************
 * # Define Paths     *
 **********************/
namespace {
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
	define('DIR_TEMPLATES', DIR_ASSETS.'templates'.DS);
	
	// Set the paths to data
	define('DIR_DATA', DIR_BASE.'data'.DS);
	define('DIR_LOGS', DIR_DATA.'logs'.DS);
	define('DIR_TEMP', sys_get_temp_dir().'quark'.DS);
}

/**********************
 * # Bootstrap System *
 **********************/
namespace Quark{
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
}

/**********************
 * # Load Application *
 **********************/
namespace Quark {
	Loader::startApplication('QuarkSample');
}