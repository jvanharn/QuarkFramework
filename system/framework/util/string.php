<?php
/**
 * Static Ascii String Helper functions.
 * 
 * @package		Quark-Framework
 * @version		$Id: string.php 72 2013-02-03 22:19:22Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 2, 2013
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
namespace Quark\Util;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Utility functions for Strings.
 */
class String {
	/**
	 * Takes the string and pad's and cut's in it untill it is of the given length.
	 * @param string $string String to pad and cut.
	 * @param integer $length Length of the string.
	 * @return string
	 */
	public static function lengthen($string, $length){
		return substr(str_pad((string) $string, $length, (string) $string), 0, $length);
	}
	
	public static function insert($insert, $position, $string){
		return substr_replace($string, $insert, (int) $position, 0);
	}
}