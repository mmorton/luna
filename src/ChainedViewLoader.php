<?php

class LunaChainedViewLoader implements ILunaViewLoader, ILunaContainerAware, ILunaInitializable
{
    /**
     * @var $container ILunaContainer
     */
	private $container;
	private $viewLoaderTypes;
	private $loaderChain = array();
	
	public function __construct($viewLoaderTypes)
	{
		$this->viewLoaderTypes = $viewLoaderTypes;
	}
	
	public function setContainer($container)
	{
		$this->container = $container;
	}
		
	public function initialize()
	{		
		foreach ($this->viewLoaderTypes as $viewLoaderType)
			$this->loaderChain[] = $this->container->getComponentFor($viewLoaderType);
	}	
		
	public function hasTemplate($name)
	{
        /**
         * @var $loader ILunaViewLoader
         */
		foreach ($this->loaderChain as $loader)
			if ($loader->hasTemplate($name))
				return true;
		return false;
	}
	
	public function getTemplate($name)
	{
        /**
         * @var $loader ILunaViewLoader
         */ 
		foreach ($this->loaderChain as $loader)
			if ($loader->hasTemplate($name))
				return $loader->getTemplate($name);
		return false;
	}
}

?>