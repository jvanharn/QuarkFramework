<?php
/**
 * Bootstrap Form Element based on the default Form
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
use Quark\Document\Form\Action;
use Quark\Document\Form\Autocomplete;
use Quark\Document\Form\IValidatableField;
use Quark\Document\Form\Selectable;
use Quark\Document\Form\TextField;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Bootstrap Form object
 */
class Form extends \Quark\Document\Form\Form {
    // Layout types
    const LAYOUT_HORIZONTAL = 'form-horizontal';
    const LAYOUT_VERTICAL = 'form-vertical';
    const LAYOUT_INLINE = 'form-inline';

    /** @var array List of the available layout types (the LAYOUT_* class constants). */
    public static $layoutTypes = array(self::LAYOUT_HORIZONTAL, self::LAYOUT_VERTICAL, self::LAYOUT_INLINE);

    /**
     * Set this form's layout style.
     * @param string $type One of the LAYOUT_* class constants
     * @return bool
     */
    public function setLayoutType($type){
        if(!in_array($type, self::$layoutTypes))
            return false;
        if(!$this->hasMarkupClass($type)) {
            foreach (self::$layoutTypes as $layout) {
                $this->removeMarkupClass($layout);
            }
            $this->addMarkupClass($type);
        }
        return true;
    }

    /**
     * Save a single field to it's html representation.
     *
     * If you want to "template" the fields, this is the easiest place to do that. (Just extend the class and override this method).
     * @param Field $field
     * @param int $depth
     * @return string
     */
    protected function saveField(Field $field, $depth=2){
        $name = $field->getName();
        $label = $field->getLabel();
        $enable_validation = ($field instanceof IValidatableField && is_array($this->validated));
        $field_error = ($enable_validation && isset($this->validated[$name]));
        if($field instanceof Checkbox) {
            return  _::line($depth, '<div class="form-group'.($field_error ? ' has-errors' : ($enable_validation ? ' has-success' : '')).'">') .
                        _::line($depth+1, '<div class="checkbox">').
                            (is_null($label) ? '' : _::line($depth + 2, '<label>')) .
                                $field->save($this->context, $depth + 3) . (is_null($label) ? '' : $label) .
                            (is_null($label) ? '' : _::line($depth + 2, '</label>')) .
                        _::line($depth+1, '</div>').
                        ($field_error ? _::line($depth + 1, '<span id="'.$name.'-errors" class="help-block">' . $this->validated[$name] . '</span>') : '') .
                    _::line($depth, '</div>');
        }else{
            if($field instanceof TextField || $field instanceof Selectable || $field instanceof Autocomplete)
                $field->addMarkupClass('form-control');
            if($field instanceof Action && !$field->hasMarkupClass('btn')){
                $field->addMarkupClass('btn');
                $field->addMarkupClass('btn-default');
            }
            return  _::line($depth, '<div class="form-group'.($field_error ? ' has-error' : ($enable_validation ? ' has-success' : '')).'">') .
                        (is_null($label) ? '' : _::line($depth + 1, '<label class="control-label" for="' . $name . '">' . $label . '</label>')) .
                        //_::line($depth + 1, '<div class="input-group'.($field_error ? ' invalid-value' : '').'">').
                            $field->save($this->context, $depth + 2) .
                        //_::line($depth + 1, '</div>').
                        ($field_error ? _::line($depth + 1, '<span id="'.$name.'-errors" class="help-block">' . $this->validated[$name] . '</span>') : '') .
                    _::line($depth, '</div>');
        }
    }
}