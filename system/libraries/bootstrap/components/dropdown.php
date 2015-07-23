<?php
/**
 * Bootstrap Dropdown IComponent
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
use Quark\Document\baseCollection,
	Quark\Document\Document;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\baseActivatable;
use Quark\Document\baseElementMarkupClasses;
use Quark\Libraries\Bootstrap\BootstrapLayoutException;
use Quark\Libraries\Bootstrap\Component;
use Quark\Libraries\Bootstrap\IActivatable;
use Quark\Libraries\Bootstrap\IActivator;
use Quark\Document\IElementMarkupClasses;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple implementation of the Collection Interface.
 */
class Dropdown extends Component implements IActivatable, IElementMarkupClasses {
	use baseActivatable, \Quark\Document\baseElementMarkupClasses;

	/**
	 * @var array Array of arrays with the first always being the display text, and the second being the href and/or dropdown.
	 */
	protected $items = array();

	/**
	 */
	public function __construct(){
		$this->cssClasses = array('dropdown-menu');
	}

	/**
	 * Sets the data-toggle string needed for the dropdowns to be activated.
	 * @param IActivator $object
	 * @return void
	 */
	public function configureActivator(IActivator $object){
		$initial = $object->getDataAttribute('data-toggle');
		if($initial === false || stripos($initial, 'dropdown') === false)
			$object->setDataAttribute('data-toggle', 'dropdown');
		else
			$object->setDataAttribute('data-toggle', $initial.' dropdown');
	}

	/**
	 * Adds an (unclickable) header item to the menu.
	 * @param string $text The text for this menu header.
	 * @return Dropdown The current object, so you can chain calls.
	 */
	public function addHeader($text){
		$this->items[] = (string) $text;
		return $this;
	}

	/**
	 * Add a link to the dropdown menu.
	 * @param string $text Display text.
	 * @param string $link URL to link to, defaults to '#'.
	 * @param bool $disabled Whether or not this item is disabled (The $link wont be used as href attr. but as data-disabled-href).
	 * @return Dropdown The current object, so you can chain calls.
	 * @throws \Quark\Libraries\Bootstrap\BootstrapLayoutException When any of the parameters are malformed or incorrectly typed.
	 */
	public function addLink($text, $link='#', $disabled=false){
		if(!empty($text) && is_string($text) && is_string($link))
			$this->items[] = array($text, $link, $disabled);
		else throw new BootstrapLayoutException('Malformed parameters for bootstrap Dropdown::addLink function.');
		return $this;
	}

	/**
	 * Add a menu item divider.
	 * @return Dropdown The current object, so you can chain calls.
	 */
	public function addDivider(){
		$this->items[] = null;
		return $this;
	}

	// Bootstrap doesn't natively allow this, so we will only be adding this in later versions.
	///**
	// * Add a submenu to this dropdown.
	// * @param $text
	// * @param Dropdown $object
	// */
	//public function addDropdown($text, Dropdown $object){
	//	if(!empty($text) && is_string($text) && !empty($object))
	//		$this->items[] = array($text, $object);
	//	else throw new BootstrapLayoutException('Malformed parameters for bootstrap Dropdown::addDropdown function.');
	//}

	/**
	 * Saves the drop down component.
	 * @param Document $context
	 * @param int $depth
	 * @throws \Quark\Libraries\Bootstrap\BootstrapLayoutException When there are multiple activators present.
	 * @return String
	 */
	public function save(Document $context, $depth = 0) {
		$id = ($this->getId() !==null ? ' '.$context->encodeAttribute('id', $this->getId()) : '').' ';
		$dropdown = _::line($depth, '<ul'.$id.$this->saveClassAttribute($context).' role="menu">');
		foreach($this->items as $item){
			if(is_null($item))
				$dropdown .= _::line($depth+1, '<li role="presentation" class="divider"></li>');
			else if(is_string($item))
				$dropdown .= _::line($depth+1, '<li role="presentation" class="dropdown-header">'.$context->encodeText($item).'</li>');
			else if(is_object($item[1]))
				$dropdown .= ''; // Not yet implemented.
			else if($item[2] == true)
				$dropdown .= _::line($depth+1, '<li role="presentation" class="disabled"><a role="menuitem" tabindex="-1" '.$context->encodeAttribute('data-disabled-href', $item[1]).'>'.$context->encodeText($item[0]).'</a></li>');
			else
				$dropdown .= _::line($depth+1, '<li role="presentation"><a role="menuitem" tabindex="-1" '.$context->encodeAttribute('href', $item[1]).'>'.$context->encodeText($item[0]).'</a></li>');
		}
		$dropdown .= _::line($depth, '</ul>');
		return $dropdown;
	}

	/**
	 * Helper to create a working dropdown button inside a button group with activator et-al.
	 * @param Dropdown &$menu Variable that will be populated with the Dropdown menu object, so you can add links etc.
	 * @param string $label Label of the activating button.
	 * @param string $icon Name of the optional icon to include.
	 * @param string $size Size of the button.
	 * @param bool $unfoldUpwards Makes the menu drop upwards instead of down.
	 * @return ButtonGroup
	 */
	public static function create(&$menu, $label, $icon=null, $size=ButtonGroup::BTN_GROUP_MD, $unfoldUpwards=false){
		$activator = new Button($label);
		if(!empty($icon))
			$activator->setIcon($icon);

		$menu = new Dropdown();
		$activator->setActivatable($menu);

		$group = new ButtonGroup($size);
		if($unfoldUpwards === true)
			$group->addMarkupClass('dropup');
		$group->addButton($activator);
		$group->addDropdown($menu);
		return $group;
	}
}