<?php

class LunaTemplateViewEngine extends LunaViewEngine
{
	public function __construct(ILunaViewLoader $viewLoader, $viewPaths = array())
	{
		parent::__construct(
				$viewLoader, 
				array_merge(
					$viewPaths, 
					array(
						"{area}/{controller}/{view}.phtml", 
						"{controller}/{view}.phtml",
						"{view}.phtml")
					)
				);		
	}
	
	protected function processTemplate($context, $template, $layout)
	{	
		$phtml = LunaTemplate::parse($template);
		
		$propertyBag = $context->propertyBag; /* copies the property bag */
		$propertyBag["context"] = $context;
		$propertyBag["flash"] = $context->flash;
		$propertyBag["template"] = new LunaTemplateViewExtension($this, $context);
		$propertyBag["childContent"] = $phtml->render($propertyBag);
		
		if ($layout)
		{
			$phtml = LunaTemplate::parse($layout);
			return $phtml->render($propertyBag);			
		}
		else
		{
			return $propertyBag["childContent"];
		}				
	}		
}

?>