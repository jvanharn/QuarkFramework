<?php
/**
 * Dynamic Layout implementation that can dynamically load the layout structure
 * from a json file.
 * 
 * @package		Quark-Framework
 * @version		$Id: dynamiclayout.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		October 19, 2012
 * @copyright	Copyright (C) 2011 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2011 Jeffrey van Harn
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
namespace Quark\Document\Layout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * 
 */
class GridLayout extends Layout{
	public function save() {
		
	}
}