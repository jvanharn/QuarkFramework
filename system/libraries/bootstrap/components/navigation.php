<?php
/**
 * Bootstrap Navigation or Nav Component
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		June 17, 2015
 * @copyright	Copyright (C) 2014-2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 *
 * Copyright (C) 2014-2015 Jeffrey van Harn
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
use IteratorAggregate;
use Quark\Document\Document;
use Quark\Document\IElement;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\baseElementDataAttributes;
use Quark\Libraries\Bootstrap\baseElementMarkupClasses;
use Quark\Libraries\Bootstrap\Glyphicon;
use Quark\Libraries\Bootstrap\IActivator;
use Quark\Libraries\Bootstrap\IElementDataAttributes;
use Quark\Libraries\Bootstrap\IElementMarkupClasses;
use Traversable;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Load dependencies
\Quark\import(
    'Libraries.Bootstrap.Component',
    true
);

/**
 * Bootstrap Navigation (Navs) Component
 */
class Navigation implements IElement, IElementMarkupClasses, IElementDataAttributes, IteratorAggregate {
    use baseElementMarkupClasses, baseElementDataAttributes;

    // Navigation types
    const TYPE_TABS = 'nav-tabs';
    const TYPE_PILLS = 'nav-pills';
    const TYPE_NAVBAR = 'navbar-nav';

    // Helper classes that you can add yourself
    const CLASS_JUSTIFIED = 'nav-justified';
    const CLASS_STACKED = 'nav-stacked';

    /**
     * @var string[] List of navigation types.
     * @internal
     */
    public static $types = array(self::TYPE_TABS, self::TYPE_PILLS, self::TYPE_NAVBAR);

    /** @var INavigationElement[] */
    protected $items = array();

    /** @var string Navigation type. */
    protected $type;

    /**
     * @param string $type The type of navbar to create.
     */
    public function __construct($type=self::TYPE_TABS){
        $this->cssClasses = array('nav');
        $this->__set('type', $type);
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function __get($name){
        switch($name){
            case 'type':
                return $this->type;
            default:
                throw new \UnexpectedValueException('The given class-variable "'.$name.'"" does not exist.');
        }
    }

    /**
     * @param string $name
     * @param string|null $value
     */
    public function __set($name, $value){
        switch($name){
            case 'type':
                if(!in_array($value, self::$types))
                    throw new \InvalidArgumentException('The value given for the class-variable "type" is not in the list of accepted values. Please check the documentation for acceptable values.');
                if(!empty($this->type))
                    $this->removeMarkupClass($this->type);
                $this->type = $value;
                $this->addMarkupClass($this->type);
                break;
            default:
                throw new \UnexpectedValueException('The given class-variable "'.$name.'"" does not exist.');
        }
    }

    /**
     * Add a navigation item to this navigation list.
     * @param INavigationElement $item
     */
    public function add(INavigationElement $item){
        @\array_push($this->items, $item);
    }

    /**
     * Add a link/item to the menu.
     * @param string $text
     * @param string $href
     */
    public function addLink($text, $href='#'){
        $this->add(new NavigationLink($text, $href));
    }

    /**
     * Add a sub-menu/dropdown to the menu.
     * @param string $text
     * @param array $menu Array of Key => value pairs where the key is the text, and the value is the url.
     * @param string $href
     */
    public function addDropdown($text, array $menu=array(), $href='#'){
        $this->add($drop = new NavigationDropdown($text, $href));
        foreach($menu as $key => $value){
            $drop->addLink($key, $value);
        }
    }

    /**
     * Remove the specified item from the navigation list.
     * @param INavigationElement $item
     * @return bool
     */
    public function remove(INavigationElement $item){
        foreach($this->items as $k => $obj){
            if($obj === $item) {
                unset($this->items[$k]);
                return true;
            }
        }
        return false;
    }

    /**
     * Saves the element.
     * @param Document $context
     * @param int $depth
     * @return String
     */
    public function save(Document $context, $depth = 0) {
        $menu  = _::line($depth, '<ul '.$this->saveClassAttribute($context).' '.$this->saveDataAttributes($context).'>');
        foreach($this->items as $item){
            $menu .= $item->save($context, $depth+1);
        }
        $menu .= _::line($depth, '</ul>');
        return $menu;
    }

    /**
     * Retrieve an external iterator.
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable
     */
    public function getIterator(){
        return new \ArrayIterator($this->items);
    }
}

/**
 * Common Interface INavigationElement
 */
interface INavigationElement extends IElement {}

/**
 * Navigation Link Item for the Navigation class.
 */
class NavigationLink implements INavigationElement {
    /**
     * @var string $text Contains the link text.
     * @var string $href Contains the link.
     * @var Glyphicon $icon Contains the optional icon.
     */
    public $text, $href, $icon;

    /**
     * @param string $text The link text.
     * @param string $href The linked location.
     * @param Glyphicon $icon The optional icon.
     */
    public function __construct($text, $href, Glyphicon $icon=null){
        $this->text = (string) $text;
        $this->href = (string) $href;
        $this->icon = $icon;
    }

    /**
     * Retrieve the HTML representation of the element
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth The current indentation depth, not required.
     * @return String HTML Representation
     */
    public function save(Document $context, $depth=0){
        return _::line($depth+1, '<li><a '.$context->encodeAttribute('href', $this->href).'>'.(!is_null($this->icon)?$this->icon->save($context).' ':'').$context->encodeText($this->text).'</a></li>');
    }
}

/**
 * Navigation Dropdown Item for the Navigation class.
 */
class NavigationDropdown implements INavigationElement, IteratorAggregate {
    /**
     * @var string $text Contains the link text.
     * @var string $href Contains the link.
     * @var NavigationLink[] $items Contains the links for this dropdown menu.
     */
    public $text, $href, $items=array();

    /**
     * @param string $text The dropdown button label.
     * @param string $href The optional link for the dropdown button if javascript is disabled.
     */
    public function __construct($text, $href='#'){
        $this->text = (string) $text;
        $this->href = (string) $href;
    }

    /**
     * Add a navigation item to this navigation list.
     * @param NavigationLink $item
     */
    public function add(NavigationLink $item){
        @\array_push($this->items, $item);
    }

    /**
     * Add a link/item to the menu.
     * @param $text
     * @param string $href
     */
    public function addLink($text, $href='#'){
        $this->add(new NavigationLink($text, $href));
    }

    /**
     * Remove the specified item from the navigation list.
     * @param NavigationLink $item
     * @return bool
     */
    public function remove(NavigationLink $item){
        foreach($this->items as $k => $obj){
            if($obj === $item) {
                unset($this->items[$k]);
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve the HTML representation of the element
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth The current indentation depth, not required.
     * @return String HTML Representation
     */
    public function save(Document $context, $depth=0){
        $menu  = _::line($depth, '<li class="dropdown">');
        $menu .= _::line($depth+1, '<a '.$context->encodeAttribute('href', (string) $this->href).' class="dropdown-toggle" data-toggle="dropdown">'.$context->encodeText((string) $this->text).' <b class="caret"></b></a>');

        $menu .= _::line($depth+1, '<ul class="dropdown-menu">');
        foreach($this->items as $item)
            $menu .= $item->save($context, $depth+2);
        $menu .= _::line($depth+1, '</ul>');
        $menu .= _::line($depth, '</li>');
        return $menu;
    }

    /**
     * Retrieve an external iterator.
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable
     */
    public function getIterator(){
        return new \ArrayIterator($this->items);
    }
}
