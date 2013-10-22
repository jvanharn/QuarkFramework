<?php
/**
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 07, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

namespace Quark\Archive;

// Prevent individual file access
use Quark\Error;
use Quark\Exception;

if(!defined('DIR_BASE')) exit;

\Quark\import('Archive.Archive');

/**
 * Zip Archiving class
 *
 * Based on the PKWARE 4.5 zip spec (Early 2001):
 * http://www.pkware.com/documents/APPNOTE/APPNOTE-4.5.0.txt
 *
 * As this format version technically supports Zip64 archives, it's just not pratical to support file size of >4GiB in PHP
 * as the limiting factor will then be PHP (String size limit of 2GB) in most cases. Archives created with this class
 * will always be in the non-zip64 format, it does however support reading them. (The Zip64 indices will be ignored though,
 * so unpacking files which are associated to a Zip64 record, e.g. large files or ones with weird compression algorithms
 * will just not be unpack/extractable.
 * @author Jeffrey vH
 * @package Quark\Archive
 */
class Zip implements Archive{
	const BLOCK_SIZE = 1024;

	/**
	 * Defines the signature of a ZIP file header, used for archive reading/analyzing.
	 * Value is set of bytes.
	 * @access private
	 */
	const LOCAL_FILE_HEADER_SIGNATURE = "\x50\x4B\x03\x04";

	/**
	 * Defines the signature of a file header in the central directory, used for archive reading/analyzing.
	 * Value is set of bytes.
	 * @access private
	 */
	const CENTRAL_DIRECTORY_RECORD_SIGNATURE = "\x50\x4B\x01\x02";

	/**
	 * Defines the signature of the zip64 central directory, used for archive reading/analyzing.
	 * Value is set of bytes.
	 * @access private
	 */
	const ZIP64_END_OF_CENTRAL_DIRECTORY_SIGNATURE = "\x50\x4B\x06\x06";

	/**
	 * Defines the signature that is supposed to mark the end of the zip64 central directory, used for archive reading/analyzing.
	 * Value is set of bytes.
	 * @access private
	 */
	const ZIP64_END_OF_CENTRAL_DIRECTORY_SIGNATURE_LOCATOR = "\x50\x4B\x06\x07";

	/**
	 * Defines the signature that marks the end of the central directory record, used for archive reading/analyzing.
	 * Value is set of bytes.
	 * @access private
	 */
	const END_OF_CENTRAL_DIRECTORY_SIGNATURE = "\x50\x4B\x05\x06";

	/**
	 * Defines the size of a ZIP file header, used for archive reading/analyzing.
	 * Value in number of bytes.
	 * @access private
	 */
	const LOCAL_FILE_HEADER_LENGTH = 30;

	/**
	 * Defines the size of a file's data descriptor, used for archive reading/analyzing.
	 * Value in number of bytes.
	 * @access private
	 */
	const DATA_DESCRIPTOR_LENGTH = 12;

	/**
	 * Defines the size of a file header in the central directory, used for archive reading/analyzing.
	 * Value in number of bytes.
	 * @access private
	 */
	const CENTRAL_DIRECTORY_RECORD_LENGTH = 46;

	/**
	 * Defines the size of the zip64 central directory header, used for archive reading/analyzing.
	 * Value in number of bytes.
	 * @access private
	 */
	const ZIP64_END_OF_CENTRAL_DIRECTORY_LENGTH = 56;

	/**
	 * Defines the size of the zip64 central directory locator header, used for archive reading/analyzing.
	 * Value in number of bytes.
	 * @access private
	 */
	const ZIP64_END_OF_CENTRAL_DIRECTORY_LOCATOR_LENGTH = 20;

	/**
	 * Defines the size of the central directory header, used for archive reading/analyzing.
	 * Value in number of bytes.
	 * @access private
	 */
	const END_OF_CENTRAL_DIRECTORY_LENGTH = 22;

	// Compression type bitfields
	const COMPRESS_STORE		= 0;
	const COMPRESS_SHRINK		= 1;
	const COMPRESS_REDUCE1		= 2;
	const COMPRESS_REDUCE2		= 3;
	const COMPRESS_REDUCE3		= 4;
	const COMPRESS_REDUCE4		= 5;
	const COMPRESS_IMPLODE		= 6;
	const COMPRESS_DEFLATE		= 8;
	const COMPRESS_DEFLATE64	= 9;
	const COMPRESS_BZIP2		= 12;
	const COMPRESS_LZMA			= 14;

	/**
	 * Opens the archive for readonly.
	 */
	const MODE_READONLY = 1;

	/**
	 * Opens the archive in a 'low-memory' consuming direct streaming mode. (Write only, no file delete, forced truncate; thus anything that involves seeking is disabled)
	 */
	const MODE_STREAM = 2;

	/**
	 * (Default) Opens the archive for both reading and writing.
	 */
	const MODE_READWRITE = 3;

	/**
	 * @var resource Holds the zip file handle.
	 */
	protected $handle;

	/**
	 * @var int The mode the archive is currently opened in. (DO NOT CHANGE AFTER CONSTRUCTOR)
	 */
	protected $mode;

