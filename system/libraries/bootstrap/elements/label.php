<?php
/**
 * Bootstrap Label Element
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		June 17, 2015
 * @copyright	Copyright (C) 2012-2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\Bootstrap\Elements;
use Quark\Document\Document,
    Quark\Document\Utils\_,
    Quark\Document\IIndependentElement,
    Quark\Document\baseIndependentElement;
use Quark\Libraries\Bootstrap\Glyphicon;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Label Element
 *
 * Simple class representing a label.
 */
class Label implements IIndependentElement {
    use baseIndependentElement;

    // Label types or label modifiers.
    const LBL_DEFAULT   = 'default';
    const LBL_PRIMARY   = 'primary';
    const LBL_SUCCESS   = 'success';
    const LBL_INFO      = 'info';
    const LBL_WARNING   = 'warning';
    const LBL_DANGER    = 'danger';

    // Icon positions
    /**
     * Places the icon before the label text.
     */
    const ICON_PREFIX = 0;
    /**
     * Places the icon after the label text.
     */
    const ICON_AFFIX = 1;

    /**
     * @var array List of every type of modifier possible.
     * @internal
     */
    public static $modifiers = array(self::LBL_DEFAULT, self::LBL_PRIMARY, self::LBL_SUCCESS, self::LBL_INFO, self::LBL_WARNING, self::LBL_DANGER);

    /**
     * @var string The text for the label.
     * @access public
     */
    private $text = '';

    /**
     * @var string The set type of modifier; one of the LBL_* constants.
     * @access public
     * @see $modifiers Possible values.
     */
    private $modifier = null;

    /**
     * @var Glyphicon|null Icon to use.
     * @access public
     */
    private $icon = null;

    /**
     * @var int Position of the icon.
     * @access public
     */
    private $iconPosition = self::ICON_PREFIX;

    /**
     * @param string $text The text for the label, should not contain html. Is correctly formatted and (re-)encoded.
     * @param string $modifier One of the LBL_* constants.
     * @param Glyphicon $icon Optional icon to use.
     * @param int $iconPosition Position of the icon; one of the ICON_* class-constants.
     */
    public function __construct($text, $modifier=self::LBL_DEFAULT, Glyphicon $icon=null, $iconPosition=self::ICON_PREFIX){
        if(!is_string($text) || empty($text)) throw new InvalidArgumentTypeException('text', 'string', $text);
        if(!is_string($modifier) || empty($modifier) || !in_array($modifier, self::$modifiers)) throw new InvalidArgumentTypeException('text', 'string', $text);
        if(!is_integer($iconPosition)) throw new InvalidArgumentTypeException('iconPosition', 'integer', $iconPosition);
        $this->text = $text;
        $this->modifier = $modifier;
        $this->icon = $icon;
        $this->iconPosition = $iconPosition;
    }

    /**
     * @param string $name
     * @return string
     */
    public function __get($name){
        switch($name){
            case 'text':
                return $this->text;
            case 'modifier':
                return $this->modifier;
            case 'icon':
                return $this->icon;
            case 'iconPosition':
                return $this->iconPosition;
            default:
                throw new \UnexpectedValueException('The given class variable name, does not exist on this class.');
        }
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value){
        switch($name){
            case 'text':
                if(!is_string($value) || empty($value)) throw new InvalidArgumentTypeException('text', 'string', $value);
                $this->text = $value;
                break;
            case 'modifier':
                if(!is_string($value) || empty($value) || !in_array($value, self::$modifiers)) throw new InvalidArgumentTypeException('modifier', 'string', $value);
                $this->modifier = $value;
                break;
            case 'icon':
                if(!(is_object($value) || !is_null($value))) throw new InvalidArgumentTypeException('icon', '*\Bootstrap\Glyphicon', $value);
                $this->icon = $value;
                break;
            case 'iconPosition':
                if(!is_integer($value)) throw new InvalidArgumentTypeException('iconPosition', 'integer', $value);
                $this->iconPosition = $value;
                break;
            default:
                throw new \UnexpectedValueException('The given class variable name, does not exist on this class.');
        }
    }

    /**
     * Retrieve the HTML representation of the element
     * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
     * @param int $depth The current indentation depth, not required.
     * @return String HTML Representation
     */
    public function save(Document $context=null, $depth=0){
        if($this->icon != null){
            return
                '<span class="label label-' . $this->modifier . '">' .
                    ($this->iconPosition == self::ICON_PREFIX ? $this->icon->independentSave(0) . ' ' : '') .
                    _::encode($this->text, $context) .
                    ($this->iconPosition == self::ICON_AFFIX ? ' ' . $this->icon->independentSave(0) : '') .
                '</span>';
        }else{
            return '<span class="label label-' . $this->modifier . '">' . _::encode($this->text, $context) . '</span>';
        }
    }

    /**
     * Retrieve the HTML representation of the element without requiring a document as context.
     * @param int $depth The current indentation depth, not required.
     * @return String HTML Representation
     */
    public function independentSave($depth=0){
        return $this->save(null, $depth);
    }
}