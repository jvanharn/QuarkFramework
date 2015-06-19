<?php
/**
 * The Document Class
 * 
 * @package		Quark-Framework
 * @version		$Id: document.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 2, 2011
 * @copyright	Copyright (C) 2011-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2011 Jeffrey van Harn
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
namespace Quark\Document;
use Quark\Document\Layout\Layout;
use Quark\Event\baseObservable;
use Quark\Event\Observable;
use Quark\Protocols\HTTP\IResponse;
use Quark\Protocols\HTTP\Request;
use Quark\Protocols\HTTP\Response;
use Quark\System\Router\Router;
use Quark\Util\Singleton;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Import the Dependencies
\Quark\import(
	'Framework.Util.Singleton',
	'Framework.Event.*',
	
	'Framework.Document.Element',
	'Framework.Document.Collection',
	//'Framework.Document.ICollection', // Gets loaded by the above rule.
	'Framework.Document.Style',
	'Framework.Document.Headers',
	'Framework.Document.Resources',
	'Framework.Document.Layout.Layout',
	'Framework.Document.Utils.Underscore'
);

/**
 * Document Class
 * 
 * Generates the basic HTML(5) document structure, and makes it skinnable.
 *
 * @package Quark-Framework
 * @subpackage UserInterface
 *
 * @property-read Layout $layout The object which manages all resources/assets which are used in the document, and makes sure they do not conflict with each other.
 * @property-read Headers $headers The object which manages all tags in the head of the document.
 * @property-read ResourceManager $resources The object which manages all resources/assets which are used in the document, and makes sure they do not conflict with each other.
 * @property-read string $doctype HTML Document-type for this document.
 * @property-read string $encoding The character encoding for this document.
 * @property-read bool $xhtml Whether or not this document uses XHTML style unclosed tags.
 *
 * @method boolean place() place(IElement $elem, string $position='') Place an element onto the current {@link \Quark\Document\Layout\Layout::place() layout}.
 */
class Document implements Singleton, Observable {
	use	baseObservable;
	
	// Preset Document Types
	const TYPE_HTML5				= 'HTML5';
	const TYPE_HTML					= self::TYPE_HTML5;
	
	const TYPE_HTML4_STRICT			= 'HTML4.01_STRICT';
	const TYPE_HTML4_TRANSITIONAL	= 'HTML4.01_TRANSITIONAL';
	const TYPE_HTML4_FRAME			= 'HTML4.01_FRAME';
	const TYPE_HTML4				= self::TYPE_HTML4_STRICT;
	
	const TYPE_XHTML_STRICT			= 'XHTML_STRICT';
	const TYPE_XHTML_TRANSITIONAL	= 'XHTML_TRANSITIONAL';
	const TYPE_XHTML_FRAME			= 'XHTML_FRAME';
	const TYPE_XHTML				= self::TYPE_XHTML_STRICT;
	
	/**
	 * Multidimensional array containing all the preset values for the various document types.
	 * @var array
	 */
	protected static $documentProperties = array(
		self::TYPE_HTML5 => array(
			'doctype'	=> '<!doctype html>',
			'charset'	=> self::CHARSET_UTF8,
			'xmlns'		=> false,
			'xhtml'		=> true
		),
		self::TYPE_HTML4_STRICT => array(
			'doctype'	=> '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">',
			'charset'	=> self::CHARSET_LATIN1,
			'xmlns'		=> false,
			'xhtml'		=> false
		),
		self::TYPE_HTML4_TRANSITIONAL => array(
			'doctype'	=> '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
			'charset'	=> self::CHARSET_LATIN1,
			'xmlns'		=> false,
			'xhtml'		=> false
		),
		self::TYPE_HTML4_FRAME => array(
			'doctype'	=> '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">',
			'charset'	=> self::CHARSET_LATIN1,
			'xmlns'		=> false,
			'xhtml'		=> false
		),
		self::TYPE_XHTML_STRICT => array(
			'doctype'	=> '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">',
			'charset'	=> self::CHARSET_UTF8,
			'xmlns'		=> 'http://www.w3.org/1999/xhtml',
			'xhtml'		=> true
		),
		self::TYPE_XHTML_TRANSITIONAL => array(
			'doctype'	=> '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
			'charset'	=> self::CHARSET_UTF8,
			'xmlns'		=> 'http://www.w3.org/1999/xhtml',
			'xhtml'		=> true
		),
		self::TYPE_XHTML_FRAME=> array(
			'doctype'	=> '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">',
			'charset'	=> self::CHARSET_UTF8,
			'xmlns'		=> 'http://www.w3.org/1999/xhtml',
			'xhtml'		=> true
		)
	);
	
