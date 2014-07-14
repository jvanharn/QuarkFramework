<?php
/**
 * Simple Form builder Object
 * 
 * @package		Quark-Framework
 * @version		$Id: form.php 73 2013-02-10 15:01:47Z Jeffrey $
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
use Quark\Document\Document,
	Quark\Document\IElement;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;


/**
 * Form Builder
 * 
 * Simplifies the construction and the easy (and safe) extraction of data from
 * the resulting submit.
 */
class Form implements IElement {
	/**
	 * The hash algorithm to use to hash the "unique" form identifiers.
	 * Should be as fast as possible. (Most platforms MD4 performs slightly faster)
	 * @access private
	 */
	const FID_HASH = 'md4';
	
	/**
	 * The default group name.
	 */
	const DEFAULT_GROUP = 'default';
	
	/**
	 * Post Method
	 * Makes the form submit the form using a POST request.
	 */
	const METHOD_POST = 'POST';
	
	/**
	 * Get Method
	 * Makes the form submit the form using a GET request.
	 */
	const METHOD_GET = 'GET';
	
	/**
	 * The Unique Form instance identifier.
	 * Saved as session data when enabled, will enable the form class to
	 * differentiate between multiple forms with exactly the same fields.
	 * @var string
	 */
	private $uid = null;
	
	/**
	 * The Form instance identifier.
	 * Enables the form to quickly determine if the submitted form is the same
	 * as the one this object represents.
	 * @var string
	 */
	private $fid = null;

	/**
	 * The document this form was created in.
	 * @var \Quark\Document\Document
	 */
	protected $context;

	/**
	 * The URL where the form get's submitted to.
	 * @var string
	 */
	protected $action = null;
	
	/**
	 * The method to use when submitting.
	 * One of the METHOD_* constants.
	 * @var string
	 */
	protected $method = 'POST';
	
	/**
	 * Current CSS class(es).
	 * @var string
	 */
	protected $class = '';
	
	/**
	 * Whether or not to automatically place fields without a group in a default group.
	 * @var boolean
	 */
	protected $autogroup;
	
	/**
	 * Groups of fields
	 * @var array
	 */
	protected $groups = array();
	
	/**
	 * Fields that are part of this form.
	 * @var array
	 */
	protected $fields = array(self::DEFAULT_GROUP => array());
	
	/**
	 * Caches the result of the submitted method.
	 * @var boolean
	 */
	private $submitted = null;
	
	/**
	 * Caches the result of the validated method.
	 * @var boolean
	 */
	private $validated = null;

	/**
	 * Constructs a new Form object.
	 *
	 * @param \Quark\Document\Document $context The document where the form will reside in.
	 * @param string $action The action url the form is submitted to. (Preferably the same url as the current, or another. that leads to the same code stack. Unless you want to keep adding the same fields :P)
	 * @param string $method One of the METHOD_* class constants.
	 * @param bool $group Whether or not to auto group fields. When turned on makes non-grouped fields automatically fall into the 'default' group. This makes sure fields are always in a fieldset.
	 * @param string $unique Add a unique form identifier/name, to help differentiate between two forms with exactly the same fields (MAXLENGTH=32).
	 * @throws \InvalidArgumentException
	 */
	public function __construct(Document $context, $action=null, $method=self::METHOD_POST, $group=true, $unique=null){
		if(!empty($context))
			$this->context = $context;
		else throw new \InvalidArgumentException('Expected Document object for argument $context, but got null.');

		if(is_null($action)){
			$app = \Quark\Loader::getApplication();
			if(method_exists($app, 'getRouter'))
				$this->action = $app->getURL();
			else throw new \InvalidArgumentException('The argument $action should be manually defined for this application: could not get a reference to the router object.');
		}else $this->action = $action;
		
		if($method == self::METHOD_POST || $method == self::METHOD_GET)
			$this->method = $method;
		else throw new \InvalidArgumentException('The $method argument should be one of the METHOD_* class constants.');
		
		if(is_bool($group))
			$this->autogroup = $group;
		else throw new \InvalidArgumentException('The argument $group should be of type "boolean" but got "'.gettype($group).'".');
		
		$this->setUID($unique);
	}

	/**
	 * Place a field on the Form.
	 * @param \Quark\Document\Form\Field $field
	 * @param string|null $group Null to just add to last active group.
	 * @throws \InvalidArgumentException
	 * @return boolean
	 */
	public function place(Field $field, $group=null){
		if(is_null($group)){
			$this->fid = null;
			$this->fields['default'][] = $field;
			return true;
		}else if(is_string($group) || is_integer($group)){
			if(isset($this->groups[$group])){
				$this->fid = null;
				$this->fields[$group][] = $field;
				return true;
			}else return false;
		}else throw new \InvalidArgumentException('Parameter $group for the method place should be of type "string" or "integer" but got "'.gettype($group).'".');
	}

