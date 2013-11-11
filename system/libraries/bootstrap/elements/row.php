<?php
/**
 * Bootstrap Row Collection
 * 
 * @package		Quark-Framework
 * @version		$Id: collection.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		December 15, 2012
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
namespace Quark\Libraries\Bootstrap\Elements;
use Quark\Document\baseCollection,
	Quark\Document\Document,
	Quark\Document\Collection;
use Quark\Libraries\Bootstrap\BootstrapElement;
use Quark\Libraries\Bootstrap\baseBootstrapCollection;
use Quark\Libraries\Bootstrap\BootstrapLayout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple implementation of the Collection Interface.
 */
class Row implements Collection, BootstrapElement {
	use baseBootstrapCollection;

	/**
	 * Quick/simple way to add a column to this row.
	 * @param int $span Number of columns to span.
	 * @param array $classes Extra CSS classes to add.
	 * @return \Quark\Libraries\Bootstrap\Elements\Column
	 * @see Column::spanning
	 */
	public function column($span, $classes=array()){
		return Column::spanning($span, BootstrapLayout::BP_MEDIUM_DEVICES, $classes);
	}

	/**
	 * Save the collection to its HTML representation.
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth Depth in the document. Used for the number of tabs before each element.
	 * @return String HTML Representation
	 */
	public function save(Document $context, $depth=1) {
		$classes = 'row';
		foreach($this->classes as $class)
			$classes .= ' '.$class;

		return str_repeat("\t",$depth).'<div class="'.$classes.'">'.$this->saveChildren($context).str_repeat("\t",$depth).'</div>';
	}
}