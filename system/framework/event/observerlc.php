<?php
/**
 * Observer Pattern - Observer Event Interface
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <jeffrey at lessthanthree.nl>
 * @since		Oktober 23, 2011
 * @copyright	Copyright (C) 2011-2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
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