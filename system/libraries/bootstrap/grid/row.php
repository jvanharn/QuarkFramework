<?php
/**
 * Bootstrap Row Collection
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		December 15, 2012
 * @copyright	Copyright (C) 2012-2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\Bootstrap\Grid;
use Quark\Document\baseCollection,
	Quark\Document\Document;
use Quark\Document\baseElementMarkupClasses;
use Quark\Document\ICollection;
use Quark\Document\IElementMarkupClasses;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\BootstrapLayout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple implementation of the Collection Interface.
 */
class Row implements ICollection, IElementMarkupClasses {
	use baseCollection, baseElementMarkupClasses;

	/**
	 * Quick/simple way to add a column to this row.
	 * @param int $span Number of columns to span.
	 * @param array $classes Extra CSS classes to add.
	 * @return \Quark\Libraries\Bootstrap\Grid\Column
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
		$this->addMarkupClass('row');
		return
			_::line($depth, '<div '.$this->saveClassAttribute($context).'>').
			$this->saveChildren($context).
			_::line($depth, '</div>');
	}
}