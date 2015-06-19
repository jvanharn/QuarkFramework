<?php
/**
 * Basic MVC Application
 * 
 * @package		Quark-Framework
 * @version		$Id: mvc.php 75 2013-04-17 20:53:45Z Jeffrey $
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		27 december 2012
 * @copyright	Copyright (C) 2011-2012 Jeffrey van Harn. All rights reserved.
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
namespace Quark\System\Application;
use Quark\Document\Document;
use Quark\Document\Layout\Layout;
use Quark\Protocols\HTTP\IRequest;
use Quark\Protocols\HTTP\Server\ServerRequest;
use Quark\Protocols\HTTP\Server\ServerResponse;
use Quark\System\MVC\IMVC;
use Quark\System\MVC\MultiRoute;
use Quark\System\Router\Router;
use Quark\Util\baseSingleton;
use Quark\Util\Type\HttpException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

// Dependencies
\Quark\import('framework.system.mvc.mvc', true);

/**
 * Basic Application Implementation using the MVC pattern.
 * 
 * This is the most basic MVC implementation there is, it sports basic database 
 * initialisation and router setup for routes to their Controllers. Can be
 * easily extended using one of the many available application base traits.
 */
abstract class MVCApplication extends Application implements IMVC {
	use baseSingleton,
		\Quark\System\Application\Base\Extensions,
		\Quark\System\Application\Base\Database;

    /**
     * Router Object.
     * @var \Quark\System\Router\Router
     */
    protected $router;

    /**
     * Document Object
     * @var \Quark\Document\Document
     */
    protected $document;

    /**
     * @param string $controllerPath The local path to the controller files.
     * @param string $controllerNamespace Namespace in which the controller classes reside.
     * @param string $controllerVirtualPathPrefix The prefix for the virtual path through which the controllers will be accessible. (E.g. setting this to '/api/' with a controller named 'ExampleController', will result in urls equivalent to 'http://example.com/api/example')
     * @param array $routes
     * @param string $databaseDriver Name of the database driver that should be used.
     * @param array $databaseOptions Connection info for the database driver, required if the database name is set to a non-null value.
     * @param Layout $layout Optional instance of the (default) layout to be used for the controller generated page.
     * @param string $documentType
     */
    protected function __construct($controllerPath=null, $controllerNamespace='\\', $controllerVirtualPathPrefix='/controllers/', array $routes=array(), $databaseDriver=null, array $databaseOptions=null, Layout $layout=null, $documentType=Document::TYPE_HTML){
		// Initiate the extensions subsystems
		$this->initExtensions();

		// Initiate the database
        if($databaseDriver != null)
		    $this->initDatabaseWithDriverName($databaseDriver, $databaseOptions, $this->extensions);

		// Initiate the router
		$this->router = Router::createInstance(Router::DEFAULT_NAME, array_merge($routes, array(
            MultiRoute::fromApplicationDirectory(
                $controllerPath == null ? DIR_APPLICATION.'controllers'.DIRECTORY_SEPARATOR : $controllerPath,
                $controllerNamespace,
                $controllerVirtualPathPrefix
            )
        )));

        // Create the main document instance
        $this->document = Document::createInstance($layout, $documentType, $this->router);
	}

    /**
     * Get the current application's document object.
     * @return \Quark\Document\Document
     */
    public function getDocument(){
        return $this->document;
    }

    /**
     * Get the current application's router object.
     * @return \Quark\System\Router\Router
     */
    public function getRouter(){
        return $this->router;
    }

    /**
     * Fetches the current request's objects, and calls the routeRequest method.
     * @see routeRequest
     */
    public function run(){
        // Get the current request objects.
        $requestObj = new ServerRequest();
        $responseObj = new ServerResponse();

        // Route the request
        try {
            $this->routeRequest($requestObj, $responseObj);
        }catch(HttpException $e){
            $e->writeTo($responseObj);
        }catch(\Exception $e){
            $e = new HttpException(500, 'An internal server error occurred whilst trying to load the page you requested.', $e);
            $e->writeTo($responseObj);
        }
    }
}