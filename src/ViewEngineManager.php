<?php

class LunaViewEngineManager implements ILunaViewEngineManager, ILunaInitializable, ILunaContainerAware
{
	private $chain = array();
	private $viewEngineTypes;
    /**
     * @var $container ILunaContainer
     */
	private $container;
	
	public function __construct($viewEngineTypes)
	{			
		$this->viewEngineTypes = $viewEngineTypes;
	}
	
	function initialize()
	{								
		foreach ($this->viewEngineTypes as $viewEngineType)
			$this->registerViewEngine($this->container->getComponentFor($viewEngineType));
	}
	
	function setContainer($container)
	{
		$this->container = $container;
	}
	
	function registerViewEngine($viewEngine)
	{
		if (isset($viewEngine))
			$this->chain[] = $viewEngine;
	}
	
	function renderTemplate($context, $templateName, $layoutName = false)
	{	
		foreach ($this->chain as $viewEngine)
		{
            /**
             * @var $viewEngine ILunaViewEngine
             */
			if ($viewEngine->hasTemplate($context, $templateName))
				return $viewEngine->renderTemplate($context, $templateName, $layoutName);
		}
		
		return "";
	}
}

?>