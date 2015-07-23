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
use Quark\Document\IElementMarkupClasses;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\BootstrapLayout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple implementation of the Collection Interface.
 */
class Column implements ICollection, IElementMarkupClasses {
	use baseCollection, baseElementMarkupClasses;

	const ORDER_PUSH = 0;
	const ORDER_PULL = 1;

	/**
	 * @var array Describes the number of columns this column element spans in each breakpoint.
	 */
	protected $spans = array();

	/**
	 * @var array Describes the offsetting of the column on various breakpoints.
	 */
	protected $offsets = array();

	/**
	 * @var array Describes the pushing and pulling of the column on various breakpoints.
	 */
	protected $ordering = array();

	/**
	 * @param array $spans The spans on every device breakpoint for this column.
	 * @throws \InvalidArgumentException
	 */
	public function __construct(array $spans=array(BootstrapLayout::BP_MEDIUM_DEVICES => 12)){
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
		if(!BootstrapLayout::isBreakpoint($breakpoint))
			throw new \InvalidArgumentException('Expected $breakpoint to be valid BootstrapLayout::BP_* constant.');
		if(!is_int($columns) || $columns > BootstrapLayout::GRID_COLUMNS)
			throw new \InvalidArgumentException('Expected $columns to be integer and be less than or equal the amount of defined grid columns (12 by default).');

		$this->spans[$breakpoint] = $columns;
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
		foreach($this->spans as $bp => $span)
			$this->addMarkupClass(BootstrapLayout::getBreakpointClassPrefix($bp).$span);
		foreach($this->offsets as $bp => $cols)
			$this->addMarkupClass(BootstrapLayout::getBreakpointClassPrefix($bp).'offset-'.$cols);
		foreach($this->ordering as $bp => $info)
			$this->addMarkupClass(BootstrapLayout::getBreakpointClassPrefix($bp).($info[0] == self::ORDER_PULL ? 'pull-' : 'push-').$info[1]);

		return
			_::line($depth, '<div '.$this->saveClassAttribute($context).'>').
				$this->saveChildren($context).
			_::line($depth, '</div>');
	}

	/**
	 * Simple creation of a column object by just giving the number of columns for the MD breakpoint only.
	 * @param int $columns Number of columns it spans on device $on.
	 * @param int $on Breakpoint to set the $columns for.
	 * @param array $classes Extra CSS classes if necessary.
	 * @return \Quark\Libraries\Bootstrap\Grid\Column
	 */
	public static function spanning($columns=BootstrapLayout::GRID_COLUMNS, $on=BootstrapLayout::BP_MEDIUM_DEVICES, $classes=array()){
		if(is_int($columns) && $columns <= BootstrapLayout::GRID_COLUMNS)
			return new Column($classes, array($on => $columns));
	}
}