	// Preset Charsets
	// There are no specific region charsets included other than Latin-9 which will probably be removed too. We want to encourage use of utf-8 for those usecases, off course you are free to just use the EUC-JP string or others yourself.
	const CHARSET_UTF8		= 'UTF-8';
	const CHARSET_UTF16		= 'UTF-16';
	const CHARSET_LATIN1	= 'ISO-8859-1';
	const CHARSET_LATIN9	= 'ISO-8859-15';
	const CHARSET_ASCII		= 'US_ASCII';
	
	/**
	 * Current document instance
	 * @var \Quark\Document\Document
	 */
	private static $_instance = null;
	
	/**
	 * Whether or not the class was already saved. (For the shutdown func)
	 * @var bool
	 */
	private static $_saved = false;
	
	// Document properties
	/**
	 * Document Type Constant
	 * @var string
	 */
	protected $type;
	
	/**
	 * Current doctype string
	 * @var string
	 */
	protected $doctype;
	
	/**
	 * Character encoding to use.
	 * @var string
	 */
	protected $encoding;
	
	/**
	 * XML Namespace URL for x(ht)ml documents.
	 * @var string
	 */
	protected $xmlns;
	
	/**
	 * Whether or not to use xhtml style tags
	 * @var boolean
	 */
	protected $xhtml;
	
	// Document parts
	/**
	 * List of headers that are applied to the current document
	 * @var \Quark\Document\Headers
	 */
	protected $headers;
	
	/**
	 * The layout manager
	 * @var \Quark\Document\Layout\Layout
	 */
	protected $layout;

	/**
	 * Resource Manager
	 * @var \Quark\Document\ResourceManager
	 */
	protected $resources;
	
	/**
	 * @see \Quark\Document\Document::createInstance
	 */
	protected function __construct(Layout $layout, $type, $doctype, $encoding, $xmlns, $xhtml, Headers $headers){
		// Initialize vital objects
		$this->headers = $headers;
		$this->layout = $layout;
		
		// Set the options
		$this->type = $type;
		$this->setDoctype($doctype);
		$this->setEncoding($encoding);
		$this->setXMLNamespace($xmlns);
		$this->setXHTML($xhtml);

		// Initialize the headers object
		$this->headers->add(Headers::TITLE, array(), 'Quark Framework');
		$this->headers->add(Headers::META, array('name'=>'viewport', 'content'=>'width=device-width, initial-scale=1.0, maximum-scale=1.0'));
		$this->headers->add(Headers::LINK, array('rel'=>'shortcut icon', 'href'=>'/assets/images/icon.ico', 'type'=>'image/x-icon'));
		
		// Register the shutdown function to make sure there always is some output
		register_shutdown_function(function(){
			// @TODO Print the saved value of this class, if not other output was made
			// Problems with correcly detecting if there was any output.. This works untill then though.
			if(!headers_sent() && !self::$_saved && !defined('ROUTED_REQUEST')) self::$_instance->display();
		});
	}
	
	/**
	 * Get the currently used doctype string.
	 * @return string
	 */
	public function getDoctype(){
		return $this->doctype;
	}
	
	/**
	 * Set the current Document's doctype.
	 * @param string $doctype
	 * @return boolean
	 */
	public function setDoctype($doctype){
		if(is_string($doctype)){
			$this->doctype = $doctype;
			return true;
		}else return false;
	}
	
	/**
	 * Get the currently used Document encoding.
	 * @return string
	 */
	public function getEncoding(){
		return $this->encoding;
	}
	
	/**
	 * Set the current Document's encoding/charset
	 * @param string $charset Encoding or charset to use.
	 * @return boolean
	 */
	public function setEncoding($charset){
		if(is_string($charset)){
			// Set the internal set
			$this->encoding = $charset;
			
			// Remove the old charset declaration
			$this->headers->filter(function($type, $attr){
				if($type == Headers::META && isset($attr['charset']))
					return false;
			});
			
			// Add the new charset (Use the http-equiv way to define the charset, to maximize compatability)
			//$this->headers->add(Headers::META, array('charset' => $charset));
			$this->headers->add(Headers::META, array('http-equiv' => 'Content-Type', 'content' => 'text/html; charset='.$charset));
			
			return true;
		}else return false;
	}
	
