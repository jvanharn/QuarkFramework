<?php
/**
 * The resources/asset management class.
 * 
 * @package		Quark-Framework
 * @version		$Id: resource.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		March 7, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Document;
use \Quark\Bundles\Bundles;
use Quark\Error;
use Quark\Protocols\HTTP\IMutableResponse;
use Quark\System\Router\IRoutableRequest;
use Quark\System\Router\Router;
use Quark\System\Router\Route;

\Quark\import('Bundles');

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Controls resources or assets within the system so no duplication occurs.
 * 
 * For example: If you need jQuery, the whole system will be supplied with one jquery file, which will only be included once.
 * This way no conflicts occur between libraries, and everything will stay in sync with their latest versions.
 * @todo Future Functionality may include, auto-compile and cache for SCSS, SASS and LESS stylesheets, including fonts via autogenerated CSS Style tags and/or CSS buffer class.
 */
class ResourceManager {
	/**
	 * @var array Cache of all resolved/referenced assets by (hashed) query.
	 */
	protected static $cache;

	/**
	 * Header object to which applicable dependency's will be added.
	 * @var Headers
	 */
	protected $headers;
	
	/**
	 * Map of assets that have been referenced/linked to the current documents. (Used for detecting conflicts)
	 * @var array
	 */
	protected $referenced = array();

	/**
	 * @var Router The router to use for building resource/bundle asset urls.
	 */
	protected $router;

	/**
	 * @param Headers $headers
	 * @param Router $router The router to use for building resource/bundle asset urls
	 */
	public function __construct(Headers $headers, Router $router=null){
		$this->headers = $headers;
        try {
            $this->router = empty($router) ? Router::getInstance() : $router;
        }catch(\OutOfBoundsException $e){
            throw new \UnexpectedValueException('I tried to get the default instance of the global router object, but I failed as it was seemingly not yet created. Please ensure it is, or give me (ResourceManager) an explicit instance of a n router, so I can resolve the required assets for this page.');
        }
	}

	/**
	 * Set the router this resource provider should use for building the asset urls.
	 * @param Router $router Router to use.
	 */
	public function setRouter(Router $router){
		$this->router = $router;
	}

	/**
	 * Reference an asset to the given Document instance.
	 *
	 * This will add a reference, pointing to the given resource, to the document, if possible (So for Javascript, CSS
     * and Font files) or return the URI to the resource. (The resource will always be registered as "used" or
     * "referenced")
	 * @param string $name The name/path of the resource or asset relative to the bundle's base directory. (E.g. jquery.js OR js/bootstrap.js OR css/normalize.css)
	 * @param string|null $type One of the Bundles::RESOURCE_TYPE_* constants. (Analyzes the given name if not set)
	 * @param string|null $bundle The identifier of the bundle which contains said asset.
	 * @param string|array|null $version The (standard formatted) version string that defines the limit's on the version requirements. (E.g. array('>=', '0.1.3') OR '0.1.3' OR array('[..]', array('0.1', '0.5')))
	 * @param bool $forceURI When this is set to true, instead of adding the header tag to the document in the a-fore mentioned situations, it will always just return the URI on success or false on failure.
	 * @return string|bool Returns true for Font, CSS and Javascript files, and an URI to the file for the rest. The exception to this situation explained in $forceURI.
	 */
	public function reference($name, $type=null, $bundle=null, $version=null, $forceURI=false){
		// Check already referenced assets
		if(isset($this->referenced[strtolower($name)]))
			return true;

		// Resolve type
		if(empty($type))
			$type = Bundles::_findResourceType($name);

		// Load cache
		self::_loadCache();

		// Check the cache
		$hash = hash('crc32b', $name.$type.$version);
		if(isset(self::$cache[$hash]))
			$bundle = self::$cache[$hash];

		// If the bundleid is set..
		elseif(!empty($bundle)){
			// Check if the given bundle actually exists
			if(Bundles::installed($bundle)){
				$object = Bundles::get($bundle);
			}else{
				Error::raiseWarning('The given bundle with id "'.$bundle.'" doesn\'t exist.');
				return false;
			}

		// BundleId not set
		}else{
			// ...so use provide to get the best bundle for the job.
			$bundle = Bundles::provide($name, $type, $version);
			if(empty($bundle))
				return false;

			// Save in cache
			self::$cache[$hash] = $bundle;
			// @todo make some sort of cache flush method, as this way the cache doesn't really have much use.
		}

		// Check if the given bundle has the actual resource/asset
		// ... and find it's name in the best providing bundle.
		// @todo Runtime Conflict Checking: Make sure that conflicts get checked with already referenced resources (But only for Javascript and CSS)
		if(!isset($object->resources[$name])){
			$assets = Bundles::_findApproximateMatchingAssets($bundle, $type, $name);// otherwise look for similar
			$asset = false;
			$best = 2;
			foreach($assets as $current => $quality){
				if($quality < $best)
					$asset = $current;
			}
			if($asset === false)
				return false;
		}else $asset = $name;

		// Save the made reference
		$this->referenced[strtolower($name)] = array(
			'bundle' => $bundle,
			'hash' => $hash,
			'asset' => $asset,
			'search' => array($name, $type, $version)
		);

		// Get the (public) path for the given asset relative to the server root
		$url = $this->router->build(
			BundleResourceRoute::getName(),
			array($bundle, $asset)
		); // @todo: Use a Document/ResourceManager assigned Router object instead of the default instance.

		// Check if we could build an URI
		if($url === false)
			Error::raiseWarning('Unable to build an URL for the bundle "'.$bundle.'" and asset "'.$asset.'". Maybe you forgot to include the '.BundleResourceRoute::getName().' route?');

		// Check if force URL is activated.
		if($forceURI === true)
			return $url;

		// Add the appropriate header tags and return
		switch($type){
			case Bundles::RESOURCE_TYPE_CSS:
				$this->headers->add(Headers::LINK, array('href' => $url));
				return true;
			case Bundles::RESOURCE_TYPE_JS:
				$this->headers->add(Headers::SCRIPT, array('src' => $url));
				return true;
			case Bundles::RESOURCE_TYPE_FONT:
				Error::raiseFatalError('Font\'s cannot be auto included (yet), failed to reference the asset "'.$asset.'" to the linked Document instance.');
				break;
			default:
				return $url;
		}
	}