	/**
	 * @var array Defines the archives file and directory structure.
	 */
	protected $structure;

	/**
	 * @var array Defines the files and directories and their attributes.
	 */
	protected $entries;

	/**
	 * @var string Archive comment if applicable.
	 */
	protected $comment = '';

	/**
	 * @var bool Whether or not the file handle has already been closed/needs to be closed in the destructor.
	 */
	protected $closed = false;

	/**
	 * Creates a new Archive.
	 * @param string $file Path to archive file to create or open.
	 * @param int $mode In what mode to open the archive, see the class MODE_* constants. Defaults to MODE_RW_CACHING.
	 * @param bool $truncate Whether or not to force the creation of a new archive, regardless of it's previous existence.
	 * @throws \Quark\Exception When something was wrong in accessing the file.
	 * @todo Add support for stream handles/resources which can be read and written.
	 */
	public function __construct($file, $mode=4, $truncate=false) {
		// Create file if necessary
		if($mode != self::MODE_READONLY && !file_exists($file))
			touch($file);
		else if(!file_exists($file))
            throw new Exception('Unable to open archive in readonly mode, the file did not exist.');

		// Open file according to mode
		switch($mode){
			case self::MODE_READONLY:
				if(is_readable($file))
					$this->handle = fopen($file, 'r');
				else
					throw new Exception('Unable to open ZIP archive, the archive you have requested for opening was not readable by me.');
				break;
			case self::MODE_READWRITE:
				if(is_readable($file) && is_writeable($file))
					$this->handle = fopen($file, 'r+');
				else
					throw new Exception('Unable to open ZIP archive, the archive you have requested for opening was not readable and/or writable by me.');
				break;
			case self::MODE_STREAM:
				if(is_writeable($file))
					$this->handle = fopen($file, 'w');
				else
					throw new Exception('Unable to open ZIP archive for streaming, the archive you have requested for opening was not writable by me.');
				break;
			default:
				throw new Exception('Invalid mode given.');
		}
		$this->mode = $mode;

		if($this->isReadable())
			$this->analyzeArchive();
	}

	/**
	 * Closes the file handle if this had not already been done.
	 */
	public function __destruct(){
		if(!$this->closed)
			$this->close();
	}

	/**
	 * Set the comment text for this archive.
	 * @param string $text
	 * @throws \Quark\Exception If comment is longer than 65535 characters, this method throws an exception.
	 * @return void
	 * @access private
	 */
	public function setComment($text){
		if(strlen($text) < 65535)
			throw new Exception('The comment you gave was too long, I can only add a comment with the maximal length of 65535; The size of an unsigned short.');
		$this->comment = $text;
	}

	/**
	 * Add a file to the archive.
	 * @param string $path File path within archive.
	 * @param resource|string $data String of data or stream to set for this file. (Please use streams where possible)
	 * @param string $comment Optionally a file comment to assign to the file.
	 * @return void
	 */
	public function addFile($path, $data, $comment='') {
		// Fix path
		$file = trim(str_replace('\\', '/', $path), ' /');

		// Add to archive structure
		$this->entries[$file] = array(
			'type' => 'file',
			'data' => $data
		);

		// Immediately flush if streaming.
		if(!$this->isSeekable())
			self::flushFileEntry($file, $data);
	}

	/**
	 * Add a directory to the archive.
	 * @param string $path
	 * @param string $comment Optionally a comment to assign to the directory.
	 * @return mixed
	 */
	public function addDirectory($path, $comment='') {
		// Fix path
		$file = trim(str_replace($path, '\\', '/'), ' /').'/';

		// Add to archive structure
		$this->entries[$file] = array(
			'type' => 'dir'
		);

		// Immediately flush if streaming.
		if(!$this->isSeekable())
			self::flushDirectoryEntry($file);
	}

	/**
	 * Get an archive iterator for the given zip path.
	 * @param string $path
	 * @return ArchiveIterator|bool
	 */
	public function getDirectoryIterator($path) {
		return new ArchiveIterator($this);
	}

	/**
	 * Get the list of items in the given directory path.
	 * @param string $path Relative path within the archive.
	 * @return array
	 */
	public function listDirectory($path) {
		$length = strlen($path);
		$list = array();
		foreach($this->entries as $entry){
			if(substr($entry['filename'], 0, $length) == $path)
				array_push($list, $entry['filename']);
		}
		return $list;
	}

	/**
	 * Check if a directory or file exists within the Archive.
	 * @param $path
	 * @return bool
	 */
	public function exists($path){
		foreach($this->entries as $entry){
			if(trim($entry, '\\/ ') == $path)
				return true;
		}
		return false;
	}

