<?php
/**
 * Bootstrap Button Component
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		January 6, 2014
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
use Quark\Document\Document;
use Quark\Document\Utils\_;
use Quark\Libraries\Bootstrap\IElementMarkupClasses;
use Quark\Libraries\Bootstrap\baseElementMarkupClasses;
use Quark\Libraries\Bootstrap\baseSingleActivator;
use Quark\Libraries\Bootstrap\IActivatable;
use Quark\Libraries\Bootstrap\ISingleActivator;
use Quark\Libraries\Bootstrap\Component;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple button that can be used in both forms and documents.
 *
 * All (new!)set methods are chainable.
 * Carets are automatically added to buttons linked to dropdowns.
 * @todo allow setting the variation of button (danger, warning, ...)
 */
class Button extends Component implements ISingleActivator, IElementMarkupClasses {
	use baseSingleActivator, baseElementMarkupClasses;

	protected $forcedButton = false;

	protected $icon;

	protected $label;

	protected $href;

	protected $action;

	/**
	 * @param string $label Display text on the button.
	 * @param string|null $href When placed in a document, this will be treated as a href, when $form is set to true it will trigger this "action" (form reset etc), when an IActivator object will activate that object.
	 * @param bool $form Set this to true when this button should treat the href as an action instead of URL.
	 */
	public function __construct($label, $href=null, $form=false){
		$this->setLabel($label);
		if($form === true)
			$this->setFormAction($href);
		else
			$this->setLink($href);

		$this->cssClasses = array('btn', 'btn-default');
	}

	/**
	 * Add an icon in front of the button. (Without the glyphicon- prefix)
	 * @param string $name Name of the glyphicon.
	 * @return Button Self reference for command chaining.
     * @see Glyphicon
	 */
	public function setIcon($name){
		$this->icon = 'glyphicon glyphicon-'.$name;
		return $this;
	}

	/**
	 * Set the new button label.
	 * @param string $label
	 * @return Button Self reference for command chaining.
	 */
	public function setLabel($label){
		$this->label = $label;
		return $this;
	}

	/**
	 * Set what will happen when the activator is toggling.
	 *
	 * Warning: This will unset any already set formActions and Links.
	 * @param IActivatable $object
	 * @return bool
	 */
	public function setActivatable(IActivatable $object){
		if(!empty($object)){
			$this->activatable = $object;
			$this->href = null;
			$this->action = null;
			return true;
		}else return false;
	}

	/**
	 * Set the form action, only useful within a form.
	 *
	 * Warning: This will unset an already set link and will conflict with any set Activatables.
	 * @param string $action
	 * @return $this
	 * @throws \RuntimeException
	 */
	public function setFormAction($action='submit'){
		if(!empty($this->activatable))
			throw new \RuntimeException('An activatable was already set on this button, which cannot be unset.');
		$this->action = $action;
		$this->href = null;
		return $this;
	}

	/**
	 * Set a link.
	 *
	 * Warning: This will unset any already set formActions and will conflict with any set Activatables.
	 * @param string $href Well formed URL. (Please make sure it is sanitized)
	 * @throws \RuntimeException
	 * @return $this
	 */
	public function setLink($href){
		if(!empty($this->activatables))
			throw new \RuntimeException('An activatable was already set on this button, which cannot be unset.');
		$this->href = $href;
		$this->action = null;
		return $this;
	}

	/**
	 * By default this will render as an Anchor whenever possible (<a href...> element), when this is called it will force it to render as a <button>...</button> element.
	 * @return Button Self reference for command chaining.
	 */
	public function forceButtonElement(){
		$this->forcedButton = true;
		return $this;
	}

	/**
	 * Saves the button component.
	 * @param Document $context
	 * @param int $depth
	 * @throws \Quark\Libraries\Bootstrap\BootstrapLayoutException When there are multiple activators present.
	 * @return String
	 */
	public function save(Document $context, $depth = 0) {
		$icon = (is_null($this->icon)?'':'<span class="'.$this->icon.'"></span> ');
		$label = $context->encodeText(trim($this->label));
		$caret = '';
		$href = $this->href ?: '';
		if(isset($this->activatable) && $this->activatable instanceof Dropdown){
			$this->addMarkupClass('dropdown-toggle');
			$caret = ' <span class="caret"></span>';
			if(!is_null($this->activatable->getId()))
				$href = '#'.$this->activatable->getId();
		}

		if(/*$this->hasActivatable() ||*/ $this->action != null || $this->forcedButton){
			$button = _::line($depth, '<button type="button" '.$this->saveClassAttribute($context).' '.$this->saveDataAttributes($context, $this).'>');
			$button .= _::line($depth+1, $icon.$label.$caret);
			$button .= _::line($depth, '</button>');
			return $button;
		}else
			return _::line($depth, '<a '.$context->encodeAttribute('href', $href).' '.$this->saveClassAttribute($context).' '.$this->saveDataAttributes($context, $this).'>'.$icon.$label.$caret.'</a>');
	}
}