<?php
/**
 * Quark application server socket exception.
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 2, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Application\Server;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Quark (Server) Socket Exception
 * @package Quark\System\Application\Server
 */
class SocketException extends \RuntimeException {
	const CANT_CREATE_SOCKET = 1;
	const CANT_BIND_SOCKET = 2;
	const CANT_LISTEN = 3;
	const CANT_ACCEPT = 4;

	public $messages = array(
		self::CANT_CREATE_SOCKET => 'Can\'t create socket on server %s: "%s"',
		self::CANT_BIND_SOCKET => 'Can\'t bind socket on server %s: "%s"',
		self::CANT_LISTEN => 'Can\'t listen on server %s: "%s"',
		self::CANT_ACCEPT => 'Can\'t accept connections on server %s: "%s"',
	);

	/**
	 * @param IServer $server
	 * @param string $code
	 * @param string $originalMessage
	 */
	public function __construct(IServer $server, $code, $originalMessage='') {
		try {
			$message = sprintf($this->messages[$code], $server->address.$server->port, $originalMessage);
		}catch(\Exception $e){
			$message = $this->messages[$code].' :: '.$originalMessage;
		}

		parent::__construct($message, E_ERROR);
	}
}