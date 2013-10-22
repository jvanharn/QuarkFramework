<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Bundles;

// Prevent individual file access
use Quark\Archive\Zip;
use Quark\Error;
use Quark\Exception;

if(!defined('DIR_BASE')) exit;

\Quark\import('Bundles.Bundle', 'Bundles.Repository');

// Define the bundle cache paths.
if(!defined('BUNDLE_CACHE_PATH'))
	define('BUNDLE_CACHE_PATH', DIR_TEMP.'bundle.repository.cache');
if(!defined('BUNDLE_LIST_PATH'))
	define('BUNDLE_LIST_PATH', DIR_DATA.'bundle.list');

/**
 * Bundles Class
 *
 * Defines the main bundle interaction class that allows for;
 *  - Finding Bundles in repositories
 *  - Installing and removing bundles from the current project
 *  - Resolving bundle dependencies
 *  - Updating bundles
 *  - Managing multiple bundle source repositories
 *  - Tracking of resources within bundles
 *  - Finding bundles that provide a given resource name
 * @package Quark\Bundles
 */
class Bundles {
	/** Resource type CSS */
	const RESOURCE_TYPE_CSS = 'css';

	/** Resource type JavaScript */
	const RESOURCE_TYPE_JS = 'js';

	/** Resource type Image */
	const RESOURCE_TYPE_IMAGE = 'image';

	/** Resource type Font */
	const RESOURCE_TYPE_FONT = 'font';

	/** Valid file extensions for Resource Type Image */
	const RESOURCE_IMAGE_EXTENSIONS = 'gif|png|jpg|jpeg|bmp|webp|tiff'; // !important: Does not include SVG because those can be both images as wel as fonts.

	/** Valid file extensions for Resource Type Font */
	const RESOURCE_FONT_EXTENSIONS = 'woff|otf|ttf'; // !important: Does not include SVG because those can be both images as wel as fonts.

	/** Install flag: Automatically install dependencies for bundle instead of generating exception. */
	const INSTALL_DEPENDENCIES = 1;

	/** @var Bundle[] List of installed bundles. */
	protected static $installed;

	/** @var array List of bundles on every remote server when last checked. (bundleId => array(repository => lastUpdateTimestamp)) */
	protected static $bundleListCache;

	/** @var array List of info about bundles on remote servers. */
	protected static $bundleInfoCache;

	/** @var array List of asset providing requests and their results. (Gets refreshed on every bundle install/remove/upgrade) */
	protected static $provideMatchingCache;

	/** @var bool Whether or not the list of installed bundles was modified. */
	private static $modified = false;

	/**
	 * Download and install a package from the first available repository.
	 * @param string $bundleId
	 * @param string $version
	 * @param int $flags
	 * @throws \Quark\Exception
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public static function install($bundleId, $version=null, $flags=0){
		if(!is_integer($flags))
			throw new \InvalidArgumentException('Argument $flags should be of type "integer", found "'.gettype($flags).'".');

		self::_invalidateMatchingCache(); // Reset matching cache

		$match = self::_findClosestMatching($bundleId, $version); // Find the repo and version we want

		// Get package info
		/*$sourceObj = Repository::GetBundleObject($match['repo'], $bundleId, $match['ver']);
		if($sourceObj === false)
			return false;*/

		// Download the package
		if($match !== false)
			$tmpPath = Repository::GetBundlePackage($match['repo'], $bundleId, $match['ver']);
		else return false;

		// Open the package
		\Quark\import('Archive.Zip');
		$archive = new Zip($tmpPath, Zip::MODE_READONLY);
		if(!$archive->exists('bundle.json'))
			throw new Exception('The downloaded package did not contain a bundle.json info file; aborted installation.');

		// Parse into object
		$bundle = LocalBundle::fromJSON($archive->extract('bundle.json'));

		// Check if id's match
		if($bundle['id'] != $bundleId)
			throw new Exception('Unable to install; the given $bundleId and the bundle id given inside the package did not match! Aborting installation.');

		// Check dependencies
		foreach($bundle['dependencies'] as $dependency => $version){
			if($dependency == $bundleId)
				throw new Exception('Bundle with id "'.$bundleId.'" has dependency onto itself.');
			if(!isset(self::$installed[$dependency])){
				if($flags & self::INSTALL_DEPENDENCIES)
					self::install($dependency, ($version == '*' ? null : $version));
				else
					throw new Exception('The package has a dependency on the package "".');
			}
		}

		// Unpack package in bundles dir
		$archive->extractAll($bundle['path']);
		unset($archive);

		// Add as installed package
		self::_loadInstalled();
		self::$installed[$bundleId] = $bundle;
		self::$modified = true;

