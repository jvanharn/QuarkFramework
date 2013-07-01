<?php
/**
 * Observer Pattern - Observer Event Interface
 * 
 * @package		Quark-Framework
 * @version		$Id: Observer.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		Oktober 23, 2011
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
namespace Quark\Event;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Observer Interface
 * 
 * This is an object that can listen for Events from an observable object.
 * 
 * If you wonder why the SPL isn't used, it is because it doesn't allow for arguments to be passed to listener's (or observers).
 * @see Observable
 */
interface Observer{
	/**
	 * The function that is called if the implementing class is called when an event is dispatched/fired
	 * @param Observable $server The server object from which the event came.
	 * @param int $eventType The event that was fired (Class constant)
	 * @param array $arguments An optional list of arguments
	 * @return mixed If the event enables you to adjust a variable or something similar, you *MUST* return that. If not just return void.
	 */
	public function notify(Observable $server, $eventType, array $arguments);
}