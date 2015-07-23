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
use Quark\Document\Utils\_;
use Quark\Document\baseElementMarkupClasses;
use Quark\Document\IElementMarkupClasses;
use Quark\Loader;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;


/**
 * Form Builder
 * 
 * Simplifies the construction and the easy (and safe) extraction of data from
 * the resulting submit.
 */
class Form implements IElement, IElementMarkupClasses {
    use \Quark\Document\baseElementMarkupClasses;

	/**
	 * The hash algorithm to use to hash the "unique" form identifiers.
	 * @access private
     * @internal Hash algorithm should be as fast as possible, security or collisions do not matter as much. (On most platforms, MD4 performs slightly faster, which is why it was chosen)
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
	 * Saved as session data when enabled, will enable the form class to
	 * differentiate between multiple forms with exactly the same fields.
	 * @var string The Unique Form instance identifier.
	 */
	private $uid = null;
	
	/**
	 * Enables the form to quickly determine if the submitted form is the same
	 * as the one this object represents.
	 * @var string The Form instance identifier.
     * @see Form::getFID()
	 */
	private $fid = null;

	/** @var \Quark\Document\Document The document this form was created in. */
	protected $context;

	/** @var string The URL where the form get's submitted to. */
	protected $action = null;
	
	/** @var string The method to use when submitting. One of the METHOD_* constants. */
	protected $method = 'POST';
	
	/** @var boolean Whether or not to automatically place fields without a group in a default group. */
	protected $autogroup;
	
	/** @var array Groups of fields */
	protected $groups = array();
	
	/** @var array Fields that are part of this form. */
	protected $fields = array(
        self::DEFAULT_GROUP => array()
    );
	
	/** @var boolean Caches the result of the submitted method. */
	protected $submitted = null;
	
	/** @var boolean Caches the result of the validated method. */
	protected $validated = null;

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
			$app = Loader::getApplication();
			if(method_exists($app, 'getRouter')) {
                try {
                    $this->action = $app->getRouter()->getURL(); // @todo This no longer works. Go fix.
                }catch(\Exception $e){
                    throw new \RuntimeException('Tried to get router object and get the current url, but this resulted in an unexpected exception.', 0, $e);
                }
            }else throw new \InvalidArgumentException('The argument $action should be manually defined for this application: could not get a reference to the router object (application object has no "getRouter" method).');
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

		$this->fid = null; // reset form id
		$this->groups[$name] = $title;
		$this->fields[$name] = array();
		foreach($fields as $field){
			if($field instanceof Field)
				$this->fields[$name][] = $field;
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

		$data  = $this->data();
		$form  = _::line($depth,     '<form action="'.$this->action.'" method="'.$this->method.'" '.$this->saveClassAttribute($context).'>');
		$form .= _::line($depth + 1, '<input type="hidden" name="formid" value="'.$this->getFID().'"/>');
        $uid = $this->getUID();
        $form .= (empty($uid) ? '' : _::line($depth + 1, '<input type="hidden" name="uniqid" value="'.$uid.'"/>'));
		
		$grouped = $this->fields;
		if(!$this->autogroup){
            /** @var Field $field */
            foreach($grouped['default'] as $field){
				$name = $field->getName();
				if($data != false && (
						( is_array($this->validated) && !isset($this->validated[$name]) && isset($data[$name]))
					||	(!is_array($this->validated) &&  isset($data[$name]))
				)){
					$field->setLastValue($data[$name]);
				}
				$form .= $this->saveField($field, $depth+2);
			}
			unset($grouped['default']);
		}
		
		foreach($grouped as $group => $fields){
			$form .= _::line($depth + 1, '<fieldset>');
			if(!empty($this->groups[$group]))
				$form .= _::line($depth + 1, '<legend>'.$this->groups[$group].'</legend>');
			foreach($fields as $field){
				$name = $field->getName();
				if($data != false && (
						( is_array($this->validated) && !isset($this->validated[$name]) && isset($data[$name]))
					||	(!is_array($this->validated) &&  isset($data[$name]))
				)){
					$field->setLastValue($data[$name]);
				}
				$form .= $this->saveField($field, $depth+2);
			}
			$form .= _::line($depth + 1, '</fieldset>');
		}
		
		$form .= _::line($depth, '</form>');
		
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
	 * Get the Unique Identifier of the current Form Object.
	 * @return string Hexadecimal string of 32 characters.
	 */
	public function getUID(){
		return $this->uid;
	}
	
	/**
	 * Set the Unique form Identifier.
	 * @param null|string Hexadecimal string of 32 characters. Shorter strings will be padded.
	 */
	public function setUID($value){
		if(empty($value))
			$this->uid = null;
		else
			$this->uid = substr(str_pad((string) $value, 32, '0'), 0, 32);
	}
	
	/**
	 * Get the current object's Form Identifier.
     *
     * This value is used to identify any submitted post requests as containing data for this specific object instance.
     * Will not work if you have two objects with the exact same fields.
     * Note: NOT FOR SECURITY PURPOSES. Just usability.
	 * @return string Hexadecimal string of 32 characters.
	 * @access protected
	 */
	public function getFID(){
		if(empty($this->fid))
			$this->generateFID();
		return $this->fid;
	}
	
	/**
	 * Generates a Form Identifier for the current fields.
     * @see Form::getFID()
	 */
	protected function generateFID(){
		$data = $this->method.$this->action;
		foreach($this->fields as $group => $fields){
			$data .= $group;
            /** @var Field $field */
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
     * @param int $depth
     * @return string
     */
	protected function saveField(Field $field, $depth=2){
		$name = $field->getName();
		$label = $field->getLabel();
		$field_error = (is_array($this->validated) && isset($this->validated[$name]));
		return	_::line($depth, '<div class="control-group">').
					(is_null($label)?'':_::line($depth + 1, '<label for="'.$name.'">'.$label.'</label>')).
                    _::line($depth + 1, '<div class="controls'.($field_error ? ' invalid-value':'').'">').
						$field->save($this->context, $depth+3).
						($field_error?_::line($depth + 2, '<span class="validation-errors">'.$this->validated[$name].'</span>'):'').
                    _::line($depth + 1, '</div>').
                _::line($depth, '</div>');
	}
}