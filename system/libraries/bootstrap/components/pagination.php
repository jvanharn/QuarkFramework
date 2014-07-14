<?php
/**
 * Bootstrap Pagination IComponent
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
	Quark\Document\Document,
	Quark\Libraries\Bootstrap\BootstrapElement;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\baseBootstrapElement;
use Quark\Libraries\Bootstrap\baseElementMarkupClasses;
use Quark\Libraries\Bootstrap\IElementMarkupClasses;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Pagination element for displaying pages to a user.
 */
class Pagination extends BootstrapElement implements \IteratorAggregate, IElementMarkupClasses {
	use baseElementMarkupClasses;

	const PAGINATION_SM = 'pagination-sm';
	const PAGINATION_MD = 'pagination-md';
	const PAGINATION_LG = 'pagination-lg';

	const BTN_NONE = 0;
	const BTN_ACTIVE = 1;
	const BTN_DISABLED = 2;

	/**
	 * @var string[] collection of the different available sizes so you can switch between them when changing the size.
	 */
	private $_sizes = array(self::PAGINATION_SM, self::PAGINATION_MD, self::PAGINATION_LG);

	/**
	 * Contains the children for this object.
	 * @var array[]
	 */
	protected $items = array();

	/**
	 * @param string $size Use this to set the size of the pagination buttons (Defaults to medium, use one of the PAGINATION_* constants)
	 */
	public function __construct($size=null){
		$this->cssClasses = array('pagination');
		if(is_string($size))
			array_push($this->cssClasses, $size);
	}

	/**
	 * Set the size of the pagination component.
	 * @param string $size One of the PAGINATION_* constants.
	 */
	public function setSize($size=null){
		foreach($this->cssClasses as $index => $class){
			if(in_array($this->_sizes, $class))
				unset($this->cssClasses[$index]);
		}
		if(!empty($size) && is_string($size))
			array_push($this->cssClasses, $class);
	}

	/**
	 * Add a generic button like previous or next.
	 * @param integer $label The text of the button.
	 * @param string $link The link that's used when clicking on the button.
	 * @param integer $options Bitmask; Combine any of the BTN_* constants to set properties on this page number.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 * @return Pagination Returns the object this method was called on for method chaining.
	 */
	public function add($label, $link, $options=self::BTN_NONE){
		if(!is_integer($options))
			throw new InvalidArgumentTypeException('options', 'integer', $options);
		array_push($this->items, array(
			$label,
			$link,
			$options
		));
	}

	/**
	 * Shortcut method to add a first page button with a double left arrow (So is language independent, not culture per sé).
	 * @example addFirstPage(''); // Adds a disabled previous button.
	 * @param string $link The link that gets loaded when the button gets clicked. When this is empty the button gets added as a disabled button.
	 * @return Pagination
	 */
	public function addFirstPage($link){
		return $this->add(
			'&laquo;',
			$link,
			empty($link) ? self::BTN_DISABLED : self::BTN_NONE
		);
	}

	/**
	 * Shortcut method to add a next page button with a double left arrow (So is language independent, not culture per sé).
	 * @example addLastPage('/books/1'); // Adds a enabled next button.
	 * @param string $link The link that gets loaded when the button gets clicked. When this is empty the button gets added as a disabled button.
	 * @return Pagination
	 */
	public function addLastPage($link){
		return $this->add(
			'&raquo;',
			$link,
			empty($link) ? self::BTN_DISABLED : self::BTN_NONE
		);
	}

	/**
	 * Add a page to the pagination component.
	 * @param integer $page The current page number.
	 * @param string $link The link that's used when clicking on the page numbers, e.g. "/books/{{page}}" or "/index.php?components=people&page={{page}}&query=john+doe". The {{page}} part will be replaced with the current page number.
	 * @param integer $options Bitmask; Combine any of the BTN_* constants to set properties on this page number.
	 * @return Pagination Returns the object this method was called on for method chaining.
	 */
	public function addPage($page, $link, $options=self::BTN_NONE){
		return $this->add(
			$page,
			str_ireplace('{{page}}', $page, $link),
			$options
		);
	}

