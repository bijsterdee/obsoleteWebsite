<?php

namespace FaynticServices\Website;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class Application extends ContainerAware implements ContainerAwareInterface
{
    /** @var string */
    private $rootDirectory;

    public function __construct()
    {
        set_error_handler([$this, 'errorHandler']);

        $this->rootDirectory = realpath(__DIR__ . '/../../..');

        require "{$this->rootDirectory}/generated/database.php";
    }

    public function getRootDirectory()
    {
        return $this->rootDirectory;
    }

    public function run()
    {
        $this->dispatchStartup();
        $this->preDispatch();
        $this->dispatch();
        $this->postDispatch();
        $this->dispatchShutdown();
    }

    protected function dispatchStartup()
    {
        session_start();
    }

    protected function preDispatch()
    {
        $container = new ContainerBuilder();
        $container->set('application', $this);
        $container->register('configuration', '\FaynticServices\Website\Configuration')
            ->addMethodCall('loadConfiguration', array("{$this->rootDirectory}/generated/configuration.json"));
        $this->setContainer($container);
    }

    protected function dispatch()
    {
        $requestUri = filter_input(INPUT_SERVER, 'REQUEST_URI');
        if (strpos($requestUri, '?') !== false) {
            $requestPath = strstr($requestUri, '?', true);
        } else {
            $requestPath = $requestUri;
        }
        $requestParts = array_map('ucfirst', explode('/', trim(strtolower($requestPath), '/')));

        foreach ($requestParts as &$requestPart) {
            if (false !== strpos($requestPart, '-')) {
                $requestPart = implode(array_map('ucfirst', explode('-', $requestPart)));
            }
        }

        $controller = $this->loadController($requestParts);
        $controller->setContainer($this->container);
        $controller->setMaintenance(file_exists("{$this->rootDirectory}/.maintenance"));
        $controller->dispatch();
    }

    protected function postDispatch()
    {
    }

    protected function dispatchShutdown()
    {
    }

    public function errorHandler($errorSeverity, $errorMessage, $errorFile, $errorLine)
    {
        throw new \ErrorException($errorMessage, 0, $errorSeverity, $errorFile, $errorLine);
    }

    /**
     * @param array $requestParts
     * @param null|string $lastControllerClass
     * @param array $parameters
     * @return Controller
     */
    private function loadController(array $requestParts = array(), $lastControllerClass = null, array $parameters = array())
    {
        $controllerClass = implode('\\', array_merge(array(__NAMESPACE__, 'Controller'), $requestParts));
        if (class_exists($controllerClass)) {
            return new $controllerClass($lastControllerClass, $parameters);
        } elseif (0 !== count($requestParts)) {
            $lastControllerClass = array_pop($requestParts);
            $parameters = array_merge(array(lcfirst($lastControllerClass)), $parameters);
            return $this->loadController($requestParts, $lastControllerClass, $parameters);
        }
        return new Controller($lastControllerClass, $parameters);
    }
}
