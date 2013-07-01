<?php
/**
 * Password Hashing Class.
 * 
 * @package		Quark-Framework
 * @version		$Id: password.php 73 2013-02-10 15:01:47Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 2, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2013 Jeffrey van Harn
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
namespace Quark\Security;

use \Quark\Util\String;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Generate Secure Password Hashes.
 * 
 * This class uses the php crypt extension and some application level salts.
 * @link http://barkingiguana.com/2009/08/03/securing-passwords-with-salt-pepper-and-rainbows/ More info on Peppering passwords.
 */
class Password {
	/**
	 * Default Pepper string.
	 */
	const DEFAULT_PEPPER = 'Y6AVcpXnunjiTbGothgD';
	
	/**
	 * Default entanglement seed.
	 */
	const DEFAULT_SEED = 71246823;
	
	/**
	 * Blowfish Hashing Algorithm.
	 * 
	 * Using this hashing algorithm results in a 60-character long hash.
	 */
	const ALGO_BLOWFISH = 1;
	
	/**
	 * SHA-512 Hashing Algorithm.
	 * 
	 * Using this hashing algorithm will result in a 118-character long hash.
	 */
	const ALGO_SHA512 = 2;
	
	/**
	 * Defines the lowest possible cost for the hashing algorithm.
	 */
	const COST_LOW = 0;
	
	/**
	 * Defines a normal generation cost for the hashing algorithm.
	 */
	const COST_NORMAL = 1;
	
	/**
	 * Defines a high generation cost for the hashing algorithm.
	 */
	const COST_HIGH = 2;
	
	/**
	 * Defines the highest possible cost for the current algorithm.
	 */
	const COST_HIGHEST = 3;
	
	/**
	 * Available algorithms and data/settings about/for them.
	 * @var array
	 */
	protected $algorithms = array(
		self::ALGO_BLOWFISH => [
			'name' => 'BLOWFISH',
			'crypt' => '2y',
			'saltlen' => 22,
			'optionstr' => '@',
			'cost' => [
				self::COST_LOW		=> '04',
				self::COST_NORMAL	=> '12',
				self::COST_HIGH		=> '21',
				self::COST_HIGHEST	=> '31',
			]
		],
		self::ALGO_SHA512 => [
			'name' => 'SHA512',
			'crypt' => '6',
			'saltlen' => 16,
			'optionstr' => 'rounds=@',
			'cost' => [
				self::COST_LOW		=> '1000',
				self::COST_NORMAL	=> '6000',
				self::COST_HIGH		=> '11000',
				self::COST_HIGHEST	=> '999999999',
			]
		]
	);
	
	/**
	 * Password object specific pepper string.
	 * @var string|boolean
	 */
	protected $pepper;
	
	/**
	 * The crypt salt string to use with the ALGO_* constant.
	 * @var string
	 */
	protected $algorithm;
	
	/**
	 * The selected COST_* constant for the current algorithm.
	 * @var integer
	 */
	protected $cost;
	
	/**
	 * Application-wide Salt or Pepper
	 * @var string
	 */
	private static $application_pepper = self::DEFAULT_PEPPER;
	
	/**
	 * @param bool|string $pepper (Recommended) Whether or not to use the application wide salt, to strengthen hashes. Using this will however make the resulting hashes incompatible with standard crypt and PHP 5.5+ password_* functions. It will however make your hashes more resistant against Rainbow-table/dictionary attacks when the attackers can't access your pepper string and application code. MAXLENGTH=32
	 */
	public function __construct($pepper=false, $algorithm=self::ALGO_BLOWFISH, $cost=self::COST_NORMAL){
		$this->setPepper($pepper);
		$this->setAlgorithm($algorithm);
		$this->setAlgorithmCost($cost);
	}
	
	/**
	 * Set the pepper of the current Password object.
	 * @param string|bool $pepper When false pepperring passwords is disabled, when true the default is used, when a string, that string is used for peppering.
	 */
	public function setPepper($pepper){
		if(is_bool($pepper))
			$this->pepper = ($pepper ? self::DEFAULT_PEPPER : false);
		else
			$this->pepper = substr(str_pad((string) $pepper, 32, $pepper), 0, 32);
	}
	
	/**
	 * Set the algorithm used for this password object.
	 * @param integer $algorithm One of the ALGO_* constants.
	 */
	public function setAlgorithm($algorithm){
		if(is_int($algorithm) && isset($this->algorithms[$algorithm])){
			$this->algorithm = $algorithm;
		}
	}
	
