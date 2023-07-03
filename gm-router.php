<?php
/**
 *   _____ __  __   _____             _            
 *  / ____|  \/  | |  __ \           | |           
 * | |  __| \  / | | |__) |___  _   _| |_ ___ _ __ 
 * | | |_ | |\/| | |  _  // _ \| | | | __/ _ \ '__|
 * | |__| | |  | | | | \ \ (_) | |_| | ||  __/ |   
 *  \_____|_|  |_| |_|  \_\___/ \__,_|\__\___|_|   
 *
 * @author: gamemaster123356
 * @version 1.0.0
 *
 * @description:
 * A flexible and intuitive PHP router for handling routing and middleware in web applications.
 *
 * @license: GNU GPL v3
 * @gtihub: https://github.com/gamemaster123356/gm-router
 */

class GMRouter {
    /**
     * The registered routes in the router.
     * Each route is represented as an array with keys:
     * - 'method': The HTTP method(s) associated with the route.
     * - 'uri': The pattern to match the requested URI against.
     * - 'handler': The callback or handler function to execute when a match is found.
     * - 'middlewares': An array of middlewares attached to the route.
     *
     * @var array 
     */
    private $routes = [];

    /**
     * The registered middlewares in the router.
     * It is an associative array where the keys represent the middleware names,
     * and the values are the corresponding middleware callback functions.
     *
     * @var array 
     */
    private $middlewares = [];

    /**
     * The middlewares associated with the current route group.
     * It is an array that accumulates the middlewares as routes are added within a group.
     *
     * @var array 
     */
    private $groups = [];

    /**
     * The named routes in the router.
     * It is an associative array where the keys represent the route names,
     * and the values are the corresponding route URIs.
     *
     * @var array 
     */
    private $routeNames = [];

    /**
     * The middleware groups registered in the router.
     * It is an associative array where the keys represent the middleware group names,
     * and the values are arrays of middleware names belonging to each group.
     *
     * @var array 
     */
    private $middlewareGroups = [];

	/**
     * The registered HTTP error handlers in the router.
     * It is an associative array where the keys represent the HTTP status codes,
     * and the values are the corresponding error handler functions or file paths.
     *
     * @var array
     */
    private $errorHandlers = [];

    /**
    * The options set for the router.
    * It is an associative array where the keys represent the option names,
    * and the values are the corresponding option values.
    *
    * @var array
    */
    private $options = [];

    /**
    * GMRouter constructor.
    *
    * @param array $options The options for GMRouter.
    * @throws Exception If an invalid option is provided.
    */
    public function __construct($options = []) {
        $validOptions = [
            'controllersClassDir' => 'string',
            'middlewareClassDir' => 'string',
			'csrfTokenExpireTime' => 'integer',
			'csrfAllowedReferers' => 'array',
        ];

        $defaultOptions = [
            'controllersClassDir' => 'App/Controllers',
            'middlewareClassDir' => 'App/Middleware',
			'csrfTokenExpireTime' => 3600,
			'csrfAllowedReferers' => [''],
        ];

        $options = array_merge($defaultOptions, $options);

        foreach ($options as $option => $value) {
            if (!array_key_exists($option, $validOptions)) {
                throw new Exception("GM Router: Option '{$option}' does not exist.");
            }

            $type = $validOptions[$option];

            if (gettype($value) !== $type) {
                throw new Exception("GM Router: Option '{$option}' must be of type '{$type}'.");
            }
        }

        $this->options = $options;
    }
	private function loadClass($classPath) {
		$classPath = str_replace('\\\\', '\\', $classPath);
		$classPath = str_replace('\\', DIRECTORY_SEPARATOR, $classPath);
		$classPath = ltrim($classPath, DIRECTORY_SEPARATOR);
		$filePath = $classPath . '.php';
		if (file_exists($filePath)) {
			require($filePath);
		} else {
			throw new Exception("GM Router: Class '{$classPath}' does not exist.");
		}
	}

