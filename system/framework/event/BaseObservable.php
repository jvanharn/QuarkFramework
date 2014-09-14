<?php
/**
 * Observer Pattern - Observable Event Interface
 * 
 * @package		Quark-Framework
 * @version		$Id: BaseObservable.php 69 2013-01-24 15:14:45Z Jeffrey $
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
use Quark\Error;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Observable Base Implementation
 * 
 * This is an object that can be listened to by Observer Objects. It notify's it's observers if something happens, which can then modify data from the observable, or react on events within it.
 * 
 * If you wonder why the SPL isn't used, it is because it doesn't allow for arguments to be passed to listener's (or observers).
 * @see Observer
 */
trait baseObservable{
	/**
	 * Contains all the registered/attached observers
	 * @var array
	 */
	protected $_observers = array();
	
	/**
	 * Attach an Observer to an Observable object
	 * @param Observer $observer An observer to attach (Must implement the Observer Interface)
	 * @param int $event The bitmask of the events to listen for. (0 or Observable::EVENT_ALL means it will listen for every event)
	 * @return bool
	 */
	public function attachObserver(Observer &$observer, $event=Observable::EVENT_ALL){
		array_push($this->_observers, array($observer, $event));
	}
	
	/**
	 * Detach an Observer from an Observable object
	 * @param Observer $observer An observer to detach (It will remove all the registred Observers for this object)
	 * @return bool
	 */
	public function detachObserver(Observer &$observer){
		foreach($this->_observers as $key => $subject){
			if($subject[0] == $observer) unset($this->_observers[$key]);
		}
		return true;
	}

	/**
	 * Notify the observers about an event that occurred
	 * @param int $event The event to fire(One event at a time)
	 * @param array $arguments Arguments to pass to the Observers
	 * @throws \InvalidArgumentException
	 * @return bool If one of the observers returns false, returns false. Otherwise always true.
	 */
	public function notifyObservers($event, array $arguments=array()){
		// Check the parameters
		if(!is_int($event)) throw new \InvalidArgumentException('The parameter $event should be of type Integer, but got "'.gettype($event).'".');
		if(is_null($arguments)) $arguments = array();
		
		// Whether or not we where successful (If all the observers executed successfully)
		$success = true;
		
		// Loop over the observers
		/** @var $observer Observer[] */
		foreach($this->_observers as $observer){
			if($event & $observer[1]){
				if($observer[0]->notify($this, $event, $arguments) === false)
					$success = false;
			}
		}
		
		// Return whether we where successful
		return $success;
	}

	/**
	 * Notify the observers about an event that occurred, and give them the opportunity to adjust/change/modify the arguments given
	 * @param int $event The event to fire(One event at a time)
	 * @param array $arguments Arguments to pass to the Observers(And get returned)
	 * @throws \UnexpectedValueException
	 * @throws \InvalidArgumentException
	 * @return array|bool The result is the modified version of the original argument list.
	 */
	public function notifyObserversModifyParams($event, array $arguments=array()){
		// Check the parameters
		if(!is_int($event)) throw new \InvalidArgumentException('The parameter $event should be of type Integer, but got "'.gettype($event).'".');
		if(empty($arguments)) throw new \InvalidArgumentException('The parameter $arguments should be non-empty.');
		
		// Loop over the observers
		$count = count($arguments);
		/** @var $observer Observer[] */
		foreach($this->_observers as $observer){
			if($event & $observer[1]){
				$result = $observer[0]->notify($this, $event, $arguments);
				
				// Check if something went wrong
				if($result === false){
					Error::raise('An Observer::notify() method from the class "'.get_class($observer[0]).'" returned false unexpectedly.. I ignored it and went on with the rest of the Observers. Hope nothing went wrong..', 'Something went wrong with some internal events. Probably a plugin or something, the webmaster will look into it.', E_WARNING);
					continue;
				}else if(!is_array($result)) throw new \UnexpectedValueException('The Observer::notify() method from the class "'.get_class($observer[0]).'" should return a modified version of the argument array, of equal length. But did not return an array at all.');
				else if(count($result) != $count) throw new \UnexpectedValueException('The Observer::notify() method from the class "'.get_class($observer[0]).'" did not meet specifications: The length of the result is not the same as the length of the argument list.');
				
				// Success, save results
				else $arguments = $result;
			}
		}
		
		// Return whether we where successful
		return $arguments;
	}
}