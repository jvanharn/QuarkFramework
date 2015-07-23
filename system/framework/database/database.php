<?php
/**
 * Database Management
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		March 4, 2012
 * @copyright	Copyright (C) 2012-2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Database;
use Quark\Util\Multiton;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Import dependencies
\Quark\import(
	'Framework.Database.Result',
	'Framework.Database.Statement',
true);

/**
 * Database management class.
 *
 * Represents the connection to a database, and makes it possible to interact with it, regardless of what backend is used.
 * @method Query select() select(string $columns)
 * @method Query update() update(string $table)
 * @method Query insert() insert()
 * @method Query deleteFrom() deleteFrom(string $table)
 */
class Database implements Multiton{
	/**
	 * Contains the current Database Instance
	 * @var Array
	 */
	private static $_instances = array();
	
	/**
	 * Name of current instance
	 * @var string
	 */
	private $name = 'default';
	
	/**
	 * Current database driver
	 * @var \Quark\Database\Driver
	 */
	private $driver;
	
	/**
	 * Query class name.
	 * @var string
	 */
	private $queryClass;

	/**
	 * Start the database connection
	 * @param string $name Connection name.
	 * @param Driver $driver Driver to use for this database instance.
	 */
	private function __construct($name, Driver $driver){
		$this->name = (string) $name;
		$this->driver = $driver;
		$this->queryClass = $driver->getQueryClassname();
	}

	/**
	 * Query the Database
	 * @param mixed $statement (SQL) Query statement for the database
	 * @param boolean $cursor Whether or not to request a scrollable result set.
	 * @return \Quark\Database\Result Query result.
	 * @throws DatabaseException When something was wrong with the query/statement.
	 */
	public function query($statement, $cursor=false){
		if(!empty($statement))
			return $this->driver->query($statement, $cursor);
		else throw new DatabaseException('Empty statement given to database query.');
	}
	
	/**
	 * Execute a statement with the Database
	 * @param mixed $statement (SQL) Query statement for the database
	 * @return boolean|integer Number of affected rows or false on failure.
	 * @throws DatabaseException When something was wrong with the query/statement.
	 */
	public function execute($statement){
		if(!empty($statement))
			return $this->driver->execute($statement);
		else throw new DatabaseException('Empty statement given to database execute.');
	}
	
	/**
	 * Get a prepared statement object.
	 * @param mixed $statement (SQL) Query statement for the database.
	 * @param boolean $cursor Whether or not to request a scrollable result set.
	 * @return \Quark\Database\Statement
	 * @throws DatabaseException When something was wrong with the query/statement.
	 */
	public function prepare($statement, $cursor=false){
		if(!empty($statement))
			return $this->driver->prepare($statement, $cursor);
		else throw new DatabaseException('Empty statement given to database execute.');
	}
	
	/**
	 * Create a QueryBuilder object of the given statement type.
	 * @param string $type Statement type.
	 * @param string $param Expression given with this statement.
	 * @return \Quark\Database\Query A query builder object.
	 * @throws DatabaseException When something was wrong with the statement type or parameter.
	 */
	public function build($type, $param=null){
		if(!empty($type))
			return new $this->queryClass($this, $type, $param);
		else throw new DatabaseException('Empty statement type given to database query builder.');
	}
	
	/**
	 * Get a new active record object of the given type.
	 * @param string $table Active Record object name.
	 * @param array $fields Fields to initialize the record with.
	 * @return \Quark\Database\Record Active record object.
	 */
	public function record($table, array $fields){
		// @todo implement record in database class.
	}
	
	/**
	 * Quote an expression or value for use in a statement.
	 * 
	 * This part of the api is mostly provided for backwards compatibility and
	 * people who requested this feature. For compatibility and especially
	 * security reasons, prepared statements are recommended over the quoted
	 * feature or even better, the query builder option of our api. These
	 * options offer greater compatibility and more security.
	 * 
	 * @param mixed $expression Expression to properly format.
	 * @return mixed A expression that may be safely used in a statement.
	 */
	public function quote($expression){
		return $this->driver->quote($expression);
	}
	
	/**
	 * Dynamically map method calls to the query builder.
	 * @param string $name Method name.
	 * @param array $arguments Arguments for the function.
	 * @return \Quark\Database\Query
	 * @throws \BadMethodCallException When an invalid statement name was called.
	 */
	public function __call($name, $arguments){
		$name = strtoupper($name);
		$cls = $this->queryClass;
		$stmts = $cls::statements();
		if(in_array($name, $stmts)){
			if(isset($arguments[0]) && is_string($arguments[0]))
				return $this->build($name, $arguments[0]);
			else return $this->build($name);
		}else throw new \BadMethodCallException('Invalid statement type "'.$name.'" given to dynamic translation method.');
	}
	
	/**
	 * Get the name of the current database instance.
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}
	
	/**
	 * Get the raw connection resource of the current driver.
	 * 
	 * When a driver exposes it's connection resource, you'll be able to get a
	 * reference to it via this function. If not, it'll return null.
	 * @return mixed|null
	 */
	public function getRawConnection(){
		return $this->driver->getRawConnection();
	}

	/**
	 * Force disconnection from the database.
	 *
	 * Also removes this instance from the multiton.
	 */
	public function disconnect(){
		$this->driver->disconnect();
		unset(self::$_instances[$this->name]);
	}
	
	/**
	 * Get the current Database Connection
	 * @return Database
	 */
	public static function getInstance($name=self::DEFAULT_NAME){
		// Check instance name and return
		if(!isset(self::$_instances[$name]))
			throw new DatabaseException('Named database instance "'.$name.'" could not be found.');
		
		return self::$_instances[$name];
	}
	
	/**
	 * Check if there is a connection by the given name.
	 * @param string $name Connection name.
	 * @return boolean
	 */
	public static function hasInstance($name=self::DEFAULT_NAME){
		return isset(self::$_instances[$name]);
	}
	
	/**
	 * Create a new non-default database connection
	 * @param \Quark\Database\Driver $driver Driver to use for the connection.
	 * @param string $name Connection name.
	 */
	public static function createInstance(Driver $driver, $name=self::DEFAULT_NAME){
		self::$_instances[$name] = new Database($name, $driver);
		return self::$_instances[$name];
	}
}

class DatabaseException extends \RuntimeException{}