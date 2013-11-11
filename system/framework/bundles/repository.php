<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		July 01, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Bundles;

use Quark\Error;
use Quark\Exception;
use Quark\Services\HTTP\Request;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Repository handling and interaction helper methods.
 * @package Quark\Bundles
 */
class Repository {
	/** Local file containing the list of repositories known to the system/added by the user. */
	const LOCAL_REPOSITORY_LIST = 'repositories.list';

	/** File at the remote server representing the list of bundles. */
	const REMOTE_BUNDLES_LIST = 'bundles.json';

	// -- Remote Repository Interaction functions ----------------------------------------------------------------------
	#region Remote Repository Interaction
	/**
	 * Check whether or not the url given is a valid Quark bundle repository.
	 * @param string $url The repository url.
	 * @return bool
	 */
	public static function TestRepository($url){
		// Test URL validity
		if(!validate_string($url, 'URL'))
			return false;

		// Request server API version.
		// @todo Implement

		// All went well
		return true;
	}

	/**
	 * Get the bundles available in this repository.
	 * @param string $url The repository URL to get the listing for.
	 * @return array|bool List of the available bundles as keys with their Name, /Versions/ and Description as array values.
	 */
	public static function GetBundleList($url){
		$request = Request::create($url.self::REMOTE_BUNDLES_LIST);

		try {
			$response = $request->send(true);
		} catch(Exception $e) {
			Error::raiseWarning('An exception occurred during the retrieval of the repository bundle index list of repo "'.$url.'". The HTTP\\Request Class gave the following exception: '.$e->getMessage(), 'An exception ocurred during the retrieval of the bundle list from the repo "'.$url.'".');
			return false;
		}

		if(!$response->hasBody()){
			Error::raiseWarning('Unable to retrieve the repository bundle index list for repo "'.$url.'". Received an empty response with status code "'.$response->getResponseCode().'".');
			return false;
		}

		return json_decode($response->getBody(), true);
	}

	/**
	 * Get the RemoteBundle object to get information about a bundle, and be able to install it.
	 * @param string $url The repository URL.
	 * @param string $bundleId The bundle Identifier.
	 * @param string $version The version to fetch or null to get the latest version.
	 * @return RemoteBundle|bool
	 */
	public static function GetBundleObject($url, $bundleId, $version = null){
		if(empty($version)){
			$version = self::GetBundleLatestAvailableVersion($url, $bundleId);
			if($version == false || empty($version))
				return negative( Error::raiseWarning('Whilst trying to determine the latest version for bundle "'.$bundleId.'" in repo "'.$url.'" something went wrong. Try determining whether or not the repo is still in working order or if your connection is still working. If none of these is to blame, consider reporting the package to the repo\'s administrator.') );
		}

		$request = Request::create($url.urlencode($bundleId).'/'.urlencode($version).'/bundle.json');

		try {
			$response = $request->send(true);
		} catch(Exception $e) {
			Error::raiseWarning('An exception occurred during the retrieval of the repository bundle index list of repo "'.$url.'". The HTTP\\Request Class gave the following exception: '.$e->getMessage(), 'An exception occurred during the retrieval of the bundle list from the repo "'.$url.'".');
			return false;
		}

		return $response->getBody();
	}

	/**
	 * Downloads the bundle package to the temporary dir and returns it's path.
	 * @param string $url
	 * @param string $bundleId
	 * @param string $version
	 * @return string The file path to the temporary location of the just downloaded package file.
	 */
	public static function GetBundlePackage($url, $bundleId, $version = null){
		$url = self::GetBundlePackageURL($url, $bundleId, $version = null);
		if($url == false)
			return false;

		$request = Request::create($url);
		try {
			$response = $request->send(true);
		} catch(Exception $e) {
			Error::raiseWarning('An exception occurred during the retrieval of the repository bundle index list of repo "'.$url.'". The HTTP\\Request Class gave the following exception: '.$e->getMessage(), 'An exception occurred during the retrieval of the bundle list from the repo "'.$url.'".');
			return false;
		}

		$path = DIR_TEMP.'bundle_cache'.DS.$bundleId.'_'.$version.'.bundle';
		file_put_contents($path, $response->getBody());
		return $path;
	}

