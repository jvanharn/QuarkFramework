<?php
/**
 * Bootstrap Column Collection
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		December 15, 2012
 * @copyright	Copyright (C) 2012-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 *
 * Copyright (C) 2012-2013 Jeffrey van Harn
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
namespace Quark\Libraries\Bootstrap;
use Quark\Document\baseCollection,
	Quark\Libraries\Bootstrap;

// Dependencies
\Quark\import(
	'Libraries.Bootstrap.Component',
true);

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Defines an activatable or toggleable component that can be activated by another component like a button, link, or other activatable object.
 *
 * I am aware that this is a neologism, just add it to your IDE's dictionary.
 * @package Quark\Libraries\Bootstrap\Components
 */
interface IActivatable extends IComponent {
	/**
	 * Set the activator object.
	 * When the setActivatable is called at the activator this method
	 * @param IActivator $object
	 * @return bool
	 */
	//public function setActivator(IActivator $object);

	/**
	 * Check if the given activator is already linked to this activatable.
	 * @param IActivator $object
	 * @return bool
	 */
	//public function hasActivator(IActivator $object);

	/**
	 * Should be called by the activator when it start's saving itself.
	 * Allows the activatable to set extra options on the previously received IActivator and should return the data-toggle string for this activatable. (WITHOUT SPACES)
	 * @return string
	 * @access private
	 */
	//public function getActivatableTypeString();

	/**
	 * Allows the activatable to configure it's activator(s) and configure it to do it correctly.
	 * @param IActivator $object
	 * @return void
	 */
	public function configureActivator(IActivator $object);
}

/**
 * Trait baseActivatable
 * @package Quark\Libraries\Bootstrap\Components
 */
trait baseActivatable {
	/**
	 * @var IActivator[]
	 */
	//protected $activators = array();

	/**
	 * Set the activator object.
	 * When the setActivatable is called at the activator this method
	 * @param IActivator $object
	 * @return bool
	 */
	/*public function setActivator(IActivator $object){
		if($object->hasActivatable($this))
			return false;
		$this->activators[] = $object;
		return $object->setActivatable($this);
	}*/

	/**
	 * Check if the given activator is already linked to this activatable.
	 * @param IActivator $object
	 * @return bool
	 */
	/*public function hasActivator(IActivator $object){
		return in_array($object, $this->activators);
	}*/

	/**
	 * Check whether or not this button is currently rigged to activate /anything/.
	 * @return boolean
	 */
	/*public function isActivator(){
		return isset($this->activators[0]);
	}*/
}