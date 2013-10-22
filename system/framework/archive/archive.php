<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Jeffrey
 * Date: 11-8-13
 * Time: 12:48
 * To change this template use File | Settings | File Templates.
 */

namespace Quark\Archive;


use Quark\Exception;

interface Archive {
	/**
	 * Creates a new Archive.
	 * @param string $file Path to archive file to create or open.
	 */
	public function __construct($file);

	/**
	 * Add a file to the archive.
	 * @param string $path
	 * @param string|resource $data
	 * @return mixed
	 */
	public function addFile($path, $data);

	/**
	 * Add a directory to the archive.
	 * @param string $path
	 * @return mixed
	 */
	public function addDirectory($path);

	/**
	 * Get an archive iterator for the given zip path.
	 * @param string $path
	 * @return ArchiveIterator|bool
	 */
	public function getDirectoryIterator($path);

	/**
	 * Get the list of items in the given directory path.
	 * @param string $path
	 * @return array
	 */
	public function listDirectory($path);

	/**
	 * Extract a file from the Archive.
	 * @param string $file The file to extract from within the archive.
	 * @param string $targetFile The file on system where the content should be written to, if none is provided it will return the file contents.
	 * @return bool|string
	 */
	public function extract($file, $targetFile=null);

	/**
	 * Extract all the files in the archive to the given directory.
	 * @param string $targetDirectory Path to the directory where the complete archives structure should be rebuild.
	 * @return bool Whether or not we were successful.
	 */
	public function extractAll($targetDirectory);

	/**
	 * Flush the created archive to disk.
	 * @return void
	 */
	public function close();
}

class ArchiveIterator implements \Iterator {

	/**
	 * @param Archive $archive The archive to iterate over.
	 */
	public function __construct(Archive $archive){

	}

	/**
	 * Return the current element
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return ArchiveIteratorItem
	 */
	public function current() {
		// TODO: Implement current() method.
	}

	/**
	 * Move forward to next element
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void
	 */
	public function next() {
		// TODO: Implement next() method.
	}

	/**
	 * Return the key of the current element
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return string|null Path to current file on success null on failure.
	 */
	public function key() {
		// TODO: Implement key() method.
	}

	/**
	 * Checks if current position is valid
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 */
	public function valid() {
		// TODO: Implement valid() method.
	}

	/**
	 * Rewind the Iterator to the first element
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void
	 */
	public function rewind() {
		// TODO: Implement rewind() method.
	}
}

class ArchiveIteratorItem {
	public function isDir(){

	}

	public function isFile(){


	}


}

class ArchiveExtractionException extends Exception { }