<?php
/**
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		November 8, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define namespace
namespace Quark\Protocols\HTTP;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Class MimeTypes
 *
 * This class maintains a static list of all known mime-types and any possible extensions for them.
 *
 * Please note that this class is dependent on the system's /etc/mime.types file when the FileInfo extension is
 * explicitly disabled. The fileinfo extension is enabled by default after php 5.3. It can however fall-back on the list
 * defined below, which only defines the essential mime-types.
 *
 * @package Quark\Protocols\HTTP
 */
class MimeTypes {
	/**
	 * @var array List of default, bare-minimum mime-types with their extensions if no /etc/mime.types file is available and finfo is not available.
	 */
	private static $defaultMimeTypes = array(
		'application/json' => array('json', 'map'),
		'application/javascript' => array('js', 'jsonp'),
		'application/octet-stream' => array('zip', 'rar', '7z', 'gz', 'bz', 'xz'),

		'text/plain' => array('list','txt'),
		'text/html' => array('html', 'htm'),
		'text/css' => array('css', 'scss'),

		'image/gif' => array('gif'),
		'image/jpeg' => array('jpeg', 'jpg', 'jpe'),
		'image/png' => array('png'),
		'image/svg+xml' => array('svg', 'svgz'),
		'image/tiff' => array('tiff', 'tif'),
		'image/x-icon' => array('gif'),
		'image/x-ms-bmp' => array('bmp'),
		'image/x-photoshop' => array('psd'),

		'application/vnd.ms-fontobject' => array('eot'),
		'application/x-font-woff' => array('woff'),
		'application/x-font-ttf' => array('ttf')
	);

	/**
	 * @var array The contents of /etc/mime.types will be read into here if finfo is not available.
	 */
	private static $mimeTypes;

	/**
	 * Get the mime-type for the given file.
	 * @param string $file The file to test.
	 * @param bool $rfc2045 Whether or not to format it with rfc2045 compliance. This includes the charset, e.g. "image/jpeg" becomes "image/jpeg; charset=binary"
	 * @param string $default Fallback value.
	 * @throws \RuntimeException When the input file does not exist/is not readable.
	 * @return string
	 */
	public static function forFile($file, $rfc2045=false, $default='application/octet-stream'){
		// @todo
		if(!is_readable($file))
			throw new \RuntimeException('File is unreadable, cannot continue.');

		if(function_exists('finfo_open')){
			$info = finfo_open($rfc2045 ? FILEINFO_MIME : FILEINFO_MIME_TYPE);
			$mime = finfo_file($info, $file);
			finfo_close($info);
			return $mime;
		}

		// No finfo, try manually parsing /etc/mime.type
		self::_readMimeTypes();
		$ext = strtolower(substr(strrchr($file, '.'), 1));
		if($rfc2045){
			foreach(self::$mimeTypes as $mimeType => $exts){
				if(in_array($ext, $exts)){
					list($type, $subtype) = MimeParser::parse($mimeType);
					switch($type){
						case 'image':
						case 'audio':
						case 'video':
							return $mimeType.'; charset=binary'; // Above average probability this is binary.
						case 'message':
						case 'application':
						/** @noinspection PhpMissingBreakStatementInspection */
						case 'text':
							if(function_exists('mb_detect_encoding')){
								$fh = fopen($file, 'r');
								$encoding = mb_detect_encoding(fread($fh, 64));
								fclose($fh);
								if($encoding !== false)
									return $mimeType.'; charset='.$encoding;
							}
							return $mimeType; // be safe and let the client detect
							//return $mimeType.'; charset=iso-8859-1'; // return the http/1.1 default
						case 'model':
						case 'multipart':
						default:
							return $mimeType; // we have no reliable way to detect this.
					}
				}
			}
			return $default;
		}else
			return self::forExtension($ext, $default);
	}

	/**
	 * Find the probable mime type for the given extension.
	 * @param string $extension
	 * @param string $default
	 * @return string
	 */
	public static function forExtension($extension, $default='application/octet-stream'){
		foreach(self::$mimeTypes as $type => $exts){
			if(in_array($extension, $exts))
				return $type;
		}
		return $default;
	}

	/**
	 * Get the first extension found for the given mime-type.
	 * @param string $mimeType
	 * @return string|null
	 */
	public static function findExtensionByMimeType($mimeType){
		if(isset(self::$mimeTypes[$mimeType]))
			return self::$mimeTypes[$mimeType][0];
		return null;
	}

	/**
	 * Read and parse the /etc/mime.types file into the MimeType::$mimeTypes variable.
	 */
	private static function _readMimeTypes(){
		if(is_readable('/etc/mime.types')){
			self::$mimeTypes = array();
			$file = file('/etc/mime.types');
			foreach($file as $line){
				$line = trim($line);
				if(empty($line) || $line[0] == '#') continue;
				list($mimeType, $fileType) = explode(' ', $line, 2);
				self::$mimeTypes[$mimeType] = explode(' ', trim($fileType));
			}
		}else self::$mimeTypes =& self::$defaultMimeTypes;
	}
}
