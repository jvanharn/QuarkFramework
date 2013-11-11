<?php
/**
 * Element Wrapper - Utility Class
 * 
 * @package		Quark-Framework
 * @version		$Id: wrapper.php 69 2013-01-24 15:14:45Z Jeffrey $
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
namespace Quark\Document\Utils;
use Quark\Document\Document;
use Quark\Document\Element;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Wraps existing elements with a HTML Tag.
 */
class Wrapper implements Element {
	/**
	 * The element to wrap.
	 * @var \Quark\Document\Element
	 */
	protected $element;
	
	/**
	 * Name/Type of tag to wrap with.
	 * @var string
	 */
	protected $tagname;
	
	/**
	 * Attributes and it's values for the wrapper element.
	 * @var array
	 */
	protected $attributes;

	/**
	 * Construct a new wrapper object.
	 * @param \Quark\Document\Element|\Quark\Document\Element $element The element to wrap.
	 * @param string $tagname Name/Type of tag to wrap with.
	 * @param array $attributes Attributes and it's values for the wrapper element.
	 * @throws \InvalidArgumentException
	 */
	public function __construct(Element $element, $tagname='div', $attributes=array()){
		// Set the element
		$this->element = $element;
		
		// Set the tagname
		if(is_string($tagname))
			$this->tagname = $tagname;
		else throw new \InvalidArgumentException('Argument $tagname should be of type "string".');
		
		// Set the attributes
		if(is_array($attributes))
			$this->attributes = $attributes;
		else throw new \InvalidArgumentException('Argument $attributes should be of type "array".');
	}

	/**
	 * Retrieve the HTML representation of the wrapper.
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @return String HTML Representation
	 */
	public function save(Document $context) {
		$attr = '';
		foreach($this->attributes as $name => $val) $attr .= $name.'="'.$val.'"';
		return '<'.$this->tagname.$attr.'>'.($this->element->save($context)).'</'.$this->tagname.'>';
	}
}