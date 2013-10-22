<?php
/**
 * Default Collection of Filters
 * 
 * @package		Quark-Framework
 * @version		$Id: filters.php 70 2013-01-28 22:11:34Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 2, 2011
 * @copyright	Copyright (C) 2011 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2011 Jeffrey van Harn
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
namespace Quark\Filter;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Default Collections for Character filters
if(!defined('CONTAINS_ALPHA_UPPER'))	define('CONTAINS_ALPHA_UPPER',	'ABCDEFGHIJKLMNOPQRSTUVWQYZ');
if(!defined('CONTAINS_ALPHA_LOWER'))	define('CONTAINS_ALPHA_LOWER',	'abcdefghijklmnopqrstuvwxyz');
if(!defined('CONTAINS_ALPHA'))			define('CONTAINS_ALPHA',		CONTAINS_ALPHA_UPPER.CONTAINS_ALPHA_LOWER);
if(!defined('CONTAINS_DECIMAL'))		define('CONTAINS_DECIMAL',		'0123456789');
if(!defined('CONTAINS_ALPHANUMERIC'))	define('CONTAINS_ALPHANUMERIC',	CONTAINS_ALPHA.CONTAINS_DECIMAL); // Can contain only alpha and decimal characters like abcABC0123 etc.
if(!defined('CONTAINS_HEXADECIMAL'))	define('CONTAINS_HEXADECIMAL',	CONTAINS_DECIMAL.'abcdefABCDEF');

class Filters{
	/**
	 * Filters out all characters but the given character set(Based on the whitelist principe)
	 * @param string $string Input string
	 * @param string $options Options array('What chars to allow', 'Whether to stop when non allowed char is found')
	 * @subpackage Filters
	 * @access private
	 */
	public static function CHARS($string, array $options=array()){
		// Set the allowed chars
		if(!isset($options[0]) || $options[0] == null){
			$allowed = CONTAINS_ALPHANUMERIC;
		}else if(is_array($options[0])){
			$allowed = $options[0];
		}else{
			$allowed = str_split($options[0], 1);
		}
		$return = '';
		$chars = str_split($string, 1);
		foreach($chars as $char){
			if(in_array($char, $allowed)){
				$return .= $char;
			}else if(isset($options[1]) && $options[1]){
				return $return;
			}
		}
		return $return;
	}
	
	/**
	 * Filters out all unwanted chars(Based on the Blacklist principle)
	 * @param string $string Input string
	 * @param string $options Options array('What chars to rem(Defaults to: array()(Nothing will be filtered))')
	 * @subpackage Filters
	 * @access private
	 */
	public static function BLACKLIST_CHARS($string, array $options=array()){
		// Set the blacklisted chars
		if(!isset($options[0]) || $options[0] == null){
			$da = array();
		}else if(is_array($options[0])){
			$da = $options[0];
		}else{
			$da = str_split($options[0], 1);
		}
		$return = '';
		$chars = str_split($string, 1);
		foreach($chars as $char){
			if(!in_array($char, $da)){
				$return .= $char;
			}else if(isset($options[1]) && $options[1]){
				return $return;
			}
		}
		return $return;
	}
	
	/**
	 * Encodes an email adress to it's hex equivalent numbers
	 * @param string $string Input email-adress
	 * @param string $options Options array('Whether or not to wrap it in an anchor tag(Defaults to: false)')
	 * @subpackage Filters
	 * @access private
	 */
	public static function ENCODE_EMAIL($string, $options=array()){
		// Convert it to html hex
		$return = '';
		$chars = str_split($string, 1);
		foreach($chars as $char){
			$return .= '&#'.ord($char).';';
		}
		// If the email chould be wrapped in a Anchor tag
		if(isset($options[0]) && $options[0]){
			return '<a href="mailto:'.$return.'">'.$return.'</a>';
		}else{
			return $return;
		}
	}
}