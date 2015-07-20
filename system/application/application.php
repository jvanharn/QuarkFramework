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
use Quark\Document\Headers;
use Quark\Document\Utils\Image;
use Quark\Libraries\Bootstrap\Form\Action;
use Quark\Protocols\HTTP\IMutableResponse;
use Quark\Protocols\HTTP\Server\ServerResponse;
use Quark\System\Application\MVC;
use Quark\System\Application\MVCApplication;
use Quark\System\MVC\MultiRoute;
use Quark\System\Router\IRoutableRequest;
use Quark\Util\baseSingleton;
use Quark\System\Application\Base\Router as RouterAppBase,
    Quark\System\Application\Base\Document as DocumentAppBase,
    Quark\System\Application\Base\Extensions as ExtensionsAppBase,
    Quark\System\Application\Base\Database as DatabaseAppBase;
use Quark\Bundles\Bundles;

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
    Quark\Document\Form\Checkbox,
    Quark\Document\Form\Selectable,
    Quark\Document\Form\Textarea,
    Quark\Document\Form\TextField;

use Quark\System\Router\StaticRoute,
	Quark\Document\BundleResourceRoute;
use Quark\Util\Type\HttpException;


/**
 * Default Quark Framework Homepage.
 */
class Application extends MVCApplication {
    /**
     * Configure the system/application.
     */
    public function __construct(){
        parent::__construct(
            // Controllers
            'controllers/', // Path of the controller directory (Relative to the application directory).
            '\\QuarkSample\\Controllers\\',
            '/',

            // Routes
            array(
                new BundleResourceRoute(),
                new StaticRoute(DIR_ASSETS, 'assets/')
            ),

            // Database
            'mysql.driver',
            array('hostname' => 'localhost', 'database' => 'quark', 'username' => 'quark', 'password' => 'quarktest'),

            // Layout
            new BootstrapLayout()
        );

        // Reference the bootstrap library
		$this->document->resources->reference('bootstrap.css');

		// Setup the document
		$this->document->headers->add(Headers::TITLE, array(), 'Quark Framework Sample');
		$this->document->headers->add(Headers::META, array('name'=>'viewport', 'content'=>'width=device-width, initial-scale=1.0, maximum-scale=1.0'));
		$this->document->headers->add(Headers::LINK, array('rel'=>'shortcut icon', 'href'=>'/assets/images/icon.ico', 'type'=>'image/x-icon'));

        // Set the default/home controller
        foreach($this->router as $route){
            if($route instanceof MultiRoute)
                $route->defaultController = 'home';
        }

		\date_default_timezone_set(@\date_default_timezone_get()); // Fix timezone warnings.

        if(Bundles::cacheWritable() && filemtime(BUNDLE_LIST_PATH) < (time() - 604800)) { // Update once in the week
            $this->document->place(new Alert('Updating local resource bundle cache..', Alert::TYPE_INFO));
            Bundles::updateList(); // Downloads the available (3rd party) bundles that *can be installed*.
            Bundles::_resetInstalledList();
            Bundles::scan(false); // Scan for new *local/already installed* bundles (This HAS to be done before bundles can be used!!)
        }else if(!Bundles::cacheWritable()){
            // @todo this is kind of ugly.
            $page = new HttpException(500, 'The bundle cache has not been made writable, and therefore will have to be reloaded every time you request a page from the server. This is bad for performance, please make the file "'.BUNDLE_LIST_PATH.'" writable for me.');
            $page->writeTo(new ServerResponse());
        }
	}

    /**
     *
     * @param IRoutableRequest $request
     * @param IMutableResponse $response
     * @return boolean
     */
    public function routeRequest(IRoutableRequest $request, IMutableResponse $response) {
        // Header
        //$navigation = new Components\NavigationBar('Quark App Framework');
        $navigation = new Components\NavigationBar(Image::find($this->document, 'icon.ico', 'default', null, 'Quark Framework')->dimensions(null, 20));
        $navigation->type = Components\NavigationBar::TYPE_INVERTED;
        $navigation->position = Components\NavigationBar::POS_STATIC_TOP;

        // Add menu
        $menu = new Components\Navigation();
        $menu->addLink('Home', '/home/index');
        $menu->addLink('Domain', '/domain');
        $menu->addDropdown('User', array('User1' => '/user/get/1'));
        $navigation->place($menu);

        // Add search form
        $search = new Form($this->document, '/home/search', Form::METHOD_POST, false);
        $search->place(new TextField('search', null, null, 'Search'));
        $search->place(new Action(Action::ACTION_SUBMIT, '', Glyphicon::ICO_SEARCH));
        $navigation->place($search, Components\NavigationBar::ALIGN_RIGHT);

        $this->document->place($navigation, BootstrapLayout::POSITION_BEFORE_CONTAINER);

        // Run the controller
        if(!$this->router->route($request, $response)){
            // 404
            $errorPage = new HttpException(404, 'Could not find the requested resource.');
            $errorPage->writeTo($response);
        }

        $this->document->display();
    }
}