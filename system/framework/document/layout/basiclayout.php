<?php
/**
 * The simplest of all layouts.
 * 
 * @package		Quark-Framework
 * @version		$Id: basiclayout.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		7 december 2012
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
namespace Quark\Document\Layout;
use \Quark\Document\Document as Document;
use Quark\Document\Headers;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Basic Layout Implementation
 * 
 * The simplest of all Layout implementations. Places all elements in a centered
 * column with about a dozen rules of css.
 * 
 * Recommended usage is simple text filled pages, or form filled pages. Examples
 * include setup screen's, simple portfolio sites, etc.
 */
class BasicLayout extends Layout{
	/**
	 * Element array.
	 * @var array
	 */
	protected $elements = array(
		'HEADER' => array(),
		'CONTENT' => array(),
		'FOOTER' => array()
	); // Pre-initialized the array to prevent errors with empty layout's

	/**
	 * Create a new Basic Layout.
	 */
	public function __construct(){
		$this->positions = new Positions(array(
			'HEADER' => ['Header', 'Put some simple text here, or your logo.'],
			'CONTENT' => ['Content', 'Put all text and other userinterface elements here.'],
			'FOOTER' => ['Footer', 'This is the place to say your thank-you\'s and put your copyright messages.']
		), array('MAIN_CONTENT' => 'CONTENT'));
	}

	/**
	 * Save the layout content to HTML
	 * @param \Quark\Document\Document $context
	 * @param int $depth
	 * @return string
	 */
	public function save(Document $context, $depth=0){
		// Set the css
		$context->headers->add(Headers::STYLE, array(),
			"body { font-family: sans-serif; background-color: #fcfcfc; }".
			"h1, h2, h3, h4, .header, legend { font-family: \"Segoe UI\", Frutiger, \"Frutiger Linotype\", \"Dejavu Sans\", \"Helvetica Neue\", Arial, sans-serif; color: #222; text-shadow: 0 0 1px rgba(0,0,0,0.3); } ".
			"abbr { border-bottom: 1px dotted #555; padding-bottom: -1px; }".
			"a { color: #0198E1; text-decoration: none; }".

			"form { max-width: 580px; margin: 10px auto; } fieldset { border: 0; background-color: rgb(240, 240, 240); border-radius: 2px; border: 1px solid #bbb; } legend { font-size: 105%; font-weight: bold; padding: 2px 9px; background-color: rgb(245, 245, 245); box-shadow: 0px 0px 1px rgba(200, 200, 200, 1.0); border-radius: 2px; }".
			"label { padding: 0 0 0 4px; min-width: 30%; display: inline-block; line-height: 30px; }".
			"input[type=text], input[type=text]:focus, input[type=password], input[type=password]:focus { padding: 5px 4px; outline: 0; box-shadow: 0px 0px 1px #888; border:0; border-bottom: 1px solid #ddd; width: 100%; } input[type=text]:focus, input[type=password]:focus { box-shadow: 0px 0px 2px black; }".
			".control-group{ clear: both; height: 32px; margin-bottom: 2px; } label, .controls { width: 47.5%; float: left; }".
			"select, select:focus { min-width: 180px; padding: 3px 3px; outline: 0; box-shadow: 0px 0px 1px #888; border:0; border-bottom: 1px solid #ddd;}".
			"form div span { font-size: 10px; color: #888;}".
			"input[type=submit] { margin-top: 5px; padding: 3px 8px; font-size: 105%; font-weight: bold; border: 1px solid #ccc; border-radius: 2px; background-color: #fefefe; color: #333; box-shadow: 0px 0px 1px rgba(150, 150, 150, 0.3); } input[type=submit]:active { background-color: #f5f5f5; box-shadow: 0px 0px 3px rgba(150, 150, 150, 0.4) inset; }".

			".container { min-width: 320px; max-width: 720px; margin: 20px auto; }".
			".row { margin: 10px 0; } .row h1, .row h2, .row h3, .row h4 { margin: 0px; padding: 0px; } .row p { line-height: 20px; margin: 4px 3px 8px 6px; }".

			".header { font-size: 55px; font-weight: bolder; font-family: helvetica, arial, sans-serif; text-align: center; border-bottom: solid 1px #bbb; padding: 10px; min-width: 320px; max-width: 720px; margin: 20px auto; color: #111; }".
			".footer { font-size: 11px; font-family: verdana, helvetica, ariel, sans-serif; text-align: center; border-top: solid 1px #bbb; padding: 10px; min-width: 320px; max-width: 720px; margin: 10px auto; color: #aaa; }"
		);
		
		// Save the header
		$saved = "<div class=\"header\">\n";
		foreach($this->elements['HEADER'] as $elem)
			$saved .= $elem->save($context)."\n";
		
		$saved .= "</div>\n";
		
		// Save the main content
		$saved .= "<div class=\"container\">\n";
		foreach($this->elements['CONTENT'] as $elem)
			$saved .= "<div class=\"row\">\n".$elem->save($context)."\n</div>\n";
		
		$saved .= "\n</div>\n";
		
		// Save the footer
		$saved .= "<div class=\"footer\">\n";
		foreach($this->elements['FOOTER'] as $elem)
			$saved .= $elem->save($context)."\n";
		
		$saved .= "</div>\n";
		
		// Return the result
		return $saved;
	}	
}