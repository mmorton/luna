<?php

abstract class LunaViewEngine implements ILunaViewEngine
{
	protected $resolveCache = array();
	protected $viewLoader;
	protected $viewPaths = array("{area}/{controller}/{view}", "{controller}/{view}", "{view}");
		
	public function __construct(ILunaViewLoader $loader, $viewPaths = array()) 
	{			
		$this->viewLoader = $loader;						
		$this->viewPaths = $viewPaths;
	}
	
	protected function applyParameters($value, &$parameters)
	{
		$fragments = preg_split('/\{(.+?)\}/', $value, -1, PREG_SPLIT_DELIM_CAPTURE);		
		for ($i = 1; $i < count($fragments); $i += 2)
		{
			$name = $fragments[$i];
			if (isset($parameters[$name]) == false)				
				return false;
						
			$fragments[$i] = $parameters[$name];
		}		
		return implode("", $fragments);
	}
	
	protected function resolveTemplate($context, $name)
	{
		if (is_string($name) === false)
			return false;
			
		if (isset($this->resolveCache[$name]))
			return $this->resolveCache[$name];
		
		$parameters = array_merge($context->urlInfo->getCustomProperties(), array('view' => $name));
		
		foreach ($this->viewPaths as $viewPath)
		{
			$viewPath = $this->applyParameters($viewPath, $parameters);
			if ($viewPath === false)
				continue;
			
			if ($this->viewLoader->hasTemplate($viewPath))							
				return ($this->resolveCache[$name] = $viewPath);			
		}	
		
		return false;
	}
		
	public function hasTemplate($context, $name)
	{					
		return ($this->resolveTemplate($context, $name) !== false);
	}	
	
	public function renderTemplate($context, $templateName, $layoutName = false) 
	{	
		$templatePath = $this->resolveTemplate($context, $templateName);
		$layoutPath = $this->resolveTemplate($context, $layoutName);
		
		$template = $this->viewLoader->getTemplate($templatePath);
		$layout = $this->viewLoader->getTemplate($layoutPath);				
		
		return $this->processTemplate($context, $template, $layout);
	}
	
	protected abstract function processTemplate($context, $template, $layout);
}

?>