    /**
    * Adds a route to the router.
    *
    * @param string|array     $methods          The HTTP method(s) for the route.
    * @param string           $uri              The URI pattern for the route.
    * @param string|null      $handlerType      The type of handler for the route (controller, file, or callback).
    * @param callable|string  $handler          The handler function or controller in the format "Controller@method" for the route,
    *                                           or the file path for file-based route.
    * @param array            $routeMiddlewares The middlewares assigned to the route.
    * @param string|null      $name             The name of the route.
    * @throws Exception                         If the handler type is empty, or if the handler file or handler type is not found.
    */
    public function addRoute($methods, $uri, $handlertype = 'controller', $handler, $routeMiddlewares = [], $name = null, $csrfProtected = false) {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        if (empty($handlertype)) {
            throw new Exception("GM Router: Handler type is empty.");
        } elseif (!in_array($handlertype, ['controller', 'file', 'callback'])) {
            throw new Exception("GM Router: Invalid handler type '{$handlertype}'.");
        }

        if ($handlertype == 'file') {
            if (file_exists($handler)) {
                $handler = function () use ($handler) {
                    include $handler;
                };
            } else {
                throw new Exception("GM Router: Handler file '{$handler}' not found.");
            }
        }
        if ($handlertype == 'controller') {
            $handler = $this->resolveControllerHandler($handler);
        }

        $route = [
            'method' => $methods,
            'uri' => $uri,
			'handler' => $handler,
            'middlewares' => $this->resolveMiddlewares($routeMiddlewares),
			'csrf_protected' => $csrfProtected,
        ];

        if (!empty($name)) {
            $this->routeNames[$name] = $uri;
        }

        $this->routes[] = $route;
    }

    /**
     * Adds a new route group to the router.
     *
     * @param array        $middlewares An array of middleware names for the group.
     * @param callable     $callback    The callback function defining the routes within the group.
     * @param string       $prefix      An optional prefix for the group's routes.
     */
    public function addGroup($middlewares, $callback, $prefix = '') {
        $previousGroups = $this->groups;
        $this->groups = array_merge($previousGroups, $middlewares);

        $previousPrefix = $this->getGroupPrefix();
        $this->setGroupPrefix($previousPrefix . $prefix);

        $previousMiddlewareGroups = $this->middlewareGroups;
        foreach ($middlewares as $middleware) {
            if (isset($this->middlewareGroups[$middleware])) {
                $this->groups = array_merge($this->groups, $this->middlewareGroups[$middleware]);
            }
        }

        $callback();

        $this->groups = $previousGroups;
        $this->middlewareGroups = $previousMiddlewareGroups;
        $this->setGroupPrefix($previousPrefix);
    }

    /**
     * Adds a route redirect to the router.
     *
     * @param string       $from The source URI to redirect from.
     * @param string       $to   The target URI to redirect to.
     */
    public function addRedirect($methods, $from, $to) {
        $this->addRoute($methods, $from, function () use ($to) {
            header("Location: {$to}");
            exit;
        });
    }

    /**
     * Adds a middleware function to the router.
     *
     * @param string       $name     The name of the middleware.
     * @param callable     $callback The middleware function.
     */
    public function addMiddleware($name, $callback) {
        $this->middlewares[$name] = $callback;
    }

    /**
     * Adds a middleware group to the router.
     *
     * @param string       $name        The name of the middleware group.
     * @param array        $middlewares An array of middleware names for the group.
     */
    public function addMiddlewareGroup($name, $middlewares) {
        $this->middlewareGroups[$name] = $middlewares;
    }

    /**
     * Runs the router and dispatches the appropriate route handler.
     */
	public function dispatch($uri = null) {
		$method = $_SERVER['REQUEST_METHOD'];
		$uri = $uri ?? $_SERVER['REQUEST_URI'];

		$matched = false;
		$matchedRoute = null;

		$allowedMethods = [];

		foreach ($this->routes as $route) {
			if ($this->matchesUri($route['uri'], $uri)) {
				if (in_array('*', $route['method']) || in_array($method, $route['method'])) {
					$matchedRoute = $route;
					$matched = true;
					break;
				}
				$allowedMethods = array_merge($allowedMethods, $route['method']);
			}
		}

		if ($matched) {
			foreach ($matchedRoute['middlewares'] as $middleware) {
				call_user_func($middleware);
			}
			
			if ($matchedRoute['csrf_protected'] && !$this->validateCsrfToken()) {
				$this->handleError(434);
				exit;
			}

			call_user_func($matchedRoute['handler'], $this->matchedParameters);

			if(in_array(http_response_code(), array(401, 403, 404, 405, 434, 435, 436, 437, 500))) {
				$this->handleError(http_response_code());
				exit;
			}
		} else {
			if (!empty($allowedMethods)) {
				$this->handleError(405);
				exit;
			} else {
				$this->handleError(404);
				exit;
			}
		}
	}