	/**
	 * Whether or not we are currently exporting using the xhtml notation.
	 * @return boolean
	 */
	public function getXMLNamespace(){
		return $this->xhtml;
	}
	
	/**
	 * Whether or not we should export using the xhtml notation.
	 * @param boolean $xmlns
	 * @return boolean
	 */
	public function setXMLNamespace($xmlns){
		if(is_string($xmlns) || $xmlns == false){
			$this->xmlns = $xmlns;
			return true;
		}else return false;
	}
	
	/**
	 * Whether or not we are currently exporting using the xhtml notation.
	 * @return boolean
	 */
	public function getXHTML(){
		return $this->xhtml;
	}
	
	/**
	 * Whether or not we should export using the xhtml notation.
	 * @param boolean $xhtml
	 * @return boolean
	 */
	public function setXHTML($xhtml){
		if(is_bool($xhtml)){
			$this->xhtml = $xhtml;
			return true;
		}else return false;
	}

	/**
	 *
	 * @param bool $required
	 * @return bool|object
	 * @throws \RuntimeException
	 */
	public function getResourceManager($required=true) {
		if(is_object($this->resources)){
			return $this->resources;
		}else if($required == true)
			throw new \RuntimeException('Tried to retrieve the (Required) Resource Manager, but no resource manager was set on this document.');
		else return false;
	}

	/**
	 * Set the Resource Manager for this Document.
	 * @param ResourceManager $resources
	 * @return bool
	 */
	public function setResourceManager(ResourceManager $resources) {
		if(is_object($resources)){
			$this->resources = $resources;
			return true;
		}else return false;
	}

	/**
	 * Magic method for making it easier to use the Document class variables.
	 * @param string $name Name of currently accessed variable.
	 * @throws \RuntimeException
	 * @return mixed
	 */
	public function __get($name){
		$name = strtolower($name);
		if($name == 'layout')
			return $this->layout;
		else if($name == 'headers' || $name == 'head' || $name == 'headermanager')
			return $this->headers;
		else if($name == 'resources' || $name == 'resourcemanager')
			return $this->resources;
		else if($name == 'doctype') return $this->doctype;
		else if($name == 'encoding' || $name == 'charset') return $this->encoding;
		else if($name == 'xhtml') return $this->xhtml;
		else throw new \RuntimeException('Tried to reference an invalid/non-existant variable name.');
	}

	/**
	 * Magic Method that supplies shorthand methods for certain Layout and header functionality.
	 * @param string $method Method to invoke.
	 * @param array $arguments Arguments for the supplied method name.
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 * @return mixed
	 */
	public function __call($method, $arguments){
		if($method == 'place' || $method == 'elem') {
			if(count($arguments) == 1)
				$this->layout->place($arguments[0], $this->layout->positions->default);
			else if(count($arguments) == 2)
				$this->layout->place($arguments[0], $arguments[1]);
			else throw new \InvalidArgumentException('The arguments given to place|elem are invalid. Check the Layout::place documentation for more info.');
		}else throw new \RuntimeException('Invalid method name given to __call: Could not resolve call.');
	}

	/**
	 * Magic Method that makes static calls on class methods possible.
	 * @param string $method Method to invoke.
	 * @param array $arguments Arguments for the supplied method name.
	 * @throws \RuntimeException
	 * @return mixed
	 */
	public static function __callStatic($method, $arguments) {
		$self = self::getInstance();
		if(count($arguments) == 1 && ($method == 'encodeText' || $method == 'encode')){
			$self->encodeText($arguments[0]);
		}else throw new \RuntimeException('Invalid method name given to __call: Could not resolve call.');
	}

	/**
	 * Check if the document (layout) has any content.
	 * @return bool
	 */
	public function hasContent(){
		$iterator = $this->layout->getIterator();
		$iterator->rewind();
		return $iterator->valid(); // If this returns true it has at least one child.
	}

