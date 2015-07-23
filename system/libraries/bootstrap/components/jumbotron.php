<?php
/**
 * Bootstrap Jumbotron Component
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		December 15, 2012
 * @copyright	Copyright (C) 2012-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 *
 * Copyright (C) 2012-2013 Jeffrey van Harn
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
use Quark\Document\Document;
use Quark\Document\IElement;
use Quark\Document\Utils\_;
use Quark\Document\baseElementMarkupClasses;
use Quark\Libraries\Bootstrap\Component;
use Quark\Document\IElementMarkupClasses;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Jumbotron Bootstrap Component.
 */
class Jumbotron extends Component implements IElementMarkupClasses {
    use \Quark\Document\baseElementMarkupClasses;

    /**
     * @var string $header
     * @var string $content
     * @var Button $button
     * @access public
     */
    protected $header, $content, $button;

    /**
     * @param string $header
     * @param string|IElement $content
     * @param Button $button
     * @param string $id
     */
    public function __construct($header, $content, Button $button=null, $id=null){
        $this->__set('header', $header);
        $this->__set('content', $content);
        $this->__set('button', $button);
        $this->setId($id);
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function __get($name){
        switch($name){
            case 'header':
                return $this->header;
            case 'content':
                return $this->content;
            case 'button':
                return $this->button;
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
            case 'header':
                if(!(is_string($value) || is_null($value)))
                    throw new InvalidArgumentTypeException('header', 'string|null', $value);
                $this->header = $value;
                break;
            case 'content':
                if(!(is_string($value) || (is_object($value) && $value instanceof IElement)))
                    throw new InvalidArgumentTypeException('content', 'string|IElement', $value);
                $this->content = $value;
                break;
            case 'button':
                if(!(is_null($value) || (is_object($value) && $value instanceof Button)))
                    throw new InvalidArgumentTypeException('button', 'Button|null', $value);
                $this->button = $value;
                break;
            default:
                throw new \UnexpectedValueException('The given class-variable "'.$name.'"" does not exist.');
        }
    }


    /**
     * Retrieve the HTML representation of the element
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth The current indentation depth, not required.
     * @return String HTML Representation
     */
    public function save(Document $context, $depth=0){
        $content = is_object($this->content) ? $this->content->save($context, $depth+3) : $context->encodeText($this->content);
        return  _::line($depth, '<div class="jumbotron">').
                    _::line($depth+1, '<div class="container">').
                        (!empty($this->header) ? _::line($depth+2, '<h1>'.$this->header.'</h1>') : '').
                        _::line($depth+2, '<p>').$content._::line($depth+2, '</p>').
                        (!empty($this->button) ? _::line($depth+2, '<p>'.$this->button->save($context, $depth+2).'</p>'):'').
                    _::line($depth+1, '</div>').
                _::line($depth, '</div>');
    }
}