<?php

namespace Solido\WP;

class Response
{
    protected $templates = array('page.php');
    protected $data = array();
    protected $response;

    public function __construct($response = null)
    {
        $this->response = $response;
    }

    public function process($app)
    {
        if ($this->response instanceof \WP_Query) {
            $this->processWpQuery();
        } else {
            $this->processDefault();
        }

        add_filter('posts_request', function ($sql, \WP_Query &$query) use ($app) {
            return $this->bailWPQuery($sql, $query, $app);
        }, 10, 2);

        add_filter('template_include', function ($original_template) {
            $template = locate_template($this->templates);
            if (empty($template)) {
                return $original_template;
            }

            return $template;
        });

        add_filter('template_include', function ($template) {
            $view = new \Solido\WP\View($template);
            $view->setData($this->data);
            $view->render();

            return false;
        }, 999, 1);
    }

    protected function processWpQuery()
    {
        remove_filter('template_redirect', 'redirect_canonical');
        remove_filter('template_redirect', 'wp_old_slug_redirect');

        add_action('parse_query', array($this, 'parse_query_wp_query'), 10, 1);
    }

    protected function processDefault()
    {
        add_action('parse_query', array($this, 'parse_query'), 10, 1);
        add_filter('the_posts', array($this, 'the_posts'), 10, 1);
    }

    public function bailWPQuery($sql, \WP_Query &$query, Application $app)
    {
        $appId = $app['id'];
        if (!isset($wpQuery->query_vars["__solido-action__$appId"])) {
            return $sql;
        }

        $query->query_vars['no_found_rows'] = true;
        /* prevent post term and meta cache update queries */
        $query->query_vars['cache_results'] = false;

        return 'SELECT NULL FROM dual WHERE false';
    }

    public function parse_query_wp_query(\WP_Query &$query)
    {
        global $wp_query, $wp_the_query;

        if (!$query === $wp_query) {
            return;
        }
        remove_filter('parse_query', array($this, 'parse_query'), 10);

        $wp_query = $this->response;
        $wp_the_query = $wp_query;

        return $query;
    }

    public function parse_query(\WP_Query &$query)
    {
        remove_filter('parse_query', array($this, 'parse_query'));

        $qv = $query->query_vars;
        $query->init();

        $query->query_vars = $qv;
        $query->query_vars['p'] = -1;
        $query->is_page = true;
        $query->is_singular = true;
        $query->query_vars['error'] = '';
        $query->is_404 = false;
    }

    public function the_posts($posts)
    {
        remove_filter('the_posts', array($this, 'the_posts'));

        $post = Faker::post(array(
            'post_content' => $this->response,
        ));

        $post->data = $this->data;
        $posts = array(
            $post,
        );

        return $posts;
    }

    public function setTemplate($template)
    {
        if (is_array($template)) {
            $this->templates = $template;
        } else {
            $this->templates = array($template);
        }

        return $this;
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
}
