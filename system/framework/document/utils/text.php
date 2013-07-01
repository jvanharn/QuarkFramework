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

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Takes some text and wraps it so you can add it to Collection's etc.
 * 
 * This class always return's text /only/. This means that all html tags will be
 * escaped. This is safe for user-input sanitation, and properly encoded text.
 * (Conform the current Docmunt's encoding)
 */
class Text implements \Quark\Document\Element{
	/**
	 * The text to save.
	 * @var string
	 */
	protected $text = '';
	
	/**
	 * Text Element Constructor
	 * @param string $text Text to return.
	 */
	public function __construct($text){
		if(is_string($text))
			$this->text = $text;
		else throw new \InvalidArgumentException('The $text given should be of type "string".');
	}
	
	/**
	 * Get the saved/sanitized text input
	 * @return string
	 */
	public function save() {
		return \Quark\Document\Document::getInstance()->encodeText($this->text);
	}
	
	/**
	 * Converts to it's text representation
	 * @return string
	 */
	public function __toString() {
		return $this->save();
	}
}