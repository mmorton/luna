<?php

class LunaEngine
{
    /**
     * @var $configuration ILunaConfiguration
     */
	public $configuration;
    /**
     * @var $container ILunaContainer
     */
	public $container;
    /**
     * @var $contextFactory ILunaContextFactory
     */
    public $contextFactory;
    /**
     * @var $routingEngine ILunaRoutingEngine
     */
    public $routingEngine;
    /**
     * @var $viewEngineManager ILunaViewEngineManager
     */
    public $viewEngineManager;
	
	public function __construct(ILunaConfiguration $configuration)
	{
		$this->configuration = $configuration;	
	}
	
	public function initialize() 
	{				
		$this->createContainer();

        $this->contextFactory = $this->container->getComponentFor("ILunaContextFactory");
        $this->routingEngine = $this->container->getComponentFor("ILunaRoutingEngine");
        $this->viewEngineManager = $this->container->getComponentFor("ILunaViewEngineManager");

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

        /* load core components */
		if (file_exists(dirname(__FILE__)."/configuration/components.json"))
		{
			$components = json_decode(file_get_contents(dirname(__FILE__)."/configuration/components.json"), true);
			foreach ($components as $name => $value)
                $this->container->addComponent($name, $value);
		}

		/* load configured components */	
		if (($components = $this->configuration->getSection("components")) !== false)
			foreach ($components as $name => $value)
				$this->container->addComponent($name, $value);
	}
	
	protected function createRoutes()
	{
		$this->routingEngine->load($this->configuration->getSection("routes"));
	}
	
	protected function createContext() 
	{
		return $this->contextFactory->create($this);
	}
	
	protected function releaseContext($context)
	{
		$this->contextFactory->release($context);
	}
	
	protected function processContext($context)
	{							
		$context->route = $this->routingEngine->find($context->request);
		if ($context->route === false)
			return $this->raiseSystemError($context, 404, "No route.");
		
		foreach ($context->route->getParameters() as $name => $value)
			$context->request->__set($name, $value);
		
		try
		{
            /**
             * @var $actionDispatcher ILunaActionDispatcher
             */
			$actionDispatcher = $context->container->getComponentFor($context->route->getDispatcherType(), false, array("context" => $context));
			
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
			
			$context->response->content[] = $this->viewEngineManager->renderTemplate(
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