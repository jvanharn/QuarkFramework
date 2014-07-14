<?php
/**
 * Form Builder Field base
 * 
 * @package		Quark-Framework
 * @version		$Id: field.php 71 2013-02-02 19:40:11Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		January 24, 2013
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
namespace Quark\Document\Form;

// Import namespaces
use Quark\Document\IElement;
use Quark\Document\IIndependentElement;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Form Field.
 */
abstract class Field implements IIndependentElement {
	/**
	 * Name of the field in the form.
	 * @var string
	 */
	protected $name;
	
	/**
	 * Label for the current field.
	 * @var string
	 */
	protected $label;
	
	/**
	 * The value the user last submitted.
	 * @var string
	 */
	protected $last;
	
	/**
	 * @access protected
	 * @return string
	 */
	public function __toString(){
		return $this->independentSave();
	}
	
	/**
	 * Get the name of the current field.
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}
	
	/**
	 * Get the label of the current field. (Can be an empty string)
	 * @return string
	 */
	public function getLabel(){
		return $this->label;
	}
	
	/**
	 * Set's the last value the user submitted.
	 * 
	 * Only get's called if the field validated.
	 * @param string $value Last submitted value.
	 */
	public function setLastValue($value){
		$this->last = $value;
	}
}

/**
 * When implemented defines that there are possible form return values where the result of a field can be not set in the request.
 */
interface INullableField {
	/**
	 * Whether or not the current field can be undefined in the request data.
	 * 
	 * For fields like Selectable and Checkbox fields where the value is not set when it is empty or false.
	 * @return boolean
	 */
	public function nullable();
}

/**
 * When implemented implicates that a field needs to transform the submitted data before it can be used.
 */
interface INormalizableField {
	/**
	 * Normalize the value given by the browser to a php value or corrects it's content.
	 * 
	 * This method always get's called before something is done with the data, like validating or retrieving the data.
	 * @param mixed $value Value for the field given by the browser.
	 * @return mixed The normalized/corrected value of the field.
	 */
	public function normalize($value);
}

/**
 * Represents a form field that can validate the data that was submitted.
 */
interface IValidatableField {
	/**
	 * Validates if the value was correctly submitted.
	 * 
	 * Because this method should only return true when valid and all other
	 * values mean invalid, you should use "=== true" to check whether the value
	 * is valid for the given field.
	 * @param mixed $value The value that was submitted by the user.
	 * @return boolean|string Boolean true when valid, a string with a message or false when invalid.
	 */
	public function validate($value);
}

trait baseRegisterableNormalizers {
	/**
	 * All registred normalizers
	 * @var array
	 */
	protected $normalizers = array();
	
	/**
	 * Add a function to validate the form data.
	 * 
	 * Function recieves the value as parameter and should return the normalized value.
	 * @param callable $normalizer Method to call.
	 * @return \Quark\Document\Form\Field The current instance of the field.
	 */
	public function addNormalizer(callable $normalizer){
		$this->normalizers[] = $normalizer;
		return $this;
	}
	
	/**
	 * Get all the registered normalizer methods.
	 * @return array
	 */
	public function getNormalizers(){
		return $this->normalizers;
	}
	
	/**
	 * Normalize the value with the registered validators.
	 * 
	 * If no normalizers are registered, this function will return the same unaltered value.
	 * The methods will be called in the same order as they were registrered.
	 * @param mixed $value Value to normalize for this field.
	 * @return mixed The normalized value.
	 */
	public function normalize($value){
		$result = $value;
		foreach($this->normalizers as $callable){
			$result = $callable($result);
		}
		return $result;
	}
}

/**
 * Trait that implements IValidatableField with rigisterable validators.
 */
trait baseRegisterableValidators {
	/**
	 * All registred validators
	 * @var array
	 */
	protected $validators = array();
	
	/**
	 * Add a function to validate the form data.
	 * 
	 * Function recieves the value as parameter and should return true on valid,
	 * and a string when there was an error.
	 * @param callable $validator Method to call.
	 * @return \Quark\Document\Form\Field The current instance of the field.
	 */
	public function addValidator(callable $validator){
		$this->validators[] = $validator;
		return $this;
	}
	
	/**
	 * Get all the registered validator methods.
	 * @return array
	 */
	public function getValidators(){
		return $this->validators;
	}
	
	/**
	 * Validate the value with the registered validators.
	 * 
	 * If no validators are registered this function will always return true.
	 * @param mixed $value Value to validate for this field.
	 * @return boolean|string Boolean true when valid, a string with a error message or false when invalid.
	 */
	public function validate($value){
		foreach($this->validators as $callable){
			$result = $callable($value);
			if($result !== true)
				return $result;
		}
		return true;
	}
}