	/**
	 * Define a group. (Optionally with it's fields)
	 * @param string $name Internal name of the fieldset/group.
	 * @param string $title Title of the group in the form, leave empty if you want no fieldset name.
	 * @param array $fields Field objects to place in the group for quicker syntaxis.
	 * @throws \UnexpectedValueException
	 * @throws \InvalidArgumentException
	 */
	public function group($name, $title='', array $fields=array()){
		if(!(is_string($name) || is_integer($name))) throw new \InvalidArgumentException('Parameter $name for the method group should be of type "string" or "integer" but got "'.gettype($name).'".');
		if(!is_string($title)) throw new \InvalidArgumentException('Parameter $title for method group should be of type "string" but got "'.gettype($title).'".');
		
		$this->groups[$name] = $title;
		$this->fields[$name] = array();
		foreach($fields as $field){
			if($field instanceof Field)
				$this->fields[$name] = $field;
			else throw new \UnexpectedValueException('Tried to place a field inside a group, but the given field value wasn\'t a \\Quark\\Document\\Form\\Field instance.');
		}
	}
	
	/**
	 * Checks whether the current form was submitted.
	 * 
	 * If you want to know if the form was submitted, and the data supplied by
	 * the user was valid, use the {@see Form::validated()} method.
	 * @return boolean
	 */
	public function submitted(){
		if(is_null($this->submitted)){
			// Check the method
			if(!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != $this->method){
				$this->submitted = false;
				return false;
			}

			// Get method array
			if($this->method == self::METHOD_POST)
				$data =& $_POST;
			else
				$data =& $_GET;

			// Check FID & UID
			if(
				$this->getFID() != (isset($data['formid']) ? : '') ||
				$this->getUID() != (isset($data['uniqid']) ? : null)
			){
				$this->submitted = false;
				return false;
			}
			
			// Check fields
			foreach($this->fields as $fields){
				foreach($fields as $field){
					if(($field instanceof INullableField && !$field->nullable()) && !isset($data[$field->getName()])){
						$this->submitted = false;
						return false;
					}
				}
			}
			
			// It passed!
			$this->submitted = true;
		}
		return $this->submitted;
	}
	
	/**
	 * Check whether the form was submitted and the values are valid for the fields.
	 * @return boolean
	 */
	public function validated(){
		if(is_null($this->validated)){
			// Check if the data was submitted
			if(!$this->submitted())
				return false;

			// Get method array
			if($this->method == self::METHOD_POST)
				$data =& $_POST;
			else
				$data =& $_GET;
			
			// Check all the fields.
			$this->validated = array();
			foreach($this->fields as $fields){
				foreach($fields as $field){
					if($field instanceof IValidatableField){
						$name = $field->getName();
						$value = (isset($data[$name])? $data[$name] : null);
						if($field instanceof INormalizableField)
							$error = $field->validate($field->normalize($value));
						else
							$error = $field->validate($value);
						if($error !== true){
							$this->validated[$name] = $error;
						}
					}
				}
			}
			
			if(empty($this->validated))
				$this->validated = true;
		}
		
		return ($this->validated === true);
	}

	/**
	 * Save the form to HTML.
	 *
	 * If validated was called earlier, the resulting errors will be displayed
	 * in the form.
	 *
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth
	 * @throws \RuntimeException
	 * @return string The HTML form representation.
	 */
	public function save(Document $context, $depth=0) {
		if($this->context != $context)
			throw new \RuntimeException('It appears this form has been saved/added to an document where it was not created for, this might result in unexpected errors or incompatible encodings.');
		$this->context = $context;

		$data = $this->data();
		$form = "\n<form action=\"".$this->action."\" method=\"".$this->method."\" class=\"".$this->class."\">\n";
		$form .= "\t<input type=\"hidden\" name=\"formid\" value=\"".$this->getFID()."\"/>\n";
		$form .= (empty($this->uid)?'':"\t<input type=\"hidden\" name=\"uniqid\" value=\"".$this->getUID()."\"/>\n");
		
		$grouped = $this->fields;
		if(!$this->autogroup){
			foreach($grouped['default'] as $field){
				$name = $field->getName();
				if($data != false && (
						( is_array($this->validated) && !isset($this->validated[$name]) && isset($data[$name]))
					||	(!is_array($this->validated) &&  isset($data[$name]))
				)){
					$field->setLastValue($data[$name]);
				}
				$form .= $this->saveField($field);
			}
			unset($grouped['default']);
		}
		
		foreach($grouped as $group => $fields){
			$form .= "\t<fieldset>\n";
			if(!empty($this->groups[$group]))
				$form .= "\t\t<legend>".$this->groups[$group]."</legend>\n";
			foreach($fields as $field){
				$name = $field->getName();
				if($data != false && (
						( is_array($this->validated) && !isset($this->validated[$name]) && isset($data[$name]))
					||	(!is_array($this->validated) &&  isset($data[$name]))
				)){
					$field->setLastValue($data[$name]);
				}
				$form .= $this->saveField($field);
			}
			$form .= "\t</fieldset>\n";
		}
		
		$form .= "</form>\n";
		
		return $form;
	}
	
