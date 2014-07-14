<?php
/**
 * UI Grid Implementation for positioning elements on a grid
 * 
 * @package		Quark-Framework
 * @version		$Id: gridlayout.php 69 2013-01-24 15:14:45Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		October 19, 2012
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
namespace Quark\Document\Layout;
use \Quark\Document\Document,
	\Quark\Document\IElement,
	\Quark\Document\Style;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Grid layout using columns to position the elements
 * 
 * This layout also sports a completely auto-generated grid system.
 * Based on the ideas of the brilliant mind Joni Korpi (http://framelessgrid.com/)
 */
class GridLayout extends Layout implements Style{
	// Predefined Breakpoints
	/**
	 * Phone View Sized Breakpoint Reference (Default size: 720, single column)
	 */
	const BP_PHONE = 1;
	
	/**
	 * Tablet View Sized Breakpoint Reference (Default size: 985)
	 */
	const BP_TABLET = 2;
	
	/**
	 * Normal Desktop View Sized Breakpoint Reference (Default size: 1235)
	 */
	const BP_DESKTOP = 4;
	
	/**
	 * Full HD Desktop View Sized Breakpoint Reference (Default size: 1875)
	 */
	const BP_DESKTOP_HD = 8;
	

	// Other constants (Not meant to be changed, but if you really wanna.. go.)
	/**
	 * Minimum hardcoded grid width. (In pixels)
	 */
	const MINIMUM_VIEWPORT_WIDTH = 320;
	
	// Grid Size Definitions
	/**
	 * Number of columns in the document.
	 * (Must be a multiplicity/power of 2,
	 * there will be two columns added at the outside for spacing)
	 * @var integer
	 */
	protected $columns = 16;
	
	/**
	 * Gutter width. (In 'px' for non-fluid '%' for fluid)
	 * @var float
	 */
	protected $gutter = 10;
	
	/**
	 * Whether or not the grid should behave as a fluid grid, or use specified breakpoints.
	 * @var boolean
	 */
	protected $fluid = false;
	
	/**
	 * Breakpoints.
	 *
	 * Breakpoints are points in the reflow of the documents that columns in the
	 * document can be reordered (Using @media Queries). Maximum of 4 flow types
	 * definable, minimal is one. Best to keep at the default settings.
	 * @var array
	 */
	protected $breakpoints = array(
		self::BP_PHONE		=> 720,
		self::BP_TABLET		=> 985,
		self::BP_DESKTOP	=> 1235,
		self::BP_DESKTOP_HD	=> 1875
	);
	
	/**
	 * Number of columns filled on the current row
	 * @var integer
	 */
	protected $rowspan = 0;

	/**
	 * All the elements in the grid grouped by row.
	 * @var IElement[][]
	 */
	protected $elements = array(0 => array());

	/**
	 *
	 * @param int $columns Multiplicity of 2
	 * @param float|int $gutter Size of the empty space between columns
	 * @param boolean $fluid Whether or not to size the columns
	 * @param array $breakpoints
	 */
	public function __construct($columns=16, $gutter=10, $fluid=false, $breakpoints=null){
		// Set the gutter and number of columns
		if(is_integer($columns)) $this->columns = $columns;
		if(is_float($gutter)) $this->gutter = $gutter;
		if(is_bool($fluid)) $this->fluid = $fluid;
		
		// Check and set the breakpoints
		if(is_array($breakpoints) && !empty($breakpoints)){
			$mergedBreaks = array();
			foreach($this->breakpoints as $bp => $val){
				if(isset($breakpoints[$bp]))
					$mergedBreaks[$bp] = $breakpoints[$bp];
			}
			
			if(empty($mergedBreaks))
				$this->breakpoints = $mergedBreaks;
			else
				\Quark\Error::raiseWarning("Breakpoint array given to the GridLayout class was incorrectly structured, or used non-existent breakpoints. Use the BREAKPOINT_* class constants as indexes to correct this error.");
		}
		
		// Populate the Positions object
		$gp = array();
		for($i=0; $i<$this->columns; $i++){ // Loop over columns
			$gp['SPAN'.($i+1)] = ['Element spans '.($i+1).' columns', 'Content column in the grid.'];
		}
		$this->positions = new Positions($gp, array('MAIN_CONTENT' => 'SPAN'.$this->columns));
	}
	
