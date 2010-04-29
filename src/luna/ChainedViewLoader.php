<?php

class LunaChainedViewLoader implements ILunaViewLoader, ILunaContainerAware, ILunaInitializable
{
	private $container;
	private $loaders;
	private $loaderChain = array();
	
	public function __construct($loaders)
	{
		$this->loaders = $loaders;
	}
	
	public function setContainer($container)
	{
		$this->container = $container;
	}
		
	public function initialize()
	{		
		foreach ($this->loaders as $name)
			$this->loaderChain[] = $this->container->getComponent($name);		
	}	
		
	public function hasTemplate($name)
	{
		foreach ($this->loaderChain as $loader)
			if ($loader->hasTemplate($name))
				return true;
		return false;
	}
	
	public function getTemplate($name)
	{
		foreach ($this->loaderChain as $loader)
			if ($loader->hasTemplate($name))
				return $loader->getTemplate($name);
		return false;
	}
}

?>