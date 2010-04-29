<?php

class LunaRouteMatch implements ILunaRouteMatch
{
	private $route;
	private $dispatcher;
	private $parameters;
	
	public function __construct($route, $dispatcher, $parameters)
	{
		$this->route = $route;
		$this->dispatcher = $dispatcher;
		$this->parameters = $parameters;
	}
	
	public function getRoute() { return $this->route; }
	public function getDispatcher() { return $this->dispatcher; }
	public function getParameters() { return $this->parameters; }
}

?>