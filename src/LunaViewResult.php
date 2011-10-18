<?php

class LunaViewResult implements ILunaActionResult
{
	public $view;
	public $layout;
	
	public function __construct($view, $layout)
	{
		$this->view = $view;
		$this->layout = $layout;
	}
	
	public function execute(LunaContext $context) 
	{
		$context->response->content[] = $context->container->viewEngineManager->renderTemplate(
			$context,
			$context->view->selectedView,
			$context->view->selectedLayout
		);
	}
}

?>