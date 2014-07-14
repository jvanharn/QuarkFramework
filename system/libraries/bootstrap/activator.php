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
	Quark\Document\Document;
use Quark\Util\Type\InvalidArgumentTypeException;

// Dependencies
\Quark\import(
	'Libraries.Bootstrap.Component',
true);

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Interface IActivator
 *
 * Defines a component that can toggle another element when clicked or is activated in another way (event, etc.).
 * Extends IComponent and IElementDataAttributes classes as these are mostly required with these types of elements.
 * @package Quark\Libraries\Bootstrap\Components
 */
interface IActivator extends IComponent, IElementDataAttributes {
	/**
	 * Check if this activator is linked to the specified activatable.
	 * @param IActivatable $object
	 * @return bool
	 */
	public function hasActivatable(IActivatable $object=null);
}

/**
 * Interface ISingleActivator
 *
 * Activates a single activatable.
 * @package Quark\Libraries\Bootstrap\Components
 */
interface ISingleActivator extends IActivator {
	/**
	 * Set what will happen when the activator is toggling.
	 * @param IActivatable|null $object The activatable to activate or NULL to reset it to activate nothing.
	 * @return bool
	 */
	public function setActivatable(IActivatable $object);
}

/**
 * Interface IMultiActivator
 *
 * Unlike the base IActivator class that is expected to only handle one activator, this type of class can activate multiple components, mostly by having multiple buttons.
 * @package Quark\Libraries\Bootstrap\Components
 */
interface IMultiActivator extends IActivator {
	/**
	 * Set what will happen when the activator is toggling.
	 * @param IActivatable $object The activatable to add.
	 * @return bool
	 */
	public function addActivatable(IActivatable $object);

	/**
	 * Remove a previously registred activator
	 * @param IActivatable $object
	 * @return mixed
	 */
	public function removeActivatable(IActivatable $object);
}

/**
 * Trait baseActivator
 *
 * The basic or reference implementation of the IActivator interface.
 * @package Quark\Libraries\Bootstrap\Components
 */
trait baseSingleActivator {
	use baseElementDataAttributes {
		baseElementDataAttributes::saveDataAttributes as _saveRawDataAttributes;
	}

	/**
	 * @var IActivatable
	 */
	protected $activatable;

	/**
	 * Set what will happen when the activator is toggling.
	 * @param IActivatable $object
	 * @return bool
	 */
	public function setActivatable(IActivatable $object){
		return $this->activatable = $object;
	}

	/**
	 * Check if this activator is already linked to an activatable.
	 * @param IActivatable|null $object
	 * @return bool
	 */
	public function hasActivatable(IActivatable $object=null){
		if(is_null($object))
			return !is_null($this->activatable);
		else
			return $this->activatable === $object;
	}

	/**
	 * Returns the html for all the data attributes that were set on this element.
	 * @param Document $document The document in which this element will be applied.
	 * @param IActivator $self The $this var.
	 * @return string
	 */
	protected function saveDataAttributes(Document $document, IActivator $self){
		if(!empty($this->activatable))
			$this->activatable->configureActivator($self);

		return $this->_saveRawDataAttributes($document);
	}
}

/**
 * Trait baseMultiActivator
 *
 * The basic or reference implementation of the IActivator interface.
 * @package Quark\Libraries\Bootstrap\Components
 */
trait baseMultiActivator {
	use baseElementDataAttributes {
		baseElementDataAttributes::saveDataAttributes as _saveRawDataAttributes;
	}

	/**
	 * @var IActivatable[]
	 */
	protected $activatables = array();

	/**
	 * Set what will happen when the activator is toggling.
	 * @param IActivatable $object
	 * @return bool
	 */
	public function setActivatable(IActivatable $object){
		if(is_null($object)){
			$this->activatables = array();
			return true;
		}else
			return $this->addActivatable($object);
	}

	/**
	 * Set what will happen when the activator is toggling.
	 * @param IActivatable $object
	 * @return bool
	 */
	public function addActivatable(IActivatable $object){
		if(!empty($object)){
			$this->activatables[] = $object;
			return true;
		}else return false;
	}

	/**
	 * Check if this activator is already linked to an activatable.
	 * @param IActivatable $object
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 * @return bool
	 */
	public function hasActivatable(IActivatable $object=null){
		if(is_null($object))
			return isset($this->activatables[0]);
		else if(is_object($object))
			return in_array($object, $this->activatables);
		else throw new InvalidArgumentTypeException('object', 'IActivatable', $object);
	}

	/**
	 * Configure the current class using the set activatables.
	 */
	protected function configureUsingActivatables(){
		foreach($this->activatables as $activatable){
			/** @noinspection PhpParamsInspection */
			$activatable->configureActivator($this);
		}
	}

	/**
	 * Returns the html for all the data attributes that were set on this element.
	 * @param Document $document The document in which this element will be applied.
	 * @return string
	 */
	protected function saveDataAttributes(Document $document){
		$this->configureUsingActivatables();
		return $this->_saveRawDataAttributes($document);
	}
}