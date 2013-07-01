<?php
/**
 * Tries to bring strict TypeHinting and more dynamic objects to PHP
 * 
 * @package		Quark-Framework
 * @version		$Id: type.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		19 december 2012
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
namespace Quark\Util\Type;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple TypeHinting implementation
 */
abstract class Type implements Comparable {
	const __default = null;
	
	protected $value;
	
	public function __construct($initial_value=null){
		if(is_null($initial_value)){
			$cls = get_called_class();
			$this->value = $cls::__default;
		}else if($this->check($initial_value))
			$this->value = $initial_value;
		else throw new \UnexpectedValueException('Invalid value type for type "'.get_called_class().'".');
	}
	
	public function compareTo(Comparable $subject) {
		if($subject instanceof Type){
			$val = $subject->get();
			if($val == $this->value)
				return 0;
			if($val > $this->value)
				return 1;
			if($val < $this->value)
				return -1;
			else return false;
		}else return false;
	}
	
	abstract public function check($value);

	public function get(){
		return $this->value;
	}
	
	public function set($value){
		if($this->check($value))
			$this->value = $value;
		else throw new \UnexpectedValueException('Invalid value type for type "'.get_called_class().'".');
	}
	
	public function __invoke($value){
		$this->set($value);
	}
	
	public function __toString(){
		return $this->get();
	}
}

class String extends Type {
	const __default = "";
	
	public function check($value) {
		return is_string($value);
	}
}

class Integer extends Type {
	const __default = 0;
	
	public function check($value) {
		return is_integer($value);
	}	
}

class Float extends Type {
	const __default = 0.0;
	
	public function check($value) {
		return is_float($value);
	}
}

class Boolean extends Type {
	const __default = false;
	
	public function check($value) {
		return is_bool($value);
	}	
}

abstract class Enum extends Integer {
	const __default = 0;
	
	protected $constants;
	
	public function getConstantList(){
		if($this->constants == null){
			$this->constants = (new \ReflectionClass(get_called_class()))->getConstants();
		}
		return $this->constants;
	}
	
	public function name(){
		array_search($this->value, $this->getConstantList());
	}
	
	public function check($value){
		return in_array($value, $this->getConstantList());
	}
}