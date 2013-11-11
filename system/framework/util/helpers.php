<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Prevent individual file access
if(!defined('DIR_BASE')) exit;


#region One-liner Helpers
/**
 * Function that always returns the boolean true.
 *
 * Can for example be used for one liner return statements whilst still keeping code readable. (Eg wrap a void returning function in a if-else statement with this to return positive as well as executing something.)
 * @return bool Always returns true.
 */
function positive(){ return true; }

/**
 * Function that always returns the boolean false.
 *
 * Can for example be used for one liner return statements whilst still keeping code readable. (Eg wrap a void returning function in a if-else statement with this to return negative as well as executing something.)
 * @return bool Returns false.
 */
function negative(){ return false; }

/**
 * Create an class instance/object.
 * @param string $type Class name to create an instance of.
 * @return object
 */
function createInstance($type){
	return new $type;
}
#endregion

#region Version Comparison
/**
 * Join/merge two version rules into one where the 1 version rule will satisfy both rules.
 * @param string|array $rule1
 * @param string|array $rule2
 * @throws RuntimeException When the rules conflict with each other an exception will be thrown.
 * @return array Valid version qualifier rule.
 */
function merge_version_rules($rule1, $rule2){
	$rule1 = normalize_version_rule($rule1);
	$rule2 = normalize_version_rule($rule2);

	$rule_match = function($operator1, $operator2, $fixed=false) use (&$rule1, &$rule2){
		if($rule1[0] == $operator1 && $rule2[0] == $operator2) return true;
		else if($rule2[0] == $operator1 && $rule1[0] == $operator2 && $fixed == false){
			$tmp = $rule1;
			$rule1 = $rule2;
			$rule2 = $tmp;
			return true;
		}
		else return false;
	};

	// created ranges
	if($rule_match('>', '<')){ // Natural range ><
		if(uniform_version_compare($rule1[1], $rule2[1]) >= 0)
			return array('><', array($rule1[1], $rule2[1]));
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first requires more than version "'.$rule1[1].'" the second requires less than "'.$rule2[1].'".');
	}else if($rule_match('>=', '<=', true)){ // Natural range [..]
		$ranged = uniform_version_compare($rule1[1], $rule2[1]);
		if($ranged == 0)
			return array('==', $rule1[1]);
		else if($ranged > 0)
			return array('[..]', array($rule1[1], $rule2[1]));
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first requires more than eq. version "'.$rule1[1].'" the second requires less than eq. "'.$rule2[1].'".');
	}else if($rule_match('>=', '<') || $rule_match('<=', '>')){ // >(=) AND range [..], ><
		throw new RuntimeException('Version qualifier/rule exception; Sorry, when it comes to combining >= and < or vice versa operators I have no way to intelligently and above all *consistently* solve this problem.');

	// conflicting ranges
	}else if($rule_match('[..]', '[..]')){
		// check if $rule1 can be contained within $rule2
		if(uniform_version_compare($rule1[1][0], $rule2[1][0]) >= 0 && uniform_version_compare($rule1[1][1], $rule2[1][1]) <= 0)
			return $rule1;
		// .. or vice versa
		else if(uniform_version_compare($rule2[1][0], $rule1[1][0]) >= 0 && uniform_version_compare($rule2[1][1], $rule1[1][1]) <= 0)
			return $rule2;
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first rule cannot be contained in the second or vice versa thus I was unable to combine these two version rules.');
	}else if($rule_match('><', '><')){
		// check if $rule1 can be contained within $rule2
		if(uniform_version_compare($rule1[1][0], $rule2[1][0]) > 0 && uniform_version_compare($rule1[1][1], $rule2[1][1]) < 0)
			return $rule1;
		// .. or vice versa
		else if(uniform_version_compare($rule2[1][0], $rule1[1][0]) > 0 && uniform_version_compare($rule2[1][1], $rule1[1][1]) < 0)
			return $rule2;
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first rule cannot be contained in the second or vice versa thus I was unable to combine these two version rules.');

	// possible conflicting with exact matches
	}else if($rule_match('==', '==')){ // Conflicting exact versions
		if(uniform_version_compare($rule1[1], $rule2[1]) == 0)
			return array('==', $rule1[1]);
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first requires version "'.$rule1[1].'" the second requires "'.$rule2[1].'".');
	}else if($rule_match('>', '==')){
		if(uniform_version_compare($rule1[1], $rule2[1]) > 0)
			return array('==', $rule2[1]);
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first requires more than version "'.$rule1[1].'" the second requires "'.$rule2[1].'".');
	}else if($rule_match('>=', '==')){
		if(uniform_version_compare($rule1[1], $rule2[1]) >= 0)
			return array('==', $rule2[1]);
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first requires at least version "'.$rule1[1].'" the second requires "'.$rule2[1].'".');
	}else if($rule_match('<', '==')){
		if(uniform_version_compare($rule1[1], $rule2[1]) < 0)
			return array('==', $rule2[1]);
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first requires less than version "'.$rule1[1].'" the second requires "'.$rule2[1].'".');
	}else if($rule_match('<=', '==')){
		if(uniform_version_compare($rule1[1], $rule2[1]) <= 0)
			return array('==', $rule2[1]);
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first requires maximally version "'.$rule1[1].'" the second requires "'.$rule2[1].'".');

	// possible conflict between ranges and less thans/more than/equals
	}else if($rule_match('[..]', '==')){
		if(uniform_version_compare($rule2[1], $rule1[1][0]) >= 0 && uniform_version_compare($rule2[1], $rule1[1][1]) <= 0)
			return array('==', $rule2[1]);
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first defines a range of "'.$rule1[1][0].'" - "'.$rule1[1][1].'" the second requires "'.$rule2[1].'" so does not meet the requirements of the first.');
	}else if($rule_match('[..]', '>=')){ // range is adjusted with a new minimum
		if(uniform_version_compare($rule2[1], $rule1[1][0]) >= 0 && uniform_version_compare($rule2[1], $rule1[1][1]) <= 0)
			return array('[..]', array($rule2[1], $rule1[1][1]));
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first defines a range of "'.$rule1[1][0].'" - "'.$rule1[1][1].'" the second requires "'.$rule2[1].'" or more so does not meet the requirements of the first.');
	}else if($rule_match('[..]', '<=')){ // range is adjusted with a new maximum
		if(uniform_version_compare($rule2[1], $rule1[1][0]) >= 0 && uniform_version_compare($rule2[1], $rule1[1][1]) <= 0)
			return array('[..]', array($rule1[1][0], $rule2[1]));
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first defines a range of "'.$rule1[1][0].'" - "'.$rule1[1][1].'" the second requires "'.$rule2[1].'" or less so does not meet the requirements of the first.');
	}else if($rule_match('><', '>')){ // range is adjusted with a new minimum
		if(uniform_version_compare($rule2[1], $rule1[1][0]) > 0 && uniform_version_compare($rule2[1], $rule1[1][1]) < 0)
			return array('><', array($rule2[1], $rule1[1][1]));
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first defines a range of "'.$rule1[1][0].'" - "'.$rule1[1][1].'" the second requires "'.$rule2[1].'" or more so does not meet the requirements of the first.');
	}else if($rule_match('><', '<')){ // range is adjusted with a new minimum
		if(uniform_version_compare($rule2[1], $rule1[1][0]) > 0 && uniform_version_compare($rule2[1], $rule1[1][1]) < 0)
			return array('><', array($rule1[1][0], $rule2[1]));
		else
			throw new RuntimeException('Version qualifier/rule exception; the given rules conflicted in an unresolvable way. The first defines a range of "'.$rule1[1][0].'" - "'.$rule1[1][1].'" the second requires "'.$rule2[1].'" or more so does not meet the requirements of the first.');
	}else if($rule_match('><', '>=') || $rule_match('><', '<=') || $rule_match('[..]', '<') || $rule_match('[..]', '>')){
		throw new RuntimeException('Version qualifier/rule exception; Sorry, when it comes to combining [..] and < or similar operators I have no way to intelligently and above all *consistently* solve this problem.');


		// no logic for the given combination
	}else
		throw new RuntimeException('Tried to merge two versions with a combination of two operators that have no logic programmed for them, please file a bug report to resolve this!');
}

