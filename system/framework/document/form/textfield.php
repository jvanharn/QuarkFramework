<?php
/**
 * Text field class for use with the Form class.
 * 
 * @package		Quark-Framework
 * @version		$Id: textfield.php 71 2013-02-02 19:40:11Z Jeffrey $
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

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Text field class for use with the Form class.
 */
class TextField extends Field implements ValidatableField, NormalizableField {
	use baseRegisterableValidators, baseRegisterableNormalizers;
	
	/**
	 * Default value of the field if applicable.
	 * @var string
	 */
	protected $value;
	
	/**
	 * A placeholder value for the field.
	 * @var string
	 */
	protected $placeholder;
	
	/**
	 * @param string $name Name of the element for referencing it's data etc.
	 * @param string $label Label of the field in the form.
	 * @param string $value Default value of the field if applicable.
	 * @param string $placeholder A placeholder value for the field.
	 */
	public function __construct($name, $label, $value=null, $placeholder=null){
		$this->name = (string) $name;
		$this->label = (string) $label;
		$this->value = (string) $value;
		$this->placeholder = (string) $placeholder;
	}
	
	public function save() {
		$value = ((empty($this->value) && empty($this->last))?'':' value="'.($this->value?:($this->last?:'')).'"');
		return "\t\t\t\t<input type=\"text\" name=\"".$this->name."\" id=\"".$this->name."\"".$value."".(empty($this->placeholder)?'':' placeholder="'.$this->placeholder.'"')."/>\n";
	}
}