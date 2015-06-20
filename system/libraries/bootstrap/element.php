<?php
/**
 * Basic Bootstrap Element
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 23, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\Bootstrap;
use Quark\Document\baseCollection,
    Quark\Document\IElement,
    Quark\Document\Document;

// Dependencies
\Quark\import(
	'Framework.Document.Element'
);

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class baseBootstrapElement
 * Provides helper function to nicely print tabs/depth.
 * @package Quark\Libraries\Bootstrap
 */
abstract class BootstrapElement implements IElement {
}

/**
 * Interface IElementDataAttributes
 *
 * Defines a component or element that can have it's data- attributes set and/or modified.
 * @package Quark\Libraries\Bootstrap\Components
 */
interface IElementDataAttributes extends IElement {
    /**
     * Set a HTML5 data-* attribute.
     * @param string $name The name of the property (!!WITHOUT "data-" prepended.)
     * @param string $value The string value of the attribute.
     */
    public function setDataAttribute($name, $value);

    /**
     * Get the value of  a HTML5 data-* attribute.
     * @param string $name The name of the property (!!WITHOUT "data-" prepended.)
     * @return string The string value of the attribute.
     */
    public function getDataAttribute($name);
}

/**
 * Trait baseElementDataAttributes
 * @package Quark\Libraries\Bootstrap\Components
 */
trait baseElementDataAttributes {
    /**
     * @var array Map of data attributes
     */
    protected $dataAttributes = array();

    /**
     * Set a HTML5 data-* attribute.
     * @param string $name The name of the property (!!WITHOUT "data-" prepended.)
     * @param string $value The string value of the attribute.
     * @return bool True on success false on error (use === false)
     */
    public function setDataAttribute($name, $value){
        if(!empty($name) && is_string($name) && is_string($value)){
            $this->dataAttributes[$name] = $value;
            return true;
        }else return false;
    }

    /**
     * Get the value of  a HTML5 data-* attribute.
     * @param string $name The name of the property (!!WITHOUT "data-" prepended.)
     * @return false|string The string value of the attribute. (Or false on failure, use === false for comparison)
     */
    public function getDataAttribute($name){
        if(!empty($name) && is_string($name) && isset($this->dataAttributes[$name]))
            return $this->dataAttributes[$name];
        else return false;
    }

    /**
     * Returns the html for all the data attributes that were set on this element.
     * @param Document $document The document in which this element will be applied.
     * @return string
     */
    protected function saveDataAttributes(Document $document){
        $attributes = '';
        foreach($this->dataAttributes as $name => $value){
            $attributes .= ' '.$document->encodeAttribute($name, $value);
        }
        return ltrim($attributes, ' ');
    }
}

/**
 * Interface IElementMarkupClasses
 *
 * Makes it possible to set CSS/Markup classes on elements and get and set them.
 * @package Quark\Libraries\Bootstrap\Components
 */
interface IElementMarkupClasses extends IElement {
    /**
     * Add a CSS/Markup class to the IComponent.
     * @param string $classname CSS class name.
     * @return void
     */
    public function addMarkupClass($classname);

    /**
     * Check if the element has a CSS/Markup class.
     * @param string $classname CSS class name.
     * @return boolean
     */
    public function hasMarkupClass($classname);

    /**
     * Remove a CSS/Markup class from the element.
     * @param string $classname CSS class name.
     * @return void
     */
    public function removeMarkupClass($classname);
}

/**
 * Trait baseElementMarkupClasses
 *
 * Base implementation of the IElementMarkupClasses interface.
 * @package Quark\Libraries\Bootstrap\Components
 */
trait baseElementMarkupClasses {
    /**
     * @var string[] List of CSS classes.
     */
    protected $cssClasses = array();

    /**
     * Add a CSS/Markup class to the IComponent.
     * @param string $classname CSS class name.
     * @return boolean|int New number of set classes on the element or false on failure.
     */
    public function addMarkupClass($classname){
        if(!empty($classname) && is_string($classname) && !$this->hasMarkupClass($classname))
            return array_push($this->cssClasses, $classname);
        return false;
    }

    /**
     * Check if the element has a CSS/Markup class.
     * @param string $classname CSS class name.
     * @return boolean
     */
    public function hasMarkupClass($classname){
        return in_array($classname, $this->cssClasses);
    }

    /**
     * Remove a CSS/Markup class from the element.
     * @param string $classname CSS class name.
     * @return boolean
     */
    public function removeMarkupClass($classname){
        foreach($this->cssClasses as $index => $class){
            if($class == $classname){
                unset($this->cssClasses[$index]);
                return true;
            }
        }
        return false;
    }

    /**
     * Saves all the classes in the form of (without the single outer quotes): 'class="some-class another-class"'
     * @param Document $document
     * @return string
     */
    protected function saveClassAttribute(Document $document){
        return $document->encodeAttribute('class', implode(' ', $this->cssClasses));
    }

    /**
     * Class switching helper.
     *
     * Helper method that makes it possible to define a list with classes, and switch to another class and automatically
     * have the other classes removed.
     * @param array $classes
     * @param string $class (New) class to set.
     */
    protected function switchMarkupClass(array &$classes, $class){
        foreach($this->cssClasses as $k => $v) {
            if (in_array($v, $classes))
                unset($this->cssClasses[$k]);
        }
        array_push($this->cssClasses, $class);
    }
}