<?php
/**
 * Controller MultiRoute (Automation)
 *
 * @package		Quark-Framework
 * @author		Jeffrey van Harn <Jeffrey at lessthanthree.nl>
 * @since		August 27, 2014
 * @copyright	Copyright (C) 2014 Jeffrey van Harn. All rights reserved.
 * @license		http://opensource.org/licenses/gpl-3.0.html GNU Public License Version 3
 */

// Define Namespace
namespace Quark\System\MVC;
use Quark\Protocols\HTTP\IMutableResponse;
use Quark\System\Router\baseRoute;
use Quark\System\Router\IRoutableRequest;
use Quark\System\Router\Route as RouteInterface;
use Quark\System\Router\URLPathInfo;
use Quark\Util\Type\HttpException;
use Quark\Util\Type\InvalidArgumentTypeException;

// Prevent individual file access
if(!defined('DIR_BASE')) exit;

/**
 * Model View Controller Router
 *
 * This route makes it possible to automatically have requests routed to the controller with a corresponding name with
 * an optional prefix. For example /controllers/home could for example refer to the controller home where
 * '/controllers/' would be the set prefix.
 */
class MultiRoute implements RouteInterface {
    use baseRoute;

    /** @var array The FQN of the controller. */
    protected $controllers;

    /** @var string Virtual server path prefix (Relative to $base). */
    protected $prefix;

    /** @var callable Auto loader for the given Controllers. */
    protected $autoloader;

    /** @var string The default controller to route to, when it is not set. */
    public $defaultController = 'index';

    /** @var string The default controller method to route to, when it is not set. */
    public $defaultMethod = 'index';

    /**
     * @param array $controllers An array of valid Controllers with their FQN. All controllers have to be already loaded in memory or be able to be autoloaded. Can be either an associative (With their names as keys) or FQN only.
     * @param string $prefix The Controller path-prefix. (Must start with a forward slash)
     * @param callable $autoloader An optional auto-loader callback that gets called with the FQN and the base name of the controller to load. Should return true on success or false when it failed.
     * @throws \Quark\Util\Type\InvalidArgumentTypeException When the prefix is not a valid string.
     * @throws \InvalidArgumentException When no controllers were given.
     */
    public function __construct(array $controllers, $prefix='/controllers/', callable $autoloader=null){
        if(count($controllers) > 0){
            if(!is_string(key($controllers)))
                array_walk($controllers, function($value, &$key){
                    $key = basename(strtolower($value), 'controller');
                });
            $this->controllers = $controllers;
        }else throw new \InvalidArgumentException('The number of given controllers must exceed 1. Otherwise I\'d have nothing to route.');

        if(!empty($prefix) && is_string($prefix))
            $this->prefix = $prefix;
        else throw new InvalidArgumentTypeException('prefix', 'string and non-empty', $prefix);

        if(is_null($autoloader) || is_callable($autoloader))
            $this->autoloader = $autoloader;
        else throw new InvalidArgumentTypeException('autoloader', 'callable or null', $autoloader);
    }

    /**
     * Checks if this route can route the given request.
     * @param IRoutableRequest $request
     * @return bool
     */
    public function routable(IRoutableRequest $request){
        $this->parsePath($request, $controller, $method, $arguments);
        return isset($this->controllers[$controller]);
    }

    /**
     * Activate this route and load the applicable resource.
     *
     * This function may ONLY be called after positive feedback (e.g. true) from the routable method.
     * @param IRoutableRequest $request {@see Route::routable()}
     * @param IMutableResponse $response The object where the response should be written to.
     * @throws \Exception
     * @throws \Quark\Util\Type\HttpException
     * @throws \Quark\Util\Type\HttpException
     * @return mixed|bool
     */
    public function route(IRoutableRequest $request, IMutableResponse $response){
        $this->parsePath($request, $controller, $method, $arguments);
        if(isset($this->controllers[$controller])){
            if(!class_exists($this->controllers[$controller], false)){
                // Try and autoload
                if(!is_null($this->autoloader)){
                    try {
                        \call_user_func($this->autoloader, $this->controllers[$controller], $controller);
                    }catch(\Exception $e){
                        throw new HttpException(500, 'Something went wrong whilst trying to invoke the controller.');
                    }
                }

                // Call the controller method
                $fqn = $this->controllers[$controller];
                if(!class_exists($fqn, false))
                    throw new HttpException(500, 'The route expected the controller "'.$fqn.'" to be already available or loaded by the provided autoloader, but it wasn\'t. Please make sure the controller is in the correct namespace, or change your configuration of MultiRoute to reflect the correct namespace.');
                try {
                    /** @var $instance Controller */
                    $instance = new $fqn();
                    $instance->setContext($request, $response);

                    if(method_exists($instance, $method)) {
                        $return = \call_user_func_array([$instance, $method], $arguments);
                    }else throw new HttpException(404, 'The controller method given was not found.');
                }catch(HttpException $e){
                    throw $e;
                }catch(\Exception $e){
                    throw new HttpException(500, 'An exception occurred in the controller that was supposed to handle this request; '.$e->getMessage());
                }

                // try route
                if(!empty($return) && !is_bool($return)){
                    $view = $instance->view;
                    if(!empty($view))
                        return $instance->view->display($return, $request, $response);
                }
                return $return;
            }
        }
        return false;
    }

