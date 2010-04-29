<?php

/**
 * A service container capable of resolving configuration parameters to constructor parameters by name.
 * 
 * Service Configuration:
 * "<<type_name>>" 
 * or
 * ["<<class_name>>", "<<class_file_name>>"]
 * or
 * type: (any of the above options)
 * singleton: (true|false) - true is the default
 * parameters:
 *   one: <<any value>> - matches constructor parameter '$one'.
 *   two: '${<<service_name>>}' - any defined service name, matches constructor parameter '$two'.
 */

class LunaContainer implements ILunaContainer
{
	private $parent = null;
	private $componentDefinitions = array();
	private $componentInstances = array();
	private $serviceToName = array();
	
	public function __construct($parent = null)
	{
		$this->parent = $parent;
	}
	
	public function __get($name) 
	{
		return $this->getComponent($name);
	}
	
	public function __set($name, $value)
	{
		$this->addComponent($name, $value);
	}
	
	public function __isset($name)
	{
		return $this->hasComponent($name);
	}
	
	public function __unset($name)
	{
		$this->removeComponent($name);
	}	
	
	public function getComponentNames()
	{
		return array_keys($this->componentDefinitions);
	}	
	
	public function removeComponent($name) 
	{
		if (isset($this->componentDefinitions[$name]))
		{
			$service = $this->componentDefinitions[$name]->service;
			
			unset($this->serviceToName[$service]);
			unset($this->componentDefinitions[$name]);
			unset($this->componentInstances[$name]);		
		}
	}
	
	public function addComponent($name, $definition, $instance = null)
	{				
		if ($definition instanceof LunaComponentDefinition)
		{						
			if (isset($definition->class) == false)
				$definition->class = LunaTypeUtility::loadType($definition->classType);
			if (isset($definition->typeReflect) == false)
				$definition->classReflect = new ReflectionClass($definition->class);
			if (isset($definition->service) == false)
				$definition->service = LunaTypeUtility::loadType($definition->serviceType);
				
			$this->componentDefinitions[$name] = $definition;
			$this->serviceToName[$definition->service] = $name;
		}		
		elseif (is_array($definition) && (isset($definition["classType"]) || isset($definition["type"])))
		{						
			$config = $definition;
			$definition = new LunaComponentDefinition();
			$definition->isSingleton = (isset($config["singleton"]) && is_bool($config["singleton"])) ? $config["singleton"] : true;
			$definition->classType = isset($config["classType"]) ? $config["classType"] : $config["type"];			
			$definition->class = LunaTypeUtility::loadType($definition->classType);
			$definition->classReflect = new ReflectionClass($definition->class);
			$definition->serviceType = isset($config["serviceType"]) ? $config["serviceType"] : $definition->classType;			
			$definition->service = LunaTypeUtility::loadType($definition->serviceType);			
			$definition->create = (isset($config["create"]) && is_bool($config["create"])) ? $config["create"] : false;
			
			if (isset($config["parameters"]))
				$definition->parameters = $config["parameters"];
			
			$this->componentDefinitions[$name] = $definition;				
			$this->serviceToName[$definition->service] = $name;
		}
		elseif (is_array($definition))
		{
			$type = $definition;
			$definition = new LunaComponentDefinition();
			$definition->isSingleton = true;
			$definition->classType = $type;
			$definition->serviceType = $type;
			$definition->class = LunaTypeUtility::loadType($definition->classType);
			$definition->classReflect = new ReflectionClass($definition->class);
			$definition->serviceType = isset($config["serviceType"]) ? $config["serviceType"] : $definition->classType;			
			$definition->service = LunaTypeUtility::loadType($definition->serviceType);			
			
			$this->componentDefinitions[$name] = $definition;
			$this->serviceToName[$definition->service] = $name;
		}
		elseif (is_string($definition))
		{
			$type = $definition;
			$definition = new LunaComponentDefinition();
			$definition->isSingleton = true;
			$definition->classType = $type;
			$definition->serviceType = $type;
			$definition->class = LunaTypeUtility::loadType($definition->classType);
			$definition->classReflect = new ReflectionClass($definition->class);
			$definition->serviceType = isset($config["serviceType"]) ? $config["serviceType"] : $definition->classType;			
			$definition->service = LunaTypeUtility::loadType($definition->serviceType);	
			
			$this->componentDefinitions[$name] = $definition;	
			$this->serviceToName[$definition->service] = $name;
		}
		elseif (is_object($definition))
		{
			$instance = $definition;
			$definition = new LunaComponentDefinition();
			$definition->isSingleton = true;
			$definition->class = get_class($instance);
			$definition->service = get_class($instance);
						
			$this->componentDefinitions[$name] = $definition;			
			$this->componentInstances[$name] = $instance;
			$this->serviceToName[$definition->service] = $name;
		}
		
		if ($definition->isSingleton && $definition->create && is_null($instance))
			$instance = $this->createInstance($definition);
		
		if (is_null($instance) == false)
			$this->componentInstances[$name] = $instance;
	}
	
