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
		$context->request = $this->createRequestContext();
		
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
	
	function createRequestContext()
	{
		$request = new LunaRequestContext();
		$request->raw = $_SERVER["REQUEST_URI"];
		$request->host = $_SERVER["HTTP_HOST"];
		$request->port = $_SERVER["SERVER_PORT"];
		$request->method = $_SERVER["REQUEST_METHOD"];
		$request->rewrite = (stripos($_SERVER["REQUEST_URI"], $_SERVER["SCRIPT_NAME"]) === false);
		$request->rootPath = dirname($_SERVER["SCRIPT_NAME"]);
		$request->basePath = $request->rewrite ? $request->rootPath : $_SERVER["SCRIPT_NAME"];
		
		/* determine path */
		$segments = explode("?", $_SERVER["REQUEST_URI"]); 
		$path = $segments[0]; /* strip out the query string */			
		
		if ($this->appUriRoot !== false)
		{
			if (stripos($path, $this->appUriRoot) === 0)		
				$request->path = substr($path, strlen($this->appUriRoot));
			else		
				$request->path = $path;
		}
		else
		{
			/* we can only determine the true path if rewrite is false, otherwise we assume it's the full request path */
			if ($request->rewrite === false)
				$request->path = substr($path, strlen($_SERVER["SCRIPT_NAME"]));
			else
				$request->path = $path;
		}
			
		/* just want query string parameters */
		$parameters = explode("&", $_SERVER["QUERY_STRING"]);
		foreach ($parameters as $parameter)
		{
			$parts = explode("=", $parameter);
			if (count($parts) == 0)
				continue;
			$request->query[urldecode($parts[0])] = (count($parts) > 1) ? urldecode($parts[1]) : "";
		}
		
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off")
			$request->secure = true;
			
		return $request;
	}
}

?>