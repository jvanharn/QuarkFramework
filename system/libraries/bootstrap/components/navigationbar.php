<?php
/**
 * Bootstrap navigation bar component
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		January 27, 2014
 * @copyright	Copyright (C) 2014-2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\Bootstrap\Components;
use Quark\Document\baseCollection;
use Quark\Document\baseElementMarkupClasses;
use Quark\Document\Document;
use Quark\Document\Form\Form;
use Quark\Document\ICollection;
use Quark\Document\IElement;
use Quark\Document\Utils\_;
use Quark\Document\Utils\Image;
use Quark\Error;
use Quark\Libraries\Bootstrap\Component;
use Quark\Libraries\Bootstrap\Elements\Text;
use Quark\Document\IElementMarkupClasses;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Load dependencies
\Quark\import(
	'Libraries.Bootstrap.Component',
true);

/**
 * Bootstrap Navigation-bar IComponent
 * @property string $brand
 * @property string $type
 * @property string|null $position
 * @property string|null $container
 */
class NavigationBar extends Component implements IElementMarkupClasses, ICollection {
    use baseElementMarkupClasses, baseCollection;

    // Positioning constants for navbar children.
    const ALIGN_DEFAULT = null;
    const ALIGN_LEFT = 'navbar-left';
    const ALIGN_RIGHT = 'navbar-right';

    // Positioning constants for the navbar itself.
    const POS_DEFAULT = null;
    const POS_FIXED_TOP = 'navbar-fixed-top';
    const POS_FIXED_BOTTOM = 'navbar-fixed-bottom';
    const POS_STATIC_TOP = 'navbar-static-top';

    // Navbar type constants
    const TYPE_DEFAULT = 'navbar-default';
    const TYPE_INVERTED = 'navbar-inverse';

    // Navbar container types
    const CONTAINER_NONE = null;
    const CONTAINER_DEFAULT = 'container';
    const CONTAINER_FLUID = 'container-fluid';

    /**
     * @var array List of possible alignment values.
     * @internal
     */
    public static $alignments = array(self::ALIGN_DEFAULT, self::ALIGN_LEFT, self::ALIGN_RIGHT);

    /**
     * @var array List of possible position values.
     * @internal
     */
    public static $positions = array(self::POS_DEFAULT, self::POS_FIXED_BOTTOM, self::POS_FIXED_TOP, self::POS_STATIC_TOP);

    /**
     * @var array List of possible type values.
     * @internal
     */
    public static $types = array(self::TYPE_DEFAULT, self::TYPE_INVERTED);

    /**
     * @var array List of possible container configurations.
     * @internal
     */
    public static $containers = array(self::CONTAINER_DEFAULT, self::CONTAINER_FLUID, self::CONTAINER_NONE);

	/** @var string The title or brand of the nav-bar. */
	protected $brand;

    /** @var string The link for the brand. */
	public $brandLink = '/';

    /** @var string The type or style of navbar to use. */
    protected $type = self::TYPE_DEFAULT;

    /** @var string|null The position of the navbar. */
    protected $position = self::POS_DEFAULT;

    /** @var string|null The type of container to use, if at all. */
    protected $container = self::CONTAINER_DEFAULT;