	/**
	 * Reference a required asset to the given Document instance.
	 *
	 * Exactly the same behaviour as reference, except that this method throws an RuntimeException when the referencing failed.
	 * @param string $name The name/path of the resource or asset relative to the bundle's base directory. (E.g. jquery.js OR js/bootstrap.js OR css/normalize.css)
	 * @param string|null $type One of the Bundles::RESOURCE_TYPE_* constants. (Analyzes the given name if not set)
	 * @param string|null $bundle The identifier of the bundle which contains said asset.
	 * @param string|array|null $version The (standard formatted) version string that defines the limit's on the version requirements. (E.g. array('>=', '0.1.3') OR '0.1.3' OR array('[..]', array('0.1', '0.5')))
	 * @param bool $forceURI When this is set to true, instead of adding the header tag to the document in the a-fore mentioned situations, it will always just return the URI on success or false on failure.
	 * @throws \RuntimeException When the referencing failed.
	 * @return string|bool Returns true for Font, CSS and Javascript files, and an URI to the file for the rest. The exception to this situation explained in $forceURI.
	 * @see ResourceManager::reference
	 */
	public function required($name, $type=null, $bundle=null, $version=null, $forceURI=false){
		$result = $this->reference($name, $type, $bundle, $version, $forceURI);
		if($result === false)
			throw new \RuntimeException('Unable to reference required resource "'.$name.'".');
		return $result;
	}
	
	/**
	 * Check if the given resource is already referenced.
	 * @param string $name The name/path of the resource or asset relative to the bundle's base directory. (E.g. jquery.js OR js/bootstrap.js OR css/normalize.css)
	 * @param string|null $type One of the Bundles::RESOURCE_TYPE_* constants. (Analyzes the given name if not set)
	 * @return bool Whether or not the given asset is already referred to in the document.
	 */
	public function referenced($name, $type=null){
		if(empty($type))
			$type = Bundles::_findResourceType($name);

		if(isset($this->referenced[$name]))
			return true;

		foreach($this->referenced as $ref){
			if($ref['search'][0] == $name && $ref['search'][1] == $type)
				return true;
		}
		return false;
	}

	/**
	 * Get info about an already referenced asset/resource.
	 * @param string $name The name/path of the resource or asset relative to the bundle's base directory. (E.g. jquery.js OR js/bootstrap.js OR css/normalize.css)
	 * @param string|null $type One of the Bundles::RESOURCE_TYPE_* constants. (Analyzes the given name if not set)
	 * @return array|bool An array with ['bundle' => bundle id, 'hash' => internal cache hash, 'asset' => the resolved full asset name and path, 'search' => array(original asset name used for the search/reference call, resolved/given asset type, when applicable the searched/required version.)
	 */
	public function info($name, $type=null){
		if(empty($type))
			$type = Bundles::_findResourceType($name);

		if(isset($this->referenced[$name]))
			return $this->referenced[$name];

		foreach($this->referenced as $ref){
			if($ref['search'][0] == $name && $ref['search'][1] == $type)
				return $this->referenced[$name];
		}
		return false;
	}
	
