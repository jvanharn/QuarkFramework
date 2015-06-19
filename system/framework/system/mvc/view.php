<?php
/**
 * MVC View Implementation
 * 
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 11, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\MVC;
use Quark\Protocols\HTTP\IMutableResponse;
use Quark\System\Router\IRoutableRequest;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * MVC View Implementation.
 */
abstract class View {
	/**
	 * Should accept the return value of a controller method and display it.
	 * @param mixed $returnValue The value to display for the controller.
	 * @param IRoutableRequest $request
	 * @param IMutableResponse $response The object to write the response to.
	 * @return mixed
	 */
	abstract public function display($returnValue, IRoutableRequest $request, IMutableResponse $response);
}