<?php
/**
 * Advanced Web Application Layout.
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 23, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\Bootstrap;
use Quark\Document\baseCollection,
	Quark\Document\IElement;
use Quark\Document\ICollection;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Trait BootstrapCollection, basic collection implementation for bootstrap layouts.
 * @package Quark\Libraries\Bootstrap
 */
abstract class BootstrapCollection extends BootstrapElement implements ICollection, IElementMarkupClasses {
	use baseCollection, baseElementMarkupClasses;

	/**
	 * Invoke the collection to simplify adding elements to the collection
	 * @param IElement $element Element to append to the collection.
	 * @return \Quark\Document\Utils\Collection The current object for chaining.
	 * @see \Quark\Document\Collection::appendChild()
	 */
	public function __invoke(IElement $element) {
		$this->appendChild($element);
		return $this;
	}
}