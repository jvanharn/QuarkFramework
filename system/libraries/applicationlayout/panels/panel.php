<?php
/**
 * Panel Class.
 * 
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 26, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\ApplicationLayout\Panels;
use Quark\Document\Layout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;


/**
 * Panel Class
 * 
 * Represents a panel that is placeable on a Application Grid.
 */
abstract class Panel implements \Quark\Document\StyledElement{
	public function __toString() {
		
	}

	public function save() {
		
	}

	public function saveStyle() {
		
	}
}