	/**
	 * Set the algorithm cost for this password object.
	 * @param integer $cost One of the COST_* constants.
	 */
	public function setAlgorithmCost($cost){
		if(is_int($cost) && $cost >= 0 && $cost <= 3){
			$this->cost = $cost;
		}
	}
	
	/**
	 * Hash a password with a pseudo-random salt.
	 * @param string $password Password to hash.
	 * @param string $salt Optionally, a salt. By default the best available random source is used.
	 * @return string The hash.
	 */
	public function hash($password, $salt=null){
		if(empty($salt))
			$salt = Random::string($this->algorithms[$this->algorithm]['saltlen'], implode(range('a', 'z')).implode(range('A', 'Z')).implode(range('0', '9')));
		else
			$salt = String::lengthen($salt, $this->algorithms[$this->algorithm]['saltlen']);
		
		var_dump($this->buildSaltString($salt));
		if(is_string($this->pepper)){
			$hash = '+'.substr(crypt(hash('SHA512', self::entangle($password, $this->pepper)), $this->buildSaltString($salt)), 1);
		}else{
			$hash = crypt($password, $this->buildSaltString($salt));
		}
		return $hash;
	}
	
	/**
	 * Check if a password is the same as the hashed one.
	 * 
	 * Beware: object should have the same setings as when the crypted string was created, otherwise it will not always yield the same results.
	 * @param string $password Password to check.
	 * @param string $hash Hash to compare with.
	 * @return boolean Whether or not it was the same password.
	 */
	public function check($password, $hash){
		if(is_string($this->pepper)){
			$uncloaked = '$'.substr($hash, 1);
			return (crypt(hash('SHA512', self::entangle($password, $this->pepper)), $uncloaked) == $uncloaked);
		}else{
			return (crypt($password, $hash) == $hash);
		}
	}
	
	/**
	 * Get information about a hash.
	 * @param string $hash Password class hashed string to get information about.
	 * @return arrat Associative array with the keys peppered, algo, algoname, cost and salt.
	 */
	public function info($hash){
		$info = array();
		$info['peppered'] = (substr($hash, 0, 1) == '+');
		
		$parts = explode('$', substr($hash, 1));
		foreach($this->algorithms as $algo => $options){
			if($options['crypt'] == $parts[0]){
				$info['algo'] = $algo;
				$info['algoname'] = $options['name'];
				// cost
				if(isset($parts[1])){
					$cost = array_search($parts[1], $options['cost']);
					if($cost !== false)
						$info['cost'] = $cost;
					else
						$info['cost'] = -1;
				}else $info['cost'] = null;
				// salt
				if(isset($parts[2])){
					$info['salt'] = substr($parts[2], 0, $options['saltlen']);
				}else $info['salt'] = null;
				return $info;
			}
		}
		
		$info['algo'] = null;
		$info['algoname'] = 'UNKNOWN';
		return $info;
	}
	
	/**
	 * Set the Application-wide salt.
	 * 
	 * This is a salt that is privately known to only the application, so that when someone get's access to the database, and not to the application, they will have a harder timecan even generate bruteforce your should only be known in the application, so to add more entropy
	 * to password salts.
	 * (Recommended for business critical/enterprise applications)
	 * @param string $salt An application wide salt that always stays the same and is only known in the application (Recomended length: 20 characters).
	 */
	public static function setApplicationPepper($pepper){
		$this->application_pepper = String::lengthen($pepper, 32);
	}
	
	/**
	 * Salts/entangles a string with the given value.
	 * 
	 * Entangles the characters from both strings to better survive rainbow table attacks.
	 * Uses the middle-square method for generating pseudo-random numbers.
	 * @param string $source Source string.
	 * @param string $salt Salt to entangle the string with.
	 * @param integer $seed When you want to have even more random character placement.
	 * @return string Entangled string of source length + salt length.
	 */
	final static function entangle($source, $salt, $seed=self::DEFAULT_SEED){
		$sourceLength	= strlen($source);
		$saltLength		= strlen($salt);
		$entangled		= $source;
		$lastSeed		= Random::pseudoNumber($seed, 21);
		for($i=0; $i<$saltLength; $i++){
			$lastSeed = Random::pseudoNumber($lastSeed, 2);
			$entangled = String::insert($salt[$i], \Quark\Util\Number::range($lastSeed, 0, $sourceLength), $entangled);
		}
		return $entangled;
	}
	
	protected function buildSaltString($salt){
		$current = $this->algorithms[$this->algorithm];
		return '$'.$current['crypt'].'$'.str_replace('@', $current['cost'][$this->cost], $current['optionstr']).'$'.$salt;
	}
}