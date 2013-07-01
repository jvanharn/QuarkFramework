<?php
/**
 * Helps disecting a URL into it's different parts.
 * 
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		February 10, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\Router;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
\Quark\import('Framework.Filter.Filter', true);

/**
 * Parses and describes URL's.
 * 
 * This class is deliberately named urL and not urI because it only parses full
 * url's with schemes etc. The underlying php functions also only support URL's.
 * 
 * @property-read \Quark\System\Router\URLPathInfo $pathinfo The path info object of this url.
 * @property-read string $scheme The scheme of the url.
 * @property-read string $host The host of the url.
 * @property-read string $user User defined in the url.
 * @property-read string $pass Password defined in the url.
 * @property-read string $path The path parts of the url.
 * @property-read string $query The query parameters for the url.
 * @property-read boolean $secure Whether or not the current connection is secure.
 * @property-read string $fragment Everything after the hashtag.
 */
class URL {
	/**
	 * Whether or not to sanitize all URL parts to only make them contain allowed characters and decode all characters.
	 * @var boolean
	 */
	protected $sanitize = true;
	
	/**
	 * Contains the original URL
	 * @var string
	 */
	protected $raw;
	
	/**
	 * Contains the parsed version of the URL.
	 * @var string
	 */
	protected $parsed;
	
	/**
	 * The split path.
	 * @var array
	 */
	protected $path;
	
	/**
	 * The split query.
	 * @var array
	 */
	protected $query;
	
	/**
	 * Path info for this url.
	 * 
	 * Null untill requested.
	 * @var \Quark\System\Router\URLPathInfo
	 */
	protected $pathinfo;
	
	/**
	 * @param string $url URL to parse or get info about.
	 * @param boolean $sanatize Whether or not to sanitize information from the url before returning it. The sanitation filters are pretty strict, so if you requery input from the url other than ascii information, you need to turn this off, or loosen the filters by adding them manually. The sanitation only applies to the path variables, the rest is your *OWN RESPONSIBILITY*, always double check.
	 * @throws \UnexpectedValueException When the URL is malformed.
	 */
	public function __construct($url, $sanitize=true){
		$this->raw = $url;
		$this->parsed = parse_url($url);
		if($this->parsed === false)
			throw new \UnexpectedValueException('The URL given to the parse function could not be parsed and was seriously malformed.');
		$this->sanitize = $sanitize;
	}
	
	/**
	 * Get the original URL given to this class.
	 * @return string
	 */
	public function getURL(){
		return $this->raw;
	}
	
	/**
	 * Get the parsed info as returned by parse_url.
	 * @return array
	 */
	public function getAll(){
		return $this->parsed;
	}
	
	/**
	 * Get the Path Info.
	 * @return \Quark\System\Router\URLPathInfo Path info for the current url.
	 */
	public function getPathInfo(){
		if(is_null($this->pathinfo))
			$this->pathinfo = new URLPathInfo($this->parsed['path'], $this->parsed['query'], ($this->parsed['scheme'] == 'https'));
		return $this->pathinfo;
	}
	
	/**
	 * Get a value dynamically.
	 * @param string $key Index of the parsed data.
	 * @return mixed The value of it.
	 * @throws \OutOfBoundsException When the key given does not exist.
	 */
	public function __get($key){
		if($key == 'secure')
			return ($this->parsed['scheme'] == 'https');
		else if($key == 'secure')
			return $this->getPathInfo();
		else if(isset($this->parsed[$key]))
			return $this->parsed[$key];
		else throw new \OutOfBoundsException('Key '.$key.' is an invalid key.');
	}
	
	/**
	 * Returns the url for this URL object.
	 * @return string
	 */
	public function __toString(){
		return $this->getURL();
	}
	
	/**
	 * Get the URL object from the current request.
	 * 
	 * WARNING: This (mostly) only works in (Fast)CGI mode: It uses the $_SERVER variable to get it's data about the request!
	 * This may not work on every platform but is tested on the more known http server's like IIS, Apache, LightHTTPD, NGINX, Hiawatha and the new built-in PHP 5.4 CLI server.
	 * @return \Quark\System\Router\URI A URI object.
	 */
	public static function fromRequest($sanitize=true){
		// Secure
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'){
			$url = 'https://';
			$dport = 443;
		}else{
			$url = 'http://';
			$dport = 80;
		}
		
		// Hostname
		if(isset($_SERVER['SERVER_NAME']))
			$url .= $_SERVER['SERVER_NAME'];
		else if(isset($_SERVER['HTTP_HOST']))
			$url .= $_SERVER['HTTP_HOST'];
		
		// Port
		if(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != $dport)
			$url .= ':'.$_SERVER['SERVER_PORT'];
		
