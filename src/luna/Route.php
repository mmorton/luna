<?php

class LunaRoute implements ILunaRoute, ILunaContainerAware
{
	private static $preMatchRequire = array("host" => true, "port" => true, "method" => true);
	private static $pathSeparator = "/";
	
	protected $config;	
	protected $for = array();	
	protected $defaults = array();
	protected $parameters = array();
	protected $requires = array();
	protected $dispatcher;
	protected $container;
	
	public function __construct($dispatcher, $definition)
	{
		$this->dispatcher = $dispatcher;
		$this->config = $definition;
		
		if (isset($this->config["for"]))
		{			
			if (is_array($this->config["for"]))
			{
				foreach ($this->config["for"] as $expression)
					$this->for[] = LunaRouteExpression::parse($expression);
			}
			else				
				$this->for[] = LunaRouteExpression::parse($this->config["for"]);			
		}				
		
		if (isset($this->config["requires"]))
			$this->requires = $this->config["requires"];
			
		if (isset($this->config["defaults"]))
			$this->defaults = $this->config["defaults"];
			
		if (isset($this->config["dispatcher"]))
			$this->dispatcher = $this->config["dispatcher"];	
			
		if (isset($this->config["parameters"]))
			$this->parameters = $this->config["parameters"];						
	}
	
	public function setContainer($container)
	{
		$this->container = $container;
	}
	
	public function match($path)
	{
		/* early out for require statements that do not rely on expression */
		foreach (array_keys(self::$preMatchRequire) as $key)
		{
			if (isset($this->requires[$key]))
				if (preg_match($this->requires[$key], $urlInfo->$key) !== 1)
					return false;
		}		
				
		foreach ($this->for as $for)
		{	
			$parameters = $for->match($path, $this->defaults);
			
			if ($parameters === false)
				continue;
			
			$requirementsPassed = true;	
			foreach ($this->requires as $name => $regex)
			{
				if (isset($parameters[$name]) == false)
				{
					$requirementsPassed = false;
					break;
				}
				
				if (preg_match($regex, $parameters[$name]) !== 1)
				{
					$requirementsPassed = false;
					break;
				}
			}
			
			if ($requirementsPassed === false)
				continue;
				
			return new LunaRouteMatch($this, $this->dispatcher, array_merge($parameters, $this->parameters));							
		}
		
		return false;
	}	
	
	public function reverse($parameters)
	{
		$requirementsPassed = true;	
		foreach ($this->requires as $name => $regex)
		{
			if (isset($parameters[$name]) == false)
			{
				$requirementsPassed = false;
				break;
			}
			
			if (preg_match($regex, $parameters[$name]) !== 1)
			{
				$requirementsPassed = false;
				break;
			}
		}
		
		if ($requirementsPassed === false)
			return false;
				
		foreach ($this->for as $for)
		{			
			if (($result = $for->reverse($parameters, $this->defaults)) !== false)
			{
				if (strlen($result) > 0 && $result[0] !== '/')
					$result = '/'.$result;
				return $result;
			}
		}
		
		return false;
	}
}

?>