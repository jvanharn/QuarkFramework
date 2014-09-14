<?php
/**
 * Bootstrap Column Collection
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
namespace Quark\Libraries\Bootstrap\Grid;
use Quark\Document\baseCollection,
	Quark\Document\Document;
use Quark\Document\IElement;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\BootstrapCollection;
use Quark\Libraries\Bootstrap\BootstrapLayout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple implementation of the Collection Interface.
 */
class Container extends BootstrapCollection {
	/**
	 * @var bool Whether or not this is an fluid container.
	 */
	protected $fluid = false;

	/**
	 * @param bool $fluid Whether or not this is a fluid container.
	 */
	public function __construct($fluid=false){
		$this->fluid = $fluid;
	}

	/**
	 * Shortcut for easily placing a (single or multiple) component(s) as a row.
	 * @param IElement|IElement[] $elements Elements to place.
	 * @return Row
	 */
	public function place($elements){
		$this->appendChild($row = new Row());
		if(is_object($elements)){
			$row->appendChild($col = new Column());
			$col->appendChild($elements);
		}
		else if(is_array($elements)){
			$numColumns = count($elements);
			$colSize = (int) floor(12/$numColumns);
			foreach($elements as $element){
				$row->appendChild($col = new Column([
					BootstrapLayout::BP_MEDIUM_DEVICES => $colSize
				]));
				$col->appendChild($element);
			}
		}

		return $row;
	}

	/**
	 * Set whether or not the container is fluid.
	 * @param bool $fluid
	 */
	public function setFluid($fluid){
		$this->fluid = $fluid;
	}

	/**
	 * Check whether the container is fluid.
	 * @return bool
	 */
	public function isFluid(){
		return $this->fluid;
	}

	/**
	 * Save the collection to its HTML representation.
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth Depth in the document. Used for the number of tabs before each element.
	 * @return String HTML Representation
	 */
	public function save(Document $context, $depth=1){
		if($this->fluid)
			$this->addMarkupClass('container-fluid');
		else
			$this->addMarkupClass('container');

		return
			_::line($depth, '<div '.$this->saveClassAttribute($context).'>').
				$this->saveChildren($context).
			_::line($depth, '</div>');
	}
}