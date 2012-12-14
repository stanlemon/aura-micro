<?php
namespace Aura;

use Aura\Router\Map;
use Aura\Router\DefinitionFactory;
use Aura\Router\RouteFactory;

/**
 * A microframework wrapper for Aura.Router based off of the Silex api
 */
class Micro {

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
	 * Create a Micro framework application
	 */
	public function __construct() {
		$this->map = new Map(new DefinitionFactory, new RouteFactory);
	}

	/**
	 * Raw route -> controller add function
	 *
	 * @param $method string GET/POST/PUT/TYPE http request method
	 * @param $route string Route to be matched against
	 * @param @controller Closure of the controller to be executed
	 */
	public function add($method, $route, $controller) {
		$this->map->add(null, $route, [
			'method' => $method,
			'values' => [
				'controller' => $controller,
			]
		]);
		return $this; // probably should return something else...
	}

	/**
	 * GET http request
	 *
	 * @param $route string Route to be matched against
	 * @param @controller Closure of the controller to be executed
	 */
	public function get($route, $controller) {
		return $this->add(self::METHOD_GET, $route, $controller);
	}

	/**
	 * POST http request
	 *
	 * @param $route string Route to be matched against
	 * @param @controller Closure of the controller to be executed
	 */
	public function post($route, $controller) {
		return $this->add(self::METHOD_POST, $route, $controller);
	}

	/**
	 * PUT http request
	 *
	 * @param $route string Route to be matched against
	 * @param @controller Closure of the controller to be executed
	 */
	public function put($route, $controller) {
		return $this->add(self::METHOD_PUT, $route, $controller);
	}

	/**
	 * DELETE http request
	 *
	 * @param $route string Route to be matched against
	 * @param @controller Closure of the controller to be executed
	 */
	public function delete($route, $controller) {
		return $this->add(self::METHOD_DELETE, $route, $controller);
	}

	/**
	 * Add callback for before routing dispatches
	 *
	 * @param $callback Closure Callback to be executed
	 */
	public function before($callback) {
		$this->callbacks[self::CALLBACK_BEFORE][] = $callback;
	}

	/**
	 * Add callback for after routing dispatches
	 *
	 * @param $callback Closure Callback to be executed
	 */
	public function after($callback) {
		$this->callbacks[self::CALLBACK_AFTER][] = $callback;
	}

	/**
	 * Add callback for when routing dispatching is finsihed
	 *
	 * @param $callback Closure Callback to be executed
	 */
	public function finish($callback) {
		$this->callbacks[self::CALLBACK_FINISH][] = $callback;
	}

	/**
	 * Add callback for when there is an error in routing dispatch
	 *
	 * @param $callback Closure Callback to be executed
	 */
	public function error($callback) {
		$this->callbacks[self::CALLBACK_ERROR][] = $callback;
	}

	/**
	 * Apply callbacks that have been stacked
	 *
	 * @param $type string The type of callback to be applyed
	 * @param mixed Various additional parameters to be passed to the callbacks
	 */
	protected function applyCallbacks() {
		$type = func_get_arg(0);
		$params = array_slice(func_get_args(), 1);

		foreach ($this->callbacks[$type] as $callback) {
			call_user_func_array($callback, $params);
		}
	}

	/**
	 * Run the application executing the dispatch process
	 */
	public function run() {
		try {
			// Just the path of whatever is exeucting.
			$path = substr($_SERVER['REQUEST_URI'], strlen(dirname($_SERVER['PHP_SELF'])));

			if (false === ($route = $this->map->match($path, $_SERVER))) {
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
}
