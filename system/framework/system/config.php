<?php
/**
 * Loads the config and makes it available to the rest of the System
 * 
 * @package		Quark-Framework
 * @version		$Id: config.php 44 2012-09-24 13:42:02Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		October 1, 2009
 * @copyright	Copyright (C) 2006-2011 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2006-2009 Jeffrey van Harn
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

// Some standard pt file statements
namespace Quark\System;
if(!defined('DIR_BASE')) exit; // Prevent acces to this standalone file

/**
 * Configuration class
 * 
 * This class holds all the configuration variables for the PageTree CMS system
 * @package Quark-Framework
 * @subpackage System
 */
class Config implements \Quark\Util\Singleton{
	use \Quark\Util\baseSingleton;
	
	/**
	 * This var will contain all the config variables
	 */
	protected $config = array();
	
	/**
	 * This array contains all the default configuration variables
	 */
	protected $defaults = array(
		// System
		'system' => array(
			'seo' => array('optimize_url' => true),
			'update' => array('do_beta' => true),
			'timezone' => 'Europe/Amsterdam',
			'simpleui' => true
		),
		
		// Application
		'router' => array(
			'index_component' => 'content'
		),

		// Engines
		'engine' => array(
			'template' => 'auto',
			'database' => 'mysql',
			'backend' => 'simple'
		),

		// Error Handler
		'error' => array(
			'mail' => false,
			'mailadress' => 'admin@localhost',
			'handle_errors' => true,
			'display' => true,
			'always_log' => true,
			'debug_mode' => true,
			'debug_extended' => true
		),

		// Language
		'lang' => array(
			'default' => 'en',
			'unicode' => false,
			'inc_alt' => true
		),

		// Caching
		'cache' => array(
			'enabled' => false,
			'days' => 1
		),
		
		// Database
		'database' => array(
			'type' => 'plain',
			'database' => 'PageTree',
			'username' => '',
			'password' => '',
			'table_prefix' => '',
			'host' => '127.0.0.1',
			'protection' => 'htaccess'
		),

		// Security
		'security' => array(
			'hash_key' => 'PageTreeDevelopmentVersion', // Changes at least every stable release, mostly other releases to, but this is not always checked. So: CHANGE THIS YOURSELF!
			'view_limit' => 20,
			'handle_junk' => true
		)
	);
	
	/**
	 * Config-Class Constructor
	 * 
	 * Loads the config files
	 */
	public function __construct(){
		// Load Configuration file
		$i = @include(DIR_DATA.'config.php');
		if($i == false) throw new \RuntimeException('Failed to load config file: Could not find the config file in the data directory!');
		if(!isset($config)) throw new \RuntimeException('Failed to load config file: The configuration file dit not contain the $config variable..');
		
		// Make sure the required settings are set
		if(isset($config['database'])){
			if(!isset($config['database']['username']) || !isset($config['database']['password']) || !isset($config['database']['database']))
				throw new \RuntimeException('Failed to load config file: The required settings of the Database are not set in your config file. At least set the options username, password and database.');
		}else throw new \RuntimeException('Failed to load config file: The required settings for db where not found in your config file. Therefore PageTree could not continue.');
		
		// Append configuration on defaults
		$this->config = array_merge($this->defaults, $config); 
		
		// Clear up
		unset($config, $i);
	}
	
	/**
	 * Retrieves a configuration variable
	 * 
	 * Example: <code>$config_var = $config_ref->get('sys', 'timezone');</code>
	 * Warning! Make sure you know what variables you can 'get' because the difference between an failure code and a config var is nihil!
	 * @param string $category The catagory
	 * @param string $variable The variable to get
	 * @param bool $default Whether or not to return the default value of the config var
	 * @return mixed The configuration variable or null on failure(And a warning)
	 */
	public function get($category, $variable, $default=false){
		// Normalize vars
		$category = strtolower($category);
		$variable = strtolower($variable);
		
		// Try to find the var, and return it's  value
		if($default){
			if(array_key_exists($category, $this->defaults)){
				if(array_key_exists($variable, $this->defaults[$category]))
					return $this->defaults[$category][$variable];
			}
		}else{
			if(array_key_exists($category, $this->config)){
				if(array_key_exists($variable, $this->config[$category]))
					return $this->config[$category][$variable];
			}
		}
		
		// If it fails, throw warning and return null
		throw new \OutOfBoundsException('Configuration variable "$config['.$cat.']['.$var.']" does not exist in the config'.($default?' defaults.':'.'));
		return null;
	}
	
	/**
	 * Set a configuration variable
	 * @param $cat The catagory
	 * @param $var The variable to set
	 * @param $value The new value to assign
	 * @return bool True on success false on failure(Mostly means that it is write protected)
	 */
	public function set($cat, $var, $value){
		// Set the val
		$this->config[$cat][$var] = $value;
		return true;
	}
	
	/**
	 * Get the default value of a configuration variable
	 * 
	 * @see ptConfig::get()
	 * @param $cat The catagory
	 * @param $var The variable to get
	 * @return mixed The configuration variable or null on failure
	 */
	public function getDefault($cat, $var){
		if(array_key_exists($cat, $this->defaults)){
			if(array_key_exists($var, $this->defaults[$cat])) return $this->defaults[$cat][$var];
			else return null;
		}
		return null;
	}
	
	/**
	 * Get the whole configuration
	 * 
	 * Example: <code>$config = $config_ref->config;</code>
	 * @param $cat The catagory
	 * @param $var The variable to get
	 * @return mixed The configuration variable or null on failure
	 */
	public function __get($var){
		// Config variable
		if(strtolower($var) == 'config')
			return $this->config;
		// Probably a underscore formatted name
		else{
			$e = explode('_', $var, 2);
			if(count($e) > 1){
				if(array_key_exists($e[0], $this->config)){
					if(array_key_exists($e[1], $this->config[$e[0]])) return $this->config[$e[0]][$e[1]];
					else return null;
				}
				return null;
			}else
				throw new \RuntimeException('Variable Config->$'.$var.' , does not exist or is inaccessible.');
		}
	}
}