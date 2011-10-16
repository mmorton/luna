<?php

class LunaRoutingEngine implements ILunaRoutingEngine, ILunaContainerAware
{
    /**
     * @var $container ILunaContainer
     */
	private $container;

	public $defaultRouteType;
	public $routes = array();
	
	function __construct($defaultRouteType)
	{		
		$this->defaultRouteType = $defaultRouteType;
	}
	
	function setContainer($container)
	{
		$this->container = $container;
	}
		
	function load($routeDefinitions) 
	{
		foreach ($routeDefinitions as $definition) $this->add($definition);
	}

    function add($route, $index = -1)
    {
        if ($route instanceof ILunaRoute)
            array_splice($this->routes, $index, 0, array($route));
        else
        {
            $routeType = isset($route["type"]) ? $route["type"] : $this->defaultRouteType;
            $routeInstance = $this->container->getComponentFor($routeType, false, array("definition" => $route));
            if ($routeInstance) array_splice($this->routes, $index, 0, array($routeInstance));
        }
    }

    function remove($index)
    {
        array_splice($this->routes, $index, 1);
    }
       
	function find($request)
	{
		foreach ($this->routes as $route)					
			if (($routeMatch = $route->match($request)) !== false)
				return $routeMatch;
		return false;
	}
	
	function reverse($parameters)
	{
		foreach ($this->routes as $route)
			if (($result = $route->reverse($parameters)) !== false)
				return $result;
		return false;
	}
}

?>