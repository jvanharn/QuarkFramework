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
namespace QuarkSample;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
\Quark\import(
	'Framework.System.Application.Application',
	'Framework.System.Application.Base.Document',
	'Framework.Document.Utils.Literal',
	
	'Framework.System.Application.Base.Extensions',
	'Framework.System.Application.Base.Database',
		
	'Framework.Security.Password'
);

use Quark\Archive\Zip;
use	\Quark\Document\Utils\Text as Text,
	\Quark\Document\Utils\Literal as Literal,
	\Quark\Document\Utils\Paragraph as Paragraph,
	
	\Quark\Document\Form\Form as Form,
	\Quark\Document\Form\TextField as Field,
	\Quark\Document\Form\Action as Action,
	\Quark\Document\Form\Checkbox as Checkbox,
	\Quark\Document\Form\Selectable as Selectable,
		
    \Quark\Util\Number as Number;

/**
 * Default Quark Framework Homepage.
 */
class Application extends \Quark\System\Application\Application{
	use \Quark\Util\baseSingleton,
		\Quark\System\Application\Base\Document,
		\Quark\System\Application\Base\Extensions,
		\Quark\System\Application\Base\Database;
	
	public function __construct(){
		$this->initDocument();
		$this->initExtensions();
		$this->initDatabaseWithDriverName(
			'mysql.driver',
			array('hostname' => 'localhost', 'database' => 'quark', 'username' => 'quark', 'password' => 'quarktest'),
			$this->extensions
		);
		/*var_dump(
			$this->database
				->select('test')->from($this->database->select('*')->from('love'))
				->where([['news', '=', 'good']])
			->save(true)
			, $this->database
				->prepare('SELECT * FROM lol WHERE id = ?')
				->query([0])
		);*/

		date_default_timezone_set(@date_default_timezone_get()); // Fix timezone warnings.
	}
	
	public function display(){
		// Header
		$this->document->place(
			new Text('Quark App Framework')
		, 'HEADER');
		
		// Content
		$this->document->place(
			new Literal(array('html' => 
				'<h2>Introduction</h2>'.
				
				'<p>
					Quark is an Application Framework which for Quark basically means that it helps you to easily and quickly conceive simple websites,
					web applications and even complete web services and matching <abbr title="Application Programming Interface">API\'s</abbr>.
					Quark does this in a way that gives you as much control as possible, for almost everything you may possibly need we try to provide
					multiple ways to do something with one being the preffered one. If you do not like to build your document using DocumentModels and
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
		, 'CONTENT');
		$this->document->place(
			new Literal(array('html' => 
				'<h2>History</h2>'.
				'<p>
					The Quark Application Framework was initially conceived for a <abbr title="Content Management System">CMS</abbr> named PageTree CMS.
				</p>'))
		, 'CONTENT');
		$this->document->place(
			new Literal(array('html' => 
				'<h2>Documentation</h2>'.
				'<p>
					Even though Quark is still in the early stages of it\'s development, we have a Documentation that matches any other Framework\'s docs.
					The documentation consists of a considerable amount of <a href="http://quark.lessthanthree.nl/" title="Application Programming Interface">API Documentation</a>,
					we also have some <a href="http://quark.lessthanthree.nl/">regular documentation</a> and off course some simple <a href="http://quark.lessthanthree.nl/">tutorials</a> to get you started!
				</p>'))
		, 'CONTENT');
		
		$form = new Form('/', Form::METHOD_POST, false);
		$form->group(Form::DEFAULT_GROUP, 'Contact');
		$form->place(new Field('name', 'Your name'));
		$form->place(new Field('love', 'Awesome'));
		$form->place(new Checkbox('checky', 'Check diz out.', false));
		$field = new Field('validated_field','This field get\'s validated');
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

		return true;
	}
}