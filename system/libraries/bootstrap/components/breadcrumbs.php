<?php
/**
 * Bootstrap Breadcrumbs UI Element
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		Juli 4, 2014
 * @copyright	Copyright (C) 2012-2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\Bootstrap\Components;

// Import namespaces
use Quark\Document\Document,
	Quark\Libraries\Bootstrap\Component;
use Quark\Document\IElement;
use Quark\Document\Utils\_;
use Quark\Document\IElementMarkupClasses,
	Quark\Document\baseElementMarkupClasses;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Bootstrap Breadcrumb UI Element
 *
 * @link http://getbootstrap.com/components/#breadcrumbs
 * @package Quark\Libraries\Bootstrap\Components
 */
class Breadcrumbs extends Component implements \IteratorAggregate, IElementMarkupClasses {
	use \Quark\Document\baseElementMarkupClasses;

	/**
	 * @var BreadcrumbPart[] Contains all the breadcrumbs
	 */
	protected $breadcrumbs = array();

	/**
	 */
	public function __construct(){
		$this->cssClasses = array('breadcrumb');
	}

	/**
	 * Append a breadcrumb to the end of the bar.
	 * @param BreadcrumbPart $breadcrumb
	 * @param bool $active
	 */
	public function append(BreadcrumbPart $breadcrumb, $active=false){
		array_push($this->breadcrumbs, $breadcrumb);
	}

	/**
	 * Retrieve the breadcrumb iterator.
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return \Iterator Array of Breadcrumb parts.
	 */
	public function getIterator() {
		return $this->breadcrumbs;
	}

	/**
	 * Saves the element.
	 * @param Document $context
	 * @param int $depth
	 * @return String
	 */
	public function save(Document $context, $depth = 0) {
		$save = _::line($depth, '<ol class="breadcrumb" id="'.$this->getId().'">');

		foreach($this->breadcrumbs as $b)
			$save .= $b->save($context, $depth+1);

		return $save._::line($depth, '</ol>');
	}
}

/**
 * Breadcrumb Part Struct
 * @package Quark\Libraries\Bootstrap\Components
 */
class BreadcrumbPart implements IElement {
	/**
	 * @var boolean Whether or not this breadcrumb is active.
	 */
	public $active;

	/**
	 * @var string The breadcrumb link (if applicable)
	 */
	public $link;

	/**
	 * @var string The breadcrumb label.
	 */
	public $label;

	/**
	 * @param string $label Label of the breadcrumb.
	 * @param string $link Optional link for the breadcrumb.
	 */
	public function __construct($label, $link=null){
		$this->label = $label;
		$this->link = $link;
	}

	/**
	 * Saves the element.
	 * @param Document $context
	 * @param int $depth
	 * @return String
	 */
	public function save(Document $context, $depth=0) {
		if(!empty($this->link))
			$link = '<a href="'.$this->link.'">'.$this->label.'</a>';
		else
			$link = $this->label;

		if($this->active === true)	// active page
			return _::line($depth+1, '<li class="active">'.$link.'</li>');
		else						// regular
			return _::line($depth+1, '<li>'.$link.'</li>');
	}
}