    /**
     * Get the available parameters for the url builder.
     * @return array Associative array of parameter indexes and descriptions as value.
     */
    public function parameters() {
        return array(
            'controller' => 'The name of the MVC controller to load.',
            'method' => 'Controller method to call.',
            'arguments' => 'Arguments for this method.'
        );
    }

    /**
     * Build a URI pointing to this resource/route with the given params.
     * @param array $params Parameters you want to pass to the receiving end.
     * @param boolean $optimized Whether or not the builder should try to go for compatible url's (E.g. index.php?name=controller&method=methodname or optimized urls like /controller/methodname/
     * @return string The URI that leads to the specified location.
     */
    public function build(array $params, $optimized = false) {
        $pathParams = '';
        $arguments = array();
        if(!empty($params['arguments']) && is_array($params['arguments'])){
            foreach($params['arguments'] as $key => $value){
                if(is_integer($key))
                    $pathParams .= '/'.$value;
                else
                    $arguments[$key] = $value;
            }
        }

        $path = new URLPathInfo(
            $this->base.$params['controller'].'/'.(empty($params['method']) ? '' : $params['method']).$pathParams,
            $arguments
        );
        return $path->export();
    }

    /**
     * Parses an path into the different components for a mvc setup.
     * @param IRoutableRequest $request
     * @param string $controller (OUT) Will be filled with the requested controller name.
     * @param string $method (OUT) Will be filled with the requested controller method name.
     * @param array $arguments (OUT) Arguments for the requested method.
     * @return void
     */
    protected function parsePath(IRoutableRequest $request, &$controller, &$method, &$arguments){
        $path = $request->getPathObject();
        $path->removeBasePath($this->base);
        $path->removeBasePath($this->prefix);
        $controller = !empty($path->path[0]) ? $path->path[0] : (isset($path->query['controller']) ? $path->query['controller'] : $this->defaultController);
        $method = isset($path->path[1]) ? $path->path[1] : $this->defaultMethod;
        $arguments = array_merge(array_slice($path->path, 2), $path->query);
    }

    /**
     * Creates an MVC Route from all the php files/controllers inside the given application subdirectory.
     *
     * So in the default config 'example.com/controllers/home' would point to the controller '\\HomeController' in the file '/system/application/controllers/home.php'.
     * Note: Files starting with an underscore (_) will be ignored, so use this when you want to temporarily disable a controller for example.
     * @param string $controllerPath The directory or path relative to the application directory where the controllers that should be routed reside.
     * @param string $namespace The namespace ALL the controllers reside in. If this is incorrectly specified, the created route object will only throw Internal 500 errors because it couldn't find the required controller object(s). Defaults to the php root namespace (\\).
     * @param string $prefix The prefix for the virtual server path that has to be applied before the controllers to be able to load them.
     * @param string $fileType Use this in environments where the extension of the files is not .php but .php5 or .hphp for example.
     * @return \Quark\System\MVC\Route
     */
    public static function fromApplicationDirectory($controllerPath, $namespace='\\', $prefix='/controllers/', $fileType='.php'){
        $controllers = array();
        $directory = new \DirectoryIterator(DIR_APPLICATION.$controllerPath);
        /** @var $file \SplFileInfo */
        foreach($directory as $file){
            if(!$file->isFile() || !$file->isReadable() || $file->getFilename()[0] == '_')
                continue;
            $name = $file->getBasename($fileType);
            $controllers[$name] = $namespace.ucfirst(str_replace('controller', '', strtolower($name))).'Controller';
        }
        return new MultiRoute($controllers, $prefix, function($fqn, $name) use ($controllerPath, $fileType) {
            // Loads the controller when required.
            if(is_file(DIR_APPLICATION.$controllerPath.DS.$name.$fileType))
                require_once(DIR_APPLICATION.$controllerPath.DS.$name.$fileType);
            else if(is_file(DIR_APPLICATION.$controllerPath.DS.str_ireplace('controller', '', $name).$fileType))
                require_once(DIR_APPLICATION.$controllerPath.DS.str_ireplace('controller', '', $name).$fileType);
            else
                require_once(DIR_APPLICATION.$controllerPath.DS.$name.'controller'.$fileType);
        });
    }
}
