<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		May 09, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Bundles;

use	Quark\Util\PropertyObject,
	Quark\Filter;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

\Quark\import('framework.filter.filter', true);

/**
 * Defines a PageTree CMS resource or library bundle.
 * @package Quark\Bundles
 */
abstract class Bundle extends PropertyObject {
	/**
	 * @var string Internal bundle identifier.
	 */
	public $id;

	/**
	 * @var string Full name of the bundle. (English)
	 */
	public $name;

	/**
	 * @var string Version of the bundle. (In the Quark Versioning format)
	 */
	public $version;

	/**
	 * @var string Description of the bundle, and what it provides to the user.
	 */
	public $description;

	/**
	 * Multidimensional array containing the resource paths and their attributes;
	 * @example [
	 *   'relative/path/to/file.js' => [
	 *     'type' => 'javascript', // The type of resource this is (Choose one of 'javascript', 'css', 'font', 'image')
	 *     'minified' => false // Whether or not this file is already minified/packed
	 *     'aliases' => ['jquery.js'] // Supported by all types of resources: A simple alias for this file.
	 *     // !IMPORTANT! When adding aliases realise that adding the same aliases for two files of THE SAME TYPE are
	 *     // allowed, and the best version for the current situation will be chosen to be returned when asked for the
	 *     // resource. Ergo: jquery.js are aliases for both 'jquery-2.0.js' and 'jquery-2.0.min.js', the system is in
	 *     // dev mode, and the min.js version has the flag minified, the other does not. In this case the normal
	 *     // unminified version will be chosen because of it's unminified state and the framework's dev mode. Say that the general inquiry is done
	 *     // for a bundle providing 'jquery.js' and another package is also providing 'jquery-1.7.js' which has the
	 *     // same relevant flags for the file as the 2.0 version, they are alphabetically ordered, and the last one is
	 *     // taken. Take this into account when aliasing your files!
	 *   ],
	 *   'relative/path/to/file.min.css' => [
	 *     'type' => 'css',
	 *     'minified' => true,
	 *     'media' => ['screen', 'print'] // Use null for "All"
	 *   ],
	 *   'relative/path/to/image.png' => [
	 *     'type' => 'image',
	 *     'transparent' => true, // May be used by compression extensions, signifies that this image is transparent and will thus never be served as jpeg etc. By default (When not set) this flag is true for 'png' and 'gif' and false for 'jpeg' files.
	 *     'resolution' => [100, 400] // The size of the image in pixels when not set, this parameter will be calculated when accessed.
	 *   ],
	 *   'relative/path/to/font.ttf' => [
	 *     'type' => 'font',
	 *     'font-family' => ['Verdana'] // Array of what font families this file supplies. Mostly is just one, but the more modern formats allow for multiple related families to be packed together.
	 *   ]
	 * ]
	 * @var array
	 */
	public $resources = array();

	/**
	 * @var array Key-value pairs of the bundle ids this bundle depends on/requires to function and their versions.
	 * @example 'jquery' => '2.0'
	 */
	public $dependencies = array();
}

/**
 * Defines a PageTree CMS resource or library bundle that is currently located on disk.
 * (Thus Installed, and available for use in the application)
 * @package Quark\Bundles
 */
class LocalBundle extends Bundle {
	/**
	 * @var string The url of the repository the currently installed version was downloaded from.
	 */
	public $repository;

	/**
	 * @var string The full path to the bundle's root-directory.
	 */
	public $path;

	/**
	 * Get a JSON string representing this bundle.
	 * @return string String with JSON representation of this bundle.
	 */
	public function toJSON(){
		$result = array();
		foreach($this->getProperties() as $prop){
			if(empty($this->{$prop}))
				return false;
			else
				$result[$prop] = $this->{$prop};
		}
		return json_encode($result);
	}

