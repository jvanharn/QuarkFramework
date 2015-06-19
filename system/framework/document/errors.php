<?php
/**
 * UI Elements for displaying Errors
 * 
 * @package		Quark-Framework
 * @version		$Id: errors.php 52 2012-11-04 13:37:23Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 2, 2011
 * @copyright	Copyright (C) 2011 Jeffrey van Harn. All rights reserved.
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

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Make sure dependencies are loaded
\Quark\import('Document.Element');

/**
 * Simple Boxed UI Field
 * 
 * A simple marked up element
 * @subpackage Interface
 */
class ErrorMessage implements IIndependentElement {
	use baseElement, baseIndependentElement;
	
	protected $defaults = array(
		'title' => null,
		'message' => '',
	);

    /**
     * Placeholder to satisfy the error checkers.
     * @throws \RuntimeException
     * @param int $depth The current indentation depth, not required.
     * @return string|void
     * @ignore
     */
    public function independentSave($depth=0){
        // @todo Stub
        throw new \RuntimeException('Stub.');
    }
}

/**
 * Simple Boxed UI Box
 * 
 * Can contain multiple SimpleUI Elements.
 * @subpackage Interface
 */
class ErrorBox implements IIndependentElement{
	use baseElement, baseIndependentElement;

	const BOX_CLASS = 'ErrorBox';

	/**
	 * @var ErrorFrame[] Frames in this box.
	 */
	private $frames = array();

	/**
	 * CSS-Styles for this element
	 */
	private $style = array(
		
	);
	
	/**
	 * Options that define the looks of the box
	 */
	protected $defaults = array(
		'title' => null,
		'text_color' => '#444444',
		'border_color' => '#f2f2f2',
		'background_color' => '#eee',
		'icon' => null,
		'icon_pos' => '7px 7px',
		'style' => '', // Your own css to add to the element
		'class' => ''
	);

	/**
	 * Add an error frame to this box.
	 * @param ErrorFrame $frame
	 */
	public function addFrame(ErrorFrame $frame){
		$this->frames[] = $frame;
	}
	
	/**
	 * Generates the HTML
     * @param int $depth The current indentation depth, not required.
	 * @return String HTML Representation
	 */
	public function independentSave($depth=0) {
		// Set the style of the box
		$style = 'color: '.$this->options['text_color'].'; font: 11px Verdana, Tahoma, Geneva, sans-serif;'.
		'margin: 4px; padding: 8px;'.
		'border: 2px solid '.$this->options['border_color'].';border-radius:3px;'.
		(empty($this->options['icon'])?'':'background: url('.$this->options['icon'].') no-repeat;'.
		'background-position:'.$this->options['icon_pos'].';').' background-color:'.$this->options['background_color'].';';
		
		// Set the HTML
		$html = '<div style="'.$style.$this->options['style'].'" class="'.$this->options['class'].' '.self::BOX_CLASS.'">'.PHP_EOL;
		if(!empty($this->options['title'])){
			$titleStyle = 'padding:0;margin:0;height:20px;font-weight:bold;font-size:110%;';
			if(!empty($this->options['icon']))
				$titleStyle .= 'padding-left:'.(is_numeric($sub = substr($this->options['icon_pos'], 0, 2)) ? ($sub*3).'em' : '21px').';';
			$html .= '<div style="'.$titleStyle.'">'.$this->options['title'].'</div>'.PHP_EOL;
		}
		
		// Add the elements
		foreach($this->frames as $frame)
			$html .= $frame->independentSave();
		
		// End the stuff
		$html .= '</div>'."\n".'<!-- -------------------------------------------------------- -->'."\n\n";
		return $html;
	}
}

/**
 * Simple Boxed UI Field
 * 
 * A element for use in a box. Can be just some text
 * @subpackage Interface
 */
class ErrorFrame implements IIndependentElement {
	use baseElement, baseIndependentElement;
	
	const Category		= 0;
	const Text			= 1;
	const NoWrap		= 2;
	
	/**
	 * Options that define the looks of the box
	 */
	protected $defaults = array(
		'title' => null,
		'type' => self::Text,
		'hidable' => false,
		'text' => '',
		'style' => ''
	);
	
	private static $boxid = 0;

	/**
	 * Retrieve the HTML representation of the element
	 * @throws \RuntimeException
     * @param int $depth The current indentation depth, not required.
	 * @return String HTML Representation
	 */
	public function independentSave($depth=0) {
		if($this->options['type'] == self::Text)
			return '<div style="padding:0;margin:0;margin-left:3px;'.$this->options['style'].'">'.$this->options['text'].'</div>';
		elseif($this->options['type'] == self::NoWrap)
			return $this->options['text'];
		elseif($this->options['type'] == self::Category){
			// Generate some random id's
			if($this->options['hidable'] == true){
				$hid = 'err_hid_'.mt_rand(0,999).'_'.self::$boxid++;
				$id = 'err_id_'.mt_rand(0,999).'_'.self::$boxid++;
			}
			
			// Set the style
			$hStyle = 'padding:3px; font-size:13px; font-weight: bold; color:black; border:1px solid black; border-bottom:0px;margin-bottom:0px; background-color:#f5f5f5;'.$this->options['style'];
			$rTop = 'border-bottom:0px solid black;border-radius:3px 3px 0px 0px; -webkit-border-radius:4px 4px 0px 0px; cursor:pointer;';
			$rAll = 'border-bottom:2px solid black;border-radius:3px; -webkit-border-radius:4px; cursor:pointer;';
			
			// Set the html
			$html = '<div style="margin-top:8px;"><div'.(isset($hid)?' id="'.$hid.'"':'').' style="'.$hStyle.$rTop.'">'.$this->options['title'].'<span style="float: right;padding: 0px 4px;">&#9660;</span></div>'.PHP_EOL. // Header
                        '<div'.(isset($id)?' id="'.$id.'"':'').' style="margin-top:0px;border: 1px solid black; color:black; padding:5px; padding-left:8px; background-color:#f5f5f5;">'.$this->options['text'].'</div></div>'.PHP_EOL;
			
			// Add the dynamic part
			if($this->options['hidable'] == true){
				$html .= '<script>'.
				// Functions
				'var $css = function(id,css){if($pt(id).style.setAttribute)$pt(id).style.setAttribute("cssText", css); else $pt(id).setAttribute("style", css);};'.PHP_EOL.
				'var $pt = function(i){if(typeof(i) == \'string\'||typeof(i) == \'number\')return document.getElementById(i);else return i;};'.PHP_EOL.
				'var s = function(id,hid){if($pt(id).style.display == \'none\'){$pt(id).style.display = \'block\';$css(hid, "'.$hStyle.$rTop.'");'.PHP_EOL.'}else{$pt(id).style.display = "none";$css(hid, "'.$hStyle.$rAll.'");}};'.PHP_EOL.
				// Logic
				'$pt(\''.$id.'\').style.display=\'none\';$css(\''.$hid.'\', "'.$hStyle.$rAll.'");$pt(\''.$hid.'\').setAttribute("title", "Click to unhide/hide the box.");$pt(\''.$hid.'\').onclick = function(){s(\''.$id.'\',\''.$hid.'\');};</script>'.PHP_EOL;
			}
			return $html;
		}else{
			throw new \RuntimeException('Invalid frame-type specified.');
		}
	}
}