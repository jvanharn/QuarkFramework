<?php
/**
 * Bootstrap Form Plaintext/Static text element
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
    Quark\Document\Utils\_,
    Quark\Document\Form\Checkbox,
    Quark\Document\Form\Field;
use Quark\Document\Form\INormalizableField;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Static plaintext field that always returns the value you set on it.
 */
class Plaintext extends Field {
    /**
     * The value for this static field
     * @var string
     */
    protected $value;

    /**
     * A placeholder value for the field.
     * @var string
     */
    protected $placeholder;

    /**
     * @param string $name Name of the element for referencing it's data etc.
     * @param string $label Label of the field in the form.
     * @param string $value Default value of the field if applicable.
     */
    public function __construct($name, $label, $value=null){
        $this->name = (string) $name;
        $this->label = (string) $label;
        $this->value = (string) $value;
        $this->addMarkupClass('form-control-static');
    }

    /**
     * Retrieve the HTML representation of the element
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth
     * @return String HTML Representation
     */
    public function save(Document $context, $depth=0) {
        return _::line($depth, '<p '.$this->saveClassAttribute($context).'>'.$this->value.'</p>');
    }
}
