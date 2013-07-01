<?php
/**
 * Application Traits - Document
 * 
 * Basic Application traits that get you started super quickly, by providing you
 * with the bare minimum to initialize each class.
 * 
 * @package		Quark-Framework
 * @version		$Id: document.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		27 december 2012
 * @copyright	Copyright (C) 2012-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012-2013 Jeffrey van Harn
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
namespace Quark\System\Application\Base;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Import needed files
\Quark\import('Framework.Document.Document');

/**
 * Provides the basic Document and Layout initialization
 */
trait Document {
	/**
	 * Document Object
	 * @var \Quark\Document\Document
	 */
	protected $document;
	
	/**
	 * Initiate the Document with a BasicLayout (Simplest of all).
	 */
	protected function initDocument(){
		\Quark\import('Framework.Document.Layout.BasicLayout');
		$this->initDocumentWithLayout(new \Quark\Document\Layout\BasicLayout());
	}
	
	/**
	 * Initialize the document with the given layout.
	 * @param \Quark\Document\Layout\Layout $layout Layout to create the document with
	 * @param string $type Valid Document TYPE_* constant. Defines the standard with wich the document will be rendered (XHTML, HTML5(Default), etc.)
	 */
	protected function initDocumentWithLayout(\Quark\Document\Layout\Layout $layout, $type=\Quark\Document\Document::TYPE_HTML){
		$this->document = \Quark\Document\Document::createInstance($layout, $type);
	}
	
	/**
	 * Get the current application's document object.
	 * @return \Quark\Document\Document
	 */
	public function getDocument(){
		return $this->document;
	}
}