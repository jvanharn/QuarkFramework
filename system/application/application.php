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
	'Library.Bootstrap',

	'Framework.System.Application.Application',
	'Framework.System.Application.Base.Document',

	'Framework.Document.Utils.Literal',
	'Framework.Document.Resources',
	
	'Framework.System.Application.Base.Extensions',
	'Framework.System.Application.Base.Database'
);

use Quark\Bundles\Bundles;
use Quark\Libraries\Bootstrap\BootstrapLayout,
	Quark\Libraries\Bootstrap\Components as Components,
	Quark\Libraries\Bootstrap\Elements as Elements;

use Quark\System\Router\StaticRoute,
	Quark\Document\BundleResourceRoute;

use \Quark\System\Application\Base\Router as RouterAppBase,
	\Quark\System\Application\Base\Document as DocumentAppBase,
	\Quark\System\Application\Base\Extensions as ExtensionsAppBase,
	\Quark\System\Application\Base\Database as DatabaseAppBase;
use Quark\Util\baseSingleton;

/**
 * Default Quark Framework Homepage.
 */
class Application extends \Quark\System\Application\Application{
	use baseSingleton,
		RouterAppBase,
		DocumentAppBase,
		ExtensionsAppBase,
		DatabaseAppBase;
	
	public function __construct(){
		$this->initRouter(array(
			new BundleResourceRoute(),
			new StaticRoute()
		));

		$this->initDocumentWithLayout(new BootstrapLayout());
		$this->document->resources->reference('bootstrap.css');

		$this->initExtensions();
		/*$this->initDatabaseWithDriverName( // Database no longer available on Debug VM
			'mysql.driver',
			array('hostname' => 'localhost', 'database' => 'quark', 'username' => 'quark', 'password' => 'quarktest'),
			$this->extensions
		);*/

		date_default_timezone_set(@date_default_timezone_get()); // Fix timezone warnings.

		// Update the bundles list AT LEAST ONCE before you run the application
		//Bundles::updateList();
		//Bundles::_resetInstalledList();
		//Bundles::scan(false);
		//var_dump(array_map(function($id){return Bundles::get($id)->resources['js'];}, Bundles::listInstalled()));
	}
	
	public function display(){
		/** @var BootstrapLayout $layout */
		$layout = $this->document->layout;

		// Header
		$navigation = new Components\NavigationBar('Quark App Framework');
		$menu = new Components\NavigationBarMenu();
		$menu->addLink('test1');
		$menu->addLink('test2');
		$menu->addDropdown('test3', array('text' => 'link'));
		$navigation->addContent($menu);
		$layout->place($navigation);

		// Breadcrumbs
		$breadcrumbs = new Components\Breadcrumbs();
		$breadcrumbs->append(new Components\BreadcrumbPart('Home', '/'));
		$breadcrumbs->append(new Components\BreadcrumbPart('News'), true);
		$layout->place($breadcrumbs);

		// Dropdown Button inside a Button group (The same as the shorthand, with the exception that this is a small button)
		$buttongroup = new Components\ButtonGroup(Components\ButtonGroup::BTN_GROUP_XS);
		$activator =
			(new Components\Button('Locking menu!'))
				->setIcon('lock');
		$activator->setActivatable($dropdown = new Components\Dropdown());
		$dropdown
			->addLink('test', '#lol')
			->addDivider()
			->addLink('test2', '#lol');
		$buttongroup->addButton($activator);
		$buttongroup->addDropdown($dropdown);
		$layout->place($buttongroup);

		// Button and dropdown with the short syntaxis
		/** @var Components\Dropdown $secondMenu */
		$secondMenu = null;
		$layout->place(Components\Dropdown::create($secondMenu, 'Shorthand Dropdown', 'ok'));
		$secondMenu
			->addHeader('Real menu')
			->addLink('second', '#lol')
			->addLink('test!', '#lol');

		// Pager
		$layout->place(new Components\Pager($dropdown, '#next'));// The dropdown won't work as in bootstrap it doesn't work with Pager elements.

		// Pagination
		$layout->place(
			(new Components\Pagination(Components\Pagination::PAGINATION_SM))
				->addCluster(1, 3, 10, '#pages/{{page}}', false));



		// Left Menu


		// Content

		/*
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
		
		$form = new Form($this->document, '/', Form::METHOD_POST, false);
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
		, 'FOOTER');*/

		// Output the resulting document.
		$this->document->display();

		return true;
	}
}