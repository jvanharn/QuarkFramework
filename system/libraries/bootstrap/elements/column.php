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
class Column implements Collection, BootstrapElement {
	use baseBootstrapCollection {
		baseBootstrapCollection::__construct as baseConstruct;
	};

	const ORDER_PUSH = 0;
	const ORDER_PULL = 1;

	/**
	 * @var array Describes the number of columns this column element spans in each breakpoint.
	 */
	protected $spans = array();

	/**
	 * @var array Describes the offsetting of the column on various breakpoints.
	 */
	protected $offsets;

	/**
	 * @var array Describes the pushing and pulling of the column on various breakpoints.
	 */
	protected $ordering;

	/**
	 * @param array $classes Extra CSS classes for this column.
	 * @param array $spans The spans on every device breakpoint for this column.
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $classes = array(), array $spans = array(BootstrapLayout::BP_MEDIUM_DEVICES => 12)){
		// Set additional classes
		$this->baseConstruct($classes);

		// Set the spans
		if(empty($spans))
			throw new \InvalidArgumentException('You need to set the span of this column for at least one breakpoint!');
		foreach($spans as $breakpoint => $span)
			$this->spans($span, $breakpoint);
	}

	/**
	 * Set the spanning for a breakpoint for this column.
	 * @param int $columns
	 * @param int $breakpoint
	 * @throws \InvalidArgumentException
	 */
	public function spans($columns, $breakpoint=BootstrapLayout::BP_MEDIUM_DEVICES){
		if(BootstrapLayout::isBreakpoint($breakpoint) && is_int($columns) && $columns <= BootstrapLayout::GRID_COLUMNS)
			$this->spans[$breakpoint] = $columns;
		else throw new \InvalidArgumentException('Expected $columns to be integer and be less than or equals 12. Expected breakpoint to be valid BootstrapLayout::BP_* constant.');
	}

	/**
	 * Set the (left) offset of this column.
	 * @param int $columns By how many columns to offset.
	 * @param int $breakpoint
	 * @return bool
	 */
	public function offset($columns, $breakpoint=BootstrapLayout::BP_MEDIUM_DEVICES){
		if(is_int($columns) && $columns <= BootstrapLayout::GRID_COLUMNS && BootstrapLayout::isBreakpoint($breakpoint)){
			$this->offsets[$breakpoint] = $columns;
			return true;
		}else return false;
	}

	/**
	 * Push or pull the column into a direction.
	 * @param int $columns Number of columns to push or pull.
	 * @param int $direction One of the ORDER_* constants.
	 * @param int $breakpoint
	 * @return bool
	 */
	public function order($columns, $direction, $breakpoint=BootstrapLayout::BP_MEDIUM_DEVICES){
		if(is_int($columns) && $columns <= BootstrapLayout::GRID_COLUMNS && is_int($direction) && $direction <= 1 && BootstrapLayout::isBreakpoint($breakpoint)){
			$this->ordering[$breakpoint] = array($direction, $columns);
			return true;
		}else return false;
	}

	/**
	 * Shorthand for {@see Column::order}.
	 * @param int $columns Number of columns to push.
	 * @param int $breakpoint
	 * @return bool
	 * @see Column::order
	 */
	public function push($columns, $breakpoint=BootstrapLayout::BP_MEDIUM_DEVICES){
		return self::order($columns, self::ORDER_PUSH, $breakpoint);
	}

	/**
	 * Shorthand for {@see Column::order}.
	 * @param int $columns Number of columns to pull.
	 * @param int $breakpoint
	 * @return bool
	 * @see Column::order
	 */
	public function pull($columns, $breakpoint=BootstrapLayout::BP_MEDIUM_DEVICES){
		return self::order($columns, self::ORDER_PULL, $breakpoint);
	}

	/**
	 * Save the collection to its HTML representation.
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth Depth in the document. Used for the number of tabs before each element.
	 * @return String HTML Representation
	 */
	public function save(Document $context, $depth=1) {
		$classes = '';
		foreach($this->spans as $bp => $span)
			$classes .= ' '.BootstrapLayout::getBreakpointClassPrefix($bp).$span;
		foreach($this->offsets as $bp => $cols)
			$classes .= ' '.BootstrapLayout::getBreakpointClassPrefix($bp).'offset-'.$cols;
		foreach($this->ordering as $bp => $info)
			$classes .= ' '.BootstrapLayout::getBreakpointClassPrefix($bp).($info[0] == self::ORDER_PULL ? 'pull-' : 'push-').$info[1];
		foreach($this->classes as $class)
			$classes .= ' '.$class;

		return str_repeat("\t",$depth).'<div class="'.$classes.'">'.$this->saveChildren($context).str_repeat("\t",$depth).'</div>';
	}

	/**
	 * Simple creation of a column object by just giving the number of columns for the MD breakpoint only.
	 * @param int $columns Number of columns it spans on device $on.
	 * @param int $on Breakpoint to set the $columns for.
	 * @param array $classes Extra CSS classes if necessary.
	 * @return \Quark\Libraries\Bootstrap\Elements\Column
	 */
	public static function spanning($columns=BootstrapLayout::GRID_COLUMNS, $on=BootstrapLayout::BP_MEDIUM_DEVICES, $classes=array()){
		if(is_int($columns) && $columns <= BootstrapLayout::GRID_COLUMNS)
			return new Column($classes, array($on => $columns));
	}
}