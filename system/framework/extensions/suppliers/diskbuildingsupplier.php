<?php
/**
 * Title
 * 
 * Description of the file.
 * 
 * @package		Quark-Framework
 * @version		$Id: diskbuildingsupplier.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		16 december 2012
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
namespace Quark\Extensions\Suppliers;

// Import namespace
use	\Quark\Extensions\Extensions;
	//\Quark\Extensions\ExtensionRegistry,
	//\Quark\Extensions\HandlerRegistry;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Description of diskbuildingsupplier
 *
 * @author Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 */
class DiskBuildingSupplier implements \Quark\Extensions\BuildingSupplier {
	public function available() {
		if(!is_dir(DIR_EXTENSIONS))
			throw new \RuntimeException('The Extension directory as defined in the DIR_EXTENSIONS constant does not exist!');
		return true;
	}

	public function fill(\Quark\Extensions\Extensions $registry) {
		// Create a new Directory Iterator
		$extensions = new \DirectoryIterator(DIR_EXTENSIONS);
		
		// Loop over the iterator
		foreach($extensions as $ext){
			if(!$ext->isDot() && $ext->isDir()){
				// It's a directory, collect info
				$name = $ext->getFilename();
				$path = $ext->getPathname().DS;
				
				// Register new extension
				$registry->register($path, $name);
			}
		}
	}

	public function update(\Quark\Extensions\Extensions $registry) {
		// Create a new Directory Iterator
		$extensions = new \DirectoryIterator(DIR_EXTENSIONS);
		
		// Existing extensions (Used for deletion later)
		$existing = array();
		
		// Loop over the iterator
		foreach($extensions as $ext){
			if(!$ext->isDot() && $ext->isDir()){
				// It's a directory, collect info
				$name = $ext->getFilename();
				$path = $ext->getPathname().DS;
				
				// Add to existing array
				$existing[] = $name;
				
				if(!$registry->exists($name)){
					// Create the node
					$registry->register($path, $name);
				}
			}
		}
		
		// Delete non-existing entry's from the registry
		foreach($registry as $name => $value){
			if(!in_array($name, $existing))
				$registry->remove($name);
		}
	}
}