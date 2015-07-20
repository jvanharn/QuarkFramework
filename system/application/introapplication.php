<?php
/**
 * Simple sample application.
 * 
 * @package		Quark-Framework
 * @version		$Id: application.php 73 2013-02-10 15:01:47Z Jeffrey $
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 2, 2012
 * @copyright	Copyright (C) 2012-2013 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 * 
 * Copyright (C) 2012-2013 Jeffrey van Harn
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
namespace QuarkSampleIntro;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
/**
 * Quark helps you manage dependencies for your application with Import functionality.
 * If you have used Jooml! before, usage should be familiar.
 */
\Quark\import(
	'Framework.System.Application.Application',
	'Framework.System.Application.Base.Document',
	'Framework.Document.Utils.Literal'
);

/**
 * Quark uses namespaces to prevent class name duplication and box in dependencies. 'use' makes it easy to use the class without their FQN or namespaces.
 */
use Quark\Document\Headers;
use	\Quark\Document\Utils\Text,
	\Quark\Document\Utils\Literal,
	\Quark\Document\Utils\Paragraph,
	
	\Quark\Document\Form\Form,
	\Quark\Document\Form\TextField,
	\Quark\Document\Form\Action,
	\Quark\Document\Form\Checkbox,
	\Quark\Document\Form\Selectable;

use Quark\System\Application\Application,
	Quark\System\Application\Base\Document as BasicDocument,
	Quark\Util\baseSingleton;

/**
 * Default Quark Framework Homepage.
 */
class IntroApplication extends Application{
	use baseSingleton,
		BasicDocument;

	/**
	 * The application base traits help you build an application with easy by setting it up with common defaults.
	 * In this example we only use the "Document" helpers, but there are helpers for setting up the Router, Database and Extension systems.
	 */
	public function __construct(){
		/**
		 * This command initializes the document system with the most basic layout (layouts position your elements on your web-page/app)
		 * If you would like to use any of the other available layouts, try one of the commented out variations. (use only one!)
		 */
		$this->initDocument();
		$this->document->headers->add(Headers::TITLE, array(), 'Quark Framework Sample');
		$this->document->headers->add(Headers::META, array('name'=>'viewport', 'content'=>'width=device-width, initial-scale=1.0, maximum-scale=1.0'));
		$this->document->headers->add(Headers::LINK, array('rel'=>'shortcut icon', 'href'=>'/assets/images/icon.ico', 'type'=>'image/x-icon'));
		//$this->initDocumentWithLayout(new GridLayout(16, 10)); // When using this layout, make sure you adjust the regions/positions of the elements in display, as this layout does not have the "header" and "footer" regions.

		// Fix timezone warnings.
		date_default_timezone_set(@date_default_timezone_get());
	}

	/**
	 * This function builds the display of the page in this super basic example.
	 */
	public function run(){
		// Header
		/**
		 * When using Quark's Document system (Strongly recommended) you can easily place the provided or your own page or application parts onto the page with the place command.
		 * This one places a "Text" element in the region "header".
		 * The regions are defined per layout. This layout for example has "Header", 'Content' and 'Footer'. All layouts also contain an alias, "Main_Content". Which should always point to a element between the footer and header for the layout, in this layout's case it points to the "Content" region.
		 * The GridLayout as defined above has the positions "Span1" until "Span%n", where %n is the number of columns the layout has. In the example line in the constructor this is 16.
		 */
		$this->document->place(new Text('Quark App Framework'), 'HEADER');
		
		// Content
		$this->document->place(
			new Literal(array('html' => 
				'<h2>Introduction</h2>'.
				
				'<p>
					Quark is an Application Framework which for Quark basically means that it helps you to easily and quickly conceive simple websites,
					web applications and even complete web services and matching <abbr title="Application Programming Interface">API\'s</abbr>.
					Quark does this in a way that gives you as much control as possible, for almost everything you may possibly need we try to provide
					multiple ways to do something with one being the preferred one. If you do not like to build your document using DocumentModels and
					Interface elements, you can use a templating system. If you just want to generate the HTML yourself? Go ahead.
				</p>'.
				'<p>
					Don\'t like all those grumpy old Framework\'s and cms\'s that have had the same aged functional interface since PHP3?
					We don\'t like them either. While providing a consistent interface for you to use, we try to make available to you the latest and
					greatest PHP functions and speed improvements that we can possibly offer. But instead of holding on to a part of the API that has
					become outdated, we provide you with a detailed upgrade guide on how to convert your existing application to the new major version
					and just fix what has to be fixed. For example, we already heavily use Inline Funcions, Short-Syntax array notation and most of
					all Trait\'s.
				</p>'
			))
		, 'MAIN_CONTENT');
		$this->document->place(
			new Literal(array('html' => 
				'<h2>History</h2>'.
				'<p>
					The Quark Application Framework was initially conceived for a <abbr title="Content Management System">CMS</abbr> named PageTree CMS.
				</p>'))
		, 'MAIN_CONTENT');
		$this->document->place(
			new Literal(array('html' => 
				'<h2>Documentation</h2>'.
				'<p>
					Even though Quark is still in the early stages of it\'s development, we have a Documentation that matches any other Framework\'s docs.
					The documentation consists of a considerable amount of <a href="http://quark.lessthanthree.nl/" title="Application Programming Interface">API Documentation</a>,
					we also have some <a href="http://quark.lessthanthree.nl/">regular documentation</a> and off course some simple <a href="http://quark.lessthanthree.nl/">tutorials</a> to get you started!
				</p>'))
		, 'MAIN_CONTENT');

		// Form
		$form = new Form($this->document, '/', Form::METHOD_POST, false);
		$form->group(Form::DEFAULT_GROUP, 'Contact');
		$form->place(new TextField('name', 'Your name'));
		$form->place(new TextField('love', 'Awesome'));
		$form->place(new Checkbox('checky', 'Check diz out.', false));
		$field = new TextField('validated_field','This field get\'s validated');
		$field->addValidator(function($v){
			if($v != 'Quark is Awesome!!')
				return 'This field should equal "Quark is Awesome!!".';
			else return true;
		});
		$form->place($field);
		$form->place(new Selectable('lolzz','Select some',false,['option1', 'option2', 'option3', 'option4', 'option5', 'option6', 'option7']));
		$form->place(new Action(Action::ACTION_SUBMIT));
		if($form->validated()) $this->document->place(new Paragraph(var_export($form->data(), true), 'Results', 2), 'CONTENT');
		else $this->document->place(new Paragraph($form, 'Contact', 2), 'CONTENT');
		
		// Footer
		$this->document->place(
			new Text('Copyleft 2012 LessThanThree Design')
		, 'FOOTER');

		// Not necessary as in most cases the document will detect shutdowns and display automatically, but we do it just in case
		$this->document->display();

		return true;
	}
}