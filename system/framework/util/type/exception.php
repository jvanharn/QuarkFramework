<?php
/**
 * Type Exception
 * 
 * @package		Quark-Framework
 * @version		$Id: exception.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		21 december 2012
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
 * Invalid Argument Type Exception
 */
class InvalidArgumentTypeException extends \RuntimeException {
	/**
	 * @param string $name
	 * @param string $expectedType
	 * @param mixed $value
	 */
	public function __construct($name, $expectedType, $value){
		parent::__construct('The argument $'.$name.' was of type "'.$expectedType.'" but found '.(empty($value)?'(empty) ':'').'"'.gettype($value).'"', E_ERROR);
		
		// Change the line number and file tot the previously called number
		$trace = $this->getTrace();
		$this->line = $trace[1]['line'];
		$this->file = $trace[1]['file'];
	}
}

class_alias('\Quark\Util\Type\InvalidArgumentTypeException', '\InvalidArgumentTypeException');