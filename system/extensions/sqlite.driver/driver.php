<?php
/**
 * SQLite Database Driver - Connector/Driver
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		16 July 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Database\Driver;

// Prevent individual file access
use Quark\Database\DatabaseException;
use Quark\Database\Driver;

if(!defined('DIR_BASE')) exit;

/**
 * MySQL Database Driver
 */
class SQLiteDriver implements Driver {
	/**
	 * PDO Connection Object
	 * @var \PDO
	 */
	protected $pdo;
	
	/**
	 * Connect to the database
	 * @param array $settings Properly formatted connection array.
	 * @throws DatabaseException When something was wrong with the connection details.
	 * @throws \BadMethodCallException When something was wrong with the settings formatting.
	 */
	public function __construct(array $settings) {
		// Check settings array
		if(!self::checkSettings($settings))
			throw new \BadMethodCallException('Settings incorrectly formatted see the driver info for the required attributes, and make sure the hostname and database fields are non-empty.');
		
		// Check if MySQL is available
		if(!self::driverAvailable())
			throw new DatabaseException('Required "MySQL" PDO Driver required for this database driver, was not installed on this server. Please do so to use this specific driver, or use another driver that does have it\'s dependency\'s installed. Drivers that you /can/ use include, but are not limited to: '.implode(', ', \PDO::getAvailableDrivers()).'.');
		
		// Create the pdo object
		try {
			$this->pdo = new \PDO('mysql:host='.$settings['hostname'].';dbname='.$settings['database'], $settings['username'], $settings['password']);
		}catch(\PDOException $e){
			throw new DatabaseException('Could not connect to the database.', E_USER_ERROR, $e);
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
	 * @throws DatabaseException When something was wrong with the query/statement.
	 * @return boolean|integer Boolean false on failure or the number of affected rows on succes. Beware that the number of rows on succes can also be evaluated as an boolean, try to use the === operator to be sure.
	 */
	public function execute($statement) {
		if(is_string($statement) || $statement instanceof MySQLQuery){
			return $this->pdo->exec((string) $statement);
		}else throw new DatabaseException('Invalid statement type given. MySQL Driver can only execute SQL Query strings and MySQL Query\'s.');
	}
	
	/**
	 * MySQL Query to query the database with for results.
	 * @param string|\Quark\Database\Driver\SQLite $statement Statement to query with.
	 * @param boolean $cursor Whether or not to enable a cursor (When possible) for this query.
	 * @throws DatabaseException When something was wrong with the query/statement.
	 * @return \Quark\Database\Driver\SQLiteResult
	 */
	public function query($statement, $cursor=false) {
		if(is_bool($cursor)){
			if(is_string($statement) || $statement instanceof MySQLQuery){
				if($cursor == true)
					return new SQLiteResult($this->pdo->query((string) $statement), false);
				else {
					$stmt = $this->pdo->prepare((string) $statement, [\PDO::ATTR_CURSOR => \PDO::CURSOR_SCROLL]);
					$stmt->execute();
					return new SQLiteResult($stmt, true);
				}
			}else throw new DatabaseException('Invalid statement type given. MySQL Driver can only execute SQL Query strings and MySQL Query\'s.');
		}else throw new DatabaseException('$cursor should be a boolean, but got "'.gettype($cursor).'".');
	}

	/**
	 * Get a prepared statement object.
	 * @param mixed $statement (SQL) Query statement for the database.
	 * @param boolean $cursor Whether or not to request a scrollable result set.
	 * @throws DatabaseException When something was wrong with the query/statement.
	 * @throws \InvalidArgumentException When something was wrong with the query/statement.
	 * @return \Quark\Database\Statement
	 */
	public function prepare($statement, $cursor = false) {
		if(is_string($statement) && is_bool($cursor))
			return new SQLiteStatement(
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
		return in_array('sqlite', $drivers);
	}
	
	/**
	 * Test if settings given can connect to a database.
	 * @param array $settings Settings array formatted as described in the getSettings() method.
	 * @return boolean|string Error message or true.
	 */
	public static function testSettings(array $settings) {
		// Check settings array
		if(!self::checkSettings($settings))
			return 'Settings incorrectly formatted see the driver info for the required attributes, and make sure the hostname and database fields are non-empty.';
		
		// Check if MySQL is available
		if(!self::driverAvailable())
			return 'Required "SQLite" PDO Driver required for this database driver, was not installed on this server. Please do so to use this specific driver, or use another driver that does have it\'s dependency\'s installed. Drivers that you /can/ use include, but are not limited to: '.implode(', ', \PDO::getAvailableDrivers()).'.';
		
		// Create the pdo object
		try {
			$pdo = @(new \PDO('sqlite:'.$settings['database']));
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
		return (isset($settings['database']) && !empty($settings['database']));
	}
	
	/**
	 * Get the classname of the query class provided by this driver.
	 * @return string Fully Qualified Classname
	 */
	public static function getQueryClassname() {
		return '\\Quark\\Database\\Driver\\SQLiteQuery';
	}
}