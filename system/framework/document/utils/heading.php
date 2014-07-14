<?php
/**
 * Text Element - Utility Class
 * 
 * @package		Quark-Framework
 * @version		$Id: text.php 70 2013-01-28 22:11:34Z Jeffrey $
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
use Quark\Document\IElement;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Takes some text and wraps it in a heading tag so you can add it to Collection's etc.
 * 
 * All $text given to this class will be properly encoded according to the encoding used within the document.
 */
class Heading implements IElement{
	/**
	 * The text to save.
	 * @var string
	 */
	protected $text = '';

	/**
	 * The heading level. (Eg. 1=h1, 2=h2, ..., 6=h6)
	 * @var int
	 */
	protected $level;

	/**
	 * Heading Element
	 * @param string $text Text to return.
	 * @param int $level The heading level. (Eg. 1=h1, 2=h2, ..., 6=h6)
	 * @throws \InvalidArgumentException
	 */
	public function __construct($text, $level=1){
		if(is_string($text))
			$this->text = $text;
		else throw new \InvalidArgumentException('The $text given should be of type "string".');
		if(is_integer($level))
			$this->level = $level;
		else throw new \InvalidArgumentException('The $level given should be of type "integer".');
	}

	/**
	 * Retrieve the HTML representation of the element
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth
	 * @return String HTML Representation
	 */
	public function save(Document $context, $depth=0) {
		return _::line($depth, '<h'.$this->level.'>'.$context->encodeText($this->text).'</h'.$this->level.'>');
	}
}