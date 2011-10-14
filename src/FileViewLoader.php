<?php

class LunaFileViewLoader implements ILunaViewLoader
{	
	private $searchPaths = array();
	
	function __construct($viewRoot = false, ILunaConfiguration $configuration = null) 
	{
		if ($viewRoot === false && is_null($configuration) === false)
		{
			$settings = $configuration->getSection("settings");
			
			$this->searchPaths[] = $settings["viewRoot"];
			$this->searchPaths[] = $settings["layoutRoot"];
			$this->searchPaths[] = "/";
		}
		else
		{
			$this->viewRoot = $viewRoot;
		}
	}
	
	public function resolveTemplatePath($name)
	{		
		foreach ($this->searchPaths as $searchPath)
		{
			$testPath = LunaPathUtility::pathCombine(dirname($_SERVER["SCRIPT_FILENAME"]), LunaPathUtility::pathCombine($searchPath, $name));
			if (file_exists($testPath))
				return $testPath;
		}
		
		return false;
	}
		
	function hasTemplate($name)
	{
		if (is_string($name) === false)
			return false;
			
		return ($this->resolveTemplatePath($name) !== false);
	}
	
	function getTemplate($name)
	{
		if (is_string($name) === false)
			return "";
			
		return file_get_contents($this->resolveTemplatePath($name));
	}
}

?>