<?php
/**
 * Database Prepared Statement Specification
 * 
 * @package		Quark-Framework
 * @version		$Id: statement.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		28 december 2012
 * @copyright	Copyright (C) 2012-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012-2013 Jeffrey van Harn
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
namespace Quark\Database;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Prepared Statement Interface
 */
interface Statement {
	/**
	 * Boolean Column Type
	 */
	const PARAM_BOOL = 1;
	
	/**
	 * Integer Column Type
	 * 
	 * NOTE: Maximum of PHP_INT_MAX. (Depending on driver)
	 */
	const PARAM_INTEGER = 2;
	
	/**
	 * Float Column Type
	 * 
	 * NOTE: With maximal precision of a php float. (Depending on driver)
	 */
	const PARAM_FLOAT = 3;
	
	/**
	 * String Column Type
	 */
	const PARAM_STRING = 4;
	
	/**
	 * Unix Timestamp Column Type
	 * 
	 * Number of seconds since the unix epoch. Should automatically get converted for databases that do not support this type.
	 * NOTE: Ceases to work on 32-bit systems after 19th of January 2038.
	 */
	const PARAM_UNIXTIME = 5;
	
	/**
	 * Date Column Type
	 */
	const PARAM_DATE = 6;
	
	/**
	 * Date/Time Column Type
	 */
	const PARAM_DATETIME = 7;
	
	/**
	 * Large Object Column Type
	 */
	const PARAM_LOB = 8;
	
	/**
	 * Bind a value to a name.
	 * @param string|integer $name Use numbers for "?"-marks and names for the ":name" notation.
	 * @param mixed $value Value for the column.
	 * @param integer $type A PARAM_* Constant or null for detection.
	 * @return \Quark\Database\Statement Returns a reference to itself for chaining.
	 */
	public function bind($name, $value, $type=null);
	
	/**
	 * Query the database with the prepared statement
	 * @param array $params The bound parameter array should be in the form of k=>v if using named notation (:name) and numerically indexed when using questionmark notation (?).
	 * @return \Quark\Database\Result Result of the query.
	 * @see \Quark\Database\Database::query
	 */
	public function query(array $params=array());
	
	/**
	 * Execute the prepared statement
	 * @param array $params The bound parameter array should be in the form of k=>v if using named notation (:name) and numerically indexed when using questionmark notation (?).
	 * @return boolean|integer
	 * @see \Quark\Database\Database::execute
	 */
	public function execute(array $params=array());
}