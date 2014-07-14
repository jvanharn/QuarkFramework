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
	Quark\Document\IElement,
	Quark\Document\Document;

// Dependencies
\Quark\import(
	'Libraries.Bootstrap.Element',
true);

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Basic IComponent
 *
 * Supplies descendants with instance tracking for Id generation.
 */
interface IComponent extends IElement {
	/**
	 * Get this component's (html) identifier.
	 * @return string
	 */
	public function getId();

	/**
	 * Set the component's (html) identifier.
	 * @param null|string $id Null will cause the element to automatically generate an id, a string will cause the id to be the given string.
	 * @return mixed
	 */
	public function setId($id=null);
}

/**
 * Class Component
 * @package Quark\Libraries\Bootstrap\Components
 */
abstract class Component extends BootstrapElement implements IComponent {
	/**
	 * @var int Number of instances this component has, used for id generation.
	 */
	protected static $instances = 0;

	/**
	 * @var string The identifier.
	 */
	protected $id = null;

	/**
	 * Get this component's (html) identifier.
	 * @return string|null
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 * Set the component's (html) identifier.
	 * @param null|string $id Null will cause the element to automatically generate an id, a string will cause the id to be the given string.
	 * @throws \InvalidArgumentException
	 * @return mixed
	 */
	public function setId($id=null){
		if(is_string($id) && !empty($id))
			$this->id = $id;
		else if($id === null)
			$this->id = $this->generateId();
		else throw new \InvalidArgumentException('Expected argument $id to be string or null.');
	}

	/**
	 * Generate a id.
	 * @return string
	 */
	protected function generateId(){
		$fqn = explode('\\', __CLASS__);
		//return 'component-'.strtolower(end($fqn)).'-'.mt_rand(0, 255).'-'.self::$instances++;
		//return 'component-'.strtolower(end($fqn)).'-'.spl_object_hash($this).'-'.self::$instances++;
		return 'component-'.strtolower(end($fqn)).'-'.self::$instances++;
	}
}

/**
 * Interface IElementDataAttributes
 *
 * Defines a component or element that can have it's data- attributes set and/or modified.
 * @package Quark\Libraries\Bootstrap\Components
 */
interface IElementDataAttributes extends IElement {
	/**
	 * Set a HTML5 data-* attribute.
	 * @param string $name The name of the property (!!WITHOUT "data-" prepended.)
	 * @param string $value The string value of the attribute.
	 */
	public function setDataAttribute($name, $value);

	/**
	 * Get the value of  a HTML5 data-* attribute.
	 * @param string $name The name of the property (!!WITHOUT "data-" prepended.)
	 * @return string The string value of the attribute.
	 */
	public function getDataAttribute($name);
}

/**
 * Trait baseElementDataAttributes
 * @package Quark\Libraries\Bootstrap\Components
 */
trait baseElementDataAttributes {
	/**
	 * @var array Map of data attributes
	 */
	protected $dataAttributes = array();

	/**
	 * Set a HTML5 data-* attribute.
	 * @param string $name The name of the property (!!WITHOUT "data-" prepended.)
	 * @param string $value The string value of the attribute.
	 * @return bool True on success false on error (use === false)
	 */
	public function setDataAttribute($name, $value){
		if(!empty($name) && is_string($name) && is_string($value)){
			$this->dataAttributes[$name] = $value;
			return true;
		}else return false;
	}

	/**
	 * Get the value of  a HTML5 data-* attribute.
	 * @param string $name The name of the property (!!WITHOUT "data-" prepended.)
	 * @return false|string The string value of the attribute. (Or false on failure, use === false for comparison)
	 */
	public function getDataAttribute($name){
		if(!empty($name) && is_string($name) && isset($this->dataAttributes[$name]))
			return $this->dataAttributes[$name];
		else return false;
	}

	/**
	 * Returns the html for all the data attributes that were set on this element.
	 * @param Document $document The document in which this element will be applied.
	 * @return string
	 */
	protected function saveDataAttributes(Document $document){
		$attributes = '';
		foreach($this->dataAttributes as $name => $value){
			$attributes .= ' '.$document->encodeAttribute($name, $value);
		}
		return ltrim($attributes, ' ');
	}
}

/**
 * Interface IElementMarkupClasses
 *
 * Makes it possible to set CSS/Markup classes on elements and get and set them.
 * @package Quark\Libraries\Bootstrap\Components
 */
interface IElementMarkupClasses extends IElement {
	/**
	 * Add a CSS/Markup class to the IComponent.
	 * @param string $classname CSS class name.
	 * @return void
	 */
	public function addMarkupClass($classname);

	/**
	 * Check if the element has a CSS/Markup class.
	 * @param string $classname CSS class name.
	 * @return boolean
	 */
	public function hasMarkupClass($classname);

	/**
	 * Remove a CSS/Markup class from the element.
	 * @param string $classname CSS class name.
	 * @return void
	 */
	public function removeMarkupClass($classname);
}

/**
 * Trait baseElementMarkupClasses
 *
 * Base implementation of the IElementMarkupClasses interface.
 * @package Quark\Libraries\Bootstrap\Components
 */
trait baseElementMarkupClasses {
	/**
	 * @var string[] List of CSS classes.
	 */
	protected $cssClasses = array();

	/**
	 * Add a CSS/Markup class to the IComponent.
	 * @param string $classname CSS class name.
	 * @return boolean|int New number of set classes on the element or false on failure.
	 */
	public function addMarkupClass($classname){
		if(!empty($classname) && is_string($classname))
			return array_push($this->cssClasses, $classname);
		return false;
	}

	/**
	 * Check if the element has a CSS/Markup class.
	 * @param string $classname CSS class name.
	 * @return boolean
	 */
	public function hasMarkupClass($classname){
		return in_array($this->cssClasses, $classname);
	}

	/**
	 * Remove a CSS/Markup class from the element.
	 * @param string $classname CSS class name.
	 * @return boolean
	 */
	public function removeMarkupClass($classname){
		foreach($this->cssClasses as $index => $class){
			if($class == $classname){
				unset($this->cssClasses[$index]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Saves all the classes in the form of (without the single outer quotes): 'class="some-class another-class"'
	 */
	protected function saveClassAttribute(Document $document){
		return $document->encodeAttribute('class', implode(' ', $this->cssClasses));
	}
}