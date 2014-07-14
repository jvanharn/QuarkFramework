<?php
/**
 * Bootstrap Column Collection
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		January 6, 2014
 * @copyright	Copyright (C) 2012-2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\Bootstrap\Components;
use Quark\Document\baseCollection,
	Quark\Document\Document;
use Quark\Document\IElement;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\BootstrapElement;
use Quark\Libraries\Bootstrap\Component;
use Quark\Libraries\Bootstrap\IElementMarkupClasses;
use Quark\Libraries\Bootstrap\baseElementMarkupClasses;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Grouping element that can group buttons together, and can group a dropdown and a button together.
 */
class ButtonGroup extends Component implements \IteratorAggregate, IElementMarkupClasses {
	use baseElementMarkupClasses;

	const BTN_GROUP_XS = 'btn-group-xs';
	const BTN_GROUP_SM = 'btn-group-sm';
	const BTN_GROUP_MD = 'btn-group-md';
	const BTN_GROUP_LG = 'btn-group-lg';

	/**
	 * @var string[] collection of the different available sizes so you can switch between them when changing the size.
	 */
	private $_sizes = array(self::BTN_GROUP_XS, self::BTN_GROUP_SM, self::BTN_GROUP_MD, self::BTN_GROUP_LG);

	/**
	 * Contains the children for this object
	 * @var BootstrapElement[]
	 */
	protected $children = array();

	/**
	 * @param string $size Use this to set the size of all the buttons in the group at once (Defaults to medium, use one of the BTN_GROUP_* constants)
	 * @param bool $vertical Orientate this button group in a vertical manner.
	 */
	public function __construct($size=self::BTN_GROUP_MD, $vertical=false){
		if($vertical === true)
			$this->cssClasses = array('btn-group-vertical');
		else
			$this->cssClasses = array('btn-group');
		if(is_string($size))
			array_push($this->cssClasses, $size);
	}

	/**
	 * Set the size of the button-group.
	 * @param string $size One of the BTN_GROUP_* constants.
	 */
	public function setSize($size=self::BTN_GROUP_MD){
		if(is_string($size)){
			foreach($this->cssClasses as $index => $class){
				if(in_array($this->_sizes, $class))
					unset($this->cssClasses[$index]);
			}
			array_push($this->cssClasses, $class);
		}
	}

	/**
	 * Set the orientation of the button group.
	 * @param bool $vertical
	 * @return void
	 */
	public function setOrientation($vertical=false){
		if($vertical === true){
			$this->addMarkupClass('btn-group-vertical');
			$this->removeMarkupClass('btn-group');
		}else{
			$this->addMarkupClass('btn-group');
			$this->removeMarkupClass('btn-group-vertical');
		}
	}

	/**
	 * Add a button to the end of the group.
	 * @param Button $element The element to add
	 * @param bool $prepend Whether or not to add the element at the beginning of the group instead at the end
	 * @return Boolean
	 */
	public function addButton(Button $element, $prepend=false){
		if($prepend)
			return (@array_unshift($this->children, $element) == 1);
		else
			return (@array_push($this->children, $element) == 1);	}

	/**
	 * Append another button-group to the end of the group.
	 * @param ButtonGroup $element The element to add
	 * @param bool $prepend Whether or not to add the element at the beginning of the group instead at the end
	 * @return Boolean
	 */
	public function appendButtonGroup(ButtonGroup $element, $prepend=false){
		if($prepend)
			return (@array_unshift($this->children, $element) == 1);
		else
			return (@array_push($this->children, $element) == 1);
	}

	/**
	 * Add a dropdown to the end of the group.
	 * @param Dropdown $element The element to add
	 * @param bool $prepend Whether or not to add the element at the beginning of the group instead at the end
	 * @return Boolean
	 */
	public function addDropdown(Dropdown $element, $prepend=false){
		if($prepend)
			return (@array_unshift($this->children, $element) == 1);
		else
			return (@array_push($this->children, $element) == 1);
	}

	/**
	 * Removes the first added occurrence of $element
	 * @param IElement $element The element to remove
	 * @return Boolean
	 */
	public function removeChild(IElement $element){
		foreach($this->children as $key => $child){
			if($child === $element){
				unset($this->children[$key]);
				return true;
			}
		}
		return false;
	}

	/**
	 * Iterator Aggregate Implementation
	 * @return \ArrayIterator
	 */
	public function getIterator(){
		return new \ArrayIterator($this->children);
	}

	/**
	 * Saves the element.
	 * @param Document $context
	 * @param int $depth The indented depth of the document right now (tabs).
	 * @return String
	 */
	public function save(Document $context, $depth = 0) {
		$group = _::line($depth, '<div '.$this->saveClassAttribute($context).'>');
		foreach($this->children as $child){
			$group .= $child->save($context, $depth+1);
		}
		$group .= _::line($depth, '</div>');
		return $group;
	}
}