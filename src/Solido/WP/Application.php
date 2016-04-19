<?php

namespace Solido\WP;

use Solido\ObjectManager;
use Solido\DIC;

class Application extends DIC
{
    protected static $ninstance = 0;

    protected static $apps = array();
    protected static $idToHash = array();

    protected $controllerCollections = array();
    protected $actions = array();

    protected $hash;

    public function __construct($values = array())
    {
        parent::__construct();

        $this->hash = uniqid(self::$ninstance++.'|');
        self::$apps[$this->hash] = $this;

        $this->initId($values);
        $this->initServices();

        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }
    }

    public function run(Request $request = null)
    {
        $app = $this;

        add_action('init', function () use ($app) {
            //$app['controllers']->addRewriteRules();

            $routes = $app['controllers']->getRewriteRules();

            $this->generateRewriteRules($routes);
        });

        add_filter('query_vars', function ($vars) use ($app) {
            $appId = $app['id'];
            if (!in_array('__solido', $vars)) {
                $vars[] = '__solido';
            }
            $vars[] = "__solido-action__$appId";

            return $vars;
        });

        add_action('parse_request', function ($request) use ($app) {
            $appId = $app['id'];

            if (!isset($request->query_vars["__solido-action__$appId"])) {
                return;
            }
            $actionId = $request->query_vars["__solido-action__$appId"];

            // Here we can modify the request
            // ...

            $action = $this->actions[$actionId];

            if (!is_callable($action)) {
                $action = $this->deduceAction($action, $request);
            }

            if (is_array($action)) {
                $method = new \ReflectionMethod($action[0], $action[1]);
                $parameters = $method->getParameters();
                foreach ($parameters as $parameter) {

                    /*
                    $reflectionClass = $parameter->getClass();
                    if ($reflectionClass instanceof \ReflectionClass) {

                    }

                    echo "[";
                    echo get_class($class);
                    echo "]";
                    exit;

                    $name = $parameter->getName();

                    echo "[$type $name]";
                    */
                }
                //print_r($parameters);
                //exit;
            }

            $response = call_user_func_array($action, array($app));

            if (!$response instanceof Response) {
                $response = new Response($response);
            }

            $response->process($app);
        });
    }

    public function deduceAction($action, $request)
    {
        $dir = $this['config']['controller_dir'];

        if (is_string($action)) {
            if (class_exists($action)) {
                $class = new $action();

                return array($class, 'index');
            }
        }

        $uri = $request->request;

        return function () {
            return 'WHAT THE FUCK?';
        };
    }

    public function _set_id($id)
    {
        $k = array_search($this->hash, self::$idToHash);
        if ($k === false) {
            self::$idToHash[$id] = $this->hash;
        } else {
            self::$idToHash[$id] = self::$idToHash[$k];
            unset(self::$idToHash[$k]);
        }
    }

    public static function getById($id)
    {
        if (!isset(self::$idToHash[$id])) {
            throw new \Exception("Application with id [$id] not found");
        }
        $hash = self::$idToHash[$id];

        return self::$apps[$hash];
    }

    public function setAction($id, $action)
    {
        $this->actions[$id] = $action;
    }

    public function mount($prefix, $controllers)
    {
        if (!$controllers instanceof ControllerCollection) {
            throw new \LogicException('The "mount" method takes either a "ControllerCollection" or a "ControllerProviderInterface" instance.');
        }

        $this['controllers']->mount($prefix, $controllers);

        return $this;
    }

    public function generateRewriteRules($routes)
    {
        foreach ($routes as $k => $route) {
            $match = $route->getPath();
            $action = $route->getDefault('_controller');

            $actionId = md5($match);

            $this->setAction($actionId, $action);

            add_rewrite_rule($match, "index.php?__solido-action__{$this['id']}=$actionId", 'top');
        }

        flush_rewrite_rules();
    }

    protected function initId($values = array())
    {
        if (is_string($values)) {
            $values = array(
                'id' => $values,
            );
        }

        if (isset($values['id'])) {
            $id = $values['id'];
            unset($values['id']);
        } else {
            if (!isset(self::$idToHash['app'])) {
                $id = 'app';
            } else {
                $id = $this->hash;
            }
        }

        $this['id'] = $id;
    }

    protected function initServices()
    {
        $app = $this;

        $this['om'] = function () {
            return new ObjectManager();
        };

        $this['controllers_factory'] = $this->factory(function () use ($app) {
            $controllerCollection = $app['om']->create('\Solido\WP\ControllerCollection', array($app, $app['route_factory']));
            $this->controllerCollections[$controllerCollection->getHash()] = $controllerCollection;

            return $controllerCollection;
        });

        $this['controllers'] = function () use ($app) {
            return $app['controllers_factory'];
        };

        $this['config'] = function () use ($app) {
            return $app['om']->create('\Solido\DIC');
        };

        $this['route_class'] = '\Solido\WP\Route';
        $this['route_factory'] = function () use ($app) {
            return $app['om']->create($app['route_class']);
        };
    }

}
