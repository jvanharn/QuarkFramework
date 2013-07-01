<?php
/**
 * Static Integer/Float Helper functions.
 * 
 * @package		Quark-Framework
 * @version		$Id: number.php 73 2013-02-10 15:01:47Z Jeffrey $
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
 * Utility functions for Integers and Floats.
 */
class Number {
	/**
	 * Takes any number and modifies it to fit in a certain range.
	 * 
	 * This method is usefull for random number generation mostly.
	 * @param integer|float $number Number to fit in the given range.
	 * @param integer $min Lower bound of the range.
	 * @param integer $max Upper bound of the range.
	 * @return integer The position of the number given in the given range.
	 */
	public static function range($number, $min, $max){
		//if(is_float($number))
		//	$number = (int) floor($number);
		//else if(is_integer($number))
		//	throw new \InvalidArgumentException('Expected number to be of type "float" or "integer"');
		//$length = (strlen((string) $number));
		
		$length = (strlen((string) (int) $number)); // This first removes precision to accomodate for the otherwise extreme powered devisions.
		$devided = ($number / pow(10, $length));
		$range = ($max - $min);
		$ranged = $devided * $range;
		$corrected = (int) round($ranged);
		return $corrected + $min;
	}
	
	/**
	 * Ranged number calculater with defineable original range.
	 * 
	 * When used with a (pseudo) random number generator the method above will sometimes simply shut out certain numbers. This ranged method accomodates for that by allowing you to define the precision/range of the original number.
	 */
	public static function convertRange($number, $orig_min, $orig_max, $new_min, $new_max){
		$orange = ($orig_max - $orig_min);
		$nrange = ($new_max - $new_min) + 1;
		$devided = ($number / $orange);
		$ranged = ($devided * $nrange);
		return $ranged + $new_min;
	}
}