	/**
	 * Get the full URL to the package for the given bundle version.
	 * @param string $url Repository URL.
	 * @param string $bundleId The bundle identifier.
	 * @param string $version The version of the bundle to download or null to get the latest.
	 * @return string The URL that points to the bundle's zip.
	 */
	public static function GetBundlePackageURL($url, $bundleId, $version = null){
		if(empty($version)){
			$version = self::GetBundleLatestAvailableVersion($url, $bundleId);
			if($version == false || empty($version))
				return negative( Error::raiseWarning('Whilst trying to determine the latest version for bundle "'.$bundleId.'" in repo "'.$url.'" something went wrong. Try determining whether or not the repo is still in working order or if your connection is still working. If none of these is to blame, consider reporting the package to the repo\'s administrator.') );
		}

		return $url.urlencode($bundleId).'/'.urlencode($version).'/bundle.zip';
	}

	/**
	 * Get the latest available version the given repository provides for the given bundle.
	 * @param string $url The repository url.
	 * @param string $bundleId The bundleId to look for.
	 * @return string The version string.
	 */
	public static function GetBundleLatestAvailableVersion($url, $bundleId){
		$request = Request::create($url.urlencode($bundleId).'/latest.version');

		try {
			$response = $request->send(true);
		} catch(Exception $e) {
			Error::raiseWarning('An exception occurred during the retrieval of the latest version number of the bundle with id "'.$bundleId.'" in repo "'.$url.'". The HTTP\\Request Class gave the following exception: '.$e->getMessage());
			return false;
		}

		return trim($response->getBody());
	}
	#endregion

	// -- Local Repository List Editing functions ----------------------------------------------------------------------
	#region Local Repository List Editing
	/**
	 * Get the URLs of the available repositories and their enabled state.
	 * @return array Array of repositories with the url as key and whether or not the repository is enabled as value.
	 */
	public static function GetAvailableRepositories(){
		$repositories = array();
		foreach(file(DIR_BUNDLES.self::LOCAL_REPOSITORY_LIST, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $raw){
			$state = substr($raw, 0, 1);
			if($state == '#')
				continue;

			$repositories[trim(substr($raw, 1))] = ($state == '+');
		}
		return $repositories;
	}

	/**
	 * @param array $repositories The repositories to write to the repository list with the urls as keys and their enabled state as values.
	 */
	public static function SetAvailableRepositories($repositories){
		$handle = @fopen(DIR_BUNDLES.self::LOCAL_REPOSITORY_LIST, 'w');
		if($handle == false)
			Error::raiseWarning('Unable to open the bundle repository list at path "'. DIR_BUNDLES.self::LOCAL_REPOSITORY_LIST .'".', 'Unable to open the bundle repository list.');
		flock($handle, LOCK_EX);
		fwrite($handle, "# This file lists all the repositories available for Quark to get Bundles from.\n# You are advised to NOT EDIT THIS FILE MANUALLY, as it may lead to framework instability.\n");
		foreach($repositories as $url => $enabled)
			fwrite($handle, ($enabled ? '+ ' : '- ') . $url . "\n");
		flock($handle, LOCK_UN);
		fclose($handle);
	}

	/**
	 * Adds the given repository URL to the available repositories list.
	 * @param string $url Repository URL.
	 * @return bool
	 */
	public static function AddAvailableRepository($url){
		 return (file_put_contents(DIR_BUNDLES.self::LOCAL_REPOSITORY_LIST, '+ '.$url."\n", FILE_APPEND | LOCK_EX) !== false);
	}

	/**
	 * Removes the given Repository from the list.
	 * @param string $url Repository URL
	 * @return bool
	 */
	public static function RemoveAvailableRepository($url){
		$repositories = self::GetAvailableRepositories();
		foreach($repositories as $repo => $enabled){
			if($repo == $url){
				unset($repositories[$repo]);
				self::SetAvailableRepositories($repositories);
				return true;
			}
		}
		return false;
	}

	/**
	 * Change the enabled state for a repository URL on the currently available list.
	 * @param string $url URL of repository to change enabled state for.
	 * @param bool $state The new enabled state.
	 * @return bool
	 */
	public static function SetAvailableRepositoryState($url, $state){
		$repositories = self::GetAvailableRepositories();
		foreach($repositories as $repo => $enabled){
			if($repo == $url){
				$repositories[$repo] = $state;
				self::SetAvailableRepositories($repositories);
				return true;
			}
		}
		return false;
	}
	#endregion
}