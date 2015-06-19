<?php
/**
 * Database Query Builder
 * 
 * @package		Quark-Framework
 * @version		$Id: query.php 69 2013-01-24 15:14:45Z Jeffrey $
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
 * Basic Query Builder Interface
 */
interface Query {
	/**
	 * Construct a new query.
	 * @param \Quark\Database\Database $db Database to eventually execute on.
	 * @param string $stmtType Type of (SQL) statement to create. (SELECT, INSERT, ...)
	 * @param string|array $params Parameter(s) to give with the query of $stmtType.
	 */
	public function __construct(Database $db, $stmtType, $params=null);
	
	/**
	 * Add a (SQL) clause to the query
	 * @param string $name Name of the clause to add.
	 * @param array $params Optional parameters for the clause. (Contents should depend on name given)
	 * @return \Quark\Database\Query The current query object for method chaining.
	 * @throws DatabaseException When the clause given is invalid.
	 */
	public function clause($name, array $params=array());
	
	/**
	 * Merge the results of the given query with the results of this query.
	 * @param \Quark\Database\Query $query Query to merge with.
	 * @return \Quark\Database\Query The current query object for method chaining.
	 * @throws \Quark\Database\DatabaseException When the union query and the current query cannot be union-fied.
	 */
	public function union(Query $query);
	
	/**
	 * Retrieve the representation of the query, executable with the engine.
	 * @return mixed Whatever can be passed to the execute or query function in the database driver.
	 */
	public function save();
	
	/**
	 * Executes the query in the database and returns the success state.
	 * @return boolean|int|Result Success state or number of affected rows.
	 */
	public function execute();
	
	/**
	 * Get the database connection used for this query.
	 */
	public function getDatabase();
	
	/**
	 * Get a list of all the valid statement types for the query builder.
	 * @return array
	 */
	public static function statements();
}

/**
 * Simple SQL Query Builder implementation
 * 
 * This class renders SQL queries with relative ease. If however your database
 * provides more functionality, you can provide this. As long as SELECT, INSERT,
 * UPDATE and DELETE statements work with the below arguments etc as stated in 
 * the appropriate testcases.
 * 
 * This class should be extended by the database drivers.
 */
abstract class SQLQuery implements Query {
	/**
	 * Parameter Type Column Name
	 * 
	 * Mostly just the string passed in except when it is a reserved word.
	 */
	const PARAM_COLUMNNAME = 1;
	
	/**
	 * Parameter Type Expression
	 * 
	 * Just an expression string allowed, like "`stringvalue`"
	 */
	const PARAM_EXPRESSION = 2;
	
	/**
	 * Parameter Type Assignment
	 * 
	 * Assignment like notation, like "'key' = value"
	 */
	const PARAM_ASSIGNMENT = 3;
	
	/**
	 * Parameter Type Predictate
	 * 
	 * Just like an assignment but with more operators allowed like >, %, <= etc.
	 */
	const PARAM_PREDICTATE = 4;
	
	/**
	 * All statements with their allowed clauses, call order and any other settings.
	 * 
	 * Structure: [
	 *   'stmtType' => [
	 *      
	 *   ]
	 * ]
	 * @var array
	 */
	protected static $statements = array(
		// 'method' =>array('default value' or null if none, (array) with possible other values, (array) allowed statements with this method, (bool) whether or not to allow k=>v parameters)
		'SELECT' => [
			'default'	=> '*',
			'empty'		=> false,
			'keyvalue'	=> false,
			'multiple'	=> true,
			'clauses'	=> ['FROM', 'WHERE', 'GROUP BY', 'HAVING', 'ORDER BY', 'LIMIT'],
			'required'	=> ['FROM'],
			'resultset'	=> true
		],
		'INSERT' => [
			'default'	=> null,
			'empty'		=> true,
			'keyvalue'	=> false,
			'multiple'	=> false,
			'clauses'	=> ['INTO', 'VALUES'],
			'required'	=> ['INTO', 'VALUES'],
			'resultset'	=> false
		],
		'UPDATE' => [
			'default'	=> null,
			'empty'		=> false,
			'keyvalue'	=> true,
			'multiple'	=> false,
			'clauses'	=> ['SET', 'WHERE', 'ORDER BY', 'LIMIT'],
			'required'	=> ['SET'],
			'resultset'	=> false
		],
		'DELETE FROM' => [
			'default'	=> null,
			'empty'		=> false,
			'keyvalue'	=> false,
			'multiple'	=> false,
			'clauses'	=> ['WHERE', 'ORDER BY', 'LIMIT'],
			'required'	=> [],
			'resultset'	=> false
		]
	);
	
