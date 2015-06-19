<?php
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
 * The console interfacing file of the QuarkNAS administration service
 *
 * @package		QuarkFramework
 * @author		Jeffrey van Harn
 * @since		November 24, 2014
 * @since		0.0.0
 * @copyright	Copyright (C) 2014-2015 Jeffrey van Harn
 * @license		http://gnu.org/licenses/gpl.html GNU Public License Version 3
 */

namespace Quark{
	use Quark\Bundles\Bundles;

	// Determine if we are indeed in the 'console'
	if(php_sapi_name() == 'cli'){
		// Set the base path
		define('DIR_BASE', dirname(__FILE__).DIRECTORY_SEPARATOR);

		// Make Bootstrapping the system possible with the loader
		require_once(DIR_BASE.'system'.DIRECTORY_SEPARATOR.'loader.php');

		// Get action
		$params = getopt('hvsa:l', ['help', 'verbose', 'silent', 'action::']);
		if($params === false)
			exit('No arguments specified.'.PHP_EOL);

		$silent = isset($params['s']) || isset($params['silent']);
		$verbose = isset($params['v']) || isset($params['verbose']);
		$action = isset($params['action']) ? $params['action'] : (isset($params['a']) ? $params['a'] : null);

		if($verbose && $silent)
			exit('You cannot combine the --verbose and --silent flags.'.PHP_EOL);
		if(empty($action))
			exit('You have to specify an action using the --action or -a flags, or try the --help flag to list all commands available.'.PHP_EOL);

		// Output header.
		if(!$silent)
			echo <<<boundry
 **************************************
 *   ___       Console         _      *
 *  / _ \  _   _   __ _  _ __ | | __  *
 * | | | || | | | / _` || '__|| |/ /  *
 * | |_| || |_| || (_| || |   |   <   *
 *  \__\_\ \__,_| \__,_||_|   |_|\_\  *
 **************************************

boundry;

		// Bootstrap
		Loader::bootstrapConsoleFramework($verbose);

		// Check what the user wants us to do.
		if($action == 'bundles:reload'){ // Update the bundles list AT LEAST ONCE before you run the application
			import('Framework.Bundles');

			if(!$silent) echo PHP_EOL.'Refreshing the locally installed bundles list...';
			//Bundles::updateList(); // Downloads the available (3rd party) bundles that *can be installed*.
			Bundles::_resetInstalledList();
			Bundles::scan(false); // Scan for new *local/already installed* bundles (This HAS to be done before bundles can be used!!)
			if(!$silent) echo "\t\t\x1b[32mDone\x1b[39m.".PHP_EOL.PHP_EOL;
			if(isset($params['l'])){
				foreach(Bundles::listInstalled() as $bundleId){
					$bundle = Bundles::get($bundleId);
					echo PHP_EOL." - \x1b[1m".$bundle->name."\x1b[0m (".$bundle->id.') @ '.$bundle->version.PHP_EOL;
					if(empty($bundle->description))
						echo "\tDescription: ".$bundle->description.PHP_EOL;
					echo "\tResources:".PHP_EOL;
					foreach($bundle->resources as $type => $resources){
						if(empty($resources)) continue;
						echo "\t\t+ ".$type.PHP_EOL;
						foreach($resources as $res => $attr){
							echo "\t\t\t- ".$res.PHP_EOL;
						}
					}
					//var_dump($bundle);
				}
			}
		}else{
			echo PHP_EOL.'No command available by that name.'.PHP_EOL;
		}
	}else exit('Quark Console(.php) is only reachable via the CLI.'.PHP_EOL);
}