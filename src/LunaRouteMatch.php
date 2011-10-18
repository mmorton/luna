<?php

class LunaRouteMatch implements ILunaRouteMatch
{
	public $route;
	public $dispatcherType;
	public $parameters;
	
	public function __construct($route, $dispatcherType, $parameters)
	{
		$this->route = $route;
		$this->dispatcherType = $dispatcherType;
		$this->parameters = $parameters;
	}
	
	public function getRoute() { return $this->route; }
	public function getDispatcherType() { return $this->dispatcherType; }
	public function getParameters() { return $this->parameters; }
}

?>