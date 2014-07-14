<?php
/**
 * Bootstrap Pager IComponent
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
	Quark\Libraries\Bootstrap\Component,
	Quark\Libraries\Bootstrap\IElementMarkupClasses,
	Quark\Libraries\Bootstrap\baseElementMarkupClasses,
	Quark\Libraries\Bootstrap\baseElementDataAttributes,
	Quark\Util\Type\InvalidArgumentTypeException;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\baseMultiActivator;
use Quark\Libraries\Bootstrap\IActivatable;
use Quark\Libraries\Bootstrap\IMultiActivator;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Grouping element that can group buttons together, and can group a drop-down and a button together.
 */
class Pager extends Component implements IMultiActivator, IElementMarkupClasses {
	use baseElementMarkupClasses, baseMultiActivator;

	/**
	 * Used to perform actions on the Previous button.
	 */
	const BTN_PREVIOUS = 0;

	/**
	 * Used to perform actions on the Next button.
	 */
	const BTN_NEXT = 1;

	/**
	 * @var bool Whether or not the previous button is disabled.
	 */
	protected $prevDisabled = true;

	/**
	 * @var bool Whether or not the next button is disabled.
	 */
	protected $nextDisabled = true;

	/**
	 * @var string|IActivatable Link or IActivatable for previous button.
	 */
	protected $prevAction = '#';

	/**
	 * @var string|IActivatable Link or IActivatable for next button.
	 */
	protected $nextAction = '#';

	/**
	 * @var string Label for previous button.
	 */
	protected $prevLabel = 'Previous';

	/**
	 * @var string Label for next button.
	 */
	protected $nextLabel = 'Next';

	/**
	 * @param string|IActivatable $actionPrevious Link of the previous button.
	 * @param string|IActivatable $actionNext Link of the next button.
	 * @param string $labelPrevious Label of the previous button.
	 * @param string $labelNext Label of the next button.
	 */
	public function __construct($actionPrevious='#', $actionNext='#', $labelPrevious=null, $labelNext=null){
		$this->cssClasses = array('pager');
		$this->setAction(self::BTN_PREVIOUS, $actionPrevious);
		$this->setAction(self::BTN_NEXT, $actionNext);
		if(!empty($labelPrevious))
			$this->prevLabel = $labelPrevious;
		if(!empty($labelNext))
			$this->nextLabel = $labelNext;
	}

	/**
	 * Check if this class is able to activate the given class or any activatable.
	 * @param IActivatable $object
	 * @return bool
	 */
	public function hasActivatable(IActivatable $object = null){
		if(is_null($object))
			return is_object($this->prevAction) || is_object($this->nextAction);
		else
			return $this->prevAction === $object || $this->nextAction === $object;
	}

	/**
	 * Disable or enable a button
	 * @param int $btn The button to modify.
	 * @param boolean $state The new state (true is enabled, false is disabled).
	 * @return Pager Returns itself for method chaining.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function setActiveState($btn, $state){
		if(!is_bool($state))
			throw new InvalidArgumentTypeException('state', 'boolean', $state);
		if($btn == self::BTN_NEXT)
			$this->nextDisabled = $state;
		else
			$this->prevDisabled = $state;
		return $this;
	}

	/**
	 * Assign the link or IActivatable to be loaded or activated when a button get's pressed.
	 *
	 * Notice: Setting the action for a button automatically enables that button!
	 * @param int $btn The button to modify.
	 * @param string|IActivatable $action The new action.
	 * @return Pager Returns itself for method chaining.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function setAction($btn, $action){
		if(!(is_string($action) || (is_object($action) && $action instanceof IActivatable)))
			throw new InvalidArgumentTypeException('action', 'string', $action);
		if($btn == self::BTN_NEXT){
			$this->nextAction = $action;
			$this->nextDisabled = false;
		}else{
			$this->prevAction = $action;
			$this->prevDisabled = false;
		}
		return $this;
	}

	/**
	 * Assign the button label.
	 * @param int $btn The button to modify.
	 * @param string $label Label for the button.
	 * @return Pager Returns itself for method chaining.
	 * @throws \Quark\Util\Type\InvalidArgumentTypeException
	 */
	public function setLabel($btn, $label){
		if(!is_string($label))
			throw new InvalidArgumentTypeException('label', 'string', $label);
		if($btn == self::BTN_NEXT)
			$this->nextLabel = $label;
		else
			$this->prevLabel = $label;
		return $this;
	}

	/**
	 * Saves the element.
	 * @param Document $context
	 * @param int $depth The indented depth of the document right now (tabs).
	 * @return String
	 */
	public function save(Document $context, $depth = 0) {
		$pager = _::line($depth, '<ul '.$this->saveClassAttribute($context).'>');

		// Previous
		if(is_string($this->prevAction))
			$href = $this->prevAction;
		else {
			$this->prevAction->configureActivator($this);
			$href = '#'.$this->prevAction->getId();
		}
		$pager .= _::line($depth+1, '<li class="previous'._::enabled($this->prevDisabled, ' disabled').'"><a '.$context->encodeAttribute('href', $href).' '.$this->saveDataAttributes($context).'>'.$context->encodeText($this->prevLabel).'</a></li>');

		//Next
		if(is_string($this->nextAction))
			$href = $this->nextAction;
		else {
			$this->nextAction->configureActivator($this);
			$href = '#'.$this->nextAction->getId();
		}
		$pager .= _::line($depth+1, '<li class="next'._::enabled($this->nextDisabled, ' disabled').'"><a '.$context->encodeAttribute('href', $href).'>'.$context->encodeText($this->nextLabel).'</a></li>');

		$pager .= _::line($depth, '</ul>');
		return $pager;
	}

	/**
	 * Set what will happen when the activator is toggling.
	 * @see setAction
	 * @param IActivatable $object The activatable to add.
	 * @param int $btn The button to modify.
	 * @return bool
	 */
	public function addActivatable(IActivatable $object, $btn=self::BTN_PREVIOUS) {
		return $this->setAction($btn, $object);
	}

	/**
	 * Remove a previously registered activator
	 * @param IActivatable $object
	 * @return mixed
	 */
	public function removeActivatable(IActivatable $object) {
		if($this->prevAction === $object)
			$this->prevAction = null;
		if($this->nextAction === $object)
			$this->nextAction = null;
	}
}