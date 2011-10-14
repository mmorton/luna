<?php

include_once "Container.php";



class LunaConfiguration implements ILunaConfiguration, ILunaInitializable
{
	private $config;
	private $active;
	private $environment;	
	
	public function __construct($config, $environment = "production", $forceInitialize = true)
	{
		$this->config = $config;
		$this->environment = $environment;
		
		if ($forceInitialize === true)
			$this->initialize();
	}
	
	public function initialize()
	{
		if (is_string($this->config))
			$this->config = json_decode(file_get_contents($this->config), true);
			
		if (isset($this->config[$this->environment]))
			$this->active =& $this->config[$this->environment];
		else
			$this->active =& $this->config;
	} 
	
	public function getSection($name)
	{		
		if (isset($this->active[$name]))
			return $this->active[$name];
		else					
			return false;
	}
}

?>