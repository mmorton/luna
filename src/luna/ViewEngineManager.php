<?php

class LunaViewEngineManager implements ILunaViewEngineManager, ILunaInitializable, ILunaContainerAware
{
	private $chain = array();
	private $engines;	
	private $container;
	
	public function __construct($engines) 
	{			
		$this->engines = $engines;		
	}
	
	function initialize()
	{								
		foreach ($this->engines as $engine)
			$this->registerViewEngine($this->container->getComponent($engine));				
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
			if ($viewEngine->hasTemplate($context, $templateName))
				return $viewEngine->renderTemplate($context, $templateName, $layoutName);
		}
		
		return "";
	}
}

?>