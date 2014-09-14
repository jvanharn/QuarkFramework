<?php
/**
 * Observer Pattern - Observable Event Interface
 * 
 * @package		Quark-Framework
 * @version		$Id: Observable.php 69 2013-01-24 15:14:45Z Jeffrey $
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
 * Observable Interface
 * 
 * This is an object that can be listened to by Observer Objects. It notify's it's observers if something happens, which can then modify data from the observable, or react on events within it.
 * 
 * If you wonder why the SPL isn't used, it is because it doesn't allow for arguments to be passed to listener's (or observers).
 * @see Observer
 */
interface Observable{
	/**
	 * Constant defining every event
	 */
	const EVENT_ALL = 0;
	
	/**
	 * Attach an Observer to an Observable object
	 * @param Observer $observer An observer to attach (Must implement the Observer Interface)
	 * @param int $event The bitmask of the events to listen for. (0 or Observable::EVENT_ALL means it will listen for every event)
	 * @return bool
	 */
	public function attachObserver(Observer &$observer, $event=self::EVENT_ALL);
	
	/**
	 * Detach an Observer from an Observable object
	 * @param Observer $observer An observer to detach (It will remove all the registered Observers for this object)
	 * @return bool
	 */
	public function detachObserver(Observer &$observer);
	
	/**
	 * Notify the observers about an event that occurred
	 * @param int $event The event to fire(One event at a time)
	 * @param array $arguments Arguments to pass to the Observers
	 * @return bool If one of the observers returns false, returns false. Otherwise always true.
	 */
	public function notifyObservers($event, array $arguments=array());
}