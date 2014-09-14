<?php
/**
 * Simple Element Collection - Utility Class
 * 
 * @package		Quark-Framework
 * @version		$Id: collection.php 69 2013-01-24 15:14:45Z Jeffrey $
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
use Quark\Document\baseCollection,
	Quark\Document\Document,
	Quark\Document\IElement;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple implementation of the Collection Interface.
 */
class Collection implements \Quark\Document\ICollection {
	use baseCollection;
	
	/**
	 * The tagname of the collection wrapper (Or null)
	 * @var string
	 */
	protected $tagname;
	
	/**
	 * The attributes of the wrapping element.
	 * @var array
	 */
	protected $attributes;
	
	/**
	 * @param string $tagname The type of element to wrap the collection with, or null for no tag.
	 * @param array $attributes The attributes for the wrapping tag.
	 * @throws \InvalidArgumentException When a attribute's type is invalid. 
	 */
	public function __construct($tagname=null, $attributes=array()){
		if(is_null($tagname) || is_string($tagname))
			$this->tagname = $tagname;
		else throw new \InvalidArgumentException('Param $tagname should be of type string.');
		if(is_array($attributes))
			$this->attributes = $attributes;
		else throw new \InvalidArgumentException('Param $attributes should be of type array.');
		
	}

	/**
	 * Save the collection to its HTML representation.
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @return String HTML Representation
	 */
	public function save(Document $context) {
		if(!empty($this->tagname)){
			$attr = '';
			foreach($this->attributes as $name => $val)
				$attr .= $name.'="'.$val.'"';

			return '<'.$this->tagname.$attr.'>'.$this->saveChildren($context).'</'.$this->tagname.'>';
		}else return $this->saveChildren($context);
	}
	
	/**
	 * Invoke the collection to simplify adding elements to the collection
	 * @param IElement $element Element to append to the collection.
	 * @return \Quark\Document\Utils\Collection The current object for chaining.
	 * @see \Quark\Document\Collection::appendChild()
	 */
	public function __invoke(IElement $element) {
		$this->appendChild($element);
		return $this;
	}
}