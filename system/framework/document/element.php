<?php
/**
 * The UI System for Quark HS
 * 
 * Contains the base classes required for the use of elements in the Document
 * 
 * @package		Quark-Framework
 * @version		$Id: element.php 55 2012-12-08 14:14:05Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 2, 2011
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
namespace Quark\Document;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Basic Element Interface
 * 
 * Makes sure the element class has the right method(s) for getting it's HTML.
 */
interface IElement {
	/**
	 * Retrieve the HTML representation of the element
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth The current indentation depth, not required.
	 * @return String HTML Representation
	 */
	public function save(Document $context, $depth=0);
}

/**
 * Loose element without context. Mostly inline elements.
 *
 * An loose or unbound element is an element that does not (Necessarily) need to know about the document it is in to render itself.
 */
interface IIndependentElement extends IElement {
	/**
	 * Retrieve the HTML representation of the element
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth The current indentation depth, not required.
	 * @return String HTML Representation
	 */
	public function save(Document $context=null, $depth=0);

    /**
     * Retrieve the HTML representation of the element without requiring a document as context.
     * @param int $depth The current indentation depth, not required.
     * @return String HTML Representation
     */
	public function independentSave($depth=0);

	/**
	 * @see save()
	 */
	public function __toString();
}

/**
 * Basic Element
 * 
 * This trait contains the basic logic for UI elements.
 * Most elements use this trait due to the ease of use,
 * you are however not forced to use this trait.
 * @subpackage Interface
 */
trait baseElement{
	/**
	 * Options that define the customizable options for the element
	 */
	protected $options = array();
	
	/**
	 * Default element constructor
	 * @param array $options Options for the element
	 */
	public function __construct($options = null){
		// Set the defaults if found
		if(isset($this->defaultOptions)) $this->options = $this->defaultOptions;
		else if(isset($this->defaults)) $this->options = $this->defaults;
		
		// Set the options
		if(!empty($options) && is_array($options)) $this->setOptions($options);
	}
	
	/**
	 * Set the class default options
	 * @param array $options Options
	 */
	protected function setDefaults($options){
		foreach($options as $key => $value){
			if(!isset($this->options[$key]))
				$this->options[$key] = $value;
		}
	}

	/**
	 * Set an option on this box
	 * @param string $option The option to set
	 * @param string $value The new value
	 * @throws \RuntimeException When the requested option does not exist.
	 * @return void
	 */
	public function setOption($option, $value){
		if(array_key_exists($option, $this->options)){
			$this->options[$option] = $value;
		}else throw new \RuntimeException('Option "'.$option.'" does not exist on this class.');
	}
	
	/**
	 * Set options on this box
	 * @param array $options The options to set in key => value pairs.
	 * @return void
	 */
	public function setOptions($options){
		foreach($options as $key => $value){
			$this->setOption($key, $value);
		}
	}
	
	/*
	 * Generates the HTML
	 * @return string The HTML
	 */
	//abstract public function save();
}

/**
 * Basic implementation of an independent element.
 * @package Quark\Document
 */
trait baseIndependentElement {
	/**
	 * Retrieve the HTML representation of the element
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth
	 * @return String HTML Representation
	 */
	public function save(Document $context=null, $depth=0){
		return $this->independentSave($depth);
	}

	/**
	 * Placeholder to satisfy the error checkers.
	 * @throws \RuntimeException
     * @param int $depth The current indentation depth, not required.
	 * @return string|void
	 * @ignore
	 */
	public function independentSave($depth=0){
		throw new \RuntimeException('This method has to be overwritten.');
	}

	/**
	 * Makes it possible to convert the class to html
	 * @return String
	 */
	public function __toString(){ return $this->independentSave(); }
}

/**
 * Interface IElementDataAttributes
 *
 * Defines a component or element that can have it's data- attributes set and/or modified.
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
 * Trait baseElementDataAttributes.
 *
 * Provides a basic implementation of the IElementDataAttributes class.
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
			$attributes .= ' ' . $document->encodeAttribute($name, $value);
		}
		return ltrim($attributes, ' ');
	}
}

/**
 * Interface IElementMarkupClasses
 *
 * Makes it possible to set CSS/Markup classes on elements and get and set them.
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
		if(!empty($classname) && is_string($classname) && !$this->hasMarkupClass($classname))
			return array_push($this->cssClasses, $classname);
		return false;
	}

	/**
	 * Check if the element has a CSS/Markup class.
	 * @param string $classname CSS class name.
	 * @return boolean
	 */
	public function hasMarkupClass($classname){
		return in_array($classname, $this->cssClasses);
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
	 * @param Document $document
	 * @return string
	 */
	protected function saveClassAttribute(Document $document){
		return $document->encodeAttribute('class', implode(' ', $this->cssClasses));
	}

	/**
	 * Class switching helper.
	 *
	 * Helper method that makes it possible to define a list with classes, and switch to another class and automatically
	 * have the other classes removed.
	 * @param array $classes
	 * @param string $class (New) class to set.
	 */
	protected function switchMarkupClass(array &$classes, $class){
		foreach($this->cssClasses as $k => $v){
			if(in_array($v, $classes))
				unset($this->cssClasses[$k]);
		}
		array_push($this->cssClasses, $class);
	}
}