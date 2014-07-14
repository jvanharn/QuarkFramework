<?php
/**
 * Makes debugging a peace of cake.
 * 
 * @package		Quark-Framework
 * @version		$Id: debug.php 23 2012-01-23 18:52:43Z Jeffrey $
 * @author		Jeffrey van Harn
 * @since		June 23, 2011
 * @copyright	Copyright (C) 2006-2009 Jeffrey van Harn
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

// Set the namespace
namespace Quark\Error;

// Prevent acces to this standalone file
if(!defined('DIR_BASE')) exit;

/**
 * Adds extended Debugging functionality to the error handler of PageTree CMS.
 * 
 * @package Quark-Framework
 * @subpackage System
 */
class Debug{
	/**
	 * Returns the Line of a given file
	 * @param string $file Filename to look in.
	 * @param int $line The line to extract.
	 * @return bool|string
	 */
	public static function getLine($file, $line){
		// Read file if possible
		if(file_exists($file)) $l = file($file);
		else return false;
		// Get the line and highlight it
		$r = explode('<br />',(highlight_string('<?php'.PHP_EOL.trim($l[$line-1]), true)));
		// Get the right lines
		if(substr($r[1],0,7) == '</span>')
			$n = trim(substr($r[1],7)).'</span>';
		else
			$n = '<span style="color: #0000BB">'.trim($r[1]).'</span>';
		// Remove excess span and code tags and return
		return substr($n, 0, strpos($n, "\n"));
	}
	
	/**
	 * Returns the last function in a string form
	 * @param array $trace The trace array.
	 * @return string
	 */
	public static function getLastFunctionAsString($trace){
		if(is_array($trace) && count($trace) >= 1){
			if(isset($trace[0]['file'])){
				if(isset($trace[0]['class']))
					return 'function: <b>'.$trace[0]['class'].'::'.$trace[0]['function'].'()</b> on line:'.$trace[0]['line'].' in file:'.$trace[0]['file'];
				else if($trace[0]['function'] == 'include')
					return 'included file: "'.$trace[0]['args'][0].'" on line: '.$trace[0]['line'];
				else
					return 'function: <b>'.$trace[0]['function'].'()</b> on line:'.$trace[0]['line'].' in file:'.$trace[0]['file'];
			}else{
				return 'internally called function: <b>'.$trace[0]['function'].'</b>';
			}
		}else{
			$trace = debug_backtrace();
			return 'request entry file <u>"'.$trace[count($trace)-1]['file'].'"</u> on line: '.$trace[count($trace)-1]['line'];
		}
	}