	/**
	 * Add a range of page numbers to the pagination component.
	 *
	 * Adds a range of page numbers starting at $start and ending at page $end (Inclusive).
	 * The link will be set for each page where {{page}} will be replaced with the appropriate page number.
	 * @param integer $start Where to start the page number range. (Inclusive)
	 * @param integer $end Where to end the added page numbers. (Inclusive)
	 * @param string $link The link that's used when clicking on the page numbers, e.g. "/books/{{page}}" or "/index.php?components=people&page={{page}}&query=john+doe". The {{page}} part will be replaced with the current page number.
	 * @param integer $active When set, signifies the page that is active.
	 * @return void
	 */
	public function addRange($start, $end, $link, $active=null){
		for($page=$start; $page<$end; $page++){
			$this->add(
				$page,
				str_ireplace('{{page}}', $page, $link),
				($page === $active ? self::BTN_ACTIVE : 0)
			);
		}
	}

	/**
	 * Add a cluster of pages to the pagination component.
	 *
	 * You give this function the current/active page and how many numbers to show on each side of the 'middle' or active page.
	 * It also automatically adds firstPage and lastPage buttons and disables them where necessary; you can also opt to hide them when they are unavailable.
	 * @param integer $active The current active page to center the cluster around.
	 * @param integer $expand The number of pages to expand to each side.
	 * @param integer $max The maximum or the total number of pages available.
	 * @param string $link The link that's used when clicking on the page numbers, e.g. "/books/{{page}}" or "/index.php?components=people&page={{page}}&query=john+doe". The {{page}} part will be replaced with the current page number.
	 * @param boolean $hide When set to true, instead of disabling the next and previous buttons when they are not available, you can hide them all together.
	 * @throws \InvalidArgumentException When any of the arguments are incorrect.
	 * @return Pagination Returns itself for method chaining.
	 */
	public function addCluster($active, $expand, $max, $link, $hide=false){
		if(!is_integer($active) || $active <= 0)
			throw new \InvalidArgumentException('Invalid type or value for argument $active: Value must be of type integer and be 1 or larger.');
		if(!is_integer($expand) || $expand < 0)
			throw new \InvalidArgumentException('Invalid type or value for argument $expand: Value must be of type integer and be 0 or larger.');
		if(!is_integer($max) || $max <= $active)
			throw new \InvalidArgumentException('Invalid type or value for argument $max: Value must be of type integer and be equal to or larger than $active.');

		// Left
		if($active == 1)
			$expandLeft = 0;
		else{
			$expandLeft = $active-$expand;
			if($expandLeft < 1)
				$expandLeft = 0;
		}

		// Right
		if($active == $max)
			$expandRight = 0;
		else{
			$expandRight = $max-$active-$expand;
			if($expandRight < 1)
				$expandRight = 0;
		}

		// First page Button
		if(($active == 1 || ($active-$expandLeft) <= 1) && $hide === false){
			$this->addFirstPage('');
		}else if($active != 1 && ($active-$expandLeft) > 1){
			$this->addFirstPage(self::_replacePage($link, 1));
		}

		// Add pages
		$this->addRange($active-$expandLeft, $active+$expandRight, $link, $active);

		// Last page button
		if(($active == $max || ($active-$expandRight) >= $max) && $hide === false){
			$this->addLastPage('');
		}else if($active != $max && ($active-$expandRight) < $max){
			$this->addLastPage(self::_replacePage($link, $max));
		}

		return $this;
	}

	/**
	 * Iterator Aggregate Implementation
	 * @return \ArrayIterator
	 */
	public function getIterator(){
		return new \ArrayIterator($this->items);
	}

	/**
	 * Saves the element.
	 * @param Document $context
	 * @param int $depth The indented depth of the document right now (tabs).
	 * @return String
	 */
	public function save(Document $context, $depth = 0) {
		$pager = _::line($depth, '<ul '.$this->saveClassAttribute($context).'>');
		foreach($this->items as $item){
			if($item[2] & self::BTN_ACTIVE)
				$pager .= _::line($depth+1, '<li class="active"><a href="#">'.$context->encodeText($item[0], false).'</a></li>');
			else if($item[2] & self::BTN_DISABLED)
				$pager .= _::line($depth+1, '<li class="disabled"><a href="#">'.$context->encodeText($item[0], false).'</a></li>');
			else
				$pager .= _::line($depth+1, '<li><a '.$context->encodeAttribute('href', $item[1]).'>'.$context->encodeText($item[0], false).'</a></li>');
		}
		$pager .= _::line($depth, '</ul>');
		return $pager;
	}

	private static function _replacePage($link, $page){
		return str_ireplace('{{page}}', $page, $link);
	}
}