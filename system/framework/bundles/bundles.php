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
use FilesystemIterator;
use Quark\Archive\Zip;
use Quark\Error;
use Quark\Exception;
use Quark\Util\Type\HttpException;
use RecursiveIteratorIterator;
use SplFileInfo;

if(!defined('DIR_BASE')) exit;

\Quark\import('Bundles.Bundle', 'Bundles.Repository', 'Util.Helpers');

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
		if($bundle->id != $bundleId)
			throw new Exception('Unable to install; the given $bundleId and the bundle id given inside the package did not match! Aborting installation.');

		// Check dependencies
		$dependencies = self::resolveDependencies($bundleId);
		foreach($dependencies as $dependency => $version){
			if($dependency == $bundleId)
				throw new Exception('Bundle with id "'.$bundleId.'" has dependency onto itself.');
			if(!isset(self::$installed[$dependency])){
				if($flags & self::INSTALL_DEPENDENCIES){
					// install the dependency
					self::install($dependency, ($version == '*' ? null : $version));
				}else
					throw new Exception('The package has a dependency on the package "".');
			}
		}

		// Unpack package in bundles dir
		$archive->extractAll($bundle->path);
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
					/** @var $item SplFileInfo */
					if($item->isFile()) unlink($item->getPathname());
					else rmdir($item->getPathname());
				}
				unlink($path);
			}

			// Remove from installed list.
			unset(self::$installed[$bundleId]);
			self::$modified = true;

			return true;
		}
		return false;
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
	 * @return LocalBundle
	 */
	public static function get($bundleId){
		if(isset(self::$installed[$bundleId]))
			return self::$installed[$bundleId];
		else return null;
	}

	/**
	 * List all available bundles.
	 *
	 * (This requires {@link Bundles::updateList()} to be called at least once)
	 * @return array
	 */
	public static function listAvailable(){
		self::_loadCache();
		return array_keys(self::$bundleListCache);
	}

	/**
	 * List all installed bundles.
	 * @return array
	 */
	public static function listInstalled(){
		self::_loadInstalled();
		return array_keys(self::$installed);
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
	 *
	 * This method downloads the list with all *available* bundles from the repositories that you have registered.
	 * If you have not changed those they will be set to the default Quark Framework official repositories.
	 * Please beware that this requires an active and working internet connection.
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
		if(empty(self::$bundleListCache))
			self::$bundleListCache = array();
		self::$bundleInfoCache = array();
		foreach($lists as $repo => $bundles){
			foreach($bundles as $bundleId => $bundleInfo){
				// update the last update time in the list
				if(isset(self::$bundleListCache[$bundleId]))
					self::$bundleListCache[$bundleId][$repo] = time();
				else
					self::$bundleListCache[$bundleId] = array($repo => time());

				// Check if bundle is already registered
				if(!isset(self::$bundleInfoCache[$bundleId])){
					// New bundle
					self::$bundleInfoCache[$bundleId] = ($bundleInfo); // force copy
					self::$bundleInfoCache[$bundleId]['availability'] = array();
					unset(self::$bundleInfoCache[$bundleId]['versions']);
				}

				// Set the available versions
				self::$bundleInfoCache[$bundleId]['availability'][$repo] = $bundleInfo['versions'];
			}
		}

		// Flush those changes
		self::_flushCache();

		return true;
	}

	/**
	 * Scan the bundle directory for (already) installed/available bundles, and make them referable.
	 * @param bool $install_dependencies Whether or not to automatically install any dependencies of the available/installed/local bundles.
	 * @throws \Quark\Exception
	 * @return void
	 */
	public static function scan($install_dependencies=true){
		foreach(glob(DIR_BUNDLES.'*/bundle.json') as $path){
			$bundle = substr($path, strlen(DIR_BUNDLES), -12);

			// Isn't installed yet: do a local install.
			if(!isset(self::$installed[$bundle])){
				// Parse into LocalBundle
				$object = LocalBundle::fromJSON(file_get_contents($path));

				// Check if id's match
				if($object->id != $bundle)
					throw new Exception('Unable to install; the given $bundleId and the bundle id given inside the package did not match! Aborting installation.');

				// Check dependencies
				if($install_dependencies == true){
					try {
						$dependencies = self::directlyResolveDependencies($object->dependencies);
						foreach($dependencies as $dependency => $version){
							if($dependency == $bundle)
								throw new Exception('Bundle with id "'.$bundle.'" has dependency onto itself.');
							if(!isset(self::$installed[$dependency])){
								// install the dependency
								try {
									self::install($dependency, ($version == '*' ? null : $version));
								}catch(\Exception $e){
									Error::raiseWarning('Unable to install bundle "'.$bundle.'" because it\'s dependency "'.$dependency.'" could not be installed/resolved. ('.$e->getMessage().')');
									continue 2;
								}
							}
						}
					}catch(\Exception $e){
						Error::raiseWarning('Unable to install bundle "'.$bundle.'" because one of it\'s dependencies could not be resolved within the current known repositories, try to update the repository list or check whether the dependencies for this bundle were mispelled. ('.$e->getMessage().')');
					}
				}

				// Add as installed package
				//self::_loadInstalled(); // @todo ???? what??? this cant be correct..
				self::$installed[$bundle] = $object;
				self::$modified = true;
			}
		}

        // @todo may be incorrectly placed here, but it seems to only work with this here.
        self::_flushInstalled();
	}

	/**
	 * Get the available versions for a bundle.
	 * @param string $bundleId
	 * @return array Version => [Repository, ...]
	 */
	public static function availableVersions($bundleId){
		$result = array();
		foreach(self::$bundleListCache[$bundleId]['availability'] as $repo => $versions){
			foreach($versions as $version){
				if(isset($result[$version]))
					$result[$version] = array($repo);
				else
					array_push($result[$version], $repo);
			}
		}
		return $result;
	}

	/**
	 * Build a list of dependencies that would be required when installing the given bundle.
	 *
	 * Returns a list not only of the given bundle, but also the dependency's dependencies, etc.
	 * Also correctly sets the the (In total) required minimal/maximal version of each bundle.
	 * Notice : Will stop at the depth MAX_DEPENDENCY_DEPTH, which is normally set at 16.
	 * @param string $bundleId Bundle to resolve dependencies for.
	 * @throws \Exception When the given bundleId doesn't exist.
	 * @throws \RuntimeException When one of the dependency bundles has a dependency that does not exist.
	 * @throws \RuntimeException When two different packages have conflicting dependency version requirements. (Thus the package cannot be installed)
	 * @return array Associative array in the form of: bundleId => requiredVersion
	 */
	public static function resolveDependencies($bundleId){
		return self::_resolveBundleDependencies($bundleId, array());
	}

	/**
	 * Build a list of dependencies that would be required when installing the given bundle.
	 *
	 * Returns a list not only of the given bundle, but also the dependency's dependencies, etc.
	 * Also correctly sets the the (In total) required minimal/maximal version of each bundle.
	 * Notice : Will stop at the depth MAX_DEPENDENCY_DEPTH, which is normally set at 16.
	 * @param array $dependencies Basic dependencies to resolve to resolve dependencies for.
	 * @throws \RuntimeException When one of the dependency bundles has a dependency that does not exist.
	 * @throws \RuntimeException When two different packages have conflicting dependency version requirements. (Thus the package cannot be installed)
	 * @return array Associative array in the form of: bundleId => requiredVersion
	 */
	public static function directlyResolveDependencies(array $dependencies){
		$result = array();
		foreach($dependencies as $dependency => $version){
			$result[$dependency] = $version;
			$result = self::_resolveBundleDependencies($dependency, $result);
		}
		return $result;
	}

	/**
	 * Get the id of a bundle that is closest to the given description in the parameters and provides the given asset.
	 * Important: This method's results get cached /across requests/ to speed things up a bit.
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

		// Get the (graded) matched bundles that provide the searched for asset
		$matches = self::providers($asset, $type, $version);

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
		return $result;
	}

	/**
	 * Get a list of all the installed/local bundles that provide the requested asset. (This method's results /DO NOT/ get cached.)
	 * @param string $asset Name of an asset or resource that is required. (Ex. 'jquery.js')
	 * @param string|null $type The type of the asset required. Will try to determine the type of asset from the file's extension when empty. (Optional but recommended)
	 * @param string|array|null $version The *minimum* version of the package providing said asset OR an array where the first element is the compare function and the second the version string. (Optional)
	 * @return array An array containing: [matched bundle => ['assetname' => match quality (int) 0-2] ]
	 */
	public static function providers($asset, $type=null, $version=null){
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
		return $matches;
	}

	#region Private Helpers
	/**
	 * Self-calling function, contains the actual logic behind Bundles::resolveDependencies.
	 *
	 * @param string $bundleId
	 * @param array $result
	 * @throws \RuntimeException
	 * @return array
	 *
	 * @access private
	 */
	private static function _resolveBundleDependencies($bundleId, $result){
		$info = self::info($bundleId);

		// @todo dunno what I did here. It should be a remotebundle? Why do I check this?
		var_dump($info);
		if(!is_array($info))
			throw new \RuntimeException('The given bundle id "'.$bundleId.'" could not be resolved; the bundle is not known in any of my repositories, thus I could not resolve any dependencies for it.');

		foreach($info->dependencies as $dependency => $version){
			if(isset($result[$dependency]))
				$result[$dependency] = merge_version_rules($result[$dependency], $version);
			else{
				$result[$dependency] = $version;
				$result = self::_resolveBundleDependencies($bundleId, $result);// @notice To make sure we don't get stuck in an infinite loop; only call this in the else.
			}
		}
		return $result;
	}

	/**
	 * Find the type of asset.
	 *
	 * @param string $asset
	 * @throws \RuntimeException When unable to find resource type.
	 * @return string Asset type.
	 *
	 * @access private
	 * @ignore
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
	 *
	 * @param string $bundleId
	 * @param string $type
	 * @param string $asset
	 * @return array Array of matches in the form of ['AssetName' => (int) MatchLevel] where MatchLevel is one of 0 => Exact Match, 1 => found basename in resource name, 2 => found basename with any possible versioning data removed.
	 *
	 * @access private
	 * @ignore
	 */
	public static function _findApproximateMatchingAssets($bundleId, $type, $asset){
		// Split up the asset name
		$file = basename($asset);
		$name = \Quark\Filter\filter_string($file, array('chars' => array(CONTAINS_ALPHANUMERIC, true))); // Filter $file and allow only letters and numbers of any case, when a non-allowed character is found, return everything you already had up until that point.
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
	 *
	 * @param string $bundleId
	 * @param string|null $version Version rule. (Defaults to $version or higher)
	 * @return array|bool
	 *
	 * @access private
	 * @ignore
	 */
	private static function _findClosestMatching($bundleId, $version=null){
		self::_loadCache();

		if(!isset(self::$bundleListCache[$bundleId]))
			return false;

		$repositories = null;
		$versions = self::availableVersions($bundleId);

		if(empty($version)){
			// Find the latest available version in any repository
			$latest = null;
			foreach($versions as $current => $repos){
				if($latest == null || uniform_version_compare($latest, $current) > 0){
					$latest = $current;
					$repositories = $repos;
				}
			}
		}else{
			// Find a version that meets the given version rule
			$latest = '0';
			foreach($versions as $current => $repos){
				if(check_version_rule($current, $version) && uniform_version_compare($latest, $current) > 0){
					$latest = $current;
					$repositories = $repos;
					break;
				}
			}
		}

		// Get that version
		if($repositories != null)
			return array('repo' => $repositories[0], 'ver' => $latest);
		else return false;
	}

	#endregion

	#region Caching Methods
    /**
     * Checks if the bundle list file is writable.
     * @return bool
     */
    public static function cacheWritable(){
        return @is_writable(dirname(BUNDLE_LIST_PATH));
    }

	/**
	 * Loads the list of currently installed bundles from cache.
	 * @access private
	 * @ignore
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
	 * @ignore
	 */
	private static function _flushInstalled(){
		if(!@is_writable(dirname(BUNDLE_LIST_PATH)))
			Error::raiseFatalError('Installed bundles list/registry is not writable! Please make it writable and readable for this application as not doing this might result in unexpected or erratic behaviour!');
		file_put_contents(BUNDLE_LIST_PATH, serialize(self::$installed));
	}

	/**
	 * Before using the bundle repo cache, the cache must be loaded.
	 * @access private
	 * @ignore
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

	/**
	 * Does what it says on the box. Clears the matching cache.
	 * @access private
	 * @ignore
	 */
	private static function _invalidateMatchingCache(){
		self::$provideMatchingCache = array();
	}

	/**
	 * Flushes current caches to the cache file if writable.
	 * @access private
	 * @ignore
	 */
	private static function _flushCache(){
		if(!@is_writable(dirname(BUNDLE_CACHE_PATH))) return;
		file_put_contents(BUNDLE_CACHE_PATH, serialize(array(
			'list' => self::$bundleListCache,
			'info' => self::$bundleInfoCache
		)));
	}

	/**
	 * Empties all repository caches.
	 */
	public static function _resetCache(){
		if(!@is_writable(dirname(BUNDLE_CACHE_PATH))) return;
		unlink(BUNDLE_CACHE_PATH);
	}

	/**
	 * Resets the cache of INSTALLED BUNDLES. Use with caution!
	 *
	 * If you want to reload the cache use {@see \Quark\Bundles\Bundles::scan} afterwards.
	 */
	public static function _resetInstalledList(){
		if(!@is_writable(dirname(BUNDLE_LIST_PATH))) return;
		if(is_file(BUNDLE_LIST_PATH)) unlink(BUNDLE_LIST_PATH);
	}
	#endregion
}