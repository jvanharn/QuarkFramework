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

use Quark\System\Application\Application,
    Quark\Util\baseSingleton,
    Quark\Bundles\Bundles;
use Quark\System\Application\Base\Router as RouterAppBase,
    Quark\System\Application\Base\Document as DocumentAppBase,
    Quark\System\Application\Base\Extensions as ExtensionsAppBase,
    Quark\System\Application\Base\Database as DatabaseAppBase;

use Quark\Libraries\Bootstrap\BootstrapLayout,
	Quark\Libraries\Bootstrap\Components as Components,
	Quark\Libraries\Bootstrap\Elements as Elements,
    Quark\Libraries\Bootstrap\Components\Alert,
    Quark\Libraries\Bootstrap\Elements\Label,
    Quark\Libraries\Bootstrap\Elements\Text,
    Quark\Libraries\Bootstrap\Glyphicon,
    Quark\Document\Utils\Paragraph;

use Quark\Libraries\Bootstrap\Form\Form,
    Quark\Libraries\Bootstrap\Form\Plaintext,
    Quark\Document\Form\Action,
    Quark\Document\Form\Checkbox,
    Quark\Document\Form\Selectable,
    Quark\Document\Form\Textarea,
    Quark\Document\Form\TextField;

use Quark\System\Router\StaticRoute,
	Quark\Document\BundleResourceRoute;

/**
 * Default Quark Framework Homepage.
 */
class BootsrapApplication extends Application{
	use baseSingleton,
		RouterAppBase,
		DocumentAppBase,
		ExtensionsAppBase,
		DatabaseAppBase;
	
	public function __construct(){
		$this->initRouter(array(
			new BundleResourceRoute(),
			new StaticRoute(DIR_ASSETS, 'assets/')
		));

		$this->initDocumentWithLayout(new BootstrapLayout());
		$this->document->resources->reference('bootstrap.css');

		$this->initExtensions();
		$this->initDatabaseWithDriverName( // Database no longer available on Debug VM
			'mysql.driver',
			array('hostname' => 'localhost', 'database' => 'quark', 'username' => 'quark', 'password' => 'quarktest'),
			$this->extensions
		);

		date_default_timezone_set(@date_default_timezone_get()); // Fix timezone warnings.

		// Update the bundles list AT LEAST ONCE before you run the application
		Bundles::updateList(); // Downloads the available (3rd party) bundles that *can be installed*.
		Bundles::_resetInstalledList();
		Bundles::scan(false); // Scan for new *local/already installed* bundles (This HAS to be done before bundles can be used!!)
		//var_dump(array_map(function($id){return Bundles::get($id)->resources;}, Bundles::listInstalled()));
		//var_dump(array_map(function($id){return Bundles::get($id)->resources;}, Bundles::listAvailable()));
	}
	
	public function run(){
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
				->setIcon(Glyphicon::ICO_LOCK);
		$activator->setActivatable($dropdown = new Components\Dropdown());
		$dropdown
			->addLink('test', '#lol')
			->addDivider()
			->addLink('test2', '#lol');
		$buttongroup->addButton($activator);
		$buttongroup->addDropdown($dropdown);

		// Button and dropdown with the short syntaxis
		/** @var Components\Dropdown $secondMenu */
		$secondMenu = null;
		$secondButtongroup = Components\Dropdown::create($secondMenu, 'Shorthand Dropdown', Glyphicon::ICO_OK);
		$secondMenu
			->addHeader('Real menu')
			->addLink('second', '#lol')
			->addLink('test!', '#lol');

		// Place the buttons on the grid
		$layout->placeRow([$buttongroup, $secondButtongroup]);

		// Pager
		$layout->place(new Components\Pager($dropdown, '#next'));// The dropdown won't work as in bootstrap it doesn't work with Pager elements.

		// Pagination
		$layout->place(
			(new Components\Pagination(Components\Pagination::PAGINATION_SM))
				->addCluster(1, 3, 10, '#pages/{{page}}', false));

        // Text with embedded label
        $layout->place(
            Text::make('Lollypop ', false)
                ->add(new Label('Danger ahead!!', Label::LBL_DANGER, Glyphicon::make(Glyphicon::ICO_EXCLAMATION_SIGN)))
                ->add(' '.Glyphicon::html(Glyphicon::ICO_ICE_LOLLY))
        );

        // Form
        $form = new Form($this->document, '/', Form::METHOD_POST, false);
        $form->group(Form::DEFAULT_GROUP, 'Contact');
        $form->setLayoutType(Form::LAYOUT_HORIZONTAL);
        $form->place(new Plaintext('statictext', 'Some static text', 'lol@example.com'));
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
        $textarea = new Textarea('a_poem', 'Type a poem', null, 'Placeholder text');
        $form->place($textarea);
        $form->place(new Action(Action::ACTION_SUBMIT, 'DO THIS STUFF!'));
        if($form->validated())
            $layout->place(new Alert(var_export($form->data(), true), Alert::TYPE_SUCCESS, true));
        else
            $layout->place(new Paragraph($form, 'Contact', 2));

		// Output the resulting document.
		$this->document->display();

		return true;
	}
}