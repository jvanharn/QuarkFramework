<?php
/**
 * Database Query Result Specification
 * 
 * @package		Quark-Framework
 * @version		$Id: result.php 69 2013-01-24 15:14:45Z Jeffrey $
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
 * Database Query Result
 */
interface Result extends \Countable, \Traversable{
	/**
	 * Fetch Style - List
	 * 
	 * Fetches the result as a numerically indexed array, starting with 0.
	 */
	const FETCH_LIST	= 1;
	
	/**
	 * Fetch Style - Named
	 * 
	 * Fetches the result as a column name indexed associative array.
	 */
	const FETCH_NAMED	= 2;
	
	/**
	 * Fetch Style - Object
	 * 
	 * Fetches the result as a {@see \Quark\Database\ResultRow} object wich is accessible as an associative array, numeric array and is iteratable.
	 */
	const FETCH_OBJECT	= 3;
	
	/**
	 * Iterate Normally over the array (Assoc and naturally ordered).
	 */
	const ITERATE_NORMAL = 0;
	
	/**
	 * Iterate in a reverse order.
	 */
	const ITERATE_REVERSE = 1;
	
	/**
	 * Iterate over the values using numerical column names/keys.
	 */
	const ITERATE_NUMERIC = 2;
	
	/**
	 * Fetch the given index of the resultset
	 * 
	 * Word of Warning: Should only be available on query's executed with the cursor enabled!
	 * @param integer $index Index of the row to retrieve. (You van use count to determine whether or not the index is in the range of this resultset)
	 * @param integer $style A FETCH_* Constant.
	 * @return array Array of the columns in the style given on the given index.
	 * @throws BadMethodCallException When the query was not executed as a query with cursor.
	 */
	public function fetch($index, $style=self::FETCH_NAMED);
	
	/**
	 * Fetch the next row in the result set.
	 * @param integer $style A FETCH_* Constant.
	 * @return array|ResultRow|null Array of row values or object depending on $style.
	 */
	public function fetchNext($style=self::FETCH_NAMED);
	
	/**
	 * Fetch the given column from the next row in the result set.
	 * @param integer|string $column Column name or index.
	 * @return mixed Value of column.
	 */
	public function fetchNextColumn($column=0);
	
	/**
	 * Fetch the next row as the given class.
	 * @param string $classname Classname of the class to inject the values in.
	 * @param array $params Parameters to give to the class when initializing them.
	 * @return object Row cast as the given class.
	 */
	public function fetchNextAsClass($classname, array $params=array());
	
	/**
	 * Fetch all rows in the resultset and their columns.
	 * @param integer $style A FETCH_* Constant.
	 * @return mixed Array consisting of array of row values or object depending on $style.
	 */
	public function fetchAll($style=self::FETCH_NAMED);
	
	/**
	 * Fetch all rows in the resultset, but only the specified column.
	 * @param integer|string $column Column name or index.
	 * @return array Array of all the values of the specified column.
	 */
	public function fetchAllOfColumn($column=0);
	
	/**
	 * Fetch all rows in the resultset as the specified class.
	 * @param string $classname Classname of the class to inject the values in.
	 * @param array $params Parameters to give to the class when initializing them.
	 * @return array Array of all the rows cast as the given class.
	 */
	public function fetchAllAsClass($classname, array $params=array());
	
	/**
	 * Set the iteration mode for the result set.
	 * @param integer $mode An ITERATE_* Constant.
	 */
	public function setIteratorMode($mode);
}

/**
 * Database Result Row
 */
class ResultRow implements \ArrayAccess, \Countable, \IteratorAggregate {
	/**
	 * Row this class represents.
	 * @var array
	 * @access private
	 */
	protected $row;
	
	/**
	 * Current iteration mode
	 * @var integer
	 * @access private
	 */
	protected $mode = 0;
	
	/**
	 * Constructs a new row.
	 * @param array $row Named/Associative result row.
	 * @access private
	 */
	public function __construct(array $row, $mode=Result::ITERATE_NORMAL){
		$this->row = $row;
		$this->mode = $mode;
	}
	
	/**
	 * Set the row iteration mode.
	 * @param integer $mode One of the Result::ITERATE_* constants.
	 */
	public function setIteratorMode($mode){
		if(is_int($mode))
			$this->mode = $mode;
		else throw new \InvalidArgumentException('Argument $mode should be an integer.');
	}
	
	/**
	 * Get the specified column name.
	 * @param string $name Name of the column.
	 * @return mixed Column value.
	 * @throws \OutOfBoundsException When the key does not exist.
	 */
	public function __get($name){
		if(isset($this->row[$name]))
			return $this->row[$name];
		else throw new \OutOfBoundsException('Key "'.$name.'" does not exist in this resultset.');
	}
	
	/**
	 * @access private
	 */
	public function count(){
		return count($this->row);
	}
	
	/**
	 * Get the current iterator, with Iterator mode applied.
	 * @return \ArrayIterator
	 */
	public function getIterator(){
		$array = $this->row;
		if(self::ITERATE_REVERSE & $this->mode)
			$array = array_reverse($array);
		if(self::ITERATE_NUMERIC & $this->mode)
			$array = array_values($array);
		return new \ArrayIterator($array);
	}
	
	/**
	 * @access private
	 */
	public function offsetExists($offset){
		if(is_integer($offset))
			$name = array_keys($this->row)[$offset];
		return (isset($this->row[$name]));
	}
	
	/**
	 * @access private
	 */
	public function offsetGet($offset){
		if(is_integer($offset))
			$name = array_keys($this->row)[$offset];
		if(isset($this->row[$name]))
			return $this->row[$name];
		else throw new \OutOfBoundsException('Key "'.$name.'" does not exist in this resultset.');
	}
	
	/**
	 * @access private
	 */
	public function offsetSet($offset, $value){
		throw new \RuntimeException('Cannot set column names/values on a result set!');
	}
	
	/**
	 * @access private
	 */
	public function offsetUnset($offset){
		throw new \RuntimeException('Cannot unset column names on a result set!');
	}
}