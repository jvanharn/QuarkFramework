<?php
/**
 * Bootstrap Text Utility/Helper
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
namespace Quark\Libraries\Bootstrap\Elements;
use Quark\Document\baseElementMarkupClasses;
use Quark\Document\Document,
    Quark\Document\Utils\_,
    Quark\Document\IIndependentElement,
    Quark\Document\IElementMarkupClasses,
	Quark\Document\IElement;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class that provides static helpers to help with common operations on (Hyper) Text and an basic implementation of an element.
 */
class Text implements IElement, IElementMarkupClasses {
    use baseElementMarkupClasses;

    /**
     * @internal
     * @var array Possible values for the class variable 'alignment'.
     */
    public static $alignmentValues = array('left', 'center', 'right', 'justify', 'nowrap');

    /**
     * @internal
     * @var array Possible values for the class variable 'transformation'.
     */
    public static $transformationValues = array('lowercase', 'uppercase', 'capitalize');

    /**
     * @var string The actual represented text. Setting this (not using the add method) will replace it's entire contents.
     * @access public
     */
    private $text = '';

    /**
     * @var int The type of heading. Possible values: null, 1-6.
     * @access public
     */
    private $heading = null;

    /**
     * @var bool Whether or not this is set inside an inline element, or inside a block-level element.
     * @access public
     */
    private $inline = false;

    /**
     * @var string Set the alignment of the text. Possible values: null, left, center, right, justify, nowrap.
     * @access public
     */
    private $alignment = null;

    /**
     * @var string Set the transformation for the text. Possible values: null, lowercase, uppercase, capitalize.
     * @access public
     */
    private $transformation = null;

    /**
     * @var bool Whether or not to encode html inside the text value for this element. (This wont prevent every type of XSS attack, but will go a long way if you use this in combination with the default charset setup of the document object.)
     * @access public
     */
    private $htmlEncode = true;

    /**
     * Creates a new text object.
     * @param string $text Required text to represent with this object.
     */
    public function __construct($text){
        $this->text = $text;
    }

    /**
     * @param string $name
     * @return int|bool|string|null
     */
    public function __get($name){
        switch($name){
            case 'text':
                return $this->text;
            case 'heading':
                return $this->heading;
            case 'inline':
                return $this->inline;
            case 'block':
                return !$this->inline;
            case 'alignment':
                return $this->alignment;
            case 'transformation':
                return $this->transformation;
            case 'htmlEncode':
                return $this->htmlEncode;
            default:
                throw new \UnexpectedValueException('The given class variable name, does not exist on this class.');
        }
    }

    /**
     * @param string $name
     * @param int|bool|string|null $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'text':
                if(!empty($value))
                    $this->text = $value;
                else
                    throw new \InvalidArgumentException('Value for class variable "text" should be a non-empty string value.');
                break;
            case 'heading':
                if (ctype_digit($value) && $value > 0 && $value <= 6)
                    $this->heading = $value;
                else if (empty($value))
                    $this->heading = null;
                else
                    throw new \InvalidArgumentException('Value for class variable "heading" should be an integer and be between 1 and 6, or be null.');
                break;
            case 'inline':
            case 'block':
                if (is_bool($value))
                    $this->inline = ($name == 'inline') ? $value : !$value;
                else
                    throw new \InvalidArgumentException('Value for class variable "inline" or "block" should be a boolean.');
                break;
            case 'alignment':
                if (in_array($value, self::$alignmentValues) || $value === null)
                    $this->alignment = $value;
                else
                    throw new \InvalidArgumentException('Value for class variable "alignment" should be an appropriate string value or null. For possible values, check the documentation.');
                break;
            case 'transformation':
                if (in_array($value, self::$transformationValues) || $value === null)
                    $this->transformation = $value;
                else
                    throw new \InvalidArgumentException('Value for class variable "transformation" should be an appropriate string value or null. For possible values, check the documentation.');
                break;
            case 'htmlEncode':
                $this->htmlEncode = !!$value;
                break;
            default:
                throw new \UnexpectedValueException('The given class variable name, does not exist on this class.');
        }
    }

    /**
     * Chainable method for setting class properties.
     * @param string $name
     * @param int|bool|string|null $value
     * @return $this
     */
    public function set($name, $value){
        $this->__set($name, $value);
        return $this;
    }

    /**
     * Add text or another element to this object.
     *
     * This method can be used to add independent elements or text to this text element.
     * Please realise that this is not an element collection. So elements that are added are instantly converted to text upon adding them.
     * Note: If you have htmlencoding set to true for the entire class (Which it is by default) any added elements will be html encoded upon save. In order to prevent this, set htmlencode to false before using this method.
     * @param string|IIndependentElement $thing The text or element to add to the text.
     * @param bool $forceHtmlEncode Force the given text or object to be html-encoded regardless of the class settings.
     * @return $this
     */
    public function add($thing, $forceHtmlEncode=false){
        if(is_object($thing) && $thing instanceof IIndependentElement){
            $txt = $thing->save();
        }else $txt = $thing;
        $this->text .= !!$forceHtmlEncode ? _::encode($txt) : $txt;
        return $this;
    }

    /**
     * Retrieve the HTML representation of the element
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth The current indentation depth, not required.
     * @return String HTML Representation
     */
    public function save(Document $context, $depth = 0) {
        if($this->inline && is_null($this->heading) && is_null($this->alignment) && is_null($this->transformation))
            return $this->htmlEncode ? _::encode($this->text, $context) : $this->text;

        // Set css classes
        if($this->inline && !is_null($this->heading))
            $this->addMarkupClass('h'.$this->heading);
        if(!is_null($this->alignment))
            $this->addMarkupClass('text-'.$this->alignment);
        if(!is_null($this->transformation))
            $this->addMarkupClass('text-'.$this->transformation);

        // Save the tag
        $otag = null;
        $etag = null;
        if($this->inline){
            $otag = '<span '.$this->saveClassAttribute($context).'>';
            $etag = '</span>';
        }else if($this->heading !== null){
            $otag = '<h'.$this->heading.' '.$this->saveClassAttribute($context).'>';
            $etag = '</h'.$this->heading.'>';
        }else {
            $otag = '<p '.$this->saveClassAttribute($context).'>';
            $etag = '</p>';
        }

        // Remove them again for compat.
        if($this->inline && !is_null($this->heading))
            $this->removeMarkupClass('h'.$this->heading);
        if(!is_null($this->alignment))
            $this->removeMarkupClass('text-'.$this->alignment);
        if(!is_null($this->transformation))
            $this->removeMarkupClass('text-'.$this->transformation);

        // Return
        return $otag.($this->htmlEncode ? _::encode($this->text, $context) : $this->text).$etag;
    }


    /**
     * Create a new text object.
     * @param string $text Text contents.
     * @param bool $htmlEncode Whether or not to html encode the given text.
     * @return Text
     */
    public static function make($text, $htmlEncode=true){
        $obj = new Text($text);
        $obj->htmlEncode = $htmlEncode;
        return $obj;
    }
}