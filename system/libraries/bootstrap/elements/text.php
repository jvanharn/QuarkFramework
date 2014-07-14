<?php
/**
 * Bootstrap Text Utility/Helper
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		July 9, 2014
 * @copyright	Copyright (C) 2012-2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 *
 * Copyright (C) 2012-2014 Jeffrey van Harn
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
namespace Quark\Libraries\Bootstrap\Components;
use Quark\Document\baseCollection,
	Quark\Libraries\Bootstrap\BootstrapElement;
use Quark\Document\Document;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class that provides static helpers to help with common operations on (Hyper) Text and an basic implementation of an element.
 */
class Text extends BootstrapElement {


	/**
	 * Retrieve the HTML representation of the element
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth The current indentation depth, not required.
	 * @return String HTML Representation
	 */
	public function save(Document $context, $depth = 0) {
		// TODO: Implement save() method.
	}
}