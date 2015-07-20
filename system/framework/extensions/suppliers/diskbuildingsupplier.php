<?php
/**
 * Extensions Directory Scanner (Extension Supplier)
 * 
 * Scans the extension directory and notifies the system of the existence of these extensions.
 * 
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		16 december 2012
 * @copyright	Copyright (C) 2012-2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Extensions\Suppliers;

// Import namespace
use Quark\Extensions\BuildingSupplier;
use Quark\Extensions\Extensions;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Get available extensions by scanning the extensions directory.
 */
class DiskBuildingSupplier implements BuildingSupplier {
	/**
	 * Current availability of the supplier.
	 *
	 * Whether or not this supplier is available or able to fill the extension
	 * registry at the moment. If it is always in this state, just return true.
	 * @return boolean
	 */
	public function available() {
		if(!is_dir(DIR_EXTENSIONS))
			throw new \RuntimeException('The Extension directory as defined in the DIR_EXTENSIONS constant does not exist!');
		return true;
	}

	/**
	 * Fills the given Extension Registry with the available extensions.
	 *
	 * This function should fill the extension registry with extensions that are
	 * currently available. This means that it should also contain extensions with statuses
	 * like enabled, disabled, new etc.
	 * @param Extensions $registry Current (empty) registry to fill.
	 * @return void
	 */
	public function fill(Extensions $registry) {
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

	/**
	 * Update an already filled registry.
	 * @param Extensions $registry
	 * @return void
	 */
	public function update(Extensions $registry) {
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