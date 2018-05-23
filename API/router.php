<?php

require_once('route.php');

class Router
{
	private $_routes;
	private $_matchingRoutes;
	private $_defaultRoute;
	
	function __construct() {
		$this->_routes = array();

		$this->_matchingRoutes = array();

		$this->_defaultRoute = NULL;
	}

	public function addRoute() {
		$args = func_get_args();
		array_push($this->_routes, new Route($args));
	}

	public function setDefaultRoute($route) {
		$this->_defaultRoute = $route;
	}

	public function run($method, $URI) {
		$this->_findMatchingMethod($this->_routes, $method);
		$this->_findMatchingPattern($this->_matchingRoutes, $URI);

		if (count($this->_matchingRoutes) == 0) {
			if (!is_null($this->_matchingRoutes)) {
                header('Location: ' . $this->_defaultRoute);
            }
		} else {
			foreach ($this->_matchingRoutes as $route) {
				$route->run();
			}
		}
	}

	private function _findMatchingMethod($routes, $method) {
		foreach ($routes as $route) {
			if ($route->methodMatches($method))
				array_push($this->_matchingRoutes, $route);
		}
	}

	private function _findMatchingPattern($routes, $URI) {
		$this->_matchingRoutes = array();
		foreach ($routes as $route) {
			if ($route->patternMatches($URI)) {
                array_push($this->_matchingRoutes, $route);
            }
		}
	}
}