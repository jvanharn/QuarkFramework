#!/usr/bin/php
<?php
use Quark\Loader;
use Quark\System\Router\RouteCollection;

/************************************************************************
 *   ____                   _    _   _           _____ 
 *  / __ \                 | |  | \ | |   /\    / ____|
 * | |  | |_   _  __ _ _ __| | _|  \| |  /  \  | (___  
 * | |  | | | | |/ _` | '__| |/ / . ` | / /\ \  \___ \ 
 * | |__| | |_| | (_| | |  |   <| |\  |/ ____ \ ____) |
 *  \___\_\\__,_|\__,_|_|  |_|\_\_| \_/_/    \_\_____/ 
 * 
 * Copyright (C) 2011-2015 Jeffrey van Harn
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
 * This file houses the code necessary to let Quark operate independent from a
 * dedicated http server. You can load it either with the buit-in CLI-server
 * that became available in PHP 5.4+ or using the cli itself. It will then by
 * default operate as an forking http server for those interested.
 *
 * WARNING! HHVM does not curently allow for a pthread like thread implementation
 *          at the time of writing this code. So untill they implement native
 *          threading for standalone/cli scripts and we adopt that technology,
 *          this will not work in HHVM.
 *
 * WARNING!	If you use an application this way, please do not expose the server
 *			directly to the internet! This can be dangerous, as this was
 *          intended for local development purposes only as a development server.
 * 
 * Run command: php -S 0.0.0.0:80 -t /path/to/quark /path/to/quark/server.php
 * 
 * @package		QuarkHS
 * @version		$Id: server.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn
 * @since		November 15, 2014
 * @since		0.0.1
 * @copyright	Copyright (C) 2014-2015 Jeffrey van Harn
 * @license		http://gnu.org/licenses/gpl.html GNU Public License Version 3
 */

// Generic config
$debug = true; // Change to false if you want to disable debugmode.
$address = empty($argv[1]) ? '0.0.0.0' : $argv[1];
$port = empty($argv[2]) ? 8080 : $argv[2];
function _configureRouter(RouteCollection &$router){
    $router->attachRoute(new \Quark\System\Router\StaticRoute(DIR_ASSETS, 'assets')); // Make the assets directory publicly available
    $router->attachRoute(new \Quark\System\Router\CallbackRoute(
        function($request){
            // Check if routable

        },
        function($request, $response){
            // Load app

            Loader::startApplication('QuarkSample');
        }
    ));
}

// Determine the type of server used
$sapi = php_sapi_name();
if($sapi == 'cli-server'){
    define('QUARK_SERVER_MODE', true);
    exit('Not yet implemented.');
}else if($sapi == 'cli'){
    // Set the base path
    define('DIR_BASE', dirname(__FILE__).DIRECTORY_SEPARATOR);

    // Make Bootstrapping the system possible with the loader
    require_once(DIR_BASE.'system'.DIRECTORY_SEPARATOR.'loader.php');

    // Bootstrap Application (Sets the required constants and the Debugmode)
    Loader::bootstrapFramework($debug);

    // Create server
    $server = new \Quark\System\Application\Server\RoutingForkingServer($address, $port);
    _configureRouter($server);
}else exit('Server.php is only reachable via the PHP CLI Server mode of PHP 5.4+ or in standalone CLI mode.');