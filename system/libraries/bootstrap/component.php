<?php
/**
 * Bootstrap Column Collection
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
namespace Quark\Libraries\Bootstrap;
use Quark\Document\baseCollection,
	Quark\Document\IElement,
	Quark\Document\Document;

// Dependencies
\Quark\import(
	'Libraries.Bootstrap.Element',
true);

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Basic IComponent
 *
 * Supplies descendants with instance tracking for Id generation.
 */
interface IComponent extends IElement {
	/**
	 * Get this component's (html) identifier.
	 * @return string
	 */
	public function getId();

	/**
	 * Set the component's (html) identifier.
	 * @param null|string $id Null will cause the element to automatically generate an id, a string will cause the id to be the given string.
	 * @return mixed
	 */
	public function setId($id=null);
}

/**
 * Class Component
 * @package Quark\Libraries\Bootstrap\Components
 */
abstract class Component extends BootstrapElement implements IComponent {
	/**
	 * @var int Number of instances this component has, used for id generation.
	 */
	protected static $instances = 0;

	/**
	 * @var string The identifier.
	 */
	protected $id = null;

	/**
	 * Get this component's (html) identifier.
	 * @return string|null
	 */
	public function getId(){
		return $this->id;
	}

	/**
	 * Set the component's (html) identifier.
	 * @param null|string $id Null will cause the element to automatically generate an id, a string will cause the id to be the given string.
	 * @throws \InvalidArgumentException
	 * @return mixed
	 */
	public function setId($id=null){
		if(is_string($id) && !empty($id))
			$this->id = $id;
		else if($id === null)
			$this->id = $this->generateId();
		else throw new \InvalidArgumentException('Expected argument $id to be string or null.');
	}

	/**
	 * Generate a id.
	 * @return string
	 */
	protected function generateId(){
		$fqn = explode('\\', __CLASS__);
		//return 'component-'.strtolower(end($fqn)).'-'.mt_rand(0, 255).'-'.self::$instances++;
		//return 'component-'.strtolower(end($fqn)).'-'.spl_object_hash($this).'-'.self::$instances++;
		return 'component-'.strtolower(end($fqn)).'-'.self::$instances++;
	}
}
