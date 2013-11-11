<?php
/**
 * Bootstrap Column Collection
 *
 * @package		Quark-Framework
 * @version		$Id: collection.php 69 2013-01-24 15:14:45Z Jeffrey $
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
	Quark\Document\Document,
	Quark\Document\Element;
use Quark\Libraries\Bootstrap\baseBootstrapElement;
use Quark\Libraries\Bootstrap\BootstrapElement;
use Quark\Libraries\Bootstrap\BootstrapLayout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple implementation of the Collection Interface.
 */
class Dropdown implements BootstrapElement {
	use baseBootstrapElement;

	/**
	 * @var int Number of initiated navbar objects this session.
	 */
	private static $instances = 0;

	/**
	 * @var string Identifier of the navigation bar.
	 */
	protected $id;

	/**
	 * @var string The title or brand of the nav-bar.
	 */
	protected $brand;

	/**
	 * @var array The (extra) classes of the element.
	 */
	protected $classes;

	/**
	 * @var NavigationBarElement[] Contains the elements that will reside in the collapsible area.
	 */
	protected $elements = array();

	/**
	 * @param string $brand
	 * @param string $id
	 * @param array $classes Extra classes for the row.
	 * @throws \InvalidArgumentException When a parameter's type is invalid.
	 */
	public function __construct($brand=null, $id=null, array $classes=array()){
		if(!empty($brand))
			$this->setBrand($brand);

		if(empty($id))
			$this->id = 'page-navbar-'.mt_rand(0, 255).'-'.self::$instances;
		else $this->id = $id;

		if(is_array($classes))
			$this->classes = $classes;
		else throw new \InvalidArgumentException('Param $classes should be of type array.');

		self::$instances++;
	}

	/**
	 * Set the bars brand-name/title.
	 * @param string|int $text
	 */
	public function setBrand($text){
		$this->brand = (string) $text;
	}

	/**
	 * Adds a navigation bar element to the collapsible area of the bar.
	 * @param NavigationBarElement $element
	 */
	public function addContent(NavigationBarElement $element){
		$this->elements[] = $element;
	}

	/**
	 * Save the navigation bar to its HTML representation.
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @param int $depth Depth in the document. Used for the number of tabs before each element.
	 * @return String HTML Representation
	 */
	public function save(Document $context, $depth=1) {
		$navigation  = self::line($depth, '<nav class="navbar navbar-default" role="navigation" id="'.$this->id.'">');
		$navigation .= $this->saveHeader($context, $depth+1);
		$navigation .= $this->saveContent($context, $depth+1);
		$navigation .= self::line($depth, '</nav>');
		return $navigation;
	}

	/**
	 * Saves the header part of the bar.
	 * @param Document $context
	 * @param int $depth
	 * @return string
	 */
	protected function saveHeader(Document $context, $depth=1){
		$header = self::line($depth, '<div class="navbar-header">');

		// Mobile dev. toggle button
		$header .= self::line($depth+1, '<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#'.$this->id.'-collapse">');
		$header .= self::line($depth+2, '<span class="sr-only">Toggle navigation</span>');
		$header .= self::line($depth+2, '<span class="icon-bar"></span>');
		$header .= self::line($depth+2, '<span class="icon-bar"></span>');
		$header .= self::line($depth+2, '<span class="icon-bar"></span>');
		$header .= self::line($depth+1, '</button>');

		// Brand-name/title
		if(!empty($this->brand))
			$header .= self::line($depth+1, '<a class="navbar-brand" href="#">'.$context->encodeText($this->brand).'</a>');

		$header .= self::line($depth, '</div>');
		return $header;
	}

	/**
	 * Saves the collapsible content of the bar.
	 * @param Document $context
	 * @param int $depth
	 */
	protected function saveContent(Document $context, $depth=1){
		$content  = self::line($depth, '<div class="collapse navbar-collapse" id="'.$this->id.'-collapse">');
		foreach($this->elements as $element){
			$content .= $element->save($context, $depth+1);
		}
		$content .= self::line($depth, '</div>');
		return $content;
	}
}