	/**
	 * Get the saved document
	 * @return string The HTML Document
	 */
	public function save(){
		// The document has now been saved
		self::$_saved = true;
		
		// Check if the layout has css
		if($this->layout instanceof Style) // @TODO: For now we just add the css as a style element. Some caching and or saving to disk for performance might be interesting.
			$this->headers->add(Headers::STYLE, array(), PHP_EOL.$this->layout->saveStyle()); // @TODO @NOTE NO2: Maybe we could just scrape all the CSS from the header class and compile that, embedded CSS isn't something we should be encouraging anyways.
		
		// Get the document elements(children)
		$children = $this->layout->save($this);
		if(empty($children)) $children = '<div style="margin:20px auto;border:1px solid grey;width:500px;text-align:center;border-radius: 4px;background:#f0f0f0;font-family: sans-serif;"><h1 style="text-shadow:#555 0px 0px 3px;border-bottom:1px solid grey;padding:0px 0px 15px 0px">Quark: Internal 404</h1><p title="Sorry the layout bound to this Document did not return any HTML.">The framework could find absolutely nothing to display.</p><p style="font-size:9px"><strong>Technical info</strong> The system did initialize the UserInterface-&gt;Document object and added a Layout, but left the Layout in it\'s default (empty) state.<br/>Thus: the application created it and did nothing with it.</p></div>';
		
		// Check if it's x(ht)ml
		if(is_string($this->xmlns) && !empty($this->xmlns))
			$xmlns = ' xmlns="'.$this->xmlns.'"';
		else $xmlns = '';
		
		// Return the document
		$doc = <<<DOCUMENT
{$this->doctype}
<html{$xmlns}>
<head>{$this->headers->save($this->xhtml)}
</head>
<body>
{$children}</body>
</html>
DOCUMENT;
		return $doc;
	}
	
	/**
	 * Print the HTML-Document
	 * @see Document::save()
	 */
	public function display(){
		print($this->save());
	}
	
	/**
	 * Get the HTML string representation for this document.
	 * @return string
	 */
	public function __toString(){
		return $this->save();
	}
	
	// Utility functions
	/**
	 * Prepare text to be included in the current document.
	 * @param string $text The text to encode as per the document's current settings.
	 * @param bool $double_encode See the {@see \htmlspecialchars} documentation on the double encode parameter.
	 * @return string
	 */
	public function encodeText($text, $double_encode=true){
		$htmlType = ENT_HTML401;
		if($this->type == self::TYPE_HTML5)
			$htmlType = ENT_HTML5;
		else if($this->type == self::TYPE_XHTML_STRICT || $this->type == self::TYPE_XHTML_TRANSITIONAL || $this->type == self::TYPE_XHTML_FRAME)
			$htmlType = ENT_XHTML;
		
		return htmlentities($text, ENT_QUOTES | ENT_SUBSTITUTE | $htmlType, $this->encoding, $double_encode);
	}

	/**
	 * Prepare text to be included as an name > value pair as an element's attribute in the current document.
	 * @param string $name The attribute name.
	 * @param string $value The attribute value.
	 * @param bool $double_encode See the {@see \htmlspecialchars} documentation on the double encode parameter.
	 * @return string The attribute ready to be applied to an element, in the form of '{$name}="{$value}"'.
	 */
	public function encodeAttribute($name, $value, $double_encode=true){
		$htmlType = ENT_HTML401;
		if($this->type == self::TYPE_HTML5)
			$htmlType = ENT_HTML5;
		else if($this->type == self::TYPE_XHTML_STRICT || $this->type == self::TYPE_XHTML_TRANSITIONAL || $this->type == self::TYPE_XHTML_FRAME)
			$htmlType = ENT_XHTML;

		if($value === null)
			return false;

		if(function_exists('\Quark\Filter\filter_string'))
			return \Quark\Filter\filter_string($name, ['chars' => CONTAINS_ALPHANUMERIC.'-']).'="'.htmlspecialchars($value, ENT_COMPAT | ENT_SUBSTITUTE | $htmlType, $this->encoding, $double_encode).'"';
		else
			return trim($name).'="'.htmlspecialchars($value, ENT_COMPAT | ENT_SUBSTITUTE | $htmlType, $this->encoding, $double_encode).'"';
	}
	
	/**
	 * Auto Display at page Shutdown control.
	 * 
	 * If display is given, sets whether or not to display at shutdown (Overrides even if it was already saved).
	 * If display was not set or is null it will return whether or not it is going to display
	 * @param bool $display
	 * @return bool
	 */
	public static function autoDisplay($display=null){
		if(is_bool($display))
			self::$_saved = !$display;
		else return !self::$_saved;
	}
	
	/**
	 * Get the default property value for the given type.
	 * @param string $type A TYPE_* constant.
	 * @param string $property Property to retrieve, eg. xmlns, xhtml, doctype, etc.
	 * @return null|mixed Null on fault, the value on success.
	 */
	public static function getDefaultTypeProperty($type, $property){
		if(is_string($type) && isset(self::$documentProperties[$type]) && is_string($property) && self::$documentProperties[$type][$property]){
			return self::$documentProperties[$type][$property];
		}else return null;
	}
	
