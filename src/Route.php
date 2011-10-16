<?php

class LunaRoute implements ILunaRoute
{
	protected static $preMatchRequire = array("host" => true, "port" => true, "method" => true);
	
	protected $definition;
    
	public $for = array();
	public $defaults = array();
	public $parameters = array();
	public $requires = array();
	public $dispatcherType;
	
	public function __construct($defaultDispatcherType, $definition)
	{
		$this->dispatcherType = $defaultDispatcherType;
		$this->definition = $definition;
		
		if (isset($this->definition["for"]))
		{			
			if (is_array($this->definition["for"]))
			{
				foreach ($this->definition["for"] as $expression)
					$this->for[] = LunaRouteExpression::parse($expression);
			}
			else				
				$this->for[] = LunaRouteExpression::parse($this->definition["for"]);
		}				
		
		if (isset($this->definition["requires"]))
			$this->requires = $this->definition["requires"];
			
		if (isset($this->definition["defaults"]))
			$this->defaults = $this->definition["defaults"];
			
		if (isset($this->definition["dispatcherType"]))
			$this->dispatcherType = $this->definition["dispatcherType"];
			
		if (isset($this->definition["parameters"]))
			$this->parameters = $this->definition["parameters"];
	}
	
	public function match($request)
	{
		/* early out for require statements that do not rely on expression */
		foreach (array_keys(self::$preMatchRequire) as $key)
		{
			if (isset($this->requires[$key]))
				if (preg_match($this->requires[$key], $request->$key) !== 1)
					return false;
		}		
				
		foreach ($this->for as $for)
		{
            /**
             * @var $for LunaRouteExpression
             */
			$parameters = $for->match($request->path, $this->defaults);
			
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
				
			return new LunaRouteMatch($this, $this->dispatcherType, array_merge($parameters, $this->parameters));
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
            /** @var $for LunaRouteExpression */
			if (($result = $for->reverse($parameters, $this->defaults, $this->parameters)) !== false)
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