	/**
	 * Extract a file from the Archive.
	 * @param string $filename The file to extract from within the archive.
	 * @param resource|string $target The file on system where the content should be written to, if none is provided it will return the file contents.
	 * @throws \Quark\Exception When the filename doesn't exist within the archive, or the target is not writable/valid.
	 * @return bool|string
	 */
	public function extract($filename, $target = null) {
		if(!isset($this->entries[$filename]))
			throw new Exception('Unable to get the contents for the file "'.$filename.'", as I cannot find the file in the archive\'s central directory.');

		fseek($this->handle, $this->entries[$filename]['position'] + $this->entries[$filename]['header_size'], SEEK_SET); // seek to the file's compressed contents

		if(empty($target))
			return $this->decompressString(
				$this->entries[$filename]['body_size'],
				isset($this->entries[$filename]['compression_method']) ? $this->entries[$filename]['compression_method'] : 8
			);
		else if(is_resource($target))
			return $this->decompressStream(
				$this->entries[$filename]['body_size_raw'],
				isset($this->entries[$filename]['compression_method']) ? $this->entries[$filename]['compression_method'] : 8,
				$target
			);
		else if(is_string($target) && (is_writable($filename) || is_writable(dirname($filename))))
			return $this->decompressStream(
				$this->entries[$filename]['body_size_raw'],
				isset($this->entries[$filename]['compression_method']) ? $this->entries[$filename]['compression_method'] : 8,
				fopen($target, 'w')
			);
		else
			throw new Exception('Target given to Zip->extract was invalid. The location was either invalid or not writable, or the stream/resource given was not writable.');
	}

	/**
	 * Extract all the files in the archive to the given directory.
	 * @param string $targetDirectory Path to the directory where the complete archives structure should be rebuild.
	 * @param int $perms Permission bit-masks of the directories that are created.
	 * @throws \Quark\Exception When the target directory isn't writable.
	 * @return bool Whether or not we were successful.
	 */
	public function extractAll($targetDirectory, $perms=0777) {
		if(!is_writable($targetDirectory))
			throw new Exception('The targetted extraction directory must exist, and be (made) writable.');

		$base = realpath($targetDirectory);
		foreach($this->entries as $entry){
			if($entry['type'] == 'dir')
				mkdir($base.DS.$entry['filename'], $perms, true);
			else
				$this->extract($entry['filename'], $base.DS.dirname($entry['filename']));
		}
		return true;
	}

	/**
	 * Decompress a archive file into a string.
	 * @param $csize
	 * @param $compression_method
	 * @return mixed|string
	 * @throws RuntimeException
	 * @throws \Quark\Exception
	 */
	protected function decompressString($csize, $compression_method){
		$compressed = fread($this->handle, $csize);
		switch($compression_method){
			case self::COMPRESS_STORE:
				return $compressed;
			case self::COMPRESS_DEFLATE:
				return gzinflate($compressed);
			case self::COMPRESS_BZIP2:
				if(function_exists('bzdecompress'))
					return bzdecompress($compressed);
				else
					throw new RuntimeException('Could not decompress file; Required BZIP2 php extension was not loaded/available.');
			case self::COMPRESS_LZMA:
				// Requires php-xz lib (Google it) (Which is in fact lzma2 but seen the used lib, will probably degrade properly)
				// Not tested
				if(function_exists('xz_decompress'))
					return xz_decompress($compressed);
				else
					throw new RuntimeException('Could not decompress file; Required LZMA(2)/XZ php extension was not loaded/available.. The extension required needs to be built from source.');
			default:
				throw new Exception('Unsupported compression algorithm used in this archive ('.$compression_method.').');
		}
	}

	/**
	 * Decompress a file in the archive to another stream. (More efficient)
	 * @param $usize Uncompressed size of the file.
	 * @param $compression_method
	 * @param $stream
	 * @return bool
	 * @throws RuntimeException
	 * @throws \Quark\Exception
	 */
	protected function decompressStream($usize, $compression_method, $stream){
		$filter = null;
		switch($compression_method){
			case self::COMPRESS_STORE:
				break;
			case self::COMPRESS_DEFLATE:
				$filter = stream_filter_append($this->handle, "zlib.inflate", STREAM_FILTER_READ);
				break;
			case self::COMPRESS_BZIP2:
				if(function_exists('bzdecompress'))
					$filter = stream_filter_append($this->handle, "bzip2.decompress", STREAM_FILTER_READ);
				else
					throw new RuntimeException('Could not decompress file; Required BZIP2 php extension was not loaded/available.');
			default:
				throw new Exception('Unsupported compression algorithm used in this archive ('.$compression_method.').');
		}

		$readsize = 0;
		while (!feof($this->handle) && $readsize < $usize){
			$readsize += fwrite($stream, fread($this->handle, min(self::BLOCK_SIZE, $usize-$readsize)));
		}

		if($filter != null)
			stream_filter_remove($filter);

		return ($readsize == $usize);
	}

	/**
	 * Flush the created archive to disk (When not streaming), and closes the file handle.
	 * @return void
	 */
	public function close() {
		// When we were read/writing, flush all files/dirs
		if($this->mode == self::MODE_READWRITE){
			foreach($this->entries as $file => $entry) {
                if($entry['type'] == 'dir')
                    self::flushDirectoryEntry($file);
                else
                    self::flushFileEntry($file, $entry['data']);
			}

			// Truncate the file directory, this get's reprocessed after this
			ftruncate($this->handle, ftell($this->handle));
		}

		// When we are streaming or reading/writing, flush the (new) directory
		if($this->mode == self::MODE_READWRITE || $this->mode == self::MODE_STREAM){
			// @todo Can't remember what I wanted to do here..
		}

        // Flush central directory when not streaming.
        if($this->mode == self::MODE_READWRITE)
		    $this->flushCentralDirectory();

		fclose($this->handle);
	}

