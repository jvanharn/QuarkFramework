<?php
/**
 * Bootstrap Alert Component
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		June 18, 2015
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
namespace Quark\Libraries\Bootstrap\Components;
use Quark\Document\Document;
use Quark\Document\Utils\_;
use Quark\Document\IElementMarkupClasses;
use Quark\Document\baseElementMarkupClasses;
use Quark\Libraries\Bootstrap\Component;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Bootstrap Alert component.
 *
 * Allows you to have a styled alert, info success or warning box on your pages.
 */
class Alert extends Component implements IElementMarkupClasses {
    use \Quark\Document\baseElementMarkupClasses;

    // Alert types.
    const TYPE_SUCCESS = 'alert-success';
    const TYPE_INFO = 'alert-info';
    const TYPE_WARNING = 'alert-warning';
    const TYPE_DANGER = 'alert-danger';

    /**
     * @var array List of valid alert types (the TYPE_* class-constants).
     * @access private
     */
    public static $types = array(self::TYPE_SUCCESS, self::TYPE_INFO, self::TYPE_WARNING, self::TYPE_DANGER);

    /** @var string Alert contents. */
    private $content = '';

    /** @var string The type of alert. */
    private $type = self::TYPE_INFO;

    /** @var bool Whether or not this alert is dismissible. (Only possible if you have the Javascript extensions enabled in the page.) */
    private $dismissible = true;

    /**
     * @param string $content The html/text to display inside the alertbox.
     * @param string $type
     * @param bool $dismissible
     */
    public function __construct($content, $type=self::TYPE_INFO, $dismissible=false){
        if(!(is_string($content) && !empty($content))) throw new \InvalidArgumentException('Argument content must be of type string, and have a non-empty value.');
        if(!(is_string($type) && in_array($type, self::$types))) throw new InvalidArgumentTypeException('type', 'string and be one of the TYPE_* class-constants', $type);
        if(!is_bool($dismissible)) throw new InvalidArgumentTypeException('dismissable', 'boolean', $dismissible);

        $this->content = $content;
        $this->type = $type;
        $this->cssClasses = array('alert', $type);
        $this->dismissible = $dismissible;
    }

    /**
     * @param string $name
     * @return string|bool
     */
    public function __get($name){
        switch($name){
            case 'content':
                return $this->content;
            case 'type':
                return $this->type;
            case 'dismissible':
                return $this->dismissible;
            default:
                throw new \UnexpectedValueException('The given class-variable "'.$name.'"" does not exist.');
        }
    }

    /**
     * @param string $name
     * @param string|bool $value
     */
    public function __set($name, $value){
        switch($name){
            case 'content':
                if(!(is_string($value) && empty($value))) throw new \InvalidArgumentException('Argument content must be of type string, and have a non-empty value.');
                $this->content = $value;
                break;
            case 'type':
                if(!(is_string($value) && in_array($value, self::$types))) throw new InvalidArgumentTypeException('type', 'string and be one of the TYPE_* class-constants', $value);
                $this->type = $value;
                $this->switchMarkupClass(self::$types, $value);
                break;
            case 'dismissible':
                if(!is_bool($value)) throw new InvalidArgumentTypeException('dismissable', 'boolean', $value);
                $this->dismissible = $value;
                break;
            default:
                throw new \UnexpectedValueException('The given class-variable "'.$name.'"" does not exist.');
        }
    }

    /**
     * Saves the alert component to html.
     * @param Document $context
     * @param int $depth
     * @return String
     */
    public function save(Document $context, $depth=0){
        return
            _::line($depth, '<div '.$this->saveClassAttribute($context).' role="alert">').
                ($this->dismissible ? _::line($depth+1, '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>') : '').
                _::line($depth+1, $this->content).
            _::line($depth, '</div>');
    }
}