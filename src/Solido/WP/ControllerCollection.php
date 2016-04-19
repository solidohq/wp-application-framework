<?php

namespace Solido\WP;

use Solido\DIC;

class ControllerCollection extends DIC
{
    protected static $ninstance = 0;

    protected $controllers = array();
    protected $defaultRoute;
    protected $defaultController;
    protected $prefix;

    public function __construct(Application $app, Route $defaultRoute)
    {
        $this->hash = uniqid(self::$ninstance++.'|');

        $this['app'] = $app;

        $this['config'] = function () use ($app) {
            return new DIC();
        };

        $this->defaultRoute = $defaultRoute;
        $this->defaultController = function (Request $request) {
            throw new \LogicException(sprintf('The "%s" route must have code to run when it matches.', $request->attributes->get('_route')));
        };
    }

    public function match($pattern, $to = null)
    {
        $route = clone $this->defaultRoute;
        $route->setPath($pattern);
        $this->controllers[] = $controller = new Controller($route);
        //$route->setDefault('_controller', null === $to ? $this->defaultController : $to);
        $route->setDefault('_controller', $to);

        return $controller;
    }

    public function get($pattern, $to = null)
    {
        return $this->match($pattern, $to)->method('GET');
    }

    public function getRewriteRules($prefix = '', $routes = array())
    {
        return $this->doAddRewriteRules($prefix, $routes);
    }

    private function doAddRewriteRules($prefix, $routes)
    {
        if ($prefix !== '') {
            $prefix = '/'.trim(trim($prefix), '/');
        }

        foreach ($this->controllers as $controller) {
            if ($controller instanceof Controller) {
                $controller->getRoute()->setPath($prefix.$controller->getRoute()->getPath());
                if (!$name = $controller->getRouteName()) {
                    $name = $controller->generateRouteName('');
                    while (isset($routes[$name])) {
                        $name .= '_';
                    }
                    $controller->bind($name);
                }
                $routes[$name] = $controller->getRoute();
                //$controller->freeze();
            } else {
                $routes = array_merge($routes, $controller->doAddRewriteRules($prefix.$controller->prefix, $routes));
            }
        }

        $this->controllers = array();

        return $routes;
    }

    public function getHash()
    {
        return $this->hash;
    }

    public function mount($prefix, ControllerCollection $controllers)
    {
        $controllers->prefix = $prefix;
        $this->controllers[] = $controllers;
    }
}
