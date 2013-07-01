<?php
/**
 * The Headers management class
 * 
 * @package		Quark-Framework
 * @version		$Id: headers.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		October 28, 2012
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
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
namespace Quark\Document;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Document helper class
 * @author Jeffrey
 */
class Headers {
	/**
	 * Title-tag Header Type
	 * 
	 * Unique tag in the header.
	 */
	const TITLE		= 'title';
	
	/**
	 * Meta-tag Header Type
	 * 
	 * Has no content.
	 */
	const META		= 'meta';
	
	/**
	 * Link-tag Header Type
	 * 
	 * Has no content.
	 */
	const LINK		= 'link';
	
	/**
	 * Script-tag Header Type
	 * 
	 * Contents are differentiated between.
	 */
	const SCRIPT	= 'script';
	
	/**
	 * Style-tag Header Type
	 * 
	 * Contents are merged.
	 */
	const STYLE		= 'style';
	
	/**
	 * Content Behaviour - NONE
	 * 
	 * If set on a tag, means that the tag does not have any content. (And should not have any)
	 */
	const CONTENT_NONE		= 0;
	
	/**
	 * Content Behaviour - DIFFER
	 * 
	 * (Default) Every new tag of the same kind will just be added to the array.
	 */
	const CONTENT_DIFFER	= 1;
	
	/**
	 * Content Behaviour - MERGE
	 * 
	 * When the attributes are the same, the content will be merged.
	 */
	const CONTENT_MERGE		= 2;
	
	/**
	 * Content Behaviour - UNIQUE
	 * 
	 * There always should be no more than one of this tag in the document.
	 */
	const CONTENT_UNIQUE	= 3;
	
	/**
	 * Whether or not to export tags in (x)html style.
	 * @var boolean
	 */
	protected $xhtml = true;
	
	/**
	 * Whether or not to enforce strict attribute specifications
	 * @var boolean
	 */
	protected $strict = true;
	
	/**
	 * Types of headers
	 * 
	 * Usage standard (Instead of adding in code, consider using the {@see Headers::registerType} method):
	 * HEADER_TYPE => ['tag', required_attr['attr'=>'default_val'|null], optional_arg['attr', 'attr2'], has_content]
	 * @var array
	 */
	protected $types = array(
		self::TITLE		=> array(array(), null, self::CONTENT_UNIQUE),
		self::META		=> array(array(), null, self::CONTENT_NONE),
		self::LINK		=> array(array('href'=>null, 'type'=>'text/css', 'rel'=>'stylesheet'), array('media', 'title'), self::CONTENT_NONE),
		self::SCRIPT	=> array(array('type'=>'text/javascript'), array('src', 'async', 'defer', 'crossorigin'), self::CONTENT_DIFFER),
		self::STYLE		=> array(array('type'=>'text/css'), array('media'), self::CONTENT_MERGE)
	);
	
	/**
	 * List of all the headers
	 * @var array
	 */
	protected $headers = array();
	
	/**
	 * Create a headers class.
	 * @param boolean $strict Whether or not to enforce strict html attribute specifications
	 */
	public function __construct($xhtml=true, $strict=true){
		$this->xhtml = $xhtml;
		$this->strict = $strict;
	}
	
	/**
	 * Add a header tag to the list
	 * @param string $type
	 * @param array $attr
	 * @param string $content
	 * @return boolean
	 */
	public function add($type, array $attr, $content=null){
		if(is_string($type) && $this->isType($type) && is_array($attr)){
			// Check for required attributes
			foreach($this->types[$type][0] as $at => $v){
				if($v === null && !isset($attr[$at])) return false;
				if($v !== null && !isset($attr[$at])) $attr[$at] = $v;
			}
			// Check strictly for optional parameters if strict and/or set by tagtype
			if($this->types[$type][1] !== null && $this->strict){
				foreach($attr as $at => $v){
					if(!(in_array($at, $this->types[$type][1]) || array_key_exists($at, $this->types[$type][0]))){
						\Quark\Error::raiseWarning('Attribute "'.$at.'" given to the tag "'.$type.'" is not allowed.');
						return false;
					}
				}
			}
			// Check for content
			if($this->types[$type][2] != self::CONTENT_NONE && !is_null($content))
				$cont = $content;
			else $cont = null;
			// Add to header list
			if($this->types[$type][2] == self::CONTENT_MERGE){
				$found = false;
				foreach($this->headers as $index => $header){
					if($header[0] == $type && $header[1] == $attr){
						$this->headers[$index][2] .= $cont;
						$found = true;
					}
				}
				if(!$found) array_push($this->headers, array($type, $attr, $cont));
			}else if($this->types[$type][2] == self::CONTENT_UNIQUE){
				$found = false;
				foreach($this->headers as $index => $header){
					if($header[0] == $type){
						$this->headers[$index] = array($type, $attr, $cont);
						$found = true;
					}
				}
				if(!$found) array_push($this->headers, array($type, $attr, $cont));
			}else array_push($this->headers, array($type, $attr, $cont));
			return true;
		}else return false;
	}
	
	/**
	 * Filter the headers by the means of a callback.
	 * 
	 * The callback will receive thetype of callback as the first param,
	 * and the attributes as second. If the callback returns false,
	 * the entry will be removed.
	 * @param callback $callback Callback to filter with.
	 * @return boolean
	 */
	public function filter(callable $callback){
		foreach($this->headers as $num => $attr){
			$tag = array_shift($attr);
			if($callback($tag, $attr) === false){
				unset($this->headers[$num]);
			}
		}
		return true;
	}
	
	/**
	 * Check if a type is registred. (Like link, script, ...)
	 * @param string $type The type to check.
	 * @return boolean
	 */
	public function isType($type){
		return isset($this->types[$type]);
	}
	
	/**
	 * 
	 * @param string $type
	 * @param array $attr
	 * @param array $opt Optional attributes for the header tag, or null if you want to allow all attributes
	 * @return boolean
	 */
	public function registerType($type, $attr, $opt=null, $content=self::CONTENT_REMOVE){
		// Check type
		if(is_string($type) && is_array($attr) && (is_array($opt)||is_null($opt))){
			$this->types[$type] = array($attr, $opt);
		}else return false;
	}
	
	/**
	 * Get all the header types that can be registred.
	 * @return array
	 */
	public function getTypes(){
		return array_keys($this->types);
	}
	
	/**
	 * Check the contents of variables before they are assigned.
	 * @param string $name
	 * @param mixed $value
	 * @ignore
	 */
	public function __set($name, $value){
		if($name == 'xhtml' && is_bool($value))
			$this->xhtml = $value;
		else if($name == 'strict')
			throw new \RuntimeException('Strict value cannot be changed after initialization.');
		else throw new \RuntimeException('Tried to access non-existant or publically unavailable class variable.');
	}
	
	/**
	 * Get the html representation of the saved headers.
	 * @return string
	 */
	public function save(){
		$saved = '';
		foreach($this->headers as $header){
			$saved .= "\n\t".'<'.$header[0];
			foreach($header[1] as $attr => $val){
				$saved .= ' '.$attr.'="'.addcslashes($val,'"').'"';
			}
			if($this->types[$header[0]][2] == self::CONTENT_NONE)
				$saved .= ' '.($this->xhtml?'/':'').'>';
			else $saved .= '>'.$header[2].'</'.$header[0].'>';
		}
		return $saved;
	}
	
	/**
	 * Convert to html string representation.
	 * @return string
	 */
	public function __toString(){
		return $this->save();
	}
}