	/**
	 * Whether or not this archive is readable.
	 * @return bool
	 */
	public function isReadable(){
		return ($this->mode != self::MODE_STREAM);
	}

	/**
	 * Whether or not this archive is writable.
	 * @return bool
	 */
	public function isWritable(){
		return ($this->mode != self::MODE_READONLY);
	}

	/**
	 * Whether or not this archive is seekable. (AKA anything but a stream)
	 * @return bool
	 */
	public function isSeekable(){
		return ($this->mode != self::MODE_STREAM);
	}

	/**
	 * Analyzes the archive and stores all meta-data
	 * (Not the actual blobs itself, only where to find them, the modification dates of the files, etc.)
	 */
	protected function analyzeArchive(){
		// Read through
		$cdi = false; // Central Directory Indicator
		while(!feof($this->handle)){
			$position = ftell($this->handle);
			$blob = fread($this->handle, $cdi ? self::CENTRAL_DIRECTORY_RECORD_LENGTH : self::LOCAL_FILE_HEADER_LENGTH);
            if(strlen($blob) == 0)
                break;

			$signature = substr($blob, 0, 4); // Blob signature
			if($signature == self::LOCAL_FILE_HEADER_SIGNATURE){
				$result = self::parseLocalFileHeader($blob);
				$entry = $result['entry'];
				$entry['position'] = $position;
            	$entry['filename'] = fread($this->handle, $result['raw']['filename_length']);
				$this->entries[$entry['filename']] = $entry; // add the entry
				fseek($this->handle, $result['raw']['extra_length'] + $result['raw']['compressed_size'], SEEK_CUR); // skip extra field and compressed file
				// @todo maybe check if there is a descriptor here
				// @todo maybe check crc32 here already?
			}else if($signature == self::CENTRAL_DIRECTORY_RECORD_SIGNATURE){
				if($cdi === false){ // We found the central directory! :3
					$cdi = true;
					// @note Decide which one is better; first one is more portable if the header sizes would change, second one will work on streams too.
					//fseek($this->handle, ftell($this->handle) - self::LOCAL_FILE_HEADER_LENGTH); continue; // Rewind a bit.
					$blob .= fread($this->handle, self::CENTRAL_DIRECTORY_RECORD_LENGTH - self::LOCAL_FILE_HEADER_LENGTH);
				}
				$result = self::parseCentralDirectoryRecord($blob);
				$entry = $result['entry'];
				$entry['filename'] = fread($this->handle, $result['raw']['filename_length']);
				if(!isset($this->entries[$entry['filename']])) throw new Exception('Error whilst reading archive; The archives central directory mentions a file which was not actually found in the archive beforehand, filename mismatch perhaps? {Bad writing Ziplib!}');
				fseek($this->handle, $result['raw']['extra_length'], SEEK_CUR); // seek past extra field
				if($result['raw']['comment_length'] > 0)
					$entry['comment'] = fread($this->handle, $result['raw']['comment_length']); // add the comment
				$this->entries[$entry['filename']] = array_merge($this->entries[$entry['filename']], $entry);


				// skip the signature
				$header = fread($this->handle, 6);
				if(substr($header, 0, 4) == "\x50\x4b\x05\x05"){// true has a signature, else: does not.
					$size = unpack('v1size', substr($header, 4))['size'];
					if($size > 0)
						fseek($this->handle, $size, SEEK_CUR);
				}else
					fseek($this->handle, -6, SEEK_CUR); // @todo this doesnt really work on streams
			}else if($signature == self::ZIP64_END_OF_CENTRAL_DIRECTORY_SIGNATURE){
				throw new Exception('STUB! No Zip64 support yet, sorry..');
			}else if($signature == self::END_OF_CENTRAL_DIRECTORY_SIGNATURE){
				// supposed end of the file: read through and process (Unless the file comment is real big, it is probably shorter than the CENTRAL_DIRECTORY_RECORD_LENGTH)
				while(!feof($this->handle))
					$blob .= fread($this->handle, self::BLOCK_SIZE);
				// parse
				$parsed = self::parseEndOfCentralDirectoryRecord($blob);
				$this->comment = substr($blob, self::END_OF_CENTRAL_DIRECTORY_LENGTH, $parsed['comment_length']);
			}else
				throw new Exception('Unable to read archive, found unidentified signature. Is this really ZIP archive? (Signature was: "'.bin2hex($signature).'", size '.strlen($signature).' bytes on offset '.$position.')');
		}

		// Done.
	}