	/**
	 * Examines the JSON string and turns it into a LocalBundle or throws an Exception when the json structure is invalid.
	 * @param string $json The JSON string to examine.
	 * @throws \UnexpectedValueException When one of the required properties on the JSON object did not exist.
	 * @return LocalBundle
	 */
	public static function fromJSON($json){
		$info = json_decode($json, true);
		if(!is_array($info))
			throw new \UnexpectedValueException('JSON_Decode failed on the given json data ('.json_last_error().'), please check your syntaxis.');
		$bundle = new LocalBundle();

		if(isset($info['id']) && \Quark\Filter\validate_string($info['id'], array('CHARS' => CONTAINS_ALPHANUMERIC.'.-')))
			$bundle->id = $info['id'];
		else throw new \UnexpectedValueException('LocalBundle->fromJSON; Expected "id" to be set and be filled with alpha-numeric characters and \'.-\'.');

		if(isset($info['name']) && \Quark\Filter\validate_string($info['name'], array('CHARS' => CONTAINS_ALPHANUMERIC.'.,-_()&@:;?! ')))
			$bundle->name = $info['name'];
		else throw new \UnexpectedValueException('LocalBundle->fromJSON; Expected "name" to be set and be filled with alpha-numeric characters and \'.,-_()&@:;?! \'.');

		if(isset($info['version']) && \Quark\Filter\validate_string($info['version'], array('CHARS' => CONTAINS_ALPHANUMERIC.'.-_ ')))
			$bundle->version = $info['version'];
		else throw new \UnexpectedValueException('LocalBundle->fromJSON; Expected "version" to be set and be filled with alpha-numeric characters and \'.-_ \'.');

		if(isset($info['description']) && \Quark\Filter\validate_string($info['description'], array('CHARS' => CONTAINS_ALPHANUMERIC.'\\|":;\'[]{}()*&%$#@!~+=,.-_ ')))
			$bundle->description = $info['description'];
		else throw new \UnexpectedValueException('LocalBundle->fromJSON; Expected "description" to be set.');

		if(isset($info['resources']) && is_array($info['resources'])){
			$bundle->resources = array('css' => array(), 'js' => array(), 'font' => array(), 'image' => array());
			foreach($info['resources'] as $type => $resources){
				if(!isset($bundle->resources[$type]))
					throw new \UnexpectedValueException('LocalBundle->fromJSON; Resource type "'.$type.'" is malformed, it should be one of "css", "font", "image" or "js".');
				foreach($resources as $resource => $properties){
					if(is_string($resource) && \Quark\Filter\validate_string($resource, array('CHARS' => CONTAINS_ALPHANUMERIC.'/\\.-_ ')) && is_array($properties)){
						$bundle->resources[$type][$resource] = array();
						if($type == 'css')
							$keys = array('media');
						else if($type == 'js')
							$keys = array();
						else if($type == 'font')
							$keys = array();
						else if($type == 'image')
							$keys = array();
						else throw new \UnexpectedValueException('Found unexpected $type value where this should not be the case.');
						for($i=0; $i<count($keys); $i++){
							if(isset($properties[$keys[$i]]))
								$bundle->resources[$type][$resource][$keys[$i]] = $properties[$keys[$i]];
						}
					}else throw new \UnexpectedValueException('LocalBundle->fromJSON; Resource "'.$resource.'" is malformed, it\'s properties should be of type "array", found "'.gettype($properties).'" or it\'s name contained illegal characters.');
				}
			}
		}else throw new \UnexpectedValueException('LocalBundle->fromJSON; Expected "resources" to be set and be of type array.');

		$bundle->dependencies = array();
		if(isset($info['dependencies']) && is_array($info['dependencies'])){
			foreach($info['dependencies'] as $dependency){
				if(is_string($dependency) && \Quark\Filter\validate_string($dependency, array('CHARS' => CONTAINS_ALPHANUMERIC.'.-')))
					$bundle->dependencies[] = $dependency;
				else if(is_array($dependency) && count($dependency) >= 2){
					$dependencyId = array_shift($dependency);
					try {
						$version = normalize_version_rule($dependency);
						$bundle->dependencies[] = array_merge(array($dependencyId), $version);
					}catch(\Exception $e){
						throw new \UnexpectedValueException('LocalBundle->fromJSON; Dependency "'.var_export($dependency, true).'" incorrectly formatted!', E_USER_ERROR, $e);
					}
				}else throw new \UnexpectedValueException('LocalBundle->fromJSON; Expected dependency id to be set and be filled with alpha-numeric characters and \'.-\' or an array foramtted as [bundleid, version comparisson operator, {version, [version, version]}].');
			}
		}// not required to be set

		if(isset($info['repository']) && \Quark\Filter\validate_string($info['repository'], array('url' => null)))
			$bundle->repository = $info['repository'];
		else throw new \UnexpectedValueException('LocalBundle->fromJSON; Expected "repository" to be set and be a valid URL.');

		$bundle->path = DIR_BUNDLES.$info['id'].DS;

		return $bundle;
	}

	/**
	 * Creates a LocalBundle object from a *trusted* JSON source.
	 * It does not validate the JSON structure, and thus requires you to know that the source was correctly formatted. (For example loading it with the standard fromJSON)
	 * @param string $json Trusted JSON string to be parsed into object form.
	 * @return LocalBundle
	 */
	public static function fromTrustedJSON($json){
		$info = json_decode($json, true);

		/*$bundle = new LocalBundle();
		$bundle->id = $info['id'];
		$bundle->name = $info['name'];
		$bundle->version = $info['version'];
		$bundle->description = $info['description'];
		$bundle->resources = $info['resources'];
		$bundle->dependencies = $info['dependencies'];
		$bundle->repository = $info['repository'];
		$bundle->path = DIR_BUNDLES.$info['id'].DS;
		return $bundle;*/

		$bundle = new LocalBundle($info); // This also checks if properties are actually available on this object, and it is shorter.
		$bundle->path = DIR_BUNDLES.$info['id'].DS;
		return $bundle;
	}
}

/**
 * Defines a PageTree CMS resource or library bundle that is available in the repository.
 * @package Quark\Bundles
 */
class RemoteBundle extends Bundle {
	/**
	 * @var array Array containing the urls of the repositories that can provide this bundle.
	 */
	public $repositories;
}