    /**
     * @param string|Image $brand The brandname in the form of a string or an Image object.
     * @param string $brandLink The href for the brand image or text.
     * @param string $type The type or style of navbar to use.
     * @param string|null $position The position of the navbar.
     * @param string|null $container The type of container to use, if at all.
     * @param string|null $id
     */
	public function __construct($brand=null, $brandLink='/', $type=self::TYPE_DEFAULT, $position=self::POS_DEFAULT, $container=self::CONTAINER_DEFAULT, $id=null){
        $this->cssClasses = array('navbar', 'navbar-default');

        if(!empty($brand))
            $this->__set('brand', $brand);
        $this->__set('type', $type);
        $this->__set('position', $position);
        $this->__set('container', $container);
        $this->setId($id);
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function __get($name){
        switch($name){
            case 'brand':
                return $this->brand;
            case 'type':
                return $this->type;
            case 'position':
                return $this->position;
            case 'container':
                return $this->container;
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
            case 'brand':
                if(!(is_string($value) || (is_object($value) && $value instanceof Image)))
                    throw new InvalidArgumentTypeException('brand', 'string or Image (object) instance', $value);
                $this->brand = $value;
                break;
            case 'type':
                if(!in_array($value, self::$types))
                    throw new \InvalidArgumentException('The value given for the class-variable "type" is not in the list of accepted values. Please check the documentation for acceptable values.');
                if(!empty($this->type))
                    $this->removeMarkupClass($this->type);
                $this->type = $value;
                $this->addMarkupClass($this->type);
                break;
            case 'position':
                if(!in_array($value, self::$positions))
                    throw new \InvalidArgumentException('The value given for the class-variable "position" is not in the list of accepted values. Please check the documentation for acceptable values.');
                if(!empty($this->position))
                    $this->removeMarkupClass($this->position);
                $this->position = $value;
                $this->addMarkupClass($this->position);
                break;
            case 'container':
                if(!in_array($value, self::$containers))
                    throw new \InvalidArgumentException('The value given for the class-variable "container" is not in the list of accepted values. Please check the documentation for acceptable values.');
                $this->container = $value;
                break;
            default:
                throw new \UnexpectedValueException('The given class-variable "'.$name.'"" does not exist.');
        }
    }

    /**
     * Place an element on the navigation bar.
     * @param IElement $element Element to place.
     * @param string|null $alignment Alignment of the element. Use one of the ALIGN_* constants.
     */
    public function place(IElement $element, $alignment=self::ALIGN_DEFAULT){
        $this->appendChild($element);
        if($alignment !== null && $element instanceof IElementMarkupClasses && in_array($alignment, self::$alignments))
            $element->addMarkupClass($alignment);
        else if(!($element instanceof IElementMarkupClasses))
            Error::raiseWarning('The element passed to NavigationBar::place cannot be aligned according to the given value, as the given element does not allow the application of markup classes. (It does not implement IElementMarkupClasses)', 'Something went wrong with aligning some UI elements.');
    }

	/**
	 * Save the navigation bar to its HTML representation.
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth Depth in the document. Used for the number of tabs before each element.
	 * @return String HTML Representation
	 */
	public function save(Document $context, $depth=1) {
        $contentDepth = $depth+1;
		$navigation  = _::line($depth, '<nav '.$this->saveClassAttribute($context).' role="navigation" id="'.$this->id.'">');
        if($this->container !== self::CONTAINER_NONE){
            $navigation .= _::line($depth+1, '<div class="'.$this->container.'">');
            $contentDepth += 1;
        }
		$navigation .= $this->saveHeader($context, $contentDepth);
		$navigation .= $this->saveContent($context, $contentDepth);
        if($this->container !== self::CONTAINER_NONE)
            $navigation .= _::line($depth+1, '</div>');
		$navigation .= _::line($depth, '</nav>');
		return $navigation;
	}

	/**
	 * Saves the header part of the bar.
	 * @param Document $context
	 * @param int $depth
	 * @return string
	 */
	protected function saveHeader(Document $context, $depth=1){
		$header = _::line($depth, '<div class="navbar-header">');

		// Mobile dev. toggle button
		$header .= _::line($depth+1, '<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#'.$this->id.'-collapse">');
		$header .= _::line($depth+2, '<span class="sr-only">Toggle navigation</span>');
		$header .= _::line($depth+2, '<span class="icon-bar"></span>');
		$header .= _::line($depth+2, '<span class="icon-bar"></span>');
		$header .= _::line($depth+2, '<span class="icon-bar"></span>');
		$header .= _::line($depth+1, '</button>');

		// Brand-name/title
		if(!empty($this->brand)){
            if(is_object($this->brand))
                $header .= _::line($depth+1, '<a class="navbar-brand" '.$context->encodeAttribute('href', $this->brandLink).'>'.$this->brand->save($context).'</a>');
            else
                $header .= _::line($depth+1, '<a class="navbar-brand" '.$context->encodeAttribute('href', $this->brandLink).'>'.$context->encodeText($this->brand).'</a>');
        }

		$header .= _::line($depth, '</div>');
		return $header;
	}

	/**
	 * Saves the collapsible content of the bar.
	 * @param Document $context
	 * @param int $depth
	 * @return string
	 */
	protected function saveContent(Document $context, $depth=1){
		$content  = _::line($depth, '<div class="collapse navbar-collapse" id="'.$this->id.'-collapse">');
		$content .= $this->saveChildren($context, $depth+1);
		$content .= _::line($depth, '</div>');
		return $content;
	}

    /**
     * Gets the string representation of all the children in an element
     * @param Document $context The Document in which this collection should be saved.
     * @param int $depth The amount of spacing to prefix lines with.
     * @return String
     */
    public function saveChildren(Document $context, $depth=1){
        $saved = '';
        // Iterate over the children, getting their string representation
        foreach($this->children as $child){
            if($child instanceof Navigation)
                $child->type = Navigation::TYPE_NAVBAR; // navbar-nav
            else if($child instanceof Text)
                $child->addMarkupClass('navbar-text');
            else if($child instanceof Button)
                $child->addMarkupClass('navbar-btn');
            else if($child instanceof Form)
                $child->addMarkupClass('navbar-form');
            $saved .= PHP_EOL.$child->save($context, $depth);
        }
        return $saved;
    }
}