		// Path and Query string
		if(isset($_SERVER['REQUEST_URI']))
			$url .= '/'.ltrim($_SERVER['REQUEST_URI'], '/');
		else if(isset($_SERVER['PATH_INFO']))
			$url .= '/'.ltrim($_SERVER['PATH_INFO'], '/');
		
		return new self($url, $sanitize);
	}
	
	/**
	 * Build a URL from a base url string and some path info.
	 * 
	 * This is a simple function that simply takes all the info from the URL
	 * path info object, and appends it to the base url. It is a handy utility
	 * for the router route class implementations.
	 * 
	 * Warning: This method does not use the secure schema property of the path
	 * info object, so even when it is set to true it will not change the schema
	 * of the base url!
	 * @param string $base
	 * @param \Quark\System\Router\URLPathInfo $info
	 */
	public static function build($base, URLPathInfo $info){
		return $base.$info->export('path').$this->export('query');
	}
}

/**
 * URL Data object.
 * 
 * Contains all of the data about a url's path. It preparses everything and makes it dead easy to generate new URL's.
 * 
 * @property boolean $secure Whether or not the path was in a secured scheme.
 * @property array $path The path part of the url split up in each of it's different parts.
 * @property array $query Query string parameters, which means all key=>value pairs after the ? (questionmark) in the typical URL.
 */
class URLPathInfo {
	protected $secure;
	protected $path;
	protected $query;
	
	/**
	 * @access private
	 * @param string|array $path The path part of the url, parsed in array form or raw.
	 * @param string|array $query The query string of the url, same formatting applies as the path but now as key=>value pair.
	 * @param boolean $secure Whether or not a secure schema was used for the url this info was destilled from, or whether or not any 
	 */
	public function __construct($path, $query, $secure=false){
		if(is_array($path) || is_string($path))
			$this->path = $path;
		if(is_array($query) || is_string($query))
			$this->query = $query;
	}
	
	/**
	 * @see URLPathInfo::get()
	 */
	public function __get($key){
		return $this->get($key);
	}
	
	/**
	 * Get the parsed representation of a key.
	 * 
	 * Get's the array values for paths (The parsed representation).
	 * @param string $key Key to retrieve.
	 * @return array|bool
	 * @throws \OutOfBoundsException When a key was invalid.
	 */
	public function get($key){
		switch($key){
			case 'path':
				if(is_string($this->path))
					$this->path = explode('/', trim($this->parsed['path'], '/'));
				return $this->path;
				break;
			case 'query':
				if(is_string($this->query))
					$this->query = parse_str($this->parsed['query']);
				return $this->query;
				break;
			case 'secure':
				return $this->secure;
				break;
			default:
				throw new \OutOfBoundsException('Key '.$key.' is an invalid key.');
		}
	}
	
	/**
	 * Get the string or boolean value of the key.
	 * @param string $key Key to retrieve.
	 * @return bool|string
	 */
	public function export($key){
		switch($key){
			case 'path':
				if(is_string($this->path))
					return $this->path;
				else
					return implode('/', $this->path).'/';
				break;
			case 'query':
				if(is_string($this->query))
					return $this->query;
				else
					return (empty($this->query) ? '' : '?'.http_build_query($this->query));
				break;
			case 'secure':
				return $this->secure;
				break;
			default:
				throw new \OutOfBoundsException('Key '.$key.' is an invalid key.');
		}
	}


	/**
	 * @see URLPathInfo::set()
	 */
	public function __set($key, $value){
		$this->set($key, $value);
	}
	
	/**
	 * Set the value of a key with a new value.
	 * @param string $key Key to set.
	 * @param array|string|bool $value Array or string to set for the path or query, or a boolean for the secure schema.
	 */
	public function set($key, $value){
		switch($key){
			case 'path':
				if(is_array($value))
					$this->path = $value;
				else
					$this->path = (string) $value;
				break;
			case 'query':
				if(is_array($value))
					$this->query = $value;
				else
					$this->query = (string) $value;
				break;
			case 'secure':
				return (bool) $this->secure;
				break;
			default:
				throw new \OutOfBoundsException('Key '.$key.' is an invalid key.');
		}
	}
	
	/**
	 * Add a path part to the object.
	 * @param string $name Name of the directory to add.
	 */
	public function addDirectory($name){
		if(is_array($this->path))
			$this->path[] = (string) $name;
		else
			$this->path .= ((string) $name).'/';
	}
	
	/**
	 * Add a key value pair to the query part of the url.
	 * 
	 * This method will override any existing query parts with the given key.
	 * @param string $key Key of the query pair.
	 * @param string $value Value of the pair.
	 */
	public function addQuery($key, $value){
		if(is_string($this->query))
			$this->query = parse_str($this->parsed['query']);
		$this->query[$key] = $value;
	}
}