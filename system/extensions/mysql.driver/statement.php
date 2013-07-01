<?php
/**
 * MySQL Database Driver - Prepared Statement
 * 
 * @package		Quark-Framework
 * @version		$Id: statement.php 69 2013-01-24 15:14:45Z Jeffrey $
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

// Dependencies
\Quark\import('Framework.Database.Result');

/**
 * MySQL Query Result
 */
class MySQLStatement implements \Quark\Database\Statement {
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
	 * @param \PDOStatement $stmt MySQL PDO Statement that was prepared.
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
	 * @return \Quark\Database\Statement Returns a reference to itself for chaining.
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
	 * @return \Quark\Database\Result Result of the query.
	 * @see \Quark\Database\Database::query
	 */
	public function query(array $params=array()){
		try {
			if($this->stmt->execute($params)){
				return new MySQLResult($this->stmt, $this->cursor);
			}else{
				throw new \Quark\Database\DatabaseException('Could not execute query, something went wrong while executed prepared PDOStatement object: "'.$this->stmt->errorInfo()[2].'"');
			}
		}catch(\PDOException $e){
			throw new \Quark\Database\DatabaseException('Could not execute prepared statement, something went wrong, see last exception for more info.', E_ERROR, $e);
		}
	}
	
	/**
	 * Execute the prepared statement
	 * @param array $params The bound parameter array should be in the form of k=>v if using named notation (:name) and numerically indexed when using questionmark notation (?).
	 * @return boolean|integer
	 * @see \Quark\Database\Database::execute
	 */
	public function execute(array $params=array()){
		try {
			if($this->stmt->execute($params))
				return $this->stmt->rowCount();
			else return false;
		}catch(\PDOException $e){
			throw new \Quark\Database\DatabaseException('Could not execute prepared statement, something went wrong, see last exception for more info.', E_ERROR, $e);
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