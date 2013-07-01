<?php
/**
 * Default Collection of Validators
 * 
 * @package		Quark-Framework
 * @version		$Id: validators.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		December 27, 2011
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

class Validators{
	/**
	 * Filters out all characters but the given character set(Based on the whitelist principe)
	 * @param string $string Input string
	 * @param string $options Options array('What chars to allow')
	 * @subpackage Filters
	 * @access private
	 */
	function CHARS($string, array $options=array()){
		// Set the allowed chars
		if(!isset($options[0]) || $options[0] == null){
			$allowed = CONTAINS_ALPHANUMERIC;
		}else if(is_array($options[0])){
			$allowed = is_array($options[0]);
		}else{
			$allowed = str_split($options[0], 1);
		}

		$chars = str_split($string, 1);
		foreach($chars as $char){
			if(!in_array($char, $allowed)){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Filters out all unwanted chars(Based on the Blacklist principle)
	 * @param string $string Input string
	 * @param string $options Options array('What chars to rem(Defaults to: array()(Nothing will be filtered))')
	 * @subpackage Filters
	 * @access private
	 */
	function BLACKLIST_CHARS($string, array $options=array()){
		// Set the blacklisted chars
		if(!isset($options[0]) || $options[0] == null){
			$da = array();
		}else if(is_array($options[0])){
			$da = $options[0];
		}else{
			$da = str_split($options[0], 1);
		}
		$chars = str_split($string, 1);
		foreach($chars as $char){
			if(in_array($char, $da)){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * RFC Email Parser
	 * 
	 * Validates a email/adress based on RFC 822/2822/5322 etc.
	 * The Comments and whitespaces where stripped of this function
	 * Original source, with comments at: https://github.com/iamcal/rfc822/blob/master/rfc822.php
	 * @version		Revision: 2719
	 * @author		Cal Henderson <cal@iamcal.com>
	 * @link		http://code.iamcal.com/php/rfc822/
	 * @since		September 10, 2009
	 * @copyright	Copyright (C) Cal Henderson
	 * @license		Creative Commons Attribution-ShareAlike 2.5 License
	 * 
	 * @param string $string Input email/adress
	 * @param array $options None
	 * @access private
	 */
	function EMAIL($email){
		if(strlen($email) > 255)return false;
		$no_ws_ctl = "[\\x01-\\x08\\x0b\\x0c\\x0e-\\x1f\\x7f]";
		$alpha = "[\\x41-\\x5a\\x61-\\x7a]";
		$digit = "[\\x30-\\x39]";
		$cr = "\\x0d";
		$lf = "\\x0a";
		$crlf = "(?:$cr$lf)";
		$obs_char = "[\\x00-\\x09\\x0b\\x0c\\x0e-\\x7f]";
		$obs_text = "(?:$lf*$cr*(?:$obs_char$lf*$cr*)*)";
		$text = "(?:[\\x01-\\x09\\x0b\\x0c\\x0e-\\x7f]|$obs_text)";
		$text = "(?:$lf*$cr*$obs_char$lf*$cr*)";
		$obs_qp = "(?:\\x5c[\\x00-\\x7f])";
		$quoted_pair = "(?:\\x5c$text|$obs_qp)";
		$wsp = "[\\x20\\x09]";
		$obs_fws = "(?:$wsp+(?:$crlf$wsp+)*)";
		$fws = "(?:(?:(?:$wsp*$crlf)?$wsp+)|$obs_fws)";
		$ctext = "(?:$no_ws_ctl|[\\x21-\\x27\\x2A-\\x5b\\x5d-\\x7e])";
		$ccontent = "(?:$ctext|$quoted_pair)";
		$comment = "(?:\\x28(?:$fws?$ccontent)*$fws?\\x29)";
		$cfws = "(?:(?:$fws?$comment)*(?:$fws?$comment|$fws))";
		$outer_ccontent_dull = "(?:$fws?$ctext|$quoted_pair)";
		$outer_ccontent_nest = "(?:$fws?$comment)";
		$outer_comment = "(?:\\x28$outer_ccontent_dull*(?:$outer_ccontent_nest$outer_ccontent_dull*)+$fws?\\x29)";
		$atext = "(?:$alpha|$digit|[\\x21\\x23-\\x27\\x2a\\x2b\\x2d\\x2f\\x3d\\x3f\\x5e\\x5f\\x60\\x7b-\\x7e])";
		$atom = "(?:$cfws?(?:$atext)+$cfws?)";
		$qtext = "(?:$no_ws_ctl|[\\x21\\x23-\\x5b\\x5d-\\x7e])";
		$qcontent = "(?:$qtext|$quoted_pair)";
		$quoted_string = "(?:$cfws?\\x22(?:$fws?$qcontent)*$fws?\\x22$cfws?)";
		$quoted_string = "(?:$cfws?\\x22(?:$fws?$qcontent)+$fws?\\x22$cfws?)";
		$word = "(?:$atom|$quoted_string)";
		$obs_local_part = "(?:$word(?:\\x2e$word)*)";
		$obs_domain = "(?:$atom(?:\\x2e$atom)*)";
		$dot_atom_text = "(?:$atext+(?:\\x2e$atext+)*)";
		$dot_atom = "(?:$cfws?$dot_atom_text$cfws?)";
		$dtext = "(?:$no_ws_ctl|[\\x21-\\x5a\\x5e-\\x7e])";
		$dcontent = "(?:$dtext|$quoted_pair)";
		$domain_literal = "(?:$cfws?\\x5b(?:$fws?$dcontent)*$fws?\\x5d$cfws?)";
		$local_part = "(($dot_atom)|($quoted_string)|($obs_local_part))";
		$domain = "(($dot_atom)|($domain_literal)|($obs_domain))";
		$addr_spec = "$local_part\\x40$domain";
		while(1){
			$_new = preg_replace("!$outer_comment!", '', $email);
			if(strlen($_new) == strlen($email)) break;
			$email = $_new;
		}
		if(!preg_match("!^$addr_spec$!", $email, $m)) return false;
		$bits = array(
			'local' => $m[1],
			'local-atom' => $m[2],
			'local-quoted' => $m[3],
			'local-obs' => $m[4],
			'domain' => $m[5],
			'domain-atom' => $m[6],
			//'domain-literal' => @$m[7],
			//'domain-obs' => @$m[8],
		);
		if(strlen($bits['local']) > 64) return false;
		if(strlen($bits['domain']) > 255) return false;
		if(isset($m[7])){
			$Snum = "(\d{1,3})";
			$IPv4_address_literal = "$Snum\.$Snum\.$Snum\.$Snum";
			$IPv6_hex = "(?:[0-9a-fA-F]{1,4})";
			$IPv6_full = "IPv6\:$IPv6_hex(:?\:$IPv6_hex){7}";
			$IPv6_comp_part = "(?:$IPv6_hex(?:\:$IPv6_hex){0,5})?";
			$IPv6_comp = "IPv6\:($IPv6_comp_part\:\:$IPv6_comp_part)";
			$IPv6v4_full = "IPv6\:$IPv6_hex(?:\:$IPv6_hex){5}\:$IPv4_address_literal";
			$IPv6v4_comp_part = "$IPv6_hex(?:\:$IPv6_hex){0,3}";
			$IPv6v4_comp = "IPv6\:((?:$IPv6v4_comp_part)?\:\:(?:$IPv6v4_comp_part\:)?)$IPv4_address_literal";
			if (preg_match("!^\[$IPv4_address_literal\]$!", $bits['domain'], $m)){
				if (intval($m[1]) > 255) return false;
				if (intval($m[2]) > 255) return false;
				if (intval($m[3]) > 255) return false;
				if (intval($m[4]) > 255) return false;
			}else{
				while (1){
					if (preg_match("!^\[$IPv6_full\]$!", $bits['domain'])) break;
					if (preg_match("!^\[$IPv6_comp\]$!", $bits['domain'], $m)){
						list($a, $b) = explode('::', $m[1]);
						$folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
						$groups = explode(':', $folded);
						if (count($groups) > 6) return false;
						break;
					}
					if (preg_match("!^\[$IPv6v4_full\]$!", $bits['domain'], $m)){
						if (intval($m[1]) > 255) return false;
						if (intval($m[2]) > 255) return false;
						if (intval($m[3]) > 255) return false;
						if (intval($m[4]) > 255) return false;
						break;
					}
					if (preg_match("!^\[$IPv6v4_comp\]$!", $bits['domain'], $m)){
						list($a, $b) = explode('::', $m[1]);
						$b = substr($b, 0, -1); # remove the trailing colon before the IPv4 address
						$folded = (strlen($a) && strlen($b)) ? "$a:$b" : "$a$b";
						$groups = explode(':', $folded);
						if (count($groups) > 4) return false;
						break;
					}
					return false;
				}
			}            
		}else{
			$labels = explode('.', $bits['domain']);
			if(count($labels) == 1) return false;
			foreach($labels as $label){
				if(strlen($label) > 63) return false;
				if(substr($label, 0, 1) == '-') return false;
				if(substr($label, -1) == '-') return false;
			}
			if(preg_match('!^[0-9]+$!', array_pop($labels))) return false;
		}
		return true;
	}
	
	/**
	 * Validates an email adress
	 * @param string $string Input email-adress
	 * @param string $options Options
	 * @subpackage Filters
	 * @access private
	 */
	function EMAIL_EXISTS($string, $options=array()){
		// Check if the emailadress is valid
		if(!(bool)_VALIDATE_EMAIL($string)) return false;

		// Check if the domain of the email adress exists
		list($ad, $domain) = explode('@', $string);
		if(function_exists('checkdnsrr')){
			if(!(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) // Check domain in DNS
				return false;
		}else{
			if(gethostbyname($domain) == $domain)
				return false;
		}
		return true;
	}
	
	/**
	 * Checks the length or size of the string/integer/array
	 * @param mixed $mixed Input variable
	 * @param string $options An array with a max of 2 values
	 * @subpackage Filters
	 * @access private
	 */
	function SIZE($mixed, array $options=array()) {
		// Get the Length
		if(is_numeric($mixed))
			$length = $mixed;
		else if(is_array($mixed))
			$length = count($mixed);
		else if(is_string($mixed))
			$length = strlen($mixed);
		else
			return false;
		
		/**** Check the length ****/
		// Length between value and value
		if(isset($options[0]) && is_numeric($options[0]) && isset($options[1]) && is_numeric($options[1]))
			return (($length < $options[0]) || ($length > $options[1]));
		
		// Length less than(default)
		else if((isset($options[0]) && $options[0] == '<' && isset($options[1]) && is_numeric($options[1])) || (isset($options[0]) && is_numeric($options[0])))
			return ($length < $options[0]);
		
		// Length more than
		else if(isset($options[0]) && $options[0] == '>' && isset($options[1]) && is_numeric($options[1]))
			return ($length > $options[0]);
		
		else return -1;
	}
}