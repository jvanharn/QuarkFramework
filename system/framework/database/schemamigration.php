<?php
/**
 * Schema/Database Migration Helper
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		July 19, 2015
 * @copyright	Copyright (C) 2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Database;

// Prevent individual file access
use Quark\Exception;

if(!defined('DIR_BASE')) exit;

/**
 * Schema/Database Migration Helper
 *
 * This class can be used to simplify database schema version management. It uses the query builder to create a single
 * table that is used for tracking schema versions and upgrades,a nd can automatically up- or downgrade to desired
 * versions.
 *
 * Note: This class adds a table to the database named SchemaVersion. Please do not delete it if using this class.
 */
class SchemaMigration {
	/** Constant that contains the name of the table used for tracking the database versions. */
	const VERSION_TRACKING_TABLE = 'SchemaVersion';

	/**
	 * @var Database
	 */
	protected $db;

	/**
	 * @var array List of all available schemas and their transformations.
	 * Layout: [$schemaName => [[$fqn, $version, $file]]]
	 */
	protected $schemas;

	/**
	 * @var array Array of applied schema transformations as they appear in the database table.
	 */
	protected $current;

	/**
	 * @param Database $db Database that will be managed.
	 * @param array $schemas Array of [$schemaName => [[$fqn, $version, $file]]]
	 */
	public function __construct(Database $db, array $schemas){
		$this->db = $db;
		$this->schemas = array_filter($schemas, function($arr){ return !empty($arr); });
		array_walk(
			$this->schemas,
			function(&$arr){
				usort($arr, function ($a, $b){
					return $this->versionCompare($b[1], $a[1]);
				});
			}
		);
		if(!$this->_getSchemaVersions())
			$this->_createMigrationTable();
	}

	/**
	 * Get all available information about the given schema.
	 * @param string $schema
	 * @return array
	 */
	public function getInfo($schema){
		foreach($this->current as $row){
			if($row['name'] == $schema)
				return $row;
		}
		return null;
	}

	/**
	 * Get the current version of the given schema in the current database.
	 * @param string $schema
	 * @return int[]
	 */
	public function getVersion($schema){
		$info = $this->getInfo($schema);
		return $info == null ? null : $info['version'];
	}

	/**
	 * Get the latest version the given schema can be upgraded to.
	 * @param string $schema
	 * @return int[]
	 */
	public function getLatestVersion($schema){
		if(isset($this->schemas[$schema]))
			return $this->schemas[$schema][0][1];
		else return null;
	}

	/**
	 * Checks whether or not the given schema is at it's latest available version.
	 * @param string $schema
	 * @return bool|null Returns null when either the latest or current version is null (which evaluates to false), false if the $current version is older than the latest version or true if the $current version is the same version or newer than the latest available version.
	 */
	public function hasLatestVersion($schema){
		$current = $this->getVersion($schema);
		$latest = $this->getLatestVersion($schema);
		if(is_null($current) || is_null($latest))
			return null;
		return ($this->versionCompare($current, $latest) >= 0);
	}

	/**
	 * Upgrade the given version to the given version.
	 * @param string $schema
	 * @param int[] $version The specific version to upgrade to.
	 * @throws SchemaMigrationException When something went wrong during the upgrade process.
	 */
	public function upgrade($schema, $version=null){
		$info = $this->getInfo($schema); // get info about the current state of the schema in the database.

		// Check if version is set
		$latest = $this->getLatestVersion($schema);
		if(empty($version) || !is_array($version)){
			$version = $latest;
		}

		if($info == null){
			// If there was no info found in the table, the schema was not yet created, and has to be created.
			// We will first find the latest IInitializerTransformation implementing transformation, and upgrade from there, if necessary.
			$upgrades = array();
			foreach($this->schemas[$schema] as $transformation){
				include_once($transformation[2]);
				if(!class_exists($transformation[0], false))
					throw new SchemaMigrationException('Could not find schema transformation class "'.$transformation[0].'" in file "'.$transformation[2].'".');
				$implemented = class_implements($transformation[0], false);
				if(!is_array($implemented))
					throw new SchemaMigrationException('Schema transformation "'.$transformation[0].'" did not implement any classes.');
				if(in_array('Quark\\Database\\IInitializerTransformation', $implemented)){
					// We found an initializer

					// Check if we can't skip some versions by checking the transformations compatible versions.
					$upgradePath = array_reverse($upgrades);// @todo implement quickpath

					// Upgrade along the upgrade path
					/** @var IInitializerTransformation $obj */
					$obj = new $transformation[0]($this->db);
					$obj->create();
					foreach($upgradePath as $upgrade){
						/** @var ITransformation $obj */
						$obj = new $upgrade[0]($this->db);
						$obj->upgrade();
					}

					// Successfully upgraded, so register the current version in the database.
					$this->_createSchemaEntry([
						'name' => $schema,
						'version' => $version,
						'last_update' => new \DateTime(),
						'migrations' => array($version)
					]);

					return;
				}else{
					// Normal transformation, add it to a list of versions we have to upgrade with
					$upgrades[] = $transformation;
				}
			}

			// None found
			throw new SchemaMigrationException('Unable to upgrade to the given version because you have no IInitializerTransformation implementing transformations, so I cannot create an initial database to upgrade from.');
		}else{
			// The schema is already set-up in the database, and has to be upgraded

			// Check if the database is already on the given version
			if($this->versionCompare($version, $info['version']) <= 0){
				throw new SchemaMigrationException('Can\'t upgrade to the given version, because the schema already is on that version, or the version given is lower than the version of the schema, currently in the database.');
			}

			// @todo
		}
	}