	protected function resolveParameters($reflectMethod, $componentDefinition, &$optionalParameters = null)
	{
		$resolvedParameters = array();		
				
		foreach ($reflectMethod->getParameters() as $i => $param)
		{
			$paramName = $param->getName();	
			$paramClass = is_null($param->getClass()) == false ? $param->getClass()->getName() : false;		

			if (is_array($componentDefinition->parameters) && isset($componentDefinition->parameters[$paramName]))
			{
				/* resolve service reference */
				if (is_string($componentDefinition->parameters[$paramName]) && preg_match('/\$\{(\w+)\}/', $componentDefinition->parameters[$paramName], $match))
					$resolvedParameters[$i] = $this->getComponent($match[1]);
				else
					$resolvedParameters[$i] = $componentDefinition->parameters[$paramName];
			}
			elseif (is_array($optionalParameters) && isset($optionalParameters[$paramName]))
			{
				$resolvedParameters[$i] = $optionalParameters[$paramName];
			}
			elseif (is_string($paramClass) && $this->hasComponentFor($paramClass))
			{				
				$resolvedParameters[$i] = $this->getComponentFor($paramClass);
			}
			elseif ($this->hasComponent($paramName)) 
			{
				$resolvedParameters[$i] = $this->getComponent($paramName);
			}
			elseif ($paramName == "componentContainer" || $paramClass == "ILunaContainer") 
			{
				$resolvedParameters[$i] = $this; /* new LunaContainer($this); */ /* set a new scoped container? */
			}
			elseif ($param->isDefaultValueAvailable())
			{
				$resolvedParameters[$i] = $param->getDefaultValue(); 
			}				
		}

		return $resolvedParameters;
	}
	
	protected function createInstance($componentDefinition, &$optionalParameters = null)
	{					
		$ctor = $componentDefinition->classReflect->getConstructor();

		if (isset($ctor))
			$instance = $componentDefinition->classReflect->newInstanceArgs($this->resolveParameters($ctor, $componentDefinition, $optionalParameters));
		else
			$instance = $componentDefinition->classReflect->newInstance();
			
		if ($instance instanceof ILunaContainerAware)
			$instance->setContainer($this); /* (new LunaContainer($this)); */ /* set a new scoped container? */
			
		if ($instance instanceof ILunaInitializable)
			$instance->initialize();
			
		return $instance;						
	}
	
	public function getComponent($name, $localOnly = false, $optionalParameters = null)
	{				
		if (isset($this->componentDefinitions[$name]) == false)
			if ($localOnly === false && isset($this->parent))
				return $this->parent->getComponent($name, false, $optionalParameters);
			else
				return null;
					
		$definition =& $this->componentDefinitions[$name];				
			
		if ($definition->isSingleton)
		{
			if (isset($this->componentInstances[$name]) == false)							
				$this->componentInstances[$name] = $this->createInstance($definition); /* should singleton services allow optional parameters? */			
				
			return $this->componentInstances[$name];			
		}					
		
		return $this->createInstance($definition, $optionalParameters);
	}
	
	public function getComponentFor($service, $localOnly = false, $optionalParameters = null)
	{
		if (isset($this->serviceToName[$service]) == false)
			if ($localOnly === false && isset($this->parent))
				return $this->parent->getComponentFor($service, false, $optionalParameters);
			else
				return null;						
				
		$name = $this->serviceToName[$service];
		$definition =& $this->componentDefinitions[$name];				
			
		if ($definition->isSingleton)
		{
			if (isset($this->componentInstances[$name]) == false)							
				$this->componentInstances[$name] = $this->createInstance($definition); /* should singleton services allow optional parameters? */			
				
			return $this->componentInstances[$name];			
		}					
		
		return $this->createInstance($definition, $optionalParameters);
	}
	
	public function hasComponent($name, $localOnly = false)
	{
		if (isset($this->componentDefinitions[$name]) == false)
			if ($localOnly === false && isset($this->parent))
				return $this->parent->hasComponent($name);
			else
				return false;
			
		return true;
	}
	
	public function hasComponentFor($service, $localOnly = false)
	{
		if (isset($this->serviceToName[$service]) == false)
			if ($localOnly === false && isset($this->parent))
				return $this->parent->hasComponentFor($service);
			else
				return false;
			
		return true;
	}
}

?>