	/**
	 * Statement clauses and their properties
	 * @var array
	 */
	protected static $clauses = array(
		'FROM' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> true
		],
		'INTO' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> true
		],
		'WHERE' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_PREDICTATE,
			'subquery'	=> false
		],
		'GROUP BY' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> false
		],
		'HAVING' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_PREDICTATE,
			'subquery'	=> false
		],
		'ORDER BY' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_COLUMNNAME,
			'subquery'	=> false
		],
		'LIMIT' => [
			'multiple'	=> false,
			'type'		=> self::PARAM_EXPRESSION,
			'subquery'	=> false
		],
		'SET' => [
			'multiple'	=> true,
			'type'		=> self::PARAM_ASSIGNMENT,
			'subquery'	=> false
		]
	);
	
	/**
	 * Statement aliasses
	 * @var array
	 */
	protected static $aliasses = array(
		'DELETE' => 'DELETE FROM'
	);
	
	/**
	 * Reserved words in this SQL dialect
	 * @var array
	 */
	protected static $reserved = array(
		'select', 'insert', 'delete', 'update', 'into', 'from', 'where',
		'group', 'by', 'having', 'order', 'limit', 'set', '?'
	);
	
	/**
	 * Database connection reference.
	 * @var \Quark\Database\Database
	 */
	protected $db;
	
	/**
	 * Current query/statement type.
	 * @var string
	 */
	protected $type;
	
	/**
	 * Params for that statement.
	 * @var array|string
	 */
	protected $param;
	
	/**
	 * Current statement type's properties.
	 * @var array
	 */
	protected $props;
	
	/**
	 * Clauses added to the current query
	 * @var array
	 */
	protected $stmt = array();
	
	/**
	 * All the queries in union with this query
	 * @var array
	 */
	protected $union = array();
	
	/**
	 * Saved version of the query (Cache).
	 * @var string
	 */
	protected $query;
	
	/**
	 * Bound parameters for prepared query's
	 * @var array
	 */
	protected $bound = array();
	
	/**
	 * @param \Quark\Database\Database $db
	 * @param string $type
	 * @param array $param
	 * @throws \InvalidArgumentException When the params given were incorrectly formatted.
	 * @access private
	 */
	public function __construct(Database $db, $type, $param=null){
		if(!is_null($db))
			$this->db = $db;
		else throw new \InvalidArgumentException('Database object shouldn\'t be null.');
		
		$class = get_called_class();
		$type = strtoupper($type);
		if(is_string($type) && in_array($type, $class::statements())){
			if(isset($class::$aliasses[$type]))
				$type = $class::$aliasses[$type];
			$this->type = $type;
			$this->props = $class::$statements[$this->type];
		}else throw new \InvalidArgumentException('Invalid statement type given. Please check available statements using the Query::statements method.');
		
		if($this->props['default'] != null){
			if(is_null($param))
				$this->param = [$this->props['default']];
			else if(!$this->props['multiple'] && is_string($param))
				$this->param = [$param];
			else if($this->props['multiple'] && is_array($param)){
				// Quick 'n Dirty check if it is an numeric indexed array
				if(!$this->props['keyvalue'] && isset($param[(count($param)-1)]))
					$this->param = $param;
				else if($this->props['keyvalue'] && !isset($param[(count($param)-1)]))
					$this->param = $param;
				else throw new \InvalidArgumentException('Argument $param was invalidly formatted, expected key=>value for given type, or vice versa.');				
			}else if($this->props['multiple'] && !$this->props['keyvalue'] && is_string($param))
				$this->param = [$param];
			else
				throw new \InvalidArgumentException('Argument $param was of unrecognised/unusable type for given statement type.');
		}
	}

	/**
	 *
	 * @param string $name
	 * @param array $params
	 * @return $this|Query
	 * @throws \OutOfBoundsException
	 * @throws \InvalidArgumentException
	 */
	public function clause($name, array $params=array()){
		$name = strtoupper($name);
		if(in_array($name, $this->props)){
			if(empty($params))
				throw new \InvalidArgumentException('Clause parameters should never be empty.');
			
			$class = get_called_class();
			if(!isset($class::$clauses[$name]))
				throw new \OutOfBoundsException('"'.$name.'" is a invalid SQL clause (at least for this database).');
			$prop = $class::$clauses[$name];
			if($prop['multiple']){
				if($prop['type'] == self::PARAM_EXPRESSION && isset($params[count($params)-1]))
					$this->stmt[$name] = $params;
				else if($prop['type'] == self::PARAM_ASSIGNMENT && !isset($params[count($params)-1]))
					$this->stmt[$name] = $params;
				else if($prop['type'] == self::PARAM_PREDICTATE && isset($params[count($params)-1])){
					if(count($params[0]) == 3)
						$this->stmt[$name] = $params;
					else throw new \InvalidArgumentException('Parameter for "'.$name.'" was incorrectly formatted. Should be in the format of array(array("keyorcolumn", ">=", "expected value"), ...).');
				}else
					throw new \InvalidArgumentException('Invalid parameter for the defined parameter type for the clause "'.$name.'".');
			}else $this->stmt[$name] = [$params[0]];
		}else throw new \InvalidArgumentException('Argument name should be valid clause for this statement type.');
		
		return $this;
	}

	/**
	 * Copies the given query to this class as an UNION query.
	 * @param Query $query
	 * @return $this|Query
	 * @throws DatabaseException
	 */
	public function union(Query $query){
		if($query->getDatabase()->getName() == $this->db->getName()){
			// Check if both query's have resultsets
			if($this->props['resultset'] == $query->getProperties()['resultset'])
				$this->union[] = $query;
			else throw new DatabaseException('The query given is not unionable with this query because their return values are different.');
		}else
			throw new DatabaseException('The given query was not build for the same database connection as this query, and can therefore not be unified.');
		
		return $this;
	}

	/**
	 * Get the string representation of the query
	 * @param bool $prepared Whether or not to save it as a prepared statement.
	 * @throws \DomainException When the query contains statements that do not adhere to the database query language domain.
	 * @return string
	 */
	public function save($prepared=false){
		// Check cache
		if(!empty($this->query) && !$prepared)
			return $this->query;
		
		// Get called class
		$class = get_called_class();
		
		// Check if the query meets the minimally required clauses
		foreach($this->props['required'] as $req){
			if(!isset($this->stmt[$req]))
				throw new \DomainException('The current query could not be build, because it does not adhere to the "'.$this->type.'" domain. These kinds of queries require at least usage of the clauses: '.implode(', ', $this->props['required']));
		}
		
		// Set statement base
		$query = $this->type.' ';
		if(!empty($this->param))
			$query .= $this->saveParameter(
				$this->param,
				($this->props['keyvalue'] ? self::PARAM_ASSIGNMENT : self::PARAM_COLUMNNAME),
				$this->props['multiple'],
				false
			);
		
		// Add clauses
		foreach($this->stmt as $clause => $param){
			// Get props
			$props = $class::$clauses[$clause];

			// Save param
			$query .= "\n".$clause.' '.$this->saveParameter($param, $props['type'], $props['multiple'], $prepared);
		}
		
		// Union with the other query's
		foreach($this->union as $query)
			$query .= "\nUNION\n".$query->save($prepared);
		
		// End of query
		$query .= ';';
		
		// Cache result
		$this->query = $query;
		
		// Return query
		return $query;
	}
	
	/**
	 * Get the bound parameters values for the saved prepared statement.
	 * @return array
	 * @throws \BadMethodCallException When the query wasn't saved yet.
	 */
	public function getBoundParams(){
		if($this->bound != null)
			return $this->bound;
		else throw new \BadMethodCallException('This method can only be called after this query was saved preparated.');
	}
	
	protected function saveParameter($parameter, $type, $multiple, $prepared){
		if(!$multiple){
			if((is_array($parameter) && $type != self::PARAM_PREDICTATE) || (is_array($parameter) && is_array($parameter[0])))
				$parameter = $parameter[0];
			switch($type){
				case self::PARAM_COLUMNNAME:
					if(is_object($parameter) && $parameter instanceof SQLQuery)
						return '('.PHP_EOL.substr($parameter->save(), 0, -1).')';
					else{
						$class = get_called_class();
						if(in_array(strtolower($parameter), $class::$reserved)) return '`'.$parameter.'`';
						else return $parameter;
					}
				case self::PARAM_EXPRESSION:
					if($prepared){
						$this->bound[] = $parameter;
						return '?';
					}
					else if(is_numeric($parameter)) return $parameter;
					else return '\''.$parameter.'\'';
				case self::PARAM_ASSIGNMENT:
					throw new \LogicException('PARAM_ASIGNMENT cannot have single value, internal parser error.');
				case self::PARAM_PREDICTATE:
					return $this->saveParameter($parameter[0], self::PARAM_COLUMNNAME, false, $prepared).' '.$parameter[1].' '.$this->saveParameter($parameter[2], self::PARAM_EXPRESSION, false, $prepared);
				default:
					throw new \LogicException('Unexpected type.');
			}
		}else{
			switch($type){
				case self::PARAM_COLUMNNAME:
					$save = '';
					foreach($parameter as $param){
						$save .= $this->saveParameter($param, self::PARAM_COLUMNNAME, false, $prepared).', ';
					}
					return substr($save, 0, -2);
				case self::PARAM_EXPRESSION:
					$save = '';
					foreach($parameter as $param){
						$save .= $this->saveParameter($param, self::PARAM_EXPRESSION, false, $prepared).', ';
					}
					return substr($save, 0, -2);
				case self::PARAM_ASSIGNMENT:
					$save = '';
					foreach($parameter as $param => $value){
						$save .= $param.' = '.$this->saveParameter($value, self::PARAM_EXPRESSION, false, $prepared).', ';
					}
					return substr($save, 0 -2);
				case self::PARAM_PREDICTATE:
					$save = '';
					foreach($parameter as $param){
						$save .= $this->saveParameter($param, self::PARAM_PREDICTATE, false, $prepared).', ';
					}
					return substr($save, 0, -2);
				default:
					throw new \LogicException('Unexpected type.');
			}
		}
	}

	/**
	 * Try to execute this query with the connection it was created at.
	 * @return bool|int|Result
	 */
	public function execute(){
		if($this->props['resultset'])
			return $this->db->query($this);
		else return $this->db->execute($this);
	}

	/**
	 * Get the database connection object this query was created on.
	 * @return Database
	 */
	public function getDatabase(){
		return $this->db;
	}

	/**
	 * Get the set proeprties for this query.
	 * @return array
	 */
	public function getProperties(){
		return array_merge($this->props, ['type' => $this->type]);
	}

	/**
	 * Get all available statements on this query type.
	 * @return array
	 */
	public static function statements(){
		$class = get_called_class();
		return array_merge(array_keys($class::$statements), array_keys($class::$aliasses));
	}
	
	// Magic methods
	/**
	 * Makes it possible for the queries to dynamically compile.
	 * @param string $name
	 * @param array $params
	 * @return $this
	 * @throws \InvalidArgumentException When arguments are invalid.
	 */
	public function __call($name, array $params){
		if(is_string($params[0]) || (is_object($params[0]) && $params[0] instanceof SQLQuery))
			$params[0] = array($params[0]);
		else if(!is_array($params[0]))
			throw new \InvalidArgumentException('Argument $params should be of type array.');
		return $this->clause(str_replace('_',' ',$name), $params[0]);
	}

	/**
	 * Same as calling save().
	 * @see save()
	 * @return string
	 */
	public function __toString() {
		return $this->save();
	}
}