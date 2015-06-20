<?php
/**
 * Bootstrap Form Action element
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		June 17, 2015
 * @copyright	Copyright (C) 2012-2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 *
 * Copyright (C) 2012-2015 Jeffrey van Harn
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
namespace Quark\Libraries\Bootstrap\Form;
use Quark\Document\Document,
    Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\Glyphicon;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Action Button class
 *
 * Enables you to add submit and/or reset buttons to your form.
 */
class Action extends \Quark\Document\Form\Action {
    /** @var string Icon for the button. */
    protected $icon;

    /**
     * @param integer $action One of the ACTION_* constants.
     * @param string $text Button label.
     * @param string $icon The glyphicon glass name without the 'glyphicon-' prefix. Use the Glyphicon helper class for convenience.
     * @see Glyphicon
     * @throws \InvalidArgumentException When the integer used for $action is invalid.
     */
    public function __construct($action=self::ACTION_SUBMIT, $text='Submit', $icon=Glyphicon::ICO_OK){
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
        $this->icon = $icon;
    }

    /**
     * Save the button to HTML.
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth
     * @return String HTML Representation
     */
    public function save(Document $context, $depth=0) {
        return _::line($depth, '<button type="'.$this->name.'" '.$this->saveClassAttribute($context).'>'.(!empty($this->icon)?'<i '.$context->encodeAttribute('class', 'glyphicon glyphicon-'.$this->icon).'></i> ':'').$context->encodeText($this->text).'</button>');
    }
}