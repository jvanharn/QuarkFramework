<?php
/**
 * Layout implementation definition
 * 
 * @package		Quark-Framework
 * @version		$Id: layout.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		7 december 2012
 * @copyright	Copyright (C) 2012 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012 Jeffrey van Harn
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
namespace Quark\Document\Layout;
use \Quark\Document\Element as Element;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Import the Dependencies
\Quark\import('Document.Element', 'Document.Layout.Positions', true);

/**
 * Layout Manager class
 * 
 * This is the layout manager for documents within Quark. If you have a Document
 * the layout will be managed by this type of class. You can add elements to the
 * layout on named places within the layout.
 * 
 * All layout classes must have at least these three positions:
 *  - HEADER
 *  - MAIN_CONTENT
 *  - FOOTER
 * 
 * The layout class is the perfect place to implement a template parser, if the 
 * need arises within your application. (You can off course also choose to not 
 * use the document model at all, we are only here to help you.)
 *
 * @property-read Positions $positions Object that manages and exposes all available positions where UI elements can be placed in this layout.
 */
abstract class Layout implements Element, \IteratorAggregate{
	/**
	 * Registry managing the available position references.
	 * @var Positions
	 */
	protected $positions = null;
	
	/**
	 * Dictionary of all the positions and the elements they contain
	 * @var Element[]
	 */
	protected $elements = array();
	
	/**
	 * Initializes the positions for this layout and the things it needs for itself.
	 */
	public function __construct(){
		$this->positions = new Positions(array('MAIN_CONTENT' => ['Main Content', 'Contains the content for this layout.']), array());
	}
	
	//abstract public function save();
	
	/**
	 * Place an element on the given position in the layout
	 * @param \Quark\Document\Element $elem Savable element object.
	 * @param string $position Valid position reference.
	 * @return boolean
	 */
	public function place(Element $elem, $position){
		if(!$this->positions->exists($position)) return false;
		else $position = $this->positions->resolve($position);
		
		if(!isset($this->elements[$position]))
			$this->elements[$position] = array();
		
		array_push($this->elements[$position], $elem);
		return true;
	}
	
	/**
	 * Remove the given element reference from the layout
	 * @param \Quark\Document\Element $elem
	 * @return boolean
	 */
	public function remove(Element $elem){
		foreach($this->elements as $pos => $elems){
			foreach($elems as $index => $current){
				if($current == $elem){
					unset($this->elements[$pos][$index]);
					return true;
				}
			}
		}
		return false;
	}
		
	/**
	 * IteratorAggregate implementation.
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new \ArrayIterator($this->elements);
	}
	
	/**
	 * Magic method for accessing emulated class variables.
	 * @param string $name
	 * @return \Quark\Document\Layout\Positions|\ArrayIterator
	 */
	public function __get($name){
		if($name == 'positions' || $name == 'pos')
			return $this->positions;
		else if($this->positions->exists($name))
			return new \ArrayIterator($this->elements[$name]);
	}
		
	/**
	 * When the class is called directly, it adds the given element to the given or otherwise default position on the layout.
	 * @see Layout::place
	 */
	final public function __invoke(Element $elem, $position=null){
		$this->place($elem, $position);
	}
}