<?php
/**
 * The UI System for Quark HS
 * 
 * Contains the base classes required for the use of Collections of elements.
 * 
 * @package		Quark-Framework
 * @version		$Id: collection.php 69 2013-01-24 15:14:45Z Jeffrey $
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
 * Basic Collection Interface
 * 
 * Ensures a element can have children and that those are traversable.
 */
interface Collection extends IElement, \IteratorAggregate{
	/**
	 * Add a child to this element at the end of the element
	 * @param IElement $element The element to add
	 * @return Boolean
	 */
	public function appendChild(IElement $element);
	
	/**
	 * Add a child at the beginning of the element
	 * @param IElement $element
	 * @return Boolean
	 */
	public function prependChild(IElement $element);
	
	/**
	 * Removes the first added occurence of $element
	 * @param IElement $element The element to remove
	 * @return Boolean
	 */
	public function removeChild(IElement $element);

	/**
	 * Gets the string representation of all the children in an element
	 * @param Document $context The document in which this element gets saved.
	 * @return String
	 */
	public function saveChildren(Document $context);
}

/**
 * Basic Extendable Element
 * 
 * An element using this trait implements the basic and bare minimum implementation for the collection interface.
 * @subpackage Interface
 */
trait baseCollection{
	/**
	 * Contains the children for this object
	 * @var Array
	 */
	protected $children = array();
	
	/**
	 * Add a child to this element at the end of the element
	 * @param IElement $element The element to add
	 * @return Boolean
	 */
	public function appendChild(IElement $element){
		return (@array_push($this->children, $element) == 1);
	}
	
	/**
	 * Add a child at the beginning of the element
	 * @param IElement $element
	 * @return Boolean
	 */
	public function prependChild(IElement $element){
		return (@array_unshift($this->children, $element) == 1);
	}
	
	/**
	 * Removes the first added occurrence of $element
	 * @param IElement $element The element to remove
	 * @return Boolean
	 */
	public function removeChild(IElement $element){
		foreach($this->children as $key => $child){
			if($child == $element){
				unset($this->children);
				return true;
			}
		}
		return false;
	}

	/**
	 * Gets the string representation of all the children in an element
	 * @param Document $context The Document in which this collection should be saved.
	 * @return String
	 */
	public function saveChildren(Document $context){
		$saved = '';
		// Iterate over the children, getting their string representation
		foreach($this->children as $child){
			$saved .= PHP_EOL.$child->save($context);
		}
		return $saved;
	}
	
	/**
	 * Iterator Aggregate Implementation
	 * @return \ArrayIterator
	 */
	public function getIterator(){
		return new \ArrayIterator($this->children);
	}
}