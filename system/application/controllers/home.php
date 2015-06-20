<?php
/**
 * Simple sample index controller
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <support@pagetreecms.org>
 * @since		July 18, 2015
 * @copyright	Copyright (C) 2015 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 *
 * Copyright (C) 2015 Jeffrey van Harn
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
namespace QuarkSample\Controllers;
use Quark\Document\Document;
use Quark\Libraries\Bootstrap\BootstrapLayout;
use Quark\System\MVC\Controller,
    Quark\Libraries\Bootstrap\Components as Components,
	Quark\Libraries\Bootstrap\Elements as Elements;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Simple homepage controller.
 */
class HomeController extends Controller {
    /** @var BootstrapLayout */
    protected $layout;

    /**
     * Initialize the controller.
     */
    public function __construct(){
        $this->layout = Document::getInstance()->layout;
    }

    /**
     * URL: /
     * @return bool
     */
    public function index(){
        // You can place a jumbotron inside the main (grid) container, however, that breaks the jumbotron on mobile devices.
        //$this->layout->getContainer()->prependChild(new Components\Jumbotron('MVC Sample Application', 'This is a sample Jumbotron component from Bootstrap.'));
        $this->layout->place(new Components\Jumbotron('MVC Sample Application', 'This is a sample Jumbotron component from Bootstrap.'));

        $this->layout->place(new Components\Alert('YAY SUCCESS!', Components\Alert::TYPE_SUCCESS));

        return true;
    }
}