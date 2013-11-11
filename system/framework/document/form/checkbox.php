<?php
/**
 * Checkbox field.
 * 
 * @package		Quark-Framework
 * @version		$Id: checkbox.php 71 2013-02-02 19:40:11Z Jeffrey $
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
namespace Quark\Document\Form;
use \Quark\Document\Document;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Checkbox form-field.
 */
class Checkbox extends Field implements NormalizableField, NullableField {
	/**
	 * Default state of the checkbox.
	 * @var boolean
	 */
	protected $default = false;
	
	/**
	 * @param string $name Fieldname.
	 * @param string $label Label of the field.
	 * @param boolean $default Default state of the field.
	 */
	public function __construct($name, $label, $default=false){
		$this->name = (string) $name;
		$this->label = (string) $label;
		$this->default = (bool) $default;
	}
	
	/**
	 * Save the field to it's html representation.
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @return String HTML Representation
	 */
	public function save(Document $context) {
		$checked = (is_null($this->last)?$this->default:$this->last);
		return "\t\t\t\t<input type=\"checkbox\" name=\"".$this->name."\" id=\"".$this->name."\"".($checked?' checked="checked"':'')." />\n";
	}
	
	/**
	 * Normalizes the on/off etc browser values to a boolean.
	 * @param mixed $value User-submitted value to normalize.
	 * @return boolean Normalized boolean value.
	 */
	public function normalize($value) {
		return (is_string($value) && strtolower($value) == 'on');
	}
	
	/**
	 * This field can in every situeation, be null.
	 * @return boolean
	 */
	public function nullable(){
		return true;
	}
}