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