	/**
	 * Validate the CSRF token retrieved from the request.
	 *
	 * @return bool True if the CSRF token is valid, false otherwise.
	 */
	private function validateCsrfToken() {
		$requestCsrfToken = $_POST['csrf_token'] ?? null;
		$storedCsrfToken = $_SESSION['csrf_token'] ?? null;

		if ($requestToken && $storedToken && hash_equals($requestToken, $storedToken)) {
			$this->regenerateCsrfToken();

			$expirationTime = $_SESSION['csrf_token_expiration'] ?? null;
			if ($expirationTime && time() > $expirationTime) {
				$this->handleError(435);
				exit;
			}

			$cookieToken = $_COOKIE['csrf_token'] ?? null;
			if (!$cookieToken || !hash_equals($cookieToken, $requestToken)) {
				$this->handleError(436);
				exit;
			}

			$referer = $_SERVER['HTTP_REFERER'] ?? null;
			$refererHost = parse_url($referer, PHP_URL_HOST);
			if (!$referer || !in_array($refererHost, $this->options['csrfAllowedReferers'])) {
				$this->handleError(437);
				exit;
			}

			return true;
		}

		return false;
	}
	
	/**
	 * Regenerates a new CSRF token and updates session and cookie values.
	 */
	private function regenerateCsrfToken() {
		$csrfToken = bin2hex(random_bytes(32));

		$_SESSION['csrf_token'] = $csrfToken;

		$expirationTime = time() + $this->options['csrfTokenExpireTime'];
		$_SESSION['csrf_token_expiration'] = $expirationTime;

		setcookie('csrf_token', $csrfToken, $expirationTime, '/', '', true, true);
	}
	
    /**
    * Resolves a controller handler into a callable.
    *
    * @param string $controllerHandler The controller handler in the format "Controller@method".
    *
    * @return callable The resolved handler callable.
    */
    private function resolveControllerHandler($controllerHandler) {
        [$controller, $method] = explode('@', $controllerHandler);

        $this->loadClass($this->options['controllersClassDir'] . '/' . $controller);

        return function () use ($controller, $method) {
			var_dump($parameters);
            $instance = new $controller();
            return $instance->$method();
        };
    }

    /**
     * Resolves the actual middleware functions for a given set of middleware names.
     *
     * @param array        $middlewares The middleware names to resolve.
     *
     * @return array The resolved middleware functions.
     */
    private function resolveMiddlewares($middlewares) {
        $resolvedMiddlewares = [];

        foreach ($middlewares as $middleware) {
            if (isset($this->middlewareGroups[$middleware])) {
                $resolvedMiddlewares = array_merge($resolvedMiddlewares, $this->middlewareGroups[$middleware]);
            } elseif (isset($this->middlewares[$middleware])) {
                $resolvedMiddlewares[] = $this->middlewares[$middleware];
            }
        }

        return array_merge($this->middlewares, $resolvedMiddlewares, $this->groups);
    }
	
    /**
     * Adds an HTTP error handler to the router.
     *
     * @param string|int    $statusCode The HTTP status code.
     * @param string|callable $handler   The error handler function or file path.
     * @throws Exception                If the handler type is invalid or the file is not found.
     */
    public function addErrorHandler($statusCode, $handler) {
        if (!is_string($statusCode) && !is_int($statusCode) && !in_array($statusCode, array(401, 403, 404, 405, 434, 435, 436, 437, 500))) {
            throw new Exception("GM Router: Invalid HTTP status code '{$statusCode}'.");
        }

        if (!is_string($handler) && !is_callable($handler)) {
            throw new Exception("GM Router: Invalid error handler for HTTP status code '{$statusCode}'.");
        }

        if (is_string($handler) && !file_exists($handler)) {
            throw new Exception("GM Router: Error handler file '{$handler}' not found.");
        }

        $this->errorHandlers[$statusCode] = $handler;
    }

