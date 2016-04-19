<?php

namespace Solido\WP;

class View
{
    protected $template;
    protected $data = array();

    public function __construct($template = null)
    {
        $this->template = $template;
    }

    public function render()
    {
        include $this->template;
    }

    public function setData($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = $key;
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function getData($key = null)
    {
        if (!$key) {
            return $this->data;
        }

        return $this->data[$key];
    }

    public function getTemplatePart($slug, $name = null)
    {
        /* CODE FROM WORDPRESS: wp-includes/general-template.php */
    do_action("get_template_part_{$slug}", $slug, $name);

        $templates = array();
        $name = (string) $name;
        if ('' !== $name) {
            $templates[] = "{$slug}-{$name}.php";
        }

        $templates[] = "{$slug}.php";
    /* END CODE FROM WORDPRESS */

    $template = locate_template($templates);

        $view = new \Solido\WP\View($template);
        $view->setData($this->data);
        $view->render();

        return $this;
    }

    public function get_template_part($slug, $name = null)
    {
        return $this->getTemplatePart($slug, $name);
    }
}