	/**
	 * Get the last called function(Or many back (:) from the current stack
	 * @param int $back How many functions back to look?
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return callback|bool A function name or an array with class and function name.
	 */
	public static function getLastCallback($back=0){
		// Check the parameter
		if(!is_int($back)) throw new \InvalidArgumentException('The param $back should be of type "Integer" but got "'.gettype($back).'".');
		
		// Make sure it does not return the call to this function
		$back += 1;
		
		// Get the backtrace
		if(version_compare(PHP_VERSION, '5.4.0') >= 0)
			$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT+DEBUG_BACKTRACE_IGNORE_ARGS, $back+1);
		else if(version_compare(PHP_VERSION, '5.3.6') >= 0)
			$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT+DEBUG_BACKTRACE_IGNORE_ARGS);
		else if(version_compare(PHP_VERSION, '5.3.0') >= 0)
			$trace = debug_backtrace(true);
		else throw new \RuntimeException('This code shouldn\'t be running on anything less than PHP 5.3! It seems you are running '.PHP_VERSION.'...');
		
		// Return the callback
		if(isset($trace[$back])){
			if(isset($trace[$back]['class'])){
				if($trace[$back]['type'] == '->')
					return array($trace[$back]['object'], $trace[$back]['function']);
				else return array($trace[$back]['class'], $trace[$back]['function']);
			}else return $trace[$back]['function'];
		}else return false;
	}

	/*
	 * Get the $object context from the last method that called(If it was an object)
	 * @param int $back How many functions back to look?
	 * @param bool $object Include the object in the returned backtrace
	 * @return callback|bool An function name or an array with class and function name.
	 */
	/*public static function getCallingObject($back=0){
		// Check the parameters
		if(!is_int($back)) throw new \Exception('The param $back should be of type "Integer" but got "'.gettype($back).'".');
		if(!is_bool($object)) throw new \Exception('The param $object should be of type "Boolean" but got "'.gettype($object).'".');
		
		// Make sure it does not return th call to this function
		$back += 1;
		
		// Get the backtrace
		if(version_compare(PHP_VERSION, '5.4.0') >= 0)
			$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT+DEBUG_BACKTRACE_IGNORE_ARGS, $back+1);
		else if(version_compare(PHP_VERSION, '5.3.6') >= 0)
			$trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT+DEBUG_BACKTRACE_IGNORE_ARGS);
		else if(version_compare(PHP_VERSION, '5.3.0') >= 0)
			$trace = debug_backtrace(true);
		else throw new \RuntimeException('This code shouldn\'t be running on anything less than PHP 5.3! It seems you are running '.PHP_VERSION.'...');
		
		
	}*/
	
	/**
	 * Converts an trace array to an string(Like one from debug_backtrace())
	 * @param array $trace The trace array to be converted
	 * @param bool $code Preview the code that is resulting in the error
	 * @return string
	 */
	public static function traceToString($trace, $code=false){
		$t = count($trace);
		$ret = '';
		// Make the textual version of the backtrace for each item
		for($i=0;$i<$t;$i++){
			// Step number
			$ret .= '#'.$i.' ';
			// Called function
			if(!isset($trace[$i]['class'])) $ret .= $trace[$i]['function'].'(';
			else $ret .= $trace[$i]['class'].$trace[$i]['type'].$trace[$i]['function'].'(';
			// Called functions parameters
			if(isset($trace[$i]['args'])){
				if(count($trace[$i]['args']) != 0){
					foreach($trace[$i]['args'] as $k=>$arg){
						if($k<(count($trace[$i]['args'])-1))
							$ret .= self::exportVariable($arg, $code).', ';
						else
							$ret .= self::exportVariable($arg, $code);
					}
				}
				$ret .= ') ';
			}
			if(isset($trace[$i]['line'])){
				// Called at
				$ret .= 'called at <span style="color:#999">['.$trace[$i]['file'].':<b>'.$trace[$i]['line'].'</b>]</span>'.PHP_EOL;
				// Add the lines
				if($code == true){
					$ret .= '<div style="margin-left: 10px;margin-bottom:5px">'.self::getLine($trace[$i]['file'], $trace[$i]['line']).'</div>';
				}
			}else $ret .= 'from an <i>internal call</i>'.PHP_EOL;
		}
		$ret .= '#'.$i.' {main}';
		return $ret;
	}

	/**
	 * Pretty export a php variable.
	 * @param mixed $var
	 * @param bool $code
	 * @return mixed|string
	 */
	public static function exportVariable($var, $code=false){
		if(is_object($var)){
			if($code === true){
				$highlighted = '';
				try { // lets prevented nested errors, as that really /is/ deadly.
					// @todo Replace with class reflection to prevent Infinite Recursion depth Fatals caused by var_export.
					//$highlighted = @highlight_string('<?php'.PHP_EOL.@var_export($var, true), true);
					$highlighted = \Reflection::export(new \ReflectionClass($var), true);
				}catch(\Exception $e){}
				$content = '<a style="text-decoration: none" href="#" title="Click to see the complete object." onclick="var w = window.open(\'\', \'\', \'width=600,height=400,resizeable,scrollbars\');w.document.body.appendChild(this.children[1].children[0]);w.document.close();return false;">';
				$content .= '<span style="color: #007700">(Object) ['.get_class($var).']</span><span style="display:none"><pre>'.$highlighted.'</pre></span></a>';
				return $content;
			}else return '(Object) ['.get_class($var).']';
		}else if(is_string($var) && strlen($var) > 30){
			if($code === true){
				$content = '<a style="text-decoration: none" href="#" title="Click to see the complete string." onclick="var w = window.open(\'\', \'\', \'width=600,height=400,resizeable,scrollbars\');w.document.body.appendChild(this.children[1].children[0]);w.document.close();return false;">';
				$content .= '<span style="color: #DD0000">[String('.strlen($var).')]</span><span style="display:none"><pre>'.@var_export($var, true).'</pre></span></a>';
				return $content;
			}else return '[String('.strlen($var).')]';
		}else{
			if($code === true)
				return @var_export($var, true);
			else
				return '['.ucfirst(gettype($var)).']';
		}
	}
}