	/**
	 * Place a element on the grid.
	 * @param \Quark\Document\IElement $element Element to place.
	 * @param string $position Position of the element on the grid.
	 * @return boolean
	 */
	public function place(IElement $element, $position){
		if(!$this->positions->exists($position)) return false;
		else $position = $this->positions->resolve($position);
		
		if($this->rowspan >= $this->columns){
			$this->elements[] = array();
			$this->rowspan = 0;
		}
		
		$this->rowspan += intval(substr($position, 4));
		array_push($this->elements[(count($this->elements)-1)], array($position, $element));
		return true;
	}
	
	/**
	 * Saves the css needed for this document
	 * @return string
	 */
	public function saveStyle() {
		// Add CSS to the document
		$css = "body { margin: 0; }\n";
		$css .= ".grid { margin: 0 auto; clear: both; } \n";
		$css .= ".row{ margin-bottom: 10px; clear:left; } .row section:first-child { margin-left: 0; } .row:after{ visibility:hidden; display:block; font-size:0; content:\" \"; clear:both; height:0; } .row{ zoom:1; }\n";
		
		for($i=0; $i<$this->columns; $i++)
			$css .= '.span'.($i+1).(($i==($this->columns-1))?'':', ');
		$css .= ' { float: left; margin-left: '.$this->gutter.'px; }'."\n";
		
		$prev = 0;
		$cnt = count($this->breakpoints)-1;
		$cur = 0;
		foreach($this->breakpoints as $bp => $px){
			if($prev == 0){ // First
				$css .= '@media (max-width: '.$px.'px) {'."\n";
				$css .= "\t".'section.grid { width: 100%; } .row { clear: both; margin: 0 10px; } .row section { float: none; margin: 8px 0; } '."\n";
			}else if($cur == $cnt){ // Last
				$css .= '@media (min-width: '.$prev.'px) {'."\n";
				$css .= "\t".'section.grid { width: '.$prev.'px; }'."\n";
			}else{ // Rest
				$css .= '@media (min-width: '.$prev.'px) and (max-width: '.$px.'px) {'."\n";
				$css .= "\t".'section.grid { width: '.$prev.'px; }'."\n";
			}
			
			$col = ceil($prev/$this->columns); // The size of a single column
			for($c=1; $c<=$this->columns; $c++){ // Loop over columns
				$css .= "\t".'.span'.$c.'{ ';
				if($this->fluid)
					$css .= '';
				else{
					if($prev == 0)
						$css .= 'width: 100%;';
					else
						$css .= 'width: '.(ceil($col * $c) - $this->gutter).'px;';
				}
				$css .= " }\n";
			}
			$css .= "}\n";
			
			$prev = $px;
			$cur++;
		}
		return $css;
	}

	/**
	 * Retrieve the HTML representation of the element
	 * @param Document $context The context within which the Element gets saved. (Contains data like encoding, XHTML or not etc.)
	 * @return String HTML Representation
	 */
	public function save(Document $context) {
		// Save the elements
		$saved = "\t<section class=\"grid\">\n";
		foreach($this->elements as $row){
			$saved .= "\t\t<section class=\"row\">\n";
			foreach($row as $elem){
				$saved .= "\t\t\t".'<section class="'.strtolower($elem[0]).'">'."\n";
				$saved .= $elem[1]->save($context);
				$saved .= "\n\t\t\t</section>\n";
			}
			$saved .= "\t\t</section>\n";
		}
		$saved .= "\t</section>\n";
		
		return $saved;
	}
}