/**
 * Check if the given $rule applies to the versioning string $version.
 * @param string $version The versioning string to validate.
 * @param string|array $rule When a string is given check that $version is minimally $rule or higher. OR When array is given expects first array element to be comparison operator and the second element to be a versioning string.
 * @throws InvalidArgumentException
 * @return bool
 */
function check_version_rule($version, $rule){
	$rule = normalize_version_rule($rule);
	switch($rule[0]){
		case '*':
			return true;
		case '>':
			return (uniform_version_compare($version, $rule[1]) > 0);
		case '<':
			return (uniform_version_compare($version, $rule[1]) < 0);
		case '>=':
			return (uniform_version_compare($version, $rule[1]) >= 0);
		case '<=':
			return (uniform_version_compare($version, $rule[1]) <= 0);
		case '==':
		//case '=':
			return (uniform_version_compare($version, $rule[1]) == 0);
		case '><':
			if(!is_array($rule[1]) || count($rule[1]) != 2)
				throw new InvalidArgumentException('Rule wasn\'t correctly formatted, expected second array element of $rule to be of type "array" for compare function *range* but got "'.gettype($rule[1]).'".');
			return (uniform_version_compare($version, $rule[1][0]) > 0) && (uniform_version_compare($version, $rule[1][1]) < 0);
		case '[..]':
		//case '[...]':
			if(!is_array($rule[1]) || count($rule[1]) != 2)
				throw new InvalidArgumentException('Rule wasn\'t correctly formatted, expected second array element of $rule to be of type "array" for compare function *range* but got "'.gettype($rule[1]).'".');
			return (uniform_version_compare($version, $rule[1][0]) >= 0) && (uniform_version_compare($version, $rule[1][1]) <= 0);
		default:
			throw new InvalidArgumentException('Argument $rule for check_version_rule is invalid; the first $rule element should be a valid operator. Expected one of >, <, >=, <=, ==, = but got "'.$rule[0].'".');
	}
}

