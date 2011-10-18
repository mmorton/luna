<?php

interface ILunaViewEngineManager
{	
	function renderTemplate($context, $templateName, $layoutName = false);
	function registerViewEngine($viewEngine);
}

?>