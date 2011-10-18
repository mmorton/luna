<?php

class LunaController implements ILunaContextAware
{
	protected $beforeFilterList = array();
	protected $afterFilterList = array();
	protected $skipFilterList = array();
    /** @var $context LunaContext */
	protected $context;
	protected $propertyBag;
	protected $flash;
	
	public function setContext($context)
    {
        $this->context = $context;
		$this->propertyBag =& $this->context->propertyBag;
		$this->flash =& $this->context->flash;
    }
	
	protected function selectView($view)
	{
		$this->context->view->selectedView = $view;
	}
	
	protected function selectLayout($layout)
	{
		$this->context->view->selectedLayout = $layout;
	}
	
	protected function cancelView()
	{
		$this->selectView(false);
	}
	
	protected function cancelLayout()
	{
		$this->selectLayout(false);
	}
	
	protected function bypassView()
	{
		$this->context->view->bypass = true;
	}
	
	protected function beforeFilter($method)
	{
		return ($this->beforeFilterList []= new LunaControllerActionFilter($method));
	}
	
	protected function afterFilter($method)
	{
		return ($this->afterFilterList []= new LunaControllerActionFilter($method));
	}
	
	protected function skipFilter($method)
	{
		return ($this->skipFilterList []= new LunaControllerActionFilter($method));
	}
	
	protected function executeFilter($filter)
	{
		if (method_exists($this, $filter->target))
			return $this->{$filter->target}();
	}
	
	public function preAction($context, $action, &$result)
	{
		/* no native $result support here */
		$skip = array();		
		foreach ($this->skipFilterList as $filter)
			if ($filter->appliesTo($action))
				$skip[$filter->target];
		
		foreach ($this->beforeFilterList as $filter)
			if ($filter->appliesTo($action) && isset($skip[$filter->target]) == false)				
				if ($this->executeFilter($filter) === false)
					return false;
	}	
	
	public function postAction($context, $action)
	{
		$skip = array();		
		foreach ($this->skipFilterList as $filter)
			if ($filter->appliesTo($action))
				$skip[$filter->target];
				
		foreach ($this->afterFilterList as $filter)
			if ($filter->appliesTo($action) && isset($skip[$filter->target]) == false)
				$this->executeFilter($filter);
	}
	
	protected function redirect($values, $options = array())
	{	
		if (is_bool($values) && $values === false)
		{
			$this->context->response->redirect = false;
		}	
		if (is_array($values)) 
		{
			$basePath = $this->context->request->basePath;
			$values = array_merge($this->context->request->getCustomProperties(), $values);
			$this->context->response->redirect = $basePath.$this->context->container->routingEngine->reverse($values);
		}
		else
		{
			$this->context->response->redirect = $values;
		}
		
		if (isset($options['anchor']))
			$this->context->response->redirect .= "#".$options['anchor'];
	}
	
	protected function redirectToAction($action, $controller = false, $options = array())
	{
		if ($controller === false)
			$this->redirect(array('action' => $action), $options);
		else
			$this->redirect(array('action' => $action, 'controller' => $controller), $options);
	}
}

?>