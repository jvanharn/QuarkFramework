<?php
/**
 * MySQL Database Driver - Query Result
 * 
 * @package		Quark-Framework
 * @version		$Id: result.php 69 2013-01-24 15:14:45Z Jeffrey $
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
use Quark\Database\Result;
use Quark\Database\ResultRow;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
\Quark\import('Framework.Database.Result');

/**
 * SQLite Query Result
 */
class SQLiteResult implements \IteratorAggregate, Result {
	/**
	 * PDO Result Statement Object
	 * @var \PDOStatement
	 */
	protected $stmt;
	
	/**
	 * Current iteration mode
	 * @var integer
	 */
	protected $mode = 0;
	
	/**
	 * Whether or not this resultset had a cursor when executed.
	 * @var boolean
	 */
	protected $cursor;
	
	/**
	 * Initiate the Resultset.
	 * @param \PDOStatement $stmt PDOStatement on SQLite connection to wrap.
	 * @param boolean $cursor Whether or not this resultset had a cursor when executed.
	 * @access private
	 */
	public function __construct(\PDOStatement $stmt, $cursor=false){
		$this->stmt = $stmt;
		$this->cursor = $cursor;
	}
	
	/**
	 * Fetch the given index of the resultset
	 * 
	 * Word of Warning: Should only be available on query's executed with the cursor enabled!
	 * @param integer $index Index of the row to retrieve. (You van use count to determine whether or not the index is in the range of this resultset)
	 * @param integer $style A FETCH_* Constant.
	 * @return array Array of the columns in the style given on the given index.
	 * @throws \BadMethodCallException When the query was not executed as a query with cursor.
	 * @throws \InvalidArgumentException When the incorrect constant was used.
	 */
	public function fetch($index, $style = self::FETCH_NAMED) {
		if(!$this->cursor)
			throw new \BadMethodCallException('This resultset does not have a cursor, so you cannot search in it.');
		switch($style) {
			case self::FETCH_NAMED:
				return $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, $index);
			case self::FETCH_LIST:
				return $this->stmt->fetch(\PDO::FETCH_NUM, \PDO::FETCH_ORI_ABS, $index);
			case self::FETCH_OBJECT:
				return new ResultRow($this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_ABS, $index));
			default:
				throw new \InvalidArgumentException('Invalid Result::FETCH_* constant used.');
		}
	}

	/**
	 * Fetch the next row in the result set.
	 * @param int $style A FETCH_* Constant.
	 * @throws \InvalidArgumentException
	 * @return mixed Array of row values or object depending on $style.
	 */
	public function fetchNext($style = self::FETCH_NAMED) {
		switch($style) {
			case self::FETCH_NAMED:
				return $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
			case self::FETCH_LIST:
				return $this->stmt->fetch(\PDO::FETCH_NUM, \PDO::FETCH_ORI_NEXT);
			case self::FETCH_OBJECT:
				return new ResultRow($this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT));
			default:
				throw new \InvalidArgumentException('Invalid Result::FETCH_* constant used.');
		}
	}

	/**
	 * Fetch the given column from the next row in the result set.
	 * @param integer|string $column Column name or index.
	 * @throws \InvalidArgumentException
	 * @return mixed Value of column.
	 */
	public function fetchNextColumn($column=0) {
		// PDO Officially only supports ints as column indices.
		if(is_integer($column))
			return $this->stmt->fetchColumn($column);
		// So just emulate strings.
		else if(is_string($column)){
			$row = $this->stmt->fetch(\PDO::FETCH_ASSOC, \PDO::FETCH_ORI_NEXT);
			return $row[$column];
		}else throw new \InvalidArgumentException('This method only accepts strings and integers as column names.');
	}
	
	/**
	 * Fetch the next row as the given class.
	 * @param string $classname Classname of the class to inject the values in.
	 * @param array $params Parameters to give to the class when initializing them.
	 * @return object Row cast as the given class.
	 */
	public function fetchNextAsClass($classname, array $params = array()) {
		return $this->stmt->fetchObject($classname, $params);
	}

	/**
	 * Fetch all rows in the resultset and their columns.
	 * @param int $style A FETCH_* Constant.
	 * @throws \InvalidArgumentException
	 * @return mixed Array consisting of array of row values or object depending on $style.
	 */
	public function fetchAll($style = self::FETCH_NAMED) {
		switch($style) {
			case self::FETCH_NAMED:
				return $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
			case self::FETCH_LIST:
				return $this->stmt->fetchAll(\PDO::FETCH_NUM);
			case self::FETCH_OBJECT:
				$result = array();
				foreach($this->stmt->fetchAll(\PDO::FETCH_ASSOC) as $row)
					$result[] = new ResultRow($row);
				return $result;
			default:
				throw new \InvalidArgumentException('Invalid Result::FETCH_* constant used.');
		}
	}

	/**
	 * Fetch all rows in the resultset, but only the specified column.
	 * @param integer|string $column Column name or index.
	 * @throws \OutOfBoundsException
	 * @throws \InvalidArgumentException
	 * @return array Array of all the values of the specified column.
	 */
	public function fetchAllOfColumn($column=0) {
		// PDO Officially only supports ints as column indices.
		if(is_integer($column))
			return $this->stmt->fetchAll(\PDO::FETCH_COLUMN, $column);
		// So just emulate strings.
		else if(is_string($column)){
			$rows = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
			if(isset($rows[0]) && !isset($rows[0][$column]))
				throw new \OutOfBoundsException('Column does not exist in the resultset.');
			foreach($rows as $key => $row)
				$rows[$key] = $row[$column];
			return $rows;
		}else throw new \InvalidArgumentException('This method only accepts strings and integers as column names.');
	}
	
	/**
	 * Fetch all rows in the resultset as the specified class.
	 * @param string $classname Classname of the class to inject the values in.
	 * @param array $params Parameters to give to the class when initializing them.
	 * @return array Array of all the rows cast as the given class.
	 */
	public function fetchAllAsClass($classname, array $params = array()) {
		return $this->stmt->fetchAll(\PDO::FETCH_CLASS, $classname, $params);
	}

	/**
	 * Set the iteration mode for the result set.
	 * @param integer $mode An ITERATE_* Constant.
	 * @throws \InvalidArgumentException
	 */
	public function setIteratorMode($mode) {
		if(is_int($mode))
			$this->mode = $mode;
		else throw new \InvalidArgumentException('Argument $mode should be an integer.');
	}
	
	/**
	 * Count the number of rows in the resultset.
	 * 
	 * Countable Implementation
	 * @return integer
	 */
	public function count() {
		return $this->stmt->rowCount();
	}
	
	/**
	 * Iterator aggregate implementation
	 * @return array
	 */
	public function getIterator() {
		if(self::ITERATE_NUMERIC & $this->mode)
			$array = $this->stmt->fetchAll(\PDO::FETCH_NUM);
		else
			$array = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		if(self::ITERATE_REVERSE & $this->mode)
			$array = array_reverse($array);
		
		return new \ArrayIterator($array);
	}
}