	/**
	 * Splits up and unpacks the local file headers.
	 * @param $blob
	 * @return array
	 */
	protected static function parseLocalFileHeader($blob){
		$unpacked = unpack('c4/v1version/v1gp_flag/v1compression_method/v1file_time/v1file_date/V1crc32/V1compressed_size/V1uncompressed_size/v1filename_length/v1extra_length', $blob);
        $entry = array(
            'position' => 0,
            'header_size' => self::LOCAL_FILE_HEADER_LENGTH + $unpacked['filename_length'] + $unpacked['extra_length'],
            'body_size' => $unpacked['compressed_size'],
            'body_size_raw' => $unpacked['uncompressed_size'],
            'filemtime' => self::dosDateTimeToTimestamp($unpacked['file_time'].$unpacked['file_date']),
            'crc32' => $unpacked['crc32'],
        );
		return array('raw' => $unpacked, 'entry' => $entry);
	}

	/**
	 * Splits up and unpacks the central directory records.
	 * @param $blob
	 * @return array
	 * @throws RuntimeException
	 */
	protected static function parseCentralDirectoryRecord($blob){
		$unpacked = unpack('c4/v1version_creator/v1version_needed/v1gp_flag/v1compression_method/v1file_time/v1file_date/V1crc32/V1compressed_size/V1uncompressed_size/v1filename_length/v1extra_length/v1comment_length/v1disk_started/v1internal_attr/V1external_attr/V1relative_offset', $blob);
		if($unpacked['disk_started'] != 0)
			throw new RuntimeException('Having ZIP archives that span multiple disks is unsupported by this class!');
		$entry = array(
			'position' => $unpacked['relative_offset'], // we assume there's only one disk.
			'header_size' => self::LOCAL_FILE_HEADER_LENGTH + $unpacked['filename_length'] + $unpacked['extra_length'] + $unpacked['comment_length'],
			'body_size' => $unpacked['compressed_size'],
			'body_size_raw' => $unpacked['uncompressed_size'],
			'filemtime' => self::dosDateTimeToTimestamp($unpacked['file_time'].$unpacked['file_date']),
			'crc32' => $unpacked['crc32'],
		);
		return array('raw' => $unpacked, 'entry' => $entry);
	}

	/**
	 * Unpacks the EO Central Directory Record.
	 * @param $blob
	 * @return array
	 */
	protected static function parseEndOfCentralDirectoryRecord($blob){
		$unpacked = unpack('c4/v1disk_number/v1cd_start_disk_number/v1total_disk_entries/v1total_dir_entries/V1cd_size/V1total_cd_offset/v1comment_length', $blob);
		return $unpacked;
	}

	/**
	 * Creates the necessary file headers, data and data-descriptor for the entry and writes them to the current handle.
	 * @param string $name Name for the file
	 * @param string|resource $data Data to write and/or analyse for descriptor
	 * @throws \Quark\Exception Writes a file unto a stream.
	 */
	protected function flushFileEntry($name, $data){
		// Set current position
		$this->entries[$name]['position'] = ftell($this->handle);

		// When $data is a resource, and we are streaming do a direct stream
		if(is_resource($data) && $this->mode == self::MODE_STREAM){
			// Write the header
			$filemtime = time();
			$header = self::buildFileHeader($name, $filemtime);
			$headSize = strlen($header);
			fwrite($this->handle, $header);
			unset($header);

			// Write the data in
			$filter = stream_filter_append($this->handle, "zlib.deflate", STREAM_FILTER_WRITE);
			// stream from/to
			$crc32 = hash_init('crc32b'); // Create hash context
			$usize = 0;
			$ipos = ftell($this->handle); // Initial position (helps calculate compressed size.)
			while(!feof($data)){
				$block = fread($data, self::BLOCK_SIZE); // Read block

				$usize += strlen($block); // Add to uncompressed size

				hash_update($crc32, $block); // Update hash
				fwrite($this->handle, $block); // Write to stream
				unset($block); // Help the gc a bit.
			}
			$crc32 = hash_final($crc32, true);
			$csize = ftell($this->handle) - $ipos;

			// Finally remove the stream deflate filter.
			stream_filter_remove($filter);

			// Write the data descriptor
			$descriptor = self::buildDataDescriptor($crc32, $csize, $usize);
			$this->entries[$name]['descriptor_size'] = strlen($descriptor);
			fwrite($this->handle, $descriptor);
			unset($descriptor);
		}

		// Read in the file and write out the resulting data.
		else{
			// Find last file modification timestamp
			if(is_string($data))
				$filemtime = time();
			else{
				$meta = stream_get_meta_data($data);
				if($meta['mode'] == 'w') // Make sure this isnt write only
					throw new Exception('Tried to add file to archive with write only stream.');
				if($meta['eof'] == true)
					throw new Exception('I cannot read from a stream that is already at it\'s end.');
				$filemtime = @filemtime($meta['uri']);
				if($filemtime == false)
					$filemtime = time();
			}

			// Make available data (If it's a resource)
			if(is_resource($data))
				$data = stream_get_contents($data, PHP_INT_MAX-1000);


			$crc32 = hexdec(hash('crc32b', $data, false));// Calculate hash (We use this method, because the order of the bytes is incurrect if we dont pack it all)
			//$crc32 = crc32($data);			// Calculate hash
			$deflated = gzdeflate($data);		// Normal Deflate
			$csize = strlen($deflated);			// Compressed size
			$usize = strlen($data);				// Uncompressed size

			// Free Mem
			unset($data);
			gc_collect_cycles();

			// Write the header
			$header = self::buildFileHeader($name, $filemtime, $crc32, $csize, $usize);
			$headSize = strlen($header);
			fwrite($this->handle, $header);
			unset($header);

			// Write the body
			fwrite($this->handle, $deflated);
			unset($deflated);
		}

		// Update structure entry to include it's file/stream position
		$this->entries[$name]['header_size'] = $headSize;
		$this->entries[$name]['body_size'] = $csize;
		$this->entries[$name]['body_size_raw'] = $usize;

		// Set it's data verification bits needed for the central directory
		$this->entries[$name]['filemtime'] = $filemtime;
		$this->entries[$name]['crc32'] = $crc32;

		// Done.
	}

