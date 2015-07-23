<?php
/**
 * MySQL Database Driver - Query Builder
 * 
 * @package		Quark-Framework
 * @version		$Id: query.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		26 december 2012
 * @copyright	Copyright (C) 2011-2012 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2011-2012 Jeffrey van Harn
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
namespace Quark\Database\Driver;

// Prevent individual file access
use Quark\Database\SQLQuery;

if(!defined('DIR_BASE')) exit;

\Quark\import('Framework.Database.Query');

/**
 * MySQL Query Builder
 */
class MySQLQuery extends SQLQuery {
	/**
	 * All statements with their allowed clauses, call order and any other settings.
	 * @var array
	 */
	protected static $statements = array(
		// 'method' =>array('default value' or null if none, (array) with possible other values, (array) allowed statements with this method, (bool) whether or not to allow k=>v parameters)
		'SELECT' => [
			'default'	=> '*',
			'empty'		=> false,
			'keyvalue'	=> false,
			'multiple'	=> true,
			'clauses'	=> ['FROM', 'WHERE', 'GROUP BY', 'HAVING', 'ORDER BY', 'LIMIT'],
			'required'	=> ['FROM'],
			'resultset'	=> true
		],
		'INSERT' => [
			'default'	=> null,
			'empty'		=> true,
			'keyvalue'	=> false,
			'multiple'	=> false,
			'clauses'	=> ['INTO', 'VALUES'],
			'required'	=> ['INTO', 'VALUES'],
			'resultset'	=> false
		],
		'UPDATE' => [
			'default'	=> null,
			'empty'		=> false,
			'keyvalue'	=> true,
			'multiple'	=> false,
			'clauses'	=> ['SET', 'WHERE', 'ORDER BY', 'LIMIT'],
			'required'	=> ['SET'],
			'resultset'	=> false
		],
		'DELETE' => [
			'default'	=> null,
			'empty'		=> true,
			'keyvalue'	=> false,
			'multiple'	=> false,
			'clauses'	=> ['FROM', 'WHERE', 'ORDER BY', 'LIMIT'],
			'required'	=> [],
			'resultset'	=> false
		]
	);
	
	/**
	 * Statement clauses and their properties
	 * @var array
	 */
	protected static $clauses = array(
		'FROM' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> true
		],
		'INTO' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> true
		],
		'WHERE' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_PREDICTATE,
			'subquery'	=> false
		],
		'GROUP BY' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> false
		],
		'HAVING' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_PREDICTATE,
			'subquery'	=> false
		],
		'ORDER BY' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> false
		],
		'LIMIT' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_EXPRESSION,
			'subquery'	=> false
		],
		'SET' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_ASSIGNMENT,
			'subquery'	=> false
		],
		'VALUES' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_ASSIGNMENT,
			'subquery'	=> false // @todo maybe allow this here and in set.
		],
	);
	
	/**
	 * All statement aliases
	 * @var array
	 */
	protected static $aliases = array();
}