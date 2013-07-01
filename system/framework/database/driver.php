<?php
/**
 * Database Driver Specification
 * 
 * @package		Quark-Framework
 * @version		$Id: driver.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		25 december 2012
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
namespace Quark\Database;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Database Driver Interface
 */
interface Driver {
	/**
	 * @param array $settings Settings to initialize the driver with.
	 */
	public function __construct(array $settings);
	
	/**
	 * Execute a statement with the Database
	 * @param mixed $statement (SQL) Query statement for the database
	 * @return boolean|integer Number of affected rows or false on failure.
	 * @throws DatabaseException When something was wrong with the query/statement.
	 */
	public function execute($statement);
	
	/**
	 * Query the Database
	 * @param mixed $statement (SQL) Query statement for the database
	 * @param boolean $cursor Whether or not to request a scrollable result set.
	 * @return \Quark\Database\Result Query result.
	 * @throws DatabaseException When something was wrong with the query/statement.
	 */
	public function query($statement, $cursor=false);
	
	/**
	 * Get a prepared statement object.
	 * @param mixed $statement (SQL) Query statement for the database.
	 * @param boolean $cursor Whether or not to request a scrollable result set.
	 * @return \Quark\Database\Statement
	 * @throws DatabaseException When something was wrong with the query/statement.
	 */
	public function prepare($statement, $cursor=false);
	
	/**
	 * Quote an expression or value for use in a statement.
	 * @param mixed $expression Expression to properly format.
	 * @return mixed A expression that may be safely used in a statement.
	 */
	public function quote($expression);
	
	/**
	 * Get the raw connection resource of the current driver.
	 * 
	 * Should return the connection resource should you choose to expose it,
	 * otherwise return null.
	 * @return mixed|null
	 */
	public function getRawConnection();
	
	/**
	 * Whether or not any dependencies for this driver, like PHP Extensions are loaded, and can be used to connect to a database.
	 * @return boolean
	 */
	public static function driverAvailable();
	
	/**
	 * Check whether the given settings can connect to the database.
	 * @param array $settings
	 * @return boolean
	 */
	public static function testSettings(array $settings);
	
	/**
	 * Get the classname of the query class provided by this driver.
	 * @return string Fully Qualified Classname
	 */
	public static function getQueryClassname();
}