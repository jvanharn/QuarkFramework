<?php
/**
 * Supplier Interface
 * 
 * @package		Quark-Framework
 * @version		$Id: supplier.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		15 december 2012
 * @copyright	Copyright (C) 2012 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012 Jeffrey van Harn
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
namespace Quark\Extensions;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Extension List Supplier Interface
 * 
 * All suppliers should implement this interface. Classes implementing this
 * interface are expected to fill the registry with all the extensions available
 * at that time.
 */
interface Supplier {
	/**
	 * Fills the given Extension Registry with the available extensions.
	 *
	 * This function should fill the extension registry with extensions that are
	 * currently available. This means that it should also contain extensions with statuses
	 * like enabled, disabled, new etc.
	 * @param \Quark\Extensions\Extensions $registry Current (empty) registry to fill.
	 * @return void
	 */
	public function fill(Extensions $registry);
	
	/**
	 * Current availability of the supplier.
	 * 
	 * Whether or not this supplier is available or able to fill the extension
	 * registry at the moment. If it is always in this state, just return true.
	 * @return boolean
	 */
	public function available();
}

/**
 * Caching Extension List Supplier Interface
 * 
 * Suppliers implementing this interface are expected to cache the registry
 * fills by 'real' suppliers. Where these real suppliers are mostly directory
 * scanning suppliers that are inefficient but find all the extensions to list,
 * these suppliers give performance during normal operation.
 */
interface CachingSupplier extends Supplier {
	/**
	 * Caches the existing/filled registry.
	 * 
	 * Caches the registry currently in memory for faster data access in future
	 * page requests.
	 * @param Extensions $registry The registry to cache.
	 */
	public function cache(Extensions $registry);
	
	/**
	 * Whether or not this supplier can cache on this moment.
	 * @return boolean
	 */
	public function cacheable();
}

/**
 * Stateless/Building or Updating Extension List Supplier Interface
 * 
 * Because of their simple origins Updating suppliers can only update existing
 * lists or build completely new ones. They do not/cannot remember the old
 * states of extensions that have already had modified states like enabled etc.
 * They can only find new extensions or determine if extensions are deleted.
 * 
 * They should also be able to fill lists, but they must then mark all
 * extensions as new, as they do not remember the states.
 */
interface BuildingSupplier extends Supplier {
	/**
	 * Update an already filled registry.
	 * @param Extensions $registry
	 * @return void
	 */
	public function update(Extensions $registry);
}