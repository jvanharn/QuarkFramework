<?php
/**
 * Literal Element - Utility Class
 * 
 * @package		Quark-Framework
 * @version		$Id: literal.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		14 december 2012
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
namespace Quark\Document\Utils;
use \Quark\Document\Element,
	\Quark\Document\Document;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * A Simple Element that will just return the HTML given, or will call a given callback when saved.
 */
class Literal implements Element{
	use \Quark\Document\baseElement;
	
	/**
	 * The result of the callback, if caching is off
	 * @var string
	 * @ignore
	 */
	private $result = null;
	
	/**
	 * Class Options
	 * 
	 *  - callback: A callback that will be called when the save() function is called
	 *  - html: Html to return, like the classname says.
	 *  - cache: Whether or not to keep calling the callback everytime the save function is called, or just once, caching the result of the first call.
	 * 
	 * If both the callback and the html are defined, then when the callback
	 * returns an 'empty' result the html will be used as a fallback.
	 */
	protected $defaults = array(
		'callback'	=> null,
		'html'		=> null,
		'cache'		=> null
	);
	
	/**
	 * Constructor
	 * @param String|Callback $html A string or callback that will be called when the save() function is called, or a simple html string that will be returned.
	 * @param Bool $repeat If the object should keep calling the callback everytime it is save()'d or only the first time.
	 */
	public function __construct($options){
		// Set the defaults
		$this->options = $this->defaults;
		
		// Check option validity
		if(isset($options['cache']) && !is_bool($options['cache']))
			throw new \InvalidArgumentException('Argument \'cache\' was invalid, should be of type "bool".');
		if(isset($options['callback']) && !is_callable($options['callback']))
			throw new \InvalidArgumentException('Argument \'callback\' was invalid, should be of type "callable".');
		if(isset($options['html']) && !is_string($options['html']))
			throw new \InvalidArgumentException('Argument \'html\' was invalid, should be of type "string".');
		if(!isset($options['html']) && !isset($options['callback']))
			throw new \InvalidArgumentException('At least one of the options \'html\' or \'callback\' has to be set, for this class to function.');
		
		// Save the result
		$this->setOptions($options);
	}
	
	/**
	 * Saves this element to it's html representation
	 */
	public function save(Document $context){
		if(!empty($this->options['callback']) && ($this->options['cache'] == true || ($this->options['cache'] == false && empty($this->result))))
			$this->result = call_user_func($this->options['callback']);
		
		if(!empty($this->options['callback']) && !empty($this->options['html'])){
			if(!empty($this->result))
				return $this->result;
			else return $this->options['html'];
		}else if(!empty($this->options['callback'])){
			return $this->result;
		}else{
			return $this->options['html'];
		}
	}
}