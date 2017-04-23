<?php
/*
 * @package Aura.Micro
 *
 * @license http://opensource.org/licenses/MIT MIT
 */
namespace Aura\Micro;

use Aura\Router\Map;
use Aura\Router\DefinitionFactory;
use Aura\Router\RouteFactory;

/**
 * 
 * @package Aura.Micro
 *
 * @license http://opensource.org/licenses/MIT MIT
 * 
 * A microframework wrapper for Aura.Router based off of the Silex api
 * 
 */
class Micro
{

    /**
     * Request method GET
     */
    const METHOD_GET = "GET";

    /**
     * Request method POST
     */
    const METHOD_POST = "POST";

    /**
     * Request method PUT
     */
    const METHOD_PUT = "PUT";

    /**
     * Request method DELETE
     */
    const METHOD_DELETE = "DELETE";

    /**
     * Callback before routes are dispatched
     */
    const CALLBACK_BEFORE = 'before';

    /**
     * Callback after routes are dispatched
     */
    const CALLBACK_AFTER = 'after';

    /**
     * Callback after routing and error handling
     */
    const CALLBACK_FINISH = 'finish';

    /**
     * Callback when a routing error occurs
     */
    const CALLBACK_ERROR = 'error';

    /**
     * Aura\Router\Map collection of routes
     */
    protected $map;

    /**
     * Callbacks for route execution
     */
    protected $callbacks = array(
        'before' => array(),
        'after' => array(),
        'finish' => array(),
        'error' => array(),
    );

	/**
	 * Parameters to look for in function calls
	 */
	protected $params = array();


    /**
     * Create a Micro framework application
     * 
     * @uses Aura\Router\Map
     * @uses Aura\Router\DefinitionFactory
     * @uses Aura\Router\RouteFactory
     */
    public function __construct()
    {
        $this->map = new Map(new DefinitionFactory, new RouteFactory);
    }

    /**
     * Get the app router object so it can be worked with directly
     *
     * @return Aura\Router\Map
     */
    public function getMap()
    {
        return $this->map;
    }

	/**
	 * Set a method parameter
	 *
	 * @param string $key Name of parameter
	 * @param mixed $value Value of parameter to be passed
	 */
	public function set($key, $value)
	{
		if (array_key_exists($key, $this->params)) {
			throw new \InvalidArgumentException(sprintf("Paramter %s has already been defined", $key));
		}

		$this->params[$key] = $value;

		return $this;
	}

	/**
	 * Fetches a method parameter
	 *
	 * @param string $key Name of parameter
	 * @return mixed The result of the parameter
	 */
	public function fetch($key)
	{
		if (!array_key_exists($key, $this->params)) {
			return null; // Die quietly
		}

		if ($this->params[$key] instanceof \Closure) {
			$this->params[$key] = $this->params[$key]();
		}

		return $this->params[$key];
	}

    /**
     * Raw route -> controller add function
     *
     * @param string $method GET/POST/PUT/TYPE http request method
     * 
     * @param string $route  Route to be matched against
     * 
     * @param closure $controller Closure of the controller to be executed
     * 
     * @return $this
     *
     */
    public function add($method, $route, $controller)
    {
        $this->map->add(
            null,
            $route,
            [
                'method' => $method,
                'values' => [
                    'controller' => $controller,
                ]
            ]
        );
        return $this; // probably should return something else...
    }

    /**
     * GET http request
     *
     * @param string  $route Route to be matched against
     * 
     * @param closure $controller Closure of the controller to be executed
     * 
     * @return $this
     */
    public function get($route, $controller)
    {
        return $this->add(self::METHOD_GET, $route, $controller);
    }

    /**
     * POST http request
     *
     * @param string  $route Route to be matched against
     * 
     * @param Closure $controller Closure of the controller to be executed
     * 
     * @return $this
     */
    public function post($route, $controller)
    {
        return $this->add(self::METHOD_POST, $route, $controller);
    }

