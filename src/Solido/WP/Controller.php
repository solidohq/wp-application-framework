<?php

namespace Solido\WP;

class Controller
{
    private $route;
    private $routeName;

    public function __construct(Route $route)
    {
        $this->route = $route;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function getRouteName()
    {
        return $this->routeName;
    }

    public function bind($routeName)
    {
        $this->routeName = $routeName;

        return $this;
    }

    public function __call($method, $arguments)
    {
        if (!method_exists($this->route, $method)) {
            throw new \Exception(sprintf('Method "%s::%s" does not exist.', get_class($this->route), $method));
        }

        call_user_func_array(array($this->route, $method), $arguments);

        return $this;
    }

    public function generateRouteName($prefix = '')
    {
        $methods = implode('_', $this->route->getMethods()).'_';

        $routeName = $methods.$prefix.$this->route->getPath();
        $routeName = str_replace(array('/', ':', '|', '-'), '_', $routeName);
        $routeName = preg_replace('/[^a-z0-9A-Z_.]+/', '', $routeName);

      // Collapse consecutive underscores down into a single underscore.
      $routeName = preg_replace('/_+/', '_', $routeName);

        return $routeName;
    }
}
