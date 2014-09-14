<?php
/**
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define namespace
namespace Quark\Protocols\HTTP;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class UnexpectedRequestEndException
 *
 * Thrown when the HTTP classes cant read from a http socket, or there seems to be an incomplete or unexpected end to the request.
 * @package Quark\Protocols\HTTP
 */
class UnexpectedRequestEndException extends \RuntimeException {}

/**
 * Class SocketReadException
 *
 * Thrown when the HTTP classes cant read from a socket.
 * @package Quark\Protocols\HTTP
 */
class SocketReadException extends \RuntimeException {}

/**
 * Class HeaderException
 *
 * Thrown when a header was not found or a header was not able to be parsed.
 * @package Quark\Protocols\HTTP
 */
class HeaderException extends \RuntimeException {}