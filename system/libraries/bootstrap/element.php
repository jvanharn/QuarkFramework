<?php
/**
 * Basic Bootstrap Element
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 23, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\Bootstrap;
use Quark\Document\baseCollection,
	Quark\Document\IElement;

// Dependencies
\Quark\import(
	'Framework.Document.Element'
);

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class baseBootstrapElement
 * Provides helper function to nicely print tabs/depth.
 * @package Quark\Libraries\Bootstrap
 */
abstract class BootstrapElement implements IElement {
}