/**
 * Normalize a versioning rule to the standard array form.
 * @param string|array $rule
 * @throws InvalidArgumentException When the rule is incorrectly formatted.
 * @return array
 */
function normalize_version_rule($rule){
	if(is_string($rule)){
		return array('>=', $rule);
	}else if(is_array($rule) && count($rule) == 2 && in_array($rule[0], array('*', '>', '<', '>=', '<=', '==', '=', '><', '[..]', '[...]'))){
		if($rule[0] == '=')
			$rule[0] = '==';
		else if($rule[0] == '[...]')
			$rule[0] = '[..]';
		return $rule;
	}else
		throw new InvalidArgumentException('Invalid $rule given to version checking function.');
}

/**
 * Compare two version strings and try to distinguish between them in the best way possible.
 * @param string $str1
 * @param string $str2
 * @param bool $use_strcmp Whether or not when no definitive answer comes from all known comparisons to try and compare the strings with strcmp (Only use when you really need a higher or lower version and are in lesser interest in identical versions))
 * @return int (<0) When ver1 < ver2, (0) When ver1 == ver2, (>0) When ver1 > ver2
 * @copyright Jeffrey vH 2013
 */
function uniform_version_compare($str1, $str2, $use_strcmp=false){
	if($str1 == $str2) return 0; // Just because; speed.

	$versioning = array('dev', 'alpha', 'beta', 'testing', 'rc', 'stable'); // In order of development stage, and score (stable > rc > beta, etc.)
	$build_prefixes = array('b', 'build', '+'); // Prefixes that, if found, indicate a build number or hash coming after this string. (Sha gets special treatment as everything after that is considered hexadecimal)
	$hash_prefixes = array('git', 'sha'); // Only helps identify identicals. Something we should be able to say something about, as they indicate pushes/commits mostly, but as they are hashes, they are completely useless. Better in every single way, except version comparison.
	$rubbish = array('i386', 'i686', 'amd64', 'ia64', 'sparc', 'armhf', 'armel', 'arm', 'powerpc', 's390x', 's390', 'mipsel', 'mips', 'bsd', '*nix', 'unix'); // Stuff that has nothing to do with version numbers, rather it indicates platforms or other nonsense that we cant really say anything about. This stuff gets removed when were falling back to strcmp after nothing has worked.

	$ver1 = str_split_version($str1, true);
	$ver2 = str_split_version($str2, true);

	// Try to determine from main version part only
	$ver1cnt = count($ver1[0]);
	$ver2cnt = count($ver2[0]);
	$longest = ($ver1cnt > $ver2cnt) ? $ver1cnt : $ver2cnt;

	for($i=0; $i<$longest; $i++){
		if(isset($ver1[0][$i])) $n1 = intval($ver1[0][$i]);
		if(isset($ver2[0][$i])) $n2 = intval($ver2[0][$i]);

		if(isset($ver1[0][$i]) && isset($ver2[0][$i])){
			if($n1 > $n2) return 1;
			if($n1 < $n2) return -1;
		}else if(isset($ver1[0][$i])){	// $ver1 doesnt have this one, assume 0 for ver2
			if($n1 > 0) return 1;
			//if($n1 < 0) return -1;
		}else{							// $ver2 doesnt have this one, idem
			if($n2 > 0) return 1;
			//if($n2 < 0) return -1;
		}
	}

	// We have been unable to find the higher/lower one, now we will have to take into consideration that the values will no longer be numeric only
	$ver1cnt = count($ver1[1]);
	$ver2cnt = count($ver2[1]);

	// Start by trying to find a versioning indicator
	$vcnt = count($versioning);
	$ver1status = $vcnt-1; // Default is stable
	$ver1pos = 0;
	for($v=0; $v<$ver1cnt; $v++){
		for($l=0; $l<$vcnt; $l++){
			if(strpos($ver1[1][$v], $versioning[$l]) > -1){
				$ver1status = $l;
				$ver1pos = $v;
			}
		}
	}
	$ver2status = $vcnt-1; // Default is stable
	$ver2pos = 0;
	for($v=0; $v<$ver2cnt; $v++){
		for($l=0; $l<$vcnt; $l++){
			if(strpos($ver2[1][$v], $versioning[$l]) > -1){
				$ver2status = $l;
				$ver2pos = $v;
			}
		}
	}
	if($ver1status > $ver2status) return -1;
	if($ver1status < $ver2status) return 1;
	if($ver1status == $ver2status && $ver1status != $vcnt-1){
		$ver1num = false;
		$ver2num = false;

		// Check if there is a number directly appended to the string
		if(strlen($versioning[$ver1status]) < strlen($ver1[1][$ver1pos])){
			$pos = strpos($ver1[1][$ver1pos], $versioning[$ver1status]);
			if($pos == 0) // Stuff after the version state string
				$str = substr($ver1[1][$ver1pos], $pos);
			else // stuff before
				$str = substr($ver1[1][$ver1pos], 0, $pos);

			$int = intval($str);
			if($str == (string) $int) // It's a number
				$ver1num = $int;
			// else: its not, drop it
		}
		if(strlen($versioning[$ver2status]) < strlen($ver2[1][$ver2pos])){
			$pos = strpos($ver2[1][$ver2pos], $versioning[$ver2status]);
			if($pos == 0) // Stuff after the version state string
				$str = substr($ver2[1][$ver2pos], $pos);
			else // stuff before
				$str = substr($ver2[1][$ver2pos], 0, $pos);

			$int = intval($str);
			if($str == (string) $int) // It's a number
				$ver2num = $int;
			// else: its not, drop it
		}

		// .. or directly after the string in the array
		if($ver1num === false && $ver1pos < $ver1cnt-1){ // Hasn't been found yet, and there are elements after this one
			$str = $ver1[1][$ver1pos+1][0]; // Get the first character of the given string
			$int = intval($str);
			if($str == (string) $int) // It is a number
				$ver1num = $int;
		}
		if($ver2num === false && $ver2pos < $ver2cnt-1){ // Hasn't been found yet, and there are elements after this one
			$str = $ver2[1][$ver2pos+1][0]; // Get the first character of the given string
			$int = intval($str);
			if($str == (string) $int) // It is a number
				$ver2num = $int;
		}

		// Compare the two numbers if both were found
		if($ver1num === false) $ver1num = 1;
		if($ver2num === false) $ver2num = 1;

		if($ver1num > $ver2num) return -1;
		if($ver1num < $ver2num) return 1;
	}

	// Find dates that may help us identify build dates or repo state (Yay these are mostly consistently formatted; e.g. 20090614 or 20131204
	// We do this by first finding a large enough continuous string of digits (exactly 8)
	$ver1date = false;
	foreach($ver1[1] as $part){
		if(ctype_digit($part) && strlen($part) >= 8){
			// Found a digit part with at least 8 digits, now hope that the date is placed at the beginning
			$year = substr($part, 0, 4);
			$month = substr($part, 4, 2);
			$day = substr($part, 6, 2);
			if(checkdate($month, $day, $year))
				$ver1date = strtotime($year.'-'.$month.'-'.$day);
		}
	}
	if($ver1date !== false){
		$ver2date = false;
		foreach($ver2[1] as $part){
			if(ctype_digit($part) && strlen($part) >= 8){
				// Found a digit part with at least 8 digits, now hope that the date is placed at the beginning
				$year = substr($part, 0, 4);
				$month = substr($part, 4, 2);
				$day = substr($part, 6, 2);
				if(checkdate($month, $day, $year))
					$ver2date = strtotime($year.'-'.$month.'-'.$day);
			}
		}

		if($ver2date !== false){
			if($ver1date > $ver2date) return -1;
			if($ver1date < $ver2date) return 1;
		}
	}

	// Try to find a build number


	// Try to find hash to distinctly identify equal (In code) versions (Only when using strcmp)
	if(!!$use_strcmp){

	}

	// Use strcmp on the rest if requested
	if(!!$use_strcmp){
		for($i1=0;$i1<$ver1cnt;$i1++){
			// Skip the rubbish
			foreach($rubbish as $bit){
				if(strpos($ver1[1][$i1], $bit) > -1)
					continue 2;
			}

			for($i2=0; $i2<$ver2cnt; $i2++){
				// Skip the rubbish
				foreach($rubbish as $bit){
					if(strpos($ver2[1][$i2], $bit) > -1)
						continue 2;
				}

				// Compare the remaining strings one by one
				$cmp = strcmp($ver1[1][$i1], $ver2[1][$i2]);
				if($cmp != 0) return $cmp;
			}
		}

		if($ver1cnt == 0) return 1; // The longer string wins if applicable
		if($ver2cnt == 0) return -1;
	}

	return 0; // Version strings probably indicate the same versions (Although in text they don't necessarily have to be identical.
}