	/**
	 * Handles the specified HTTP status code and executes the appropriate error handler, if available.
	 *
	 * @param int $statusCode The HTTP status code to handle.
	 */
    private function handleError($statusCode) {
		$errorHandler = isset($this->errorHandlers[$statusCode]) ? $this->errorHandlers[$statusCode] : null;
        if (is_callable($errorHandler)) {
            call_user_func($errorHandler);
        } elseif (is_string($errorHandler) && file_exists($errorHandler)) {
            include $errorHandler;
        } else {
			switch ($statusCode) {
				case 401:
					header("HTTP/1.0 401 Unauthorized");
					echo "401 Unauthorized";
				break;

				case 403:
					header("HTTP/1.0 403 Forbidden");
					echo "403 Forbidden";
				break;

				case 404:
					header("HTTP/1.0 404 Not Found");
					echo "404 Not Found";
				break;

				case 405:
					header("HTTP/1.0 405 Method Not Allowed");
					echo "405 Method Not Allowed";
				break;
					
				case 434:
					header("HTTP/1.0 434 CSRF Token Invalid");
					echo "434 CSRF Token Invalid";
				break;

				case 435:
					header("HTTP/1.0 435 CSRF Token Expired");
					echo "435 CSRF Token Expired";
				break;

				case 436:
					header("HTTP/1.0 436 CSRF Token Cookie Invalid");
					echo "436 CSRF Token Cookie Invalid";
				break;

				case 437:
					header("HTTP/1.0 437 CSRF Token Referer Invaild");
					echo "437 CSRF Token Referer Invaild";
				break;

				default:
					header("HTTP/1.0 500 Internal Server Error");
					echo "500 Internal Server Error";
				break;
			}
        }
    }

    /**
     * Generates the URL for a named route.
     *
     * @param string       $name   The name of the route.
     * @param array        $params An array of route parameters.
     *
     * @return string|null The generated URL or null if the route name is not found.
     */
    public function getUrl($name, $params = []) {
        if (isset($this->routeNames[$name])) {
            $url = $this->routeNames[$name];
            foreach ($params as $key => $value) {
                $url = str_replace("{{$key}}", $value, $url);
            }
            return $url;
        }

        return null;
    }

    /**
     * Matches a given URI against a route URI pattern.
     *
     * @param string       $routeUri    The URI pattern of the route.
     * @param string       $currentUri  The current URI to match.
     *
     * @return bool Whether the URI matches the route pattern.
     */
	private function matchesUri($routeUri, $currentUri) {
		$currentPath = parse_url($currentUri, PHP_URL_PATH);
		$pattern = str_replace('/', '\/', $routeUri);
		$pattern = preg_replace_callback('/\[(\w+)(?::(\([^\]]+\)))?\]/', function ($matches) {
			$paramName = $matches[1];
			$filterPattern = isset($matches[2]) ? $matches[2] : '([^\/]+)';
			return "(?P<$paramName>$filterPattern)";
		}, $pattern);
		$pattern = '/^' . $pattern . '$/';

		$matches = [];
		if (preg_match($pattern, $currentPath, $matches)) {
			$parameters = [];
			foreach ($matches as $key => $value) {
				if (is_string($key)) {
					$parameters[$key] = $value;
				}
			}
			$this->matchedParameters = $parameters;
			return true;
		}

		return false;
	}

    /**
     * Sets the prefix for the current route group.
     *
     * @param string       $prefix The prefix for the route group.
     */
    private function setGroupPrefix($prefix) {
        $_SERVER['GMROUTER_GROUP_PREFIX'] = $prefix;
    }

    /**
     * Gets the current prefix for the route group.
     *
     * @return string The current prefix for the route group.
     */
    private function getGroupPrefix() {
        return isset($_SERVER['GMROUTER_GROUP_PREFIX']) ? $_SERVER['GMROUTER_GROUP_PREFIX'] : '';
    }
}
