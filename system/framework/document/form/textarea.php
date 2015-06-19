<?php
/**
 * Textarea class for use with the Form class.
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		June 17, 2015
 * @copyright	Copyright (C) 2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 *
 * Copyright (C) 2015 Jeffrey van Harn
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
use Quark\Document\Document;
use Quark\Document\Utils\_;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Text field class for use with the Form class.
 */
class Textarea extends TextField {
    /**
     * Retrieve the HTML representation of the element
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth
     * @return String HTML Representation
     */
    public function save(Document $context, $depth=0) {
        return _::line(
            $depth,
            '<textarea type="text" '.
                $context->encodeAttribute('name', $this->name).' '.
                $context->encodeAttribute('id', $this->name).' '.
                (empty($this->placeholder)?'':$context->encodeAttribute('placeholder', $this->placeholder).' ').
                $this->saveClassAttribute($context).' '.
            '>'.((empty($this->value) && empty($this->last))?'':$context->encodeText($this->value?:($this->last?:'')).' ').'</textarea>');
    }
}