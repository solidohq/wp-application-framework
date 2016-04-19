<?php

namespace Solido\WP;

class Route
{
    private $path = '/';
    private $defaults = array();
    private $methods = array();
    private $requirements = array();
    private $schemes = array();

    public function __construct($path = '/', array $defaults = array(), array $requirements = array(), array $options = array(), $host = '', $schemes = array(), $methods = array())
    {
        $this->setPath($path);
        $this->setDefaults($defaults);
        $this->setRequirements($requirements);
    //$this->setOptions($options);
    //$this->setHost($host);
    // The conditions make sure that an initial empty $schemes/$methods does not override the corresponding requirement.
    // They can be removed when the BC layer is removed.
    if ($schemes) {
        $this->setSchemes($schemes);
    }
        if ($methods) {
            $this->setMethods($methods);
        }
    //$this->setCondition($condition);
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setDefaults(array $defaults)
    {
        $this->defaults = array();

        return $this->addDefaults($defaults);
    }

    public function addDefaults(array $defaults)
    {
        foreach ($defaults as $name => $default) {
            $this->defaults[$name] = $default;
        }
        $this->compiled = null;

        return $this;
    }

    public function setDefault($name, $default)
    {
        $this->defaults[$name] = $default;
        $this->compiled = null;

        return $this;
    }

    public function getDefault($name)
    {
        return isset($this->defaults[$name]) ? $this->defaults[$name] : null;
    }

    public function method($method)
    {
        $this->setMethods(explode('|', $method));

        return $this;
    }

    public function assert($variable, $regexp)
    {
        $this->setRequirement($variable, $regexp);

        return $this;
    }

    public function setMethods($methods)
    {
        $this->methods = array_map('strtoupper', (array) $methods);

    // this is to keep BC and will be removed in a future version
    if ($this->methods) {
        $this->requirements['_method'] = implode('|', $this->methods);
    } else {
        unset($this->requirements['_method']);
    }

        $this->compiled = null;

        return $this;
    }

    public function getMethods()
    {
        return $this->methods;
    }

    public function setRequirements(array $requirements)
    {
        $this->requirements = array();

        return $this->addRequirements($requirements);
    }

    public function addRequirements(array $requirements)
    {
        foreach ($requirements as $key => $regex) {
            $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        }
        $this->compiled = null;

        return $this;
    }

    public function getRequirement($key)
    {
        if ('_scheme' === $key) {
            @trigger_error('The "_scheme" requirement is deprecated since version 2.2 and will be removed in 3.0. Use getSchemes() instead.', E_USER_DEPRECATED);
        } elseif ('_method' === $key) {
            @trigger_error('The "_method" requirement is deprecated since version 2.2 and will be removed in 3.0. Use getMethods() instead.', E_USER_DEPRECATED);
        }

        return isset($this->requirements[$key]) ? $this->requirements[$key] : null;
    }

    public function hasRequirement($key)
    {
        return array_key_exists($key, $this->requirements);
    }

    public function setRequirement($key, $regex)
    {
        $this->requirements[$key] = $this->sanitizeRequirement($key, $regex);
        $this->compiled = null;

        return $this;
    }

    private function sanitizeRequirement($key, $regex)
    {
        if (!is_string($regex)) {
            throw new \InvalidArgumentException(sprintf('Routing requirement for "%s" must be a string.', $key));
        }

        if ('' !== $regex && '^' === $regex[0]) {
            $regex = (string) substr($regex, 1); // returns false for a single character
        }

        if ('$' === substr($regex, -1)) {
            $regex = substr($regex, 0, -1);
        }

        if ('' === $regex) {
            throw new \InvalidArgumentException(sprintf('Routing requirement for "%s" cannot be empty.', $key));
        }

    // this is to keep BC and will be removed in a future version
    if ('_scheme' === $key) {
        @trigger_error('The "_scheme" requirement is deprecated since version 2.2 and will be removed in 3.0. Use the setSchemes() method instead.', E_USER_DEPRECATED);
        $this->setSchemes(explode('|', $regex));
    } elseif ('_method' === $key) {
        @trigger_error('The "_method" requirement is deprecated since version 2.2 and will be removed in 3.0. Use the setMethods() method instead.', E_USER_DEPRECATED);
        $this->setMethods(explode('|', $regex));
    }

        return $regex;
    }

    public function setSchemes($schemes)
    {
        $this->schemes = array_map('strtolower', (array) $schemes);

    // this is to keep BC and will be removed in a future version
    if ($this->schemes) {
        $this->requirements['_scheme'] = implode('|', $this->schemes);
    } else {
        unset($this->requirements['_scheme']);
    }

        $this->compiled = null;

        return $this;
    }
}
