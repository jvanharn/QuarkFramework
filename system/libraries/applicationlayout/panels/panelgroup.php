<?php
/**
 * Group of panels.
 * 
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 27, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\ApplicationLayout\Panels;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Panel Group Class
 * 
 * This class represents a group of panels where panels can be added and removed
 * from. The extending classes will determine the behaviour of those panels when
 * displaying in the area/space that is available in the cell, another panel.
 */
abstract class PanelGroup extends Panel {
	
}