/**
 * Splits a version string into chunks by a set of simple rules:
 * - Main version string only consists of numbers and dots
 * - The moment the first letter, dash or other character is introduced letters and numbers are grouped together (It goes into a sort of secondary version string parsing mode)
 * - After this mode gets activated chunks are split by the '$sepchr' the separation characters.
 * @param string $str The string to parse
 * @param bool $group Whether or not to group the main versioning string and the secondary string in different arrays (returns an array with the first array containing the main versioning string, and a second array containg the rest)
 * @return array
 * @copyright Jeffrey vH 2013
 */
function str_split_version($str, $group=false){
	$str = strtolower(ltrim(trim($str), 'v'));
	$sepchrs = '.-';
	$mchunks = array();
	$schunks = array();
	$cur = 0;
	$main = true;
	$strlen = strlen($str);
	for($c=0; $c<$strlen; $c++){
		if($main){
			if(is_numeric($str[$c])){
				if(isset($mchunks[$cur]))
					$mchunks[$cur] .= $str[$c];
				else
					$mchunks[$cur] = $str[$c];
			}else if($str[$c] == '.')
				$cur++;
			else{
				$main = false;
				$cur = 0;
			}
		}else{
			if(stripos($sepchrs, $str[$c]) > -1)
				$cur++;
			else {
				if(isset($schunks[$cur]))
					$schunks[$cur] .= $str[$c];
				else
					$schunks[$cur] = $str[$c];
			}
		}
	}
	if($group)
		return array($mchunks, $schunks);
	else
		return array_merge($mchunks, $schunks);
}

#endregion