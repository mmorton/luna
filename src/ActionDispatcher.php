<?php

class LunaActionDispatcher implements ILunaActionDispatcher, ILunaInitializable, ILunaContainerAware
{
    /**
     * @var $container ILunaContainer
     */
	protected $container;
	protected $appRoot;
	protected $parameters = array();
	protected $controller;
	protected $controllerClass;
	protected $controllerReflect;
	protected $controllerRoot;
	protected $controllerPaths = array("{area}/{controller}Controller.php", "{controller}Controller.php");
	protected $controllerNames = array("{area}_{controller}Controller", "{controller}Controller");	
	
	protected $hasPreAction = false;	
	protected $hasPostAction = false;
		
	public function __construct($context, $controllerRoot = false, $controllerPaths = false, $controllerNames = false, ILunaConfiguration $configuration = null)
	{	
		$this->appRoot = $context->appRoot;
		$this->controller = $context->request->controller;
		$this->parameters =& $context->request->getCustomProperties();
		
		if ($controllerRoot === false && is_null($configuration) === false)
		{
			$settings = $configuration->getSection("settings");
			$this->controllerRoot = $settings["controllerRoot"];
		}
		else
		{
			$this->controllerRoot = $controllerRoot;
		}
		
		if ($controllerPaths !== false)
			$this->controllerPaths = $controllerPaths;
			
		if ($controllerNames !== false)
			$this->controllerNames = $controllerNames;
		
		if (isset($context->request->controllerPaths))
			$this->controllerPaths = array_merge($this->controllerPaths, 
				is_array($context->request->controllerPaths)
					? $context->request->controllerPaths
					: array($context->request->controllerPaths));
		
		if (isset($context->request->controllerNames))
			$this->controllerNames = array_merge($this->controllerNames, 
				is_array($context->request->controllerNames)
					? $context->request->controllerNames
					: array($context->request->controllerNames));
	}
	
	public function setContainer($container)
	{
		$this->container = $container;
	}
	
	protected function isValidPathSegment($segment)
	{
		return (preg_match("/^[\\w-]+$/", $segment) === 1);
	}
	
	protected function applyParameters($value, &$parameters)
	{
		$fragments = preg_split('/\{(.+?)\}/', $value, -1, PREG_SPLIT_DELIM_CAPTURE);		
		for ($i = 1; $i < count($fragments); $i += 2)
		{
			$name = $fragments[$i];
			if (isset($parameters[$name]) == false)				
				return false;
				
			$parts = explode("_", $parameters[$name]);
			for ($j = 0; $j < count($parts); $j++)
				$parts[$j] = ucfirst($parts[$j]);
			
			$fragments[$i] = implode("_", $parts);
		}		
		return implode("", $fragments);
	}
			
	public function initialize()
	{		
		if (!isset($this->controller) || strlen($this->controller) == 0)
			throw new Exception("Cannot create action dispatcher.  No controller specified.");					
		
		$controllerRoot = LunaPathUtility::pathCombine($this->appRoot, $this->controllerRoot);						
				
		$controllerName = false;		
		foreach ($this->controllerNames as $controllerName)
		{
			$controllerName = $this->applyParameters($controllerName, $this->parameters);
			if ($controllerName === false)
				continue;
			
			if (class_exists($controllerName))							
				break;
			else
				$controllerName = false;
		}				
		
		if ($controllerName === false)
		{	
			$searched = array();			
			$controllerPath = false;
			foreach ($this->controllerPaths as $controllerPath)
			{
				$searched []= $controllerPath;
				
				$controllerPath = $this->applyParameters($controllerPath, $this->parameters);
				if ($controllerPath === false)
					continue;								
								
				$controllerPath = LunaPathUtility::pathCombine($controllerRoot, $controllerPath);

				if (file_exists($controllerPath))			
					break;
				else
					$controllerPath = false;			
			}	
			
			if ($controllerPath === false)
				throw new Exception(sprintf("Cannot initialize dispatcher.  Could not locate include for controller. Searched: %s.", implode(",", $searched)));
				
			include_once($controllerPath);				
			
			$searched = array();	
			$controllerName = false;		
			foreach ($this->controllerNames as $controllerName)
			{
				$searched []= $controllerName;
				
				$controllerName = $this->applyParameters($controllerName, $this->parameters);
				if ($controllerName === false)
					continue;
					
				if (class_exists($controllerName))							
					break;
				else
					$controllerName = false;
			}
			
			if ($controllerName === false)
				throw new Exception(sprintf("Cannot initialize dispatcher.  Could not locate class for controller. Searched: %s.", implode(",", $searched)));
		}
		
		$this->controllerReflect = new ReflectionClass($controllerName);
		$this->hasPreAction = $this->controllerReflect->hasMethod("preAction");				
		$this->hasPostAction = $this->controllerReflect->hasMethod("postAction");					
	}
	
