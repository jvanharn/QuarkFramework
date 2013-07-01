<?php
/**
 * Advanced Web Application Layout.
 * 
 * @package		Quark-Framework
 * @version		$Id$
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		February 23, 2013
 * @copyright	Copyright (C) 2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\Libraries\ApplicationLayout;
use Quark\Document\Layout\Layout;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Web Application Layout
 * 
 * This layout is meant to be used for applications with fixed viewports.
 * It allows you to define a fixed grid of a certain number of columns and rows that you can define for various viewport sizes. (Breakpoints)
 * 
 * The application layout is basically a screen filling fluent grid. Each cell is filled with a element, or more purposly built "Panels".
 * A panel can be a basic panel or a group of panels which can be mutated etc, etc. The basic "cell" (A basic row and column square) is not seperated by any gutter. E.g. there is no spacing between cells, the panels themselves add spacing if they see it fit.
 * 
 * For most interface altering methods you find in this library, there also is a clientside version that you could use. For that part however you need the asset library, which is further explained there.
 * 
 * As we have to stay compatible with the basic layout class you /can/ place generic elements in this layout without any problem, /However/ it is _recommended_ to use the panels distributed with this library for maximal compatability, and features. As they provide cooler controls and get you started very easily.
 */
class ApplicationLayout extends Layout implements \Quark\Document\Style{
	/**
	 * Number of horizontal columns in the grid.
	 * @var integer
	 */
	protected $columns = 5;
	
	/**
	 * Number of vertical rows in the grid.
	 * @var integer
	 */
	protected $rows = 4;
	
	/**
	 * Elements and panels in the grid.
	 * @var array
	 */
	protected $cells = array();
	
	/**
	 * Construct a new Application grid.
	 */
	public function __construct(){
		$this->positions = new \Quark\Document\Layout\Positions(array(
			'HEADER' => ['Header', 'Put some simple text here, or your logo.'],
			'CONTENT' => ['Content', 'Put all text and other userinterface elements here.'],
			'FOOTER' => ['Footer', 'This is the place to say your thank-you\'s and put your copyright messages.']
		), array('MAIN_CONTENT' => 'CONTENT'));
	}
	
	/**
	 * Save the layout content to HTML.
	 * @return string HTML
	 */
	public function save(){
		
	}
	
	/**
	 * Saves the styles associated with this layout.
	 * @return string CSS
	 */
	public function saveStyle() {
		
	}	
}