<?php
/**
 * Bootstrap Column Collection
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
use Quark\Document\IElement;
use Quark\Document\IElementMarkupClasses;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\BootstrapLayout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple implementation of the Collection Interface.
 */
class Container implements ICollection, IElementMarkupClasses {
	use baseCollection, baseElementMarkupClasses;

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

        $children = $this->saveChildren($context, $depth+1);
        if(empty($children)) return _::line($depth, ''); // If the response is empty do not return the wrapper element.

		return
			_::line($depth, '<div '.$this->saveClassAttribute($context).'>').
				$children.
			_::line($depth, '</div>');
	}
}