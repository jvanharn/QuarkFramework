<?php
/**
 * Random string generation class.
 * 
 * @package		Quark-Framework
 * @version		$Id: random.php 73 2013-02-10 15:01:47Z Jeffrey $
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
namespace Quark\Security;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * (Static) Random Generation Class.
 * 
 * Tries to get the best random data available on the current system.
 */
class Random {
	/**
	 * The minimum number the random generator can return.
	 * 
	 * Used for converting range.
	 */
	const PSEUDONUM_MIN = 100000000;
	
	/**
	 * The maximum number the random generator can return.
	 * 
	 * Used for converting range.
	 */
	const PSEUDONUM_MAX = 9999999999999999;
	
	/**
	 * Get a random integer.
	 * 
	 * Synonym for mt_rand.
	 * @see mt_rand()
	 * @param integer $min Minimal value.
	 * @param integer $max Maximal value.
	 * @return integer
	 */
	public static function integer($min=0, $max=999){
		return mt_rand($min, $max);
	}
	
	/**
	 * Get a random floating point number.
	 * @link http://www.php.net/manual/en/function.mt-getrandmax.php Pulled literally from the PHP documentation.
	 * @param integer $min Minimal value.
	 * @param integer $max Maximal value.
	 * @return float
	 */
	public static function float($min=0, $max=1){
		return $min + mt_rand() / mt_getrandmax() * ($max - $min);
	}
	
	/**
	 * Get a random ASCII character.
	 * @return string The character.
	 */
	public static function char(){
		switch(mt_rand(1,3)){
			case 1:
				return chr(mt_rand(48, 57));
			case 2:
				return chr(mt_rand(65, 90));
			case 3:
				return chr(mt_rand(97, 122));
		}
	}
	
	/**
	 * Get a string of random hexadecimal characters.
	 * 
	 * This function will return 0-9a-f characters, for more range use the bytes or string method.
	 * @param integer $length Length of the string in (ascii) characters.
	 * @return string String of the given length.
	 */
	public static function hexadecimal($length=32){
		return substr(bin2hex(self::bytes(ceil($length / 2))), 0, $length);
	}
	
	/**
	 * Get a string of randomized ascii characters.
	 * 
	 * By default this method will return any ascii human readable character, but if you define the chars list, it will only return characters in that list.
	 * @param integer $length Length of the string in characters.
	 * @param string $chars Characters that should be in the string.
	 * @return string String of the given length.
	 */
	public static function string($length=32, $chars=null){
		$buffer = self::bytes($length);
		if(empty($chars))
			return substr(convert_uuencode($buffer), 0, $length);
		else{
			$result = '';
			$numchars = strlen($chars);
			for($i=0; $i<$length; $i++){
				for($b=ord($buffer[$i]); $b>=$numchars; $b-=$numchars); // Find index
				$result .= $chars[$b];
			}
			return $result;
		}
	}
	
	/**
	 * Get a string of randomized binary bytes.
	 * 
	 * By default this method will return any randomized byte (Mostly in ascii range 0-255).
	 * @param integer $length Length of the string in bytes.
	 * @return string The number of bytes requested.
	 */
	public static function bytes($length=32){
		$buffer = '';
		if(function_exists('mcrypt_create_iv'))
			$buffer = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
		if(empty($buffer) && function_exists('openssl_random_pseudo_bytes'))
			$buffer = openssl_random_pseudo_bytes($length);
		if(empty($buffer) && is_readable('/dev/urandom') && ($f = @fopen('/dev/urandom', 'r'))){
			$buffer = fread($f, $length);
			fclose($f);
		}
		for($i=strlen($buffer); $i<$length; $i++)
			$buffer .= chr(self::integer(0, 255));
		return $buffer;
	}
	
	/**
	 * Get a random boolean value.
	 * @return boolean
	 */
	public static function boolean(){
		return (bool) self::integer(0, 1);
	}
	
	/**
	 * Shuffle an array and return the result.
	 * 
	 * This method has more entropy than the standard php array_shuffle function. This (off-course) comes at a performance cost, only use this when truly needed.
	 * @param array $array Array to shuffle.
	 * @param integer $entropy How many times elements should be switched. (Defaults to $array length times two)
	 * @return array Shuffled array.
	 */
	public static function shuffle(array $array, $entropy=null){
		$length = count($array);
		
		if(empty($entropy) || $entropy < 1)
			$entropy = $length*2;
		
		for($i=$entropy; $i>=0; $i--){
			$source	= self::integer(0, $length);
			$dest	= self::integer(0, $length);
			$cache	= $array[$source];
			
			$array[$source]	= $array[$dest];
			$array[$dest]	= $cache;
		}
		unset($source, $dest, $cache);
		
		return $array;
	}
	
	/**
	 * Pick a certain number of elements randomly from an array.
	 * 
	 * Similar to array_rand.
	 * @param array $array Source array to pick from.
	 * @param integer $elements Number of elements to pick.
	 * @return array Randomly picked elements.
	 */
	public static function pick(array $array, $elements=1){
		$length = count($array);
		$buffer = array();
		
		for($i=0; $i<(int)$elements; $i++)
			$buffer[] = $array[self::integer(0, $length)];
		
		return $buffer;
	}
	
	/**
	 * Pseudo Random Number Generator.
	 * 
	 * This generator /always/ returns the same result with the same seed, which
	 * is usefull in some cases where you want persistent values.
	 * @param integer $seed Seed to use for the generator.
	 * @return integer The pseudo random number.
	 */
	public static function pseudoNumber($seed, $iterations=10){
		$newseed = (int) str_pad((string) $seed, 9, (string) $seed);
		for(; $iterations>0; $iterations--){
			$range = (int) substr((string) $newseed, 3, 9);
			$newseed = pow($range, 2);
		}
		return (int) substr((string) $newseed, 0, 16);
	}
}