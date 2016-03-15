<?php
/**
 * Created by PhpStorm.
 * User: dennis
 * Date: 09.02.16
 * Time: 23:55
 */

namespace freefair\RestBundle\DependencyInjection;


use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RoutingLoader extends Loader
{
	/**
	 * @var Container
	 */
	private $container;
	private $loaded = false;

	public function __construct($container)
	{
		$this->container = $container;
	}

	/**
	 * Loads a resource.
	 *
	 * @param mixed $resource The resource
	 * @param string|null $type The resource type or null if unknown
	 *
	 * @return RouteCollection
	 * @throws \Exception If something went wrong
	 */
	public function load($resource, $type = null)
	{
		if (true === $this->loaded) {
			throw new \RuntimeException('Do not add the "extra" loader twice');
		}

		$parameter = $this->container->getParameter("rest.config");
		if(!$parameter["authentication"]["enabled"] || $parameter["authentication"]["oauth_type"] != "own") return new RouteCollection();

		$grant_url = $parameter["authentication"]["oauth"]["grant_url"];
		$token_url = $parameter["authentication"]["oauth"]["token_url"];

		$grant_controller = $parameter["authentication"]["oauth"]["grant_controller"];
		$token_controller = $parameter["authentication"]["oauth"]["token_controller"];

		if($grant_controller == null)
			throw new \RuntimeException("Grant controller not found");

		$routes = new RouteCollection();

		$this->addRoute($routes, $grant_url, $grant_controller);
		$this->addRoute($routes, $token_url, $token_controller);

		$this->loaded = true;

		return $routes;
	}

	/**
	 * Returns whether this class supports the given resource.
	 *
	 * @param mixed $resource A resource
	 * @param string|null $type The resource type or null if unknown
	 *
	 * @return bool True if this class supports the given resource, false otherwise
	 */
	public function supports($resource, $type = null)
	{
		return 'rest' === $type;
	}

	/**
	 * @param RouteCollection $routes
	 * @param string $url
	 * @param string $controller
	 */
	public function addRoute(RouteCollection $routes, $url, $controller)
	{
		// prepare a new route
		$defaults = array(
			'_controller' => $controller,
		);
		$requirements = array();
		$route = new Route($url, $defaults, $requirements);

		// add the new route to the route collection
		$routeName = 'rest_route_' . str_replace(':', '_', $controller);
		$routes->add($routeName, $route);
	}
}