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
    /**
     * @var $parent LunaContainer
     */
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

    /**
     * @param $name
     * @param $definition A key/value array, an indexed array, an object instance, or a LunaComponentDefinition, describing the component.
     * @param null $instance
     * @return void
     *
     * Definition Properties:
     * * `serviceType`: The service type (dependency type) of the component.  Can either be a string or an array.  If this parameter
     *     is omitted, `serviceType` is set to `classType`.
     *   * `string`: The class name only, or a string containing the class name and the relative file path, i.e.: `class name, file`.
     *   * `array`: Must at least contain the class name at #0; #1 may contain the relative file path.
     *
     * * `classType`: The implementation type of the component.  Can either be a string or an array, like `serviceType`.  This
     *     parameter is required.
     *
     * * `type`: Same as `classType`.
     *
     * * `singleton`: True if the component is a singleton, False otherwise.  By default, this value is false, except when the `$definition`
     *     parameter is an object instance.
     *
     * * `create`: True if the singleton component should be created immediately, False otherwise.
     *
     * * `parameters`: An optional dictionary of named parameters for this component.
     */
	public function addComponent($name, $definition, $instance = null)
	{				
		if ($definition instanceof LunaComponentDefinition)
		{						
			if (isset($definition->class) == false)
				$definition->class = LunaTypeUtility::loadType($definition->classType);
			if (isset($definition->classReflect) == false)
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
			$definition->isSingleton = isset($config["singleton"]) ? $config["singleton"] : false;
			$definition->classType = isset($config["classType"]) ? $config["classType"] : $config["type"];			
			$definition->class = LunaTypeUtility::loadType($definition->classType);
			$definition->classReflect = new ReflectionClass($definition->class);
			$definition->serviceType = isset($config["serviceType"]) ? $config["serviceType"] : $definition->classType;			
			$definition->service = LunaTypeUtility::loadType($definition->serviceType);			
			$definition->create = isset($config["create"]) ? $config["create"] : false;
			
			if (isset($config["parameters"]))
				$definition->parameters = $config["parameters"];
			
			$this->componentDefinitions[$name] = $definition;				
			$this->serviceToName[$definition->service] = $name;
		}
		elseif (is_array($definition))
		{
			$type = $definition;
			$definition = new LunaComponentDefinition();
			$definition->isSingleton = false;
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
			$definition->isSingleton = false;
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
			$instance = $this->createComponentInstance($definition);
		
		if (is_null($instance) == false)
			$this->componentInstances[$name] = $instance;
	}

    /**
     * @param $function ReflectionFunction
     * @param null $componentParameters
     * @param null $optionalParameters
     * @param $root LunaContainer
     * @return array
     */
    protected function resolveParametersForFunction($function, &$componentParameters, &$optionalParameters, LunaContainer $root = null)
    {
        $resolvedParameters = array();

		foreach ($function->getParameters() as $i => $parameter)
		{
            /** @var $parameter ReflectionParameter */
			$parameterName = $parameter->getName();
			$parameterClass = is_null($parameter->getClass()) == false ? $parameter->getClass()->getName() : false;

            if (is_string($parameterClass) && $this->hasComponentFor($parameterClass))
			{
				$resolvedParameters[$i] = $this->getComponentFor($parameterClass);
			}
            elseif ($parameterClass == "ILunaContainer")
			{
				$resolvedParameters[$i] = isset($root) ? $root : $this; /* new LunaContainer($this); */ /* set a new scoped container? */
			}
            elseif (is_array($optionalParameters) && isset($optionalParameters[$parameterName]))
			{
				$resolvedParameters[$i] = $optionalParameters[$parameterName];
			}
			elseif (is_array($componentParameters) && isset($componentParameters[$parameterName]))
			{
				/* resolve service reference */
				if (is_string($componentParameters[$parameterName]) && preg_match('/\$\{(\w+)\}/', $componentParameters[$parameterName], $match))
					$resolvedParameters[$i] = $this->getComponent($match[1]);
				else
					$resolvedParameters[$i] = $componentParameters[$parameterName];
			}
			elseif ($this->hasComponent($parameterName))
			{
				$resolvedParameters[$i] = $this->getComponent($parameterName);
			}
			elseif ($parameterName == "componentContainer")
			{
				$resolvedParameters[$i] = isset($root) ? $root : $this; /* new LunaContainer($this); */ /* set a new scoped container? */
			}
			elseif ($parameter->isDefaultValueAvailable())
			{
				$resolvedParameters[$i] = $parameter->getDefaultValue();
			}
		}

		return $resolvedParameters;
    }
	
	public function getParametersFor($instance, $function, $optionalParameters = null)
    {
        $functionReflect = null;
        $componentParameters = null;

        if ($function instanceof ReflectionFunctionAbstract)
        {
            $functionReflect = $function;
        }
        elseif (is_string($function))
        {
            if (isset($instance))
            {
                $instanceReflect = new ReflectionClass($instance);
                $functionReflect = $instanceReflect->getMethod($function);
            }
            else
            {
                $functionReflect = new ReflectionFunction($function);
            }
        }

        return $this->resolveParametersForFunction($functionReflect, $componentParameters, $optionalParameters);
    }
	
	protected function createComponentInstance($componentDefinition, &$optionalParameters = null, LunaContainer $root = null)
	{					
		$ctor = $componentDefinition->classReflect->getConstructor();

		if (isset($ctor))
			$instance = $componentDefinition->classReflect->newInstanceArgs($this->resolveParametersForFunction($ctor, $componentDefinition->parameters, $optionalParameters, $root));
		else
			$instance = $componentDefinition->classReflect->newInstance();
			
		if ($instance instanceof ILunaContainerAware)
			$instance->setContainer(isset($root) ? $root : $this); /* (new LunaContainer($this)); */ /* set a new scoped container? */
			
		if ($instance instanceof ILunaInitializable)
			$instance->initialize();
			
		return $instance;						
	}
	
	public function getComponent($name, $localOnly = false, $optionalParameters = null, LunaContainer $root = null)
	{				
		if (isset($this->componentDefinitions[$name]) == false)
			if ($localOnly === false && isset($this->parent))
				return $this->parent->getComponent($name, false, $optionalParameters, isset($root) ? $root : $this);
			else
				return null;
					
		$definition =& $this->componentDefinitions[$name];				
			
		if ($definition->isSingleton)
		{
			if (isset($this->componentInstances[$name]) == false)							
				$this->componentInstances[$name] = $this->createComponentInstance($definition); /* should singleton services allow optional parameters? */
				
			return $this->componentInstances[$name];			
		}					
		
		return $this->createComponentInstance($definition, $optionalParameters, $root);
	}
	
	public function getComponentFor($service, $localOnly = false, $optionalParameters = null, ILunaContainer $root = null)
	{
		if (isset($this->serviceToName[$service]) == false)
			if ($localOnly === false && isset($this->parent))
				return $this->parent->getComponentFor($service, false, $optionalParameters, isset($root) ? $root : $this);
			else
				return null;						
				
		$name = $this->serviceToName[$service];
		$definition =& $this->componentDefinitions[$name];				
			
		if ($definition->isSingleton)
		{
			if (isset($this->componentInstances[$name]) == false)							
				$this->componentInstances[$name] = $this->createComponentInstance($definition); /* should singleton services allow optional parameters? */
				
			return $this->componentInstances[$name];			
		}					
		
		return $this->createComponentInstance($definition, $optionalParameters, $root);
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