	/**
	 * Creates the necessary file-headers for a directory.
	 * @param string $name Name for the new directory
	 * @param int $filemtime Optional date when the directory was created. (Defaults to current date and time)
	 */
	protected function flushDirectoryEntry($name, $filemtime=null){
		if(empty($filemtime))
			$filemtime = time();

		// Save current position
		$this->entries[$name]['position'] = ftell($this->handle);

		// Directory header
		$header = self::buildDirectoryHeader($name, $filemtime);
		$this->entries[$name]['header_size'] = strlen($header);
		fwrite($this->handle, $header);

		// Empty body
		//$this->entries[$name]['body_size'] = 0;
		//$this->entries[$name]['body_size_raw'] = 0;

		// Dir descriptor
		$descriptor = self::buildDataDescriptor(0, 0, 0);
		$this->entries[$name]['descriptor_size'] = strlen($descriptor);
		fwrite($this->handle, $descriptor);

		// Other info
		$this->entries[$name]['filemtime'] = $filemtime;

		// Done.
	}

	/**
	 * Flushes the required central directory records to the stream. (After calling this don't write any more files!)
	 */
	protected function flushCentralDirectory(){
		// Record current position
		$cdoffset = ftell($this->handle);

		// Loop over entries and..
		$ecount = 0;
		$cdsize = 0;
		foreach($this->entries as $name => $entry){
			// ..add each entry to the central directory
			if($entry['type'] == 'dir')
				$blob = $this->buildCentralDirectoryDirectoryHeader($name, $entry['filemtime'], $entry['position']);
			else
				$blob = $this->buildCentralDirectoryFileHeader($name, $entry['filemtime'], $entry['crc32'], $entry['body_size'], $entry['body_size_raw'], $entry['position']);
			$cdsize += strlen($blob);
			$ecount++;
			fwrite($this->handle, $blob);
		}

		// Write directory signature (many libs forget this, and I'm not sure if I should even include this, as the spec doesn't even explain what format this 'signature' should be)
		//$signature = $this->buildCentralDirectorySignature();
		//$cdsize += strlen($signature);
		//fwrite($this->handle, $signature);

		// Write the (regular) End of central directory record
		fwrite($this->handle, $this->buildEndOfCentralDirectory($ecount, $cdsize, $cdoffset, $this->comment));
	}

	/**
	 * Get the estimated size of the file header in bytes.
	 * @param string $name
	 * @return int Size of header in bytes.
	 */
	protected static function predictFileHeaderSize($name){
		return (30 + strlen($name));
	}

	// http://www.darkfader.net/toolbox/convert/
	/**
	 * @param string $name
	 * @param int $filemtime *NIX Timestamp
	 * @param int $crc32 Unsigned Integer
	 * @param int $csize
	 * @param int $usize
	 * @return string
	 */
	protected static function buildFileHeader($name, $filemtime, $crc32=null, $csize=null, $usize=null){
		$blob = self::LOCAL_FILE_HEADER_SIGNATURE; // local file header signature
		$blob .= "\x14\x00";				// version needed to extract (2.0)
		if($crc32 == null)
			$blob .= "\x20\x00";			// general purpose bit flag (normal deflate, set bit 3: were streaming)
		else
			$blob .= "\x00\x00";			// general purpose bit flag (normal deflate, no need to set)
		$blob .= "\x08\x00";				// compression method (8 - The file is Deflated)
		$blob .= self::timestampToDosDateTime($filemtime);	// last mod file time & last mod file date
		if($crc32 == null){
			$blob .= pack('V', 0);			// crc-32
			$blob .= pack('V', 0); 			// compressed size
			$blob .= pack('V', 0);			// uncompressed size
		}else{
			$blob .= pack('V', $crc32);		// crc-32
			$blob .= pack('V', $csize); 	// compressed size
			$blob .= pack('V', $usize);		// uncompressed size
		}
		$blob .= pack('v', strlen($name));	// file name length
		$blob .= pack('v', 0);				// extra field length

		$blob .= $name;
		return $blob;
	}

