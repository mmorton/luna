<?php

class LunaContextFactory implements ILunaContextFactory
{	
	private $appUriRoot;
	
	function __construct($appUriRoot = false, ILunaConfiguration $configuration = null)
	{		
		if ($appUriRoot === false && is_null($configuration) === false)
		{
			$settings = $configuration->getSection("settings");
			if (isset($settings["appUriRoot"]))
				$this->appUriRoot = $settings["appUriRoot"];
			else
				$this->appUriRoot = false;
		}
		else
		{
			$this->appUriRoot = $appUriRoot;
		}
	}
	
	function create($engine) 
	{
		$context = new LunaContext();
		$context->items = array();
		$context->view = new LunaViewContext();
		$context->response = new LunaResponseContext();		
		$context->appRoot = dirname($_SERVER["SCRIPT_FILENAME"]);	
		$context->engine = $engine;
		$context->container = new LunaContainer($engine->container); /* create a context only child container */
		$context->urlInfo = $this->createUrlInfo();		
		
		$context->propertyBag = array();		
		$context->flash = new LunaFlashArrayObject();
		$context->flash->read();			
			
		return $context;
	}
	
	function release($context)
	{
		if (isset($context->flash)) 
			$context->flash->write();
	}
	
	function createUrlInfo()
	{
		$urlInfo = new LunaUrlInfo();
		$urlInfo->raw = $_SERVER["REQUEST_URI"];
		$urlInfo->host = $_SERVER["HTTP_HOST"];
		$urlInfo->port = $_SERVER["SERVER_PORT"];	
		$urlInfo->method = $_SERVER["REQUEST_METHOD"];
		$urlInfo->rewrite = (stripos($_SERVER["REQUEST_URI"], $_SERVER["SCRIPT_NAME"]) === false);
		$urlInfo->rootPath = dirname($_SERVER["SCRIPT_NAME"]); 
		$urlInfo->basePath = $urlInfo->rewrite ? $urlInfo->rootPath : $_SERVER["SCRIPT_NAME"];
		
		/* determine path */
		$segments = explode("?", $_SERVER["REQUEST_URI"]); 
		$path = $segments[0]; /* strip out the query string */			
		
		if ($this->appUriRoot !== false)
		{
			if (stripos($path, $this->appUriRoot) === 0)		
				$urlInfo->path = substr($path, strlen($this->appUriRoot));		
			else		
				$urlInfo->path = $path;
		}
		else
		{
			/* we can only determine the true path if rewrite is false, otherwise we assume it's the full request path */
			if ($urlInfo->rewrite === false)
				$urlInfo->path = substr($path, strlen($_SERVER["SCRIPT_NAME"]));
			else
				$urlInfo->path = $path;
		}
			
		/* just want query string parameters */
		$parameters = explode("&", $_SERVER["QUERY_STRING"]);
		foreach ($parameters as $parameter)
		{
			$parts = explode("=", $parameter);
			if (count($parts) == 0)
				continue;
			$urlInfo->query[urldecode($parts[0])] = (count($parts) > 1) ? urldecode($parts[1]) : "";			
		}
		
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
			$urlInfo->secure = true;
			
		return $urlInfo;
	}
}

?>