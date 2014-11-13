<?php
/**
 * @package		Quark-Framework
 * @author		Joe Gregario, Andrew "Venom" K., Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		November 8, 2014
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define namespace
namespace Quark\Protocols\HTTP;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Mime parser class.
 *
 * This class provides basic functions for handling mime-types. It can handle matching mime-types against a list of
 * media-ranges. See section 14.1 of the HTTP specification [RFC 2616] for a complete explanation.
 *
 * It's just a port to php from original Python code (http://code.google.com/p/mimeparse/) ported from version 0.1.2
 *
 * Comments are mostly excerpted from the original.
 *
 * This class has been further modified by Jeffrey vH including but not limited to speed improvements and removing the
 * use of deprecated language functions as well as removing (in my eyes) unnecessary API bloat.
 *
 * @author Joe Gregario, Andrew "Venom" K., Jeffrey van Harn
 * @license http://opensource.org/licenses/MIT MIT License
 */
class MimeParser {
	/**
	 * Carves up a mime-type and returns an Array of the [type, subtype, params] where "params" is a Hash of all the
	 * parameters for the media range.
	 *
	 * For example, the media range "application/xhtml;q=0.5" would get parsed into:
	 *
	 * 	array("application", "xhtml", array( "q" => "0.5" ))
	 *
	 * @param string $mimeType The mime-type to parse.
	 * @param bool $autoFillRange Whether or not to automatically fill the media range in the params dictionary, if it is not or incorrectly set. (The q=.5 key)
	 * @throws \Exception When the given mime type string was malformed.
	 * @return array ($type, $subtype, $params)
	 */
	public static function parse($mimeType, $autoFillRange=true){
		$parts = explode(";", $mimeType);

		$params = array();
		foreach($parts as $param){
			if(strpos($param, '=') !== false){
				list($k, $v) = explode('=', trim($param), 3);
				$params[$k] = $v;
			}
		}

		if($autoFillRange && (empty($params['q']) || ($qval = floatval($params['q'])) > 1 || $qval <= 0))
			$params['q'] = '1';

		$fullType = trim($parts[0]);
		// Java URLConnection class sends an Accept header that includes a single "*"; Turn it into a legal wildcard.
		if($fullType == '*')
			$fullType = '*/*';

		list($type, $subtype) = explode('/', $fullType);
		if(!$subtype)
			throw new \Exception("Tried to parse an malformed mime type.");

		return array(trim($type), trim($subtype), $params);
	}

	/**
	 * Find the best match for a given $mimeType against a list of media ranges that have both already been
	 * parsed by {@link MimeParser::parse()}
	 *
	 * Returns the fitness and the "q" quality parameter of the best match, or false if no match was found.
	 * Just as for {@link MimeParser::quality()}, $parsedRanges must be an Enumerable of parsed media ranges.
	 *
	 * @param array $mimeType Parsed mime-type /w media range.
	 * @param array $parsedRanges List of parsed mime-types /w media ranges.
	 * @return array|false array(/best fitness/, /best fit 'q'/)
	 */
	public static function parseQualityAndFitness($mimeType, $parsedRanges) {
		$bestFitness = -1;
		$bestFitQ = 0;
		list($targetType, $targetSubtype, $targetParams) = $mimeType;

		foreach($parsedRanges as $item){
			list($type, $subtype, $params) = $item;

			if(
				($type == $targetType or $type == "*" or $targetType == "*") &&
				($subtype == $targetSubtype or $subtype == "*" or $targetSubtype == "*")
			){
				$param_matches = 0;
				foreach($targetParams as $k => $v){
					if($k != 'q' && isset($params[$k]) && $v == $params[$k])
						$param_matches++;
				}

				$fitness = ($type == $targetType) ? 100 : 0;
				$fitness += ($subtype == $targetSubtype) ? 10 : 0;
				$fitness += $param_matches;

				if($fitness > $bestFitness){
					$bestFitness = $fitness;
					$bestFitQ = $params['q'];
				}
			}
		}

		if($bestFitness == -1 && $bestFitQ == 0) return false;
		else return array($bestFitness, (float) $bestFitQ);
	}

	/**
	 * Returns the quality "q" of a mime-type when compared against
	 * the media-ranges in ranges. For example:
	 *
	 * 	$q = MimeParser::quality("text/html", "text/*;q=0.3, text/html;q=0.7, text/html;level=1, text/html;level=2;q=0.4, *\/*;q=0.5");
	 * 	var_dump($q); // float(0.7)
	 *
	 * @param string $mimeType
	 * @param array $ranges
	 * @return float
	 */
	public static function quality($mimeType, $ranges) {
		$result = self::parseQualityAndFitness(
			self::parse($mimeType, true),
			self::_parseAcceptHeader($ranges, true)
		);
		return $result[1];
	}

	/**
	 * Find best matching mime-types for an HTTP/1.x Accept header/ media range.
	 *
	 * Takes a list of supported mime-types and finds the best match for all the media-ranges listed in header.
	 * The value of header must be a string that conforms to the format of the HTTP Accept: * header. The value of
	 * supported is an Enumerable of mime-types.
	 *
	 * 	$mimeType = MimeParser::best_match(array("application/xbel+xml", "text/xml"), "text/*;q=0.5,*\/*; q=0.1");
	 * 	var_dump($mimeType); // string("text/xml")
	 *
	 * @param array $supported
	 * @param string $header
	 * @return string|null The best mime-type or NULL.
	 */
	public static function match($supported, $header) {
		$accepted = self::_parseAcceptHeader($header, true);

		$weightedMatches = array();
		foreach($supported as $mimeType){
			$weightedMatches[] = array(
				self::parseQualityAndFitness(
					self::parse($mimeType, true),
					$accepted
				),
				$mimeType
			);
		}

		array_multisort($weightedMatches);

		$a = $weightedMatches[ count($weightedMatches) - 1 ];
		return (empty($a[0][1]) ? null : $a[1]);
	}

	/**
	 * Check if the mimeType you have is accepted by the given HTTP/1.x Accept header.
	 * @param string $mimeType Mime type to check (for your local file).
	 * @param string $header The Accept header to check against.
	 * @return bool
	 */
	public static function acceptable($mimeType, $header){
		$mimeType = self::parse($mimeType, false);
		$accepted = self::_parseAcceptHeader($header, false);

		foreach($accepted as $accept){
			if(
				($accept[0] == $mimeType[0] || $accept[0] == '*') &&
				($accept[1] == $mimeType[1] || $accept[1] == '*')
			)
				return true;
		}
		return false;
	}

	/**
	 * Splits the accept header's comma's and parses each meta-type/media-range independently.
	 * @param string $header
	 * @param bool $autoFillRange Value to pass to MimeParser::parse.
	 * @return array
	 */
	private static function _parseAcceptHeader($header, $autoFillRange=true){
		$parsedHeader = explode(',', $header);
		$accepted = array();
		foreach($parsedHeader as $value){
			if(empty($value)) continue;
			array_push($accepted, self::parse($value, $autoFillRange));
		}
		unset($parsedHeader);
		return $accepted;
	}
}