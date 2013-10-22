<?php
if(php_sapi_name() != 'cli-server')
	exit('This file is only intended for the PHP 5.4+ cli-server.');
else define('QUARK_SERVER_MODE', true);
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
 * The Router file/entry point for launching QuarkHS with the PHP CLI-webserver
 * 
 * Since PHP 5.4 the PHP-CLI executable has a builtin webserver. This php file
 * makes it possible to launch QuarkHS as a small footprint application, hosted
 * by this server inside the php runtime. This is especially useful for
 * low-powered devices.
 *
 * Whilst executing in this CLI-server this file will act as the _router_.
 * 
 * WARNING!	If you use QuarkHS this way, please do not expose the server
 *			directly to the internet! This can be dangerous, as it was intended 
 *			as a development server.
 * 
 * Run command: php -S 0.0.0.0:80 -t /path/to/quark /path/to/quark/server.php
 * 
 * @package		QuarkHS
 * @version		$Id: server.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn
 * @since		March 6, 2012
 * @since		0.0.1
 * @copyright	Copyright (C) 2011 Jeffrey van Harn
 * @license		http://gnu.org/licenses/gpl.html GNU Public License Version 3
 */

// Process the URI


// Let resource files pass through