	protected function actionToMethodName($action)
	{
		$parts = explode('_', $action);
		for ($i = 1; $i < count($parts); $i++)
			$parts[$i] = ucfirst($parts[$i]);
		return implode('', $parts);
	}
	
	public function canDispatch($context)
	{
		if (strcasecmp($context->request->action, "preAction") === 0 ||
			strcasecmp($context->request->action, "postAction") === 0)
			return false;
			
		if ($this->controllerReflect->implementsInterface("ILunaContainerAware"))
			if (strcasecmp($context->request->action, "setContainer") === 0)
				return false;

        if ($this->controllerReflect->implementsInterface("ILunaContextAware"))
            if (strcasecmp($context->request->action, "setContext") === 0)
				return false;
				
		if ($this->controllerReflect->implementsInterface("ILunaInitializable"))
			if (strcasecmp($context->request->action, "initialize") === 0)
				return false;
		
		$methodName = $this->actionToMethodName($context->request->action);
		
		if (isset($methodName) && strlen($methodName) > 0)
		{	
			if ($this->controllerReflect->hasMethod($methodName))
			{
				$method = $this->controllerReflect->getMethod($methodName);
				if ($method->isConstructor() == true || 
					$method->isDestructor() == true || 
					$method->isPublic() == false)
					return false;
				
				return true;
			}			
		}
		
		return false;
	}
	
	protected function resolveParameters($reflectMethod, $context)
	{
        $optionalParameters = array_merge($context->request->getCustomProperties(), $context->request->query);

        return $this->container->getParametersFor(null, $reflectMethod, $optionalParameters);
	}
	
	protected function createControllerInstance($context)
	{
		$ctor = $this->controllerReflect->getConstructor();	
		
		if (isset($ctor))				
			$instance = $this->controllerReflect->newInstanceArgs($this->resolveParameters($ctor, $context));	
		else
			$instance = $this->controllerReflect->newInstance();

        if ($instance instanceof ILunaContainerAware)
			$instance->setContainer($this->container);

        if ($instance instanceof ILunaContextAware)
            $instance->setContext($context);
			
		if ($instance instanceof ILunaInitializable)
			$instance->initialize();
			
		return $instance;
	}
	
	public function dispatch($context)
	{			
		$action = $context->request->action;
		
		$context->view->selectedLayout = "default"; /* can be overridden in controller constructor */
		$context->view->selectedView = $action;			
		
		$methodName = $this->actionToMethodName($action);
		
		$actionMethod = $this->controllerReflect->getMethod($methodName);
		$controllerInstance = $this->createControllerInstance($context);
							
		if ($this->hasPreAction)
		{	
			if ($controllerInstance->preAction($context, $action, $result) === false)
				return $result;
		}
			
		$actionResult = $actionMethod->invokeArgs($controllerInstance, $this->resolveParameters($actionMethod, $context));
		
		if ($this->hasPostAction)
			$controllerInstance->postAction($context, $action);
		
		return $actionResult;
	}
}


?>