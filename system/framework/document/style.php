<?php
/**
 * Title
 * 
 * Description of the file.
 * 
 * @package		Quark-Framework
 * @version		$Id: style.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		11 december 2012
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
namespace Quark\Document;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;


interface Style {
	/**
	 * Get the style associated with this element
	 * @return string
	 */
	public function saveStyle();
}

interface StyledElement extends Style, IElement {}

/**
 * Dynamicly modifiable style object that can be added to and can minify css
 *
 * @author Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 */
class StyleBuffer implements Style {
	/**
	 * Minify on save
	 * @var boolean
	 */
	protected $minify = true;
	
	/**
	 * Concatenated $css strings
	 * @var string
	 */
	protected $css = '';
	
	/**
	 * Constructor
	 * @param type $minify Whether or not this style document gets minified on save.
	 */
	public function __construct($minify=true){
		$this->minify = $minify;
	}
	
	/**
	 * Add style to the buffer
	 * @param string $style Style to add to the buffer.
	 */
	public function add($style){
		$this->css .= PHP_EOL.$style;
	}
	
	/**
	 * Get the buffered (and minfied) css.
	 * @return string
	 */
	public function saveStyle(){
		if($this->minify){
			
		}else return $this->style;
	}
}