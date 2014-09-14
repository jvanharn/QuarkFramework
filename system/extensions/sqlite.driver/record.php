<?php
/**
 * SQLite Database Driver - Active Record Driver
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		16 July 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Database\Driver;
use Quark\Database\RecordDriver;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * MySQL Query Result
 */
class SQLiteRecord implements RecordDriver {

	/**
	 * Update a record to the given value(s).
	 * @param string $table Name of the table to update
	 * @param string $primaryKey Name of the primary key for this table
	 * @param string $keyValue Value of the primary key for the given table.
	 * @param array $value Key value pairs of the columns to update to what values.
	 * @param integer $type
	 */
	public function update($table, $primaryKey, $keyValue, $values, $type) {
		// TODO: Implement update() method.
	}
}