	/**
	 * Downgrade the given schema to the version given.
	 * @param string $schema Schema name.
	 * @param int[] $version Schema version to downgrade to.
	 */
	public function downgrade($schema, $version){
		// @todo
	}

	/**
	 * Compares two versions.
	 * @param int[] $version1
	 * @param int[] $version2
	 * @return int returns -1 if the first is older than the second, 0 if they are equal and 1 if the first is newer than the second
	 */
	protected function versionCompare($version1, $version2){
		if($version1[0] > $version2[0]) return 1;
		if($version1[0] < $version2[0]) return -1;
		else{
			if($version1[1] > $version2[1]) return 1;
			if($version1[1] < $version2[1]) return -1;
			else{
				if($version1[2] > $version2[2]) return 1;
				if($version1[2] < $version2[2]) return -1;
				else return 0;
			}
		}
	}

	/**
	 * Get the schema version data.
	 * @return bool
	 */
	private function _getSchemaVersions(){
		$this->current = array();
		try{
			$result = $this->db
				->select('*')
				->from(self::VERSION_TRACKING_TABLE)
				->execute();
			$row = null;
			while(($row = $result->fetchNext()) != false){
				$this->current[] = array(
					'name' => $row['name'],
					'version' => explode('.', $row['version']),
					'last_update' => \DateTime::createFromFormat('U', $row['last_update']),
					'migrations' => array_map(function($elem){ return explode('.', $elem); }, explode(',', (string) $row['migrations']))
				);
			}
			return true;
		}catch(DatabaseException $e){
			return false;
		}
	}

	/**
	 * Create the migration table.
	 */
	private function _createMigrationTable(){
		$qry = '
			CREATE TABLE '.self::VERSION_TRACKING_TABLE.' (
				`name` varchar(24),
				`version` varchar(21),
				`last_update` datetime,
				`migrations` varchar(255)
			);
		';
		if($this->db->execute($qry) === false)
			throw new \RuntimeException('Unable to create the schema version table in the database. Please create it manually using the following SQL: "'.$qry.'"."');
	}

	/**
	 * Add a row to the schema migration table by giving the values for the new row.
	 * @param array $entry
	 */
	private function _createSchemaEntry($entry){
		$result = $this->db
			->insert()->into(self::VERSION_TRACKING_TABLE)
			->values([
				(string) $entry['name'],
				implode('.', $entry['version']),
				((!is_null($entry['last_update']) && $entry['last_update'] instanceof \DateTime) ? $entry['last_update']->getTimestamp() : null),
				implode(',', array_map(function($entry){ return implode('.', $entry); }, $entry['migrations']))
			])
			->execute();
		if($result !== 1)
			throw new \RuntimeException('Unable to update the database schema migration table with the succeeded up-/downgrade."');
	}

	/**
	 * Update a table row by giving the new values for the row.
	 * @param array $entry
	 */
	private function _updateSchemaEntry($entry){
		$result = $this->db
			->update(self::VERSION_TRACKING_TABLE)
			->where(['schema' => $entry['schema']])
			->set([
				'version' => implode('.', $entry['version']),
				'last_update' => ((!is_null($entry['last_update']) && $entry['last_update'] instanceof \DateTime) ? $entry['last_update']->getTimestamp() : null),
				'migrations' => implode(',', array_map(function($entry){ return implode('.', $entry); }, $entry['migrations']))
			])
			->execute();
		if($result !== 1)
			throw new \RuntimeException('Unable to update the database schema migration table with the succeeded up-/downgrade."');
	}

	/**
	 * Get the current version of the given schema in the given database.
	 * @param Database $db
	 * @param string $schema
	 * @return int[]|null
	 */
	public static function getSchemaVersion(Database $db, $schema){
		try{
			$version = $db
				->select('version')
				->from(self::VERSION_TRACKING_TABLE)
				->where(['schema' => $schema])
				->execute()
				->fetchNextColumn(0);
			if($version != false)
				return explode('.', $version);
			else return null;
		}catch(DatabaseException $e){
			return null;
		}
	}

