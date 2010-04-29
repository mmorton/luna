<?php

class LunaTemplateViewExtension 
{
	private $engine;
	private $context;	
	
	public function __construct($engine, $context)
	{
		$this->engine = $engine;
		$this->context = $context;		
	}
	
	public function render($name, $variables = array())
	{
		// TODO: make implementation more robust
		if (count($variables) == 0)
		{		
			return $this->engine->renderTemplate($this->context, $name);
		}
		
		$original =& $this->context->view->propertyBag;
		
		$this->context->view->propertyBag = array_merge($original, $variables);
		
		$result = $this->engine->renderTemplate($this->context, $name);
		
		$this->context->view->propertyBag =& $original;
		
		return $result;
	}
}

?>