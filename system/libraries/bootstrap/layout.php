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
use Quark\Bundles\Bundles;
use Quark\Document\baseCollection,
	Quark\Document\Document,
	Quark\Document\Layout\Layout,
	Quark\Document\Layout\Positions,
	Quark\Document\IElement;
use Quark\Libraries\Bootstrap\Elements\Row;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Bootstrap Document Layout
 *
 * This class provides all the benefits of the Twitter Bootstrap (3.0) project, tightly integrated with the Quark document model.
 * @package Quark\Libraries\Bootstrap
 */
class BootstrapLayout extends Layout {
	// Predefined Breakpoints
	/**
	 * Phone View Sized Breakpoint Reference (Default size: <768px, single column)
	 */
	const BP_PHONES = 1;

	/**
	 * Tablet View Sized Breakpoint Reference (Default size: >=768px)
	 */
	const BP_SMALL_DEVICES = 2;

	/**
	 * Normal Desktop View Sized Breakpoint Reference (Default size: >=992px)
	 */
	const BP_MEDIUM_DEVICES = 4;

	/**
	 * Full HD Desktop View Sized Breakpoint Reference (Default size: >=1200px)
	 */
	const BP_LARGE_DEVICES = 8;

	// Usefull Positions
	/**
	 * Places the element directly inside the body, before the container.
	 */
	const POSITION_BEFORE_CONTAINER = 'body';

	/**
	 * Places the element inside /THE/ root container tag. (Default)
	 */
	const POSITION_CONTAINER = 'container';

	/**
	 * Places the element after the container, directly inside the body.
	 */
	const POSITION_AFTER_CONTAINER = 'footer';

	// Other constants
	/**
	 * Number of columns in the bootstrap grid (for reference).
	 */
	const GRID_COLUMNS = 12;

	/**
	 * Breakpoint classes.
	 * @var array
	 */
	protected static $breakpoints = array(
		self::BP_PHONES				=> 'col-xs-',
		self::BP_SMALL_DEVICES		=> 'col-sm-',
		self::BP_MEDIUM_DEVICES		=> 'col-md-',
		self::BP_LARGE_DEVICES		=> 'col-lg-'
	);

	/**
	 * Create a new Bootstrap layout.
	 */
	public function __construct(){
		$this->_populatePositionsObject();
	}

	/**
	 * Fills the positions object so that it stays compatible with any editor systems.
	 * Please note that this only "exposes" the medium device breakpoint positions.
	 * @ignore
	 */
	private function _populatePositionsObject(){
		// Populate the Positions object
		$this->positions = new Positions(array(
			self::POSITION_BEFORE_CONTAINER => array('Before the Page-Container', 'descr'),
			self::POSITION_CONTAINER => array('Inside the Page-Container', 'descr'),
			self::POSITION_AFTER_CONTAINER => array('After the Page-Container', 'descr'),
		), array('MAIN_CONTENT' => self::POSITION_CONTAINER));
	}

	/**
	 * Place an element on the given position in the bootstrap layout.
	 * @param \Quark\Document\IElement $elem Savable element object.
	 * @param string $position Valid position reference.
	 * @return boolean
	 */
	public function place(IElement $elem, $position=self::POSITION_CONTAINER){
		parent::place($elem, $position);
	}

	/**
	 * Creates a new Row bootstrap element and places it inside the container, it then returns it's reference.
	 * @param array $classes Extra CSS classes to add.
	 * @return \Quark\Libraries\Bootstrap\Elements\Row
	 */
	public function row($classes=array()){
		$row = new Row($classes);
		$this->place($row, self::POSITION_CONTAINER);
		return $row;
	}

	/**
	 * Get the html representation of the bootstrap layout.
	 * @param \Quark\Document\Document $context The context document in which the layout gets rendered.
	 * @param int $depth
	 * @throws \UnexpectedValueException
	 * @return string
	 */
	public function save(Document $context, $depth=1) {
		/** @var $element IElement */ // Fixes IDE type inference.

		// Check doc layout
		if($context->layout != $this)
			throw new \UnexpectedValueException('Tried to save a BootstrapLayout in a document in which it is not the layout. I got confused.');

		// Load necessary resources
		$context->resources->required('bootstrap.css', Bundles::RESOURCE_TYPE_CSS, 'bootstrap');
		$context->resources->required('jquery.js', Bundles::RESOURCE_TYPE_JS);
		$context->resources->required('bootstrap.js', Bundles::RESOURCE_TYPE_JS, 'bootstrap');

		// Start savin'
		$saved = '';

		// Save the body elements
		if(isset($this->elements[self::POSITION_BEFORE_CONTAINER])){
			foreach($this->elements[self::POSITION_BEFORE_CONTAINER] as $element){
				$saved .= $element->save($context, $depth);
			}
		}

		// Save the elements
		if(isset($this->elements[self::POSITION_CONTAINER])){
			$saved .= "\t<div class=\"container\">\n";
			foreach($this->elements[self::POSITION_CONTAINER] as $element){
				$saved .= $element->save($context, $depth+1);
			}
			$saved .= "\n\t</div>\n";
		}

		// Save the footer
		if(isset($this->elements[self::POSITION_AFTER_CONTAINER])){
			foreach($this->elements[self::POSITION_AFTER_CONTAINER] as $element){
				$saved .= $element->save($context, $depth);
			}
		}

		return $saved;
	}

	/**
	 * Checks whether the argument is a valid breakpoint
	 * @param mixed $breakpoint
	 * @return bool
	 */
	public static function isBreakpoint($breakpoint){
		return (is_int($breakpoint) && isset(self::$breakpoints[$breakpoint]));
	}

	/**
	 * Get the CSS column class prefix for the given breakpoint.
	 * @param integer $breakpoint
	 * @return mixed
	 * @throws \InvalidArgumentException
	 */
	public static function getBreakpointClassPrefix($breakpoint){
		if(!is_int($breakpoint))
			throw new \InvalidArgumentException('Argument $breakpoint should be of type "integer", but got "'.gettype($breakpoint).'".');
		return self::$breakpoints[$breakpoint];
	}
}

/**
 * Class BootstrapLayoutException
 * @package Quark\Libraries\Bootstrap
 */
class BootstrapLayoutException extends \Exception { }