    /**
     * PUT http request
     *
     * @param string $route Route to be matched against
     * 
     * @param Closure $controller Closure of the controller to be executed
     * 
     * @return $this
     */
    public function put($route, $controller)
    {
        return $this->add(self::METHOD_PUT, $route, $controller);
    }

    /**
     * DELETE http request
     *
     * @param string $route Route to be matched against
     * 
     * @param Closure $controller Closure of the controller to be executed
     * 
     * @return $this
     */
    public function delete($route, $controller)
    {
        return $this->add(self::METHOD_DELETE, $route, $controller);
    }

    /**
     * Add callback for before routing dispatches
     *
     * @param Closure $callback Closure Callback to be executed
     * 
     * @return void
     */
    public function before($callback)
    {
        $this->callbacks[self::CALLBACK_BEFORE][] = $callback;
    }

    /**
     * Add callback for after routing dispatches
     *
     * @param Closure $callback Closure Callback to be executed
     * 
     * @return void
     * 
     */
    public function after($callback)
    {
        $this->callbacks[self::CALLBACK_AFTER][] = $callback;
    }

    /**
     * Add callback for when routing dispatching is finsihed
     *
     * @param Closure $callback Closure Callback to be executed
     * 
     * @return void
     * 
     */
    public function finish($callback)
    {
        $this->callbacks[self::CALLBACK_FINISH][] = $callback;
    }

    /**
     * Add callback for when there is an error in routing dispatch
     *
     * @param Closure $callback Closure Callback to be executed
     * 
     * @return void
     */
    public function error($callback)
    {
        $this->callbacks[self::CALLBACK_ERROR][] = $callback;
    }

    /**
     * Apply callbacks that have been stacked
     *
     * $type   The type of callback to be applyed
     * $params Various additional parameters to be passed to the callbacks
     */
    protected function applyCallbacks()
    {
        $type = func_get_arg(0);
        $params = array_slice(func_get_args(), 1);

        foreach ($this->callbacks[$type] as $callback) {
            call_user_func_array($callback, $params);
        }
    }

    /**
     * Run the application executing the dispatch process
     * 
     * @return void
     */
    public function run($path = null, $request = null)
    {
        try {
            if (is_null($path)) {
				$dir = dirname($_SERVER['PHP_SELF']);

				$path = (strlen($dir) > 1) ? 
					substr($_SERVER['REQUEST_URI'], strlen($dir)) :
					$_SERVER['REQUEST_URI'];
            }

            if (is_null($request)) {
                $request = $_SERVER;
            }

            if (false === ($route = $this->map->match($path, $request))) {
                throw new \InvalidArgumentException("No route found!");
            } else {
                $params = $route->values;

                $controller = $params["controller"];

                unset($params["controller"]);

                $reflection = new \ReflectionFunction($controller);

                $args = array();

                foreach ($reflection->getParameters() as $parameter) {
                    if (isset($params[$parameter->getName()])) {
                        $args[] = $params[$parameter->getName()];
                    } elseif (($value = $this->fetch($parameter->getName())) !== null) {
						$args[] = $value;
					} else {
                        $args[] = null;
                    }
                }

                $this->applyCallbacks(self::CALLBACK_BEFORE);

                call_user_func_array($controller, $args);

                $this->applyCallbacks(self::CALLBACK_AFTER);
            }
        } catch (\Exception $e) {
            $this->applyCallbacks(self::CALLBACK_ERROR, $e);
        }

        $this->applyCallbacks(self::CALLBACK_FINISH);
    }
    
    /**
     * 
     * Allows us to call, e.g., $app->map->add() to override stuff
     * in a seemingly-direct manner.
     *
     * @param string $method Method to call.
     * 
     * @param array $params Params for the method.
     * 
     */
    public function __call($method, array $params)
    {
        return call_user_func_array([$this->map, $method], $params);
    }
}
