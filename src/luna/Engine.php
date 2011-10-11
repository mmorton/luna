<?php

class LunaEngine
{
	public $container;
	public $configuration;
	
	public function __construct(ILunaConfiguration $configuration)
	{
		$this->configuration = $configuration;	
	}
	
	public function initialize() 
	{				
		$this->createContainer();
		$this->createRoutes();		
	}
	
	protected function createContainer() 
	{
		$this->container = new LunaContainer();				
		$this->container->addComponent(
			"configuration", 
			array(
				"serviceType" => array("ILunaConfiguration"),
				"classType" => array(get_class($this->configuration)),
				"singleton" => true
			), 
			$this->configuration);
		
		/* load configured components */	
		if (($components = $this->container->configuration->getSection("components")) !== false)		
			foreach ($components as $name => $value)
				$this->container->addComponent($name, $value);					
		
		/* load core components */
		if (file_exists(dirname(__FILE__)."/configuration/components.yml"))
		{
			$components = Spyc::YAMLLoad(dirname(__FILE__)."/configuration/components.yml");
			foreach ($components as $name => $value)
				if ($this->container->hasComponent($name) == false) /* ensure service was not added by configuration */
					$this->container->addComponent($name, $value);	
		}
	}
	
	protected function createRoutes()
	{
		$this->container->routingEngine->load($this->container->configuration->getSection("routes"));
	}
	
	protected function createContext() 
	{
		return $this->container->contextFactory->create($this);
	}
	
	protected function releaseContext($context)
	{
		$this->container->contextFactory->release($context);
	}
	
	protected function processContext($context)
	{							
		$context->route = $this->container->routingEngine->find($context->urlInfo->path);		
		if ($context->route === false)
			return $this->raiseSystemError($context, 404, "No route.");
		
		foreach ($context->route->getParameters() as $name => $value)
			$context->urlInfo->__set($name, $value);					
		
		try
		{	
			$actionDispatcher = $this->container->getComponent($context->route->getDispatcher(), false, array("context" => $context));						
			
			if ($actionDispatcher->canDispatch($context) === false)		
				return $this->raiseSystemError($context, 404, "Can't dispatch.");																
			
			ob_start();	
			$actionResult = $actionDispatcher->dispatch($context);		
			$actionContents = ob_get_contents();
			ob_end_clean();			
			
			$context->response->content[] = $actionContents;
			
			if ($context->response->redirect !== false)
			{
				return $this->sendResponse($context);
			}
			
			if ($context->view->bypass === true || $context->view->selectedView === false || isset($actionResult))
			{
				if (isset($actionResult))
				{
					if ($actionResult instanceof ILunaActionResult)
						$actionResult->execute($context);				
					else if (is_string($actionResult))
						$context->response->content[] = $actionResult;
				}
				
				return $this->sendResponse($context);
			}															
			
			$context->response->content[] = $this->container->viewEngineManager->renderTemplate(
				$context,
				$context->view->selectedView,
				$context->view->selectedLayout
				);
			
			return $this->sendResponse($context);																								
		}
		catch (LunaException $e)
		{
			return $this->raiseSystemError($context, $e->getStatusCode(), false, $e);
		}
		catch (Exception $e)
		{			
			return $this->raiseSystemError($context, 500, false, $e);
		}	
	}
		
	public function processRequest()
	{				
		$context = $this->createContext();
		
		$result = $this->processContext($context);
		
		$this->releaseContext($context);
		
		return $result;						
	}
	
	function sendResponse($context)
	{
		if ($context->response->redirect)
		{
			header("Location: {$context->response->redirect}");
		}
		else
		{				
			if ($context->response->statusCode !== false)
			{
				header(
					sprintf(
						"HTTP/1.1 %d %s", 
						$context->response->statusCode, 
						$context->response->statusMessage 
							? $context->response->statusMessage 
							: ""
					)
				);				
			}
			
			if ($context->response->contentType !== false)
				header("Content-Type: {$context->response->contentType}");
					
			foreach ($context->response->content as $content)
				echo $content;
		}
	}
	
	function raiseSystemError($context, $code = 500, $message = false, $exception = false)
	{
		if ($exception) error_log($exception->getMessage());				
		
		$previousResponse = $context->response;
		$context->response = new LunaResponseContext();
		$context->response->statusCode = $code;
		
		$text = array();
		if ($message && strlen($exception) > 0)
			$text[] = $message;
		if ($exception && strlen($exception->getMessage()) > 0)
			$text[] = $exception->getMessage();
			
		if (count($text) == 0)
			$text[] = "An unhandled error occured."; /* there must be some content or the IIS FCGI module will fail */
		
		$context->response->content[] = implode("\n", $text);
		
		$this->sendResponse($context);
	}
}

?>