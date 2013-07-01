<?php
/**
 * Database Record Specification for Active Record/Object Relational Mapper implementation.
 * 
 * @package		Quark-Framework
 * @version		$Id: record.php 69 2013-01-24 15:14:45Z Jeffrey $
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

// Import Namespaces
use	\Quark\Database\Statement,
	\Quark\Database\Query;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Active Record Basic Implementation.
 */
abstract class Record {
	// Relation Constants
	// These constants are only here for conveniance, it's easier just to define the multiplicity as a string or array.
	/**
	 * Relation - This record has one record of the other type. (Multiplicity: 1..1)
	 */
	const RELATION_HAS_ONE		= '1..1';
	
	/**
	 * Relation - This record has multiple record of the other type. (Multiplicity: 1..*)
	 */
	const RELATION_HAS_MANY		= '1..*';
	
	/**
	 * Relation - This record has one record of the other type. (Multiplicity: 1..1)
	 */
	const RELATION_BELONGS_TO	= '*..1';
	
	/**
	 * Relation - This record has one record of the other type. (Multiplicity: 1..1)
	 */
	const RELATION_MANY_TO_MANY	= '*..*';
	
	/**
	 * No action taken when relation table is updated or deleted.
	 */
	const ACTION_NONE			= 0;
	
	/**
	 * Update or delete is stopped when it still has references.
	 */
	const ACTION_RESTRICT		= 1;
	
	/**
	 * The referenced table is also updated and/or deleted.
	 */
	const ACTION_CASCADE		= 2;
	
	/**
	 * The referencing tables field is set to null.
	 */
	const ACTION_SET_NULL		= 3;
	
	/**
	 * The referencing tables field is set to it's default value.
	 */
	const ACTION_SET_DEFAULT	= 4;
	
	protected static $name;
	
	protected static $primary;
	
	protected static $columns;
	
	protected static $relations;
	
	/**
	 * Whether or not this 
	 * @var boolean
	 */
	protected $new = true;
	
	/**
	 * Holds the reference to the current database's active record driver.
	 * @var \Quark\Database\RecordDriver
	 */
	protected $driver;
	
	/**
	 * Contains the values for each column in
	 * @var array
	 */
	protected $data = array();
	
	/**
	 * @param array $data Data for the record.
	 * @param \Quark\Database\RecordDriver $driver The ActiveRecord driver implementation for the current conenction.
	 * @param boolean $new
	 * @access private
	 */
	public function __construct($data, RecordDriver $driver, $new){
		$this->new = ($new === true);
		$this->driver = $driver;
		$this->data = $data;
	}
	
	/**
	 * Get the value of the column in the current record.
	 * @param string $column Column name.
	 * @param boolean $raw Whether or not to return the value the database returned or get the transformed type(For dates etc.)
	 * @return mixed Column value.
	 */
	public function get($column, $raw=false){
		$called = get_called_class();
		if(is_string($column) && isset($called::$columns[$column])){
			// @todo check type and return date object etc if of a given type.
			return $this->data[$column];
		}else throw new \InvalidArgumentException('Invalid $column name given.');
	}
	
	/**
	 * Set the value for the column.
	 * @param string $column Column to assign to
	 * @param string $value Value to assign to it.
	 */
	public function set($column, $value, $type=Statement::PARAM_STRING){
		$called = get_called_class();
		if(isset($called::$columns[$column])){
			$this->driver->updateValue($called::getName(), $called::$primary, $this->data[$called::primary], $value, $type);
		}else throw new \OutOfBoundsException('Column does not exist.');
	}
	
	/**
	 * Get the name of the object.
	 * 
	 * If the name of the table was not explicitly defined, it will be reduced
	 * from the name of the object.
	 * @return string Table name.
	 */
	public static function getName(){
		$called = get_called_class();
		if(empty($called::$name)){
			$parts = explode('\\', $called);
			$called::$name = end($parts);
		}
		return $called::$name;
	}
	
	/**
	 * Get the columns and their types/attributes.
	 * @return array
	 */
	public static function getColumns(){
		$called = get_called_class();
		if(!empty($called::$columns))
			return $called::$columns;
		else throw new \LogicException('This record has no defined columns');
	}
	
	/**
	 * Get the relations between this object and others.
	 * 
	 * Returns an array formatted like the value; the other table as key with
	 * as value an array with the [[left AND right multiplicity's] or a string,
	 * update action ACTION_* constant, delete action].
	 * @return array
	 */
	public static function getRelations();
	
	/**
	 * Get all records that have the given key(s).
	 * @param integer|string|array $value
	 * @return array
	 */
	public static function key($value);
	
	/**
	 * Find all the records that have the given value in the specified column.
	 * @param string $column Column to search in.
	 * @param mixed $value Value of the column.
	 * @return \Quark\Database\Result
	 */
	public static function find($column, $value, $type=Statement::PARAM_STRING);
	
	/**
	 * Find records that are like the given value in the given column.
	 * @param string $column Column to search in.
	 * @param mixed $value Value of the column.
	 * @return \Quark\Database\Result
	 */
	public static function like($column, $value);
	
	/**
	 * Get all records that have a set relation with the given object.
	 * @param string $record Name of the record this record has a relation with.
	 */
	public static function with($record);
	
	/**
	 * Get a query builder object.
	 * 
	 * Query builder object is provided to build some queries that might not be
	 * possible with the active record implementation.
	 * @param string $stmtName Type of statement to create (SELECT, INSERT, etc.)
	 * @param array $columns Columns to retrieve in this query.
	 * @return \Quark\Database\Query Query Builder object for the current driver.
	 */
	public static function build($stmtName, array $columns){
		if(!is_string($stmtName))
			throw new \InvalidArgumentException('Statement name should be of type "string" but got "'.gettype($stmtName).'".');
		
		$called = get_called_class();
		if(empty($columns))
			return Database::getInstance()->build($stmtName)->from($called::getName());
		else
			return Database::getInstance()->build($stmtName, $columns)->from($called::getName());
	}
}

interface RecordDriver {
	/**
	 * Update a record to the given value(s).
	 * @param string $table Name of the table to update
	 * @param string $primaryKey Name of the primary key for this table
	 * @param string $keyValue Value of the primary key for the given table.
	 * @param array $value Key value pairs of the columns to update to what values.
	 * @param integer $type
	 */
	public function update($table, $primaryKey, $keyValue, $values, $type);
}

/**
 * Default Active Record Driver.
 * 
 * Implements the active record classes using the SQLQuery classes.
 */
class SQLQueryRecordDriver implements RecordDriver {
	
}