	/**
	 * Data Descriptor - Comes after a 'local file header' and a file's data, thus is created for each file. Only present when in streaming mode.
	 * @param int $crc32 !Unsigned Integer
	 * @param int $csize
	 * @param int $usize
	 * @return string
	 */
	protected static function buildDataDescriptor($crc32, $csize, $usize){
		$packed_hash = pack('V', $crc32);
		if($packed_hash == "\x00\x00\x00\x00")
			$blob = $crc32;			// crc-32 (Hash was already big endian)
		else
			$blob = $packed_hash;	// crc-32 (Convert)
		$blob .= pack('V', $csize); // compressed size
		$blob .= pack('V', $usize);	// uncompressed size
		return $blob;
	}

	/**
	 * @param string $name
	 * @param int $filemtime *NIX Timestamp
	 * @return string
	 */
	protected static function buildDirectoryHeader($name, $filemtime){
		$blob = "\x50\x4B\x03\x04";			// local file header signature
		$blob .= "\x14\x00";				// version needed to extract (2)
		$blob .= "\x00\x00";				// general purpose bit flag (none)
		$blob .= "\x00\x00";				// compression method (none, this is a dir)
		$blob .= self::timestampToDosDateTime($filemtime);	// last mod file time & last mod file date
		$blob .= pack('V', 0);				// crc-32 (functions to remove unsigned bit)
		$blob .= pack('V', 0); 				// compressed size
		$blob .= pack('V', 0);				// uncompressed size
		$blob .= pack('v', strlen($name));	// file name length
		$blob .= pack('v', 0);				// extra field length

		$blob .= $name;
		return $blob;
	}

	/**
	 * @param string $name
	 * @param int $filemtime *NIX Timestamp
	 * @param int $crc32 Unsigned Integer
	 * @param int $csize
	 * @param int $usize
	 * @param int $offset Offset of the original file header from the disk it is on (In this class always disk 0)
	 * @param string $comment
	 * @return string
	 */
	protected static function buildCentralDirectoryFileHeader($name, $filemtime, $crc32, $csize, $usize, $offset, $comment=''){
		$blob = self::CENTRAL_DIRECTORY_RECORD_SIGNATURE; // central file header signature
		$blob .= "\x00\x00";				// version made by (MSDOS/FAT)
		$blob .= "\x14\x00";				// version needed to extract
		$blob .= "\x00\x00";				// general purpose bit flag
		$blob .= "\x08\x00";				// compression method
		$blob .= self::timestampToDosDateTime($filemtime);	// last mod file time & last mod file date
		$blob .= pack('V', $crc32);;		// crc-32
		$blob .= pack('V', $csize); 		// compressed size
		$blob .= pack('V', $usize);			// uncompressed size
		$blob .= pack('v', strlen($name));	// file name length
		$blob .= pack('v', 0);				// extra field length
		$blob .= pack('v', strlen($comment)); // file comment length
		$blob .= pack('v', 0);				// disk number start
		$blob .= pack('v', 0);				// internal file attributes
		$blob .= pack('V', 32);				// external file attributes (eg. 16 for dir, 0/32 for file) - http://www.win.tue.nl/~aeb/linux/fs/fat/fat-1.html - See Directory entry: Byte 11: Attribute - A bit vector...
		$blob .= pack('V', $offset);		// relative offset of local header

		$blob .= $name;		// file name (variable size)
							// extra field (variable size) - unused
		$blob .= $comment;	// file comment (variable size)

		return $blob;
	}

	/**
	 * @param string $name
	 * @param int $filemtime *NIX Timestamp
	 * @param int $offset Offset of the original file header from the disk it is on (In this class always disk 0)
	 * @param string $comment
	 * @return string
	 */
	protected static function buildCentralDirectoryDirectoryHeader($name, $filemtime, $offset, $comment=''){
		$blob = self::CENTRAL_DIRECTORY_RECORD_SIGNATURE; // central file header signature
		$blob .= "\x00\x00";				// version made by (MSDOS/FAT)
		$blob .= "\x14\x00";				// version needed to extract
		$blob .= "\x00\x00";				// general purpose bit flag
		$blob .= "\x08\x00";				// compression method
		$blob .= self::timestampToDosDateTime($filemtime);	// last mod file time & last mod file date
		$blob .= pack('V', 0);				// crc-32
		$blob .= pack('V', 0); 				// compressed size
		$blob .= pack('V', 0);				// uncompressed size
		$blob .= pack('v', strlen($name));	// file name length
		$blob .= pack('v', 0);				// extra field length
		$blob .= pack('v', strlen($comment)); // file comment length
		$blob .= pack('v', 0);				// disk number start
		$blob .= pack('v', 0);				// internal file attributes
		$blob .= pack('V', 16);				// external file attributes (eg. 16 for dir, 0/32 for file) - http://www.win.tue.nl/~aeb/linux/fs/fat/fat-1.html - See Directory entry: Byte 11: Attribute - A bit vector...
		$blob .= pack('V', $offset);		// relative offset of local header

		$blob .= $name;		// file name (variable size)
							// extra field (variable size) - unused
		$blob .= $comment;	// file comment (variable size)

		return $blob;
	}