	/**
	 * Search for the given asset in all available/known bundles.
	 *
	 * Notice: This method is an alias for Bundles::providers
	 * @param string $name The name/path of the resource or asset relative to the bundle's base directory. (E.g. jquery.js OR js/bootstrap.js OR css/normalize.css)
	 * @param string|null $type One of the Bundles::RESOURCE_TYPE_* constants. (Analyzes the given name if not set)
	 * @param string|array|null $version The (standard formatted) version string that defines the limit's on the version requirements. (E.g. array('>=', '0.1.3') OR '0.1.3' OR array('[..]', array('0.1', '0.5')))
	 * @return array Array with the identifiers of all the bundles that provide the given asset and qualify for the given search parameters.
	 * @see Bundles::providers()
	 */
	public function search($name, $type=null, $version=null){
		return Bundles::providers($name, $type, $version);
	}

	/**
	 * Check whether the given asset/type/bundle combination is a valid referable asset. (And thus can be used with reference)
	 * @param string $name The name/path of the resource or asset relative to the bundle's base directory. (E.g. jquery.js OR js/bootstrap.js OR css/normalize.css)
	 * @param string|null $type One of the Bundles::RESOURCE_TYPE_* constants. (Analyzes the given name if not set)
	 * @param string|null $bundle The identifier of the bundle which contains said asset.
	 * @param string|array|null $version The (standard formatted) version string that defines the limit's on the version requirements. (E.g. array('>=', '0.1.3') OR '0.1.3' OR array('[..]', array('0.1', '0.5')))
	 * @return bool Whether or not the given asset is available/referable.
	 */
	public static function valid($name, $type=null, $bundle=null, $version=null){
		// @todo Stub: Implement this.
	}

	private static function _loadCache(){
		self::$cache = array();
		// @todo Stub
	}
}

/**
 * Alias to keep the class resolver happy
 * @ignore
 * @access private
 */
class_alias('\Quark\Document\ResourceManager', '\Quark\Document\Resources');

/**
 * Static Resource/Asset Route.
 *
 * Mainly builds URL's to specified (bundle) resources on disk.
 * When running in PHP server mode, this route will also serve (bundle) resources to the client.
 */
class BundleResourceRoute implements Route {
	/**
	 * @var string The base URL with which urls get parsed and built.
	 */
	protected $base;

	/**
	 * @var string The path relative to the base, where the bundles reside. (Defaults to DIR_BUNDLES without the DIR_BASE)
	 */
	protected $bundle_path = '';

	/**
	 * @param string $bundle_path The path relative to the base, where the bundles reside. (Defaults to DIR_BUNDLES without the DIR_BASE)
	 * @throws \RuntimeException When it was unable to autodetermine the public bundles base path.
	 */
	public function __construct($bundle_path=null){
		if(is_string($bundle_path) && !empty($bundle_path))
			$this->bundle_path = trim($bundle_path, '/').'/';
		else if(strncmp(DIR_BASE, DIR_BUNDLES, strlen(DIR_BASE)) === 0) // This checks that the bundles dir is inside the base dir.
			$this->bundle_path = substr(DIR_BUNDLES, strlen(DIR_BASE));
		else // We really need help with this.
			throw new \RuntimeException('In your application\'s directory structure I have not been able to successfully determine the public path of the bundles directory. Please provide this path to your BundleResourceRoute instance to ensure correct operation.');
	}

	/**
	 * Get the routes Fully Qualified name (Class name with complete namespace information).
	 * @return string
	 */
	public static function getName() { return __CLASS__; }

	/**
	 * Get the available parameters for the url builder.
	 * @return array Associative array of parameter indexes and descriptions as value.
	 */
	public function parameters() { return array(); }

	/**
	 * Gives the base url of the Application to which this route was bound.
	 * @param string $url URL to the base application.
	 */
	public function setBase($url) { $this->base = $url; }

	/**
	 * Build a URI pointing to this resource/route with the given params.
	 * @param array $params Parameters.
	 * @param boolean $optimized Whether or not the builder should try to go for compatible url's (E.g. index.php?name=controller&method=methodname or optimized urls like /controller/methodname/
	 * @throws \InvalidArgumentException
	 * @return string The URI that leads to the specified location.
	 */
	public function build(array $params, $optimized = false) {
		if(empty($params) && count($params) == 2)
			throw new \InvalidArgumentException('Argument $params has to be set and consist of 2 parts (bundle id, resource name).');

		return $this->base.$this->bundle_path.urlencode($params[0]).'/'.$params[1];
	}

	/**
	 * Checks if this route can route the given request.
	 * @param IRoutableRequest $request
	 * @return bool
	 */
	public function routable(IRoutableRequest $request) {
		// TODO: Implement routable() method.
	}

	/**
	 * Activate this route and load the applicable resource.
	 *
	 * This function may ONLY be called after positive feedback (e.g. true) from the routable method.
	 * @param IRoutableRequest $request {@see Route::routable()}
	 * @param IMutableResponse $response The object where the response should be written to.
	 * @return void
	 */
	public function route(IRoutableRequest $request, IMutableResponse $response) {
		// TODO: Implement route() method.
	}
}