	/**
	 * Create a new Schema Migration management object, using the transformation files in the given directory.
	 *
	 * The naming scheme of the files in the directory must be "schemaname-1.0.0.php" where schema name is the name of
	 * the schema the transformation is for, and 1.0.0 is the version of that schema. They are separated by a dash.
	 * @param Database $db Database object to manage the schemas for.
	 * @param string $path Path to the directory containing the transformation files.
	 * @param string $namespace The namespace the transformations are located in. Should end with a backslash.
	 * @return SchemaMigration A new schema migration object.
	 */
	public static function fromDirectory(Database $db, $path, $namespace='\\'){
		if($namespace[0] == '\\')
			$namespace = substr($namespace, 1);

		// Create a new Directory Iterator
		$transformations = new \DirectoryIterator($path);

		// Array to place the scanned files in.
		$schemas = array();

		// Loop over the iterator
		foreach($transformations as $trans){
			if($trans->isFile()){
				// It's a file, collect info
				$name = explode('-', $trans->getBasename('.php'));

				if(!isset($schemas[$name[0]]))
					$schemas[$name[0]] = array();
				$schemas[$name[0]][] = array(
					$namespace.$name[0].'SchemaTransformation'.str_replace('.', '', $name[1]),
					explode('.', $name[1]),
					$trans->getPathname()
				);
			}
		}

		return new SchemaMigration($db, $schemas);
	}
}

/**
 * Basic schema transformation interface.
 */
interface ITransformation {
	/**
	 * Creates a new transformation instance.
	 * @param Database $db
	 */
	public function __construct(Database $db);

	/**
	 * Upgrade the schema to the version provided by this transformation.
	 * @throws SchemaTransformationException
	 * @return void
	 */
	public function upgrade();

	/**
	 * Get the schema version of this schema.
	 *
	 * Array of Major, Minor and point release numbers. (ex. 1.0.1)
	 *
	 * Every major version increase, /requires/ an IInitializerTransformation.
	 * @return int[] An array of three numbers: major, minor and point versions.
	 */
	public function getSchemaVersion();

	/**
	 * Gives a list of upgradable versions for this transformation.
	 *
	 * Array of version numbers, formatted in the same way as the getSchemaVersion. Can contain multiple compatible versions.
	 * @return int[][] An array of version arrays, consisting of three numbers: major, minor and point version numbers.
	 */
	public function getUpgradableVersions();

	/**
	 * Get the (unique) schema name.
	 *
	 * Get the name of this type of schema. Is used to group different parts of a database together,
	 * and independently up- and downgrade them.
	 * @return string String of no more than 24 characters, only containing alphanumeric characters and dots.
	 */
	public function getSchemaName();
}

/**
 * Interface for a schema transformation that also has the ability to downgrade to a previous version.
 */
interface IBidirectionalTransformation extends ITransformation {
	/**
	 * Downgrade the schema from the current version to the given version.
	 * @param int[] $version One of the versions returned by {@see getUpgradableVersions()}
	 * @throws SchemaTransformationException
	 * @return void
	 */
	public function downgrade($version);
}

/**
 * Interface for Transformation that can also be used as a base.
 *
 * Note!: Classes that implement this interface should have their getUpgradableVersions method return an empty array!
 */
interface IInitializerTransformation extends ITransformation {
	/**
	 * This method should create an empty database of this schema version.
	 * @throws SchemaTransformationException
	 * @return void
	 */
	public function create();
}

/**
 * Class SchemaTransformationException
 */
class SchemaTransformationException extends Exception {}

/**
 * Class SchemaMigrationException
 */
class SchemaMigrationException extends Exception {}

/**
 * Class Migration
 */
abstract class SchemaTransformation implements ITransformation {
	/** @var Database Database instance to perform transformations on. */
	protected $db;

	/**
	 * @var string Name of the schema. Contains maximally 24 characters consisting of alphanumeric and dot characters only.
	 * @abstract
	 */
	public static $schemaName = 'DefaultSchema';

	/**
	 * @var int[] The version this schema transformation upgrades the database to.
	 * @abstract
	 */
	public static $version = [0, 0, 1];

	/**
	 * @var int[][] Versions this transformation can upgrade the database to.
	 * @abstract
	 */
	public static $supportedVersions = [];

	/**
	 * Create a new schema transformation instance.
	 * @param Database $db
	 */
	public function __construct(Database $db){
		$this->db = $db;
	}

	/**
	 * Get the schema version of this schema.
	 *
	 * Array of Major, Minor and point release numbers. (ex. 1.0.1)
	 *
	 * Every major version increase, /requires/ an IInitializerTransformation.
	 * @return int[] An array of three numbers: major, minor and point versions.
	 */
	public function getSchemaVersion(){
		return self::$version;
	}

	/**
	 * Gives a list of upgradable versions for this transformation.
	 *
	 * Array of version numbers, formatted in the same way as the getSchemaVersion. Can contain multiple compatible versions.
	 * @return int[][] An array of version arrays, consisting of three numbers: major, minor and point version numbers.
	 */
	public function getUpgradableVersions(){
		return self::$supportedVersions;
	}

	/**
	 * Get the (unique) schema name.
	 *
	 * Get the name of this type of schema. Is used to group different parts of a database together,
	 * and independently up- and downgrade them.
	 * @return string String of no more than 24 characters, only containing alphanumeric characters and dots.
	 */
	public function getSchemaName(){
		return self::$schemaName;
	}
}