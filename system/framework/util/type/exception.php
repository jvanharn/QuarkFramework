<?php
/**
 * Type Exception
 * 
 * @package		Quark-Framework
 * @version		$Id: exception.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		21 december 2012
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
use Quark\Document\Document;
use Quark\Document\Layout\BasicLayout;
use Quark\Document\Utils\Literal;
use Quark\Protocols\HTTP\IMutableResponse;

if(!defined('DIR_BASE')) exit;

/**
 * Invalid Argument Type Exception
 */
class InvalidArgumentTypeException extends \RuntimeException {
	/**
	 * @param string $name
	 * @param string $expectedType
	 * @param mixed $value
	 */
	public function __construct($name, $expectedType, $value){
		parent::__construct('The argument $'.$name.' was expected to be of type "'.$expectedType.'" but found '.(empty($value)?'(empty) ':'').'"'.gettype($value).'" ('.$value.').', E_ERROR);
		
		// Change the line number and file tot the previously called number
		$trace = $this->getTrace();
		$this->line = $trace[1]['line'];
		$this->file = $trace[1]['file'];
	}
}

class_alias('\Quark\Util\Type\InvalidArgumentTypeException', '\InvalidArgumentTypeException');

/**
 * Http Exception
 *
 * Throw this in an controller or route to have the server send an error page.
 * @package Quark\Util\Type
 */
class HttpException extends \RuntimeException {
	/**
	 * Create a http exception with the given message
	 * @param string $httpCode
	 * @param string $message
	 * @param \Exception $previous
	 */
	public function __construct($httpCode, $message, $previous=null){
		parent::__construct($message, $httpCode, $previous);
	}

	/**
	 * Write this HttpException directly to the given $response message.
	 * @param IMutableResponse $response
	 * @param string $mimeType The mime-type to try and respond in. (E.g. application/json, text/html. text/plain..)
	 * @return void
	 */
	public function writeTo(IMutableResponse $response, $mimeType='text/html'){
		$response->setStatus($this->code);
		switch($mimeType){
			case 'application/json':
			case 'application/jsonp':
			case 'application/javascript':
				$response->setHeader('Content-Type', 'application/json');
				$response->setBody(json_encode(array(
					'code' => $this->code,
					'message' => $this->message
				)));
				break;

			case 'text/html':
				$document = Document::createInstance(new BasicLayout());
				$document->place(new Literal([
					'html' =>
						'<div style="margin:40px auto;max-width:700px;background:#FFFFFF;font-family: Roboto, Noto, Lato, \'Open Sans\', sans-serif;box-shadow:0 2px 5px rgba(0,0,0,0.26)">'.
							'<h1 style="background:#F44336;color: white;padding: 5px 0 5px 14px;margin:0 0 3px 0;">'.
								$response->getStatusCode().': '.$response->getStatusText().
							'</h1>'.
							'<p style="padding: 10px;line-height: 1.4em">'.$this->message.'</p>'.
						'</div>'
				]));
				$document->toResponse($response);
				break;

			case 'text/plain':
			default:
				$response->setBody(
					PHP_EOL.
					'Error with code '.$this->code.' occurred with message:'.PHP_EOL.
					"\t".$this->message.PHP_EOL
				);
				break;
		}
	}
}