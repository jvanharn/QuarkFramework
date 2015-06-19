<?php
/**
 * Basic MVC Application Interface
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		June 18, 2015
 * @copyright	Copyright (C) 2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 *
 * Copyright (C) 2015 Jeffrey van Harn
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
namespace Quark\System\MVC;
use Quark\Protocols\HTTP\IMutableResponse;
use Quark\System\Router\IRoutableRequest;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
\Quark\import(
    'Protocols.HTTP.Response',
    'System.Router.RoutableRequest',
    'System.MVC.*',
    true
);

/**
 * MVC Application Interface
 *
 * The basic definition for a MVC application.
 */
interface IMVC {
    /**
     * @param IRoutableRequest $request
     * @param IMutableResponse $response
     * @return boolean
     */
    public function routeRequest(IRoutableRequest $request, IMutableResponse $response);
}