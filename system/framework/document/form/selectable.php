<?php
/**
 * Basic Select field
 * 
 * @package		Quark-Framework
 * @version		$Id: selectable.php 71 2013-02-02 19:40:11Z Jeffrey $
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
 * Select form-field.
 */
class Selectable extends Field implements IValidatableField, INullableField {
	/**
	 * Whether or not multiple fields can be selected
	 * @var boolean|integer
	 */
	public $multiple = false;
	
	/**
	 * Options defined for this field.
	 * @var array
	 */
	public $options = array();
	
	/**
	 * @param string $name
	 * @param string $label
	 * @param boolean|integer $multiple False for single selection, true for unlimited selections, a number for a limited number of selections.
	 * @param array $options Value(Key) => Text(Value) pairs of the options you want to initalize the selectable with. When an option has a numeric key, it will use the value of the item as value. If you want more freedom use the add option method.
	 */
	public function __construct($name, $label, $multiple=false, array $options=array()){
		$this->name = (string) $name;
		$this->label = (string) $label;
		
		if(is_bool($multiple) || (is_integer($multiple) && $multiple >= 2))
			$this->multiple = $multiple;
		
		foreach($options as $text => $value){
			if(is_string($text))
				$this->addOption($text, $value);
			else
				$this->addOption($value, $value);
		}
	}
	
	/**
	 * Add an option to the Selectable field.
	 * @param string|integer $text Text of the option, if no value is specified, this will also be used as the value of this option.
	 * @param string|integer $value Value for the option.
	 * @return boolean
	 */
	public function addOption($text, $value=null){
		if(is_string($text) || is_integer($text)){
			if(!empty($value) && (is_string($value) || is_integer($value)))
				$this->options[] = [$text, $value];
			else
				$this->options[] = [$text, $text];
			return true;
		}else return false;
	}
	
	/**
	 * Get all the options in the field.
	 * @return array
	 */
	public function getOptions(){
		return $this->options;
	}
	
	/**
	 * Get the html representation of the selectable field.
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @return string HTML Representation
	 */
	public function save(Document $context) {
		if($this->multiple !== false){
			$saved = "\t\t\t\t<select size=\"4\" multiple=\"multiple\" name=\"".$this->name."[]\" id=\"".$this->name."\">\n";
		}else{
			$saved = "\t\t\t\t<select name=\"".$this->name."\" id=\"".$this->name."\">\n";
		}
		foreach($this->options as $option){
			$saved .= "\t\t\t\t\t<option value=\"".$option[1]."\"".(($this->last == $option[1])?' selected="selected"':'').">".$option[0]."</option>\n";
		}
		$saved .= "\t\t\t\t</select>\n";
		return $saved;
	}
	
	/**
	 * Checks if the data value was a valid option for this Selectable.
	 * @param string|integer $value
	 * @return boolean
	 */
	public function validate($value) {
		if($this->multiple === false){
			if(is_string($value) || is_integer($value))
				$value = array($value);
			else
				return false;
		}else{
			if(!is_array($value))
				return false;
		}
		
		foreach($value as $current){
			$found = false;
			foreach($this->options as $option){
				if($option[1] == $current){
					$found = true;
					break;
				}
			}
			if(!$found)
				return false;
		}
		return true;
	}
	
	/**
	 * This field is nullable when multiple fields can be selected.
	 * @return boolean
	 */
	public function nullable() {
		return ($this->multiple !== false);
	}
}