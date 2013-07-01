<?php
/**
 * Auto completed textfield class for the Form Builder.
 * 
 * @package		Quark-Framework
 * @version		$Id: autocomplete.php 72 2013-02-03 22:19:22Z Jeffrey $
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
 * Auto completed textfield.
 * 
 * This field requires MooTools.
 */
class Autocomplete extends TextField implements ValidatableField, NormalizableField {
	use baseRegisterableValidators, baseRegisterableNormalizers;
	
	protected $uri;
	
	protected $static;
	
	/**
	 * @param string $name Name of the element for referencing it's data etc.
	 * @param string $label Label of the field in the form.
	 * @param string $value Default value of the field if applicable.
	 * @param string $placeholder A placeholder value for the field.
	 * @param string $api_url URL to the autocomplete api interface (By default it uses POST requests) when null, it will only autocomplete with static posibilities.
	 */
	public function __construct($name, $label, $value=null, $placeholder=null, $api_uri=null){
		parent::__construct($name, $label, $value, $placeholder);
		
		// Set the API
		$this->setAPI($api_uri);
	}
	
	/**
	 * Set the URI to the api to use for the suggestions.
	 * @param string $uri URI to the API.
	 * @return boolean
	 */
	public function setAPI($uri){
		if(!empty($uri)){
			$this->setAPI($uri);
			return true;
		}else return false;
	}
	
	/**
	 * Set some static suggestions for a autocompleted fields without a active api.
	 * @param array $suggestions Flat array with a few possibilities for this field, the maximum length is 40 elements for obvious performance reasons.
	 * @return boolean
	 */
	public function setSuggestions(array $suggestions){
		if(count($suggestions) <= 40){
			$this->static = $suggestions;
			return true;
		}else return false;
	}
	
	public function save() {
		
	}
	
	/**
	 * Encode the result for the api.
	 * @param string $result
	 * @return string String to return for the API.
	 */
	public static function encode(array $result){
		return json_encode($result);
	}
}