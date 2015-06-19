<?php
/**
 * Simple Submit button class.
 * 
 * @package		Quark-Framework
 * @version		$Id: action.php 71 2013-02-02 19:40:11Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		January 27, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2013 Jeffrey van Harn
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
namespace Quark\Document\Form;
use \Quark\Document\Document;
use Quark\Document\Utils\_;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Action Button class
 * 
 * Enables you to add submit and/or reset buttons to your form.
 */
class Action extends Field {
	/**
	 * When clicked this button will submit the form.
	 */
	const ACTION_SUBMIT = 0;
	
	/**
	 * When clicked this button will reset the form to it's initial state.
	 */
	const ACTION_RESET = 1;
	
	/**
	 * The action chosen.
	 * @var integer
	 */
	protected $action;
	
	/**
	 * Text on the button.
	 * @var string
	 */
	protected $text;

	/**
	 * @param integer $action One of the ACTION_* constants.
	 * @param string $text Button label.
	 * @throws \InvalidArgumentException When the integer used for $action is invalid.
	 */
	public function __construct($action, $text='Submit'){
		switch($action){
			case self::ACTION_SUBMIT:
				$this->name = 'submit';
			break;
			case self::ACTION_RESET:
				$this->name = 'reset';
			break;
			default:
				throw new \InvalidArgumentException('Parameter $action should be one of the ACTION_* constants.');
		}
		$this->action = $action;
		$this->text = (string) $text;
	}

    /**
     * Save the button to HTML.
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth
     * @return String HTML Representation
     */
	public function save(Document $context, $depth=0) {
		//return _::line($depth, '<input type="'.$this->name.'" '.$context->encodeAttribute('value', $this->text).' '.$this->saveClassAttribute($context).' />');
		return _::line($depth, '<button type="'.$this->name.'" '.$this->saveClassAttribute($context).'>'.$context->encodeText($this->text).'</button>');
	}
}