	// Singleton functions
	/**
	 * Get the currently active Document instance.
	 * @return \Quark\Document\Document Current document instance.
	 * @throws \RuntimeException When no instance was created.
	 */
	public static function getInstance() {
		if(!self::hasInstance())
			throw new \RuntimeException('Tried to get a reference to the Document object before it was initialized.');
		else return self::$_instance;
	}

	/**
	 * Check whether or not an instance was already created.
	 * @return boolean
	 */
	public static function hasInstance() {
		return (self::$_instance != null);
	}

    /**
     * Create the default document instance
     * @param \Quark\Document\Layout\Layout $layout Layout manager to use.
     * @param string $type A TYPE_* constant, defines what type of document this will be. For example; HTML4.01 Strict, HTML5 doc etc. Affects the rules used to generate the document.
     * @param Router $router Optional router object to use for the resource manager instance.
     * @param Headers $headers Optional Headers instance to bind to the document.
     * @return bool|Document
     */
	public static function createInstance(Layout $layout, $type=self::TYPE_HTML, Router $router=null, Headers $headers=null){
		if(is_string($type) && isset(self::$documentProperties[$type])){
			if($headers == null)
                $headers = new Headers;
			self::$_instance = new Document($layout, $type, self::$documentProperties[$type]['doctype'], self::$documentProperties[$type]['charset'], self::$documentProperties[$type]['xmlns'], self::$documentProperties[$type]['xhtml'], $headers);
			self::$_instance->setResourceManager(new ResourceManager($headers, $router));
			return self::$_instance;
		}else return false;
	}

	/**
	 * Create the document instance with customized options.
	 * @param \Quark\Document\Layout\Layout $layout Layout manager to use.
	 * @param string $type The TYPE_* constant that comes closest to your custom document, this is for comparability.
	 * @param string $doctype The (Custom) HTML Doctype to use for this document.
	 * @param string $encoding Document encoding to use, see class constants to change or define your own. (Defaults to UTF-8)
	 * @param string $xmlns The xmlns string to use
	 * @param boolean $xhtml Whether or not to use the html style single tag closure style (<link />) or the HTML style (<link>).
	 * @param Headers $headers The header manager to use for this document.
	 * @param ResourceManager|null $resources Resource Manager object to use for this document, may be set to null.
	 * @return bool|\Quark\Document\Document
	 */
	public static function createCustomInstance(Layout $layout, $type, $doctype, $encoding, $xmlns, $xhtml, Headers $headers, ResourceManager $resources=null){
		if((is_string($type) && isset(self::$documentProperties[$type])) && is_string($doctype) && is_string($encoding) && (is_string($xmlns) || $xmlns == false) && is_string($xhtml)){
			self::$_instance = new Document($layout, $type, $doctype, $encoding, $xmlns, $xhtml, $headers);
			if($resources !== null)
				self::$_instance->setResourceManager($resources);
			return self::$_instance;
		}else return false;
	}

	/**
	 * Write the document to the given response
	 *
	 * The added bonus of creating a response object this way is that many http-headers are automatically set correctly.
	 * @param IResponse $response The response to set this document as te body as. Whn the $response object is empty, it will create an empty {@link \Quark\Protocols\HTTPS\Response} object with a 200 OK status.
	 * @return void
	 */
	public function toResponse(IResponse &$response){
		// Create the response
		if($response == null)
			$response = new Response(200, 'OK');

		// Set the encoding
		//$response->setHeader('Content-Type', ($this->getXHTML() ? 'application/xml+xhtml' : 'text/html').'; charset='.$this->getEncoding()); // @todo HTML5 doesn't require xhtml mime-type, maybe drop alltogether?
		$response->setHeader('Content-Type', 'text/html; charset='.$this->getEncoding());

		// Set the saved body
		$response->setBody($this->save());
	}

	// These methods allow for custom document extending classes registering themselves as the active Document classes.
	// For debugging purposes, not for production use, if you do, use at your own risk and inform the user(If it is an extension, component, etc via description)
	/**
	 * Reset's the document class to it's beginning state
	 * @ignore
	 */
	public function _reset(){
		self::$_instance = null;
	}
	
	/**
	 * Set the active Document instance
	 * @ignore
	 */
	public function _setInstance(Document $doc){
		self::$_instance = $doc;
	}
}