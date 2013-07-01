<?php
/**
 * MySQL Database Driver - Connector/Driver
 * 
 * @package		Quark-Framework
 * @version		$Id: driver.php 69 2013-01-24 15:14:45Z Jeffrey $
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
if(!defined('DIR_BASE')) exit;

/**
 * MySQL Database Driver
 */
class MySQLDriver implements \Quark\Database\Driver {
	/**
	 * PDO MySQL Connection
	 * @var \PDO
	 */
	protected $pdo;
	
	/**
	 * Connect to the database
	 * @param array $settings Properly formatted connection array.
	 */
	public function __construct(array $settings) {
		// Check settings array
		if(!self::checkSettings($settings))
			throw new \BadMethodCallException('Settings incorrectly formatted see the driver info for the required attributes, and make sure the hostname and database fields are non-empty.');
		
		// Check if MySQL is available
		if(!self::driverAvailable())
			throw new \Quark\Database\DatabaseException('Required "MySQL" PDO Driver required for this database driver, was not installed on this server. Please do so to use this specific driver, or use another driver that does have it\'s dependency\'s installed. Drivers that you /can/ use include, but are not limited to: '.implode(', ', \PDO::getAvailableDrivers()).'.');
		
		// Create the pdo object
		try {
			$this->pdo = new \PDO('mysql:host='.$settings['hostname'].';dbname='.$settings['database'], $settings['username'], $settings['password']);
		}catch(\PDOException $e){
			throw new \Quark\Database\DatabaseException('Could not connect to the database.', E_USER_ERROR, $e);
		}
	}
	
	/**
	 * Closes the connection.
	 */
	public function __destruct() {
		$this->pdo = null;
	}
	
	/**
	 * MySQL Query to Execute
	 * @param string|\Quark\Database\Driver\MySQLQuery $statement Statement to execute.
	 * @return boolean|integer Boolean false on failure or the number of affected rows on succes. Beware that the number of rows on succes can also be evaluated as an boolean, try to use the === operator to be sure.
	 */
	public function execute($statement) {
		if(is_string($statement) || $statement instanceof MySQLQuery){
			return $this->pdo->exec((string) $statement);
		}else throw new \Quark\Database\DatabaseException('Invalid statement type given. MySQL Driver can only execute SQL Query strings and MySQL Query\'s.');
	}
	
	/**
	 * MySQL Query to query the database with for results.
	 * @param string|\Quark\Database\Driver\MySQLQuery $statement Statement to query with.
	 * @param boolean $cursor Whether or not to enable a cursor (When possible) for this query.
	 * @return \Quark\Database\Driver\MySQLResult Description
	 */
	public function query($statement, $cursor=false) {
		if(is_bool($cursor)){
			if(is_string($statement) || $statement instanceof MySQLQuery){
				if($cursor == true)
					return new MySQLResult($this->pdo->query((string) $statement), false);
				else {
					$stmt = $this->pdo->prepare((string) $statement, [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]);
					$stmt->execute();
					return new MySQLResult($stmt, true);
				}
			}else throw new \Quark\Database\DatabaseException('Invalid statement type given. MySQL Driver can only execute SQL Query strings and MySQL Query\'s.');
		}else throw new \Quark\Database\DatabaseException('$cursor should be a boolean, but got "'.gettype($cursor).'".');
	}
	
	/**
	 * Get a prepared statement object.
	 * @param mixed $statement (SQL) Query statement for the database.
	 * @param boolean $cursor Whether or not to request a scrollable result set.
	 * @return \Quark\Database\Statement
	 * @throws DatabaseException When something was wrong with the query/statement.
	 */
	public function prepare($statement, $cursor = false) {
		if(is_string($statement) && is_bool($cursor))
			return new MySQLStatement(
				$this->pdo->prepare(
					$statement,
					[\PDO::ATTR_CURSOR => ($cursor?\PDO::CURSOR_SCROLL:\PDO::CURSOR_FWDONLY)]
				),
				$cursor
			);
		else
			throw new \InvalidArgumentException('Invalid statement type (should be string) or invalid cursor type (Should be boolean).');
	}
	
	/**
	 * Quote an expression or value for use in a statement.
	 * @param mixed $expression Expression to properly format.
	 * @return mixed A expression that may be safely used in a statement.
	 */
	public function quote($expression) {
		return $this->pdo->quote($expression);
	}
	
	/**
	 * Exposes the PDO object.
	 * @return \PDO
	 */
	public function getRawConnection() {
		return $this->pdo;
	}
	
	/**
	 * Whether or not the MySQL Driver is available/can be used.
	 * @return boolean
	 */
	public static function driverAvailable() {
		$drivers = \PDO::getAvailableDrivers();
		return in_array('mysql', $drivers);
	}
	
	/**
	 * Test if settings given can connectto a database.
	 * @param array $settings Settings aray formatted as described in the getSettings() method.
	 * @return boolean|string Error message or true.
	 */
	public static function testSettings(array $settings) {
		// Check settings array
		if(!self::checkSettings($settings))
			return 'Settings incorrectly formatted see the driver info for the required attributes, and make sure the hostname and database fields are non-empty.';
		
		// Check if MySQL is available
		if(!self::driverAvailable())
			return 'Required "MySQL" PDO Driver required for this database driver, was not installed on this server. Please do so to use this specific driver, or use another driver that does have it\'s dependency\'s installed. Drivers that you /can/ use include, but are not limited to: '.implode(', ', \PDO::getAvailableDrivers()).'.';
		
		// Create the pdo object
		try {
			$pdo = @(new \PDO('mysql:host='.$settings['hostname'].';dbname='.$settings['database'], $settings['username'], $settings['password']));
		}catch(\PDOException $e){
			return 'Could not connect to the database, PDO Driver gave the error message "'.$e->getMessage().'".';
		}
		
		// Check if it isn't null
		if($pdo == null)
			return 'I could not connect to the database.';
		
		// Success
		return true;
	}
	
	/**
	 * Checks whether or not the given settings array is correctly formatted.
	 * @param array $settings
	 * @return boolean
	 */
	public static function checkSettings(array $settings) {
		return (isset($settings['hostname']) && !empty($settings['hostname']) && isset($settings['database']) && !empty($settings['database']) && isset($settings['username']) && isset($settings['password']));
	}
	
	/**
	 * Get the classname of the query class provided by this driver.
	 * @return string Fully Qualified Classname
	 */
	public static function getQueryClassname() {
		return '\\Quark\\Database\\Driver\\MySQLQuery';
	}
}