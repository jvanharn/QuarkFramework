<?php
/**
 * SQLite Database Driver - Query Builder
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		16 July 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Database\Driver;
use Quark\Database\SQLQuery;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

\Quark\import('Framework.Database.Query');

/**
 * SQLite Query Builder
 */
class SQLiteQuery extends SQLQuery {
	/**
	 * All statements with their allowed clauses, call order and any other settings.
	 * @var array
	 */
	protected static $statements = array(
		// 'method' =>array('default value' or null if none, (array) with possible other values, (array) allowed statements with this method, (bool) whether or not to allow k=>v parameters)
		'SELECT' => [
			'default'	=> 'ALL',
			'empty'		=> false,
			'keyvalue'	=> false,
			'multiple'	=> true,
			'clauses'	=> ['FROM', 'JOIN', 'LEFT JOIN', 'LEFT OUTER JOIN', 'INNER JOIN', 'CROSS JOIN', 'USING', 'ON', 'WHERE', 'GROUP BY', 'HAVING', 'ORDER BY', 'LIMIT'],
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
			'type'		=> self::PARAM_EXPRESSION,
			'subquery'	=> false // @todo maybe allow this here and in set.
		],

		// JOINs
		'JOIN' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> true
		],
		'LEFT JOIN' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> true
		],
		'LEFT OUTER JOIN' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> true
		],
		'INNER JOIN' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> true
		],
		'CROSS JOIN' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> true
		],

		'USING' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> false
		],
		'ON' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_EXPRESSION,
			'subquery'	=> false
		],
	);
	
	/**
	 * All statement aliases
	 * @var array
	 */
	protected static $aliases = array('DELETE' => 'DELETE FROM');
}