	/**
	 * Get the data from a submitted form.
	 * 
	 * Before using this function, make sure the form was submitted with the {@see \Quark\Document\Form\Form::submitted()} method.
	 * @param boolean $grouped Instead of a flat array of values, get an associative array of groups with the fields inside of them. Ungrouped fields will be in the "default' group.
	 * @return array|false Array if the form was submitted, otherwise false.
	 */
	public function data($grouped=false){
		if($this->submitted()){
			if($this->method == self::METHOD_POST)
				$source =& $_POST;
			else
				$source =& $_GET;
			
			$data = array();
			foreach($this->fields as $group => $fields){
				if($grouped)
					$data[$group] = array();
				foreach($fields as $field){
					$name = $field->getName();
					$raw = (isset($source[$name])?$source[$name]:null);
					if($field instanceof INormalizableField)
						$value = $field->normalize($raw);
					else
						$value = $raw;
					if($grouped)
						$data[$group][$name] = $value;
					else
						$data[$name] = $value;
				}
			}
			return $data;
		}else return false;
	}
	
	/**
	 * Get an array with validation errors, if there were any.
	 * @return array|boolean
	 */
	public function errors(){
		return $this->validated;
	}
	
	/**
	 * Reset the data in the form.
	 * 
	 * When this function get's called the data used by this form will be removed. This will result in submitted returning false, and the form will not reuse the value's that were submitted last time.
	 */
	public function reset(){
		if($this->method == self::METHOD_POST)
			$source =& $_POST;
		else
			$source =& $_GET;
		
		foreach($this->fields as $fields){
			foreach($fields as $field){
				unset($source[$field->getName()]);
			}
		}
	}
	
	/**
	 * Get the current css class(es).
	 * @return string
	 */
	public function getClass(){
		return $this->class;
	}
	
	/**
	 * Set the css class(es)
	 * @param string $classname
	 * @return boolean
	 */
	public function setClass($classname){
		if(is_string($classname)){
			$this->class = $classname;
			return true;
		}else return false;
	}
	
	/**
	 * Get the Unique Identifier of the current Form Object.
	 * @return string Hexadecimal string of 32 characters.
	 */
	public function getUID(){
		return $this->uid;
	}
	
	/**
	 * Set the Unique form Identifier.
	 * @param null|string Hexadecimal string of 32 characters, if it is not is is padded.
	 */
	public function setUID($value){
		if(empty($value))
			$this->uid = null;
		else
			$this->uid = substr(str_pad((string) $value, 32, '0'), 0, 32);
	}
	
	/**
	 * Get the current object's Form IDentifier.
	 * @return string Hexadecimal string of 32 characters.
	 * @access protected
	 */
	public function getFID(){
		if(empty($this->fid))
			$this->generateFID();
		return $this->fid;
	}
	
	/**
	 * Generates a Form IDentifier for the current fields.
	 */
	protected function generateFID(){
		$data = $this->method.$this->action;
		foreach($this->fields as $group => $fields){
			$data .= $group;
			foreach($fields as $field)
				$data .= $field->getName().get_class($field);
		}
		$this->fid = hash(self::FID_HASH, $data, false);
	}
	
	/**
	 * Save a single field to it's html representation.
	 * 
	 * If you want to "template" the fields, this is the easiest place to do that. (Just extend the class and override this method).
	 * @param \Quark\Document\Form\Field $field
	 * @return string
	 */
	protected function saveField(Field $field){
		$name = $field->getName();
		$label = $field->getLabel();
		$field_error = (is_array($this->validated) && isset($this->validated[$name]));
		return	"\t\t<div class=\"control-group\">\n".
					(is_null($label)?'':"\t\t\t<label for=\"".$name."\">".$label."</label>\n").
					"\t\t\t<div class=\"controls".($field_error?' invalid-value':'')."\">\n".
						$field->save($this->context).
						($field_error?"\t\t\t\t<span class=\"validation-errors\">".$this->validated[$name]."</span>\n":'').
					"\t\t\t</div>\n".
				"\t\t</div>\n";
	}
}