	/**
	 * Creates the central directory signature.
	 * @todo Does not really do anything, anyone that has knowledge of the content of such a signature is welcome to modify this method.
	 * @return string
	 */
	protected static function buildCentralDirectorySignature(){
		$blob = "\x50\x4B\x05\x05";	// header signature
		$blob .= pack('v', 0);
		return $blob;
	}

	/**
	 * Build the end of central directory record.
	 * @param int $totalEntries Total number of entries in the Central Directory.
	 * @param int $cdsize Size in bytes of the central directory.
	 * @param int $cdoffset Offset in bytes of the central directory from the start of the archive, disk 0 (We do not support disks, so the beginning of the file.)
	 * @param string $comment A (Global) Zip file comment.
	 * @return string
	 */
	protected static function buildEndOfCentralDirectory($totalEntries, $cdsize, $cdoffset, $comment=''){
		$blob = "\x50\x4B\x05\x06"; 		// end of central dir signature
		$blob .= pack('v', 0);				// number of this disk
		$blob .= pack('v', 0);				// number of the disk with the start of the central directory
		$blob .= pack('v', $totalEntries);	// total number of entries in the central directory on this disk
		$blob .= pack('v', $totalEntries);	// total number of entries in the central directory
		$blob .= pack('V', $cdsize);		// size of the central directory
		$blob .= pack('V', $cdoffset);		// offset of start of central directory with respect to the starting disk number (I think this is what this means, I'm still not quite sure)
		$blob .= pack('v', strlen($comment)); // .ZIP file comment length
		$blob .= $comment;					// .ZIP file comment (variable size)
		return $blob;
	}

	/**
	 * Formats the given Unix Timestamp as a FAT/DOS date and time bit field.
	 *
	 * For reference: (Source; http://msdn.microsoft.com/en-us/library/windows/desktop/ms724274(v=vs.85).aspx)
	 *
	 * TIME:
	 *  Bits	No	Description
	 *  0–4		5	Second divided by 2
	 *  5–10		6	Minute (0–59)
	 *  11–15	5	Hour (0–23 on a 24-hour clock)
	 *
	 * DATE:
	 * 	Bits	No	Description
	 *  0–4		5	Day of the month (1–31)
	 *  5–8		4	Month (1 = January, 2 = February, etc.)
	 *  9-15	7	Year offset from 1980 (add 1980 to get actual year)
	 *
	 * @param int $timestamp Valid UNIX Timestamp before the date of 12/31/2107. (FAT date does not go beyond that.)
	 * @return string Contains the binary FAT time and date fields after each other. (2x 16bits = 32bits = 4bytes)
	 * @author Jeffrey vH
	 */
	protected static function timestampToDosDateTime($timestamp){
		//@todo works like sh*t. The other one was done somewhat better.
		if($timestamp > 4354732800){
			Error::raiseWarning('End of DOS/FAT epoch: Tried to convert a date past 12/31/2107 to FAT format.');
			return "\x00\x00\x00\x00"; // Null.
		}

		$bits = "";
		$datetime = getdate($timestamp);

		// Time
		$bits .= str_pad(decbin(floor($datetime['seconds'] / 2)), 5, '0', STR_PAD_LEFT); // Seconds /2 (Low res)
		$bits .= str_pad(decbin($datetime['minutes']), 6, '0', STR_PAD_LEFT); // Minutes
		$bits .= str_pad(decbin($datetime['hours']), 5, '0', STR_PAD_LEFT); // Hours

		// Date
		$bits .= str_pad(decbin($datetime['mday']), 5, '0', STR_PAD_LEFT); // Days (1-31)
		$bits .= str_pad(decbin($datetime['mon']), 4, '0', STR_PAD_LEFT); // Month (1-12)
		$bits .= str_pad(decbin($datetime['year'] - 1980), 7, '0', STR_PAD_LEFT); // Years (Year - 1980 {DOS Epoch})

		$converted = base_convert($bits, 2, 16);
		return hex2bin($converted);
	}

	/**
	 * Converts a DOS/FAT formatted date time field and converts it to a UNIX timestamp.
	 * @see Zip::timestampToDosDateTime()
	 * @param string|integer $bytes
	 * @return int
	 */
	protected static function dosDateTimeToTimestamp($bytes){
		// Split the byte array up
		$bytes = unpack("vtime/vdate", $bytes);

		// Time
		$time = $bytes['time'];
		$second = 2 * ($time & 31); // Take the last 5 bits && multiply by two (Low precision)
		$minute = ($time & 2016) >> 5; // take the middle 6 bits and drop off the seconds
		$hour = ($time & 63488) >> 11; // Take the last 5 bits && chop off the rest

		// Date
		$date = $bytes['date'];
		$day = $date & 31; // = 11111 So this takes the last 5 bits from the string.
		$month = ($date & 480) >> 5; // lose the $day bits && only keep the resulting first 4 bits
		$year = 1980 + (($date & 65024) >> 9); // 1980 (Dos epoch) + shift away the first 9 bits(Rest of date) && keep the last 7

		// Make date
		return mktime($hour, $minute, $second, $month, $day, $year);
	}
}