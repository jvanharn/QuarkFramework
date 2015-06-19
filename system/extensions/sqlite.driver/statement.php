<?php
/**
 * SQLite Database Driver - Prepared Statement
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		16 July 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Database\Driver;
use Quark\Database\DatabaseException;
use Quark\Database\Statement;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
\Quark\import('Framework.Database.Result');

/**
 * SQLite Query Result
 */
class SQLiteStatement implements Statement {
	/**
	 * Whether or not this statement should have a cursor over it's result.
	 * @var boolean
	 */
	protected $cursor = false;
	
	/**
	 * The pdo statement that is wrapped for this driver.
	 * @var \PDOStatement
	 */
	protected $stmt = null;

	/**
	 * @param \PDOStatement $stmt SQLite PDO Statement that was prepared.
	 * @param boolean $cursor
	 * @access private
	 */
	public function __construct(\PDOStatement $stmt, $cursor){
		$this->cursor = $cursor;
		$this->stmt = $stmt;
	}

	/**
	 * Bind a value to a name.
	 * @param string|integer $name Use numbers for "?"-marks and names for the ":name" notation.
	 * @param mixed $value Value for the column.
	 * @param integer $type A PARAM_* Constant or null for detection.
	 * @throws \InvalidArgumentException
	 * @return Statement Returns a reference to itself for chaining.
	 */
	public function bind($name, $value, $type=null){
		if(!(is_string($name) || is_integer($name)))
			throw new \InvalidArgumentException('Name should be of type "string" or "integer", but got "'.gettype($name).'".');
		else if(!(is_integer($type) || is_null($type)))
			throw new \InvalidArgumentException('$type should be of type "integer" or NULL for autodetection, but got "'.gettype($type).'".');
		
		if(is_null($type)){
			if(is_integer($value))
				$type = self::PARAM_INTEGER;
			else if(is_float($value))
				$type = self::PARAM_FLOAT;
			else if(is_bool($value))
				$type = self::PARAM_BOOL;
			else
				$type = self::PARAM_STRING;
		}
		$this->stmt->bindValue($name, $value, $this->_convertTypeForPDO($type));
	}

	/**
	 * Query the database with the prepared statement
	 * @param array $params The bound parameter array should be in the form of k=>v if using named notation (:name) and numerically indexed when using questionmark notation (?).
	 * @throws DatabaseException
	 * @return \Quark\Database\Result Result of the query.
	 * @see \Quark\Database\Database::query
	 */
	public function query(array $params=array()){
		try {
			if($this->stmt->execute($params)){
				return new SQLiteResult($this->stmt, $this->cursor);
			}else{
				throw new DatabaseException('Could not execute query, something went wrong while executed prepared PDOStatement object: "'.$this->stmt->errorInfo()[2].'"');
			}
		}catch(\PDOException $e){
			throw new DatabaseException('Could not execute prepared statement, something went wrong, see last exception for more info.', E_ERROR, $e);
		}
	}

	/**
	 * Execute the prepared statement
	 * @param array $params The bound parameter array should be in the form of k=>v if using named notation (:name) and numerically indexed when using questionmark notation (?).
	 * @throws \Quark\Database\DatabaseException
	 * @return boolean|integer
	 * @see \Quark\Database\Database::execute
	 */
	public function execute(array $params=array()){
		try {
			if($this->stmt->execute($params))
				return $this->stmt->rowCount();
			else return false;
		}catch(\PDOException $e){
			throw new DatabaseException('Could not execute prepared statement, something went wrong, see last exception for more info.', E_ERROR, $e);
		}
	}
	
	/**
	 * @param integer $type Quark type constant value.
	 * @return integer PDO type constant.
	 */
	private function _convertTypeForPDO($type){
		switch($type){
			case self::PARAM_BOOL:
				return \PDO::PARAM_BOOL;
			case self::PARAM_INTEGER:
			case self::PARAM_FLOAT:
			case self::PARAM_UNIXTIME:
				return \PDO::PARAM_INT;
			case self::PARAM_STRING:
			case self::PARAM_DATE:
			case self::PARAM_DATETIME:
				return \PDO::PARAM_STR;
			case self::PARAM_LOB:
				return \PDO::PARAM_LOB;
		}
	}
}