<?php
/**
 * The resources/asset management class.
 * 
 * @package		Quark-Framework
 * @version		$Id: resources.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		March 7, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Document;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Controls resources or assets within the system so no duplication occurs.
 * 
 * For example: If you need jQuery, the whole system will be supplied with one jquery file, which will only be included once.
 * This way no conflicts occur between libraries, and everything will stay in sync with their latest versions.
 * 
 * @property-read AssetRepositoryRegistry $repositories Registry of Repository Names and their URLs that keeps it's state over sessions.
 */
class Assets implements \Quark\Util\Singleton{
	use Quark\Util\baseSingleton;
	
	/**
	 * Registry of key value pairs that keeps it's state over sessions.
	 * @var AssetRepositoryRegistry
	 */
	protected $repositories;
	
	/**
	 * Installed asset libraries.
	 * @var array
	 */
	protected $installed = array();
	
	/**
	 * Map of assets that have been referenced/linked to documents, grouped by hashcodes of those Document objects.
	 * @var array
	 */
	protected $referenced = array();
	
	/**
	 * Instantiate the resource manager
	 */
	public function __construct(){
		$this->repositories = new AssetRepositoryRegistry();
	}
	
	/**
	 * Reference a asset to the given Document instance.
	 * @param string $name Name of the asset to reference.
	 * @param \Quark\Document\Document $document Document that will be refering to the given asset.
	 */
	public function reference($name, Document $document){
		
	}
	
	/**
	 * Get info about an asset library.
	 * @param string $name
	 */
	public function info($name){
		
	}
	
	/**
	 * Search for the given asset library in all available repositories.
	 * @param string $name
	 */
	public function search($name){
		
	}
	
	/**
	 * Install the given asset library.
	 * @param string $name
	 */
	public function install($name){
		
	}
	
	/**
	 * Remove a asset library with the given name.
	 * @param string $name
	 */
	public function remove($name){
		
	}
	
	/**
	 * Check whether the given asset is available for referencing.
	 * @param string $name
	 */
	public function available($name){
		
	}
	
	/**
	 * Magic method implementation for read only access to the AssetRepositoryRegistry and info about assets.
	 * @param string $key
	 * @return mixed
	 * @access private
	 */
	public function __get($key){
		if($key == 'repositories' || $key == 'repos')
			return $this->repositories;
		else if(isset($this->installed[$key]))
			return $this->info($key);
	}
}

class AssetRepositoryRegistry extends \Quark\Util\Registry {
	protected function validValue($value){
		return (is_string($name) && !empty($name));
	}
}