		return true;
	}

	/**
	 * Remove/uninstall bundle from system.
	 * @param $bundleId
	 * @return bool
	 */
	public static function remove($bundleId){
		if(self::installed($bundleId)){ // Already loads installed list
			// Reset matching cache
			self::_invalidateMatchingCache();

			// Remove files
			$path = @realpath(self::$installed[$bundleId]['path']);
			if(is_string($path)){
				foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item){
					if($item->isFile()) unlink($item->getPathname());
					else rmdir($item->getPathname());
				}
				unlink($path);
			}

			// Remove from installed list.
			unset(self::$installed[$bundleId]);
			self::$modified = true;
		}else return false;
	}

	/**
	 * Check whether a bundle with the given Id is installed.
	 * @param string $bundleId
	 * @return bool
	 */
	public static function installed($bundleId){
		self::_loadInstalled();
		return isset(self::$installed[$bundleId]);
	}

	/**
	 * Get all the info about the bundle. (Always returns a RemoteBundle)
	 * @param string $bundleId The bundle you want info about.
	 * @param string $version The bundle version you want info about.
	 * @return RemoteBundle|bool
	 */
	public static function info($bundleId, $version=null){
		$match = self::_findClosestMatching($bundleId, $version);

		if($match !== false)
			return Repository::GetBundleObject($match['repo'], $bundleId, $match['ver']);
		else return false;
	}

	/**
	 * Get a bundle object of a /installed/ bundle. (Always returns a LocalBundle)
	 * @param string $bundleId
	 * @return LocalBundle|bool
	 */
	public static function get($bundleId){
		if(isset(self::$installed[$bundleId]))
			return self::$installed[$bundleId];
		else return false;
	}

	/**
	 * List all available bundles.
	 * @return array
	 */
	public static function listAll(){
		return array_keys(self::$bundleListCache);
	}


	/**
	 * Upgrades the given package to the latest known version. (Latest version given in the local repository cache)
	 * @param string $bundleId Identifier name of the bundle.
	 * @return bool
	 */
	public static function upgrade($bundleId){
		self::_invalidateMatchingCache(); // Reset matching cache
		// @todo Compare dependency changes between versions and suggest to remove unnecessary bundles.
	}

	/**
	 * Upgrade all installed bundles to the latest version.
	 */
	public static function upgradeAll(){
		foreach(self::$installed as $bundle){
			self::upgrade($bundle->id);
		}
	}

	/**
	 * Update the list with available bundles by getting the bundle list from all enabled repositories.
	 * @return bool
	 */
	public static function updateList(){
		// First get all listed repositories
		$repos = Repository::GetAvailableRepositories();

		// Then get all bundle lists for the enabled repos
		$lists = array();
		foreach($repos as $repo => $enabled){
			if($enabled)
				$lists[$repo] = Repository::GetBundleList($repo);
		}

		// Check if we got any
		if(empty($lists))
			return negative( Error::raiseWarning("Unable to get any bundle lists as there are no known repositories or all repositories were disabled.") );

		// Merge all the bundle lists
		self::$bundleListCache = array();
		foreach($lists as $repo => $bundles){
			foreach($bundles as $bundleId => $bundleInfo){
				// Check if bundle is already registred
				if(isset(self::$bundleListCache[$bundleId])){
					// New bundle
					self::$bundleListCache[$bundleId] = ($bundleInfo);
					unset(self::$bundleListCache[$bundleId]['versions']);
				}
				// Set the available versions
				self::$bundleListCache[$bundleId]['availability'][$repo] = $bundleInfo['versions'];
			}
		}

		// Flush those changes
		self::_flushCache();
	}

	/**
	 * Build a list of dependencies that would be required when installing the given bundle.
	 *
	 * Returns a list not only of the given bundle, but also the dependency's dependencies, etc.
	 * Also correctly sets the the (In total) required minimal/maximal version of each bundle.
	 * Notice : Will stop at the depth MAX_DEPENDENCY_DEPTH, which is normally set at 16.
	 * @param string $bundleId Bundle to resolve dependencies for.
	 * @throws Exception When the given bundleId doesn't exist.
	 * @throws \RuntimeException When one of the dependency bundles has a dependency that does not exist.
	 * @throws \RuntimeException When two different packages have conflicting dependency version requirements. (Thus the package cannot be installed)
	 * @return array Associative array in the form of: bundleId => requiredVersion
	 */
	public static function resolveDependencies($bundleId){
// @todo Obviously.............
	}

	/**
	 * Get the id of a bundle that is closest to the given description in the parameters and provides the given asset.
	 *
	 * This function will check all installed
	 * @param string $asset Name of an asset or resource that is required. (Ex. 'jquery.js')
	 * @param string|null $type The type of the asset required. Will try to determine the type of asset from the file's extension when empty. (Optional but recommended)
	 * @param string|array|null $version The *minimum* version of the package providing said asset OR an array where the first element is the compare function and the second the version string. (Optional)
	 * @param int $quality A variable reference where the quality of the match provided by this function is stored in. (Number from 0-2, where 0 is perfect/exact and 2 is similar)
	 * @return string Returns the Id of an *installed* bundle that provides the requested asset and meets the given requirements, or false if it was unable to satisfy the request.
	 */
	public static function provide($asset, $type=null, $version=null, &$quality=null){
		// @todo Implement bundle usage tracking so the interface can suggest to remove bundles that are no longer used.
		if(empty($type))
			$type = self::_findResourceType($asset);

		// Check match cache
		$matchId = hash('crc32b', $asset.$type.$version);
		if(isset(self::$provideMatchingCache[$matchId])){
			$quality = self::$provideMatchingCache[$matchId][1];
			return self::$provideMatchingCache[$matchId][0];
		}

		// Built list of matched resources per installed bundle
		self::_loadInstalled(); // Load installed bundles list
		$matches = array();
		if(!empty($version)){ // if version was set immediately filter out the incorrect versions
			foreach(self::$installed as $id => $bundle){
				if(check_version_rule($bundle->version, $version)){
					$current = self::_findApproximateMatchingAssets($id, $type, $asset);
					if(!empty($current))
						$matches[$id] = $current;
				}
			}
		}else{ // just do it without the version check
			foreach(self::$installed as $id => $bundle){
				$current = self::_findApproximateMatchingAssets($id, $type, $asset);
				if(!empty($current))
					$matches[$id] = $current;
			}
		}

		// When nothing was found cache empty result.
		if(empty($matches)){
			self::$provideMatchingCache[$matchId] = array(false, 2);
			$quality = 2;
			return false;
		}

		// Find the best match quality
		$best = 2;
		foreach($matches as $match){
			foreach($match as $grade){
				if($grade < $best)
					$best = $grade;
			}
		}

		// Filter by best available quality
		$graded = array();
		foreach($matches as $bundleId => $match){
			$current = array();
			foreach($match as $res => $grade){
				if($grade == $best)
					$current[] = $res;
			}
			if(!empty($current))
				$graded[$bundleId] = $current;
		}
		unset($matches);

		// Find best result by getting latest available bundle
		$result = false; // the current best
		$latest = '0'; // current latest version
		foreach($graded as $bundleId => $matches){
			if(uniform_version_compare(self::$installed[$bundleId]->version, $latest) > 0){
				$latest = self::$installed[$bundleId]->version;
				$result = $bundleId;
			}
		}

		// Cache the result
		self::$provideMatchingCache[$matchId] = array($result, $best);

		// Return result
		$quality = $best;
		return $best;
	}

	/**
	 * Get the public/web path to the requested resource/asset
	 * @param string $bundleId
	 * @param string $asset
	 * @param string|null $type
	 * @param string|null $version
	 * @return string
	 */
	public static function getResourcePath($bundleId, $asset, $type=null, $version=null){
		// @todo See the Bundles::provide todo note.

	}


	/**
	 * Find the type of asset.
	 * @param string $asset
	 * @throws \RuntimeException When unable to find resource type.
	 * @return string Asset type.
	 */
	public static function _findResourceType($asset){
		$asset = trim($asset, ' /\\.-_');
		if(stripos($asset, self::RESOURCE_TYPE_CSS) !== false)
			return self::RESOURCE_TYPE_CSS;
		else if(stripos($asset, self::RESOURCE_TYPE_JS) !== false)
			return self::RESOURCE_TYPE_JS;
		else if(substr($asset, 0, 3) == 'img' || substr($asset, 0, 5) == 'image' || stripos(self::RESOURCE_IMAGE_EXTENSIONS, end(explode('.', $asset))) !== false)
			return self::RESOURCE_TYPE_IMAGE;
		else if(substr($asset, 0, 4) == 'font' || stripos(self::RESOURCE_FONT_EXTENSIONS, end(explode('.', $asset))) !== false)
			return self::RESOURCE_TYPE_FONT;
		else
			throw new \RuntimeException('Unknown resource type detected. Unable to find resource type for "'.$asset.'".');
	}

	/**
	 * Find the closest matching/similarly named assets for the given bundle.
	 * @param string $bundleId
	 * @param string $type
	 * @param string $asset
	 * @return array Array of matches in the form of ['AssetName' => (int) MatchLevel] where MatchLevel is one of 0 => Exact Match, 1 => found basename in resource name, 2 => found basename with any possible versioning data removed.
	 */
	private static function _findApproximateMatchingAssets($bundleId, $type, $asset){
		// Split up the asset name
		$file = basename($asset);
		$name = filer_string($file, array(CONTAINS_ALPHANUMERIC, true)); // Filter $file and allow only letters and numbers of any case, when a non-allowed character is found, return everything you already had up until that point.
		$nameSize = strlen($name);

		$found = array();
		foreach(self::$installed[$bundleId]->resources[$type] as $resource => $properties){
			if($asset == $resource) // exact match
				$found[$resource] = 0;
			else if(stripos($resource, $file) !== false)
				$found[$resource] = 1;
			else if($nameSize > 4 && stripos($resource, $name) !== false)
				$found[$resource] = 2;
		}

		return $found;
	}

	/**
	 * Find the best available option for the given bundle with the given version.
	 * @param string $bundleId
	 * @param string|null $version
	 * @return array|bool
	 */
	private static function _findClosestMatching($bundleId, $version=null){
		self::_loadCache();

		if(!isset(self::$bundleListCache[$bundleId]))
			return false;

		if(empty($version)){
			// Find the latest available version in any repository
			$version = null;
			$repository = null;
			foreach(self::$bundleListCache[$bundleId] as $repo => $versions){
				foreach($versions as $current){
					if($version == null || uniform_version_compare($version, $current) > 0){
						$version = $current;
						$repository = $repo;
					}
				}
			}

			// Get that version
			if($version != null && $repository != null)
				return array('repo' => $repository, 'ver' => $version);
			else return false;
		}else{
			// Find a specific version
			$repository = null;
			foreach(self::$bundleListCache[$bundleId] as $repo => $versions){
				foreach($versions as $current){
					if(uniform_version_compare($version, $current) == 0){
						$version = $current; // Because we need the EXACT version string
						$repository = $repo;
						break;
					}
				}
			}

			// Get that version
			if($repository != null)
				return array('repo' => $repository, 'ver' => $version);
			else return false;
		}
	}

	/**
	 * Loads the list of currently installed bundles from cache.
	 * @access private
	 */
	private static function _loadInstalled(){
		if(self::$installed !== null)
			return;

		// Register shutdown functions to flush the Caches if they are modified
		register_shutdown_function(function(){
			if(self::$modified)
				self::_flushInstalled();
		});

		// Load
		if(file_exists(BUNDLE_LIST_PATH)){
			if(!!@is_readable(BUNDLE_LIST_PATH)){
				$list = unserialize(file_get_contents(BUNDLE_LIST_PATH));
				self::$installed = $list;
			}else{ // Non lethal, should not be catchable.
				self::$installed = array();
				Error::raiseError('The bundle list file is not readable for me, please make it readable and writable for the application to make sure the application continues to work as expected.');
			}
		}else self::$installed = array();
	}

	/**
	 * Flushes the list of currently installed bundles to disk.
	 *
	 * Uses php's serialize function rather than JSON, because serialize is quicker with reading.
	 * @access private
	 */
	private static function _flushInstalled(){
		if(!@is_writable(BUNDLE_LIST_PATH))
			Error::raiseFatalError('Installed bundles list/registry is not writable! Please make it writable and readable for this application as not doing this might result in unexpected or erratic behaviour!');
		file_put_contents(BUNDLE_LIST_PATH, serialize(self::$installed));
	}

	/**
	 * Before using the bundle repo cache, the cache must be loaded.
	 * @access private
	 */
	private static function _loadCache(){
		if(self::$bundleListCache == null && self::$bundleInfoCache == null){
			// Load list and bundle cache
			if(!!@is_readable(BUNDLE_CACHE_PATH)){
				$cache = unserialize(file_get_contents(BUNDLE_CACHE_PATH));
				self::$bundleInfoCache = $cache['info'];
				self::$bundleListCache = $cache['list'];
				self::$provideMatchingCache = $cache['matches'];
			}else{
				self::$bundleInfoCache = array();
				self::$bundleListCache = array();
				self::$provideMatchingCache = array();
				Error::raiseWarning('Please update the cache at least once, as it hasn\'t been loaded yet.');
			}
		}
	}

	private static function _invalidateMatchingCache(){
		self::$provideMatchingCache = array();
	}

	/**
	 * Flushes current caches to the cache file if writable.
	 * @access private
	 */
	private static function _flushCache(){
		if(!@is_writable(BUNDLE_CACHE_PATH)) return;
		file_put_contents(BUNDLE_CACHE_PATH, serialize(array(
			'list' => self::$bundleListCache,
			'info' => self::$bundleInfoCache
		)));
	}
}