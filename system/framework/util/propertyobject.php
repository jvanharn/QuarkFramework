<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		May 9, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Util;

/**
 * Represents an (settings) object which is solely used for defining and grouping a predefined set of properties.
 * Extend this class if you want to define a group of properties.
 * @package Quark\Util
 */
abstract class PropertyObject {
	/**
	 * @var string
	 */
	private $_childName;

	/**
	 * @var array
	 */
	private $_properties;

	/**
	 * Create a new property object.
	 * @param array $values (Optionally) A hash-map containing the values for the properties of this object.
	 */
	public function __construct($values=null){
		$this->_childName = get_called_class();
		if($values != null && is_array($values))
			$this->setArray($values);
	}

	/**
	 * Get all the property names of this object.
	 * @return array
	 */
	public function getProperties(){
		if($this->_properties == null)
			$this->_populateChildProperties();
		return array_keys($this->_properties);
	}

	/**
	 * Get all the properties of this object, and their default values.
	 * @return array
	 */
	public function getDefaults(){
		if($this->_properties == null)
			$this->_populateChildProperties();
		return $this->_properties;
	}

	/**
	 * Set's the array/hash-map given as the values of this property object.
	 * @param array $values
	 *
	 * @throws \UnexpectedValueException
	 */
	public function setArray(array $values){
		if($this->_properties == null)
			$this->_properties = array();

		foreach($values as $name => $value){
			if(isset($this->_properties[$value]))
				$this->{$name} = $value;
			else
				throw new \UnexpectedValueException('The property name given in the $values hash-map is an invalid property name for this object.');
		}
	}

	/**
	 * Get the contents of all the properties and their values as an associative array.
	 * @return array
	 */
	public function toArray(){
		$result = array();
		foreach($this->getProperties() as $prop)
			$result[$prop] = $this->{$prop};
		return $result;
	}

	/**
	 * This method makes sure the object's properties are known.
	 * @return array
	 */
	private function _populateChildProperties(){
		if($this->_properties == null) {
			$this->_properties = get_class_vars($this->_childName);
			foreach($this->_properties as $prop => $value){
				if(substr($prop, 0, 1) == '_')
					unset($this->_properties[$prop]);
			}
		}
	}
}