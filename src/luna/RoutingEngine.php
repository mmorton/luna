<?php

class LunaRoutingEngine implements ILunaRoutingEngine, ILunaContainerAware
{	
	private $container;
	private $defaultRouteType;
	
	private $routes = array();		
	
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
		foreach ($routeDefinitions as $definition) 
		{	
			$componentName = isset($definition["type"]) ? $definition["type"] : $this->defaultRouteType;

			if ($this->container->hasComponent($componentName))			
				$this->routes[] = $this->container->getComponent($componentName, false, array("definition" => $definition));			
		}
	}
	
	function find($urlInfo)
	{
		foreach ($this->routes as $route)					
			if (($routeMatch = $route->match($urlInfo)) !== false)
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