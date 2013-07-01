<?php
/**
 * Brings Comparable's to PHP
 * 
 * @package		Quark-Framework
 * @version		$Id: comparable.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		19 december 2012
 * @copyright	Copyright (C) 2012 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012 Jeffrey van Harn
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
namespace Quark\Util\Type;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Comparable Interface
 * 
 * Helps you compare an object to another. Obviously borrowed from Java.
 */
interface Comparable {
	/**
	 * Compares two objects to another.
	 * @param \Quark\Util\Type\Comparable $subject Other comparable to compare to.
	 * @return Returns -1 if the other object is less, 0 if it is equal and 1 if it is higher.
	 */
	public function compareTo(Comparable $subject);
}