<?php
/**
 * Text and Hypertext Utility Functionality
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		July 10, 2014
 * @copyright	Copyright (C) 2012-2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Document\Utils;
use Quark\Document\IElement,
	Quark\Document\Document;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Utility class
 */
abstract class _ {
	/**
	 * Encode text.
	 *
	 * Tries to automatically find the main Document instance when the context is null and use the encode method on that.
	 * (When programming for main framework methods, DO NOT leave the $context empty. Meant for simple applications not using threading and for use in fastcgi etc.)
	 * @param string $text Encode the given text appropriately according to the main document.
	 * @param Document|null $context The context document to use for the encoding, if not set uses the default instance.
	 * @param boolean $doubleEncode Whether or not to re-encode html-entities already in the text.
	 * @return string
	 */
	public static function encode($text, Document $context=null, $doubleEncode=true){
		if(is_null($context))
			$context = Document::getInstance();
		return $context->encodeText($text, $doubleEncode);
	}

	/**
	 * Properly encodes the text and tries to find a translation provider to translate the given text.
	 * @param string $text The text to translate or the token for the translation. (Depends on the translation method used)
	 * @param Document|null $context
	 * @return string Localised and encoded text.
	 */
	public static function translate($text, Document $context=null){
		// @todo i18n Localisation and Nationalisation support
		return self::encode($text, $context, false);
	}

	/**
	 * Convert number formats from the given string to an double or vise-versa.
	 *
	 * @see \NumberFormatter Uses the Intl extensions NumberFormatter to achieve it's goal.
	 * @param mixed $value The value to convert.
	 * @param int $format The format to output in.
	 * @param Document $context The document to use as context. (Used for the language and other internationalisation settings).
	 * @return mixed
	 */
	public static function convert($value, $format=\NumberFormatter::DECIMAL, Document $context=null){
		// @todo i18n Use the document's internationalisation settings.
		return $value;
	}

	/**
	 * Prepends the given line of HTML or plaintext with the given amount of tabs, and prepends it with a newline.
	 * @param int $depth Number of tabs.
	 * @param string $html html/text for the line.
	 * @return string
	 */
	public static function line($depth, $html){ return str_repeat("\t", $depth).$html."\n"; }

	/**
	 * Simple one-liner for $state switching when null as a result is not an option and an empty string is required.
	 * @param boolean $state Whether or not to return $text.
	 * @param string $text The text to return.
	 * @return string Returns $text or empty string.
	 */
	public static function enabled($state, $text){ return $state ? $text : ''; }

	#region Atomic Method Aliases
	/**
	 * {@inheritdoc _::translate()}
	 * @see _::translate()
	 */
	public static function t($text, Document $context=null){
		return self::translate($text, $context);
	}

	/**
	 * {@inheritdoc _::convert()}
	 * @see _::convert()
	 */
	public static function c($value, $format=\NumberFormatter::DECIMAL, Document $context=null){
		return self::convert($value, $format, $context);
	}

	/**
	 * {@inheritdoc _::line()}
	 * @see _::line()
	 */
	public static function nl($depth, $html){
		return self::line($depth, $html);
	}
	#endregion
}