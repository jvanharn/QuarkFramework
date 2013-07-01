<?php
/**
 * (Headlined) Paragraph Element - Utility Class
 * 
 * @package		Quark-Framework
 * @version		$Id: paragraph.php 70 2013-01-28 22:11:34Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		January 27, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2013 Jeffrey van Harn
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
 * Takes some text and optionally a title, and wraps it with a paragraph and H* element.
 */
class Paragraph implements \Quark\Document\Element{
	/**
	 * The title to set.
	 * @var string
	 */
	protected $title = '';
	
	/**
	 * A number between 1 and 6, represents the h1 to h6 elements.
	 * @var integer
	 */
	protected $level = 3;
	
	/**
	 * Content to return.
	 * @var string
	 */
	protected $content = '';
	
	/**
	 * Paragraph Element Constructor
	 * @param string $content Text or element to wrap in a paragraph element.
	 * @param string $title Headline for the text (May be null, then only the paragraph is returned.)
	 * @param integer $level Headline level to use (Between 1 and 6).
	 */
	public function __construct($content, $title=null, $level=3){
		if(is_string($content))
			$this->content = '<p>'.\Quark\Document\Document::getInstance()->encodeText($content).'</p>';
		else if($content instanceof \Quark\Document\Element)
			$this->content = $content->save();
		else throw new \InvalidArgumentException('The $text given should be of type "string".');
		if(empty($title))
			$this->title = null;
		else if(is_string($title))
			$this->title = \Quark\Document\Document::getInstance()->encodeText($title);
		else throw new \InvalidArgumentException('Parameter $title should be of type "string" or be null.');
		if(is_int($level) && $level >= 1 && $level <= 6)
			$this->level = $level;
		else throw new \InvalidArgumentException('Parameter $level should be of type "integer" but got "'.gettype($level).'".');
	}
	
	/**
	 * Get the saved/sanitized and wrapped text input
	 * @return string
	 */
	public function save() {
		$saved = (is_null($this->title)?'':'<h'.$this->level.'>'.$this->title.'</h'.$this->level.'>'."\n");
		$saved .= $this->content."\n";
		return $saved;
	}
	
	/**
	 * Converts to it's text representation
	 * @return string
	 */
	public function __toString() {
		return $this->save();
	}
}